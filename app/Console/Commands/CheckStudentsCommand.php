<?php

namespace App\Console\Commands;

use App\Services\DptDeduplicationService;
use Illuminate\Console\Command;

class CheckStudentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:check-siswa {--class= : Filter pemeriksaan hanya untuk kelas tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa data siswa DPT: rincian jumlah per kelas, NISN kembar, nama ganda, dan temukan selisih';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $classFilter = $this->option('class');

        $this->info('===============================================================');
        $this->info('     🔍 PEMERIKSAAN INTEGRITAS DATA SISWA DPT (PILKETOS)       ');
        $this->info('===============================================================');

        if (! empty($classFilter)) {
            $this->comment("Filter kelas: {$classFilter}");
        }

        $result = DptDeduplicationService::checkStudentsIntegrity($classFilter);

        $this->newLine();
        $this->line("📊 <fg=cyan>TOTAL SISWA TERDAFTAR:</> <fg=yellow;options=bold>{$result['total_siswa']} Siswa</>");
        $this->line('🏫 <fg=cyan>JUMLAH ROMBEL / KELAS:</> <fg=yellow;options=bold>'.count($result['classes']).' Rombel</>');

        // 1. Rekapitulasi Tingkat
        if (! empty($result['grade_subtotals'])) {
            $this->newLine();
            $this->info('📌 Subtotal Siswa Per Tingkat:');
            $gradeRows = [];
            foreach ($result['grade_subtotals'] as $grade => $cnt) {
                $gradeRows[] = [$grade, "{$cnt} Siswa"];
            }
            $this->table(['Tingkat', 'Jumlah Siswa'], $gradeRows);
        }

        // 2. Rincian Per Kelas
        $this->newLine();
        $this->info('📋 Rincian Jumlah Siswa Per Kelas (By Class):');
        $classRows = [];
        $i = 1;
        foreach ($result['classes'] as $cls => $cnt) {
            $classRows[] = [$i++, $cls, "{$cnt} Siswa"];
        }
        $this->table(['No', 'Nama Kelas', 'Jumlah Siswa'], $classRows);

        // 3. Cek Duplikat NISN
        $this->newLine();
        if (! empty($result['duplicate_nisn'])) {
            $this->error('⚠️ PERINGATAN: Ditemukan NISN Kembar/Duplikat Pada Siswa:');
            $dupNisnRows = [];
            foreach ($result['duplicate_nisn'] as $item) {
                $dupNisnRows[] = [
                    $item['id'],
                    $item['nisn'],
                    $item['name'],
                    $item['class'],
                    $item['has_voted'],
                ];
            }
            $this->table(['ID', 'NISN', 'Nama Siswa', 'Kelas', 'Status Memilih'], $dupNisnRows);
            $this->warn('👉 Rekomendasi: Jalankan "php artisan dpt:clean-duplicate-siswa" untuk membersihkan atau perbaiki di menu DPT.');
        } else {
            $this->info('✅ Tidak ada NISN kembar/duplikat pada data siswa.');
        }

        // 4. Cek Nama Kembar
        $this->newLine();
        if (! empty($result['duplicate_names'])) {
            $this->error('⚠️ PERINGATAN: Ditemukan Nama Siswa yang Sama/Duplikat:');
            $dupNameRows = [];
            foreach ($result['duplicate_names'] as $item) {
                $dupNameRows[] = [
                    $item['id'],
                    $item['name'],
                    $item['nisn'],
                    $item['class'],
                    $item['has_voted'],
                ];
            }
            $this->table(['ID', 'Nama Siswa', 'NISN', 'Kelas', 'Status Memilih'], $dupNameRows);
            $this->warn('👉 Jika ini adalah orang yang sama (duplikat), jalankan "php artisan dpt:clean-duplicate-siswa".');
        } else {
            $this->info('✅ Tidak ada nama siswa kembar/duplikat.');
        }

        // 5. Cek Siswa Tanpa NISN
        if (! empty($result['empty_nisn'])) {
            $this->newLine();
            $this->comment('ℹ️ Siswa dengan NISN kosong ('.count($result['empty_nisn']).' orang):');
            if (count($result['empty_nisn']) <= 15) {
                $emptyRows = [];
                foreach ($result['empty_nisn'] as $item) {
                    $emptyRows[] = [$item['id'], $item['name'], $item['class'], $item['has_voted']];
                }
                $this->table(['ID', 'Nama Siswa', 'Kelas', 'Status Memilih'], $emptyRows);
            } else {
                $this->line('Terdapat '.count($result['empty_nisn']).' siswa tanpa NISN.');
            }
        }

        // 6. Cek Nama Dummy/Sampel
        if (! empty($result['suspicious_names'])) {
            $this->newLine();
            $this->error('⚠️ Ditemukan data terindikasi Dummy / Akun Percobaan:');
            $suspRows = [];
            foreach ($result['suspicious_names'] as $item) {
                $suspRows[] = [$item['id'], $item['name'], $item['class'], $item['nisn'], $item['has_voted']];
            }
            $this->table(['ID', 'Nama', 'Kelas', 'NISN', 'Status Memilih'], $suspRows);
        }

        $this->newLine();
        $this->info('===============================================================');

        return Command::SUCCESS;
    }
}
