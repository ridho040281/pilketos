<?php

namespace App\Http\Controllers;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\Voter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BilikController extends Controller
{
    /**
     * Display Bilik Suara login / token entry.
     */
    public function login(Request $request): View
    {
        $setting = ElectionSetting::current();
        $initialToken = strtoupper(trim((string) $request->query('token', '')));

        return view('bilik.login', compact('setting', 'initialToken'));
    }

    /**
     * Authenticate voter using passcode token.
     */
    public function masuk(Request $request): RedirectResponse
    {
        $setting = ElectionSetting::current();

        if (! $setting->is_active) {
            return back()->with('error', 'Pemilihan saat ini sedang ditutup atau belum dimulai oleh Panitia.');
        }

        $validated = $request->validate([
            'passcode' => ['required', 'string', 'max:20'],
        ]);

        $token = strtoupper(trim($validated['passcode']));
        $voter = Voter::where('passcode', $token)->first();

        if (! $voter) {
            return back()->withInput()->with('error', 'Token yang Anda masukkan tidak terdaftar dalam DPT.');
        }

        if ($voter->has_voted) {
            return back()->withInput()->with('error', 'Token ini sudah digunakan untuk memilih pada '.($voter->voted_at ? $voter->voted_at->format('H:i').' WIB' : 'sesi sebelumnya').'. Setiap pemilih hanya memiliki 1 hak suara.');
        }

        // Store voter session
        $request->session()->put([
            'voter_id' => $voter->id,
            'voter_name' => $voter->name,
            'voter_class' => $voter->class,
            'voter_category' => $voter->category ?? Voter::CATEGORY_SISWA,
            'voter_category_label' => $voter->category_label,
        ]);

        return redirect()->route('bilik.suara');
    }

    /**
     * Display the digital ballot room.
     */
    public function suara(Request $request): View|RedirectResponse
    {
        $setting = ElectionSetting::current();

        if (! $setting->is_active) {
            $request->session()->forget(['voter_id', 'voter_name', 'voter_class', 'voter_category', 'voter_category_label']);

            return redirect()->route('bilik.login')->with('error', 'Pemilihan telah ditutup.');
        }

        $candidates = Candidate::orderBy('candidate_number', 'asc')->get();
        $voterName = $request->session()->get('voter_name');
        $voterClass = $request->session()->get('voter_class');
        $voterCategory = $request->session()->get('voter_category', 'siswa');
        $voterCategoryLabel = $request->session()->get('voter_category_label', 'Siswa');

        return view('bilik.suara', compact('setting', 'candidates', 'voterName', 'voterClass', 'voterCategory', 'voterCategoryLabel'));
    }

    /**
     * Cast vote anonymously with DB atomic transaction to prevent race conditions.
     */
    public function coblos(Request $request): RedirectResponse
    {
        $voterId = $request->session()->get('voter_id');

        if (! $voterId) {
            return redirect()->route('bilik.login')->with('error', 'Sesi bilik suara Anda telah berakhir.');
        }

        $validated = $request->validate([
            'candidate_id' => ['required', 'exists:candidates,id'],
        ]);

        try {
            DB::transaction(function () use ($voterId, $validated): void {
                // Lock row to prevent concurrent double-vote
                $voter = Voter::where('id', $voterId)->lockForUpdate()->first();

                if (! $voter || $voter->has_voted) {
                    throw new Exception('Hak suara untuk token ini sudah digunakan.');
                }

                // 1. Mark voter as voted (token invalidated)
                $voter->update([
                    'has_voted' => true,
                    'voted_at' => now(),
                ]);

                // 2. Insert anonymous ballot (NO voter id stored - pure secret ballot)
                Ballot::create([
                    'candidate_id' => $validated['candidate_id'],
                    'created_at' => now(),
                ]);
            });

            // Destroy voter session immediately
            $request->session()->forget(['voter_id', 'voter_name', 'voter_class', 'voter_category', 'voter_category_label']);
            $request->session()->put('vote_completed', true);

            return redirect()->route('bilik.sukses');
        } catch (Exception $e) {
            $request->session()->forget(['voter_id', 'voter_name', 'voter_class', 'voter_category', 'voter_category_label']);

            return redirect()->route('bilik.login')->with('error', $e->getMessage());
        }
    }

    /**
     * Display success page and trigger auto-logout.
     */
    public function sukses(Request $request): View|RedirectResponse
    {
        if (! $request->session()->pull('vote_completed', false)) {
            return redirect()->route('bilik.login');
        }

        $setting = ElectionSetting::current();

        return view('bilik.sukses', compact('setting'));
    }

    /**
     * Cancel session and exit bilik suara.
     */
    public function keluar(Request $request): RedirectResponse
    {
        $request->session()->forget(['voter_id', 'voter_name', 'voter_class', 'voter_category', 'voter_category_label']);

        return redirect()->route('bilik.login');
    }
}
