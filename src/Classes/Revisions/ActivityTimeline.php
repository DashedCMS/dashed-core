<?php

namespace Dashed\DashedCore\Classes\Revisions;

use Illuminate\Support\Str;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * De wijzigingen van één record uit de spatie-activitylog: wie, wanneer en
 * welke velden van oud naar nieuw. Neemt de log van de metadata en de extra
 * blokken van het record mee, want die loggen los.
 */
class ActivityTimeline
{
    public const IGNORED = ['updated_at', 'created_at'];

    public static function supports(?Model $model): bool
    {
        return $model !== null
            && $model->exists
            && class_exists(Activity::class)
            && in_array(LogsActivity::class, class_uses_recursive($model), true);
    }

    /**
     * @return array<int, array{at: string, user: string, event: string, subject: ?string, changes: array<int, array{field: string, old: string, new: string}>}>
     */
    public static function for(Model $model, int $limit = 50): array
    {
        if (! static::supports($model)) {
            return [];
        }

        $subjects = [[$model->getMorphClass(), $model->getKey(), null]];

        foreach (RevisionSnapshot::relationsFor($model) as $relation) {
            $related = $model->{$relation}()->first();

            if ($related) {
                $subjects[] = [$related->getMorphClass(), $related->getKey(), $relation];
            }
        }

        $labels = [];
        $query = Activity::query()->with('causer')->latest('id')->limit($limit);
        $query->where(function ($query) use ($subjects, &$labels) {
            foreach ($subjects as [$type, $id, $relation]) {
                $labels[$type . '#' . $id] = $relation;
                $query->orWhere(fn ($q) => $q->where('subject_type', $type)->where('subject_id', $id));
            }
        });

        return $query->get()->map(function (Activity $activity) use ($labels) {
            $properties = $activity->properties?->toArray() ?? [];
            $new = (array) ($properties['attributes'] ?? []);
            $old = (array) ($properties['old'] ?? []);
            $changes = [];

            foreach (array_unique(array_merge(array_keys($new), array_keys($old))) as $field) {
                if (in_array($field, static::IGNORED, true)) {
                    continue;
                }

                $oldValue = RevisionDiff::stringify($old[$field] ?? null);
                $newValue = RevisionDiff::stringify($new[$field] ?? null);

                if ($oldValue === $newValue) {
                    continue;
                }

                $changes[] = [
                    'field' => Str::ucfirst(str_replace('_', ' ', Str::snake((string) $field))),
                    'old' => Str::limit($oldValue, 120),
                    'new' => Str::limit($newValue, 120),
                ];
            }

            $relation = $labels[$activity->subject_type . '#' . $activity->subject_id] ?? null;

            return [
                'at' => $activity->created_at?->format('d-m-Y H:i') ?? '',
                'user' => Revision::userLabel($activity->causer instanceof User ? $activity->causer : null),
                'event' => static::eventLabel((string) ($activity->event ?: $activity->description)),
                'subject' => match ($relation) {
                    'metadata' => __('Metadata'),
                    'customBlocks' => __('Extra blokken'),
                    null => null,
                    default => $relation,
                },
                'changes' => $changes,
            ];
        })->all();
    }

    protected static function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => __('Aangemaakt'),
            'updated' => __('Gewijzigd'),
            'deleted' => __('Verwijderd'),
            'restored' => __('Hersteld'),
            default => $event,
        };
    }
}
