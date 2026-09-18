<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElectionSetting;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Show form for editing election settings.
     */
    public function edit(): View
    {
        $setting = ElectionSetting::current();
        $academicYears = ElectionSetting::getAcademicYearsList();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $totalGuruVoters = Voter::where('category', Voter::CATEGORY_GURU)->count();
        $votedGuruCount = Voter::where('category', Voter::CATEGORY_GURU)->where('has_voted', true)->count();

        return view('admin.settings.edit', compact('setting', 'academicYears', 'totalVoters', 'votedCount', 'totalGuruVoters', 'votedGuruCount'));
    }

    /**
     * Update election settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $setting = ElectionSetting::current();

        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'election_title' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:20'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'headmaster_name' => ['nullable', 'string', 'max:255'],
            'headmaster_nip' => ['nullable', 'string', 'max:50'],
            'pembina_name' => ['nullable', 'string', 'max:255'],
            'pembina_nip' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'show_quick_count' => ['boolean'],
            'show_qr_code' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:1024'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_quick_count'] = $request->boolean('show_quick_count');
        $validated['show_qr_code'] = $request->boolean('show_qr_code');

        if ($request->hasFile('logo')) {
            if ($setting->school_logo && Storage::disk('public')->exists($setting->school_logo)) {
                Storage::disk('public')->delete($setting->school_logo);
            }
            $validated['school_logo'] = $request->file('logo')->store('settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($setting->favicon && Storage::disk('public')->exists($setting->favicon)) {
                Storage::disk('public')->delete($setting->favicon);
            }
            $validated['favicon'] = $request->file('favicon')->store('settings', 'public');
        }

        unset($validated['logo']);
        $setting->update($validated);

        return redirect()->route('admin.settings.edit')->with('success', 'Pengaturan pemilihan berhasil diperbarui.');
    }
}
