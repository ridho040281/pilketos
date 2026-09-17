<?php

namespace App\Services;

use App\Models\Voter;
use Illuminate\Support\Facades\DB;

class DptDeduplicationService
{
    /**
     * Clean duplicate teachers (category = guru).
     *
     * @return array{
     *     duplicate_groups: int,
     *     deleted_count: int,
     *     details: array<int, array<string, mixed>>
     * }
     */
    public static function cleanDuplicateTeachers(bool $dryRun = false): array
    {
        return DB::transaction(function () use ($dryRun) {
            $teachers = Voter::where('category', Voter::CATEGORY_GURU)
                ->orderBy('id', 'asc')
                ->get();

            // Group by normalized name (lowercase, collapse multiple whitespaces)
            $groups = [];
            foreach ($teachers as $teacher) {
                $normalizedName = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $teacher->name)));
                $groups[$normalizedName][] = $teacher;
            }

            $deletedCount = 0;
            $duplicateGroupsCount = 0;
            $details = [];

            foreach ($groups as $normName => $records) {
                if (count($records) <= 1) {
                    continue;
                }

                $duplicateGroupsCount++;

                // Sort records to pick the best keeper:
                // 1. has_voted = true first
                // 2. has non-empty nisn (NIP) first
                // 3. lowest id first
                usort($records, function (Voter $a, Voter $b): int {
                    if ($a->has_voted !== $b->has_voted) {
                        return $b->has_voted ? 1 : -1;
                    }

                    $aHasNip = ! empty($a->nisn);
                    $bHasNip = ! empty($b->nisn);
                    if ($aHasNip !== $bHasNip) {
                        return $bHasNip ? 1 : -1;
                    }

                    return $a->id <=> $b->id;
                });

                /** @var Voter $keeper */
                $keeper = $records[0];
                $duplicatesToDelete = array_slice($records, 1);

                // If keeper has no NIP but a duplicate does, copy NIP to keeper
                if (empty($keeper->nisn)) {
                    foreach ($duplicatesToDelete as $dup) {
                        if (! empty($dup->nisn)) {
                            if (! $dryRun) {
                                $keeper->update(['nisn' => $dup->nisn]);
                            }
                            break;
                        }
                    }
                }

                foreach ($duplicatesToDelete as $dup) {
                    $details[] = [
                        'id' => $dup->id,
                        'name' => $dup->name,
                        'nisn' => $dup->nisn ?? '-',
                        'has_voted' => $dup->has_voted ? 'Sudah' : 'Belum',
                        'kept_id' => $keeper->id,
                        'kept_name' => $keeper->name,
                    ];

                    if (! $dryRun) {
                        $dup->delete();
                    }
                    $deletedCount++;
                }
            }

            return [
                'duplicate_groups' => $duplicateGroupsCount,
                'deleted_count' => $deletedCount,
                'details' => $details,
            ];
        });
    }

    /**
     * Check integrity and potential anomalies in students data (category = siswa).
     *
     * @return array{
     *     total_siswa: int,
     *     classes: array<string, int>,
     *     grade_subtotals: array<string, int>,
     *     duplicate_nisn: array<int, array<string, mixed>>,
     *     duplicate_names: array<int, array<string, mixed>>,
     *     empty_nisn: array<int, array<string, mixed>>,
     *     suspicious_names: array<int, array<string, mixed>>
     * }
     */
    public static function checkStudentsIntegrity(?string $classFilter = null): array
    {
        $query = Voter::where('category', Voter::CATEGORY_SISWA)->orderBy('id', 'asc');
        if (! empty($classFilter)) {
            $query->where('class', $classFilter);
        }

        $students = $query->get();
        $totalSiswa = $students->count();

        // 1. Classes count
        $classes = [];
        $gradeSubtotals = [];
        foreach ($students as $s) {
            $cls = ! empty($s->class) ? trim((string) $s->class) : '[Tanpa Kelas]';
            $classes[$cls] = ($classes[$cls] ?? 0) + 1;

            // Extract grade level (e.g. 7 from 71, 710, VII-A; 8 from 81; 9 from 91)
            $grade = 'Lainnya';
            if (preg_match('/^([789]|1[012])/', $cls, $m)) {
                $grade = 'Kelas '.$m[1];
            } elseif (preg_match('/^(VII|VIII|IX|X|XI|XII)/i', $cls, $m)) {
                $grade = 'Kelas '.strtoupper($m[1]);
            }
            $gradeSubtotals[$grade] = ($gradeSubtotals[$grade] ?? 0) + 1;
        }

        // Sort classes naturally
        uksort($classes, function ($a, $b) {
            if ($a === '[Tanpa Kelas]') {
                return 1;
            }
            if ($b === '[Tanpa Kelas]') {
                return -1;
            }
            $lenDiff = strlen($a) - strlen($b);

            return $lenDiff !== 0 ? $lenDiff : strcmp($a, $b);
        });

        // 2. Duplicate NISN
        $nisnGroups = [];
        $emptyNisn = [];
        foreach ($students as $s) {
            $nisn = trim((string) ($s->nisn ?? ''));
            if ($nisn === '') {
                $emptyNisn[] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'class' => $s->class ?? '-',
                    'has_voted' => $s->has_voted ? 'Sudah' : 'Belum',
                ];
            } else {
                $nisnGroups[$nisn][] = $s;
            }
        }

        $duplicateNisn = [];
        foreach ($nisnGroups as $nisn => $records) {
            if (count($records) > 1) {
                foreach ($records as $r) {
                    $duplicateNisn[] = [
                        'id' => $r->id,
                        'nisn' => $nisn,
                        'name' => $r->name,
                        'class' => $r->class ?? '-',
                        'has_voted' => $r->has_voted ? 'Sudah' : 'Belum',
                    ];
                }
            }
        }

        // 3. Duplicate Names (Exact & Normalized)
        $nameGroups = [];
        foreach ($students as $s) {
            $norm = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $s->name)));
            $nameGroups[$norm][] = $s;
        }

        $duplicateNames = [];
        foreach ($nameGroups as $records) {
            if (count($records) > 1) {
                foreach ($records as $r) {
                    $duplicateNames[] = [
                        'id' => $r->id,
                        'name' => $r->name,
                        'nisn' => $r->nisn ?? '-',
                        'class' => $r->class ?? '-',
                        'has_voted' => $r->has_voted ? 'Sudah' : 'Belum',
                    ];
                }
            }
        }

        // 4. Suspicious dummy/sample names
        $suspiciousNames = [];
        $suspiciousPatterns = ['test', 'dummy', 'coba', 'admin', 'contoh', 'sample', 'percobaan'];
        foreach ($students as $s) {
            $lower = strtolower((string) $s->name);
            foreach ($suspiciousPatterns as $pat) {
                if (str_contains($lower, $pat)) {
                    $suspiciousNames[] = [
                        'id' => $s->id,
                        'name' => $s->name,
                        'class' => $s->class ?? '-',
                        'nisn' => $s->nisn ?? '-',
                        'has_voted' => $s->has_voted ? 'Sudah' : 'Belum',
                    ];
                    break;
                }
            }
        }

        return [
            'total_siswa' => $totalSiswa,
            'classes' => $classes,
            'grade_subtotals' => $gradeSubtotals,
            'duplicate_nisn' => $duplicateNisn,
            'duplicate_names' => $duplicateNames,
            'empty_nisn' => $emptyNisn,
            'suspicious_names' => $suspiciousNames,
        ];
    }

    /**
     * Clean duplicate students (category = siswa).
     *
     * @return array{
     *     duplicate_groups: int,
     *     deleted_count: int,
     *     details: array<int, array<string, mixed>>
     * }
     */
    public static function cleanDuplicateStudents(bool $dryRun = false): array
    {
        return DB::transaction(function () use ($dryRun) {
            $students = Voter::where('category', Voter::CATEGORY_SISWA)
                ->orderBy('id', 'asc')
                ->get();

            // 1. Group by non-empty NISN
            $nisnGroups = [];
            foreach ($students as $student) {
                $nisn = trim((string) ($student->nisn ?? ''));
                if ($nisn !== '') {
                    $nisnGroups[$nisn][] = $student;
                }
            }

            // 2. Group by normalized name
            $nameGroups = [];
            foreach ($students as $student) {
                $normName = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $student->name)));
                $nameGroups[$normName][] = $student;
            }

            $candidateGroups = [];
            foreach ($nisnGroups as $records) {
                if (count($records) > 1) {
                    $candidateGroups[] = $records;
                }
            }
            foreach ($nameGroups as $records) {
                if (count($records) > 1) {
                    $candidateGroups[] = $records;
                }
            }

            $deletedCount = 0;
            $duplicateGroupsCount = 0;
            $details = [];
            $alreadyProcessedIds = [];

            foreach ($candidateGroups as $records) {
                $records = array_values(array_filter($records, fn ($r) => ! in_array($r->id, $alreadyProcessedIds, true)));
                if (count($records) <= 1) {
                    continue;
                }

                $duplicateGroupsCount++;

                // Sort to pick the best keeper:
                // 1. has_voted = true first
                // 2. has non-empty nisn first
                // 3. lowest id first
                usort($records, function (Voter $a, Voter $b): int {
                    if ($a->has_voted !== $b->has_voted) {
                        return $b->has_voted ? 1 : -1;
                    }

                    $aHasNisn = ! empty($a->nisn);
                    $bHasNisn = ! empty($b->nisn);
                    if ($aHasNisn !== $bHasNisn) {
                        return $bHasNisn ? 1 : -1;
                    }

                    return $a->id <=> $b->id;
                });

                /** @var Voter $keeper */
                $keeper = $records[0];
                $duplicatesToDelete = array_slice($records, 1);

                // If keeper has no NISN but duplicate does, copy NISN
                if (empty($keeper->nisn)) {
                    foreach ($duplicatesToDelete as $dup) {
                        if (! empty($dup->nisn)) {
                            if (! $dryRun) {
                                $keeper->update(['nisn' => $dup->nisn]);
                            }
                            break;
                        }
                    }
                }

                $alreadyProcessedIds[] = $keeper->id;

                foreach ($duplicatesToDelete as $dup) {
                    $alreadyProcessedIds[] = $dup->id;
                    $details[] = [
                        'id' => $dup->id,
                        'name' => $dup->name,
                        'nisn' => $dup->nisn ?? '-',
                        'class' => $dup->class ?? '-',
                        'has_voted' => $dup->has_voted ? 'Sudah' : 'Belum',
                        'kept_id' => $keeper->id,
                        'kept_name' => $keeper->name,
                        'kept_class' => $keeper->class ?? '-',
                    ];

                    if (! $dryRun) {
                        $dup->delete();
                    }
                    $deletedCount++;
                }
            }

            return [
                'duplicate_groups' => $duplicateGroupsCount,
                'deleted_count' => $deletedCount,
                'details' => $details,
            ];
        });
    }
}
