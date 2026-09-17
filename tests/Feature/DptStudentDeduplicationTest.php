<?php

namespace Tests\Feature;

use App\Models\ElectionSetting;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DptStudentDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ElectionSetting::create([
            'id' => 1,
            'school_name' => 'MTsN 1 Blitar',
            'election_title' => 'Pemilihan Ketua OSIS',
            'academic_year' => '2026/2027',
            'is_active' => true,
            'show_quick_count' => true,
        ]);
    }

    public function test_check_siswa_command_displays_class_breakdown(): void
    {
        Voter::create([
            'nisn' => '1001',
            'name' => 'Ahmad Dani',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '71',
            'passcode' => 'PAS111',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '1002',
            'name' => 'Budi Santoso',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '71',
            'passcode' => 'PAS112',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '1003',
            'name' => 'Citra Lestari',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '81',
            'passcode' => 'PAS113',
            'has_voted' => true,
        ]);

        $this->artisan('dpt:check-siswa')
            ->expectsOutputToContain('TOTAL SISWA TERDAFTAR: 3 Siswa')
            ->expectsOutputToContain('71')
            ->expectsOutputToContain('2 Siswa')
            ->expectsOutputToContain('81')
            ->expectsOutputToContain('1 Siswa')
            ->expectsOutputToContain('Tidak ada NISN kembar/duplikat')
            ->assertSuccessful();
    }

    public function test_check_siswa_detects_duplicate_nisn_and_names(): void
    {
        Voter::create([
            'nisn' => '9999',
            'name' => 'Fajar Pratama',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '71',
            'passcode' => 'PAS991',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '9999',
            'name' => 'Fajar Pratama',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '72',
            'passcode' => 'PAS992',
            'has_voted' => false,
        ]);

        $this->artisan('dpt:check-siswa')
            ->expectsOutputToContain('Ditemukan NISN Kembar/Duplikat')
            ->expectsOutputToContain('9999')
            ->assertSuccessful();
    }

    public function test_clean_duplicate_siswa_dry_run_does_not_delete(): void
    {
        Voter::create([
            'nisn' => '8888',
            'name' => 'Gita Gutawa',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '71',
            'passcode' => 'PAS881',
            'has_voted' => false,
        ]);

        Voter::create([
            'nisn' => '8888',
            'name' => 'Gita Gutawa',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '72',
            'passcode' => 'PAS882',
            'has_voted' => false,
        ]);

        $this->artisan('dpt:clean-duplicate-siswa', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertSuccessful();

        $this->assertEquals(2, Voter::where('category', Voter::CATEGORY_SISWA)->count());
    }

    public function test_clean_duplicate_siswa_removes_duplicate_and_preserves_vote_status(): void
    {
        $voter1 = Voter::create([
            'nisn' => null,
            'name' => 'Hendra Setiawan',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '91',
            'passcode' => 'PAS771',
            'has_voted' => false,
        ]);

        $voter2 = Voter::create([
            'nisn' => '7777777777',
            'name' => 'Hendra Setiawan',
            'category' => Voter::CATEGORY_SISWA,
            'class' => '91',
            'passcode' => 'PAS772',
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        $this->assertEquals(2, Voter::where('category', Voter::CATEGORY_SISWA)->count());

        $this->artisan('dpt:clean-duplicate-siswa')
            ->expectsOutputToContain('Sukses menghapus 1 data duplikat siswa')
            ->assertSuccessful();

        $this->assertEquals(1, Voter::where('category', Voter::CATEGORY_SISWA)->count());

        $remaining = Voter::where('category', Voter::CATEGORY_SISWA)->first();
        $this->assertTrue($remaining->has_voted);
        $this->assertEquals('7777777777', $remaining->nisn);
    }
}
