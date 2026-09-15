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

        <form action="{{ route('admin.candidates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nomor Urut Paslon <span class="text-rose-500">*</span>
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Calon Ketua OSIS <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="leader_name" value="{{ old('leader_name') }}" required placeholder="Contoh: Muhammad Fathur" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('leader_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Calon Wakil Ketua OSIS <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="co_leader_name" value="{{ old('co_leader_name') }}" required placeholder="Contoh: Nabila Putri" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
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
