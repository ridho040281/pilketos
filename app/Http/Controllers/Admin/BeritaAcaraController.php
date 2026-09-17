<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\Voter;
use App\Support\SimpleXLSXGen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BeritaAcaraController extends Controller
{
    /**
     * Display Laporan page containing Tab 1 (Daftar Hadir) and Tab 2 (Berita Acara Pleno).
     */
    public function index(Request $request): View
    {
        $setting = ElectionSetting::current();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = max(0, $totalVoters - $votedCount);
        $turnoutPercentage = $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 2) : 0;
        $totalBallots = Ballot::count();

        // Candidates data for Berita Acara
        $candidates = Candidate::withCount('ballots')
            ->orderBy('candidate_number', 'asc')
            ->get()
            ->map(function ($candidate) use ($totalBallots) {
                $candidate->percentage = $totalBallots > 0
                    ? round(($candidate->ballots_count / $totalBallots) * 100, 2)
                    : 0;

                return $candidate;
            });

        // Determine winner
        $winner = $candidates->sortByDesc('ballots_count')->first();

        // Class breakdown for Berita Acara (Khusus Siswa)
        $classesStats = Voter::where('category', Voter::CATEGORY_SISWA)
            ->selectRaw("COALESCE(NULLIF(TRIM(class), ''), '[Tanpa Kelas]') as class, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted")
            ->groupBy('class')
            ->orderByRaw("CASE WHEN class = '[Tanpa Kelas]' THEN 1 ELSE 0 END, LENGTH(class), class")
            ->get();

        // Tab 1: Daftar Hadir Query & Filters
        $attendeesQuery = Voter::query();
        $status = $request->input('status', 'all');

        if ($status === 'voted') {
            $attendeesQuery->where('has_voted', true);
        } elseif ($status === 'unvoted') {
            $attendeesQuery->where('has_voted', false);
        }

        if ($search = $request->input('search')) {
            $attendeesQuery->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $attendeesQuery->where('category', $category);
        }

        if ($class = $request->input('class')) {
            $attendeesQuery->where('class', $class);
        }

        if ($status === 'voted') {
            $attendeesQuery->orderByDesc('voted_at')->orderBy('name', 'asc');
        } else {
            $attendeesQuery->orderBy('category', 'asc')->orderBy('class', 'asc')->orderBy('name', 'asc');
        }

        $attendees = $attendeesQuery
            ->paginate(50)
            ->withQueryString();

        $classes = Voter::whereNotNull('class')
            ->where('class', '!=', '')
            ->distinct()
            ->orderBy('class')
            ->pluck('class');

        $categoryClasses = [
            Voter::CATEGORY_SISWA => [],
            Voter::CATEGORY_GURU => [],
            Voter::CATEGORY_TENDIK => [],
        ];
        foreach (Voter::whereNotNull('class')->where('class', '!=', '')->select('category', 'class')->distinct()->orderBy('class')->get() as $item) {
            if (isset($categoryClasses[$item->category])) {
                $categoryClasses[$item->category][] = $item->class;
            } else {
                $categoryClasses[$item->category] = [$item->class];
            }
        }

        $classesCounts = Voter::whereNotNull('class')
            ->where('class', '!=', '')
            ->selectRaw('class, count(*) as count')
            ->groupBy('class')
            ->pluck('count', 'class');

        $categories = Voter::CATEGORIES;
        $activeTab = $request->input('tab', 'daftar-hadir');

        return view('admin.laporan.index', compact(
            'setting',
            'totalVoters',
            'votedCount',
            'unvotedCount',
            'turnoutPercentage',
            'totalBallots',
            'candidates',
            'winner',
            'classesStats',
            'attendees',
            'classes',
            'classesCounts',
            'categoryClasses',
            'categories',
            'activeTab'
        ));
    }

    /**
     * Printable A4 Daftar Hadir document.
     */
    public function printDaftarHadir(Request $request): View
    {
        $setting = ElectionSetting::current();
        $attendeesQuery = Voter::query();
        $status = $request->input('status', 'all');

        if ($status === 'voted') {
            $attendeesQuery->where('has_voted', true);
        } elseif ($status === 'unvoted') {
            $attendeesQuery->where('has_voted', false);
        }

        if ($search = $request->input('search')) {
            $attendeesQuery->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $attendeesQuery->where('category', $category);
        }

        if ($class = $request->input('class')) {
            $attendeesQuery->where('class', $class);
        }

        $attendees = $attendeesQuery
            ->orderBy('category', 'asc')
            ->orderBy('class', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = max(0, $totalVoters - $votedCount);
        $turnoutPercentage = $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 2) : 0;

        return view('admin.laporan.print-daftar-hadir', compact(
            'setting',
            'attendees',
            'totalVoters',
            'votedCount',
            'unvotedCount',
            'turnoutPercentage'
        ));
    }

    /**
     * Export Daftar Hadir to Excel (.xlsx).
     */
    public function exportDaftarHadir(Request $request): Response
    {
        $attendeesQuery = Voter::query();
        $status = $request->input('status', 'all');

        if ($status === 'voted') {
            $attendeesQuery->where('has_voted', true);
        } elseif ($status === 'unvoted') {
            $attendeesQuery->where('has_voted', false);
        }

        if ($search = $request->input('search')) {
            $attendeesQuery->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $attendeesQuery->where('category', $category);
        }

        if ($class = $request->input('class')) {
            $attendeesQuery->where('class', $class);
        }

        $attendees = $attendeesQuery
            ->orderBy('category', 'asc')
            ->orderBy('class', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $rows = [
            ['No', 'NISN / NIP', 'Nama Lengkap', 'Kategori', 'Kelas / Mapel', 'Jenis Kelamin (L/P)', 'Status Hak Pilih', 'Waktu Coblos / Hadir'],
        ];

        $i = 1;
        foreach ($attendees as $attendee) {
            $rows[] = [
                $i++,
                $attendee->nisn ?? '-',
                $attendee->name,
                $attendee->category_label,
                $attendee->class ?? '-',
                $attendee->gender ?? '-',
                $attendee->has_voted ? 'Sudah Memilih' : 'Belum Memilih',
                $attendee->voted_at ? $attendee->voted_at->format('d/m/Y H:i:s') : '-',
            ];
        }

        $xlsx = SimpleXLSXGen::fromArray($rows, 'Daftar Hadir');
        $filename = 'daftar_hadir_pemilih_'.date('Ymd_His').'.xlsx';

        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Dedicated printable A4 Berita Acara Pleno document.
     */
    public function printBeritaAcara(): View
    {
        $setting = ElectionSetting::current();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = max(0, $totalVoters - $votedCount);
        $turnoutPercentage = $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 2) : 0;
        $totalBallots = Ballot::count();

        $candidates = Candidate::withCount('ballots')
            ->orderBy('candidate_number', 'asc')
            ->get()
            ->map(function ($candidate) use ($totalBallots) {
                $candidate->percentage = $totalBallots > 0
                    ? round(($candidate->ballots_count / $totalBallots) * 100, 2)
                    : 0;

                return $candidate;
            });

        $winner = $candidates->sortByDesc('ballots_count')->first();

        // Class breakdown for Berita Acara (Khusus Siswa)
        $classesStats = Voter::where('category', Voter::CATEGORY_SISWA)
            ->whereNotNull('class')
            ->where('class', '!=', '')
            ->selectRaw('class, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted')
            ->groupBy('class')
            ->orderBy('class')
            ->get();

        return view('admin.berita-acara.index', compact(
            'setting',
            'totalVoters',
            'votedCount',
            'unvotedCount',
            'turnoutPercentage',
            'totalBallots',
            'candidates',
            'winner',
            'classesStats'
        ));
    }

    /**
     * Legacy redirect from /admin/berita-acara to /admin/laporan?tab=berita-acara.
     */
    public function legacyRedirect(): RedirectResponse
    {
        return redirect()->route('admin.laporan.index', ['tab' => 'berita-acara']);
    }
}
