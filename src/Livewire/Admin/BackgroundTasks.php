<?php

namespace Dashed\DashedCore\Livewire\Admin;

use Livewire\Component;
use Dashed\DashedCore\Models\BackgroundTask;

/**
 * Het takenicoon in de topbalk: lopende taken met balk en resterende tijd,
 * en wat er de laatste 24 uur klaar of mislukt is. Pollt met $refresh, en
 * dat telt niet als activiteit voor automatisch uitloggen (zie
 * CmsIdleTimeout::isActivity()); snel zolang er iets loopt, rustig anders.
 */
class BackgroundTasks extends Component
{
    public const RECENT_HOURS = 24;

    public const LIMIT = 10;

    public function render()
    {
        $user = auth()->user();

        $active = rescue(fn () => BackgroundTask::query()
            ->visibleTo($user)
            ->active()
            ->latest('id')
            ->limit(self::LIMIT)
            ->get(), collect(), report: false);

        $recent = rescue(fn () => BackgroundTask::query()
            ->visibleTo($user)
            ->whereIn('status', [BackgroundTask::STATUS_COMPLETED, BackgroundTask::STATUS_FAILED])
            ->where('updated_at', '>=', now()->subHours(self::RECENT_HOURS))
            ->latest('updated_at')
            ->limit(self::LIMIT)
            ->get(), collect(), report: false);

        return view('dashed-core::livewire.admin.background-tasks', [
            'active' => $active,
            'recent' => $recent,
        ]);
    }

    public static function formatSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return __('minder dan een minuut');
        }

        if ($seconds < 3600) {
            return __(':aantal min', ['aantal' => (int) ceil($seconds / 60)]);
        }

        return __(':uren u :minuten min', ['uren' => intdiv($seconds, 3600), 'minuten' => (int) floor(($seconds % 3600) / 60)]);
    }
}
