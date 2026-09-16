<?php

namespace Tests\Feature;

use App\Models\ElectionSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected ElectionSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting = ElectionSetting::create([
            'id' => 1,
            'school_name' => 'MTsN 1 Blitar',
            'election_title' => 'Pemilihan OSIS',
            'academic_year' => '2026/2027',
            'is_active' => true,
            'pilketos_api_key' => 'secret_test_key_12345',
        ]);

        $this->admin = User::create([
            'name' => 'Admin API',
            'username' => 'adminapi',
            'email' => 'adminapi@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_view_api_integration_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.api-integration.index'));
        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'api']));

        $followResponse = $this->actingAs($this->admin)->get(route('admin.settings.edit', ['tab' => 'api']));
        $followResponse->assertStatus(200);
        $followResponse->assertSee('Integrasi API Database Sekolah');
        $followResponse->assertSee('secret_test_key_12345');
    }

    public function test_admin_can_update_school_api_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.api-integration.update'), [
            'school_api_url' => 'https://madrasah.sch.id/api/v1/siswa',
            'school_api_key' => '41775c7b9361f5c7e04c835e49d7',
            'school_api_client_id' => 'client_TAKceoaT6jNt',
            'school_api_secret' => 'supersecret',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('https://madrasah.sch.id/api/v1/siswa', $this->setting->fresh()->school_api_url);
        $this->assertEquals('41775c7b9361f5c7e04c835e49d7', $this->setting->fresh()->school_api_key);
    }

    public function test_external_system_can_push_voters_with_valid_api_key(): void
    {
        $payload = [
            'students' => [
                [
                    'nisn' => '99887701',
                    'name' => 'Ahmad API',
                    'class' => 'IX-A',
                    'gender' => 'L',
                ],
                [
                    'nisn' => '99887702',
                    'name' => 'Siti API',
                    'class' => 'IX-B',
                    'gender' => 'P',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-API-KEY' => 'secret_test_key_12345',
            'Accept' => 'application/json',
        ])->postJson(route('api.voters.sync'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'summary' => [
                'inserted' => 2,
            ],
        ]);

        $this->assertDatabaseHas('voters', [
            'nisn' => '99887701',
            'name' => 'Ahmad API',
            'class' => 'IX-A',
        ]);

        // Token must be auto-generated
        $voter = Voter::where('nisn', '99887701')->first();
        $this->assertNotNull($voter->passcode);
        $this->assertEquals(6, strlen($voter->passcode));
    }

    public function test_external_system_rejected_without_valid_api_key(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'wrong_key',
            'Accept' => 'application/json',
        ])->postJson(route('api.voters.sync'), [
            'students' => [
                ['nisn' => '111', 'name' => 'Fake Student', 'class' => 'X-1'],
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error']);
        $this->assertEquals(0, Voter::count());
    }

    public function test_pilketos_can_pull_data_from_school_api(): void
    {
        $this->setting->update([
            'school_api_url' => 'https://api.sekolah.sch.id/v1/students',
            'school_api_key' => 'legacy_41775c',
        ]);

        Http::fake([
            'https://api.sekolah.sch.id/v1/students*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'nisn' => '55667701',
                        'nama' => 'Zaky Mubarok',
                        'kelas' => 'VIII-C',
                        'jk' => 'L',
                    ],
                    [
                        'nisn' => '55667702',
                        'nama' => 'Laila Majnun',
                        'kelas' => 'VIII-D',
                        'jk' => 'P',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.api-integration.pull'));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voters', [
            'nisn' => '55667701',
            'name' => 'Zaky Mubarok',
            'class' => 'VIII-C',
        ]);
        $this->assertDatabaseHas('voters', [
            'nisn' => '55667702',
            'name' => 'Laila Majnun',
            'class' => 'VIII-D',
        ]);
    }

    public function test_admin_can_view_guru_api_tab(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.edit', ['tab' => 'api_guru']));
        $response->assertStatus(200);
        $response->assertSee('Integrasi API Guru');
        $response->assertSee('client_edp3yftse3bxcrcf');
        $response->assertSee('EgUmiD5xGq1v6IdUMlTvxGModoUOoCjCIKncKu2I');
        $response->assertSee('UOcvFMOE4fPisQxFDh1W7Q77wx6glE9P87wkcOswAd6RtxVLFvSmV3rrbXGW');
    }

    public function test_admin_can_update_guru_api_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.api-integration.update-guru'), [
            'guru_api_url' => 'https://sim.sekolah.sch.id/api/v1/guru',
            'guru_api_client_id' => 'client_custom_123',
            'guru_api_secret' => 'secret_custom_456',
            'guru_api_token' => 'token_custom_789',
        ]);

        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'api_guru']));
        $response->assertSessionHas('success');

        $setting = $this->setting->fresh();
        $this->assertEquals('https://sim.sekolah.sch.id/api/v1/guru', $setting->guru_api_url);
        $this->assertEquals('client_custom_123', $setting->guru_api_client_id);
        $this->assertEquals('secret_custom_456', $setting->guru_api_secret);
        $this->assertEquals('token_custom_789', $setting->guru_api_token);
    }

    public function test_pilketos_can_pull_and_sync_guru_data(): void
    {
        $this->setting->update([
            'guru_api_url' => 'https://sim.sekolah.sch.id/api/v1/guru',
            'guru_api_client_id' => 'client_edp3yftse3bxcrcf',
            'guru_api_secret' => 'EgUmiD5xGq1v6IdUMlTvxGModoUOoCjCIKncKu2I',
            'guru_api_token' => 'UOcvFMOE4fPisQxFDh1W7Q77wx6glE9P87wkcOswAd6RtxVLFvSmV3rrbXGW',
        ]);

        Http::fake([
            'https://sim.sekolah.sch.id/api/v1/guru*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'nip' => '198001012005011001',
                        'nama' => 'Drs. H. Ahmad Dahlan, M.Pd',
                        'mapel' => 'Guru Matematika',
                        'jk' => 'L',
                    ],
                    [
                        'nip' => '198502022010012002',
                        'nama' => 'Siti Khodijah, S.Pd',
                        'mapel' => 'Guru Bahasa Inggris',
                        'jk' => 'P',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.api-integration.pull-guru'));

        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'api_guru']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('voters', [
            'nisn' => '198001012005011001',
            'name' => 'Drs. H. Ahmad Dahlan, M.Pd',
            'category' => 'guru',
            'class' => 'Guru Matematika',
            'gender' => 'L',
        ]);

        $this->assertDatabaseHas('voters', [
            'nisn' => '198502022010012002',
            'name' => 'Siti Khodijah, S.Pd',
            'category' => 'guru',
            'class' => 'Guru Bahasa Inggris',
            'gender' => 'P',
        ]);

        $setting = $this->setting->fresh();
        $this->assertNotNull($setting->last_guru_sync_at);
        $this->assertEquals(2, $setting->last_guru_sync_count);
    }

    public function test_pull_guru_data_updates_existing_teacher_without_duplication(): void
    {
        $existing = Voter::create([
            'nisn' => '198001012005011001',
            'name' => 'Ahmad Dahlan',
            'category' => 'guru',
            'class' => 'Guru Lama',
            'gender' => 'L',
            'passcode' => 'ABC123',
            'has_voted' => false,
        ]);

        $this->setting->update([
            'guru_api_url' => 'https://sim.sekolah.sch.id/api/v1/guru',
            'guru_api_token' => 'test_token',
        ]);

        Http::fake([
            'https://sim.sekolah.sch.id/api/v1/guru*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'nip' => '198001012005011001',
                        'nama' => 'Dr. H. Ahmad Dahlan, M.Pd',
                        'mapel' => 'Guru Matematika Baru',
                        'jk' => 'L',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.api-integration.pull-guru'));
        $response->assertSessionHas('success');

        $this->assertEquals(1, Voter::where('category', 'guru')->count());

        $existing->refresh();
        $this->assertEquals('Dr. H. Ahmad Dahlan, M.Pd', $existing->name);
        $this->assertEquals('Guru Matematika Baru', $existing->class);
        $this->assertEquals('ABC123', $existing->passcode); // passcode preserved
    }

    public function test_clean_duplicate_teachers_preserves_voted_and_merges_nip(): void
    {
        // Teacher 1: older ID, has NOT voted, no NIP
        $t1 = Voter::create([
            'nisn' => null,
            'name' => 'Budi Santoso, S.Pd',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Guru IPA',
            'gender' => 'L',
            'passcode' => 'PAS001',
            'has_voted' => false,
        ]);

        // Teacher 2: newer ID, HAS voted, has NIP
        $t2 = Voter::create([
            'nisn' => '197501012000011001',
            'name' => 'Budi Santoso, S.Pd',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Guru IPA',
            'gender' => 'L',
            'passcode' => 'PAS002',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        // Teacher 3: distinct teacher, should NOT be deleted
        $t3 = Voter::create([
            'nisn' => '198002022005012002',
            'name' => 'Siti Nurhaliza, M.Pd',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Guru B.Indo',
            'gender' => 'P',
            'passcode' => 'PAS003',
            'has_voted' => false,
        ]);

        $this->assertEquals(3, Voter::where('category', 'guru')->count());

        // Call Artisan Command
        $this->artisan('dpt:clean-duplicate-guru')
            ->expectsOutputToContain('Sukses menghapus 1 data duplikat guru')
            ->assertSuccessful();

        $this->assertEquals(2, Voter::where('category', 'guru')->count());

        // Verify t2 (who voted) was kept, and t1 was deleted
        $this->assertTrue(Voter::where('id', $t2->id)->exists());
        $this->assertFalse(Voter::where('id', $t1->id)->exists());
        $this->assertTrue(Voter::where('id', $t3->id)->exists());
    }

    public function test_clean_duplicate_teachers_dry_run_does_not_delete(): void
    {
        Voter::create([
            'nisn' => '111',
            'name' => 'Guru Duplikat',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Guru',
            'passcode' => 'CODE1',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => null,
            'name' => 'Guru Duplikat',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Guru',
            'passcode' => 'CODE2',
            'has_voted' => false,
        ]);

        $this->assertEquals(2, Voter::where('category', 'guru')->count());

        $this->artisan('dpt:clean-duplicate-guru', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertSuccessful();

        $this->assertEquals(2, Voter::where('category', 'guru')->count());
    }

    public function test_admin_can_trigger_clean_duplicate_teachers_via_route(): void
    {
        Voter::create([
            'nisn' => '9991',
            'name' => 'Guru Web Clean',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Guru',
            'passcode' => 'WEB1',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => null,
            'name' => 'Guru Web Clean',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Guru',
            'passcode' => 'WEB2',
            'has_voted' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.api-integration.clean-duplicate-guru'));
        $response->assertSessionHas('success');

        $this->assertEquals(1, Voter::where('category', 'guru')->where('name', 'Guru Web Clean')->count());
    }
}
