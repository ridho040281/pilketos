<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateCardColorTest extends TestCase
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
        ]);

        $this->admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@pilketos.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
    }

    public function test_candidate_card_color_palette_matches_candidate_numbers(): void
    {
        $c1 = Candidate::create([
            'candidate_number' => 1,
            'leader_name' => 'Calon Satu',
            'vision' => 'Visi 1',
            'mission' => 'Misi 1',
        ]);

        $c2 = Candidate::create([
            'candidate_number' => 2,
            'leader_name' => 'Calon Dua',
            'vision' => 'Visi 2',
            'mission' => 'Misi 2',
        ]);

        $c3 = Candidate::create([
            'candidate_number' => 3,
            'leader_name' => 'Calon Tiga',
            'vision' => 'Visi 3',
            'mission' => 'Misi 3',
        ]);

        $c4 = Candidate::create([
            'candidate_number' => 4,
            'leader_name' => 'Calon Empat',
            'vision' => 'Visi 4',
            'mission' => 'Misi 4',
        ]);

        $this->assertSame('#4f46e5', $c1->card_color);
        $this->assertSame('#059669', $c2->card_color);
        $this->assertSame('#dc2626', $c3->card_color);
        $this->assertSame('#9333ea', $c4->card_color);
    }

    public function test_candidate_custom_color_tag_is_used_when_provided(): void
    {
        $candidate = Candidate::create([
            'candidate_number' => 2,
            'leader_name' => 'Calon Custom Color',
            'vision' => 'Visi',
            'mission' => 'Misi',
            'color_tag' => '#d97706',
        ]);

        $this->assertSame('#d97706', $candidate->card_color);
    }

    public function test_bilik_suara_renders_candidate_colors(): void
    {
        Candidate::create([
            'candidate_number' => 1,
            'leader_name' => 'Calon 01',
            'vision' => 'Visi 1',
            'mission' => 'Misi 1',
            'color_tag' => '#4f46e5',
        ]);

        Candidate::create([
            'candidate_number' => 2,
            'leader_name' => 'Calon 02',
            'vision' => 'Visi 2',
            'mission' => 'Misi 2',
            'color_tag' => '#059669',
        ]);

        $voter = Voter::create([
            'nisn' => '1234567890',
            'name' => 'Budi Santoso',
            'class' => 'XII IPA 1',
            'passcode' => '123456',
            'has_voted' => false,
            'category' => 'siswa',
        ]);

        $response = $this->withSession([
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'voter_class' => $voter->class,
            'voter_category' => 'siswa',
            'voter_category_label' => 'Siswa',
        ])->get(route('bilik.suara'));

        $response->assertOk();
        $response->assertSee('#4f46e5');
        $response->assertSee('#059669');
    }
}
