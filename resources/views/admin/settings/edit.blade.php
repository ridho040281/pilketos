@extends('layouts.admin')

@section('title', 'Pengaturan TPS & Integrasi API')
@section('header_title', 'Konfigurasi & Pengaturan TPS')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    mainTab: '{{ request('tab', 'general') }}',
    academicYear: '{{ old('academic_year', $setting->academic_year) }}',
    electionTitle: '{{ old('election_title', $setting->election_title) }}',
    isCustomYear: {{ !in_array(old('academic_year', $setting->academic_year), $academicYears) ? 'true' : 'false' }},
    apiSubTab: 'pull',
    copied: false,
    copyKey(text) {
        navigator.clipboard.writeText(text);
        this.copied = true;
        setTimeout(() => this.copied = false, 2000);
    },
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
    <!-- Main Navigation Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-3">
        <button 
            type="button"
            @click="mainTab = 'general'" 
            :class="mainTab === 'general' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-bold' : 'text-slate-600 hover:bg-slate-100 font-semibold'"
            class="px-5 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span>Pengaturan Umum TPS</span>
        </button>

        <button 
            type="button"
            @click="mainTab = 'api'" 
            :class="mainTab === 'api' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-bold' : 'text-slate-600 hover:bg-slate-100 font-semibold'"
            class="px-5 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span>Integrasi API Database Sekolah (DPT Sync)</span>
            @if(!empty($setting->school_api_url))
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            @endif
        </button>

        <button 
            type="button"
            @click="mainTab = 'api_guru'" 
            :class="mainTab === 'api_guru' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-bold' : 'text-slate-600 hover:bg-slate-100 font-semibold'"
            class="px-5 py-2.5 rounded-2xl text-xs transition-all flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Integrasi API Guru</span>
            @if(!empty($setting->guru_api_url))
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            @endif
        </button>
    </div>

    <!-- ==================== TAB 1: PENGATURAN UMUM TPS ==================== -->
    <div x-show="mainTab === 'general'" class="space-y-6">
        <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Pengaturan Umum Pemilihan OSIS</h2>
                <p class="text-xs text-slate-500">Konfigurasi nama sekolah, periode pemilihan, sakelar proyektor, logo resmi, dan favicon browser</p>
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
                                @click="isCustomYear = !isCustomYear; if(isCustomYear) $nextTick(() => $refs.customInput?.focus())"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 transition-colors"
                            >
                                <span x-text="isCustomYear ? 'Pilih dari Daftar' : '+ Input Manual'"></span>
                            </button>
                        </div>

                        <!-- Dropdown Select 3 Tahun -->
                        <div x-show="!isCustomYear">
                            <select 
                                x-model="academicYear" 
                                @change="syncYear($event.target.value)"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                            >
                                @foreach($academicYears as $year)
                                    <option value="{{ $year }}" {{ old('academic_year', $setting->academic_year) === $year ? 'selected' : '' }}>
                                        Tahun Ajaran {{ $year }}
                                    </option>
                                @endforeach
                                <option value="custom">-- Ketik Manual / Tahun Lain --</option>
                            </select>
                        </div>

                        <!-- Input Text Kustom -->
                        <div x-show="isCustomYear" class="space-y-1" x-cloak>
                            <input 
                                type="text" 
                                x-ref="customInput"
                                x-model="academicYear"
                                name="academic_year" 
                                value="{{ old('academic_year', $setting->academic_year) }}" 
                                placeholder="Format: 2026/2027" 
                                required 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-semibold"
                            >
                            <p class="text-[11px] text-slate-400">Contoh format standar: <code>2027/2028</code></p>
                        </div>
                        @error('academic_year') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Judul Acara Pemilihan <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-slate-400 font-medium">Otomatis sinkron dengan Tahun Ajaran</span>
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
                        @error('start_time') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Waktu Selesai Pemilihan
                        </label>
                        <input type="datetime-local" name="end_time" value="{{ old('end_time', $setting->end_time ? $setting->end_time->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                        @error('end_time') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="space-y-4 pt-2">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Status TPS Pemilihan</h4>
                            <p class="text-xs text-slate-500">Jika ditutup, bilik suara siswa tidak dapat diakses untuk mencoblos.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $setting->is_active) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
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

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Tampilkan QR Code pada Kartu Pemilih</h4>
                            <p class="text-xs text-slate-500">Jika dinonaktifkan, kartu pemilih dicetak tanpa QR Code dengan posisi Kode Token berada di tengah (Center) dan berukuran lebih besar.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_qr_code" value="1" {{ old('show_qr_code', $setting->show_qr_code ?? true) ? 'checked' : '' }} class="sr-only peer">
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

        <!-- Zona Bahaya: Reset Suara Pemilihan -->
        <div class="bg-white rounded-3xl border border-rose-200/80 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5 text-rose-700 font-bold text-base">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </div>
                        <span>Zona Bahaya: Reset Seluruh Suara Pemilihan</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed max-w-2xl">
                        Tindakan ini akan <strong>menghapus seluruh surat suara di kotak suara</strong> dan mengembalikan status semua pemilih (DPT) menjadi <strong>Belum Memilih</strong>. Gunakan opsi ini saat simulasi/uji coba selesai untuk memulai pemungutan suara resmi dari awal (0 suara).
                    </p>
                </div>
                <button 
                    type="button" 
                    @click="showResetModal = true" 
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/30 transition-colors shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Reset Suara Pemilihan
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 2: INTEGRASI API SEKOLAH ==================== -->
    <div x-show="mainTab === 'api'" class="space-y-6" x-cloak>
        <!-- Top KPI Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Total DPT Saat Ini</span>
                <div class="text-2xl font-black text-slate-900">{{ number_format($totalVoters, 0, ',', '.') }} Pemilih</div>
                <p class="text-xs text-slate-500 mt-1">{{ number_format($votedCount, 0, ',', '.') }} telah menggunakan hak suara</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Terakhir Sinkronisasi</span>
                <div class="text-base font-bold text-slate-900">
                    {{ $setting->last_sync_at ? $setting->last_sync_at->format('d/m/Y H:i') . ' WIB' : 'Belum Pernah' }}
                </div>
                <p class="text-xs text-indigo-600 font-semibold mt-1">
                    {{ $setting->last_sync_count ? number_format($setting->last_sync_count, 0, ',', '.') . ' data diproses' : '-' }}
                </p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Status Integrasi API</span>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ !empty($setting->school_api_url) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    <span class="w-2 h-2 rounded-full {{ !empty($setting->school_api_url) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ !empty($setting->school_api_url) ? 'Terkoneksi ke Database Sekolah' : 'Belum Konfigurasi URL API' }}
                </div>
                <p class="text-xs text-slate-500 mt-2">Dukungan format SPMB / CBT / Rapor</p>
            </div>
        </div>

        <!-- Sub-tabs API (PULL vs PUSH) -->
        <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
            <button 
                type="button"
                @click="apiSubTab = 'pull'" 
                :class="apiSubTab === 'pull' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Tarik Data dari Database Sekolah (PULL)
            </button>

            <button 
                type="button"
                @click="apiSubTab = 'push'" 
                :class="apiSubTab === 'push' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Endpoint Inbound Pilketos (PUSH dari Luar)
            </button>
        </div>

        <!-- SUB-TAB 1: PULL DATA DARI SEKOLAH -->
        <div x-show="apiSubTab === 'pull'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <div class="mb-6 pb-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Konfigurasi API Database Sekolah</h3>
                        <p class="text-xs text-slate-500">Masukkan kredensial API yang tertera pada aplikasi database madrasah/sekolah Anda</p>
                    </div>

                    @if(!empty($setting->school_api_url))
                        <form action="{{ route('admin.api-integration.pull') }}" method="POST" onsubmit="return confirm('Tarik dan sinkronkan data siswa dari API sekolah sekarang?')">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-lg shadow-emerald-600/30 flex items-center gap-2 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Tarik & Sinkronkan Data Sekarang
                            </button>
                        </form>
                    @endif
                </div>

                <form action="{{ route('admin.api-integration.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            URL Endpoint API Siswa Sekolah <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="url" 
                            name="school_api_url" 
                            value="{{ old('school_api_url', $setting->school_api_url) }}" 
                            placeholder="Contoh: https://spmb.mtsn1blitar.sch.id/api/sync/active-students" 
                            required 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-mono text-xs"
                        >
                        <p class="text-[11px] text-slate-400 mt-1">Endpoint resmi SPMB: <code>https://spmb.mtsn1blitar.sch.id/api/sync/active-students</code></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Legacy API Key / Token
                            </label>
                            <input 
                                type="text" 
                                name="school_api_key" 
                                value="{{ old('school_api_key', $setting->school_api_key) }}" 
                                placeholder="Contoh: 41775c7b9361f5c7e04c835e49d7" 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-mono text-xs"
                            >
                            <p class="text-[10px] text-slate-400 mt-1">Dikirim via header <code>X-API-KEY</code> atau <code>Bearer</code>.</p>
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Client ID (Opsional)
                            </label>
                            <input 
                                type="text" 
                                name="school_api_client_id" 
                                value="{{ old('school_api_client_id', $setting->school_api_client_id) }}" 
                                placeholder="Contoh: client_GAIOyvd2URmW" 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-mono text-xs"
                            >
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Client Secret (Opsional)
                            </label>
                            <input 
                                type="password" 
                                name="school_api_secret" 
                                value="{{ old('school_api_secret', $setting->school_api_secret) }}" 
                                placeholder="••••••••••••••••" 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 font-mono text-xs"
                            >
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-colors">
                            Simpan Pengaturan API
                        </button>
                    </div>
                </form>
            </div>

            <!-- Panduan Format JSON API Sekolah -->
            <div class="bg-slate-900 text-slate-300 rounded-3xl p-6 sm:p-8 shadow-sm">
                <h4 class="text-sm font-bold text-white mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Format Response JSON yang Didukung Pilketos
                </h4>
                <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                    Aplikasi Pilketos secara cerdas membaca response JSON dari aplikasi sekolah Anda (mendukung key <code>data</code>, <code>siswa</code>, atau <code>students</code>):
                </p>

                <pre class="bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs font-mono text-emerald-400 overflow-x-auto">
{
  "status": "success",
  "data": [
    {
      "nisn": "3149083061",
      "nama": "ABID AQELA PRASAJA",
      "kelas": "71",
      "gender": "L"
    }
  ]
}</pre>
            </div>
        </div>

        <!-- SUB-TAB 2: PUSH DARI APLIKASI SEKOLAH KE PILKETOS -->
        <div x-show="apiSubTab === 'push'" class="space-y-6" x-cloak>
            <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <div class="mb-6 pb-4 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Endpoint Inbound API Pilketos</h3>
                    <p class="text-xs text-slate-500">Gunakan kredensial ini jika aplikasi sekolah Anda ingin mengirimkan data pemilih secara otomatis ke Pilketos</p>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            URL API Endpoint Pilketos (Method: POST)
                        </label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                readonly 
                                value="{{ url('/api/v1/voters/sync') }}" 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-xs font-mono font-bold"
                            >
                            <button 
                                type="button"
                                @click="copyKey('{{ url('/api/v1/voters/sync') }}')" 
                                class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors shrink-0"
                            >
                                Salin URL
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            API Key Pilketos (Wajib Disertakan pada Header <code>X-API-KEY</code>)
                        </label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                readonly 
                                value="{{ $setting->pilketos_api_key }}" 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-indigo-700 text-xs font-mono font-extrabold"
                            >
                            <button 
                                type="button"
                                @click="copyKey('{{ $setting->pilketos_api_key }}')" 
                                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors shrink-0 flex items-center gap-1"
                            >
                                <span x-text="copied ? 'Tersalin!' : 'Salin Key'"></span>
                            </button>
                            <form action="{{ route('admin.api-integration.regenerate-key') }}" method="POST" onsubmit="return confirm('Buat ulang API Key? Key lama tidak akan berlaku lagi.')">
                                @csrf
                                <button type="submit" class="px-3 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-50 text-xs font-semibold shrink-0">
                                    Refresh Key
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contoh Script Integrasi -->
            <div class="bg-slate-900 text-slate-300 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    Contoh Script Pengiriman Data dari Aplikasi Sekolah (PHP / CodeIgniter / Laravel)
                </h4>

                <pre class="bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto">
// Kirim data siswa langsung ke Pilketos:
$apiKey = '{{ $setting->pilketos_api_key }}';
$apiUrl = '{{ url('/api/v1/voters/sync') }}';

$payload = json_encode([
    'students' => [
        ['nisn' => '2026001', 'name' => 'Ahmad Fauzi', 'class' => 'X-1', 'gender' => 'L'],
        ['nisn' => '2026002', 'name' => 'Budi Santoso', 'class' => 'X-1', 'gender' => 'L'],
    ]
]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-KEY: ' . $apiKey,
]);

$response = curl_exec($ch);
curl_close($ch);</pre>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 3: INTEGRASI API GURU ==================== -->
    <div x-show="mainTab === 'api_guru'" class="space-y-6" x-cloak>
        <!-- Top KPI Overview Guru -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Pemilih Guru (DPT)</span>
                <div class="text-2xl font-black text-slate-900">{{ number_format($totalGuruVoters, 0, ',', '.') }} Guru</div>
                <p class="text-xs text-slate-500 mt-1">{{ number_format($votedGuruCount, 0, ',', '.') }} telah menggunakan hak suara</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Terakhir Sinkronisasi Guru</span>
                <div class="text-base font-bold text-slate-900">
                    {{ $setting->last_guru_sync_at ? $setting->last_guru_sync_at->format('d/m/Y H:i') . ' WIB' : 'Belum Pernah' }}
                </div>
                <p class="text-xs text-indigo-600 font-semibold mt-1">
                    {{ $setting->last_guru_sync_count ? number_format($setting->last_guru_sync_count, 0, ',', '.') . ' data diproses' : '-' }}
                </p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Status Koneksi Guru</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="w-2.5 h-2.5 rounded-full {{ !empty($setting->guru_api_url) ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-amber-400 ring-4 ring-amber-100' }}"></span>
                        <span class="text-sm font-bold {{ !empty($setting->guru_api_url) ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ !empty($setting->guru_api_url) ? 'Endpoint Terhubung' : 'Belum Dikonfigurasi' }}
                        </span>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-2 truncate">{{ $setting->guru_api_url ?: 'URL belum diisi' }}</p>
            </div>
        </div>

        <!-- Form Konfigurasi API Guru -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Konfigurasi Integrasi API Guru</h3>
                    <p class="text-xs text-slate-500">Hubungkan Pilketos ke endpoint API Guru sekolah untuk menarik daftar guru dan tenaga pendidik secara otomatis</p>
                </div>
            </div>

            <form action="{{ route('admin.api-integration.update-guru') }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        URL Endpoint API Guru
                    </label>
                    <div class="relative">
                        <input 
                            type="url" 
                            name="guru_api_url" 
                            value="{{ old('guru_api_url', $setting->guru_api_url ?? 'https://jadwal.mtsn1blitar.sch.id/api/v1/sync/guru') }}" 
                            placeholder="https://jadwal.mtsn1blitar.sch.id/api/v1/sync/guru"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                        >
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">URL Endpoint e-Jadwal MTsN 1 Blitar: <code class="text-indigo-600 font-mono font-semibold">https://jadwal.mtsn1blitar.sch.id/api/v1/sync/guru</code></p>
                    @error('guru_api_url') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Client ID
                        </label>
                        <input 
                            type="text" 
                            name="guru_api_client_id" 
                            value="{{ old('guru_api_client_id', $setting->guru_api_client_id ?? 'client_edp3yftse3bxcrcf') }}" 
                            placeholder="client_edp3yftse3bxcrcf"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                        >
                        <p class="text-[11px] text-slate-400 mt-1">Dikirim otomatis melalui HTTP header <code class="text-indigo-600 font-mono">X-CLIENT-ID</code>.</p>
                        @error('guru_api_client_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Client Secret
                        </label>
                        <input 
                            type="text" 
                            name="guru_api_secret" 
                            value="{{ old('guru_api_secret', $setting->guru_api_secret ?? 'EgUmiD5xGq1v6IdUMlTvxGModoUOoCjCIKncKu2I') }}" 
                            placeholder="EgUmiD5xGq1v6IdUMlTvxGModoUOoCjCIKncKu2I"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                        >
                        <p class="text-[11px] text-slate-400 mt-1">Dikirim otomatis melalui HTTP header <code class="text-indigo-600 font-mono">X-CLIENT-SECRET</code>.</p>
                        @error('guru_api_secret') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        API Token
                    </label>
                    <textarea 
                        name="guru_api_token" 
                        rows="2"
                        placeholder="UOcvFMOE4fPisQxFDh1W7Q77wx6glE9P87wkcOswAd6RtxVLFvSmV3rrbXGW"
                        class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs font-mono focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                    >{{ old('guru_api_token', $setting->guru_api_token ?? 'UOcvFMOE4fPisQxFDh1W7Q77wx6glE9P87wkcOswAd6RtxVLFvSmV3rrbXGW') }}</textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Dikirim via header <code class="text-indigo-600 font-mono">Authorization: Bearer &lt;token&gt;</code> dan <code class="text-indigo-600 font-mono">X-API-TOKEN</code>.</p>
                    @error('guru_api_token') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-colors">
                        Simpan Pengaturan API Guru
                    </button>
                </div>
            </form>
        </div>

        <!-- Tombol Tarik & Sinkronkan Data Guru -->
        <div class="bg-gradient-to-br from-indigo-50/70 to-purple-50/50 rounded-3xl border border-indigo-100 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100/80 text-indigo-700 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Tarik & Sinkronkan Data DPT Guru</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">Sinkronkan Data Guru ke DPT Pilketos</h3>
                    <p class="text-xs text-slate-600 max-w-2xl leading-relaxed">
                        Sistem akan menghubungi Endpoint API Guru di atas, mengunduh data daftar guru, dan menyimpannya secara otomatis ke tabel DPT dengan kategori <strong>Guru</strong>. Setiap guru yang baru akan diberikan kode coblos unik (passcode). Jika guru sudah ada, data nama dan jabatan akan diperbarui tanpa membuat duplikat.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                    <!-- Tombol Bersihkan Duplikat Guru -->
                    <form action="{{ route('admin.api-integration.clean-duplicate-guru') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data guru yang memiliki nama dobel/duplikat? Sistem akan secara cerdas mempertahankan data yang sudah memilih atau memiliki NIP.')">
                        @csrf
                        <button 
                            type="submit" 
                            title="Hapus data guru yang berulang/duplikat secara aman"
                            class="w-full sm:w-auto px-5 py-3.5 rounded-2xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer shadow-sm"
                        >
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Bersihkan Duplikat Guru</span>
                        </button>
                    </form>

                    <!-- Tombol Tarik API -->
                    <form action="{{ route('admin.api-integration.pull-guru') }}" method="POST" onsubmit="return confirm('Mulai tarik data guru dari API sekolah? Proses ini akan menambahkan guru ke DPT.')">
                        @csrf
                        <button 
                            type="submit" 
                            @if(empty($setting->guru_api_url)) disabled @endif
                            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl {{ !empty($setting->guru_api_url) ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-xl shadow-indigo-600/30' : 'bg-slate-200 text-slate-400 cursor-not-allowed' }} font-bold text-xs flex items-center justify-center gap-2.5 transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span>Tarik & Sinkronkan Data Guru Sekarang</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Petunjuk Format Data API Guru -->
            <div class="mt-6 pt-6 border-t border-indigo-100/80 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600">
                <div class="bg-white/80 p-4 rounded-2xl border border-indigo-50">
                    <h5 class="font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Format Field yang Didukung
                    </h5>
                    <ul class="space-y-1 text-slate-500 list-disc list-inside">
                        <li><strong>Identitas:</strong> <code class="text-indigo-600">nip</code>, <code class="text-indigo-600">nuptk</code>, <code class="text-indigo-600">nik</code>, atau <code class="text-indigo-600">id</code></li>
                        <li><strong>Nama:</strong> <code class="text-indigo-600">nama</code>, <code class="text-indigo-600">name</code>, atau <code class="text-indigo-600">nama_lengkap</code></li>
                        <li><strong>Jabatan / Mapel:</strong> <code class="text-indigo-600">mapel</code>, <code class="text-indigo-600">jabatan</code>, atau <code class="text-indigo-600">kelas</code></li>
                        <li><strong>Jenis Kelamin:</strong> <code class="text-indigo-600">jk</code> atau <code class="text-indigo-600">gender</code> ('L' / 'P')</li>
                    </ul>
                </div>

                <div class="bg-white/80 p-4 rounded-2xl border border-indigo-50">
                    <h5 class="font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Otomasi & Keamanan Data
                    </h5>
                    <p class="text-slate-500 leading-relaxed">
                        Data pemilih guru langsung masuk ke daftar DPT Pilketos dengan kategori <strong>Guru</strong>. Token/passcode coblos dibuat secara acak dan unik. Kartu suara untuk guru dapat langsung dicetak pada menu Manajemen DPT.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
