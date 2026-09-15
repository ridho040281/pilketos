@extends('layouts.app')

@section('title', 'Surat Suara Digital - ' . ($setting->school_name ?? 'Pilketos'))

@php
    $candidatesJson = $candidates->mapWithKeys(function ($candidate) {
        return [
            $candidate->id => [
                'id' => $candidate->id,
                'number' => sprintf('%02d', $candidate->candidate_number),
                'leader' => $candidate->leader_name,
                'coLeader' => $candidate->co_leader_name ?? '',
                'vision' => $candidate->vision ?: 'Tidak ada keterangan visi.',
                'mission' => $candidate->mission ?: 'Tidak ada keterangan misi.',
            ],
        ];
    });
@endphp

@section('content')
<div class="min-h-screen flex flex-col bg-slate-100 py-6 px-4 sm:px-6 lg:px-8" 
     x-data="{
         candidatesData: @js($candidatesJson),
         showConfirmModal: false,
         showVisionModal: false,
         selectedCandidate: null,
         activeVision: { number: '', leader: '', coLeader: '', vision: '', mission: '' },
         isSubmitting: false,
         openVision(id) {
             const c = this.candidatesData[id];
             if (!c) return;
             this.activeVision = c;
             this.showVisionModal = true;
         },
         confirmVote(id) {
             const c = this.candidatesData[id];
             if (!c) return;
             this.selectedCandidate = c;
             this.showConfirmModal = true;
         }
     }"
     @keydown.escape.window="showVisionModal = false; if(!isSubmitting) showConfirmModal = false">
    <!-- Top Nav Header -->
    <div class="max-w-6xl w-full mx-auto mb-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white px-6 py-4 rounded-3xl shadow-sm border border-slate-200">
        <div class="flex items-center space-x-3 text-center sm:text-left">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                {{ substr($voterName ?? 'P', 0, 1) }}
            </div>
            <div>
                <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Pemilih Terdaftar &bull; {{ $voterCategoryLabel ?? 'Siswa' }}</div>
                <div class="text-base font-bold text-slate-800">{{ $voterName }} <span class="text-indigo-600 font-medium">({{ $voterClass }})</span></div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                Bilik Suara Aktif
            </span>
            <form action="{{ route('bilik.keluar') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari bilik suara?')">
                @csrf
                <button type="submit" class="text-xs text-slate-500 hover:text-rose-600 px-3 py-1.5 rounded-xl hover:bg-rose-50 transition-colors font-medium">
                    Batal & Keluar
                </button>
            </form>
        </div>
    </div>

    <!-- Instruction Title -->
    <div class="max-w-7xl w-full mx-auto text-center mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">SURAT SUARA DIGITAL</h1>
        <p class="text-sm text-slate-600 mt-1 max-w-xl mx-auto">
            Gunakan hak pilih Anda dengan bijak. Klik tombol <strong>Coblos</strong> pada calon pilihan Anda.
        </p>
    </div>

    <!-- Candidates Grid (Responsive: 4 sejajar pada layar desktop) -->
    @php
        $candidateCount = count($candidates);
        $gridConfig = match(true) {
            $candidateCount === 1 => 'max-w-md grid-cols-1',
            $candidateCount === 2 => 'max-w-3xl grid-cols-1 sm:grid-cols-2',
            $candidateCount === 3 => 'max-w-6xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            default => 'max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        };
    @endphp
    <div class="w-full mx-auto grid {{ $gridConfig }} gap-4 sm:gap-5 pb-12">
        @foreach ($candidates as $candidate)
            <div class="bg-white rounded-3xl shadow-md shadow-slate-200/60 border-2 border-slate-200 hover:border-indigo-400 hover:shadow-xl transition-all duration-200 flex flex-col overflow-hidden relative group">
                <!-- Top Number Badge -->
                <div class="p-3.5 sm:p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nomor Urut</span>
                    <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-indigo-600 text-white font-black text-lg sm:text-xl flex items-center justify-center shadow-md shadow-indigo-600/30">
                        {{ sprintf('%02d', $candidate->candidate_number) }}
                    </span>
                </div>

                <!-- Photo Container -->
                <div class="relative bg-slate-100 h-52 sm:h-56 overflow-hidden flex items-center justify-center">
                    @if ($candidate->photo_path)
                        <img src="{{ asset('storage/' . $candidate->photo_path) }}" alt="{{ $candidate->leader_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                        <!-- Modern Avatar Silhouette Placeholder -->
                        <div class="flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                            <div class="w-20 h-20 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-2xl mb-1.5">
                                {{ sprintf('%02d', $candidate->candidate_number) }}
                            </div>
                            <span class="text-[11px] font-medium text-slate-400">Foto Calon</span>
                        </div>
                    @endif
                </div>

                <!-- Candidate Info -->
                <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                    <div class="space-y-3 mb-6">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-600">Calon Ketua</span>
                            <h3 class="text-lg font-bold text-slate-900 leading-tight">{{ $candidate->leader_name }}</h3>
                        </div>
                        @if(!empty($candidate->co_leader_name))
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Calon Wakil Ketua</span>
                                <h4 class="text-base font-semibold text-slate-700 leading-tight">{{ $candidate->co_leader_name }}</h4>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2.5">
                        <!-- Button Visi Misi Modal -->
                        <button 
                            type="button" 
                            @click="openVision({{ $candidate->id }})"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 active:scale-[0.98] transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Lihat Visi & Misi
                        </button>

                        <!-- Button Coblos -->
                        <button 
                            type="button" 
                            @click="confirmVote({{ $candidate->id }})"
                            class="w-full py-3.5 px-4 rounded-2xl text-sm font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            COBLOS {{ !empty($candidate->co_leader_name) ? 'PASLON' : 'CALON' }} {{ sprintf('%02d', $candidate->candidate_number) }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal Visi & Misi -->
    <div x-show="showVisionModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop Blur (Klik untuk auto close) -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity cursor-pointer" 
             @click="showVisionModal = false"></div>

        <!-- Wrapper Dialog (Klik area luar kartu untuk auto close) -->
        <div class="min-h-full flex items-center justify-center p-4 cursor-pointer" 
             @click="showVisionModal = false">
            
            <!-- Kartu Modal (click.stop agar klik isi modal tidak menutup) -->
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-left z-10 cursor-default" 
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95">
                
                <!-- Modal Header dengan Tombol Silang (X) -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <span class="w-10 h-10 rounded-2xl bg-indigo-600 text-white font-black text-lg flex items-center justify-center shadow-md shadow-indigo-600/30 shrink-0" 
                              x-text="activeVision.number"></span>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Visi & Misi Kandidat</span>
                            <h3 class="text-base sm:text-lg font-bold text-slate-800 leading-tight" x-text="activeVision.leader"></h3>
                            <p class="text-xs text-slate-500 font-medium" x-show="activeVision.coLeader" x-text="'& ' + activeVision.coLeader"></p>
                        </div>
                    </div>
                    
                    <!-- Tombol Silang (X) -->
                    <button type="button" 
                            @click="showVisionModal = false" 
                            class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 p-2 rounded-2xl transition-colors cursor-pointer" 
                            title="Tutup (Esc)">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Konten Visi & Misi dengan Scroll -->
                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            Visi
                        </h4>
                        <div class="text-sm text-slate-700 bg-indigo-50/60 border border-indigo-100/60 p-4 rounded-2xl leading-relaxed whitespace-pre-line" x-text="activeVision.vision"></div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Misi
                        </h4>
                        <div class="text-sm text-slate-700 bg-slate-50 border border-slate-200/60 p-4 rounded-2xl leading-relaxed whitespace-pre-line" x-text="activeVision.mission"></div>
                    </div>
                </div>

                <!-- Footer Tombol Tutup -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end">
                    <button type="button" 
                            @click="showVisionModal = false" 
                            class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Coblos -->
    <div x-show="showConfirmModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop Blur (Klik untuk auto close jika tidak sedang submit) -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity cursor-pointer" 
             @click="if (!isSubmitting) showConfirmModal = false"></div>

        <!-- Wrapper Dialog (Klik area luar kartu untuk auto close) -->
        <div class="min-h-full flex items-center justify-center p-4 cursor-pointer" 
             @click="if (!isSubmitting) showConfirmModal = false">
            
            <div class="relative bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-center z-10 cursor-default" 
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95">
                
                <!-- Tombol Silang (X) untuk Konfirmasi Modal -->
                <button type="button" 
                        :disabled="isSubmitting"
                        @click="showConfirmModal = false" 
                        class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 p-2 rounded-2xl transition-colors cursor-pointer" 
                        title="Tutup (Esc)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="w-16 h-16 mx-auto mb-4 rounded-3xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>

                <h3 class="text-xl font-extrabold text-slate-900">Konfirmasi Suara Anda</h3>
                <p class="text-xs text-slate-500 mt-1">Apakah Anda yakin memilih kandidat calon berikut?</p>

                <div class="my-5 p-4 rounded-2xl bg-indigo-50/80 border border-indigo-200 text-slate-800 text-left flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white font-black text-xl flex items-center justify-center shrink-0 shadow-md shadow-indigo-600/30" x-text="selectedCandidate?.number">
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-indigo-700 font-semibold uppercase tracking-wider" x-text="selectedCandidate?.coLeader ? ('Pasangan Calon No. ' + selectedCandidate?.number) : ('Calon No. ' + selectedCandidate?.number)"></div>
                        <div class="text-sm font-bold text-slate-900 truncate" x-text="selectedCandidate?.leader"></div>
                        <div class="text-xs text-slate-600 truncate" x-show="selectedCandidate?.coLeader" x-text="'& ' + selectedCandidate?.coLeader"></div>
                    </div>
                </div>

                <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs mb-6 text-left flex items-start gap-2">
                    <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span><strong>PENTING:</strong> Pilihan Anda bersifat <em>final</em> dan tidak dapat diubah setelah tombol kirim diklik.</span>
                </div>

                <form action="{{ route('bilik.coblos') }}" method="POST" @submit="isSubmitting = true">
                    @csrf
                    <input type="hidden" name="candidate_id" :value="selectedCandidate?.id">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button 
                            type="button" 
                            :disabled="isSubmitting"
                            @click="showConfirmModal = false" 
                            class="py-3 px-4 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Kembali Periksa
                        </button>

                        <button 
                            type="submit" 
                            :disabled="isSubmitting"
                            class="py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white text-xs font-extrabold shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <span x-show="!isSubmitting">Ya, Kirim Suara</span>
                            <span x-show="isSubmitting" class="flex items-center gap-1" x-cloak>
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
