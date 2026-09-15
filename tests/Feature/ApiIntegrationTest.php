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
        $response->assertStatus(200);
        $response->assertSee('Integrasi API Database Sekolah');
        $response->assertSee('secret_test_key_12345');
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
}
