<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CandidateController extends Controller
{
    /**
     * Display list of candidates.
     */
    public function index(): View
    {
        $candidates = Candidate::withCount('ballots')
            ->orderBy('candidate_number', 'asc')
            ->get();

        return view('admin.candidates.index', compact('candidates'));
    }

    /**
     * Show form for creating a new candidate.
     */
    public function create(): View
    {
        $nextNumber = (Candidate::max('candidate_number') ?? 0) + 1;

        return view('admin.candidates.create', compact('nextNumber'));
    }

    /**
     * Store new candidate.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'candidate_number' => ['required', 'integer', 'min:1', 'unique:candidates,candidate_number'],
            'leader_name' => ['required', 'string', 'max:255'],
            'co_leader_name' => ['required', 'string', 'max:255'],
            'vision' => ['required', 'string'],
            'mission' => ['required', 'string'],
            'color_tag' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('candidates', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);
        Candidate::create($validated);

        return redirect()->route('admin.candidates.index')->with('success', 'Pasangan Calon berhasil ditambahkan.');
    }

    /**
     * Show edit form for candidate.
     */
    public function edit(Candidate $candidate): View
    {
        return view('admin.candidates.edit', compact('candidate'));
    }

    /**
     * Update candidate details.
     */
    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $validated = $request->validate([
            'candidate_number' => ['required', 'integer', 'min:1', 'unique:candidates,candidate_number,'.$candidate->id],
            'leader_name' => ['required', 'string', 'max:255'],
            'co_leader_name' => ['required', 'string', 'max:255'],
            'vision' => ['required', 'string'],
            'mission' => ['required', 'string'],
            'color_tag' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            if ($candidate->photo_path && Storage::disk('public')->exists($candidate->photo_path)) {
                Storage::disk('public')->delete($candidate->photo_path);
            }
            $path = $request->file('photo')->store('candidates', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);
        $candidate->update($validated);

        return redirect()->route('admin.candidates.index')->with('success', 'Pasangan Calon berhasil diperbarui.');
    }

    /**
     * Delete candidate.
     */
    public function destroy(Candidate $candidate): RedirectResponse
    {
        if ($candidate->photo_path && Storage::disk('public')->exists($candidate->photo_path)) {
            Storage::disk('public')->delete($candidate->photo_path);
        }

        $candidate->delete();

        return redirect()->route('admin.candidates.index')->with('success', 'Pasangan Calon berhasil dihapus.');
    }
}
