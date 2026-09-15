<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        $setting = ElectionSetting::current();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = max(0, $totalVoters - $votedCount);
        $turnoutPercentage = $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 1) : 0;
        $totalBallots = Ballot::count();

        $candidates = Candidate::withCount('ballots')
            ->orderBy('candidate_number', 'asc')
            ->get();

        // Class-based participation stats
        $classesStats = Voter::selectRaw('class, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted')
            ->groupBy('class')
            ->orderBy('class')
            ->get()
            ->map(function ($item) {
                $item->percentage = $item->total > 0 ? round(($item->voted / $item->total) * 100, 1) : 0;

                return $item;
            });

        // Category-based participation stats (Siswa, Guru, Tenaga Kependidikan)
        $categoryStats = Voter::selectRaw('category, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                $item->percentage = $item->total > 0 ? round(($item->voted / $item->total) * 100, 1) : 0;
                $item->label = Voter::CATEGORIES[$item->category] ?? ucfirst($item->category);

                return $item;
            });

        return view('admin.dashboard', compact(
            'setting',
            'totalVoters',
            'votedCount',
            'unvotedCount',
            'turnoutPercentage',
            'totalBallots',
            'candidates',
            'classesStats',
            'categoryStats'
        ));
    }

    /**
     * Quick toggle TPS active status.
     */
    public function toggleTps(Request $request): RedirectResponse
    {
        $setting = ElectionSetting::current();
        $setting->update([
            'is_active' => ! $setting->is_active,
        ]);

        $statusMsg = $setting->is_active ? 'TPS berhasil DIBUKA.' : 'TPS berhasil DITUTUP.';

        return back()->with('success', $statusMsg);
    }

    /**
     * Quick toggle live quick count visibility on projector screen.
     */
    public function toggleQuickCount(Request $request): RedirectResponse
    {
        $setting = ElectionSetting::current();
        $setting->update([
            'show_quick_count' => ! $setting->show_quick_count,
        ]);

        $statusMsg = $setting->show_quick_count
            ? 'Hasil Quick Count sekarang DITAMPILKAN di proyektor.'
            : 'Hasil Quick Count sekarang DISEMBUNYIKAN (Freeze Mode) di proyektor.';

        return back()->with('success', $statusMsg);
    }
}
