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
    categoryClasses: {{ \Illuminate\Support\Js::from($categoryClasses) }},
    allClasses: {{ \Illuminate\Support\Js::from($classes) }},
    classCounts: {{ \Illuminate\Support\Js::from($classesCounts ?? []) }},
    init() {
        const initialClass = '{{ addslashes(request('class', '')) }}';
        if (initialClass) {
            this.$nextTick(() => {
                this.filterClass = initialClass;
            });
        }
        if (this.activeTab === 'hitung-cepat') {
            setTimeout(() => {
                if (window.renderHitungCepatCharts) {
                    window.renderHitungCepatCharts();
                }
            }, 100);
        }
    },
    get availableClasses() {
        if (this.filterCategory && this.categoryClasses[this.filterCategory]) {
            return this.categoryClasses[this.filterCategory];
        }
        return this.allClasses;
    },
    get classPlaceholder() {
        if (this.filterCategory === 'guru') return 'Semua Mapel';
        if (this.filterCategory === 'siswa') return 'Semua Kelas';
        if (this.filterCategory === 'tendik') return 'Semua Unit';
        return 'Semua Kelas / Mapel';
    },
    onCategoryChange() {
        if (this.filterClass && this.filterCategory) {
            const allowed = this.categoryClasses[this.filterCategory] || [];
            if (!allowed.includes(this.filterClass)) {
                this.filterClass = '';
            }
        }
        this.fetchLaporan();
    },
    cetakUrl: '{{ route('admin.laporan.cetak-daftar-hadir', request()->query()) }}',
    exportUrl: '{{ route('admin.laporan.export-daftar-hadir', request()->query()) }}',
    setTab(tab) {
        this.activeTab = tab;
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
        if (tab === 'hitung-cepat') {
            this.$nextTick(() => {
                setTimeout(() => {
                    if (window.renderHitungCepatCharts) {
                        window.renderHitungCepatCharts();
                    }
                }, 50);
            });
        }
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
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/80 self-start sm:self-auto overflow-x-auto max-w-full">
                <button 
                    type="button"
                    @click="setTab('hitung-cepat')" 
                    :class="activeTab === 'hitung-cepat' ? 'bg-white text-indigo-600 font-bold shadow-sm shadow-slate-200' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-lg text-xs transition-all cursor-pointer whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Hitung Cepat</span>
                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md font-bold" :class="activeTab === 'hitung-cepat' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-200 text-slate-600'">
                        Grafik Live
                    </span>
                </button>

                <button 
                    type="button"
                    @click="setTab('daftar-hadir')" 
                    :class="activeTab === 'daftar-hadir' ? 'bg-white text-indigo-600 font-bold shadow-sm shadow-slate-200' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-lg text-xs transition-all cursor-pointer whitespace-nowrap">
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
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-lg text-xs transition-all cursor-pointer whitespace-nowrap">
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
    <!-- TAB 1: HITUNG CEPAT (GRAFIK & REALTIME)    -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'hitung-cepat'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        <!-- Quick Action & Header Info Bar -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span>
                        Hitung Cepat Realtime
                    </span>
                    <span class="text-xs text-slate-400 font-medium">Diperbarui: {{ now()->translatedFormat('d M Y, H:i') }} WIB</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 mt-1">Perolehan Suara & Statistik Partisipasi Pemilih</h3>
                <p class="text-xs text-slate-500 mt-0.5">Grafik hasil persentase dan jumlah suara masuk per kategori Guru, Kelas Siswa, dan Tenaga Kependidikan.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.laporan.index', ['tab' => 'hitung-cepat']) }}" class="inline-flex items-center px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Segarkan Data
                </a>
                <a href="{{ route('proyektor.index') }}" target="_blank" class="inline-flex items-center px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-colors shadow-sm shadow-indigo-600/30">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    Layar Proyektor
                </a>
            </div>
        </div>

        <!-- 4 Global Summary Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Total DPT</span>
                    <span class="text-xl font-black text-slate-900">{{ number_format($totalVoters, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-slate-500 block">Pemilih Terdaftar</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider block">Suara Masuk</span>
                    <span class="text-xl font-black text-emerald-700">{{ number_format($votedCount, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-emerald-600 font-bold block">{{ $turnoutPercentage }}% Partisipasi</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider block">Belum Memilih</span>
                    <span class="text-xl font-black text-amber-700">{{ number_format($unvotedCount, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-amber-600 font-bold block">{{ $totalVoters > 0 ? round(($unvotedCount / $totalVoters) * 100, 1) : 0 }}% Belum Hadir</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-purple-600 uppercase tracking-wider block">Kotak Suara</span>
                    <span class="text-xl font-black text-purple-700">{{ number_format($totalBallots, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-purple-600 font-bold block">{{ $candidates->count() }} Pasangan Calon</span>
                </div>
            </div>
        </div>

        <!-- Section 1: Perolehan Suara Paslon (Graphic & Cards) -->
        <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Perolehan Suara Pasangan Calon</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Grafik dan persentase perolehan suara sah di bilik suara</p>
                </div>
                <div class="text-xs text-slate-500 font-medium">
                    Total Suara Sah: <strong class="text-slate-900 font-bold">{{ number_format($totalBallots, 0, ',', '.') }}</strong> suara
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left: Candidate Cards -->
                <div class="lg:col-span-7 space-y-3">
                    @forelse ($candidates as $c)
                        <div class="p-4 rounded-2xl border-2 transition-all {{ $winner && $winner->id === $c->id && $totalBallots > 0 ? 'border-indigo-500 bg-indigo-50/20 shadow-sm' : 'border-slate-100 bg-slate-50/60' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <span class="w-10 h-10 rounded-xl text-white font-black text-base flex items-center justify-center shrink-0 shadow-sm" style="background-color: {{ $c->card_color }}">
                                        {{ sprintf('%02d', $c->candidate_number) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-bold text-slate-900 truncate">{{ $c->leader_name }}</h4>
                                            @if($winner && $winner->id === $c->id && $totalBallots > 0)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-700 shrink-0">
                                                    Unggul
                                                </span>
                                            @endif
                                        </div>
                                        @if(!empty($c->co_leader_name))
                                            <p class="text-xs text-slate-500 truncate">& {{ $c->co_leader_name }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="text-lg font-black text-slate-900">{{ number_format($c->ballots_count, 0, ',', '.') }}</span>
                                    <span class="text-xs text-slate-400 block font-semibold">suara</span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                                    <span class="text-slate-500">Persentase</span>
                                    <span class="font-bold" style="color: {{ $c->card_color }}">{{ $c->percentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-200/80 rounded-full h-2.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" style="width: {{ $c->percentage }}%; background-color: {{ $c->card_color }}"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-400">Belum ada data pasangan calon.</div>
                    @endforelse
                </div>

                <!-- Right: Chart Graphic Paslon -->
                <div class="lg:col-span-5 bg-slate-50/70 rounded-2xl p-4 border border-slate-200 flex flex-col justify-between">
                    <div class="mb-3">
                        <span class="text-xs font-bold text-slate-700 block">Grafik Perolehan Suara Paslon</span>
                        <span class="text-[11px] text-slate-400">Diagram perbandingan suara sah</span>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="paslonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Rekapitulasi & Grafik Partisipasi Per Kategori (Guru, Tendik, Siswa) -->
        <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Grafik & Rekapitulasi Partisipasi Per Kategori</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Persentase dan jumlah suara masuk dari Guru, Tenaga Kependidikan, dan Siswa</p>
                </div>
            </div>

            <!-- 3 Category Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Guru Card -->
                <div class="p-5 rounded-2xl border border-emerald-200/80 bg-emerald-50/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-100 text-emerald-800 text-xs font-bold">
                                <span>👨‍🏫</span> Guru
                            </span>
                            <span class="text-base font-black text-emerald-700">{{ $guruStats->percentage }}%</span>
                        </div>
                        <div class="mt-4 flex items-baseline justify-between">
                            <div>
                                <span class="text-2xl font-black text-slate-900">{{ number_format($guruStats->voted, 0, ',', '.') }}</span>
                                <span class="text-xs text-slate-500 font-semibold">/ {{ number_format($guruStats->total, 0, ',', '.') }} hadir</span>
                            </div>
                            <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200/60">
                                {{ number_format($guruStats->unvoted, 0, ',', '.') }} belum
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-emerald-200/60 rounded-full h-2 mt-3 overflow-hidden">
                        <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" style="width: {{ $guruStats->percentage }}%"></div>
                    </div>
                </div>

                <!-- Tendik Card -->
                <div class="p-5 rounded-2xl border border-amber-200/80 bg-amber-50/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-100 text-amber-800 text-xs font-bold">
                                <span>💼</span> Tenaga Kependidikan (Tendik)
                            </span>
                            <span class="text-base font-black text-amber-700">{{ $tendikStats->percentage }}%</span>
                        </div>
                        <div class="mt-4 flex items-baseline justify-between">
                            <div>
                                <span class="text-2xl font-black text-slate-900">{{ number_format($tendikStats->voted, 0, ',', '.') }}</span>
                                <span class="text-xs text-slate-500 font-semibold">/ {{ number_format($tendikStats->total, 0, ',', '.') }} hadir</span>
                            </div>
                            <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200/60">
                                {{ number_format($tendikStats->unvoted, 0, ',', '.') }} belum
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-amber-200/60 rounded-full h-2 mt-3 overflow-hidden">
                        <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $tendikStats->percentage }}%"></div>
                    </div>
                </div>

                <!-- Siswa Card -->
                <div class="p-5 rounded-2xl border border-indigo-200/80 bg-indigo-50/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-indigo-100 text-indigo-800 text-xs font-bold">
                                <span>🎓</span> Siswa (Semua Kelas)
                            </span>
                            <span class="text-base font-black text-indigo-700">{{ $siswaStats->percentage }}%</span>
                        </div>
                        <div class="mt-4 flex items-baseline justify-between">
                            <div>
                                <span class="text-2xl font-black text-slate-900">{{ number_format($siswaStats->voted, 0, ',', '.') }}</span>
                                <span class="text-xs text-slate-500 font-semibold">/ {{ number_format($siswaStats->total, 0, ',', '.') }} hadir</span>
                            </div>
                            <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200/60">
                                {{ number_format($siswaStats->unvoted, 0, ',', '.') }} belum
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-indigo-200/60 rounded-full h-2 mt-3 overflow-hidden">
                        <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $siswaStats->percentage }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Graphic Charts for Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-200">
                    <div class="mb-3">
                        <span class="text-xs font-bold text-slate-700 block">Grafik Tingkat Partisipasi Antar Kategori (%)</span>
                        <span class="text-[11px] text-slate-400">Perbandingan persentase kehadiran pemilih</span>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="categoryBarChart"></canvas>
                    </div>
                </div>

                <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-200">
                    <div class="mb-3">
                        <span class="text-xs font-bold text-slate-700 block">Komposisi Suara Masuk Berdasarkan Kategori</span>
                        <span class="text-[11px] text-slate-400">Proporsi suara yang masuk ke kotak suara</span>
                    </div>
                    <div class="relative h-60 w-full">
                        <canvas id="categoryDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Rekapitulasi & Grafik Partisipasi Per Kelas (Siswa) -->
        <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Grafik & Rekapitulasi Partisipasi Per Kelas Siswa</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pantau capaian persentase dan jumlah suara masuk untuk setiap rombel kelas</p>
                </div>
                <div class="text-xs text-slate-500 font-medium">
                    Total: <strong class="text-slate-900 font-bold">{{ $classesStats->count() }}</strong> Kelas
                </div>
            </div>

            <!-- Graphic Bar Chart for Classes -->
            <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-200 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <span class="text-xs font-bold text-slate-700 block">Grafik Batang Persentase Suara Masuk Per Kelas</span>
                        <span class="text-[11px] text-slate-400">Urutan tingkat kehadiran siswa dari tiap rombel</span>
                    </div>
                </div>
                <div class="relative w-full" style="height: {{ max(280, min(500, $classesStats->count() * 26)) }}px;">
                    <canvas id="classesBarChart"></canvas>
                </div>
            </div>

            <!-- Detailed Classes Table -->
            <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">No</th>
                            <th class="px-4 py-3">Nama Kelas</th>
                            <th class="px-4 py-3 text-right">Total DPT</th>
                            <th class="px-4 py-3 text-right">Sudah Memilih</th>
                            <th class="px-4 py-3 text-right">Belum Memilih</th>
                            <th class="px-4 py-3 w-48">Persentase Partisipasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse ($classesStats as $index => $stat)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-2.5 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-2.5 font-bold text-slate-900">{{ $stat->class }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold">{{ $stat->total }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-emerald-600">{{ $stat->voted }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-amber-600">{{ $stat->unvoted }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $stat->percentage >= 80 ? 'bg-emerald-500' : ($stat->percentage >= 50 ? 'bg-indigo-600' : 'bg-amber-500') }}" style="width: {{ $stat->percentage }}%"></div>
                                        </div>
                                        <span class="font-extrabold text-xs {{ $stat->percentage >= 80 ? 'text-emerald-700' : ($stat->percentage >= 50 ? 'text-indigo-700' : 'text-amber-700') }}">
                                            {{ $stat->percentage }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada data kelas siswa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($classesStats->isNotEmpty())
                        <tfoot class="bg-slate-100/90 font-bold text-slate-800 border-t-2 border-slate-300">
                            <tr>
                                <td colspan="2" class="px-4 py-3.5 font-black uppercase text-slate-900">Total Kelas Siswa ({{ $classesStats->count() }} Kelas)</td>
                                <td class="px-4 py-3.5 font-black text-right text-slate-900">{{ number_format($classesStats->sum('total'), 0, ',', '.') }}</td>
                                <td class="px-4 py-3.5 font-black text-right text-emerald-700">{{ number_format($classesStats->sum('voted'), 0, ',', '.') }}</td>
                                <td class="px-4 py-3.5 font-black text-right text-amber-700">{{ number_format($classesStats->sum('unvoted'), 0, ',', '.') }}</td>
                                <td class="px-4 py-3.5 font-black text-indigo-700">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="flex-1 bg-slate-300 rounded-full h-2.5 overflow-hidden">
                                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $siswaStats->percentage }}%"></div>
                                        </div>
                                        <span class="font-black text-xs text-indigo-700">{{ $siswaStats->percentage }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Section 4: Breakdown Guru (Mapel) & Tendik (Unit) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Guru per Mapel -->
            <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-base">👨‍🏫</span>
                        <h4 class="text-sm font-bold text-slate-900">Partisipasi Guru per Mapel</h4>
                    </div>
                    <span class="text-xs text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                        {{ $guruStats->percentage }}% Total
                    </span>
                </div>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase font-bold border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2">Mata Pelajaran / Tugas</th>
                                <th class="px-3 py-2 text-right">DPT</th>
                                <th class="px-3 py-2 text-right">Hadir</th>
                                <th class="px-3 py-2 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($guruClassStats as $g)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-3 py-2 font-semibold text-slate-800">{{ $g->class }}</td>
                                    <td class="px-3 py-2 text-right text-slate-600">{{ $g->total }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-emerald-600">{{ $g->voted }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-indigo-600">{{ $g->percentage }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-slate-400">Belum ada rincian data mapel guru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tendik per Unit -->
            <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-base">💼</span>
                        <h4 class="text-sm font-bold text-slate-900">Partisipasi Tendik per Unit Kerja</h4>
                    </div>
                    <span class="text-xs text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200">
                        {{ $tendikStats->percentage }}% Total
                    </span>
                </div>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase font-bold border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2">Unit Kerja / Jabatan</th>
                                <th class="px-3 py-2 text-right">DPT</th>
                                <th class="px-3 py-2 text-right">Hadir</th>
                                <th class="px-3 py-2 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tendikClassStats as $t)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-3 py-2 font-semibold text-slate-800">{{ $t->class }}</td>
                                    <td class="px-3 py-2 text-right text-slate-600">{{ $t->total }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-amber-600">{{ $t->voted }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-indigo-600">{{ $t->percentage }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-slate-400">Belum ada rincian data unit tendik.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: DAFTAR HADIR PEMILIH                -->
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
                        <select name="category" x-model="filterCategory" @change="onCategoryChange()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-slate-50/50 text-slate-700 cursor-pointer font-medium">
                            <option value="">Semua Kategori</option>
                            <option value="siswa">🎓 Siswa</option>
                            <option value="guru">👨‍🏫 Guru</option>
                            <option value="tendik">💼 Tenaga Kependidikan</option>
                        </select>
                    </div>

                    <!-- Auto Filter Kelas / Mapel -->
                    <div>
                        <select name="class" x-model="filterClass" @change="fetchLaporan()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-slate-50/50 text-slate-700 cursor-pointer font-medium">
                            <option value="" x-text="classPlaceholder">Semua Kelas / Mapel</option>
                            <template x-for="c in availableClasses" :key="c">
                                <option :value="c" x-text="classCounts[c] ? `${c} (${classCounts[c]})` : c" :selected="filterClass === c"></option>
                            </template>
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

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    if (typeof Chart === 'undefined') {
        document.write('<script src="https://cdn.jsdelivr.net/npm/chart.js"><\/script>');
    }
</script>
<script>
    let hitungCepatCharts = {};

    window.renderHitungCepatCharts = function() {
        if (typeof Chart === 'undefined') {
            setTimeout(window.renderHitungCepatCharts, 200);
            return;
        }

        // 1. Paslon Chart
        const paslonCtx = document.getElementById('paslonChart');
        if (paslonCtx) {
            if (hitungCepatCharts.paslon) {
                hitungCepatCharts.paslon.destroy();
            }
            const candidates = @js($candidates);
            const labels = candidates.map(c => 'Paslon ' + String(c.candidate_number).padStart(2, '0'));
            const data = candidates.map(c => c.ballots_count);
            const colors = candidates.map(c => c.card_color || '#4f46e5');

            hitungCepatCharts.paslon = new Chart(paslonCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Perolehan Suara',
                        data: data,
                        backgroundColor: colors,
                        borderRadius: 10,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const c = candidates[ctx.dataIndex];
                                    return ` ${ctx.raw} Suara (${c ? c.percentage : 0}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { family: 'Plus Jakarta Sans', weight: 'bold' } },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            ticks: { font: { family: 'Plus Jakarta Sans', weight: 'bold' } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. Category Bar Chart (Partisipasi %)
        const catBarCtx = document.getElementById('categoryBarChart');
        if (catBarCtx) {
            if (hitungCepatCharts.catBar) {
                hitungCepatCharts.catBar.destroy();
            }
            hitungCepatCharts.catBar = new Chart(catBarCtx, {
                type: 'bar',
                data: {
                    labels: ['Guru', 'Tendik', 'Siswa'],
                    datasets: [{
                        label: 'Partisipasi (%)',
                        data: [
                            {{ $guruStats->percentage }},
                            {{ $tendikStats->percentage }},
                            {{ $siswaStats->percentage }}
                        ],
                        backgroundColor: ['#059669', '#d97706', '#4f46e5'],
                        borderRadius: 10,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` ${ctx.raw}% Kehadiran`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: (val) => val + '%',
                                font: { family: 'Plus Jakarta Sans', weight: 'bold' }
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            ticks: { font: { family: 'Plus Jakarta Sans', weight: 'bold' } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 3. Category Doughnut Chart (Komposisi Suara Masuk)
        const catPieCtx = document.getElementById('categoryDoughnutChart');
        if (catPieCtx) {
            if (hitungCepatCharts.catPie) {
                hitungCepatCharts.catPie.destroy();
            }
            const votedGuru = {{ $guruStats->voted }};
            const votedTendik = {{ $tendikStats->voted }};
            const votedSiswa = {{ $siswaStats->voted }};
            const hasVotes = (votedGuru + votedTendik + votedSiswa) > 0;

            hitungCepatCharts.catPie = new Chart(catPieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Guru', 'Tendik', 'Siswa'],
                    datasets: [{
                        data: hasVotes ? [votedGuru, votedTendik, votedSiswa] : [1, 1, 1],
                        backgroundColor: hasVotes ? ['#059669', '#d97706', '#4f46e5'] : ['#e2e8f0', '#e2e8f0', '#e2e8f0'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    if (!hasVotes) return ' Belum ada suara masuk';
                                    return ` ${ctx.label}: ${ctx.raw} suara`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 4. Classes Bar Chart (Partisipasi Per Kelas Siswa)
        const classesCtx = document.getElementById('classesBarChart');
        if (classesCtx) {
            if (hitungCepatCharts.classes) {
                hitungCepatCharts.classes.destroy();
            }
            const classesStats = @js($classesStats);
            const labels = classesStats.map(s => s.class);
            const percentages = classesStats.map(s => s.percentage);
            const colors = percentages.map(p => p >= 80 ? '#059669' : (p >= 50 ? '#4f46e5' : '#d97706'));

            hitungCepatCharts.classes = new Chart(classesCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Persentase Hadir (%)',
                        data: percentages,
                        backgroundColor: colors,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const stat = classesStats[ctx.dataIndex];
                                    return ` ${ctx.raw}% (${stat.voted} dari ${stat.total} pemilih)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: (val) => val + '%',
                                font: { family: 'Plus Jakarta Sans', weight: 'bold' }
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        y: {
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 },
                                autoSkip: false
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            if (window.renderHitungCepatCharts) {
                window.renderHitungCepatCharts();
            }
        }, 150);
    });
</script>
@endpush
