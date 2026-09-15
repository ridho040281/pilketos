<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\Voter;
use Illuminate\View\View;

class BeritaAcaraController extends Controller
{
    /**
     * Display printable official Berita Acara Pleno.
     */
    public function index(): View
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

        // Determine winner
        $winner = $candidates->sortByDesc('ballots_count')->first();

        // Class breakdown
        $classesStats = Voter::selectRaw('class, count(*) as total, sum(case when has_voted = 1 then 1 else 0 end) as voted')
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
}
