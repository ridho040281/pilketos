@extends('layouts.app')

@section('title', 'Login Panitia & Admin - ' . ($setting->school_name ?? 'Pilketos'))

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center bg-slate-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-indigo-600 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-indigo-600/30">
                P
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Panel Panitia Pemilihan</h2>
            <p class="text-xs text-slate-500 mt-1">{{ $setting->school_name }} &bull; {{ $setting->election_title }}</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/80 border border-slate-200 p-8">
            @if (session('error'))
                <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-start gap-2">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-start gap-2">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="login" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Username / Email
                    </label>
                    <input 
                        type="text" 
                        name="login" 
                        id="login" 
                        value="{{ old('login') }}" 
                        required 
                        autofocus 
                        placeholder="Contoh: admin" 
                        class="block w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition-colors"
                    >
                    @error('login')
                        <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        placeholder="••••••••" 
                        class="block w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition-colors"
                    >
                    @error('password')
                        <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                        <span class="ml-2">Ingat saya</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="w-full mt-2 py-3.5 px-4 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] shadow-lg shadow-indigo-600/30 transition-all duration-150"
                >
                    Masuk ke Panel Panitia
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <a href="{{ route('bilik.login') }}" class="text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Halaman Bilik Suara
                </a>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            Default Panitia: <code>admin</code> / <code>admin123</code>
        </div>
    </div>
</div>
@endsection
