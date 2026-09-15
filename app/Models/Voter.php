<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nisn',
    'name',
    'category',
    'class',
    'gender',
    'passcode',
    'has_voted',
    'voted_at',
])]
class Voter extends Model
{
    public const CATEGORY_SISWA = 'siswa';

    public const CATEGORY_GURU = 'guru';

    public const CATEGORY_TENDIK = 'tendik';

    public const CATEGORIES = [
        self::CATEGORY_SISWA => 'Siswa',
        self::CATEGORY_GURU => 'Guru',
        self::CATEGORY_TENDIK => 'Tenaga Kependidikan',
    ];

    /**
     * Get human readable category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category ?? 'siswa');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_voted' => 'boolean',
            'voted_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique 6-character alphanumeric passcode without ambiguous characters.
     */
    public static function generatePasscode(): string
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $passcode = '';
            for ($i = 0; $i < 6; $i++) {
                $passcode .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (static::where('passcode', $passcode)->exists());

        return $passcode;
    }
}
