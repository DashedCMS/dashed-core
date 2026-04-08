<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Dashed\DashedCore\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Utilities\Set;
use Dashed\DashedCore\Filament\Resources\RoleResource\Pages\EditRole;
use Dashed\DashedCore\Filament\Resources\RoleResource\Pages\ListRoles;
use Dashed\DashedCore\Filament\Resources\RoleResource\Pages\CreateRole;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | UnitEnum | null $navigationGroup = 'Gebruikers';

    protected static ?string $navigationLabel = 'Rollen';

    protected static ?string $label = 'Rol';

    protected static ?string $pluralLabel = 'Rollen';

    protected static ?int $navigationSort = 101;

    public static function form(Schema $schema): Schema
    {
        $allPermissions = cms()->getRolePermissions();

        $allGroupKeys = collect($allPermissions)
            ->map(fn ($perms, $group) => 'permissions_' . md5($group))
            ->values()
            ->toArray();

        $permissionSections = collect($allPermissions)
            ->map(function (array $permissions, string $group) {
                $fieldKey = 'permissions_' . md5($group);

                return Section::make("Permissies voor {$group}")
                    ->headerActions([
                        Action::make('selectGroup_' . md5($group))
                            ->label('Alles aanzetten')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->action(function (Set $set) use ($fieldKey, $permissions) {
                                $set($fieldKey, array_keys($permissions));
                            }),
                        Action::make('deselectGroup_' . md5($group))
                            ->label('Alles uitzetten')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->action(function (Set $set) use ($fieldKey) {
                                $set($fieldKey, []);
                            }),
                    ])
                    ->schema([
                        CheckboxList::make($fieldKey)
                            ->hiddenLabel()
                            ->options($permissions)
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(false);
            })
            ->values()
            ->toArray();

        return $schema->schema([
            Section::make('Rol')
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ])
                ->columnSpanFull(),

            Section::make('Gebruikers')
                ->schema([
                    Select::make('users')
                        ->label('Gekoppelde gebruikers')
                        ->multiple()
                        ->options(User::orderBy('first_name')->get()->mapWithKeys(fn ($u) => [$u->id => $u->name . ' (' . $u->email . ')']))
                        ->searchable()
                        ->preload(),
                ])
                ->columnSpanFull(),

            Section::make('Permissies')
                ->headerActions([
                    Action::make('selectAll')
                        ->label('Alles aanzetten')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Set $set) use ($allPermissions) {
                            foreach ($allPermissions as $group => $permissions) {
                                $set('permissions_' . md5($group), array_keys($permissions));
                            }
                        }),
                    Action::make('deselectAll')
                        ->label('Alles uitzetten')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Set $set) use ($allPermissions) {
                            foreach ($allPermissions as $group => $permissions) {
                                $set('permissions_' . md5($group), []);
                            }
                        }),
                ])
                ->schema($permissionSections)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable(),

                TextColumn::make('users_count')
                    ->label('Gebruikers')
                    ->counts('users')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
