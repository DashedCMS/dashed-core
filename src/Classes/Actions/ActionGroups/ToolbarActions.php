<?php

namespace Dashed\DashedCore\Classes\Actions\ActionGroups;

use Dashed\DashedCore\Classes\Actions\SetNotPublicAction;
use Dashed\DashedCore\Classes\Actions\SetPublicAction;
use Dashed\DashedCore\Classes\Actions\TranslateAction;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Illuminate\Support\Facades\Schema;

class ToolbarActions
{
    public static function getActions(array $actions = []): array
    {
        return array_merge($actions, [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                RestoreBulkAction::make()
                    ->visible(function ($livewire) {
                        $modelClass = $livewire->getModel();
                        $model = app($modelClass);

                        return method_exists($model, 'trashed');
                    }),
                ForceDeleteBulkAction::make()
                    ->visible(function ($livewire) {
                        $modelClass = $livewire->getModel();
                        $model = app($modelClass);

                        return method_exists($model, 'trashed');
                    }),
            ]),
            BulkActionGroup::make([
                TranslateAction::make(),
            ])
                ->visible(function ($livewire) {

                    $modelClass = $livewire->getModel();
                    $model = app($modelClass);

                    if (AutomatedTranslation::automatedTranslationsEnabled() && ($model->translatable ?? false)) {
                        return true;
                    }

                    return false;
                })
                ->icon('heroicon-o-language')
                ->label('Vertalen'),
            BulkActionGroup::make([
                SetPublicAction::make(),
                SetNotPublicAction::make(),
            ])
                ->visible(function ($livewire) {

                    $modelClass = $livewire->getModel();
                    $model = app($modelClass);

                    return Schema::hasColumn($model->getTable(), 'public');
                })
                ->icon('heroicon-o-eye')
                ->label('Status'),
        ]);
    }
}
