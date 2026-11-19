<?php

namespace Dashed\DashedCore\Classes;

use Filament\Panel;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;

/**
 * De Dashed-huisstijl van het paneel: strak, rustig, in licht en donker. Het
 * menu en de indeling blijven die van Filament; alleen de stijl verandert.
 * Alles zit in één stijlblok via een render hook, zodat een klantproject er
 * geen eigen Vite-build voor nodig heeft.
 *
 * Standaard aan; DASHED_CMS_LAYOUT=classic zet de kale Filament-stijl terug.
 */
class CmsLayout
{
    public static function enabled(): bool
    {
        return config('dashed-core.dashed_cms.layout', 'dashed') === 'dashed';
    }

    public static function apply(Panel $panel): Panel
    {
        return $panel
            ->colors([
                'primary' => config('dashed-core.dashed_cms.primary_color', '#00D2CD'),
                'gray' => Color::Zinc,
            ])
            ->font('Geist')
            ->sidebarWidth('17.5rem')
            ->brandLogo(fn () => new HtmlString(view('dashed-core::filament.cms-layout.brand', [
                'siteName' => Customsetting::get('site_name', null, 'Dashed'),
            ])->render()))
            ->brandLogoHeight('2.75rem')
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('dashed-core::filament.cms-layout.styles'))
            // Inlogpagina en andere losse pagina's (MFA, wachtwoord vergeten):
            // donkere achtergrond, merk boven de kaart, tegels eronder.
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn () => view('dashed-core::filament.cms-layout.auth-aside', [
                'siteName' => Customsetting::get('site_name', null, 'Dashed'),
            ]));
    }
}
