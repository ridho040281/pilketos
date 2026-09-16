<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElectionSetting;
use App\Models\Voter;
use App\Services\DptDeduplicationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ApiIntegrationController extends Controller
{
    /**
     * Display API Integration management view (redirect to Settings tab).
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.settings.edit', ['tab' => 'api']);
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

        return redirect()->route('admin.settings.edit', ['tab' => 'api'])->with('success', 'Pengaturan koneksi API database sekolah berhasil disimpan.');
    }

    /**
     * Pull student data from the school API and sync into DPT.
     */
    public function pullData(Request $request): RedirectResponse
    {
        set_time_limit(180);

        $setting = ElectionSetting::current();

        if (empty($setting->school_api_url)) {
            return redirect()->route('admin.settings.edit', ['tab' => 'api'])->with('error', 'URL API Database Sekolah belum dikonfigurasi. Silakan isi URL terlebih dahulu.');
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
                return redirect()->route('admin.settings.edit', ['tab' => 'api'])->with('error', 'Gagal menghubungi API Sekolah (HTTP '.$response->status().'): '.substr($response->body(), 0, 150));
            }

            $body = $response->json();
            $students = $this->extractStudentsList($body);

            if (empty($students)) {
                return redirect()->route('admin.settings.edit', ['tab' => 'api'])->with('error', 'API merespons tetapi tidak ditemukan data siswa dalam format JSON yang didukung.');
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

            return redirect()->route('admin.settings.edit', ['tab' => 'api'])->with('success', "Sinkronisasi berhasil! {$insertedCount} pemilih baru ditambahkan, {$updatedCount} data pemilih diperbarui.");
        } catch (Exception $e) {
            return redirect()->route('admin.settings.edit', ['tab' => 'api'])->with('error', 'Terjadi kesalahan saat menarik data: '.$e->getMessage());
        }
    }

    /**
     * Save Guru API connection settings.
     */
    public function updateGuruSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'guru_api_url' => ['nullable', 'url', 'max:255'],
            'guru_api_client_id' => ['nullable', 'string', 'max:100'],
            'guru_api_secret' => ['nullable', 'string', 'max:255'],
            'guru_api_token' => ['nullable', 'string', 'max:1000'],
        ]);

        $setting = ElectionSetting::current();
        $setting->update($validated);

        return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])->with('success', 'Pengaturan koneksi Integrasi API Guru berhasil disimpan.');
    }

    /**
     * Pull teacher data from the Guru API and sync into DPT (kategori: guru).
     */
    public function pullGuruData(Request $request): RedirectResponse
    {
        set_time_limit(180);

        $setting = ElectionSetting::current();

        if (empty($setting->guru_api_url)) {
            return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])->with('error', 'URL Endpoint API Guru belum dikonfigurasi. Silakan isi URL terlebih dahulu.');
        }

        try {
            $clientId = (string) ($setting->guru_api_client_id ?? '');
            $clientSecret = (string) ($setting->guru_api_secret ?? '');
            $apiToken = (string) ($setting->guru_api_token ?? '');
            $baseUrl = $setting->guru_api_url;

            $teachers = [];
            $page = 1;
            $maxPages = 20;

            do {
                $timestamp = time();
                $payload = ''; // Kosong untuk GET request

                // Formula Signature HMAC SHA-256 (sesuai spesifikasi e-Jadwal MTsN 1 Blitar)
                $rawString = $timestamp.$clientId.$payload;
                $signature = ! empty($clientSecret) ? hash_hmac('sha256', $rawString, $clientSecret) : '';

                $headers = [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Pilketos-API-Client/1.0',
                    'Authorization' => 'Bearer '.$apiToken,
                    'X-Client-ID' => $clientId,
                    'X-Timestamp' => (string) $timestamp,
                    'X-Signature' => $signature,
                ];

                // Tambahkan parameter per_page=100 & page untuk menarik seluruh data guru
                $querySeparator = str_contains($baseUrl, '?') ? '&' : '?';
                $url = $baseUrl.$querySeparator.'per_page=100&page='.$page;

                $response = Http::timeout(60)
                    ->withHeaders($headers)
                    ->get($url);

                if (! $response->successful()) {
                    if ($page === 1) {
                        return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])->with('error', 'Gagal menghubungi API Guru (HTTP '.$response->status().'): '.substr($response->body(), 0, 150));
                    }
                    break;
                }

                $body = $response->json();
                $pageTeachers = $this->extractTeachersList($body);

                if (empty($pageTeachers)) {
                    break;
                }

                $teachers = array_merge($teachers, $pageTeachers);

                $lastPage = $body['meta']['last_page'] ?? 1;
                $page++;
            } while ($page <= $lastPage && $page <= $maxPages);

            if (empty($teachers)) {
                return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])->with('error', 'API merespons tetapi tidak ditemukan data guru dalam format JSON yang didukung.');
            }

            $insertedCount = 0;
            $updatedCount = 0;

            DB::transaction(function () use ($teachers, &$insertedCount, &$updatedCount): void {
                foreach ($teachers as $row) {
                    $nip = ! empty($row['nip']) ? trim((string) $row['nip']) : null;
                    $name = ! empty($row['name']) ? trim((string) preg_replace('/\s+/', ' ', (string) $row['name'])) : null;
                    $class = ! empty($row['class']) ? trim((string) $row['class']) : 'Guru';
                    $gender = ! empty($row['gender']) ? strtoupper(trim((string) $row['gender'])) : null;
                    if (! in_array($gender, ['L', 'P'])) {
                        $gender = null;
                    }

                    if (empty($name)) {
                        continue;
                    }

                    $existing = null;
                    if ($nip) {
                        $existing = Voter::where('nisn', $nip)->first();
                    }
                    if (! $existing) {
                        $existing = Voter::where('category', Voter::CATEGORY_GURU)
                            ->whereRaw('TRIM(LOWER(name)) = ?', [strtolower($name)])
                            ->first();
                    }

                    if ($existing) {
                        $existing->update([
                            'category' => Voter::CATEGORY_GURU,
                            'name' => $name,
                            'class' => $class,
                            'gender' => $gender ?? $existing->gender,
                            'nisn' => $nip ?? $existing->nisn,
                        ]);
                        $updatedCount++;

                        continue;
                    }

                    Voter::create([
                        'nisn' => $nip,
                        'name' => $name,
                        'category' => Voter::CATEGORY_GURU,
                        'class' => $class,
                        'gender' => $gender,
                        'passcode' => Voter::generatePasscode(),
                        'has_voted' => false,
                    ]);
                    $insertedCount++;
                }
            });

            $setting->update([
                'last_guru_sync_at' => now(),
                'last_guru_sync_count' => $insertedCount + $updatedCount,
            ]);

            return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])->with('success', "Sinkronisasi data guru berhasil! {$insertedCount} guru baru ditambahkan, {$updatedCount} data guru diperbarui.");
        } catch (Exception $e) {
            return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])->with('error', 'Terjadi kesalahan saat menarik data guru: '.$e->getMessage());
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

        return redirect()->route('admin.settings.edit', ['tab' => 'api'])->with('success', 'API Key internal Pilketos berhasil diperbarui.');
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

    /**
     * Extract teacher rows from flexible API responses (data, guru, teachers, pegawai, results, rows, or direct array).
     *
     * @return array<int, array{nip: ?string, name: string, class: string, gender: ?string}>
     */
    private function extractTeachersList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $rawList = [];

        // Check common wrapper keys
        if (isset($data['data']) && is_array($data['data'])) {
            $rawList = $data['data'];
        } elseif (isset($data['guru']) && is_array($data['guru'])) {
            $rawList = $data['guru'];
        } elseif (isset($data['teachers']) && is_array($data['teachers'])) {
            $rawList = $data['teachers'];
        } elseif (isset($data['pegawai']) && is_array($data['pegawai'])) {
            $rawList = $data['pegawai'];
        } elseif (isset($data['results']) && is_array($data['results'])) {
            $rawList = $data['results'];
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

            if (isset($item['aktif']) && $item['aktif'] === false) {
                continue;
            }

            $nip = $item['nip'] ?? $item['nuptk'] ?? $item['nik'] ?? $item['id'] ?? $item['kode_guru'] ?? $item['nisn'] ?? null;
            $name = $item['nama'] ?? $item['name'] ?? $item['nama_lengkap'] ?? (isset($item['user_account']['name']) ? $item['user_account']['name'] : null) ?? $item['nama_guru'] ?? null;
            $class = $item['mapel'] ?? $item['mata_pelajaran'] ?? $item['jabatan'] ?? $item['tugas'] ?? $item['unit'] ?? $item['kelas'] ?? $item['class'] ?? 'Guru';
            $gender = $item['jenis_kelamin'] ?? $item['jk'] ?? $item['gender'] ?? $item['sex'] ?? null;

            if ($gender) {
                $gender = strtoupper(substr(trim((string) $gender), 0, 1));
                if (! in_array($gender, ['L', 'P'])) {
                    $gender = null;
                }
            }

            if ($name) {
                $normalized[] = [
                    'nip' => $nip ? (string) $nip : null,
                    'name' => (string) $name,
                    'class' => (string) $class,
                    'gender' => $gender,
                ];
            }
        }

        return $normalized;
    }

    /**
     * Clean duplicate teachers from the voters list.
     */
    public function cleanDuplicateTeachers(): RedirectResponse
    {
        $result = DptDeduplicationService::cleanDuplicateTeachers(false);

        if ($result['deleted_count'] === 0) {
            return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])
                ->with('success', 'Pemeriksaan selesai: Tidak ditemukan data guru ganda/duplikat.');
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'api_guru'])
            ->with('success', "Berhasil membersihkan {$result['deleted_count']} data guru duplikat dari {$result['duplicate_groups']} kelompok nama.");
    }
}
