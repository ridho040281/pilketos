<?php

namespace Tests\Feature;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        ElectionSetting::create([
            'id' => 1,
            'school_name' => 'SMA Kebangsaan',
            'election_title' => 'Pemilihan Ketua OSIS',
            'academic_year' => '2026/2027',
            'is_active' => true,
            'show_quick_count' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@pilketos.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        Candidate::create([
            'candidate_number' => 1,
            'leader_name' => 'Ahmad Calon',
            'co_leader_name' => 'Budi Wakil',
            'vision' => 'Visi A',
            'mission' => 'Misi A',
        ]);
    }

    public function test_guest_cannot_access_laporan_page(): void
    {
        $response = $this->get(route('admin.laporan.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_access_laporan_page_with_both_tabs(): void
    {
        Voter::create([
            'nisn' => '1001',
            'name' => 'Siswa Hadir',
            'category' => 'siswa',
            'class' => 'XII-IPA-1',
            'passcode' => 'ABC123',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        Voter::create([
            'nisn' => '1002',
            'name' => 'Siswa Belum',
            'category' => 'siswa',
            'class' => 'XII-IPA-2',
            'passcode' => 'XYZ789',
            'has_voted' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.index'));

        $response->assertStatus(200);
        $response->assertSee('Laporan dan Rekapitulasi Pemilihan');
        $response->assertSee('Daftar Hadir Pemilih');
        $response->assertSee('Berita Acara Pleno');
        $response->assertSee('Siswa Hadir');
        $response->assertSee('Siswa Belum');
    }

    public function test_voters_appear_even_when_zero_have_voted(): void
    {
        Voter::create([
            'nisn' => '5001',
            'name' => 'Budi Belum Memilih',
            'category' => 'siswa',
            'class' => 'X-1',
            'passcode' => 'TEST01',
            'has_voted' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.index'));

        $response->assertStatus(200);
        $response->assertSee('Budi Belum Memilih');
        $response->assertSee('Belum Memilih');
    }

    public function test_admin_can_filter_daftar_hadir(): void
    {
        Voter::create([
            'nisn' => '2001',
            'name' => 'Dewi Lestari',
            'category' => 'siswa',
            'class' => 'XI-RPL-1',
            'passcode' => 'PASS01',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        Voter::create([
            'nisn' => '2002',
            'name' => 'Pak Joko',
            'category' => 'guru',
            'class' => 'Guru',
            'passcode' => 'PASS02',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        Voter::create([
            'nisn' => '2003',
            'name' => 'Siti Belum',
            'category' => 'siswa',
            'class' => 'XI-RPL-1',
            'passcode' => 'PASS03',
            'has_voted' => false,
        ]);

        // Default all shows both voted and unvoted
        $responseAll = $this->actingAs($this->admin)->get(route('admin.laporan.index'));
        $responseAll->assertSee('Dewi Lestari');
        $responseAll->assertSee('Siti Belum');

        // Filter by status voted
        $responseVoted = $this->actingAs($this->admin)->get(route('admin.laporan.index', [
            'status' => 'voted',
        ]));
        $responseVoted->assertSee('Dewi Lestari');
        $responseVoted->assertDontSee('Siti Belum');

        // Filter by status unvoted
        $responseUnvoted = $this->actingAs($this->admin)->get(route('admin.laporan.index', [
            'status' => 'unvoted',
        ]));
        $responseUnvoted->assertSee('Siti Belum');
        $responseUnvoted->assertDontSee('Dewi Lestari');

        // Filter by category guru
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.index', [
            'tab' => 'daftar-hadir',
            'category' => 'guru',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Pak Joko');
        $response->assertDontSee('Dewi Lestari');

        // Filter by search name
        $responseSearch = $this->actingAs($this->admin)->get(route('admin.laporan.index', [
            'tab' => 'daftar-hadir',
            'search' => 'Dewi',
        ]));

        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Dewi Lestari');
        $responseSearch->assertDontSee('Pak Joko');
    }

    public function test_admin_can_print_daftar_hadir(): void
    {
        Voter::create([
            'nisn' => '3001',
            'name' => 'Pemilih Cetak',
            'category' => 'siswa',
            'class' => 'X-1',
            'passcode' => 'PASS99',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.cetak-daftar-hadir'));

        $response->assertStatus(200);
        $response->assertSee('DAFTAR HADIR PEMILIH DIGITAL (E-VOTING)');
        $response->assertSee('Pemilih Cetak');
        $response->assertSee('Petugas Presensi (KPPS)');
    }

    public function test_admin_can_export_daftar_hadir_excel(): void
    {
        Voter::create([
            'nisn' => '4001',
            'name' => 'Pemilih Export',
            'category' => 'siswa',
            'class' => 'X-2',
            'passcode' => 'EXP123',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.export-daftar-hadir'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_can_print_berita_acara(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.cetak-berita-acara'));

        $response->assertStatus(200);
        $response->assertSee('BERITA ACARA RAPAT PLENO PENGHITUNGAN SUARA');
        $response->assertSee('Ahmad Calon');
    }

    public function test_legacy_berita_acara_route_redirects_to_laporan_tab(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.berita-acara.index'));

        $response->assertRedirect(route('admin.laporan.index', ['tab' => 'berita-acara']));
    }

    public function test_admin_can_view_hitung_cepat_tab_with_category_and_class_stats(): void
    {
        // Guru
        Voter::create([
            'nisn' => 'G01',
            'name' => 'Guru Matematika 1',
            'category' => 'guru',
            'class' => 'Matematika',
            'passcode' => 'GURU01',
            'has_voted' => true,
            'voted_at' => now(),
        ]);
        Voter::create([
            'nisn' => 'G02',
            'name' => 'Guru Biologi 1',
            'category' => 'guru',
            'class' => 'Biologi',
            'passcode' => 'GURU02',
            'has_voted' => false,
        ]);

        // Tendik
        Voter::create([
            'nisn' => 'T01',
            'name' => 'Tata Usaha 1',
            'category' => 'tendik',
            'class' => 'Staf TU',
            'passcode' => 'TEND01',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        // Siswa
        Voter::create([
            'nisn' => 'S01',
            'name' => 'Siswa 1',
            'category' => 'siswa',
            'class' => 'X-A',
            'passcode' => 'SISW01',
            'has_voted' => true,
            'voted_at' => now(),
        ]);
        Voter::create([
            'nisn' => 'S02',
            'name' => 'Siswa 2',
            'category' => 'siswa',
            'class' => 'X-B',
            'passcode' => 'SISW02',
            'has_voted' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.index', ['tab' => 'hitung-cepat']));

        $response->assertStatus(200);
        $response->assertSee('Hitung Cepat');
        $response->assertSee('Grafik Live');
        $response->assertSee('Perolehan Suara & Statistik Partisipasi Pemilih', false);
        $response->assertSee('Tenaga Kependidikan (Tendik)');
        $response->assertSee('X-A');
        $response->assertSee('X-B');
        $response->assertSee('Partisipasi Guru per Mapel');
        $response->assertSee('Partisipasi Tendik per Unit Kerja');
        $response->assertSee('paslonChart');
        $response->assertSee('classesBarChart');
        $response->assertSee('categoryBarChart');
        $response->assertSee('categoryDoughnutChart');
        $response->assertSee('classesPaslonChart');
    }

    public function test_hitung_cepat_displays_candidate_percentages_per_class_and_category(): void
    {
        $c1 = Candidate::where('candidate_number', 1)->first();
        $c2 = Candidate::create([
            'candidate_number' => 2,
            'leader_name' => 'Calon Beta',
            'co_leader_name' => 'Wakil Beta',
            'vision' => 'Visi Beta',
            'mission' => 'Misi Beta',
            'card_color' => '#3b82f6',
        ]);

        // Voter DPT
        Voter::create([
            'nisn' => 'S101',
            'name' => 'Siswa 1',
            'category' => Voter::CATEGORY_SISWA,
            'class' => 'XII-IPA-1',
            'passcode' => 'PASS101',
            'has_voted' => true,
        ]);
        Voter::create([
            'nisn' => 'S102',
            'name' => 'Siswa 2',
            'category' => Voter::CATEGORY_SISWA,
            'class' => 'XII-IPA-1',
            'passcode' => 'PASS102',
            'has_voted' => true,
        ]);
        Voter::create([
            'nisn' => 'S103',
            'name' => 'Siswa 3',
            'category' => Voter::CATEGORY_SISWA,
            'class' => 'XII-IPA-1',
            'passcode' => 'PASS103',
            'has_voted' => false,
        ]);

        // Ballots with class and category
        Ballot::create([
            'candidate_id' => $c1->id,
            'voter_category' => Voter::CATEGORY_SISWA,
            'voter_class' => 'XII-IPA-1',
        ]);
        Ballot::create([
            'candidate_id' => $c1->id,
            'voter_category' => Voter::CATEGORY_SISWA,
            'voter_class' => 'XII-IPA-1',
        ]);
        Ballot::create([
            'candidate_id' => $c2->id,
            'voter_category' => Voter::CATEGORY_GURU,
            'voter_class' => 'Matematika',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.index', ['tab' => 'hitung-cepat']));

        $response->assertStatus(200);
        $response->assertSee('XII-IPA-1');
        $response->assertSee('Ahmad Calon');
        $response->assertSee('Calon Beta');
        $response->assertSee('Perolehan Suara Pasangan Calon Berdasarkan Kategori Pemilih');
        $response->assertSee('Grafik & Rekapitulasi Perolehan Suara Paslon Per Kelas Siswa', false);
        $response->assertSee('classesPaslonChart');

        $classesStats = $response->viewData('classesStats');
        $classItem = $classesStats->firstWhere('class', 'XII-IPA-1');

        $this->assertNotNull($classItem);
        $this->assertEquals(3, $classItem->total);
        $this->assertEquals(2, $classItem->voted);
        $this->assertEquals(2, $classItem->total_ballots);
        // Candidate 1 won 2 of 2 votes in class XII-IPA-1 => 100%
        $this->assertEquals(2, $classItem->candidate_results[$c1->id]['votes']);
        $this->assertEquals(100.0, $classItem->candidate_results[$c1->id]['percentage']);
        $this->assertEquals($c1->id, $classItem->leading_candidate_id);

        $categoryBreakdown = $response->viewData('categoryCandidateBreakdown');
        $this->assertEquals(2, $categoryBreakdown['siswa']['candidates'][$c1->id]['votes']);
        $this->assertEquals(1, $categoryBreakdown['guru']['candidates'][$c2->id]['votes']);
    }

    public function test_admin_can_save_headmaster_and_pembina_in_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'school_name' => 'MTsN 1 BLITAR',
            'academic_year' => '2026/2027',
            'election_title' => 'Pemilihan Ketua OSIS Periode 2026/2027',
            'headmaster_name' => 'Drs. H. Ahmad Fauzi, M.Pd',
            'headmaster_nip' => '197001011995031002',
            'pembina_name' => 'Siti Rahmawati, S.Pd',
            'pembina_nip' => '198502152010012025',
            'is_active' => '1',
            'show_quick_count' => '1',
            'show_qr_code' => '1',
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $response->assertSessionHas('success');

        $setting = ElectionSetting::current();
        $this->assertEquals('Drs. H. Ahmad Fauzi, M.Pd', $setting->headmaster_name);
        $this->assertEquals('197001011995031002', $setting->headmaster_nip);
        $this->assertEquals('Siti Rahmawati, S.Pd', $setting->pembina_name);
        $this->assertEquals('198502152010012025', $setting->pembina_nip);
        $this->assertEquals('Kepala Madrasah', $setting->headmaster_title);
    }

    public function test_berita_acara_and_laporan_displays_headmaster_and_pembina_signatures(): void
    {
        $setting = ElectionSetting::current();
        $setting->update([
            'school_name' => 'MTsN 1 BLITAR',
            'headmaster_name' => 'Drs. H. Ahmad Fauzi, M.Pd',
            'headmaster_nip' => '197001011995031002',
            'pembina_name' => 'Siti Rahmawati, S.Pd',
            'pembina_nip' => '198502152010012025',
        ]);

        // 1. Preview in Laporan tab berita-acara
        $resTab = $this->actingAs($this->admin)->get(route('admin.laporan.index', ['tab' => 'berita-acara']));
        $resTab->assertStatus(200);
        $resTab->assertSee('Kepala Madrasah,');
        $resTab->assertSee('Drs. H. Ahmad Fauzi, M.Pd');
        $resTab->assertSee('197001011995031002');
        $resTab->assertSee('Siti Rahmawati, S.Pd');
        $resTab->assertSee('198502152010012025');

        // 2. Printable Berita Acara A4
        $resPrintBA = $this->actingAs($this->admin)->get(route('admin.laporan.cetak-berita-acara'));
        $resPrintBA->assertStatus(200);
        $resPrintBA->assertSee('Kepala Madrasah,');
        $resPrintBA->assertSee('Drs. H. Ahmad Fauzi, M.Pd');
        $resPrintBA->assertSee('197001011995031002');

        // 3. Printable Daftar Hadir A4
        $resPrintDH = $this->actingAs($this->admin)->get(route('admin.laporan.cetak-daftar-hadir'));
        $resPrintDH->assertStatus(200);
        $resPrintDH->assertSee('Siti Rahmawati, S.Pd');
        $resPrintDH->assertSee('198502152010012025');
    }
}
