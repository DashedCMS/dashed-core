<?php

namespace Dashed\DashedCore\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('provider')
                    ->options([
                        'own' => 'Own',
                        'google' => 'Google',
                        'trustpilot' => 'Trustpilot',
                    ])
                    ->required()
                    ->default('own'),

                TextInput::make('review_id')
                    ->label('Provider Review ID')
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
                        1 => '⭐ 1',
                        2 => '⭐⭐ 2',
                        3 => '⭐⭐⭐ 3',
                        4 => '⭐⭐⭐⭐ 4',
                        5 => '⭐⭐⭐⭐⭐ 5',
                    ])
                    ->required(),

                Textarea::make('review')
                    ->rows(5)
                    ->required()
                    ->columnSpanFull(),

                DateTimePicker::make('created_at')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
