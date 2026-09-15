@extends('layouts.admin')

@section('title', 'Daftar Pemilih Tetap (DPT)')
@section('header_title', 'Manajemen DPT & Kartu Pemilih')

@section('content')
<div class="space-y-6" x-data="{ showImportModal: false }">
    <!-- Top Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Daftar Pemilih Tetap ({{ number_format($totalCount, 0, ',', '.') }} DPT)</h2>
            <div class="flex flex-wrap items-center gap-2 text-xs mt-1">
                <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold border border-indigo-100">🎓 Siswa: {{ number_format($siswaCount, 0, ',', '.') }}</span>
                <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold border border-emerald-100">👨‍🏫 Guru: {{ number_format($guruCount, 0, ',', '.') }}</span>
                <span class="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 font-bold border border-amber-100">💼 Tendik: {{ number_format($tendikCount, 0, ',', '.') }}</span>
                <span class="text-slate-300">|</span>
                <span class="text-emerald-600 font-bold">&bull; Sudah Memilih: {{ number_format($votedCount, 0, ',', '.') }}</span>
                <span class="text-amber-600 font-bold">&bull; Belum Memilih: {{ number_format($unvotedCount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Button Download Template -->
            <a href="{{ route('admin.voters.template') }}" class="inline-flex items-center px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold border border-slate-300 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Template CSV
            </a>

            <!-- Button Import CSV -->
            <button @click="showImportModal = true" type="button" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                Import Excel/CSV
            </button>

            <!-- Button Tarik API -->
            <a href="{{ route('admin.settings.edit', ['tab' => 'api']) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Tarik via API Sekolah
            </a>

            <!-- Button Cetak Kartu -->
            <a href="{{ route('admin.voters.print-cards', ['class' => request('class'), 'category' => request('category')]) }}" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md shadow-slate-900/30 transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Kartu Pemilih
            </a>

            <!-- Button Tambah Manual -->
            <a href="{{ route('admin.voters.create') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah
            </a>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
        <form method="GET" action="{{ route('admin.voters.index') }}" class="w-full flex flex-col sm:flex-row gap-3 items-center">
            <!-- Search -->
            <div class="relative w-full sm:w-64">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nama, NISN/NIP, token..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <!-- Category Filter -->
            <select name="category" onchange="this.form.submit()" class="w-full sm:w-40 py-2 px-3 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white font-semibold">
                <option value="">Semua Kategori</option>
                <option value="siswa" {{ request('category') == 'siswa' ? 'selected' : '' }}>🎓 Siswa</option>
                <option value="guru" {{ request('category') == 'guru' ? 'selected' : '' }}>👨‍🏫 Guru</option>
                <option value="tendik" {{ request('category') == 'tendik' ? 'selected' : '' }}>💼 Tendik</option>
            </select>

            <!-- Class Filter -->
            <select name="class" onchange="this.form.submit()" class="w-full sm:w-40 py-2 px-3 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white">
                <option value="">Semua Kelas/Unit</option>
                @foreach ($classes as $c)
                    <option value="{{ $c }}" {{ request('class') == $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="w-full sm:w-40 py-2 px-3 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white">
                <option value="">Semua Status</option>
                <option value="voted" {{ request('status') == 'voted' ? 'selected' : '' }}>Sudah Memilih</option>
                <option value="unvoted" {{ request('status') == 'unvoted' ? 'selected' : '' }}>Belum Memilih</option>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                Terapkan
            </button>

            @if(request()->hasAny(['search', 'category', 'class', 'status']))
                <a href="{{ route('admin.voters.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">No</th>
                        <th class="px-4 py-3.5">NISN / NIP</th>
                        <th class="px-4 py-3.5">Nama Pemilih</th>
                        <th class="px-4 py-3.5">Kategori</th>
                        <th class="px-4 py-3.5">Kelas / Unit Kerja</th>
                        <th class="px-4 py-3.5">L/P</th>
                        <th class="px-4 py-3.5">Kode Token (Passcode)</th>
                        <th class="px-4 py-3.5">Status Hak Pilih</th>
                        <th class="px-4 py-3.5">Waktu Memilih</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse ($voters as $index => $voter)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 text-slate-400">{{ $voters->firstItem() + $index }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $voter->nisn ?? '-' }}</td>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $voter->name }}</td>
                            <td class="px-4 py-3">
                                @if($voter->category === 'guru')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-200">
                                        👨‍🏫 Guru
                                    </span>
                                @elseif($voter->category === 'tendik')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 text-[11px] font-bold border border-amber-200">
                                        💼 Tendik
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[11px] font-bold border border-indigo-200">
                                        🎓 Siswa
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px] border border-slate-200">
                                    {{ $voter->class }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $voter->gender ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <code class="px-2 py-1 rounded bg-slate-100 text-slate-900 font-bold font-mono tracking-wider border border-slate-200">
                                    {{ $voter->passcode }}
                                </code>
                            </td>
                            <td class="px-4 py-3">
                                @if ($voter->has_voted)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        Sudah Memilih
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                        Belum Memilih
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-[11px]">
                                {{ $voter->voted_at ? $voter->voted_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <a href="{{ route('admin.voters.edit', $voter) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>
                                    <form action="{{ route('admin.voters.destroy', $voter) }}" method="POST" onsubmit="return confirm('Hapus pemilih ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-slate-400">
                                Tidak ada data pemilih yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100">
            {{ $voters->links() }}
        </div>
    </div>

    <!-- Modal Import CSV -->
    <div x-show="showImportModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showImportModal = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-left" @click.stop>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Import DPT Massal (Siswa, Guru, Tendik)</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Upload file CSV berisi daftar pemilih (Siswa, Guru, atau Tenaga Kependidikan). Sistem otomatis men-generate token unik untuk masing-masing pemilih.
                </p>

                <div class="mt-4 p-3 bg-indigo-50 border border-indigo-100 rounded-xl text-xs text-indigo-700">
                    Format 5 Kolom: <code>Kategori (siswa/guru/tendik), NISN/NIP, Nama Lengkap, Kelas/Unit, Jenis Kelamin (L/P)</code>.
                    <br><span class="text-slate-500 text-[11px]">*Mendukung juga format siswa standar 4 kolom (NISN, Nama, Kelas, JK).</span>
                    <a href="{{ route('admin.voters.template') }}" class="font-bold underline block mt-1">Unduh Template CSV</a>
                </div>

                <form action="{{ route('admin.voters.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Pilih File CSV (.csv, .txt)
                        </label>
                        <input type="file" name="file" accept=".csv,.txt" required class="block w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" @click="showImportModal = false" class="py-2.5 px-4 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" class="py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30">
                            Mulai Import Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
