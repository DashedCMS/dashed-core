<?php

namespace Dashed\DashedCore\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class NestableSortingAction
{
    /**
     * @param  Closure(Model): string|null  $labelResolver
     */
    public static function make(
        Builder $query,
        string $parentColumn = 'parent_id',
        string $labelColumn = 'name',
        string $orderColumn = 'order',
        ?Closure $labelResolver = null,
        string $name = 'sorteren',
        string $emptyLabel = 'Er zijn nog geen items om te sorteren.',
        string $successMessage = 'Volgorde opgeslagen',
    ): Action {
        $loadTree = function () use ($query, $parentColumn, $labelColumn, $orderColumn, $labelResolver): array {
            $items = (clone $query)->orderBy($orderColumn)->get();
            $byParent = $items->groupBy(fn (Model $item) => $item->{$parentColumn});

            $build = function (?int $parentId) use (&$build, $byParent, $labelColumn, $labelResolver): array {
                $children = $byParent->get($parentId, collect());

                return $children->map(function (Model $item) use (&$build, $labelColumn, $labelResolver): array {
                    $label = $labelResolver
                        ? ($labelResolver)($item)
                        : ($item->{$labelColumn} ?: '#'.$item->getKey());

                    return [
                        'id' => $item->getKey(),
                        'name' => (string) $label,
                        'children' => $build($item->getKey()),
                    ];
                })->values()->all();
            };

            return $build(null);
        };

        $persistTree = function (array $nodes, ?int $parentId, Closure $self) use ($query, $parentColumn, $orderColumn): void {
            foreach ($nodes as $position => $node) {
                $id = (int) ($node['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                (clone $query)
                    ->where($query->getModel()->getQualifiedKeyName(), $id)
                    ->update([
                        $parentColumn => $parentId,
                        $orderColumn => $position + 1,
                    ]);

                $children = is_array($node['children'] ?? null) ? $node['children'] : [];
                if ($children !== []) {
                    $self($children, $id, $self);
                }
            }
        };

        return Action::make($name)
            ->label('Sorteren')
            ->icon('heroicon-o-bars-arrow-down')
            ->button()
            ->modalHeading('Sorteren')
            ->modalSubmitActionLabel('Opslaan')
            ->modalCancelActionLabel('Annuleren')
            ->modalWidth('xl')
            ->fillForm(fn () => [
                'tree' => json_encode($loadTree(), JSON_THROW_ON_ERROR),
            ])
            ->schema(fn () => [
                Hidden::make('tree')->default('[]'),
                View::make('dashed-core::filament.nestable-sorting-modal')
                    ->viewData(fn (View $component): array => [
                        'tree' => $loadTree(),
                        'statePath' => $component->getContainer()->getStatePath().'.tree',
                        'emptyLabel' => $emptyLabel,
                    ]),
            ])
            ->action(function (array $data) use ($persistTree, $successMessage): void {
                $tree = json_decode($data['tree'] ?? '[]', true) ?: [];
                $persistTree($tree, null, $persistTree);

                Notification::make()
                    ->title($successMessage)
                    ->success()
                    ->send();
            });
    }
}
