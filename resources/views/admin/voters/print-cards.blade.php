<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pemilih - {{ $class ? 'Kelas ' . $class : 'Semua DPT' }}</title>
    <link rel="icon" href="{{ \App\Models\ElectionSetting::current()->getFaviconUrl() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .card-container { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 text-slate-900">
    <!-- Top Control Bar (Hidden on print) -->
    <div class="no-print max-w-5xl mx-auto mb-6 p-4 bg-white rounded-2xl shadow-md border border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-base font-bold text-slate-800">Cetak Kartu Pemilih Pilketos</h1>
            <p class="text-xs text-slate-500">
                Total kartu: <strong>{{ count($voterCards) }}</strong> kartu siap cetak & potong
                @if($category) &bull; Kategori: <strong class="capitalize">{{ $categories[$category] ?? $category }}</strong> @endif
                @if($class) &bull; Kelas/Unit: <strong>{{ $class }}</strong> @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('admin.voters.print-cards') }}" class="flex items-center gap-2">
                <!-- Filter Kategori -->
                <select name="category" onchange="this.form.submit()" class="py-1.5 px-3 rounded-xl border border-slate-300 text-xs font-semibold bg-slate-50 text-slate-700">
                    <option value="">Semua Kategori</option>
                    <option value="siswa" {{ request('category') == 'siswa' ? 'selected' : '' }}>🎓 Siswa</option>
                    <option value="guru" {{ request('category') == 'guru' ? 'selected' : '' }}>👨‍🏫 Guru</option>
                    <option value="tendik" {{ request('category') == 'tendik' ? 'selected' : '' }}>💼 Tendik</option>
                </select>

                <!-- Filter Kelas -->
                <select name="class" onchange="this.form.submit()" class="py-1.5 px-3 rounded-xl border border-slate-300 text-xs font-semibold bg-slate-50 text-slate-700">
                    <option value="">Semua Kelas/Unit</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c }}" {{ request('class') == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </form>

            <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak (A4)
            </button>
            <button onclick="window.close()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Tutup
            </button>
        </div>
    </div>

    <!-- Cards Grid A4 Printable -->
    <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach ($voterCards as $voter)
            <div class="card-container bg-white rounded-2xl border-2 border-dashed border-slate-300 p-4 flex flex-col justify-between relative overflow-hidden shadow-sm">
                <!-- Header Kartu -->
                <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-3">
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
                    <!-- Biodata & Token -->
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
                                <span class="font-bold text-indigo-600">{{ $voter->class }}</span>
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
                        <div class="pt-2">
                            <span class="text-[8px] uppercase tracking-wider font-bold text-slate-500 block">Kode Token Bilik:</span>
                            <div class="inline-block px-3 py-1 rounded-lg bg-slate-900 text-yellow-400 font-mono font-black text-sm tracking-widest border border-slate-700">
                                {{ $voter->passcode }}
                            </div>
                        </div>
                    </div>

                    <!-- QR Code SVG -->
                    <div class="shrink-0 flex flex-col items-center justify-center p-1.5 bg-slate-50 border border-slate-200 rounded-xl">
                        @if ($voter->qr_svg)
                            <div class="w-20 h-20 [&>svg]:w-full [&>svg]:h-full">
                                {!! $voter->qr_svg !!}
                            </div>
                        @else
                            <div class="w-20 h-20 bg-slate-200 rounded-lg flex items-center justify-center text-[10px] text-slate-400">
                                QR Code
                            </div>
                        @endif
                        <span class="text-[8px] font-bold text-slate-400 mt-1 uppercase tracking-tighter">Scan Bilik</span>
                    </div>
                </div>

                <!-- Footer Peraturan -->
                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[8px] text-slate-400">
                    <span>* Kartu ini hanya berlaku 1x pemilihan. Jaga kerahasiaan token Anda.</span>
                    <span class="font-bold text-slate-600">Panitia Pilketos</span>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
