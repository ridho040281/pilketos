@extends('layouts.admin')

@section('title', 'Daftar Pemilih Tetap (DPT)')
@section('header_title', 'Manajemen DPT & Kartu Pemilih')

@section('content')
<div class="space-y-6" x-data="{
    showImportModal: false,
    isSearching: false,
    searchQuery: '{{ addslashes(request('search', '')) }}',
    filterCategory: '{{ addslashes(request('category', '')) }}',
    filterClass: '{{ addslashes(request('class', '')) }}',
    filterStatus: '{{ addslashes(request('status', '')) }}',
    fetchVoters() {
        this.isSearching = true;
        const params = new URLSearchParams();
        if (this.searchQuery) params.set('search', this.searchQuery);
        if (this.filterCategory) params.set('category', this.filterCategory);
        if (this.filterClass) params.set('class', this.filterClass);
        if (this.filterStatus) params.set('status', this.filterStatus);
        
        const url = '{{ route('admin.voters.index') }}' + (params.toString() ? '?' + params.toString() : '');

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newTable = doc.getElementById('voters-table-container');
            const newStats = doc.getElementById('voters-stats-container');
            
            if (newTable) {
                document.getElementById('voters-table-container').innerHTML = newTable.innerHTML;
            }
            if (newStats) {
                document.getElementById('voters-stats-container').innerHTML = newStats.innerHTML;
            }
            window.history.replaceState({}, '', url);
        })
        .catch(err => console.error('Search error:', err))
        .finally(() => {
            this.isSearching = false;
        });
    },
    resetFilters() {
        this.searchQuery = '';
        this.filterCategory = '';
        this.filterClass = '';
        this.filterStatus = '';
        this.fetchVoters();
    }
}">
    <!-- Top Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div id="voters-stats-container">
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
            <!-- Button Import Excel -->
            <button @click="showImportModal = true" type="button" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition-colors cursor-pointer">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                Import Excel
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

            <!-- Button Reset Suara -->
            <button @click="showResetModal = true" type="button" class="inline-flex items-center px-4 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold border border-rose-200 transition-colors cursor-pointer shadow-sm">
                <svg class="w-4 h-4 mr-1.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Reset Suara
            </button>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm">
        <form id="voters-filter-form" method="GET" action="{{ route('admin.voters.index') }}" @submit.prevent="fetchVoters()" class="flex flex-wrap items-center gap-3">
            <!-- Search -->
            <div class="relative flex-1 min-w-[220px]">
                <input 
                    type="text" 
                    name="search" 
                    x-model="searchQuery"
                    @input.debounce.300ms="fetchVoters()"
                    autocomplete="off"
                    spellcheck="false"
                    placeholder="Ketik nama, NISN/NIP, token... (langsung cari)" 
                    class="w-full pl-9 pr-8 py-2 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                >
                <!-- Search Icon / Loading Spinner -->
                <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                    <svg x-show="!isSearching" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <svg x-show="isSearching" class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <!-- Clear Button -->
                <button 
                    type="button" 
                    x-show="searchQuery && searchQuery.length > 0" 
                    @click="searchQuery = ''; fetchVoters()" 
                    class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
                    title="Hapus pencarian"
                    x-cloak
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Category Filter -->
            <select name="category" x-model="filterCategory" @change="fetchVoters()" class="py-2 px-3 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white font-semibold cursor-pointer">
                <option value="">Semua Kategori</option>
                <option value="siswa">🎓 Siswa</option>
                <option value="guru">👨‍🏫 Guru</option>
                <option value="tendik">💼 Tendik</option>
            </select>

            <!-- Class Filter -->
            <select name="class" x-model="filterClass" @change="fetchVoters()" class="py-2 px-3 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white cursor-pointer">
                <option value="">Semua Kelas/Unit</option>
                @foreach ($classes as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>

            <!-- Status Filter -->
            <select name="status" x-model="filterStatus" @change="fetchVoters()" class="py-2 px-3 rounded-xl border border-slate-300 text-xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white cursor-pointer">
                <option value="">Semua Status</option>
                <option value="voted">Sudah Memilih</option>
                <option value="unvoted">Belum Memilih</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors shrink-0 cursor-pointer">
                Terapkan
            </button>

            <button 
                type="button" 
                x-show="searchQuery !== '' || filterCategory !== '' || filterClass !== '' || filterStatus !== ''" 
                @click="resetFilters()" 
                class="text-xs text-rose-600 hover:underline shrink-0 cursor-pointer font-semibold"
                x-cloak>
                Reset Filter
            </button>
        </form>
    </div>

    <!-- Table Container with Live Refresh Support -->
    <div id="voters-table-container" 
         class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden relative"
         @click="
             const a = $event.target.closest('a');
             if (a && a.href && a.closest('nav')) {
                 $event.preventDefault();
                 isSearching = true;
                 fetch(a.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                     .then(res => res.text())
                     .then(html => {
                         const doc = new DOMParser().parseFromString(html, 'text/html');
                         const newTable = doc.getElementById('voters-table-container');
                         if (newTable) {
                             $el.innerHTML = newTable.innerHTML;
                         }
                         window.history.pushState({}, '', a.href);
                     })
                     .catch(err => console.error('Pagination error:', err))
                     .finally(() => { isSearching = false; });
             }
         ">
        <!-- Loading Overlay Indicator -->
        <div x-show="isSearching" 
             class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity" 
             x-cloak>
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-slate-900/90 text-white text-xs font-bold shadow-xl">
                <svg class="animate-spin h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Mencari data...</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
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

    <!-- Modal Import Excel -->
    <div x-show="showImportModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="showImportModal = false">
        
        <!-- Backdrop Blur (Klik luar untuk auto close) -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity cursor-pointer" 
             @click="showImportModal = false"></div>

        <!-- Wrapper Dialog (Klik area luar kartu untuk auto close) -->
        <div class="min-h-full flex items-center justify-center p-4 cursor-pointer" 
             @click="showImportModal = false">
            
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-left cursor-default z-10" 
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95">
                
                <!-- Tombol Silang (X) -->
                <button type="button" 
                        @click="showImportModal = false" 
                        class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 p-2 rounded-2xl transition-colors cursor-pointer" 
                        title="Tutup (Esc)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Import DPT Massal (Excel)</h3>
                        <p class="text-xs text-slate-500">Siswa, Guru, & Tenaga Kependidikan</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    Upload file Excel (.xlsx / .xls) atau CSV berisi daftar pemilih. Sistem otomatis men-generate kode token unik (passcode) untuk masing-masing pemilih.
                </p>

                <!-- Box Download Template di Dalam Modal -->
                <div class="my-4 p-4 bg-emerald-50/80 border border-emerald-200 rounded-2xl">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                Template Format Excel (.xlsx)
                            </div>
                            <p class="text-[11px] text-emerald-700 mt-1 leading-relaxed">
                                Format 5 Kolom: <code>Kategori, NISN/NIP, Nama Lengkap, Kelas/Unit, L/P</code>.
                            </p>
                        </div>
                        <a href="{{ route('admin.voters.template') }}" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shrink-0 transition-colors shadow-sm inline-flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Unduh Template Excel
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.voters.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Pilih File Excel (.xlsx, .xls) atau CSV
                        </label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="block w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-2xl p-1 bg-slate-50">
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" @click="showImportModal = false" class="py-2.5 px-4 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            Mulai Import Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
