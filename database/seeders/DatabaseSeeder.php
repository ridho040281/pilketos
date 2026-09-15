<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator Pilketos',
                'email' => 'admin@sekolah.sch.id',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ]
        );

        // 2. Election Setting
        ElectionSetting::updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'SMA Negeri 1 Teladan',
                'election_title' => 'Pemilihan Ketua & Wakil Ketua OSIS Periode 2026/2027',
                'academic_year' => '2026/2027',
                'start_time' => now()->startOfDay(),
                'end_time' => now()->endOfDay(),
                'is_active' => true,
                'show_quick_count' => false,
            ]
        );

        // 3. Candidates
        $candidates = [
            [
                'candidate_number' => 1,
                'leader_name' => 'Fathur Rahman',
                'co_leader_name' => 'Nabila Putri',
                'color_tag' => '#4f46e5', // Indigo
                'vision' => 'Mewujudkan OSIS SMA Negeri 1 yang Proaktif, Inovatif, dan Kolaboratif Berbasis Digitalisasi Sekolah.',
                'mission' => "1. Menumbuhkan budaya literasi digital dan kreativitas siswa melalui event modern.\n2. Mengoptimalkan aspirasi siswa dengan kotak saran digital terintegrasi.\n3. Mempererat hubungan kekeluargaan antar ekskul dan angkatan.",
            ],
            [
                'candidate_number' => 2,
                'leader_name' => 'Dimas Arya Pratama',
                'co_leader_name' => 'Siti Nurhaliza',
                'color_tag' => '#059669', // Emerald
                'vision' => 'Membangun Generasi Muda Sekolah yang Berkarakter Luhur, Berprestasi, dan Berjiwa Wirausaha.',
                'mission' => "1. Menyelenggarakan pekan wirausaha dan karya seni siswa tahunan.\n2. Meningkatkan pembinaan bakat akademik dan non-akademik menuju ajang perlombaan.\n3. Menegakkan kedisiplinan dan kepedulian sosial di lingkungan sekolah.",
            ],
            [
                'candidate_number' => 3,
                'leader_name' => 'Kevin Sanjaya',
                'co_leader_name' => 'Amanda Zahra',
                'color_tag' => '#d97706', // Amber
                'vision' => 'OSIS Harmonis: Wadah Aspirasi Inklusif, Kreatif, dan Berbudaya Lingkungan Sehat (Green Campus).',
                'mission' => "1. Gerakan peduli lingkungan dan zero-waste school berkelanjutan.\n2. Forum diskusi terbuka antara perwakilan kelas dan pihak sekolah secara berkala.\n3. Revitalisasi kegiatan ekstrakurikuler yang aktif, inklusif, dan menginspirasi.",
            ],
        ];

        foreach ($candidates as $candidateData) {
            Candidate::updateOrCreate(
                ['candidate_number' => $candidateData['candidate_number']],
                $candidateData
            );
        }

        // 4. Sample Voters across various classes
        $sampleClasses = ['X-1', 'X-2', 'XI-IPA-1', 'XI-IPS-1', 'XII-MIPA-1'];
        $sampleNames = [
            ['Ahmad Fauzi', 'L'],
            ['Budi Santoso', 'L'],
            ['Citra Dewi', 'P'],
            ['Dewi Lestari', 'P'],
            ['Eko Prasetyo', 'L'],
            ['Fani Rahmawati', 'P'],
            ['Gilang Ramadhan', 'L'],
            ['Hani Salsabila', 'P'],
            ['Indra Wijaya', 'L'],
            ['Jihan Safitri', 'P'],
            ['Kurniawan Dwi', 'L'],
            ['Lina Marlina', 'P'],
            ['Muhammad Rizky', 'L'],
            ['Nia Daniaty', 'P'],
            ['Oscar Pratama', 'L'],
            ['Putri Ayu', 'P'],
            ['Rian Hidayat', 'L'],
            ['Siti Aisyah', 'P'],
            ['Tegar Satria', 'L'],
            ['Ulfa Zahra', 'P'],
        ];

        $nisnBase = 20260010;
        foreach ($sampleNames as $index => $item) {
            $class = $sampleClasses[$index % count($sampleClasses)];
            Voter::updateOrCreate(
                ['nisn' => (string) ($nisnBase + $index)],
                [
                    'category' => Voter::CATEGORY_SISWA,
                    'name' => $item[0],
                    'class' => $class,
                    'gender' => $item[1],
                    'passcode' => Voter::generatePasscode(),
                    'has_voted' => false,
                    'voted_at' => null,
                ]
            );
        }

        // 5. Sample Teachers (Guru)
        $sampleTeachers = [
            ['197505122005011002', 'Drs. Hendro Wibowo, M.Pd.', 'Guru Matematika', 'L'],
            ['198003182008012015', 'Dra. Endang Sulistyowati', 'Guru Bahasa Indonesia', 'P'],
            ['198507202010011008', 'Bambang Sudarsono, S.Pd.', 'Guru Olahraga & PJOK', 'L'],
            ['199011042015032004', 'Nurul Hidayati, S.Si.', 'Guru Biologi', 'P'],
        ];

        foreach ($sampleTeachers as $item) {
            Voter::updateOrCreate(
                ['nisn' => $item[0]],
                [
                    'category' => Voter::CATEGORY_GURU,
                    'name' => $item[1],
                    'class' => $item[2],
                    'gender' => $item[3],
                    'passcode' => Voter::generatePasscode(),
                    'has_voted' => false,
                    'voted_at' => null,
                ]
            );
        }

        // 6. Sample Staff (Tenaga Kependidikan)
        $sampleStaff = [
            ['198804152019032008', 'Sri Wahyuni, S.Kom.', 'Staf Tata Usaha', 'P'],
            ['198209212009021003', 'Agus Supriyadi', 'Kepala Perpustakaan', 'L'],
            ['199201102022031001', 'Wahyu Kurniawan', 'Laboran Komputer', 'L'],
        ];

        foreach ($sampleStaff as $item) {
            Voter::updateOrCreate(
                ['nisn' => $item[0]],
                [
                    'category' => Voter::CATEGORY_TENDIK,
                    'name' => $item[1],
                    'class' => $item[2],
                    'gender' => $item[3],
                    'passcode' => Voter::generatePasscode(),
                    'has_voted' => false,
                    'voted_at' => null,
                ]
            );
        }
    }
}
