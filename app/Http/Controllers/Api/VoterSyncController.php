<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ElectionSetting;
use App\Models\Voter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoterSyncController extends Controller
{
    /**
     * Inbound API Endpoint for external school systems (CodeIgniter, SIMAK, CBT)
     * to push/sync student voters directly into Pilketos.
     *
     * URL: POST /api/v1/voters/sync
     * Header: X-API-KEY: {pilketos_api_key} or Bearer {pilketos_api_key}
     */
    public function syncFromExternal(Request $request): JsonResponse
    {
        $setting = ElectionSetting::current();
        $expectedKey = $setting->pilketos_api_key;

        // Verify API Key
        $providedKey = $request->header('X-API-KEY')
            ?? $request->bearerToken()
            ?? $request->input('api_key');

        if (empty($expectedKey) || empty($providedKey) || ! hash_equals($expectedKey, (string) $providedKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: API Key tidak valid atau tidak disertakan pada header X-API-KEY.',
            ], 401);
        }

        // Parse student list
        $payload = $request->json()->all();
        $students = $this->extractStudentsList($payload);

        if (empty($students)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format payload tidak valid atau daftar siswa kosong. Gunakan format array list atau key "students"/"siswa"/"data".',
            ], 422);
        }

        try {
            $insertedCount = 0;
            $updatedCount = 0;

            DB::transaction(function () use ($students, &$insertedCount, &$updatedCount): void {
                foreach ($students as $row) {
                    $category = $row['category'] ?? Voter::CATEGORY_SISWA;
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
                                'category' => $category,
                                'name' => $name,
                                'class' => $class,
                                'gender' => $gender,
                            ]);
                            $updatedCount++;

                            continue;
                        }
                    }

                    Voter::create([
                        'category' => $category,
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

            return response()->json([
                'status' => 'success',
                'message' => 'Sinkronisasi data pemilih berhasil diproses.',
                'summary' => [
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'total_dpt' => Voter::count(),
                    'synced_at' => now()->toIso8601String(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses sinkronisasi database: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normalize student data from flexible incoming payloads.
     *
     * @return array<int, array{nisn: ?string, name: string, class: string, gender: ?string}>
     */
    private function extractStudentsList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $rawList = [];
        if (isset($data['students']) && is_array($data['students'])) {
            $rawList = $data['students'];
        } elseif (isset($data['siswa']) && is_array($data['siswa'])) {
            $rawList = $data['siswa'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $rawList = $data['data'];
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

            $rawCategory = strtolower(trim((string) ($item['category'] ?? $item['kategori'] ?? $item['role'] ?? $item['tipe'] ?? 'siswa')));
            if (in_array($rawCategory, ['guru', 'pendidik', 'teacher'])) {
                $category = Voter::CATEGORY_GURU;
            } elseif (in_array($rawCategory, ['tendik', 'tenaga kependidikan', 'staff', 'tu', 'karyawan'])) {
                $category = Voter::CATEGORY_TENDIK;
            } else {
                $category = Voter::CATEGORY_SISWA;
            }

            $nisn = $item['nisn'] ?? $item['nip'] ?? $item['nuptk'] ?? $item['nis'] ?? $item['no_induk'] ?? null;
            $name = $item['nama'] ?? $item['name'] ?? $item['nama_lengkap'] ?? $item['nama_siswa'] ?? null;
            $class = $item['kelas'] ?? $item['class'] ?? $item['rombel'] ?? $item['unit'] ?? $item['mapel'] ?? $item['nama_kelas'] ?? ($category === Voter::CATEGORY_GURU ? 'Guru' : ($category === Voter::CATEGORY_TENDIK ? 'Tenaga Kependidikan' : 'Umum'));
            $gender = $item['jk'] ?? $item['jenis_kelamin'] ?? $item['gender'] ?? null;

            if ($gender) {
                $gender = strtoupper(substr(trim((string) $gender), 0, 1));
                if (! in_array($gender, ['L', 'P'])) {
                    $gender = null;
                }
            }

            if ($name) {
                $normalized[] = [
                    'category' => $category,
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
