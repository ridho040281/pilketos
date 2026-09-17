<?php

namespace App\Console\Commands;

use App\Models\Voter;
use App\Services\DptDeduplicationService;
use Illuminate\Console\Command;

class FixTendikCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:fix-tendik';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindahkan pegawai/staf Tata Usaha yang salah tercatat sebagai siswa ke kategori Tendik';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa staf/pegawai yang salah masuk ke kategori Siswa...');

        $result = DptDeduplicationService::fixMiscategorizedTendik();

        if ($result['fixed_count'] === 0) {
            $this->info('✅ Tidak ditemukan pegawai/staf Tata Usaha pada kategori siswa.');

            return Command::SUCCESS;
        }

        $tableRows = [];
        foreach ($result['details'] as $item) {
            $tableRows[] = [
                $item['id'],
                $item['name'],
                $item['class'],
                $item['nisn'],
                'Siswa -> Tendik',
            ];
        }

        $this->table(['ID', 'Nama Pegawai', 'Kelas / Unit', 'NIP / NISN', 'Perubahan Kategori'], $tableRows);

        $newSiswaCount = Voter::where('category', Voter::CATEGORY_SISWA)->count();
        $newTendikCount = Voter::where('category', Voter::CATEGORY_TENDIK)->count();

        $this->newLine();
        $this->info("🎉 Sukses memindahkan {$result['fixed_count']} orang ke kategori Tenaga Kependidikan (Tendik).");
        $this->line("📊 <fg=cyan>Total Siswa Sekarang:</> <fg=yellow;options=bold>{$newSiswaCount} Siswa</>");
        $this->line("💼 <fg=cyan>Total Tendik Sekarang:</> <fg=yellow;options=bold>{$newTendikCount} Tendik</>");

        return Command::SUCCESS;
    }
}
