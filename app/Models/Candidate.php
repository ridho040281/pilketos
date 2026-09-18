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
     * @var list<string>
     */
    protected $appends = [
        'photo_url',
        'card_color',
    ];

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
     * Default distinct color palette for candidates.
     */
    public const DEFAULT_COLORS = [
        1 => '#4f46e5', // 01: Indigo / Biru
        2 => '#059669', // 02: Emerald / Hijau
        3 => '#dc2626', // 03: Merah / Red-Orange
        4 => '#9333ea', // 04: Ungu / Magenta
        5 => '#0284c7', // 05: Sky Blue
        6 => '#d97706', // 06: Amber Gold
        7 => '#0d9488', // 07: Teal
        8 => '#e11d48', // 08: Rose / Pink
    ];

    /**
     * Accessor for full photo URL or placeholder.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
        );
    }

    /**
     * Accessor for candidate distinct card/accent color.
     */
    protected function cardColor(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // If candidate has a custom color_tag that differs from default #4f46e5, use it
                if (! empty($this->color_tag) && $this->color_tag !== '#4f46e5') {
                    return $this->color_tag;
                }

                // If candidate 1 has #4f46e5, that's valid
                if (! empty($this->color_tag) && $this->candidate_number === 1) {
                    return $this->color_tag;
                }

                // Use the matching palette color based on candidate number
                return self::DEFAULT_COLORS[$this->candidate_number] ?? ($this->color_tag ?: '#4f46e5');
            }
        );
    }
}
