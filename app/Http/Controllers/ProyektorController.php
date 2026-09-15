<?php

namespace App\Http\Controllers;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\Voter;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProyektorController extends Controller
{
    /**
     * Display live projector screen.
     */
    public function index(): View
    {
        $setting = ElectionSetting::current();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = max(0, $totalVoters - $votedCount);
        $turnoutPercentage = $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 1) : 0;

        $candidates = Candidate::withCount('ballots')
            ->orderBy('candidate_number', 'asc')
            ->get();

        return view('proyektor.index', compact(
            'setting',
            'candidates',
            'totalVoters',
            'votedCount',
            'unvotedCount',
            'turnoutPercentage'
        ));
    }

    /**
     * Return live JSON data for polling updates without page reload.
     */
    public function apiData(): JsonResponse
    {
        $setting = ElectionSetting::current();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = max(0, $totalVoters - $votedCount);
        $turnoutPercentage = $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 1) : 0;
        $totalBallots = Ballot::count();

        $candidates = Candidate::withCount('ballots')
            ->orderBy('candidate_number', 'asc')
            ->get()
            ->map(function ($candidate) use ($setting, $totalBallots) {
                $count = $candidate->ballots_count;
                $pct = $totalBallots > 0 ? round(($count / $totalBallots) * 100, 1) : 0;

                return [
                    'id' => $candidate->id,
                    'number' => $candidate->candidate_number,
                    'leader_name' => $candidate->leader_name,
                    'co_leader_name' => $candidate->co_leader_name,
                    'color_tag' => $candidate->color_tag ?? '#4f46e5',
                    'photo_url' => $candidate->photo_url,
                    'votes' => $setting->show_quick_count ? $count : null,
                    'percentage' => $setting->show_quick_count ? $pct : null,
                ];
            });

        return response()->json([
            'is_active' => (bool) $setting->is_active,
            'is_frozen' => ! (bool) $setting->show_quick_count,
            'total_voters' => $totalVoters,
            'voted_count' => $votedCount,
            'unvoted_count' => $unvotedCount,
            'turnout_percentage' => $turnoutPercentage,
            'total_ballots' => $totalBallots,
            'candidates' => $candidates,
            'updated_at' => now()->format('H:i:s'),
        ]);
    }
}
