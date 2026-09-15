<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElectionSetting;
use App\Models\Voter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ApiIntegrationController extends Controller
{
    /**
     * Display API Integration management view.
     */
    public function index(): View
    {
        $setting = ElectionSetting::current();
        $totalVoters = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();

        return view('admin.api-integration.index', compact('setting', 'totalVoters', 'votedCount'));
    }

    /**
     * Save School API connection settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_api_url' => ['nullable', 'url', 'max:255'],
            'school_api_key' => ['nullable', 'string', 'max:255'],
            'school_api_client_id' => ['nullable', 'string', 'max:100'],
            'school_api_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = ElectionSetting::current();
        $setting->update($validated);

        return back()->with('success', 'Pengaturan koneksi API database sekolah berhasil disimpan.');
    }

    /**
     * Pull student data from the school API and sync into DPT.
     */
    public function pullData(Request $request): RedirectResponse
    {
        set_time_limit(180);

        $setting = ElectionSetting::current();

        if (empty($setting->school_api_url)) {
            return back()->with('error', 'URL API Database Sekolah belum dikonfigurasi. Silakan isi URL terlebih dahulu.');
        }

        try {
            $headers = [
                'Accept' => 'application/json',
                'User-Agent' => 'Pilketos-API-Client/1.0',
            ];

            if (! empty($setting->school_api_key)) {
                $headers['X-API-KEY'] = $setting->school_api_key;
                $headers['Authorization'] = 'Bearer '.$setting->school_api_key;
            }

            if (! empty($setting->school_api_client_id)) {
                $headers['X-CLIENT-ID'] = $setting->school_api_client_id;
            }

            if (! empty($setting->school_api_secret)) {
                $headers['X-CLIENT-SECRET'] = $setting->school_api_secret;
            }

            // Append API key query param as fallback
            $url = $setting->school_api_url;
            if (! empty($setting->school_api_key) && ! str_contains($url, 'api_key=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator.'api_key='.urlencode($setting->school_api_key);
            }

            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->get($url);

            if (! $response->successful()) {
                return back()->with('error', 'Gagal menghubungi API Sekolah (HTTP '.$response->status().'): '.substr($response->body(), 0, 150));
            }

            $body = $response->json();
            $students = $this->extractStudentsList($body);

            if (empty($students)) {
                return back()->with('error', 'API merespons tetapi tidak ditemukan data siswa dalam format JSON yang didukung.');
            }

            $insertedCount = 0;
            $updatedCount = 0;

            DB::transaction(function () use ($students, &$insertedCount, &$updatedCount): void {
                foreach ($students as $row) {
                    $nisn = ! empty($row['nisn']) ? trim((string) $row['nisn']) : null;
                    $name = ! empty($row['name']) ? trim((string) $row['name']) : null;
                    $class = ! empty($row['class']) ? trim((string) $row['class']) : 'Umum';
                    $gender = ! empty($row['gender']) ? strtoupper(trim((string) $row['gender'])) : null;
                    if (! in_array($gender, ['L', 'P'])) {
                        $gender = null;
                    }

                    if (empty($name)) {
                        continue;
                    }

                    if ($nisn) {
                        $existing = Voter::where('nisn', $nisn)->first();
                        if ($existing) {
                            $existing->update([
                                'name' => $name,
                                'class' => $class,
                                'gender' => $gender,
                            ]);
                            $updatedCount++;

                            continue;
                        }
                    }

                    Voter::create([
                        'nisn' => $nisn,
                        'name' => $name,
                        'class' => $class,
                        'gender' => $gender,
                        'passcode' => Voter::generatePasscode(),
                        'has_voted' => false,
                    ]);
                    $insertedCount++;
                }
            });

            $setting->update([
                'last_sync_at' => now(),
                'last_sync_count' => $insertedCount + $updatedCount,
            ]);

            return back()->with('success', "Sinkronisasi berhasil! {$insertedCount} pemilih baru ditambahkan, {$updatedCount} data pemilih diperbarui.");
        } catch (Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat menarik data: '.$e->getMessage());
        }
    }

    /**
     * Regenerate internal Pilketos API Key for inbound sync.
     */
    public function regeneratePilketosKey(): RedirectResponse
    {
        $setting = ElectionSetting::current();
        $setting->update([
            'pilketos_api_key' => bin2hex(random_bytes(16)),
        ]);

        return back()->with('success', 'API Key internal Pilketos berhasil diperbarui.');
    }

    /**
     * Extract student rows from flexible API responses (data, siswa, students, or direct array).
     *
     * @return array<int, array{nisn: ?string, name: string, class: string, gender: ?string}>
     */
    private function extractStudentsList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $rawList = [];

        // Check common wrapper keys
        if (isset($data['data']) && is_array($data['data'])) {
            $rawList = $data['data'];
        } elseif (isset($data['siswa']) && is_array($data['siswa'])) {
            $rawList = $data['siswa'];
        } elseif (isset($data['students']) && is_array($data['students'])) {
            $rawList = $data['students'];
        } elseif (isset($data['rows']) && is_array($data['rows'])) {
            $rawList = $data['rows'];
        } elseif (array_is_list($data)) {
            $rawList = $data;
        }

        $normalized = [];
        foreach ($rawList as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nisn = $item['nisn'] ?? $item['nis'] ?? $item['no_induk'] ?? null;
            $name = $item['nama'] ?? $item['name'] ?? $item['nama_lengkap'] ?? $item['nama_siswa'] ?? null;
            $class = $item['kelas'] ?? $item['class'] ?? $item['rombel'] ?? $item['nama_kelas'] ?? 'Umum';
            $gender = $item['jk'] ?? $item['jenis_kelamin'] ?? $item['gender'] ?? null;

            if ($gender) {
                $gender = strtoupper(substr(trim((string) $gender), 0, 1));
                if (! in_array($gender, ['L', 'P'])) {
                    $gender = null;
                }
            }

            if ($name) {
                $normalized[] = [
                    'nisn' => $nisn ? (string) $nisn : null,
                    'name' => (string) $name,
                    'class' => (string) $class,
                    'gender' => $gender,
                ];
            }
        }

        return $normalized;
    }
}
