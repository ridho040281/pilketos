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

        // Category-based participation stats (Guru, Tendik, Siswa)
        $categoryStats = Voter::selectRaw('category, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                $item->unvoted = max(0, $item->total - $item->voted);
                $item->percentage = $item->total > 0 ? round(($item->voted / $item->total) * 100, 1) : 0;
                $item->label = Voter::CATEGORIES[$item->category] ?? ucfirst($item->category);

                return $item;
            })
            ->keyBy('category');

        $guruStats = $categoryStats->get(Voter::CATEGORY_GURU, (object) [
            'total' => 0, 'voted' => 0, 'unvoted' => 0, 'percentage' => 0, 'label' => 'Guru',
        ]);
        $tendikStats = $categoryStats->get(Voter::CATEGORY_TENDIK, (object) [
            'total' => 0, 'voted' => 0, 'unvoted' => 0, 'percentage' => 0, 'label' => 'Tenaga Kependidikan',
        ]);
        $siswaStats = $categoryStats->get(Voter::CATEGORY_SISWA, (object) [
            'total' => 0, 'voted' => 0, 'unvoted' => 0, 'percentage' => 0, 'label' => 'Siswa',
        ]);

        // Class breakdown for Siswa (Khusus Siswa)
        $classesStats = Voter::where('category', Voter::CATEGORY_SISWA)
            ->selectRaw("COALESCE(NULLIF(TRIM(class), ''), '[Tanpa Kelas]') as class, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted")
            ->groupBy('class')
            ->orderByRaw("CASE WHEN class = '[Tanpa Kelas]' THEN 1 ELSE 0 END, LENGTH(class), class")
            ->get()
            ->map(function ($item) {
                $item->unvoted = max(0, $item->total - $item->voted);
                $item->percentage = $item->total > 0 ? round(($item->voted / $item->total) * 100, 1) : 0;

                return $item;
            });

        // Mapel breakdown for Guru
        $guruClassStats = Voter::where('category', Voter::CATEGORY_GURU)
            ->selectRaw("COALESCE(NULLIF(TRIM(class), ''), '[Tanpa Mapel]') as class, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted")
            ->groupBy('class')
            ->orderBy('class')
            ->get()
            ->map(function ($item) {
                $item->unvoted = max(0, $item->total - $item->voted);
                $item->percentage = $item->total > 0 ? round(($item->voted / $item->total) * 100, 1) : 0;

                return $item;
            });

        // Unit breakdown for Tendik
        $tendikClassStats = Voter::where('category', Voter::CATEGORY_TENDIK)
            ->selectRaw("COALESCE(NULLIF(TRIM(class), ''), '[Tanpa Unit]') as class, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted")
            ->groupBy('class')
            ->orderBy('class')
            ->get()
            ->map(function ($item) {
                $item->unvoted = max(0, $item->total - $item->voted);
                $item->percentage = $item->total > 0 ? round(($item->voted / $item->total) * 100, 1) : 0;

                return $item;
            });

        // Perolehan Suara Paslon Per Kelas Siswa
        $rawClassVotes = Ballot::where('voter_category', Voter::CATEGORY_SISWA)
            ->whereNotNull('voter_class')
            ->selectRaw("COALESCE(NULLIF(TRIM(voter_class), ''), '[Tanpa Kelas]') as class, candidate_id, count(*) as votes")
            ->groupBy('class', 'candidate_id')
            ->get();

        $classesStats = $classesStats->map(function ($item) use ($candidates, $rawClassVotes) {
            $classVotes = $rawClassVotes->where('class', $item->class);
            $totalVotesInClass = $classVotes->sum('votes');
            $item->total_ballots = $totalVotesInClass;
            $candidateResults = [];
            $highestVotes = 0;
            $leadingCandidateId = null;
            $isTie = false;

            foreach ($candidates as $candidate) {
                $v = (int) ($classVotes->where('candidate_id', $candidate->id)->first()->votes ?? 0);
                $pct = $totalVotesInClass > 0 ? round(($v / $totalVotesInClass) * 100, 1) : 0;
                $candidateResults[$candidate->id] = [
                    'votes' => $v,
                    'percentage' => $pct,
                ];

                if ($v > $highestVotes && $v > 0) {
                    $highestVotes = $v;
                    $leadingCandidateId = $candidate->id;
                    $isTie = false;
                } elseif ($v === $highestVotes && $v > 0) {
                    $isTie = true;
                }
            }

            $item->candidate_results = $candidateResults;
            $item->leading_candidate_id = $isTie ? null : $leadingCandidateId;
            $item->is_tie = $isTie;

            return $item;
        });

        // Perolehan Suara Paslon Per Kategori (Guru, Tendik, Siswa)
        $rawCategoryVotes = Ballot::whereNotNull('voter_category')
            ->selectRaw('voter_category, candidate_id, count(*) as votes')
            ->groupBy('voter_category', 'candidate_id')
            ->get();

        $categoryCandidateBreakdown = [];
        foreach ([Voter::CATEGORY_GURU, Voter::CATEGORY_TENDIK, Voter::CATEGORY_SISWA] as $catKey) {
            $catVotes = $rawCategoryVotes->where('voter_category', $catKey);
            $totalCatVotes = $catVotes->sum('votes');
            $catResults = [];

            foreach ($candidates as $candidate) {
                $v = $catVotes->where('candidate_id', $candidate->id)->first()->votes ?? 0;
                $pct = $totalCatVotes > 0 ? round(($v / $totalCatVotes) * 100, 1) : 0;
                $catResults[$candidate->id] = [
                    'votes' => $v,
                    'percentage' => $pct,
                ];
            }

            $categoryCandidateBreakdown[$catKey] = [
                'total_votes' => $totalCatVotes,
                'candidates' => $catResults,
            ];
        }

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
        $activeTab = $request->input('tab', 'hitung-cepat');

        return view('admin.laporan.index', compact(
            'setting',
            'totalVoters',
            'votedCount',
            'unvotedCount',
            'turnoutPercentage',
            'totalBallots',
            'candidates',
            'winner',
            'categoryStats',
            'guruStats',
            'tendikStats',
            'siswaStats',
            'classesStats',
            'guruClassStats',
            'tendikClassStats',
            'categoryCandidateBreakdown',
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
