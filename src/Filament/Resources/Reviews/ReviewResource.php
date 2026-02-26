<?php

namespace Dashed\DashedCore\Filament\Resources\Reviews;

use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Dashed\DashedCore\Models\Review;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Dashed\DashedCore\Filament\Resources\Reviews\Pages\EditReview;
use Dashed\DashedCore\Filament\Resources\Reviews\Pages\ListReviews;
use Dashed\DashedCore\Filament\Resources\Reviews\Pages\CreateReview;
use Dashed\DashedCore\Filament\Resources\Reviews\Schemas\ReviewForm;
use Dashed\DashedCore\Filament\Resources\Reviews\Tables\ReviewsTable;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;
    protected static string | \UnitEnum | null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Reviews';
    protected static ?string $label = 'Review';
    protected static ?string $pluralLabel = 'Reviews';

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
