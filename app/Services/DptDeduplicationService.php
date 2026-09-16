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
}
