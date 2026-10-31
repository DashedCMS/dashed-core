<?php

namespace Dashed\DashedCore\Classes\Seo;

use Illuminate\Support\Str;

/**
 * De slug van een model in één taal.
 *
 * Tot v4.99.0 kreeg een taal zonder eigen naam een slug van alleen willekeurige tekens: de lege slug botste met
 * andere lege slugs en kreeg er een teken bij tot hij uniek was. Zo ontstonden adressen als /en/m of
 * /en/blogs/1idw, met een pagina zonder inhoud, die Google massaal vond. Een taal zonder naam en zonder slug
 * krijgt nu geen slug (dus geen pagina in die taal, zie HreflangUrls en Sitemap), en een botsing krijgt -2,
 * -3 in plaats van een willekeurig teken.
 */
class SlugMaker
{
    /**
     * De gewenste slug: de ingevulde slug, anders de naam, beide als slug. Leeg als er geen van beide is.
     */
    public static function bron(?string $slug, ?string $naam): string
    {
        return Str::slug(trim((string) $slug) !== '' ? (string) $slug : (string) $naam);
    }

    /**
     * Maakt de slug uniek met -2, -3, ... zolang $bestaat hem al kent. Een lege slug blijft leeg.
     *
     * @param  callable(string): bool  $bestaat
     */
    public static function uniek(string $slug, callable $bestaat): string
    {
        if ($slug === '' || ! $bestaat($slug)) {
            return $slug;
        }

        $nummer = 2;
        while ($bestaat($slug . '-' . $nummer)) {
            $nummer++;
        }

        return $slug . '-' . $nummer;
    }
}
