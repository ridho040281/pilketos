@extends('layouts.app')

@section('title', 'Suara Berhasil Direkam - ' . ($setting->school_name ?? 'Pilketos'))

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-emerald-50 via-white to-slate-100 p-4" x-data="{
    seconds: 3,
    init() {
        let timer = setInterval(() => {
            this.seconds--;
            if (this.seconds <= 0) {
                clearInterval(timer);
                window.location.href = '{{ route('bilik.login') }}';
            }
        }, 1000);
    }
}">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-emerald-500/10 border border-emerald-100 p-8 text-center animate-fade-in">
        <!-- Animated Success Icon -->
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-emerald-100 border-4 border-emerald-200 text-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
            <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
        </div>

        <span class="inline-block text-xs font-extrabold uppercase tracking-widest text-emerald-600 mb-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200">
            Suara Sah Tercatat
        </span>

        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Terima Kasih!</h1>
        <p class="text-sm text-slate-600 mt-2 leading-relaxed">
            Hak suara Anda telah berhasil dimasukkan ke dalam kotak suara secara sah, rahasia, dan anonim.
        </p>

        <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-500 text-left space-y-1.5">
            <div class="flex items-center text-slate-700 font-semibold">
                <svg class="w-4 h-4 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                Token pemilih telah dinonaktifkan
            </div>
            <p>Silakan tinggalkan bilik suara dan persilakan pemilih berikutnya untuk menggunakan bilik.</p>
        </div>

        <!-- Countdown auto-logout -->
        <div class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 bg-slate-100 px-4 py-2 rounded-full">
            <svg class="w-4 h-4 text-slate-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Otomatis kembali dalam <span class="text-emerald-600 font-extrabold text-sm" x-text="seconds">3</span> detik...
        </div>

        <div class="mt-6 pt-4 border-t border-slate-100">
            <a href="{{ route('bilik.login') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                Klik di sini jika tidak beralih otomatis &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
