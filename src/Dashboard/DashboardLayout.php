<?php

namespace Dashed\DashedCore\Dashboard;

use Dashed\DashedCore\Models\Customsetting;

class DashboardLayout
{
    public const SETTING_KEY = 'dashboard_widget_layout';

    public function __construct(private DashboardWidgetRegistry $registry) {}

    /** @return array<int, array{id:string,class:string,label:string,visible:bool,width:int|string}> */
    public function resolved(string $siteId): array
    {
        $registry = $this->registry->all();
        $saved = $this->savedItems($siteId);

        $result = [];
        $usedIds = [];

        // 1. Opgeslagen items in hun volgorde (alleen bekende id's).
        foreach ($saved as $item) {
            $id = (string) ($item['id'] ?? '');
            if (! isset($registry[$id])) {
                continue;
            }
            $result[] = [
                'id' => $id,
                'class' => $registry[$id]['class'],
                'label' => $registry[$id]['label'],
                'visible' => (bool) ($item['visible'] ?? true),
                'width' => self::clampWidth($item['width'] ?? $registry[$id]['width']),
            ];
            $usedIds[$id] = true;
        }

        // 2. Nieuw geregistreerde widgets onderaan, zichtbaar, met registry-defaults.
        foreach ($registry as $id => $meta) {
            if (isset($usedIds[$id])) {
                continue;
            }
            $result[] = [
                'id' => $id,
                'class' => $meta['class'],
                'label' => $meta['label'],
                'visible' => true,
                'width' => self::clampWidth($meta['width']),
            ];
        }

        return $result;
    }

    public function save(string $siteId, array $items): void
    {
        $registry = $this->registry->all();
        $clean = [];

        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            if (! isset($registry[$id])) {
                continue; // onbekende id's niet opslaan
            }
            $clean[] = [
                'id' => $id,
                'visible' => (bool) ($item['visible'] ?? true),
                'width' => self::clampWidth($item['width'] ?? $registry[$id]['width']),
            ];
        }

        Customsetting::set(self::SETTING_KEY, json_encode($clean), $siteId);
    }

    public function reset(string $siteId): void
    {
        Customsetting::reset(self::SETTING_KEY, $siteId);
    }

    public static function clampWidth(mixed $width): int|string
    {
        if ($width === 'full') {
            return 'full';
        }
        if (is_numeric($width)) {
            return max(1, min(4, (int) $width));
        }

        return 'full';
    }

    /** @return array<int, array> */
    protected function savedItems(string $siteId): array
    {
        $raw = Customsetting::get(self::SETTING_KEY, $siteId);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }
}
