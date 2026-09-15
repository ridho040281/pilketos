@extends('layouts.admin')

@section('title', 'Manajemen Pasangan Calon')
@section('header_title', 'Data Pasangan Calon (Kandidat OSIS)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Daftar Kandidat Calon OSIS</h2>
            <p class="text-xs text-slate-500">Kandidat (Paslon atau Tunggal) yang akan tampil pada bilik suara digital dan surat suara</p>
        </div>
        <a href="{{ route('admin.candidates.create') }}" class="inline-flex items-center px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Calon Baru
        </a>
    </div>

    <!-- Candidates Cards List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($candidates as $c)
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <!-- Card Header -->
                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Nomor Urut</span>
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-white text-base shadow-sm" style="background-color: {{ $c->color_tag ?? '#4f46e5' }}">
                            {{ sprintf('%02d', $c->candidate_number) }}
                        </div>
                    </div>

                    <!-- Photo -->
                    <div class="h-48 bg-slate-100 relative overflow-hidden flex items-center justify-center">
                        @if ($c->photo_path)
                            <img src="{{ asset('storage/' . $c->photo_path) }}" alt="{{ $c->leader_name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-20 h-20 rounded-full bg-slate-200 flex items-center justify-center text-slate-400 font-bold text-2xl">
                                {{ sprintf('%02d', $c->candidate_number) }}
                            </div>
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="p-5 space-y-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-600">Calon Ketua</span>
                            <h4 class="text-base font-bold text-slate-900">{{ $c->leader_name }}</h4>
                        </div>
                        @if(!empty($c->co_leader_name))
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Calon Wakil Ketua</span>
                                <h5 class="text-sm font-semibold text-slate-700">{{ $c->co_leader_name }}</h5>
                            </div>
                        @else
                            <div class="pt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10px] font-medium border border-slate-200">
                                    👤 Calon Tunggal (Hanya Ketua)
                                </span>
                            </div>
                        @endif
                        <div class="pt-2 border-t border-slate-100 text-xs text-slate-500 line-clamp-2">
                            <strong>Visi:</strong> {{ $c->vision }}
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">
                        {{ $c->ballots_count }} suara
                    </span>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('admin.candidates.edit', $c) }}" class="p-2 text-slate-600 hover:text-indigo-600 hover:bg-slate-200 rounded-xl transition-colors" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </a>
                        <form action="{{ route('admin.candidates.destroy', $c) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pasangan calon ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-white rounded-3xl border border-slate-200 text-slate-400">
                Belum ada data pasangan calon. Klik tombol "Tambah Paslon Baru" di atas.
            </div>
        @endforelse
    </div>
</div>
@endsection
