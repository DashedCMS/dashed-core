<?php

namespace Dashed\DashedCore\Filament\Resources\Reviews\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('provider')
                    ->options([
                        'own' => __('Own'),
                        'google' => __('Google'),
                        'trustpilot' => __('Trustpilot'),
                    ])
                    ->required()
                    ->default('own'),

                TextInput::make('review_id')
                    ->label(__('Provider Review ID'))
                    ->disabled()
                    ->maxLength(255),

                TextInput::make('name')
                    ->maxLength(255),

                TextInput::make('company')
                    ->maxLength(255),

                mediaHelper()->field('profile_image', 'Profiel afbeelding'),

                mediaHelper()->field('image', 'Afbeelding'),

                Select::make('stars')
                    ->options([
                        1 => __('⭐ 1'),
                        2 => __('⭐⭐ 2'),
                        3 => __('⭐⭐⭐ 3'),
                        4 => __('⭐⭐⭐⭐ 4'),
                        5 => __('⭐⭐⭐⭐⭐ 5'),
                    ])
                    ->required(),

                Textarea::make('review')
                    ->rows(5)
                    ->required()
                    ->columnSpanFull(),

                DateTimePicker::make('created_at')
                    ->label(__('Aangemaakt op'))
                    ->columnSpanFull(),
            ]);
    }
}
