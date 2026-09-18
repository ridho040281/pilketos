<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Hadir Pemilih - {{ $setting->school_name }}</title>
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
            tr { page-break-inside: avoid; }
            .signature-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 text-slate-800">
    <!-- Action Bar (hidden when printing) -->
    <div class="no-print max-w-5xl mx-auto mb-6 p-4 bg-white rounded-2xl shadow-md border border-slate-200 flex items-center justify-between">
        <div>
            <h1 class="text-sm font-bold text-slate-800">Daftar Hadir Pemilih Resmi</h1>
            <p class="text-xs text-slate-500">Format dokumen siap cetak A4 & tanda tangan panitia</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Dokumen (A4)
            </button>
            <a href="{{ route('admin.laporan.index', ['tab' => 'daftar-hadir']) }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- Official A4 Document Paper -->
    <div class="print-page max-w-5xl mx-auto bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-slate-200 text-slate-900 leading-relaxed">
        <!-- Kop Surat -->
        <div class="text-center border-b-4 border-double border-slate-900 pb-4 mb-6">
            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-600">{{ $setting->panitia_title }}</h3>
            <h2 class="text-lg sm:text-2xl font-black uppercase tracking-tight text-slate-900 mt-0.5">{{ $setting->school_name }}</h2>
            <p class="text-xs text-slate-600 mt-1">
                {{ $setting->election_title }} &bull; Tahun Pelajaran {{ $setting->academic_year }}
            </p>
        </div>

        <!-- Document Title -->
        <div class="text-center my-6">
            <h1 class="text-base sm:text-lg font-black uppercase tracking-wider underline">DAFTAR HADIR PEMILIH DIGITAL (E-VOTING)</h1>
            <p class="text-xs text-slate-600 mt-1 font-mono">
                Dicetak pada: {{ now()->translatedFormat('l, d F Y - H:i') }} WIB
            </p>
        </div>

        <!-- Filter / Parameter Info -->
        <div class="flex flex-wrap items-center justify-between text-xs mb-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
            <div>
                <span class="text-slate-500">Filter Kelas:</span>
                <span class="font-bold text-slate-800">{{ request('class') ?: 'Semua Kelas' }}</span>
                &bull;
                <span class="text-slate-500">Kategori:</span>
                <span class="font-bold text-slate-800 capitalize">{{ request('category') ?: 'Semua' }}</span>
            </div>
            <div>
                <span class="text-slate-500">Total Tercatat:</span>
                <span class="font-bold text-slate-900">{{ number_format($attendees->count(), 0, ',', '.') }} Pemilih</span>
                @if(request('status') === 'voted')
                    (Sudah Hadir/Memilih)
                @elseif(request('status') === 'unvoted')
                    (Belum Hadir)
                @else
                    (Semua DPT)
                @endif
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto mb-8">
            <table class="w-full text-xs border border-slate-300">
                <thead class="bg-slate-100 font-bold uppercase text-slate-700 text-center">
                    <tr>
                        <th class="p-2 border border-slate-300 w-10 text-center whitespace-nowrap">No</th>
                        <th class="p-2 border border-slate-300 w-28 text-left whitespace-nowrap">NISN / NIP</th>
                        <th class="p-2 border border-slate-300 text-left whitespace-nowrap">Nama Lengkap</th>
                        <th class="p-2 border border-slate-300 w-20 text-center whitespace-nowrap">Kategori</th>
                        <th class="p-2 border border-slate-300 w-36 text-center whitespace-nowrap">Kelas/Mapel</th>
                        <th class="p-2 border border-slate-300 w-10 text-center whitespace-nowrap">L/P</th>
                        <th class="p-2 border border-slate-300 w-36 text-center whitespace-nowrap">Tanda Tangan / Paraf</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($attendees as $index => $voter)
                        <tr>
                            <td class="p-2 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                            <td class="p-2 border border-slate-300 font-mono text-[11px] whitespace-nowrap">{{ $voter->nisn ?: '-' }}</td>
                            <td class="p-2 border border-slate-300 font-semibold whitespace-nowrap">{{ $voter->name }}</td>
                            <td class="p-2 border border-slate-300 text-center capitalize text-[11px] whitespace-nowrap">{{ $voter->category_label }}</td>
                            <td class="p-2 border border-slate-300 text-center text-[11px] whitespace-nowrap px-3">{{ $voter->class ?: '-' }}</td>
                            <td class="p-2 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $voter->gender ?: '-' }}</td>
                            <td class="p-2 border border-slate-300 text-left text-[10px] text-slate-400 whitespace-nowrap">
                                {{ $index + 1 }}. ......................
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-500 italic border border-slate-300">
                                Tidak ada data pemilih yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tanda Tangan Panitia -->
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
                    <span class="block text-slate-700 font-medium mb-16">Pembina OSIS,</span>
                    <span class="block font-bold underline">( {{ $setting->pembina_name ?: '....................................................' }} )</span>
                    <span class="block text-[11px] text-slate-500 mt-1">NIP. {{ $setting->pembina_nip ?: '.....................................' }}</span>
                </div>

                <div>
                    <span class="block text-slate-700 font-medium mb-16">Ketua Panitia (MPK),</span>
                    <span class="block font-bold underline">( .................................................... )</span>
                    <span class="block text-[11px] text-slate-500 mt-1">NIS. .....................................</span>
                </div>

                <div>
                    <span class="block text-slate-700 font-medium mb-16">Petugas Presensi (KPPS),</span>
                    <span class="block font-bold underline">( .................................................... )</span>
                    <span class="block text-[11px] text-slate-500 mt-1">NIS. .....................................</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
