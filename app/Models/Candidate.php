<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'candidate_number',
    'leader_name',
    'co_leader_name',
    'vision',
    'mission',
    'photo_path',
    'color_tag',
])]
class Candidate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'candidate_number' => 'integer',
        ];
    }

    /**
     * Get ballots cast for this candidate.
     */
    public function ballots(): HasMany
    {
        return $this->hasMany(Ballot::class);
    }

    /**
     * Accessor for full photo URL or placeholder.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
        );
    }
}
