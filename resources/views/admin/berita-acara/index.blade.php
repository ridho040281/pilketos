<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Pleno Hasil Pemilihan OSIS - {{ $setting->school_name }}</title>
    <link rel="icon" href="{{ $setting->getFaviconUrl() }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', serif, sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; color: black !important; }
            .print-page { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 text-slate-800">
    <!-- Top Action Bar (hidden on print) -->
    <div class="no-print max-w-4xl mx-auto mb-6 p-4 bg-white rounded-2xl shadow-md border border-slate-200 flex items-center justify-between">
        <div>
            <h1 class="text-sm font-bold text-slate-800">Berita Acara Resmi Sidang Pleno</h1>
            <p class="text-xs text-slate-500">Format dokumen resmi A4 siap cetak & tanda tangan</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Dokumen (A4)
            </button>
            <a href="{{ route('admin.laporan.index', ['tab' => 'berita-acara']) }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- Official A4 Document Paper -->
    <div class="print-page max-w-4xl mx-auto bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-slate-200 text-slate-900 leading-relaxed">
        <!-- Kop Surat -->
        <div class="text-center border-b-4 border-double border-slate-900 pb-4 mb-6">
            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-600">PANITIA PEMILIHAN KETUA DAN WAKIL KETUA OSIS</h3>
            <h2 class="text-lg sm:text-2xl font-black uppercase tracking-tight text-slate-900 mt-0.5">{{ $setting->school_name }}</h2>
            <p class="text-xs text-slate-600 mt-1">
                {{ $setting->election_title }} &bull; Tahun Pelajaran {{ $setting->academic_year }}
            </p>
        </div>

        <!-- Judul Berita Acara -->
        <div class="text-center my-6">
            <h1 class="text-base sm:text-lg font-black uppercase tracking-wider underline">BERITA ACARA RAPAT PLENO PENGHITUNGAN SUARA</h1>
            <p class="text-xs text-slate-600 mt-1 font-mono">Nomor: 001/BA-PLENO/PILKETOS/{{ date('Y') }}</p>
        </div>

        <!-- Paragraf Pembuka -->
        <div class="text-xs sm:text-sm text-justify space-y-3 mb-6">
            <p>
                Pada hari ini, <strong>{{ now()->translatedFormat('l') }}</strong> tanggal <strong>{{ now()->translatedFormat('d F Y') }}</strong>, bertempat di Lingkungan {{ $setting->school_name }}, telah diselenggarakan Rapat Pleno Terbuka Penghitungan dan Rekapitulasi Perolehan Suara Pemilihan Ketua dan Wakil Ketua OSIS Periode {{ $setting->academic_year }} yang dilaksanakan secara digital (E-Voting) dengan berasaskan <strong>Langsung, Umum, Bebas, Rahasia, Jujur, dan Adil (LUBER JURDIL)</strong>.
            </p>
            <p>
                Berdasarkan data audit sistem elektronik kotak suara digital, diperoleh hasil rekapitulasi sebagai berikut:
            </p>
        </div>

        <!-- I. Data Pemilih & Partisipasi -->
        <div class="mb-6">
            <h4 class="text-xs sm:text-sm font-bold uppercase text-slate-900 mb-2">I. REKAPITULASI PARTISIPASI PEMILIH</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-slate-300">
                    <tr class="bg-slate-50">
                        <td class="p-2 border border-slate-300 w-12 text-center font-bold">1</td>
                        <td class="p-2 border border-slate-300 font-medium">Jumlah Daftar Pemilih Tetap (DPT) Terdaftar</td>
                        <td class="p-2 border border-slate-300 w-36 text-right font-bold">{{ number_format($totalVoters, 0, ',', '.') }} orang</td>
                    </tr>
                    <tr>
                        <td class="p-2 border border-slate-300 text-center font-bold">2</td>
                        <td class="p-2 border border-slate-300 font-medium">Jumlah Suara Sah yang Menggunakan Hak Pilih</td>
                        <td class="p-2 border border-slate-300 text-right font-bold text-emerald-700">{{ number_format($votedCount, 0, ',', '.') }} suara</td>
                    </tr>
                    <tr class="bg-slate-50">
                        <td class="p-2 border border-slate-300 text-center font-bold">3</td>
                        <td class="p-2 border border-slate-300 font-medium">Jumlah Pemilih yang Tidak Hadir / Tidak Memilih (Golput)</td>
                        <td class="p-2 border border-slate-300 text-right font-bold text-amber-700">{{ number_format($unvotedCount, 0, ',', '.') }} suara</td>
                    </tr>
                    <tr>
                        <td class="p-2 border border-slate-300 text-center font-bold">4</td>
                        <td class="p-2 border border-slate-300 font-bold">Tingkat Partisipasi Kehadiran Pemilih</td>
                        <td class="p-2 border border-slate-300 text-right font-black text-indigo-700">{{ $turnoutPercentage }}%</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- II. Hasil Perolehan Suara Pasangan Calon -->
        <div class="mb-6">
            <h4 class="text-xs sm:text-sm font-bold uppercase text-slate-900 mb-2">II. PEROLEHAN SUARA PASANGAN CALON</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-slate-300 text-left">
                    <thead class="bg-slate-100 font-bold uppercase text-slate-700 text-center">
                        <tr>
                            <th class="p-2.5 border border-slate-300 w-16">No. Urut</th>
                            <th class="p-2.5 border border-slate-300 text-left">Nama Pasangan Calon</th>
                            <th class="p-2.5 border border-slate-300 w-32 text-right">Perolehan Suara</th>
                            <th class="p-2.5 border border-slate-300 w-28 text-right">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($candidates as $candidate)
                            <tr class="{{ $winner && $winner->id === $candidate->id ? 'bg-indigo-50/50 font-bold' : '' }}">
                                <td class="p-2.5 border border-slate-300 text-center font-bold text-sm">
                                    {{ sprintf('%02d', $candidate->candidate_number) }}
                                </td>
                                <td class="p-2.5 border border-slate-300">
                                    <div class="font-bold text-slate-900 text-xs">{{ $candidate->leader_name }}</div>
                                    @if(!empty($candidate->co_leader_name))
                                        <div class="text-[11px] text-slate-600">& {{ $candidate->co_leader_name }}</div>
                                    @endif
                                </td>
                                <td class="p-2.5 border border-slate-300 text-right font-black text-sm">
                                    {{ number_format($candidate->ballots_count, 0, ',', '.') }} suara
                                </td>
                                <td class="p-2.5 border border-slate-300 text-right font-bold">
                                    {{ $candidate->percentage }}%
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-100 font-extrabold">
                            <td colspan="2" class="p-2.5 border border-slate-300 text-right uppercase">Total Suara Sah Masuk:</td>
                            <td class="p-2.5 border border-slate-300 text-right text-sm">{{ number_format($totalBallots, 0, ',', '.') }} suara</td>
                            <td class="p-2.5 border border-slate-300 text-right">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- III. Penetapan Pemenang -->
        @if ($winner)
            <div class="mb-8 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs sm:text-sm">
                <h4 class="font-bold uppercase text-slate-900 mb-1">III. PENETAPAN {{ !empty($winner->co_leader_name) ? 'PASANGAN CALON' : 'CALON' }} TERPILIH</h4>
                <p class="leading-relaxed">
                    Menetapkan bahwa {{ !empty($winner->co_leader_name) ? 'Pasangan Calon' : 'Calon' }} Nomor Urut <strong>{{ sprintf('%02d', $winner->candidate_number) }}</strong> atas nama <strong>{{ $winner->leader_name }}</strong>@if(!empty($winner->co_leader_name)) dan <strong>{{ $winner->co_leader_name }}</strong>@endif yang memperoleh sebanyak <strong>{{ number_format($winner->ballots_count, 0, ',', '.') }} suara ({{ $winner->percentage }}%)</strong>, secara sah ditetapkan sebagai <strong>{{ !empty($winner->co_leader_name) ? 'Ketua dan Wakil Ketua' : 'Ketua' }} OSIS Terpilih Periode {{ $setting->academic_year }}</strong>.
                </p>
            </div>
        @endif

        <!-- Tanda Tangan Pleno -->
        <div class="pt-6 border-t border-slate-200 text-xs signature-section">
            <div class="grid grid-cols-3 gap-6 mb-3">
                <div></div>
                <div></div>
                <div class="text-center font-medium">
                    Blitar, {{ now()->translatedFormat('d F Y') }}
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6 text-center items-start">
                <div>
                    <span class="block text-slate-700 font-medium mb-16">Ketua Panitia Pemilihan (MPK),</span>
                    <span class="block font-bold underline">( .................................................... )</span>
                    <span class="block text-[11px] text-slate-500 mt-1">NIS. .....................................</span>
                </div>

                <div>
                    <span class="block text-slate-700 font-medium mb-16">Pembina OSIS,</span>
                    <span class="block font-bold underline">( .................................................... )</span>
                    <span class="block text-[11px] text-slate-500 mt-1">NIP. .....................................</span>
                </div>

                <div>
                    <span class="block text-slate-700 font-medium mb-16">Kepala Sekolah,</span>
                    <span class="block font-bold underline">( .................................................... )</span>
                    <span class="block text-[11px] text-slate-500 mt-1">NIP. .....................................</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
