<?php

namespace App\Console\Commands;

use App\Services\DptDeduplicationService;
use Illuminate\Console\Command;

class CleanDuplicateStudentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:clean-duplicate-siswa {--dry-run : Hanya tampilkan data yang akan dihapus tanpa menghapus dari database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus duplikat data siswa pada DPT dengan aman (mempertahankan siswa yang sudah memilih atau memiliki NISN valid)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '🔍 Menjalankan simulasi (Dry Run) pembersihan duplikat siswa...' : '🧹 Memproses pembersihan duplikat siswa di DPT...');

        $result = DptDeduplicationService::cleanDuplicateStudents($dryRun);

        if ($result['duplicate_groups'] === 0) {
            $this->info('✅ Tidak ditemukan data duplikat pada siswa DPT.');

            return Command::SUCCESS;
        }

        $this->warn("Ditemukan {$result['duplicate_groups']} kelompok siswa duplikat, dengan total {$result['deleted_count']} data ganda.");

        $tableRows = [];
        foreach ($result['details'] as $row) {
            $tableRows[] = [
                $row['id'],
                $row['name'],
                $row['class'],
                $row['nisn'],
                $row['has_voted'],
                "ID #{$row['kept_id']} ({$row['kept_class']})",
                $dryRun ? 'Akan Dihapus' : 'Dihapus',
            ];
        }

        $this->table(
            ['ID Duplikat', 'Nama Siswa', 'Kelas', 'NISN', 'Status Memilih', 'Dilebur ke', 'Status'],
            $tableRows
        );

        if ($dryRun) {
            $this->comment('ℹ️ [DRY RUN] Tidak ada data yang dihapus. Jalankan perintah tanpa --dry-run untuk mengeksekusi penghapusan.');
        } else {
            $this->info("🎉 Sukses menghapus {$result['deleted_count']} data duplikat siswa.");
        }

        return Command::SUCCESS;
    }
}
