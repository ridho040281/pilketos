@extends('layouts.app')

@section('title', 'Bilik Suara Siswa - ' . ($setting->school_name ?? 'Pilketos'))

@section('content')
<div class="min-h-screen flex flex-col justify-between bg-gradient-to-b from-indigo-50/50 via-white to-slate-100 py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="max-w-md w-full mx-auto text-center">
        @if ($setting->school_logo)
            <img class="mx-auto h-16 w-auto mb-3 drop-shadow-sm" src="{{ asset('storage/' . $setting->school_logo) }}" alt="Logo">
        @else
            <div class="mx-auto w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-indigo-600/30 mb-3">
                OSIS
            </div>
        @endif
        <h2 class="text-xs uppercase tracking-widest font-bold text-indigo-600 mb-1">
            {{ $setting->school_name }}
        </h2>
        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
            {{ $setting->election_title }}
        </h1>
        <p class="text-xs text-slate-500 mt-1">Tahun Ajaran {{ $setting->academic_year }}</p>

        <!-- Status TPS Badge -->
        <div class="mt-3 inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $setting->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
            <span class="w-2 h-2 rounded-full {{ $setting->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
            {{ $setting->is_active ? 'TPS Sedang Dibuka' : 'TPS Sedang Ditutup' }}
        </div>
    </div>

    <!-- Login Box -->
    <div class="max-w-md w-full mx-auto my-6">
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/80 border border-slate-200/80 p-6 sm:p-8 backdrop-blur-sm">
            <div class="mb-6 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Bilik Suara Digital</h3>
                <p class="text-xs text-slate-500 mt-1">Ketikkan 6 digit Token / Passcode unik dari Kartu Pemilih Anda</p>
            </div>

            @if (session('error'))
                <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if (! $setting->is_active)
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs text-center leading-relaxed">
                    <p class="font-bold mb-1">Pemberitahuan Panitia</p>
                    <p>Sistem bilik suara sedang dinonaktifkan sementara. Silakan hubungi panitia TPS untuk informasi pembukaan sesi pemungutan suara.</p>
                </div>
            @else
                <form action="{{ route('bilik.masuk') }}" method="POST" class="space-y-5" x-data="{ code: '{{ old('passcode', $initialToken) }}' }">
                    @csrf
                    <div>
                        <label for="passcode" class="block text-xs font-semibold text-slate-600 mb-2 uppercase tracking-wider text-center">
                            Kode Token Pemilih
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="passcode" 
                                id="passcode" 
                                x-model="code"
                                @input="code = code.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                                maxlength="12"
                                required 
                                autofocus
                                placeholder="CONTOH: K7X9P2" 
                                class="block w-full text-center text-2xl sm:text-3xl font-mono tracking-[0.25em] font-extrabold px-4 py-3.5 rounded-2xl border-2 border-slate-300 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-500/20 text-slate-900 uppercase placeholder:text-slate-300 placeholder:font-normal placeholder:tracking-normal placeholder:text-base transition duration-150"
                            >
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full flex items-center justify-center py-4 px-6 rounded-2xl text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] shadow-lg shadow-indigo-600/30 transition-all duration-150"
                    >
                        <span>Masuk ke Bilik Suara</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            @endif

            <!-- Secret Ballot Guarantee Notice -->
            <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-center gap-2 text-slate-400 text-xs">
                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span>Asas LUBER: Pilihan Anda 100% Rahasia & Anonim</span>
            </div>
        </div>
    </div>

    <!-- Footer Links -->
    <div class="max-w-md w-full mx-auto text-center text-xs text-slate-400 space-y-2">
        <p>&copy; {{ date('Y') }} Panitia Pemilihan OSIS. Hak Cipta Dilindungi.</p>
        <div class="flex items-center justify-center space-x-4">
            <a href="{{ route('proyektor.index') }}" target="_blank" class="hover:text-indigo-600 transition-colors">Layar Proyektor</a>
            <span>&bull;</span>
            <a href="{{ route('admin.login') }}" class="hover:text-indigo-600 transition-colors">Login Panitia TPS</a>
        </div>
    </div>
</div>
@endsection
