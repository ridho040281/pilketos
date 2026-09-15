@extends('layouts.admin')

@section('title', 'Edit Data Pemilih')
@section('header_title', 'Edit Data Pemilih')

@section('content')
<div class="max-w-xl mx-auto space-y-6" x-data="{ category: '{{ old('category', $voter->category ?? 'siswa') }}' }">
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Perbarui Data Pemilih</h2>
                <p class="text-xs text-slate-500">Token: <code class="font-bold text-indigo-600">{{ $voter->passcode }}</code></p>
            </div>
            <a href="{{ route('admin.voters.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('admin.voters.update', $voter) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Pilihan Kategori Pemilih -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Kategori Pemilih <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-3 gap-2.5">
                    <label 
                        class="cursor-pointer border-2 rounded-2xl p-3 text-center transition-all flex flex-col items-center justify-center gap-1.5"
                        :class="category === 'siswa' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold shadow-sm' : 'border-slate-200 hover:border-slate-300 text-slate-600 font-semibold'"
                    >
                        <input type="radio" name="category" value="siswa" x-model="category" class="sr-only">
                        <span class="text-xl">🎓</span>
                        <span class="text-xs">Siswa</span>
                    </label>

                    <label 
                        class="cursor-pointer border-2 rounded-2xl p-3 text-center transition-all flex flex-col items-center justify-center gap-1.5"
                        :class="category === 'guru' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold shadow-sm' : 'border-slate-200 hover:border-slate-300 text-slate-600 font-semibold'"
                    >
                        <input type="radio" name="category" value="guru" x-model="category" class="sr-only">
                        <span class="text-xl">👨‍🏫</span>
                        <span class="text-xs">Guru</span>
                    </label>

                    <label 
                        class="cursor-pointer border-2 rounded-2xl p-3 text-center transition-all flex flex-col items-center justify-center gap-1.5"
                        :class="category === 'tendik' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 font-bold shadow-sm' : 'border-slate-200 hover:border-slate-300 text-slate-600 font-semibold'"
                    >
                        <input type="radio" name="category" value="tendik" x-model="category" class="sr-only">
                        <span class="text-xl">💼</span>
                        <span class="text-xs">Tenaga Kependidikan</span>
                    </label>
                </div>
                @error('category') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Nomor Induk / Identitas -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    <span x-show="category === 'siswa'">NISN / NIS (Opsional)</span>
                    <span x-show="category === 'guru'" x-cloak>NIP / NUPTK (Opsional)</span>
                    <span x-show="category === 'tendik'" x-cloak>NIP / NIK (Opsional)</span>
                </label>
                <input 
                    type="text" 
                    name="nisn" 
                    value="{{ old('nisn', $voter->nisn) }}" 
                    :placeholder="category === 'siswa' ? 'Contoh: 20260123' : (category === 'guru' ? 'Contoh: 197505122005011002' : 'Contoh: 198804152019032008')" 
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                >
                @error('nisn') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    <span x-show="category === 'siswa'">Nama Lengkap Siswa <span class="text-rose-500">*</span></span>
                    <span x-show="category === 'guru'" x-cloak>Nama Lengkap Guru & Gelar <span class="text-rose-500">*</span></span>
                    <span x-show="category === 'tendik'" x-cloak>Nama Tenaga Kependidikan <span class="text-rose-500">*</span></span>
                </label>
                <input 
                    type="text" 
                    name="name" 
                    value="{{ old('name', $voter->name) }}" 
                    required 
                    :placeholder="category === 'siswa' ? 'Contoh: Muhammad Budi' : (category === 'guru' ? 'Contoh: Drs. Hendro Wibowo, M.Pd.' : 'Contoh: Sri Wahyuni, S.Kom.')" 
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                >
                @error('name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Kelas / Penugasan & Jenis Kelamin -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        <span x-show="category === 'siswa'">Kelas <span class="text-rose-500">*</span></span>
                        <span x-show="category === 'guru'" x-cloak>Mata Pelajaran / Tugas <span class="text-rose-500">*</span></span>
                        <span x-show="category === 'tendik'" x-cloak>Unit Kerja / Jabatan <span class="text-rose-500">*</span></span>
                    </label>
                    <input 
                        type="text" 
                        name="class" 
                        value="{{ old('class', $voter->class) }}" 
                        required 
                        :placeholder="category === 'siswa' ? 'Contoh: X-1, XI-IPA 2' : (category === 'guru' ? 'Contoh: Guru Matematika' : 'Contoh: Staf Tata Usaha')" 
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20"
                    >
                    @error('class') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Jenis Kelamin
                    </label>
                    <select name="gender" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 bg-white">
                        <option value="">Pilih (Opsional)</option>
                        <option value="L" {{ old('gender', $voter->gender) == 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                        <option value="P" {{ old('gender', $voter->gender) == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.voters.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30">
                    Perbarui Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
