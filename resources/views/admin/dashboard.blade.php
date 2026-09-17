@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard Ringkasan Pemilihan')

@section('content')
<div class="space-y-6">
    <!-- Quick Control Bar -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <!-- TPS Status Toggle -->
            <form action="{{ route('admin.toggle-tps') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-sm {{ $setting->is_active ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-emerald-600/30' : 'bg-rose-600 text-white hover:bg-rose-700 shadow-rose-600/30' }}">
                    <span class="w-2.5 h-2.5 rounded-full mr-2 {{ $setting->is_active ? 'bg-white animate-pulse' : 'bg-white' }}"></span>
                    {{ $setting->is_active ? 'TPS: Sedang DIBUKA (Klik utk Tutup)' : 'TPS: Sedang DITUTUP (Klik utk Buka)' }}
                </button>
            </form>

            <!-- Quick Count Freeze Mode Toggle -->
            <form action="{{ route('admin.toggle-quick-count') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-sm {{ $setting->show_quick_count ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-indigo-600/30' : 'bg-amber-500 text-white hover:bg-amber-600 shadow-amber-500/30' }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $setting->show_quick_count ? 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' : 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18' }}"></path></svg>
                    {{ $setting->show_quick_count ? 'Proyektor: Hasil DITAMPILKAN' : 'Proyektor: Hasil DISEMBUNYIKAN (Freeze)' }}
                </button>
            </form>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('proyektor.index') }}" target="_blank" class="inline-flex items-center px-4 py-2.5 rounded-2xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                Buka Layar Proyektor
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Total DPT</span>
                <span class="p-2 rounded-xl bg-slate-50 text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-slate-900">{{ number_format($totalVoters, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">Hak suara terdaftar</div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-indigo-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Suara Masuk</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-indigo-600">{{ number_format($votedCount, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">Surat suara sah tercatat</div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-amber-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Belum Memilih</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-amber-600">{{ number_format($unvotedCount, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">Siswa belum hadir di TPS</div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-emerald-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Partisipasi</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-emerald-600">{{ $turnoutPercentage }}%</div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $turnoutPercentage }}%"></div>
            </div>
        </div>
    </div>

    <!-- Candidate Results Overview (Panitia View) -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Perolehan Suara Pasangan Calon (Internal Panitia)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Data suara sah masuk dari kotak suara digital</p>
            </div>
            <a href="{{ route('admin.candidates.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                Kelola Paslon &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach ($candidates as $c)
                @php
                    $pct = $totalBallots > 0 ? round(($c->ballots_count / $totalBallots) * 100, 1) : 0;
                @endphp
                <div class="p-5 rounded-2xl border-2 border-slate-100 hover:border-indigo-200 transition-colors bg-slate-50/50">
                    <div class="flex items-center space-x-3 mb-3">
                        <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white font-black text-lg flex items-center justify-center shadow-md shadow-indigo-600/30">
                            {{ sprintf('%02d', $c->candidate_number) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-slate-900 truncate">{{ $c->leader_name }}</h4>
                            @if(!empty($c->co_leader_name))
                                <p class="text-xs text-slate-500 truncate">& {{ $c->co_leader_name }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-baseline justify-between mt-4">
                        <span class="text-2xl font-black text-slate-800">{{ number_format($c->ballots_count, 0, ',', '.') }}</span>
                        <span class="text-sm font-bold text-indigo-600">{{ $pct }}%</span>
                    </div>

                    <div class="w-full bg-slate-200 rounded-full h-2 mt-2">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Per-Category Attendance Breakdown (Siswa, Guru, Tendik) -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Rekapitulasi Partisipasi Berdasarkan Kategori</h3>
                <p class="text-xs text-slate-500 mt-0.5">Statistik hak suara Siswa, Guru, dan Tenaga Kependidikan</p>
            </div>
            <a href="{{ route('admin.voters.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                Kelola DPT &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse ($categoryStats as $cat)
                <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800">
                            @if($cat->category === 'guru') 👨‍🏫 Guru
                            @elseif($cat->category === 'tendik') 💼 Tenaga Kependidikan
                            @else 🎓 Siswa
                            @endif
                        </span>
                        <span class="text-xs font-extrabold text-indigo-600">{{ $cat->percentage }}%</span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-sm font-semibold text-slate-600">
                            <strong class="text-slate-900">{{ $cat->voted }}</strong> / {{ $cat->total }} hadir
                        </span>
                        <span class="text-xs text-slate-400">
                            ({{ $cat->total - $cat->voted }} belum)
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5 mt-2">
                        <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $cat->percentage }}%"></div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-4 text-xs text-slate-400">Belum ada data kategori pemilih.</div>
            @endforelse
        </div>
    </div>

    <!-- Per-Class Attendance Breakdown -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Rekapitulasi Partisipasi Per Kelas</h3>
                <p class="text-xs text-slate-500 mt-0.5">Monitor kehadiran pemilih per rombel/tingkat</p>
            </div>
            <a href="{{ route('admin.voters.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                Lihat Detail DPT &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nama Kelas</th>
                        <th class="px-4 py-3">Total Pemilih</th>
                        <th class="px-4 py-3">Sudah Memilih</th>
                        <th class="px-4 py-3">Belum Memilih</th>
                        <th class="px-4 py-3">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse ($classesStats as $stat)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $stat->class }}</td>
                            <td class="px-4 py-3">{{ $stat->total }}</td>
                            <td class="px-4 py-3 text-emerald-600 font-bold">{{ $stat->voted }}</td>
                            <td class="px-4 py-3 text-amber-600">{{ $stat->total - $stat->voted }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <div class="w-24 bg-slate-200 rounded-full h-1.5">
                                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $stat->percentage }}%"></div>
                                    </div>
                                    <span class="font-bold">{{ $stat->percentage }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada data pemilih.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($classesStats->isNotEmpty())
                    <tfoot class="bg-slate-100/90 font-bold text-slate-800 border-t-2 border-slate-300">
                        <tr>
                            <td class="px-4 py-3.5 font-black uppercase text-slate-900">Total Siswa ({{ $classesStats->count() }} Kelas)</td>
                            <td class="px-4 py-3.5 font-black text-slate-900">{{ number_format($classesStats->sum('total'), 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 font-black text-emerald-700">{{ number_format($classesStats->sum('voted'), 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 font-black text-amber-700">{{ number_format($classesStats->sum('total') - $classesStats->sum('voted'), 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 font-black text-indigo-700">
                                {{ $classesStats->sum('total') > 0 ? round(($classesStats->sum('voted') / $classesStats->sum('total')) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
