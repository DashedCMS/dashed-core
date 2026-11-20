<?php

namespace Dashed\DashedCore\Filament\Pages\Auth;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Dashed\DashedCore\Classes\AdminLocale;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;

/**
 * Filaments eigen profielpagina met daartussen de keuze voor de taal van het
 * CMS en de schakelaar voor het menu op eigen gebruik. De overige velden zijn
 * precies die van de standaardpagina, zodat opslaan van naam, e-mail en
 * wachtwoord ongewijzigd blijft werken.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getAdminLocaleFormComponent(),
            $this->getNavigationSortFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    protected function getAdminLocaleFormComponent(): Select
    {
        $options = AdminLocale::options();

        return Select::make('admin_locale')
            ->label(__('Taal van het CMS'))
            ->options($options)
            ->in(array_keys($options))
            ->placeholder($options[AdminLocale::default()] ?? null)
            ->helperText(__('Alleen de knoppen en teksten van het CMS. De inhoud, mails aan klanten en de website blijven in hun eigen taal.'));
    }

    protected function getNavigationSortFormComponent(): Toggle
    {
        return Toggle::make('navigation_sort_by_usage')
            ->label(__('Menu sorteren op mijn gebruik'))
            ->default(true)
            ->helperText(__('Binnen elk menukopje staan vastgepinde items bovenaan, daarna wat je de laatste 90 dagen het vaakst opende. Uit: de vaste volgorde, zonder vastgepinde items.'));
    }
}
