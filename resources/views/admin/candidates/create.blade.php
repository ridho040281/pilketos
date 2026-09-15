@extends('layouts.admin')

@section('title', 'Tambah Pasangan Calon')
@section('header_title', 'Tambah Pasangan Calon Baru')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Formulir Pendaftaran Paslon</h2>
                <p class="text-xs text-slate-500">Lengkapi data ketua, wakil ketua, visi misi, dan foto resmi</p>
            </div>
            <a href="{{ route('admin.candidates.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('admin.candidates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5" x-data="{
            hasVice: {{ old('co_leader_name') ? 'true' : (old('has_vice', 'false') === 'true' ? 'true' : 'false') }},
            coLeader: '{{ old('co_leader_name') }}'
        }">
            @csrf

            <!-- Opsi Format Calon -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Format Pemilihan Calon
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label 
                        @click="hasVice = false; coLeader = ''" 
                        :class="!hasVice ? 'bg-white border-indigo-600 ring-2 ring-indigo-500/20 shadow-sm text-indigo-700 font-bold' : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white font-medium'"
                        class="p-3.5 rounded-xl border flex items-center gap-3 cursor-pointer transition-all"
                    >
                        <input type="radio" name="has_vice" value="false" :checked="!hasVice" class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <div class="text-xs font-bold flex items-center gap-1.5">
                                <span>👤 Hanya Calon Ketua</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">Tunggal</span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Pemilihan hanya ketua saja (tanpa calon wakil ketua)</div>
                        </div>
                    </label>

                    <label 
                        @click="hasVice = true" 
                        :class="hasVice ? 'bg-white border-indigo-600 ring-2 ring-indigo-500/20 shadow-sm text-indigo-700 font-bold' : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white font-medium'"
                        class="p-3.5 rounded-xl border flex items-center gap-3 cursor-pointer transition-all"
                    >
                        <input type="radio" name="has_vice" value="true" :checked="hasVice" class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <div class="text-xs font-bold flex items-center gap-1.5">
                                <span>👥 Pasangan Calon (Ketua & Wakil)</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Paslon</span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Pemilihan berpasangan (ada calon ketua & calon wakil)</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nomor Urut Calon <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="candidate_number" value="{{ old('candidate_number', $nextNumber) }}" min="1" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('candidate_number') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Warna Aksen Badge (HEX)
                    </label>
                    <input type="text" name="color_tag" value="{{ old('color_tag', '#4f46e5') }}" placeholder="#4f46e5" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('color_tag') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5" :class="hasVice ? 'sm:grid-cols-2' : ''">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Calon Ketua OSIS <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="leader_name" value="{{ old('leader_name') }}" required placeholder="Contoh: Muhammad Fathur" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('leader_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div x-show="hasVice" x-cloak>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Calon Wakil Ketua OSIS <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="co_leader_name" x-model="coLeader" :disabled="!hasVice" placeholder="Contoh: Nabila Putri" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('co_leader_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Visi Pasangan Calon <span class="text-rose-500">*</span>
                </label>
                <textarea name="vision" rows="3" required placeholder="Tuliskan visi paslon..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">{{ old('vision') }}</textarea>
                @error('vision') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Misi Pasangan Calon <span class="text-rose-500">*</span>
                </label>
                <textarea name="mission" rows="4" required placeholder="Tuliskan poin-poin misi paslon..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">{{ old('mission') }}</textarea>
                @error('mission') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Foto Resmi Paslon (JPG/PNG, Maks. 2MB)
                </label>
                <input type="file" name="photo" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                @error('photo') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.candidates.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30">
                    Simpan Paslon
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
