<?php

namespace Tests\Feature;

use App\Models\ElectionSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoterFilterTest extends TestCase
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

        // Create sample voters across different categories
        Voter::create([
            'nisn' => '1001',
            'name' => 'Siswa Andi',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '71',
            'passcode' => 'PASSSISWA1',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '1002',
            'name' => 'Siswa Budi',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '72',
            'passcode' => 'PASSSISWA2',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '2001',
            'name' => 'Guru Joko, S.Pd.',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Matematika',
            'passcode' => 'PASSGURU1',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '2002',
            'name' => 'Guru Siti, M.Pd.',
            'category' => Voter::CATEGORY_GURU,
            'class' => 'Bahasa Indonesia',
            'passcode' => 'PASSGURU2',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '3001',
            'name' => 'Tendik Hendro',
            'category' => Voter::CATEGORY_TENDIK,
            'class' => 'Tata Usaha',
            'passcode' => 'PASSTENDIK1',
            'has_voted' => false,
        ]);
    }

    public function test_voters_index_provides_category_classes_mapping(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.voters.index'));

        $response->assertOk();
        $response->assertViewHas('categoryClasses');
        $response->assertViewHas('classes');

        $categoryClasses = $response->viewData('categoryClasses');

        $this->assertContains('71', $categoryClasses[Voter::CATEGORY_SISWA]);
        $this->assertContains('72', $categoryClasses[Voter::CATEGORY_SISWA]);
        $this->assertNotContains('Matematika', $categoryClasses[Voter::CATEGORY_SISWA]);

        $this->assertContains('Matematika', $categoryClasses[Voter::CATEGORY_GURU]);
        $this->assertContains('Bahasa Indonesia', $categoryClasses[Voter::CATEGORY_GURU]);
        $this->assertNotContains('71', $categoryClasses[Voter::CATEGORY_GURU]);

        $this->assertContains('Tata Usaha', $categoryClasses[Voter::CATEGORY_TENDIK]);
    }

    public function test_voters_index_filters_by_category_guru(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.voters.index', [
            'category' => 'guru',
        ]));

        $response->assertOk();
        $response->assertSee('Guru Joko, S.Pd.');
        $response->assertSee('Guru Siti, M.Pd.');
        $response->assertDontSee('Siswa Andi');
        $response->assertDontSee('Tendik Hendro');
    }

    public function test_voters_index_filters_by_category_guru_and_mapel(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.voters.index', [
            'category' => 'guru',
            'class' => 'Matematika',
        ]));

        $response->assertOk();
        $response->assertSee('Guru Joko, S.Pd.');
        $response->assertDontSee('Guru Siti, M.Pd.');
        $response->assertDontSee('Siswa Andi');
    }

    public function test_print_cards_scopes_classes_to_selected_category(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.voters.print-cards', [
            'category' => 'guru',
        ]));

        $response->assertOk();
        $response->assertViewHas('classes');

        $classes = $response->viewData('classes');
        $this->assertContains('Matematika', $classes);
        $this->assertContains('Bahasa Indonesia', $classes);
        $this->assertNotContains('71', $classes);
        $this->assertNotContains('Tata Usaha', $classes);
    }

    public function test_print_cards_resets_mismatched_class_filter(): void
    {
        // When category is guru but class is 71 (student class), it should ignore/clear the invalid class
        $response = $this->actingAs($this->admin)->get(route('admin.voters.print-cards', [
            'category' => 'guru',
            'class' => '71',
        ]));

        $response->assertOk();
        $this->assertNull($response->viewData('class'));
        $this->assertEquals(2, $response->viewData('voterCards')->count());
    }

    public function test_laporan_index_provides_category_classes_mapping(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.index'));

        $response->assertOk();
        $response->assertViewHas('categoryClasses');
        $response->assertViewHas('classes');

        $categoryClasses = $response->viewData('categoryClasses');
        $this->assertContains('Matematika', $categoryClasses[Voter::CATEGORY_GURU]);
        $this->assertContains('71', $categoryClasses[Voter::CATEGORY_SISWA]);
    }
}
