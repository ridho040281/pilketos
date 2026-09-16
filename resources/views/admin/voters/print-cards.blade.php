<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pemilih - {{ $class ? 'Kelas ' . $class : 'Semua DPT' }} ({{ $layout }} Kartu/A4)</title>
    <link rel="icon" href="{{ \App\Models\ElectionSetting::current()->getFaviconUrl() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        @media screen {
            .page-sheet {
                max-width: 210mm;
                margin: 0 auto 32px auto;
                background: white;
                padding: 6mm 5mm;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
                border-radius: 16px;
                border: 1px solid #e2e8f0;
            }
            .page-sheet-header {
                font-size: 11px;
                font-weight: 700;
                color: #64748b;
                margin-bottom: 8px;
                padding-bottom: 6px;
                border-bottom: 1px dashed #cbd5e1;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm 5mm;
            }
            html, body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .page-sheet-header {
                display: none !important;
            }
            .page-sheet {
                width: 100% !important;
                max-width: 100% !important;
                height: 286mm !important;
                max-height: 286mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                break-after: page !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }
            .page-sheet:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            /* Layout 20 (4x5) */
            .grid-layout-20 {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                grid-template-rows: repeat(5, 54.5mm) !important;
                column-gap: 2mm !important;
                row-gap: 2mm !important;
                height: 284mm !important;
                box-sizing: border-box !important;
            }
            .card-item-20 {
                height: 54.5mm !important;
                max-height: 54.5mm !important;
                box-sizing: border-box !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
            }

            /* Layout 10 (2x5) */
            .grid-layout-10 {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                grid-template-rows: repeat(5, 54.5mm) !important;
                column-gap: 3mm !important;
                row-gap: 2.5mm !important;
                height: 284mm !important;
                box-sizing: border-box !important;
            }
            .card-item-10 {
                height: 54.5mm !important;
                max-height: 54.5mm !important;
                box-sizing: border-box !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
            }

            /* Layout 8 (2x4) */
            .grid-layout-8 {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                grid-template-rows: repeat(4, 68mm) !important;
                column-gap: 3.5mm !important;
                row-gap: 3mm !important;
                height: 284mm !important;
                box-sizing: border-box !important;
            }
            .card-item-8 {
                height: 68mm !important;
                max-height: 68mm !important;
                box-sizing: border-box !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 p-3 sm:p-6 text-slate-900">
    <!-- Top Control Bar (Hidden on print) -->
    <div class="no-print max-w-5xl mx-auto mb-6 p-4 bg-white rounded-2xl shadow-md border border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span>Cetak Kartu Pemilih Pilketos</span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $layout }} Kartu / Lembar A4
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Total: <strong>{{ count($voterCards) }}</strong> kartu &bull; 
                Estimasi Cetak: <strong class="text-indigo-600">{{ ceil(count($voterCards) / $layout) }}</strong> lembar A4
                @if($category) &bull; Kategori: <strong class="capitalize">{{ $categories[$category] ?? $category }}</strong> @endif
                @if($class) &bull; Kelas/Mapel: <strong>{{ $class }}</strong> @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('admin.voters.print-cards') }}" class="flex flex-wrap items-center gap-2">
                <!-- Layout Selector -->
                <select name="layout" onchange="this.form.submit()" class="py-1.5 px-3 rounded-xl border border-indigo-300 bg-indigo-50 text-indigo-900 text-xs font-bold shadow-sm cursor-pointer hover:bg-indigo-100 transition-colors">
                    <option value="20" {{ $layout == 20 ? 'selected' : '' }}>⚡ 20 Kartu / Lembar (Hemat A4 - 4x5)</option>
                    <option value="10" {{ $layout == 10 ? 'selected' : '' }}>📄 10 Kartu / Lembar (Sedang - 2x5)</option>
                    <option value="8" {{ $layout == 8 ? 'selected' : '' }}>📑 8 Kartu / Lembar (Besar - 2x4)</option>
                </select>

                <!-- Filter Kategori -->
                <select name="category" onchange="this.form.submit()" class="py-1.5 px-3 rounded-xl border border-slate-300 text-xs font-semibold bg-slate-50 text-slate-700 cursor-pointer">
                    <option value="">Semua Kategori</option>
                    <option value="siswa" {{ request('category') == 'siswa' ? 'selected' : '' }}>🎓 Siswa</option>
                    <option value="guru" {{ request('category') == 'guru' ? 'selected' : '' }}>👨‍🏫 Guru</option>
                    <option value="tendik" {{ request('category') == 'tendik' ? 'selected' : '' }}>💼 Tendik</option>
                </select>

                <!-- Filter Kelas -->
                <select name="class" onchange="this.form.submit()" class="py-1.5 px-3 rounded-xl border border-slate-300 text-xs font-semibold bg-slate-50 text-slate-700 cursor-pointer">
                    <option value="">Semua Kelas/Mapel</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c }}" {{ request('class') == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </form>

            <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 flex items-center gap-1.5 transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak (A4)
            </button>
            <button onclick="window.close()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors cursor-pointer">
                Tutup
            </button>
        </div>
    </div>

    <!-- Cards Output Chunked by Page -->
    @php
        $chunks = $voterCards->chunk($layout);
        $totalPages = $chunks->count();
    @endphp

    @forelse ($chunks as $pageIndex => $pageCards)
        <div class="page-sheet">
            <!-- Screen Page Header Indicator (hidden when printing) -->
            <div class="page-sheet-header">
                <span>📄 Halaman {{ $pageIndex + 1 }} dari {{ $totalPages }}</span>
                <span>Kartu #{{ $pageIndex * $layout + 1 }} s/d #{{ min(($pageIndex + 1) * $layout, count($voterCards)) }}</span>
            </div>

            @if ($layout == 20)
                {{-- LAYOUT 20 KARTU (4 KOLOM x 5 BARIS) - FORMAT HEMAT A4 --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 grid-layout-20">
                    @foreach ($pageCards as $voter)
                        <div class="card-item-20 bg-white rounded-xl border border-dashed border-slate-300 p-2 flex flex-col justify-between relative overflow-hidden">
                            <!-- Top Row: Icon + OSIS + Badge -->
                            <div class="flex items-center justify-between border-b border-slate-200 pb-1">
                                <div class="flex items-center gap-1 min-w-0">
                                    <span class="w-4 h-4 rounded bg-indigo-600 text-white font-black text-[8px] flex items-center justify-center shrink-0">P</span>
                                    <div class="leading-none min-w-0">
                                        <span class="text-[7.5px] font-black uppercase text-indigo-700 block truncate">KARTU PEMILIH</span>
                                        <span class="text-[6.5px] font-bold text-slate-700 block truncate">{{ $setting->school_name }}</span>
                                    </div>
                                </div>
                                <span class="text-[6.5px] font-extrabold px-1 py-0.2 rounded uppercase shrink-0 {{ $voter->category === 'guru' ? 'bg-emerald-100 text-emerald-800' : ($voter->category === 'tendik' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800') }}">
                                    {{ $voter->category }}
                                </span>
                            </div>

                            <!-- Middle Row: Nama & Kelas / NISN -->
                            <div class="leading-tight my-0.5">
                                <div class="text-[9.5px] font-black text-slate-900 truncate" title="{{ $voter->name }}">
                                    {{ $voter->name }}
                                </div>
                                <div class="flex items-center justify-between text-[7.5px] text-slate-600 mt-0.5">
                                    <span class="font-bold text-indigo-600 truncate max-w-[60%]">{{ $voter->class ?: '-' }}</span>
                                    <span class="font-mono text-slate-500 truncate text-[7px]">{{ $voter->nisn ?: '-' }}</span>
                                </div>
                            </div>

                            <!-- Bottom Row: Token Box & QR Code -->
                            <div class="flex items-center justify-between gap-1 pt-1 border-t border-slate-100">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[6px] font-bold uppercase text-slate-400 block tracking-tighter">TOKEN BILIK:</span>
                                    <div class="px-1.5 py-0.5 mt-0.5 rounded bg-slate-900 text-yellow-400 font-mono font-black text-[11px] tracking-wider text-center border border-slate-700 shadow-sm leading-tight inline-block">
                                        {{ $voter->passcode }}
                                    </div>
                                    <span class="text-[5.5px] text-slate-400 block mt-0.5 leading-none">*1x pemilihan</span>
                                </div>

                                <div class="shrink-0 flex flex-col items-center">
                                    <div class="w-10 h-10 [&>svg]:w-full [&>svg]:h-full p-0.5 bg-white border border-slate-200 rounded">
                                        @if ($voter->qr_svg)
                                            {!! $voter->qr_svg !!}
                                        @else
                                            <div class="w-full h-full bg-slate-100 rounded flex items-center justify-center text-[7px] text-slate-400">QR</div>
                                        @endif
                                    </div>
                                    <span class="text-[5.5px] font-bold text-slate-400 mt-0.5 uppercase tracking-tighter">Scan Bilik</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            @elseif ($layout == 10)
                {{-- LAYOUT 10 KARTU (2 KOLOM x 5 BARIS) - FORMAT SEDANG --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 grid-layout-10">
                    @foreach ($pageCards as $voter)
                        <div class="card-item-10 bg-white rounded-xl border-2 border-dashed border-slate-300 p-2.5 flex flex-col justify-between relative overflow-hidden">
                            <!-- Top Row: Icon + OSIS + Badge -->
                            <div class="flex items-center justify-between border-b border-slate-200 pb-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-5 h-5 rounded-lg bg-indigo-600 text-white font-black text-[10px] flex items-center justify-center shrink-0">P</span>
                                    <div class="leading-none min-w-0">
                                        <span class="text-[8.5px] font-black uppercase text-indigo-700 block truncate">KARTU PEMILIH OSIS</span>
                                        <span class="text-[8px] font-bold text-slate-700 block truncate">{{ $setting->school_name }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[7.5px] font-extrabold px-1.5 py-0.5 rounded uppercase shrink-0 {{ $voter->category === 'guru' ? 'bg-emerald-100 text-emerald-800' : ($voter->category === 'tendik' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800') }}">
                                        {{ $voter->category }}
                                    </span>
                                    <span class="text-[7.5px] font-semibold text-slate-400">TP. {{ $setting->academic_year }}</span>
                                </div>
                            </div>

                            <!-- Middle Row: Info & QR Code -->
                            <div class="flex items-center justify-between gap-2 my-1">
                                <div class="space-y-0.5 text-left min-w-0 flex-1">
                                    <div>
                                        <span class="text-[8px] text-slate-400 block font-semibold leading-none">
                                            {{ $voter->category === 'guru' ? 'Nama Guru:' : ($voter->category === 'tendik' ? 'Nama Tendik:' : 'Nama Siswa:') }}
                                        </span>
                                        <span class="text-xs font-extrabold text-slate-900 block truncate mt-0.5">{{ $voter->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-[9px] leading-tight">
                                        <div>
                                            <span class="text-slate-400 font-semibold">{{ $voter->category === 'guru' ? 'Mapel:' : 'Kelas:' }}</span>
                                            <span class="font-bold text-indigo-600">{{ $voter->class ?: '-' }}</span>
                                        </div>
                                        @if($voter->nisn)
                                            <div>
                                                <span class="text-slate-400 font-semibold">NIP/NISN:</span>
                                                <span class="font-mono text-slate-700">{{ $voter->nisn }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="pt-1">
                                        <span class="text-[7px] uppercase font-bold text-slate-500 block">KODE TOKEN BILIK:</span>
                                        <div class="inline-block px-2.5 py-0.5 rounded bg-slate-900 text-yellow-400 font-mono font-black text-xs tracking-wider border border-slate-700 shadow-sm">
                                            {{ $voter->passcode }}
                                        </div>
                                    </div>
                                </div>

                                <div class="shrink-0 flex flex-col items-center justify-center p-1 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="w-12 h-12 [&>svg]:w-full [&>svg]:h-full">
                                        @if ($voter->qr_svg)
                                            {!! $voter->qr_svg !!}
                                        @else
                                            <div class="w-12 h-12 bg-slate-200 rounded flex items-center justify-center text-[8px] text-slate-400">QR</div>
                                        @endif
                                    </div>
                                    <span class="text-[7px] font-bold text-slate-400 mt-0.5 uppercase tracking-tighter">Scan Bilik</span>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-1 flex items-center justify-between text-[7px] text-slate-400">
                                <span>* Berlaku 1x pemilihan. Jaga kerahasiaan token.</span>
                                <span class="font-bold text-slate-500">Panitia Pilketos</span>
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                {{-- LAYOUT 8 KARTU (2 KOLOM x 4 BARIS) - FORMAT STANDAR BESAR --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 grid-layout-8">
                    @foreach ($pageCards as $voter)
                        <div class="card-item-8 bg-white rounded-2xl border-2 border-dashed border-slate-300 p-3.5 flex flex-col justify-between relative overflow-hidden shadow-sm">
                            <!-- Header Kartu -->
                            <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-black text-xs flex items-center justify-center">
                                        P
                                    </div>
                                    <div class="leading-tight">
                                        <span class="block text-[9px] font-bold uppercase tracking-wider text-indigo-600">KARTU PEMILIH OSIS</span>
                                        <span class="block text-xs font-bold text-slate-800">{{ $setting->school_name }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($voter->category === 'guru')
                                        <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">GURU</span>
                                    @elseif($voter->category === 'tendik')
                                        <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">TENDIK</span>
                                    @else
                                        <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 border border-indigo-200">SISWA</span>
                                    @endif
                                    <span class="text-[9px] font-semibold text-slate-400">TP. {{ $setting->academic_year }}</span>
                                </div>
                            </div>

                            <!-- Body Kartu: Info & QR Code -->
                            <div class="flex items-center justify-between gap-3">
                                <div class="space-y-1 text-left min-w-0 flex-1">
                                    <div>
                                        <span class="text-[9px] text-slate-400 block font-semibold">
                                            {{ $voter->category === 'guru' ? 'Nama Guru:' : ($voter->category === 'tendik' ? 'Nama Tendik:' : 'Nama Siswa:') }}
                                        </span>
                                        <span class="text-xs font-extrabold text-slate-900 block truncate">{{ $voter->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-[10px]">
                                        <div>
                                            <span class="text-slate-400 font-semibold">
                                                {{ $voter->category === 'guru' ? 'Tugas/Mapel:' : ($voter->category === 'tendik' ? 'Unit Kerja:' : 'Kelas:') }}
                                            </span>
                                            <span class="font-bold text-indigo-600">{{ $voter->class ?: '-' }}</span>
                                        </div>
                                        @if($voter->nisn)
                                            <div>
                                                <span class="text-slate-400 font-semibold">
                                                    {{ $voter->category === 'guru' ? 'NIP/NUPTK:' : ($voter->category === 'tendik' ? 'NIP/NIK:' : 'NISN:') }}
                                                </span>
                                                <span class="font-mono text-slate-700">{{ $voter->nisn }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Big Passcode Box -->
                                    <div class="pt-1">
                                        <span class="text-[8px] uppercase tracking-wider font-bold text-slate-500 block">Kode Token Bilik:</span>
                                        <div class="inline-block px-3 py-1 rounded-lg bg-slate-900 text-yellow-400 font-mono font-black text-sm tracking-widest border border-slate-700">
                                            {{ $voter->passcode }}
                                        </div>
                                    </div>
                                </div>

                                <!-- QR Code SVG -->
                                <div class="shrink-0 flex flex-col items-center justify-center p-1.5 bg-slate-50 border border-slate-200 rounded-xl">
                                    @if ($voter->qr_svg)
                                        <div class="w-16 h-16 [&>svg]:w-full [&>svg]:h-full">
                                            {!! $voter->qr_svg !!}
                                        </div>
                                    @else
                                        <div class="w-16 h-16 bg-slate-200 rounded-lg flex items-center justify-center text-[10px] text-slate-400">
                                            QR Code
                                        </div>
                                    @endif
                                    <span class="text-[8px] font-bold text-slate-400 mt-1 uppercase tracking-tighter">Scan Bilik</span>
                                </div>
                            </div>

                            <!-- Footer Peraturan -->
                            <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[8px] text-slate-400">
                                <span>* Kartu ini hanya berlaku 1x pemilihan. Jaga kerahasiaan token Anda.</span>
                                <span class="font-bold text-slate-600">Panitia Pilketos</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="max-w-md mx-auto p-12 text-center bg-white rounded-2xl border border-slate-200 shadow-sm mt-12">
            <div class="w-12 h-12 mx-auto mb-3 text-slate-300">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Tidak Ada Data Pemilih</h3>
            <p class="text-xs text-slate-500 mt-1">Coba ubah filter kategori atau kelas.</p>
        </div>
    @endforelse
</body>
</html>
