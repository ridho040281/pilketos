<?php

namespace Tests\Feature;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\User;
use App\Models\Voter;
use App\Support\SimpleXLSXGen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VotingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ElectionSetting::create([
            'id' => 1,
            'school_name' => 'SMA Test',
            'election_title' => 'Pemilihan Ketua OSIS',
            'academic_year' => '2026/2027',
            'is_active' => true,
            'show_quick_count' => false,
        ]);

        Candidate::create([
            'candidate_number' => 1,
            'leader_name' => 'Calon Satu',
            'co_leader_name' => 'Wakil Satu',
            'vision' => 'Visi 1',
            'mission' => 'Misi 1',
        ]);
    }

    public function test_student_can_view_bilik_login_page(): void
    {
        $response = $this->get(route('bilik.login'));
        $response->assertStatus(200);
        $response->assertSee('Bilik Suara Digital');
    }

    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->post(route('bilik.masuk'), [
            'passcode' => 'INVALID99',
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse(session()->has('voter_id'));
    }

    public function test_valid_token_authenticates_voter(): void
    {
        $voter = Voter::create([
            'name' => 'Siswa Uji',
            'class' => 'X-1',
            'passcode' => 'TEST01',
            'has_voted' => false,
        ]);

        $response = $this->post(route('bilik.masuk'), [
            'passcode' => 'TEST01',
        ]);

        $response->assertRedirect(route('bilik.suara'));
        $this->assertEquals($voter->id, session('voter_id'));
    }

    public function test_voter_can_cast_anonymous_ballot(): void
    {
        $voter = Voter::create([
            'name' => 'Siswa Uji',
            'class' => 'X-1',
            'passcode' => 'TEST02',
            'has_voted' => false,
        ]);

        $candidate = Candidate::first();

        $response = $this->withSession([
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'voter_class' => $voter->class,
        ])->post(route('bilik.coblos'), [
            'candidate_id' => $candidate->id,
        ]);

        $response->assertRedirect(route('bilik.sukses'));

        // Voter status updated
        $this->assertTrue($voter->fresh()->has_voted);
        $this->assertNotNull($voter->fresh()->voted_at);

        // Anonymous ballot stored
        $this->assertEquals(1, Ballot::where('candidate_id', $candidate->id)->count());

        // Voter session cleared
        $this->assertNull(session('voter_id'));
    }

    public function test_voter_cannot_vote_twice(): void
    {
        $voter = Voter::create([
            'name' => 'Siswa Uji',
            'class' => 'X-1',
            'passcode' => 'TEST03',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        // 1. Cannot login again with used token
        $response = $this->post(route('bilik.masuk'), [
            'passcode' => 'TEST03',
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse(session()->has('voter_id'));

        // 2. Direct POST cannot bypass if already voted
        $candidate = Candidate::first();
        $bypassResponse = $this->withSession([
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'voter_class' => $voter->class,
        ])->post(route('bilik.coblos'), [
            'candidate_id' => $candidate->id,
        ]);

        $bypassResponse->assertRedirect(route('bilik.login'));
        $this->assertEquals(0, Ballot::count());
    }

    public function test_projector_api_masks_results_when_frozen(): void
    {
        $response = $this->get(route('proyektor.api'));
        $response->assertStatus(200);
        $response->assertJson([
            'is_frozen' => true,
        ]);
        $data = $response->json();
        $this->assertNull($data['candidates'][0]['votes']);
    }

    public function test_projector_api_reveals_results_when_unfrozen(): void
    {
        ElectionSetting::current()->update(['show_quick_count' => true]);

        $response = $this->get(route('proyektor.api'));
        $response->assertStatus(200);
        $response->assertJson([
            'is_frozen' => false,
        ]);
        $data = $response->json();
        $this->assertNotNull($data['candidates'][0]['votes']);
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'admintest',
            'email' => 'admintest@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $loginResponse = $this->post(route('admin.login.submit'), [
            'login' => 'admintest',
            'password' => 'secret123',
        ]);

        $loginResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $dashResponse = $this->actingAs($user)->get(route('admin.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Dashboard Ringkasan Pemilihan');
    }

    public function test_admin_can_view_and_print_voter_cards(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'admintest2',
            'email' => 'admintest2@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        Voter::create([
            'name' => 'Siswa Kartu',
            'class' => 'XI-IPA-1',
            'passcode' => 'KRT001',
            'has_voted' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.voters.print-cards'));
        $response->assertStatus(200);
        $response->assertSee('Cetak Kartu Pemilih');
        $response->assertSee('KRT001');
        $response->assertSee('Siswa Kartu');
    }

    public function test_admin_can_create_guru_and_tendik_voters(): void
    {
        $user = User::create([
            'name' => 'Admin Test 3',
            'username' => 'admintest3',
            'email' => 'admintest3@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        // Create Guru
        $responseGuru = $this->actingAs($user)->post(route('admin.voters.store'), [
            'category' => 'guru',
            'nisn' => '197505122005011002',
            'name' => 'Drs. Hendro Wibowo, M.Pd.',
            'class' => 'Guru Matematika',
            'gender' => 'L',
        ]);
        $responseGuru->assertRedirect(route('admin.voters.index'));
        $this->assertDatabaseHas('voters', [
            'category' => 'guru',
            'name' => 'Drs. Hendro Wibowo, M.Pd.',
            'class' => 'Guru Matematika',
        ]);

        // Create Tendik
        $responseTendik = $this->actingAs($user)->post(route('admin.voters.store'), [
            'category' => 'tendik',
            'nisn' => '198804152019032008',
            'name' => 'Sri Wahyuni, S.Kom.',
            'class' => 'Staf Tata Usaha',
            'gender' => 'P',
        ]);
        $responseTendik->assertRedirect(route('admin.voters.index'));
        $this->assertDatabaseHas('voters', [
            'category' => 'tendik',
            'name' => 'Sri Wahyuni, S.Kom.',
            'class' => 'Staf Tata Usaha',
        ]);
    }

    public function test_admin_can_filter_dpt_by_category(): void
    {
        $user = User::create([
            'name' => 'Admin Test 4',
            'username' => 'admintest4',
            'email' => 'admintest4@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        Voter::create([
            'category' => 'siswa',
            'name' => 'Siswa Alpha',
            'class' => 'X-1',
            'passcode' => 'PAS001',
            'has_voted' => false,
        ]);

        Voter::create([
            'category' => 'guru',
            'name' => 'Bambang Sudarsono, S.Pd.',
            'class' => 'Guru Olahraga',
            'passcode' => 'PAS002',
            'has_voted' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.voters.index', ['category' => 'guru']));
        $response->assertStatus(200);
        $response->assertSee('Bambang Sudarsono, S.Pd.');
        $response->assertDontSee('Siswa Alpha');
    }

    public function test_teacher_voter_can_vote_in_bilik_suara(): void
    {
        $teacher = Voter::create([
            'category' => 'guru',
            'name' => 'Dra. Endang Sulistyowati',
            'class' => 'Guru Bahasa Indonesia',
            'passcode' => 'GUR001',
            'has_voted' => false,
        ]);

        $candidate = Candidate::first();

        // 1. Teacher logs into Bilik Suara
        $loginResponse = $this->post(route('bilik.masuk'), [
            'passcode' => 'GUR001',
        ]);
        $loginResponse->assertRedirect(route('bilik.suara'));
        $this->assertEquals('guru', session('voter_category'));
        $this->assertEquals('Guru', session('voter_category_label'));

        // 2. View ballot room
        $roomResponse = $this->withSession([
            'voter_id' => $teacher->id,
            'voter_name' => $teacher->name,
            'voter_class' => $teacher->class,
            'voter_category' => 'guru',
            'voter_category_label' => 'Guru',
        ])->get(route('bilik.suara'));
        $roomResponse->assertStatus(200);
        $roomResponse->assertSee('Dra. Endang Sulistyowati');
        $roomResponse->assertSee('Guru');

        // 3. Vote
        $voteResponse = $this->withSession([
            'voter_id' => $teacher->id,
            'voter_name' => $teacher->name,
            'voter_class' => $teacher->class,
            'voter_category' => 'guru',
            'voter_category_label' => 'Guru',
        ])->post(route('bilik.coblos'), [
            'candidate_id' => $candidate->id,
        ]);
        $voteResponse->assertRedirect(route('bilik.sukses'));

        $this->assertTrue($teacher->fresh()->has_voted);
        $this->assertEquals(1, Ballot::where('candidate_id', $candidate->id)->count());
    }

    public function test_api_voter_sync_supports_categories(): void
    {
        $setting = ElectionSetting::current();
        $apiKey = 'external_school_key_123';
        $setting->update(['pilketos_api_key' => $apiKey]);

        $payload = [
            [
                'kategori' => 'guru',
                'nip' => '197101012001011001',
                'nama' => 'Pak Guru Test API',
                'mapel' => 'Fisika',
                'jk' => 'L',
            ],
            [
                'category' => 'tendik',
                'nip' => '198502022010022002',
                'name' => 'Ibu Staff Test API',
                'class' => 'Administrasi',
                'gender' => 'P',
            ],
        ];

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey,
        ])->postJson(route('api.voters.sync'), $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('voters', [
            'category' => 'guru',
            'name' => 'Pak Guru Test API',
            'class' => 'Fisika',
        ]);
        $this->assertDatabaseHas('voters', [
            'category' => 'tendik',
            'name' => 'Ibu Staff Test API',
            'class' => 'Administrasi',
        ]);
    }

    public function test_dynamic_academic_years_generation_and_admin_setting_update(): void
    {
        $years = ElectionSetting::getAcademicYearsList();
        $this->assertIsArray($years);
        $this->assertCount(3, $years);

        // Ensure current year format (e.g. 2026/2027) and next year format (e.g. 2027/2028) are in the 3 years
        $currentYear = (int) date('Y');
        $expectedNext = ($currentYear + 1).'/'.($currentYear + 2);
        $this->assertContains($expectedNext, $years);

        $user = User::create([
            'name' => 'Admin Test 5',
            'username' => 'admintest5',
            'email' => 'admintest5@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        // View settings page
        $pageResponse = $this->actingAs($user)->get(route('admin.settings.edit'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee($expectedNext);

        // Update settings to next academic year
        $updateResponse = $this->actingAs($user)->put(route('admin.settings.update'), [
            'school_name' => 'MTsN 1 Blitar',
            'academic_year' => $expectedNext,
            'election_title' => 'Pemilihan Ketua & Wakil Ketua OSIS Periode '.$expectedNext,
            'is_active' => '1',
            'show_quick_count' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.settings.edit'));
        $this->assertDatabaseHas('election_settings', [
            'school_name' => 'MTsN 1 Blitar',
            'academic_year' => $expectedNext,
            'election_title' => 'Pemilihan Ketua & Wakil Ketua OSIS Periode '.$expectedNext,
        ]);
    }

    public function test_favicon_upload_fallback_and_rendering(): void
    {
        Storage::fake('public');

        $setting = ElectionSetting::current();

        // 1. Initial fallback when neither favicon nor school_logo is set -> asset('favicon.ico')
        $this->assertEquals(asset('favicon.ico'), $setting->getFaviconUrl());

        // 2. Fallback when only school_logo is set
        Storage::disk('public')->put('settings/logo.png', 'dummy content');
        $setting->update(['school_logo' => 'settings/logo.png']);
        $this->assertEquals(asset('storage/settings/logo.png'), $setting->getFaviconUrl());

        // 3. Upload custom favicon via admin
        $user = User::create([
            'name' => 'Admin Favicon Test',
            'username' => 'adminfavicon',
            'email' => 'adminfavicon@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $file = UploadedFile::fake()->image('my_favicon.png', 64, 64);

        $response = $this->actingAs($user)->put(route('admin.settings.update'), [
            'school_name' => 'SMA Favorit',
            'election_title' => 'Pemilihan Ketua OSIS',
            'academic_year' => '2026/2027',
            'favicon' => $file,
        ]);

        $response->assertRedirect(route('admin.settings.edit'));

        $setting->refresh();
        $this->assertNotNull($setting->favicon);
        Storage::disk('public')->assertExists($setting->favicon);
        $this->assertEquals(asset('storage/'.$setting->favicon), $setting->getFaviconUrl());

        // 4. Verify favicon is rendered in head of admin layout and bilik login
        $adminView = $this->actingAs($user)->get(route('admin.dashboard'));
        $adminView->assertStatus(200);
        $adminView->assertSee('rel="icon"', false);
        $adminView->assertSee($setting->favicon, false);

        $bilikView = $this->get(route('bilik.login'));
        $bilikView->assertStatus(200);
        $bilikView->assertSee('rel="icon"', false);
        $bilikView->assertSee($setting->favicon, false);

        // 5. Upload new favicon and ensure old favicon is deleted
        $oldFavicon = $setting->favicon;
        $newFile = UploadedFile::fake()->image('new_favicon.png', 32, 32);

        $response2 = $this->actingAs($user)->put(route('admin.settings.update'), [
            'school_name' => 'SMA Favorit',
            'election_title' => 'Pemilihan Ketua OSIS',
            'academic_year' => '2026/2027',
            'favicon' => $newFile,
        ]);

        $response2->assertRedirect(route('admin.settings.edit'));
        $setting->refresh();

        $this->assertNotEquals($oldFavicon, $setting->favicon);
        Storage::disk('public')->assertMissing($oldFavicon);
        Storage::disk('public')->assertExists($setting->favicon);
    }

    public function test_admin_can_create_single_chairman_candidate_without_vice(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admintest',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.candidates.store'), [
            'candidate_number' => 2,
            'leader_name' => 'Ketua Tunggal',
            'co_leader_name' => '',
            'vision' => 'Visi Ketua',
            'mission' => 'Misi Ketua',
        ]);

        $response->assertRedirect(route('admin.candidates.index'));
        $this->assertDatabaseHas('candidates', [
            'candidate_number' => 2,
            'leader_name' => 'Ketua Tunggal',
            'co_leader_name' => null,
        ]);

        $indexView = $this->actingAs($admin)->get(route('admin.candidates.index'));
        $indexView->assertStatus(200);
        $indexView->assertSee('Ketua Tunggal');
        $indexView->assertSee('Calon Tunggal (Hanya Ketua)');
    }

    public function test_bilik_suara_renders_vision_modal_and_backdrop_blur(): void
    {
        $voter = Voter::create([
            'name' => 'Siswa Modal Test',
            'class' => 'XII-IPA-1',
            'passcode' => 'MODAL01',
            'has_voted' => false,
        ]);

        $response = $this->withSession([
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'voter_class' => $voter->class,
        ])->get(route('bilik.suara'));

        $response->assertStatus(200);
        $response->assertSee('showVisionModal');
        $response->assertSee('backdrop-blur-md');
        $response->assertSee('Lihat Visi');
        $response->assertSee('COBLOS');
    }

    public function test_admin_can_download_excel_template(): void
    {
        $admin = User::create([
            'name' => 'Admin Excel',
            'username' => 'adminexcel',
            'email' => 'adminexcel@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.voters.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_dpt_pilketos.xlsx"');
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_import_voters_from_excel_xlsx(): void
    {
        $admin = User::create([
            'name' => 'Admin Excel Import',
            'username' => 'adminexcelimport',
            'email' => 'adminexcelimport@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $data = [
            ['Kategori', 'NISN', 'Nama Lengkap', 'Kelas/Unit', 'JK'],
            ['siswa', '00112233', 'Doni Pratama', 'XI-MIPA-2', 'L'],
            ['guru', '19800101', 'Bambang Supriyanto', 'Guru Fisika', 'L'],
            ['tendik', '19850505', 'Ratna Sari', 'Administrasi', 'P'],
        ];

        $xlsxContent = (string) SimpleXLSXGen::fromArray($data);
        $file = UploadedFile::fake()->createWithContent('dpt_test.xlsx', $xlsxContent);

        $response = $this->actingAs($admin)->post(route('admin.voters.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.voters.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('voters', [
            'nisn' => '00112233',
            'name' => 'Doni Pratama',
            'class' => 'XI-MIPA-2',
            'gender' => 'L',
            'category' => Voter::CATEGORY_SISWA,
        ]);

        $this->assertDatabaseHas('voters', [
            'nisn' => '19800101',
            'name' => 'Bambang Supriyanto',
            'category' => Voter::CATEGORY_GURU,
        ]);

        $this->assertDatabaseHas('voters', [
            'nisn' => '19850505',
            'name' => 'Ratna Sari',
            'category' => Voter::CATEGORY_TENDIK,
        ]);
    }

    public function test_voters_index_view_shows_import_excel_and_no_template_button_outside(): void
    {
        $admin = User::create([
            'name' => 'Admin View Test',
            'username' => 'adminviewtest',
            'email' => 'adminviewtest@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.voters.index'));

        $response->assertStatus(200);
        $response->assertSee('Import Excel');
        $response->assertDontSee('Template CSV');
        $response->assertSee('Unduh Template Excel');
    }

    public function test_admin_can_reset_votes_with_password(): void
    {
        $admin = User::create([
            'name' => 'Admin Reset Test',
            'username' => 'adminreset',
            'email' => 'adminreset@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $candidate = Candidate::first();
        $voter = Voter::create([
            'nisn' => '778899',
            'name' => 'Pemilih Reset',
            'class' => 'XII',
            'gender' => 'L',
            'category' => Voter::CATEGORY_SISWA,
            'passcode' => 'TOKEN1',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        Ballot::create([
            'candidate_id' => $candidate->id,
            'voter_category' => Voter::CATEGORY_SISWA,
            'cast_at' => now(),
        ]);

        $this->assertEquals(1, Ballot::count());
        $this->assertTrue($voter->fresh()->has_voted);

        $response = $this->actingAs($admin)->post(route('admin.voters.reset-votes'), [
            'confirm_password' => 'secret123',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, Ballot::count());
        $this->assertFalse($voter->fresh()->has_voted);
        $this->assertNull($voter->fresh()->voted_at);
    }

    public function test_admin_can_reset_votes_with_reset_keyword(): void
    {
        $admin = User::create([
            'name' => 'Admin Reset Keyword',
            'username' => 'adminresetkw',
            'email' => 'adminresetkw@test.com',
            'password' => bcrypt('secret456'),
            'role' => 'admin',
        ]);

        $candidate = Candidate::first();
        Voter::create([
            'nisn' => '778800',
            'name' => 'Pemilih Reset 2',
            'class' => 'XI',
            'gender' => 'P',
            'category' => Voter::CATEGORY_SISWA,
            'passcode' => 'TOKEN2',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        Ballot::create([
            'candidate_id' => $candidate->id,
            'voter_category' => Voter::CATEGORY_SISWA,
            'cast_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.voters.reset-votes'), [
            'confirmation' => 'reset',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, Ballot::count());
    }

    public function test_settings_page_displays_danger_zone_reset_suara(): void
    {
        $admin = User::create([
            'name' => 'Admin Settings',
            'username' => 'adminsettings',
            'email' => 'adminsettings@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $response->assertStatus(200);
        $response->assertSee('Zona Bahaya: Reset Seluruh Suara Pemilihan');
        $response->assertSee('Reset Suara Pemilihan');
    }

    public function test_used_token_cannot_be_used_again_to_login(): void
    {
        Voter::create([
            'nisn' => '11223344',
            'name' => 'Siswa Sudah Memilih',
            'class' => 'XII-IPA-1',
            'gender' => 'L',
            'category' => Voter::CATEGORY_SISWA,
            'passcode' => 'SUDAH1',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        $response = $this->post(route('bilik.masuk'), [
            'passcode' => 'SUDAH1',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak bisa digunakan lagi', session('error'));
        $this->assertFalse(session()->has('voter_id'));
    }

    public function test_voter_with_used_token_cannot_access_suara_room(): void
    {
        $voter = Voter::create([
            'nisn' => '11223355',
            'name' => 'Siswa Paksa Bilik',
            'class' => 'XII-IPA-2',
            'gender' => 'P',
            'category' => Voter::CATEGORY_SISWA,
            'passcode' => 'SUDAH2',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        $response = $this->withSession([
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'voter_class' => $voter->class,
        ])->get(route('bilik.suara'));

        $response->assertRedirect(route('bilik.login'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak bisa digunakan lagi', session('error'));
    }

    public function test_import_skips_duplicate_nisn_and_does_not_reimport(): void
    {
        $admin = User::create([
            'name' => 'Admin Test Duplicate',
            'username' => 'admindup',
            'email' => 'admindup@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Pre-existing voter with NISN
        Voter::create([
            'nisn' => '55667788',
            'name' => 'Siswa Sudah Ada',
            'class' => 'X-A',
            'gender' => 'L',
            'category' => Voter::CATEGORY_SISWA,
            'passcode' => 'EXIST1',
            'has_voted' => false,
        ]);

        $this->assertEquals(1, Voter::count());

        // Import file containing:
        // 1. Pre-existing NISN (55667788) -> must be skipped!
        // 2. New NISN (99001122) -> must be imported!
        // 3. Duplicate row of new NISN (99001122) -> must be skipped!
        $data = [
            ['Kategori', 'NISN', 'Nama Lengkap', 'Kelas/Unit', 'JK'],
            ['siswa', '55667788', 'Siswa Sudah Ada (Dobel)', 'X-A', 'L'],
            ['siswa', '99001122', 'Siswa Baru Unik', 'X-B', 'P'],
            ['siswa', '99001122', 'Siswa Baru Unik (Duplikat File)', 'X-B', 'P'],
        ];

        $xlsxContent = (string) SimpleXLSXGen::fromArray($data);
        $file = UploadedFile::fake()->createWithContent('dpt_duplicate_test.xlsx', $xlsxContent);

        $response = $this->actingAs($admin)->post(route('admin.voters.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHas('success');
        $this->assertStringContainsString('dilewati karena NISN/NIP sudah terdaftar', session('success'));

        // Total voters must be 2 (the pre-existing one and the one new unique one)
        $this->assertEquals(2, Voter::count());
        $this->assertEquals(1, Voter::where('nisn', '55667788')->count());
        $this->assertEquals(1, Voter::where('nisn', '99001122')->count());
    }

    public function test_manual_voter_store_rejects_duplicate_nisn(): void
    {
        $admin = User::create([
            'name' => 'Admin Store Dup',
            'username' => 'adminstoredup',
            'email' => 'adminstoredup@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        Voter::create([
            'nisn' => '33445566',
            'name' => 'Voter Awal',
            'class' => 'XI-1',
            'gender' => 'L',
            'category' => Voter::CATEGORY_SISWA,
            'passcode' => 'TOKENA',
            'has_voted' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.voters.store'), [
            'category' => 'siswa',
            'nisn' => '33445566',
            'name' => 'Voter Baru Dengan NISN Sama',
            'class' => 'XI-2',
            'gender' => 'L',
        ]);

        $response->assertSessionHasErrors('nisn');
        $this->assertEquals(1, Voter::where('nisn', '33445566')->count());
    }
}
