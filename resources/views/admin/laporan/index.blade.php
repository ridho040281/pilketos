@extends('layouts.admin')

@section('title', 'Laporan Pemilihan & Berita Acara')
@section('header_title', 'Laporan Pemilihan')

@section('content')
<div x-data="{ 
    activeTab: '{{ $activeTab }}',
    isSearching: false,
    searchQuery: '{{ addslashes(request('search', '')) }}',
    filterStatus: '{{ addslashes(request('status', 'all')) }}',
    filterCategory: '{{ addslashes(request('category', '')) }}',
    filterClass: '{{ addslashes(request('class', '')) }}',
    cetakUrl: '{{ route('admin.laporan.cetak-daftar-hadir', request()->query()) }}',
    exportUrl: '{{ route('admin.laporan.export-daftar-hadir', request()->query()) }}',
    setTab(tab) {
        this.activeTab = tab;
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    },
    fetchLaporan() {
        this.isSearching = true;
        const params = new URLSearchParams();
        params.set('tab', 'daftar-hadir');
        if (this.searchQuery) params.set('search', this.searchQuery);
        if (this.filterStatus && this.filterStatus !== 'all') params.set('status', this.filterStatus);
        if (this.filterCategory) params.set('category', this.filterCategory);
        if (this.filterClass) params.set('class', this.filterClass);

        const queryString = params.toString() ? '?' + params.toString() : '';
        const url = '{{ route('admin.laporan.index') }}' + queryString;
        
        // Update Cetak & Export action URLs
        this.cetakUrl = '{{ route('admin.laporan.cetak-daftar-hadir') }}' + queryString;
        this.exportUrl = '{{ route('admin.laporan.export-daftar-hadir') }}' + queryString;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newTable = doc.getElementById('laporan-table-container');
            const newInfo = doc.getElementById('laporan-count-info');
            
            if (newTable) {
                document.getElementById('laporan-table-container').innerHTML = newTable.innerHTML;
            }
            if (newInfo) {
                document.getElementById('laporan-count-info').innerHTML = newInfo.innerHTML;
            }
            window.history.replaceState({}, '', url);
        })
        .catch(err => console.error('Filter error:', err))
        .finally(() => {
            this.isSearching = false;
        });
    },
    resetFilters() {
        this.searchQuery = '';
        this.filterStatus = 'all';
        this.filterCategory = '';
        this.filterClass = '';
        this.fetchLaporan();
    }
}" class="space-y-6">

    <!-- Page Header & Tab Navigation -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Laporan dan Rekapitulasi Pemilihan</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pantau daftar kehadiran pemilih secara realtime dan cetak berita acara resmi pleno.</p>
            </div>

            <!-- Tab Switcher Buttons -->
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/80 self-start sm:self-auto">
                <button 
                    type="button"
                    @click="setTab('daftar-hadir')" 
                    :class="activeTab === 'daftar-hadir' ? 'bg-white text-indigo-600 font-bold shadow-sm shadow-slate-200' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Daftar Hadir Pemilih</span>
                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md font-bold" :class="activeTab === 'daftar-hadir' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-200 text-slate-600'">
                        {{ number_format($totalVoters, 0, ',', '.') }} DPT
                    </span>
                </button>

                <button 
                    type="button"
                    @click="setTab('berita-acara')" 
                    :class="activeTab === 'berita-acara' ? 'bg-white text-indigo-600 font-bold shadow-sm shadow-slate-200' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Berita Acara Pleno</span>
                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md font-bold" :class="activeTab === 'berita-acara' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600'">
                        A4 Resmi
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: DAFTAR HADIR PEMILIH                -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'daftar-hadir'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        <!-- Summary Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Total DPT</span>
                    <span class="text-lg font-black text-slate-800">{{ number_format($totalVoters, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider block">Sudah Hadir / Coblos</span>
                    <span class="text-lg font-black text-emerald-700">{{ number_format($votedCount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider block">Belum Hadir</span>
                    <span class="text-lg font-black text-amber-700">{{ number_format($unvotedCount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-indigo-600 uppercase tracking-wider block">Partisipasi</span>
                    <span class="text-lg font-black text-indigo-700">{{ $turnoutPercentage }}%</span>
                </div>
            </div>
        </div>

        <!-- Auto Filter Bar & Action Buttons -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-5 space-y-4">
            <form method="GET" action="{{ route('admin.laporan.index') }}" @submit.prevent="fetchLaporan()" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <input type="hidden" name="tab" value="daftar-hadir">

                <!-- Filter Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 flex-1">
                    <!-- Instant Search Input -->
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            x-model="searchQuery"
                            @input.debounce.300ms="fetchLaporan()"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="Cari nama, NISN/NIP..." 
                            class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all bg-slate-50/50">
                        
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
                            @click="searchQuery = ''; fetchLaporan()" 
                            class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
                            title="Hapus pencarian"
                            x-cloak>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Auto Filter Status Presensi -->
                    <div>
                        <select name="status" x-model="filterStatus" @change="fetchLaporan()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-slate-50/50 text-slate-700 cursor-pointer">
                            <option value="all">Semua DPT (Default)</option>
                            <option value="voted">Hadir / Sudah Memilih</option>
                            <option value="unvoted">Belum Hadir / Belum Memilih</option>
                        </select>
                    </div>

                    <!-- Auto Filter Kategori (Siswa, Guru, Tendik) -->
                    <div>
                        <select name="category" x-model="filterCategory" @change="fetchLaporan()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-slate-50/50 text-slate-700 cursor-pointer font-medium">
                            <option value="">Semua Kategori</option>
                            <option value="siswa">🎓 Siswa</option>
                            <option value="guru">👨‍🏫 Guru</option>
                            <option value="tendik">💼 Tenaga Kependidikan</option>
                        </select>
                    </div>

                    <!-- Auto Filter Kelas -->
                    <div>
                        <select name="class" x-model="filterClass" @change="fetchLaporan()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-slate-50/50 text-slate-700 cursor-pointer">
                            <option value="">Semua Kelas / Mapel</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Reset Filter (otomatis muncul jika filter aktif) -->
                <div class="flex items-center">
                    <button 
                        type="button" 
                        x-show="searchQuery !== '' || filterCategory !== '' || filterClass !== '' || filterStatus !== 'all'" 
                        @click="resetFilters()" 
                        class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1"
                        x-cloak>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </form>

            <!-- Action Toolbar (Cetak & Export) -->
            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div id="laporan-count-info" class="text-xs text-slate-500 font-medium">
                    Menampilkan <span class="font-bold text-slate-800">{{ $attendees->total() }}</span> data pemilih
                    @if(request('class')) untuk kelas <span class="font-bold text-slate-800">{{ request('class') }}</span> @endif
                    @if(request('category')) (Kategori: <span class="font-bold text-slate-800 capitalize">{{ request('category') }}</span>) @endif
                </div>

                <div class="flex items-center gap-2">
                    <a :href="exportUrl" href="{{ route('admin.laporan.export-daftar-hadir', request()->query()) }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-600/20 flex items-center gap-1.5 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Export Excel (.xlsx)
                    </a>

                    <a :href="cetakUrl" href="{{ route('admin.laporan.cetak-daftar-hadir', request()->query()) }}" target="_blank" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/20 flex items-center gap-1.5 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak Daftar Hadir (A4)
                    </a>
                </div>
            </div>
        </div>

        <!-- Attendees Table Container with Live Refresh -->
        <div id="laporan-table-container" 
             class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative"
             @click="
                 const a = $event.target.closest('a');
                 if (a && a.href && a.closest('nav')) {
                     $event.preventDefault();
                     isSearching = true;
                     fetch(a.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                         .then(res => res.text())
                         .then(html => {
                             const doc = new DOMParser().parseFromString(html, 'text/html');
                             const newTable = doc.getElementById('laporan-table-container');
                             if (newTable) {
                                 $el.innerHTML = newTable.innerHTML;
                             }
                             window.history.replaceState({}, '', a.href);
                         })
                         .catch(err => console.error('Pagination error:', err))
                         .finally(() => { isSearching = false; });
                 }
             ">
            <!-- Loading Indicator Overlay -->
            <div x-show="isSearching" 
                 class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity" 
                 x-cloak>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-slate-900/90 text-white text-xs font-bold shadow-xl">
                    <svg class="animate-spin h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memuat data pemilih...</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold text-[11px] tracking-wider">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">NISN / NIP</th>
                            <th class="py-3 px-4">Nama Lengkap</th>
                            <th class="py-3 px-4 text-center whitespace-nowrap">Kategori</th>
                            <th class="py-3 px-4 text-center whitespace-nowrap">Kelas / Mapel</th>
                            <th class="py-3 px-4 text-center">L/P</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        @forelse ($attendees as $voter)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ ($attendees->currentPage() - 1) * $attendees->perPage() + $loop->iteration }}
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                                    {{ $voter->nisn ?: '-' }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-800">
                                    {{ $voter->name }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if ($voter->category === 'guru')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Guru</span>
                                    @elseif ($voter->category === 'tendik')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Tendik</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">Siswa</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 whitespace-nowrap inline-block">
                                        {{ $voter->class ?: '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-bold {{ $voter->gender === 'L' ? 'text-blue-600' : 'text-pink-600' }}">
                                    {{ $voter->gender ?: '-' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if ($voter->has_voted)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Hadir (Memilih)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                            Belum Memilih
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-4 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                    <p class="font-semibold text-sm text-slate-600">Tidak ada data kehadiran yang cocok</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter di atas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if ($attendees->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $attendees->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: BERITA ACARA PLENO                  -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'berita-acara'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        <!-- Action Toolbar -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-800">Berita Acara Rapat Pleno Terbuka</h3>
                <p class="text-xs text-slate-500 mt-0.5">Dokumen rekapitulasi sah hasil perolehan suara pemilihan Ketua & Wakil Ketua OSIS.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.laporan.cetak-berita-acara') }}" target="_blank" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 flex items-center gap-1.5 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak Berita Acara (A4)
                </a>
            </div>
        </div>

        <!-- Official Berita Acara Document Paper -->
        <div class="max-w-4xl mx-auto bg-white rounded-3xl p-6 sm:p-12 shadow-md border border-slate-200 text-slate-900 leading-relaxed font-serif">
            <!-- Kop Surat -->
            <div class="text-center border-b-4 border-double border-slate-900 pb-4 mb-6 font-sans">
                <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-600">PANITIA PEMILIHAN KETUA DAN WAKIL KETUA OSIS</h3>
                <h2 class="text-lg sm:text-2xl font-black uppercase tracking-tight text-slate-900 mt-0.5">{{ $setting->school_name }}</h2>
                <p class="text-xs text-slate-600 mt-1">
                    {{ $setting->election_title }} &bull; Tahun Pelajaran {{ $setting->academic_year }}
                </p>
            </div>

            <!-- Judul Berita Acara -->
            <div class="text-center my-6 font-sans">
                <h1 class="text-base sm:text-lg font-black uppercase tracking-wider underline">BERITA ACARA RAPAT PLENO PENGHITUNGAN SUARA</h1>
                <p class="text-xs text-slate-600 mt-1 font-mono">Nomor: 001/BA-PLENO/PILKETOS/{{ date('Y') }}</p>
            </div>

            <!-- Paragraf Pembuka -->
            <div class="text-xs sm:text-sm text-justify space-y-3 mb-6 font-sans">
                <p>
                    Pada hari ini, <strong>{{ now()->translatedFormat('l') }}</strong> tanggal <strong>{{ now()->translatedFormat('d F Y') }}</strong>, bertempat di Lingkungan {{ $setting->school_name }}, telah diselenggarakan Rapat Pleno Terbuka Penghitungan dan Rekapitulasi Perolehan Suara Pemilihan Ketua dan Wakil Ketua OSIS Periode {{ $setting->academic_year }} yang dilaksanakan secara digital (E-Voting) dengan berasaskan <strong>Langsung, Umum, Bebas, Rahasia, Jujur, dan Adil (LUBER JURDIL)</strong>.
                </p>
                <p>
                    Berdasarkan data audit sistem elektronik kotak suara digital, diperoleh hasil rekapitulasi sebagai berikut:
                </p>
            </div>

            <!-- I. Data Pemilih & Partisipasi -->
            <div class="mb-6 font-sans">
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
            <div class="mb-6 font-sans">
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
                <div class="mb-8 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-sans">
                    <h4 class="font-bold uppercase text-slate-900 mb-1">III. PENETAPAN {{ !empty($winner->co_leader_name) ? 'PASANGAN CALON' : 'CALON' }} TERPILIH</h4>
                    <p class="leading-relaxed">
                        Menetapkan bahwa {{ !empty($winner->co_leader_name) ? 'Pasangan Calon' : 'Calon' }} Nomor Urut <strong>{{ sprintf('%02d', $winner->candidate_number) }}</strong> atas nama <strong>{{ $winner->leader_name }}</strong>@if(!empty($winner->co_leader_name)) dan <strong>{{ $winner->co_leader_name }}</strong>@endif yang memperoleh sebanyak <strong>{{ number_format($winner->ballots_count, 0, ',', '.') }} suara ({{ $winner->percentage }}%)</strong>, secara sah ditetapkan sebagai <strong>{{ !empty($winner->co_leader_name) ? 'Ketua dan Wakil Ketua' : 'Ketua' }} OSIS Terpilih Periode {{ $setting->academic_year }}</strong>.
                    </p>
                </div>
            @endif

            <!-- Tanda Tangan Pleno -->
            <div class="pt-6 border-t border-slate-200 text-xs font-sans">
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
    </div>
</div>
@endsection
