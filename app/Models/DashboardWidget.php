<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard vidjetlarining rahbarga ko'rinishi. Admin hamma vidjetni ko'radi,
 * rahbar faqat visible_to_manager = true bo'lganlarini (yozuv yo'q bo'lsa — DEFAULTS).
 */
class DashboardWidget extends Model
{
    // kalit => [nomi, rahbarga odatiy ko'rinishi]
    public const CATALOG = [
        'kpi' => ["KPI ko'rsatkichlari", true],
        'trend' => ['Kunlik trend', true],
        'funnel' => ["Murojaat yo'li", true],
        'statuses' => ['Holatlar', true],
        'channels' => ['Kanallar', true],
        'heatmap' => ['Qaysi vaqtda murojaat keladi', true],
        'resolution' => ['Yechim vaqti taqsimoti', true],
        'at_risk' => ['Kechikkan va xavfdagilar', true],
        'executors' => ['Ijrochilar KPI reytingi', false],
        'score_parts' => ['KPI ball tarkibi', true],
        'departments' => ["Bo'limlar", false],
    ];

    protected $fillable = ['key', 'visible_to_manager'];

    protected function casts(): array
    {
        return ['visible_to_manager' => 'boolean'];
    }

    /** @return array<string, bool> kalit => rahbarga ko'rinadimi */
    public static function managerVisibility(): array
    {
        $stored = Schema::hasTable('dashboard_widgets')
            ? static::query()->pluck('visible_to_manager', 'key')->all()
            : [];

        return collect(self::CATALOG)
            ->mapWithKeys(fn (array $meta, string $key) => [$key => (bool) ($stored[$key] ?? $meta[1])])
            ->all();
    }

    public static function toggle(string $key): bool
    {
        $visible = ! (static::managerVisibility()[$key] ?? false);

        static::query()->updateOrCreate(['key' => $key], ['visible_to_manager' => $visible]);

        return $visible;
    }
}
