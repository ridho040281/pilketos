@extends('layouts.admin')

@section('title', 'Pengaturan Pemilihan')
@section('header_title', 'Konfigurasi & Pengaturan TPS')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{
    academicYear: '{{ old('academic_year', $setting->academic_year) }}',
    electionTitle: '{{ old('election_title', $setting->election_title) }}',
    isCustomYear: {{ !in_array(old('academic_year', $setting->academic_year), $academicYears) ? 'true' : 'false' }},
    syncYear(val) {
        if (val === 'custom') {
            this.isCustomYear = true;
            this.$nextTick(() => this.$refs.customInput?.focus());
            return;
        }
        this.isCustomYear = false;
        this.academicYear = val;
        // Automatically sync election title if it contains a year pattern
        if (this.electionTitle && this.electionTitle.match(/[0-9]{4}\/[0-9]{4}/)) {
            this.electionTitle = this.electionTitle.replace(/[0-9]{4}\/[0-9]{4}/g, val);
        }
    }
}">
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900">Pengaturan Umum Pemilihan OSIS</h2>
            <p class="text-xs text-slate-500">Konfigurasi nama sekolah, periode pemilihan, dan sakelar proyektor</p>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Sekolah <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="school_name" value="{{ old('school_name', $setting->school_name) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('school_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Tahun Ajaran / Periode <span class="text-rose-500">*</span>
                        </label>
                        <button 
                            type="button" 
                            x-show="!isCustomYear" 
                            @click="isCustomYear = true; $nextTick(() => $refs.customInput?.focus())"
                            class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
                        >
                            + Input Manual
                        </button>
                        <button 
                            type="button" 
                            x-show="isCustomYear" 
                            @click="isCustomYear = false; academicYear = '{{ $academicYears[1] ?? '2026/2027' }}'; syncYear(academicYear)"
                            class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
                            x-cloak
                        >
                            &larr; Pilih dari Daftar
                        </button>
                    </div>

                    <!-- Dropdown Pilihan Tahun Ajaran Dinamis -->
                    <div x-show="!isCustomYear">
                        <select 
                            x-model="academicYear" 
                            @change="syncYear($event.target.value)"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white text-slate-800"
                        >
                            @foreach ($academicYears as $year)
                                <option value="{{ $year }}" {{ old('academic_year', $setting->academic_year) == $year ? 'selected' : '' }}>
                                    Tahun Ajaran {{ $year }}
                                </option>
                            @endforeach
                            <option value="custom">-- Ketik Manual / Tahun Lain --</option>
                        </select>
                    </div>

                    <!-- Input Manual Jika Ingin Custom -->
                    <div x-show="isCustomYear" x-cloak>
                        <input 
                            type="text" 
                            x-ref="customInput"
                            x-model="academicYear" 
                            placeholder="Contoh: 2026/2027" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-semibold"
                        >
                    </div>

                    <!-- Hidden input to guarantee proper form submit value -->
                    <input type="hidden" name="academic_year" :value="academicYear" value="{{ old('academic_year', $setting->academic_year) }}">
                    @error('academic_year') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Judul Acara Pemilihan <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[11px] text-slate-400">Otomatis sinkron dengan Tahun Ajaran</span>
                </div>
                <input 
                    type="text" 
                    name="election_title" 
                    x-model="electionTitle" 
                    value="{{ old('election_title', $setting->election_title) }}" 
                    required 
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                >
                @error('election_title') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Waktu Mulai Pemilihan
                    </label>
                    <input type="datetime-local" name="start_time" value="{{ old('start_time', $setting->start_time ? $setting->start_time->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Waktu Selesai Pemilihan
                    </label>
                    <input type="datetime-local" name="end_time" value="{{ old('end_time', $setting->end_time ? $setting->end_time->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>

            <!-- Toggles -->
            <div class="pt-4 border-t border-slate-100 space-y-4">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Status TPS Pemilihan</h4>
                        <p class="text-xs text-slate-500">Jika ditutup, bilik suara siswa tidak dapat diakses untuk mencoblos.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $setting->is_active) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Tampilkan Hasil Quick Count di Proyektor</h4>
                        <p class="text-xs text-slate-500">Jika dinonaktifkan (Freeze Mode), grafik perolehan paslon disamarkan di layar proyektor panggung.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_quick_count" value="1" {{ old('show_quick_count', $setting->show_quick_count) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Logo Resmi Sekolah (PNG/SVG, Maks. 2MB)
                    </label>
                    @if ($setting->school_logo)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ asset('storage/' . $setting->school_logo) }}" alt="Logo" class="w-14 h-14 object-contain rounded-xl border border-slate-200 p-1 bg-white">
                            <span class="text-xs text-slate-500">Logo aktif terpasang</span>
                        </div>
                    @endif
                    <input type="file" name="logo" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('logo') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Logo Favicon Browser (PNG/ICO/SVG, Maks. 1MB)
                    </label>
                    @if ($setting->favicon)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ asset('storage/' . $setting->favicon) }}" alt="Favicon" class="w-14 h-14 object-contain rounded-xl border border-slate-200 p-1 bg-white">
                            <span class="text-xs text-slate-500">Favicon aktif terpasang</span>
                        </div>
                    @else
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ $setting->getFaviconUrl() }}" alt="Favicon Default" class="w-14 h-14 object-contain rounded-xl border border-slate-200 p-1 bg-white opacity-70">
                            <span class="text-xs text-slate-400 italic">Favicon bawaan / otomatis</span>
                        </div>
                    @endif
                    <input type="file" name="favicon" accept="image/x-icon,image/png,image/svg+xml,image/jpeg,image/webp,.ico" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('favicon') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            @if ($setting->school_logo || $setting->favicon)
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Jika gambar logo/favicon tampak silang di web hosting, perbarui tautan storage server.</span>
                    </div>
                    <a href="{{ route('storage.link') }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 text-indigo-600 hover:bg-indigo-50 font-semibold transition-colors shrink-0 shadow-sm">
                        Sinkronkan Storage
                    </a>
                </div>
            @endif

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
