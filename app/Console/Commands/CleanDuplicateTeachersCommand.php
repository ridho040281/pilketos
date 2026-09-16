<?php

namespace App\Console\Commands;

use App\Services\DptDeduplicationService;
use Illuminate\Console\Command;

class CleanDuplicateTeachersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:clean-duplicate-guru {--dry-run : Hanya tampilkan data yang akan dihapus tanpa menghapus dari database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus duplikat data guru pada DPT dengan aman (mempertahankan guru yang sudah memilih atau memiliki NIP)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '🔍 Menjalankan simulasi (Dry Run) pembersihan duplikat guru...' : '🧹 Memproses pembersihan duplikat guru di DPT...');

        $result = DptDeduplicationService::cleanDuplicateTeachers($dryRun);

        if ($result['duplicate_groups'] === 0) {
            $this->info('✅ Tidak ditemukan duplikat nama pada data guru DPT.');

            return Command::SUCCESS;
        }

        $this->warn("Ditemukan {$result['duplicate_groups']} kelompok guru duplikat, dengan total {$result['deleted_count']} data duplikat.");

        $tableRows = [];
        foreach ($result['details'] as $row) {
            $tableRows[] = [
                $row['id'],
                $row['name'],
                $row['nisn'],
                $row['has_voted'],
                $row['kept_id'],
                $dryRun ? 'Akan Dihapus' : 'Dihapus',
            ];
        }

        $this->table(
            ['ID Duplikat', 'Nama Guru', 'NIP/NISN', 'Status Memilih', 'Dilebur ke ID', 'Status'],
            $tableRows
        );

        if ($dryRun) {
            $this->comment('ℹ️ [DRY RUN] Tidak ada data yang dihapus. Jalankan perintah tanpa --dry-run untuk mengeksekusi penghapusan.');
        } else {
            $this->info("🎉 Sukses menghapus {$result['deleted_count']} data duplikat guru.");
        }

        return Command::SUCCESS;
    }
}
