@extends('layouts.admin')

@section('title', 'Edit Pasangan Calon')
@section('header_title', 'Edit Pasangan Calon')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Perbarui Paslon No. {{ sprintf('%02d', $candidate->candidate_number) }}</h2>
                <p class="text-xs text-slate-500">Ubah data identitas, visi misi, atau foto paslon</p>
            </div>
            <a href="{{ route('admin.candidates.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('admin.candidates.update', $candidate) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nomor Urut Paslon <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="candidate_number" value="{{ old('candidate_number', $candidate->candidate_number) }}" min="1" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('candidate_number') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Warna Aksen Badge (HEX)
                    </label>
                    <input type="text" name="color_tag" value="{{ old('color_tag', $candidate->color_tag ?? '#4f46e5') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('color_tag') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Calon Ketua OSIS <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="leader_name" value="{{ old('leader_name', $candidate->leader_name) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('leader_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Calon Wakil Ketua OSIS <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="co_leader_name" value="{{ old('co_leader_name', $candidate->co_leader_name) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">
                    @error('co_leader_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Visi Pasangan Calon <span class="text-rose-500">*</span>
                </label>
                <textarea name="vision" rows="3" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">{{ old('vision', $candidate->vision) }}</textarea>
                @error('vision') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Misi Pasangan Calon <span class="text-rose-500">*</span>
                </label>
                <textarea name="mission" rows="4" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">{{ old('mission', $candidate->mission) }}</textarea>
                @error('mission') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Ganti Foto Paslon (Kosongkan jika tidak ingin mengubah)
                </label>
                @if ($candidate->photo_path)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ asset('storage/' . $candidate->photo_path) }}" alt="Foto Lama" class="w-16 h-16 rounded-xl object-cover border border-slate-200">
                        <span class="text-xs text-slate-500">Foto saat ini aktif</span>
                    </div>
                @endif
                <input type="file" name="photo" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                @error('photo') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.candidates.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30">
                    Perbarui Paslon
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
