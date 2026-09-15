@extends('layouts.admin')

@section('title', 'Integrasi API Database Sekolah')
@section('header_title', 'Integrasi API Database Sekolah (DPT Sync)')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'pull',
    copied: false,
    copyKey(text) {
        navigator.clipboard.writeText(text);
        this.copied = true;
        setTimeout(() => this.copied = false, 2000);
    }
}">
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
            <p class="text-xs text-slate-500 mt-2">Dukungan format SIMAK / CBT / Rapor</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
        <button 
            @click="activeTab = 'pull'" 
            :class="activeTab === 'pull' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-600 hover:bg-slate-100'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Tarik Data dari Database Sekolah (PULL)
        </button>

        <button 
            @click="activeTab = 'push'" 
            :class="activeTab === 'push' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-600 hover:bg-slate-100'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Endpoint API Pilketos (PUSH dari Luar)
        </button>
    </div>

    <!-- TAB 1: PULL DATA DARI DATABASE SEKOLAH -->
    <div x-show="activeTab === 'pull'" class="space-y-6">
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
                        placeholder="Contoh: http://192.168.1.100/simak/api/siswa atau http://madrasah.test/api/students" 
                        required 
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Endpoint ini harus mengembalikan data daftar siswa dalam format JSON (misal kolom <code>nisn</code>, <code>nama</code>, <code>kelas</code>, <code>jk</code>).</p>
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
                            placeholder="Contoh: client_TAKceoaT6jNt" 
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
                        Simpan Pengaturan
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
                Aplikasi Pilketos dirancang fleksibel untuk membaca response JSON dari aplikasi sekolah Anda (baik array langsung maupun di dalam key <code>data</code>, <code>siswa</code>, atau <code>students</code>):
            </p>

            <pre class="bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs font-mono text-emerald-400 overflow-x-auto">
{
  "status": "success",
  "data": [
    {
      "nisn": "20260001",
      "nama": "Ahmad Fauzi",
      "kelas": "X-1",
      "jk": "L"
    },
    {
      "nisn": "20260002",
      "nama": "Citra Dewi",
      "kelas": "X-2",
      "jk": "P"
    }
  ]
}</pre>
        </div>
    </div>

    <!-- TAB 2: PUSH DARI APLIKASI SEKOLAH KE PILKETOS -->
    <div x-show="activeTab === 'push'" class="space-y-6" x-cloak>
        <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Endpoint Inbound API Pilketos</h3>
                <p class="text-xs text-slate-500">Gunakan kredensial ini jika aplikasi sekolah Anda (CodeIgniter / SIMAK) ingin mengirimkan data pemilih langsung ke Pilketos</p>
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

        <!-- Contoh Script Integrasi (cURL & PHP CodeIgniter) -->
        <div class="bg-slate-900 text-slate-300 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                Contoh Script Pengiriman Data dari Aplikasi Sekolah (PHP / CodeIgniter)
            </h4>

            <pre class="bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto">
// Contoh kirim data siswa dari sistem sekolah ke Pilketos:
$apiKey = '{{ $setting->pilketos_api_key }}';
$apiUrl = '{{ url('/api/v1/voters/sync') }}';

$payload = json_encode([
    'students' => [
        ['nisn' => '2026001', 'name' => 'Ahmad Fauzi', 'class' => 'X-1', 'gender' => 'L'],
        ['nisn' => '2026002', 'name' => 'Budi Santoso', 'class' => 'X-1', 'gender' => 'L'],
        // ... (data 2.000 siswa lainnya)
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
curl_close($ch);
echo $response;</pre>
        </div>
    </div>
</div>
@endsection
