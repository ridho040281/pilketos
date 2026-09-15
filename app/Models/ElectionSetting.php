<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'school_name',
    'school_logo',
    'favicon',
    'election_title',
    'academic_year',
    'start_time',
    'end_time',
    'is_active',
    'show_quick_count',
    'school_api_url',
    'school_api_key',
    'school_api_client_id',
    'school_api_secret',
    'pilketos_api_key',
    'last_sync_at',
    'last_sync_count',
    'guru_api_url',
    'guru_api_client_id',
    'guru_api_secret',
    'guru_api_token',
    'last_guru_sync_at',
    'last_guru_sync_count',
])]
class ElectionSetting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_active' => 'boolean',
            'show_quick_count' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_sync_count' => 'integer',
            'last_guru_sync_at' => 'datetime',
            'last_guru_sync_count' => 'integer',
        ];
    }

    protected static ?self $currentInstance = null;

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    public static function clearCache(): void
    {
        static::$currentInstance = null;
    }

    /**
     * Get the active election setting instance or default.
     */
    public static function current(): self
    {
        if (static::$currentInstance !== null) {
            return static::$currentInstance;
        }

        $setting = static::firstOrCreate(
            ['id' => 1],
            [
                'school_name' => 'SMA Negeri 1 Sekolah Impian',
                'election_title' => 'Pemilihan Ketua & Wakil Ketua OSIS Periode 2026/2027',
                'academic_year' => '2026/2027',
                'is_active' => true,
                'show_quick_count' => false,
                'pilketos_api_key' => bin2hex(random_bytes(16)),
                'guru_api_client_id' => 'client_edp3yftse3bxcrcf',
                'guru_api_secret' => 'EgUmiD5xGq1v6IdUMlTvxGModoUOoCjCIKncKu2I',
                'guru_api_token' => 'UOcvFMOE4fPisQxFDh1W7Q77wx6glE9P87wkcOswAd6RtxVLFvSmV3rrbXGW',
            ]
        );

        if (empty($setting->pilketos_api_key)) {
            $setting->update([
                'pilketos_api_key' => bin2hex(random_bytes(16)),
            ]);
        }

        return static::$currentInstance = $setting;
    }

    /**
     * Get the URL for the favicon, falling back to school logo or default favicon.
     */
    public function getFaviconUrl(): string
    {
        if ($this->favicon && Storage::disk('public')->exists($this->favicon)) {
            return asset('storage/'.$this->favicon);
        }

        if ($this->school_logo && Storage::disk('public')->exists($this->school_logo)) {
            return asset('storage/'.$this->school_logo);
        }

        return asset('favicon.ico');
    }

    /**
     * Generate dynamic academic years list (3 years: previous, current, next).
     *
     * @return array<int, string>
     */
    public static function getAcademicYearsList(): array
    {
        $month = (int) date('n');
        $baseYear = $month >= 7 ? (int) date('Y') : (int) date('Y') - 1;

        return [
            ($baseYear - 1).'/'.$baseYear,
            $baseYear.'/'.($baseYear + 1),
            ($baseYear + 1).'/'.($baseYear + 2),
        ];
    }
}
