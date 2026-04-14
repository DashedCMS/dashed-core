<?php

namespace Dashed\DashedCore;

use Livewire\Livewire;
use Dashed\DashedCore\Models\Role;
use Dashed\DashedCore\Models\User;
use Dashed\DashedPages\Models\Page;
use Dashed\DashedCore\Models\Review;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Providers\AppServiceProvider;
use Dashed\DashedForms\Classes\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\Redirect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Package;
use Filament\Forms\Components\TextInput;
use Dashed\DashedCore\Mail\EmailRenderer;
use Dashed\DashedCore\Models\GlobalBlock;
use Dashed\DashedCore\Models\NotFoundPage;
use Dashed\DashedCore\Policies\RolePolicy;
use Dashed\DashedCore\Policies\UserPolicy;
use Dashed\DashedCore\Commands\MigrateToV4;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedCore\Mail\NotificationMail;
use Dashed\DashedCore\Policies\ReviewPolicy;
use Filament\Forms\Components\Builder\Block;
use Dashed\DashedCore\Commands\CreateSitemap;
use Dashed\DashedCore\Commands\UpdateCommand;
use Dashed\DashedCore\Mail\PasswordResetMail;
use Dashed\DashedCore\Commands\InstallCommand;
use Dashed\DashedCore\Policies\RedirectPolicy;
use Filament\Schemas\Components\Utilities\Get;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Dashed\DashedCore\Commands\CreateAdminUser;
use Dashed\DashedCore\Mail\NewAdminAccountMail;
use Dashed\DashedCore\Commands\CleanupOldExports;
use Dashed\DashedCore\Commands\SyncGoogleReviews;
use Dashed\DashedCore\Mail\EmailBlocks\TextBlock;
use Dashed\DashedCore\Mail\EmailTemplateRegistry;
use Dashed\DashedCore\Policies\GlobalBlockPolicy;
use Dashed\DashedCore\Commands\CreateDefaultPages;
use Dashed\DashedCore\Mail\EmailBlocks\ImageBlock;
use Dashed\DashedCore\Policies\NotFoundPagePolicy;
use Dashed\DashedCore\Commands\MigrateDatabaseToV4;
use Dashed\DashedCore\Livewire\Frontend\Auth\Login;
use Dashed\DashedCore\Mail\EmailBlocks\ButtonBlock;
use Dashed\DashedCore\Commands\CreateVisitableModel;
use Dashed\DashedCore\Mail\EmailBlocks\DividerBlock;
use Dashed\DashedCore\Mail\EmailBlocks\HeadingBlock;
use Dashed\DashedCore\Commands\PruneWebVitalsCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedCore\Commands\GenerateFaviconsCommand;
use Dashed\DashedCore\Livewire\Frontend\Account\Account;
use Dashed\DashedCore\Commands\AggregateWebVitalsCommand;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageStats;
use Dashed\DashedCore\Mail\EmailBlocks\OrderSummaryBlock;
use Dashed\DashedCore\Commands\ReplaceEditorStringsInFiles;
use Dashed\DashedCore\Livewire\Frontend\Auth\ResetPassword;
use Dashed\DashedCore\Livewire\Frontend\Auth\ForgotPassword;
use Dashed\DashedCore\Livewire\Frontend\Notification\Toastr;
use Dashed\DashedCore\Classes\RichEditorPlugins\HtmlIdPlugin;
use Dashed\DashedCore\Commands\InvalidatePasswordResetTokens;
use Dashed\DashedCore\Livewire\Frontend\Search\SearchResults;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Livewire\Infolists\SEO\SEOScoreInfoList;
use Dashed\DashedCore\Performance\Images\ImagePriorityTracker;
use Dashed\DashedCore\Performance\Scripts\DeferredScriptStore;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageGlobalStats;
use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\EmailSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Classes\RichEditorPlugins\MediaEmbedPlugin;
use Dashed\DashedCore\Classes\RichEditorPlugins\VideoEmbedPlugin;
use Dashed\DashedCore\Filament\Pages\Settings\ExportSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ReviewSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SearchSettingsPage;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonQueueStats;
use Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonOverviewStats;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonWaitTimeChart;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonFailedJobsTable;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonThroughputChart;
use Dashed\DashedCore\Livewire\Frontend\Protection\PasswordProtection;

class DashedCoreServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-core';

    public function bootingPackage()
    {
        if (file_exists(__DIR__.'/../routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        $this->app->singleton(EmailTemplateRegistry::class);
        $this->app->singleton(EmailRenderer::class);
        $this->app->singleton(\Dashed\DashedCore\Notifications\Channels\TelegramChannel::class);
        $this->app->singleton(\Dashed\DashedCore\Notifications\AdminNotifier::class);
        $this->app->singleton(\Dashed\DashedCore\Services\DocsRegistry::class);

        $this->app->scoped(ImagePriorityTracker::class, function () {
            return new ImagePriorityTracker(
                firstEagerCount: (int) config('dashed-core.performance.lazy_images_first_eager_count', 3),
            );
        });
        $this->app->scoped(
            DeferredScriptStore::class
        );

        cms()
            ->emailBlock('heading', HeadingBlock::class)
            ->emailBlock('text', TextBlock::class)
            ->emailBlock('button', ButtonBlock::class)
            ->emailBlock('image', ImageBlock::class)
            ->emailBlock('divider', DividerBlock::class)
            ->emailBlock('order-summary', OrderSummaryBlock::class);

        cms()
            ->registerMailable(NotificationMail::class)
            ->registerMailable(PasswordResetMail::class)
            ->registerMailable(NewAdminAccountMail::class);

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\EmailTemplateResource::class,
            title: 'E-mail templates',
            intro: 'Beheer de e-mails die je website automatisch verstuurt, zoals bestelbevestigingen en account e-mails. Je stelt elke mail samen uit losse blokken zodat je zonder technische kennis de inhoud en opmaak kunt aanpassen.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Een bestaande template openen en de inhoud aanpassen.
- Nieuwe templates aanmaken voor specifieke momenten.
- Per template het onderwerp en de afzender instellen.
- Zien welke templates actief zijn en welke niet.
MARKDOWN,
                ],
                [
                    'heading' => 'Werken met blokken',
                    'body' => <<<MARKDOWN
Elke e-mail bouw je op uit blokken die je in de gewenste volgorde onder elkaar zet:

- **Heading** voor een duidelijke titel bovenaan of tussen stukken tekst.
- **Tekst** voor een alinea met uitleg of een persoonlijke boodschap.
- **Knop** om de ontvanger naar een specifieke pagina te sturen.
- **Afbeelding** voor een banner, productfoto of logo.
- **Divider** om visueel een scheiding aan te brengen tussen secties.
- **Order summary** om automatisch het besteloverzicht van de klant te tonen.

Je kunt blokken slepen om de volgorde te veranderen en ze tussentijds verwijderen of dupliceren.
MARKDOWN,
                ],
            ],
            tips: [
                'Houd teksten kort en vriendelijk, lange mails worden vaak niet helemaal gelezen.',
                'Test een nieuwe template altijd eerst door hem naar jezelf te sturen.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\ExportResource::class,
            title: 'Exports',
            intro: 'Hier vind je alle exports terug die je eerder hebt gegenereerd vanuit het CMS, zoals exports van bestellingen, producten of facturen. Je kunt de bestanden opnieuw downloaden zonder dat je ze helemaal opnieuw hoeft aan te maken.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Eerder gegenereerde exports terugvinden en opnieuw downloaden.
- Zien wanneer een export is aangemaakt en door wie.
- Controleren wat voor type export het betreft, bijvoorbeeld orders of producten.
- Oude exports verwijderen die je niet meer nodig hebt.
MARKDOWN,
                ],
                [
                    'heading' => 'Hoe maak ik een export aan?',
                    'body' => 'Nieuwe exports maak je niet op deze pagina zelf. Je start ze via de export knoppen op de overzichten waar je de gegevens vandaan wilt halen, zoals de pagina met bestellingen, producten of facturen. Nadat de export klaar is komt hij automatisch in dit overzicht terecht.',
                ],
                [
                    'heading' => 'Bewaartermijn',
                    'body' => 'Exports worden niet oneindig bewaard. Na de bewaartermijn die je in de Exports instellingen hebt opgegeven worden de bestanden automatisch opgeruimd. Heb je een export langer nodig? Download hem dan op tijd naar je eigen computer.',
                ],
            ],
            tips: [
                'Download belangrijke exports meteen na het aanmaken zodat je ze lokaal hebt.',
                'Verwijder oude exports die je niet meer gebruikt om het overzicht rustig te houden.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\GlobalBlockResource::class,
            title: 'Globale blokken',
            intro: 'Met globale blokken maak je content die je op meerdere pagina\'s van de website wilt gebruiken. Wijzig je een globaal blok, dan wordt die wijziging automatisch doorgevoerd op elke pagina waar het blok staat.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Nieuwe globale blokken aanmaken voor content die je op meer dan een plek wilt tonen.
- Bestaande blokken openen en hun inhoud aanpassen.
- Zien welke blokken je al hebt en ze een duidelijke naam geven.
- Blokken verwijderen als je ze niet meer nodig hebt.
MARKDOWN,
                ],
                [
                    'heading' => 'Wanneer gebruik je een globaal blok?',
                    'body' => <<<MARKDOWN
Globale blokken zijn ideaal voor content die overal hetzelfde moet zijn en die je op een centrale plek wilt kunnen bijwerken. Denk aan:

- Een footer sectie met openingstijden of contactgegevens.
- Een banner met een actie die op meerdere pagina\'s terug moet komen.
- Een vaste call to action onder meerdere landingspagina\'s.
- Een sectie met kenmerken of USP\'s van je website.
MARKDOWN,
                ],
            ],
            tips: [
                'Geef elk globaal blok een herkenbare naam zodat je het later snel terugvindt.',
                'Controleer voor het wijzigen op welke pagina\'s het blok gebruikt wordt, zodat je weet waar de wijziging zichtbaar wordt.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\NotFoundPageResource::class,
            title: '404 hits',
            intro: 'Dit overzicht toont welke niet bestaande URLs bezoekers op je website proberen te openen. Zo zie je snel welke links kapot zijn of verkeerd gedeeld worden en kun je er direct een redirect van maken.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Zien welke URLs bezoekers tevergeefs opvragen.
- Aflezen hoe vaak een bepaalde URL is geprobeerd.
- Direct een redirect aanmaken vanuit de 404 hit.
- 404 hits verwijderen die je hebt opgelost of die niet meer relevant zijn.
MARKDOWN,
                ],
                [
                    'heading' => 'Een redirect aanmaken',
                    'body' => 'Naast elke 404 hit zit een knop "Maak redirect" waarmee je de niet bestaande URL in een paar klikken omzet naar een werkende bestemming. Je kiest zelf of het een permanente of tijdelijke redirect wordt en op welke pagina de bezoeker moet uitkomen.',
                ],
            ],
            tips: [
                'Kijk wekelijks in dit overzicht om nieuwe kapotte links snel te vangen.',
                'Gebruik 301 voor URLs die definitief verhuisd zijn en 302 voor tijdelijke oplossingen.',
                'Let op URLs die heel vaak voorkomen, die hebben de meeste impact op bezoekers.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\RedirectResource::class,
            title: 'Redirects',
            intro: 'Met redirects stuur je bezoekers van een oude URL door naar een nieuwe URL. Zo verlies je geen bezoekers als een pagina verhuist en blijven links in nieuwsbrieven of zoekresultaten werken.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Een nieuwe redirect aanmaken tussen een oude en een nieuwe URL.
- Kiezen of de redirect permanent (301) of tijdelijk (302) is.
- Een vervaldatum instellen zodat een redirect automatisch stopt.
- Bestaande redirects aanpassen of verwijderen.
MARKDOWN,
                ],
                [
                    'heading' => 'Permanent of tijdelijk?',
                    'body' => <<<MARKDOWN
Het verschil tussen de twee types bepaalt hoe zoekmachines met de verhuizing omgaan:

- **301 Permanent**: gebruik je als een pagina definitief verhuisd is. Zoekmachines geven de waarde van de oude URL door aan de nieuwe.
- **302 Tijdelijk**: gebruik je voor een doorverwijzing die later terug kan komen, bijvoorbeeld tijdens een actieperiode of onderhoud.
MARKDOWN,
                ],
            ],
            tips: [
                'Gebruik 301 als je zeker weet dat de oude URL nooit meer terugkomt.',
                'Zet een einddatum op tijdelijke acties zodat je later geen opruimwerk hebt.',
                'Maak redirects het liefst direct vanuit het 404 hits overzicht, dat scheelt overtypen.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\Reviews\ReviewResource::class,
            title: 'Reviews',
            intro: 'Hier beheer je alle beoordelingen die klanten op de website achterlaten. Bekijk nieuwe reviews, zet ze online wanneer ze akkoord zijn en houd de reputatie van je website netjes op orde.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Nieuwe reviews bekijken die binnengekomen zijn.
- Reviews publiceren zodat bezoekers ze op de website kunnen lezen.
- De tekst van een review bewerken, bijvoorbeeld om een typfout te verbeteren.
- Ongewenste of ongepaste reviews verwijderen.
MARKDOWN,
                ],
                [
                    'heading' => 'Publiceren of niet?',
                    'body' => 'Nieuwe reviews staan eerst klaar ter controle voordat ze op de website zichtbaar worden. Zo voorkom je dat spam of ongepaste inhoud zomaar online komt te staan. Pas wanneer je een review publiceert wordt hij voor bezoekers zichtbaar.',
                ],
            ],
            tips: [
                'Controleer minstens wekelijks op nieuwe reviews zodat ze niet blijven liggen.',
                'Verbeter alleen evidente typfouten, laat de mening van de klant verder staan.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\RoleResource::class,
            title: 'Rollen',
            intro: 'Met rollen bepaal je wat gebruikers van het CMS mogen zien en doen. Elke rol heeft zijn eigen set permissies die je heel gericht kunt instellen, zodat iedereen alleen toegang krijgt tot wat voor hem of haar nodig is.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Een nieuwe rol aanmaken voor bijvoorbeeld een beheerder, redacteur of magazijnmedewerker.
- Per rol precies aangeven welke onderdelen zichtbaar zijn en wat er mag.
- Bestaande rollen aanpassen als de taakverdeling in het team verandert.
- Rollen verwijderen die je niet meer gebruikt.
MARKDOWN,
                ],
                [
                    'heading' => 'Permissies instellen',
                    'body' => 'Permissies zijn gegroepeerd per onderdeel van het CMS, bijvoorbeeld bestellingen, producten of pagina\'s. Per groep kies je welke acties zijn toegestaan, zoals bekijken, aanmaken, bewerken of verwijderen. Met de knoppen "Alles aanzetten" en "Alles uitzetten" kun je razendsnel een hele groep tegelijk in- of uitschakelen.',
                ],
            ],
            tips: [
                'Geef elke rol een duidelijke naam die aansluit bij de functie, bijvoorbeeld "Redacteur".',
                'Begin met "Alles uitzetten" en vink alleen aan wat echt nodig is, dat is veiliger.',
                'Test een nieuwe rol door tijdelijk met een testaccount in te loggen.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedCore\Filament\Resources\UserResource::class,
            title: 'Gebruikers',
            intro: 'Hier beheer je alle accounts in het CMS, zowel de collega\'s die met het beheer werken als de klanten van de website. Je maakt nieuwe gebruikers aan, koppelt de juiste rol en beheert hun contact- en adresgegevens.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Een nieuwe gebruiker aanmaken en direct een rol toekennen.
- Contactgegevens bekijken en aanpassen.
- Bedrijfs- en bezorggegevens beheren voor klanten die dat nodig hebben.
- Gebruikers verwijderen die geen toegang meer moeten hebben.
MARKDOWN,
                ],
                [
                    'heading' => 'Inloggen als een andere gebruiker',
                    'body' => 'Met de impersonate actie log je tijdelijk in als een andere gebruiker om te zien wat hij of zij ziet. Heel handig om te testen of een rol de juiste rechten heeft of om een klant te helpen die vastloopt. Je keert daarna weer terug naar je eigen account zonder dat je opnieuw hoeft in te loggen.',
                ],
            ],
            tips: [
                'Gebruik impersonate alleen voor testen of ondersteuning, niet om acties namens de klant uit te voeren.',
                'Geef nieuwe collega\'s eerst een beperkte rol en breid uit zodra het nodig blijkt.',
                'Verwijder of deactiveer oude accounts van medewerkers die weg zijn.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Performance\WebVitalsPage::class,
            title: 'Web Vitals',
            intro: 'Real-time performance dashboard met de Core Web Vitals (LCP, CLS, INP, FCP, TTFB) van echte bezoekers van de website. Helpt om performance regressies te zien en optimalisaties te valideren.',
            sections: [
                [
                    'heading' => 'Wat zie je hier?',
                    'body' => <<<MARKDOWN
Het dashboard verzamelt metingen van echte bezoekers en groepeert ze per metric:

- **Tijdsbereik selectie** voor 7, 30 of 90 dagen rolling window.
- **Tabellen per metric** (LCP, CLS, INP, FCP en TTFB) met daarin URL pattern, device type, p75 percentiel waarde, aantal samples en een status badge.
- **Kleurcodering** met groen voor goed, oranje voor kan beter en rood voor slecht.
MARKDOWN,
                ],
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => 'Schakel tussen 7, 30 en 90 dagen om korte trends van structurele problemen te onderscheiden. Bekijk per metric welke pagina patronen onder de maat presteren. Vergelijk mobiel en desktop om te zien of een probleem alleen op trage verbindingen optreedt. Controleer na een release of je optimalisatie ook echt effect heeft in de praktijk.',
                ],
            ],
            tips: [
                'De data wordt een keer per nacht gebundeld. Je ziet geen live meting maar de afgeronde cijfers van de afgelopen volledige dag of dagen.',
                'LCP en CLS zijn de belangrijkste metrics voor SEO en gebruikerservaring.',
                'Focus op pagina\'s met veel samples. Een slechte score op een pagina met drie bezoekers zegt minder dan een middelmatige score op de homepage.',
                'Toegang tot dit dashboard is gelimiteerd tot @dashed e-mailadressen.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage::class,
            title: 'Account instellingen',
            intro: 'Hier koppel je de frontend pagina\'s die met klantaccounts te maken hebben en bepaal je hoe CMS-gebruikers extra moeten inloggen via tweestapsverificatie (MFA).',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Deze pagina bestaat uit twee delen:

1. **Account pagina\'s**: welke pagina op de website hoort bij inloggen, registreren, wachtwoord vergeten en het account-overzicht. De website verwijst klanten automatisch naar deze pagina\'s.
2. **Tweestapsverificatie (MFA)**: een extra beveiligingsstap voor iedereen die in het CMS inlogt. Naast wachtwoord moet er dan ook een code worden ingevuld.

Deze instellingen zijn per site configureerbaar. Beheer je meerdere sites, dan vind je bovenaan tabs om per site de waarden in te stellen.
MARKDOWN,
                ],
                [
                    'heading' => 'Wanneer gebruik je MFA?',
                    'body' => <<<MARKDOWN
Zet MFA aan zodra meerdere mensen toegang hebben tot het CMS, of wanneer je gevoelige gegevens beheert (klantgegevens, bestellingen, betalingen). Het toevoegen van een tweede stap maakt het veel moeilijker voor onbevoegden om binnen te komen, ook als een wachtwoord uitlekt.

Als je MFA forceert moet minstens een van de methodes hieronder aanstaan, anders kan niemand meer inloggen.
MARKDOWN,
                ],
            ],
            fields: [
                'Account pagina' => 'De pagina waar ingelogde klanten hun accountoverzicht zien (bestellingen, gegevens, adressen).',
                'Inlog pagina' => 'De pagina met het inlogformulier voor klanten.',
                'Wachtwoord vergeten pagina' => 'De pagina waar klanten een nieuw wachtwoord kunnen aanvragen als ze die vergeten zijn.',
                'Wachtwoord reset pagina' => 'De pagina waar klanten via de link uit hun e-mail een nieuw wachtwoord instellen.',
                'Wachtwoord beveiliging pagina' => 'De pagina waar bezoekers terechtkomen als een onderdeel van de site achter een wachtwoord staat.',
                'MFA verplichten' => 'Verplicht alle CMS-gebruikers om naast hun wachtwoord ook een tweede stap in te vullen bij het inloggen. Zet minstens een methode hieronder aan voordat je dit aanzet.',
                'MFA via app' => 'Sta toe dat gebruikers MFA via een authenticator app gebruiken (bijvoorbeeld Google Authenticator of Authy). Dit is de meest veilige methode.',
                'MFA via e-mail' => 'Sta toe dat gebruikers een code per e-mail ontvangen als tweede stap. Handig als back-up wanneer iemand geen authenticator app heeft.',
            ],
            tips: [
                'Forceer je MFA, communiceer dit dan vooraf met je team. Iedereen moet eenmalig de tweede stap instellen voor ze weer kunnen inloggen.',
                'MFA via een app is veiliger dan via e-mail. Bied beide aan als gebruikers de keuze willen hebben.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage::class,
            title: 'Cache instellingen',
            intro: 'Met deze pagina leeg je in een klik de volledige cache van de website. Dat dwingt het systeem af om alles opnieuw op te bouwen.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
Deze pagina bevat een knop: **Cache legen**. Druk je daarop, dan worden alle gecachte pagina\'s, instellingen en gegevens verwijderd. De eerstvolgende bezoeker bouwt de cache dan opnieuw op.

De website bewaart veel gegevens tijdelijk in de cache zodat pagina\'s sneller laden. Meestal werkt dit automatisch goed, maar soms zie je een wijziging niet meteen terug op de voorkant. In dat geval helpt het om de cache handmatig te legen.
MARKDOWN,
                ],
                [
                    'heading' => 'Wanneer gebruik je dit?',
                    'body' => <<<MARKDOWN
Druk op de knop in deze situaties:

1. Je hebt een wijziging gedaan in de instellingen of teksten en ziet die niet terug op de voorkant.
2. Een pagina laadt verouderde inhoud of toont oude prijzen of voorraden.
3. Je hebt een nieuwe taal, route of pagina toegevoegd en die werkt nog niet zoals verwacht.
4. Na een update van het systeem of na het wijzigen van technische instellingen.

Direct na het legen van de cache kan de eerste bezoeker iets langzamer laden. Dat is normaal: het systeem bouwt dan alles opnieuw op.
MARKDOWN,
                ],
            ],
            tips: [
                'Leeg de cache buiten piekuren als je veel bezoekers hebt. Vlak na het legen voelen de eerste paginaladingen iets trager.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\EmailSettingsPage::class,
            title: 'E-mail instellingen',
            intro: 'Hier bepaal je hoe e-mails die de website verstuurt eruit zien. Denk aan orderbevestigingen, formulier-meldingen en accountmails.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Op deze pagina stel je de huisstijl van uitgaande e-mails in:

1. **Kleuren**: de primaire kleur (topbar en knoppen), de tekstkleur op die kleur en de achtergrondkleur rondom het mailtje.
2. **Footer tekst**: de tekst die onderaan elke mail verschijnt.

Pas dit aan zodat e-mails herkenbaar passen bij de huisstijl van de website. Klanten zien deze mails als visitekaartje van het merk.
MARKDOWN,
                ],
            ],
            fields: [
                'Primaire kleur' => 'De hoofdkleur van de e-mail, gebruikt voor de topbar en voor knoppen zoals "Bekijk je bestelling". Kies een kleur die past bij het logo.',
                'Tekstkleur' => 'De kleur van tekst die op de primaire kleur staat, zoals tekst in knoppen of in de topbar. Zorg dat deze goed leesbaar is op de primaire kleur (meestal wit of zwart).',
                'Achtergrondkleur' => 'De achtergrondkleur rondom de witte e-mail container. Dit is het stukje dat zichtbaar is als de mail in een breed scherm wordt geopend.',
                'Footer tekst' => 'De tekst die onderaan iedere e-mail staat. Standaard is dit "© jaar sitenaam", maar je kunt er ook bedrijfsgegevens of een disclaimer in zetten.',
            ],
            tips: [
                'Test na een wijziging altijd even een echte e-mail (bijvoorbeeld door een testbestelling te plaatsen) om te zien hoe het er bij de klant uitziet.',
                'Houd de footer tekst kort. Te veel tekst onderin maakt mails rommelig.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\ExportSettingsPage::class,
            title: 'Exports instellingen',
            intro: 'Hier bepaal je hoe lang exportbestanden bewaard blijven voordat ze automatisch worden opgeruimd.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Wanneer je in het CMS een export maakt (bijvoorbeeld van orders, klanten of producten) wordt het bestand opgeslagen zodat je het later kunt downloaden. Op deze pagina stel je in hoeveel dagen die bestanden bewaard blijven. Daarna worden ze automatisch verwijderd.

Een korte bewaartermijn houdt de opslag schoon en is gunstig voor privacy. Een langere termijn is handig als je oude exports nog regelmatig terug moet zoeken.
MARKDOWN,
                ],
            ],
            fields: [
                'Bewaartermijn exports' => 'Het aantal dagen dat exportbestanden bewaard blijven. Standaard 365 dagen. Minimaal 1 dag, maximaal 3650 dagen (10 jaar). Daarna worden ze automatisch verwijderd.',
            ],
            tips: [
                'Bewaar exports met persoonsgegevens niet langer dan nodig. Dat past beter bij de AVG.',
                'Heb je een export voor lange termijn nodig? Download die zelf en bewaar hem buiten het CMS.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage::class,
            title: 'Algemeen instellingen',
            intro: 'De basisgegevens van je website. Hier leg je naam, contactgegevens, bedrijfsinformatie, branding en externe koppelingen vast.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Deze pagina is opgedeeld in drie tabbladen:

1. **Bedrijfsgegevens**: naam van de site, contact e-mailadressen en bedrijfsinformatie zoals KVK, BTW en adres.
2. **Branding**: logo en favicon (het kleine icoontje in de browsertab).
3. **Externe koppeling**: tracking scripts en verificatie tags voor Google, Facebook, Bing en andere diensten.

Al deze instellingen zijn per site configureerbaar. Als je meerdere sites beheert vind je bovenaan tabs waarin je per site de waarden invult.
MARKDOWN,
                ],
                [
                    'heading' => 'Hoe zet je dit op?',
                    'body' => <<<MARKDOWN
Begin altijd met de bedrijfsgegevens en branding. Die zijn meteen zichtbaar voor klanten. Externe koppelingen (zoals Google Analytics of de Facebook Pixel) doe je daarna, op het moment dat de tracking accounts klaar zijn.

Verificatie tags voor Google en Bing heb je eenmalig nodig om aan zoekmachines te bewijzen dat de site van jou is. Daarna kun je ze laten staan.
MARKDOWN,
                ],
            ],
            fields: [
                'Site naam' => 'De naam van de website zoals die op de voorkant verschijnt en in e-mails wordt gebruikt.',
                'Contact e-mail' => 'Het e-mailadres waar belangrijke meldingen naartoe gaan, bijvoorbeeld nieuwe bestellingen of formulier inzendingen. Dit adres is intern, klanten zien het niet.',
                'Afzender e-mail' => 'Het afzender-adres voor e-mails die de website naar klanten stuurt. Dit adres is wel zichtbaar voor klanten. Zorg dat dit domein via SPF en DKIM geauthoriseerd is, anders belanden mails in de spam map.',
                'KVK nummer' => 'Het KVK-nummer van het bedrijf. Wordt gebruikt op facturen en in de footer.',
                'BTW nummer' => 'Het BTW-nummer van het bedrijf. Verplicht op facturen.',
                'Telefoonnummer' => 'Telefoonnummer waarop klanten het bedrijf kunnen bereiken.',
                'Bank / IBAN' => 'IBAN van het bedrijf, gebruikt op facturen.',
                'Straatnaam' => 'Straatnaam van het bedrijfsadres.',
                'Huisnummer' => 'Huisnummer van het bedrijfsadres.',
                'Plaatsnaam' => 'Plaatsnaam van het bedrijfsadres.',
                'Postcode' => 'Postcode van het bedrijfsadres.',
                'Land' => 'Land van het bedrijfsadres.',
                'Logo' => 'Het logo van de website. Wordt getoond in de header en op verschillende plekken in de website.',
                'Favicon' => 'Het kleine icoontje dat in de browsertab verschijnt naast de paginatitel. Bij voorkeur vierkant, minimaal 32x32 pixels.',
                'Google Analytics ID' => 'Het meet-ID van Google Analytics 4, begint meestal met "G-". Hiermee worden bezoekersstatistieken verzameld.',
                'Google Tag Manager ID' => 'Het container-ID van Google Tag Manager, begint met "GTM-". Hiermee laad je via een centrale plek meerdere tracking scripts.',
                'Google Places API sleutel' => 'API-sleutel voor Google Places. Nodig om automatisch reviews vanuit Google Maps op te halen.',
                'Google Places ID' => 'Het unieke Place ID van de bedrijfsvermelding op Google Maps. Samen met de API-sleutel worden hiermee reviews opgehaald.',
                'Facebook Conversions API ID' => 'Het ID van de Facebook Conversions API. Nodig om server-side conversies te sturen.',
                'Facebook Pixel ID' => 'Het ID van de Facebook Pixel die op de site wordt geladen.',
                'Facebook events sturen' => 'Stuur automatisch e-commerce events naar Facebook over bezoekersacties op de website (bijvoorbeeld product bekeken en aankoop). Vereist een ingevulde Facebook Pixel. Alleen van toepassing als je e-commerce functionaliteit op de site gebruikt.',
                'TikTok events sturen' => 'Stuur automatisch e-commerce events naar TikTok. Vereist een ingestelde TikTok Pixel.',
                'Google verificatiecode' => 'Verificatiecode voor Google Search Console. Hiermee bewijs je dat de site van jou is.',
                'Bing verificatiecode' => 'Verificatiecode voor Bing Webmaster Tools.',
                'Alexa verificatiecode' => 'Verificatiecode voor Alexa.',
                'Pinterest verificatiecode' => 'Verificatiecode voor Pinterest.',
                'Yandex verificatiecode' => 'Verificatiecode voor Yandex Webmaster.',
                'Norton verificatiecode' => 'Verificatiecode voor Norton Safe Web.',
                'Extra scripts in head' => 'Extra HTML of script-tags die in de head van iedere pagina worden geplaatst. Gebruik dit alleen voor scripts die echt in de head moeten, bijvoorbeeld bepaalde tracking pixels.',
                'Extra scripts in body' => 'Extra HTML of script-tags die net voor het sluitende body-einde worden geplaatst. Voor de meeste tracking is dit de juiste plek omdat het de pagina niet vertraagt.',
                'Beheerder-balk tonen' => 'Toon een balk bovenaan de site voor ingelogde beheerders met snelle links naar het CMS. Bezoekers zien deze balk niet.',
            ],
            tips: [
                'Een ongeldig from e-mailadres kan ervoor zorgen dat orderbevestigingen in de spam map belanden.',
                'Gebruik bij voorkeur Google Tag Manager in plaats van losse scripts in de extra scripts velden. Dat houdt het overzicht beter.',
                'Vergeet niet om bij meerdere sites de tabs bovenaan langs te lopen. Iedere site heeft eigen instellingen.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage::class,
            title: 'Afbeeldingen instellingen',
            intro: 'Instellingen voor hoe afbeeldingen op de website worden geladen en getoond.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Op deze pagina bepaal je twee dingen over hoe afbeeldingen worden geladen:

1. **Lazy loading**: laadt afbeeldingen pas op het moment dat de bezoeker er bijna bij is. Dat maakt de eerste indruk van een pagina sneller.
2. **Sizes attribuut**: geeft de browser informatie over welk formaat afbeelding nodig is op verschillende schermgroottes.

Beide instellingen kunnen de prestaties van de website beinvloeden. Test na een wijziging altijd even hoe de website voelt.
MARKDOWN,
                ],
            ],
            fields: [
                'Lazy loading forceren' => 'Forceer lazy loading op alle afbeeldingen op de website. Hierdoor laden afbeeldingen pas wanneer de bezoeker er naartoe scrolt. Dit maakt pagina\'s sneller, maar kan in zeldzame gevallen de eerste afbeelding bovenaan iets later tonen.',
                'Sizes attribuut tonen' => 'Voeg een sizes attribuut toe aan image tags zodat de browser de juiste afbeeldingsgrootte kiest. Let op: in sommige situaties kan dit juist de website vertragen. Test na het aanzetten of pagina\'s nog snel laden.',
            ],
            tips: [
                'Lazy loading is in bijna alle gevallen een goede instelling. Laat hem aan tenzij je ergens vreemd gedrag merkt.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\NotificationSettingsPage::class,
            title: 'Meldingen instellingen',
            intro: 'Stuur belangrijke meldingen ook naar Telegram, naast de e-mailmeldingen die het CMS al verstuurt. Denk aan nieuwe bestellingen, formulier inzendingen en lage voorraad.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Op deze pagina koppel je een Telegram bot aan het CMS. Zodra de koppeling werkt ontvang je via Telegram een bericht bij belangrijke gebeurtenissen, bovenop de e-mailmelding.

Telegram is handig omdat het sneller binnenkomt dan e-mail en je het op je telefoon direct ziet. Ideaal voor mensen die niet de hele dag in hun mailbox zitten.
MARKDOWN,
                ],
                [
                    'heading' => 'Hoe zet je dit op?',
                    'body' => <<<MARKDOWN
Een bot maken en koppelen gaat zo:

1. Open Telegram en zoek **@BotFather**. Stuur het commando `/newbot` en volg de stappen. BotFather geeft je een **bot token** terug. Plak die hieronder bij "Bot token".
2. Bepaal naar welke chat de meldingen moeten. Voor jezelf: zoek **@userinfobot** in Telegram en kopieer je persoonlijke chat ID. Voor een groep: voeg de bot toe aan de groep en gebruik een group ID (begint altijd met -100).
3. Vul de chat ID in en sla op. Test daarna met een echte gebeurtenis (bijvoorbeeld een testbestelling) of de melding aankomt.
MARKDOWN,
                ],
                [
                    'heading' => 'Wanneer gebruik je dit?',
                    'body' => 'Telegram meldingen zijn handig als je snel wilt reageren op nieuwe bestellingen, of als meerdere collega\'s tegelijk een melding moeten zien (via een groep). Het vervangt de e-mail niet, het komt er bovenop.',
                ],
            ],
            fields: [
                'Telegram actief' => 'Pauzeer of activeer Telegram meldingen zonder de instellingen weg te gooien. Handig als je tijdelijk geen meldingen wilt (bijvoorbeeld op vakantie).',
                'Bot token' => 'Het token van de Telegram bot, in de vorm "123456:ABC-DEF...". Krijg je via @BotFather wanneer je een bot aanmaakt. Behandel dit als een wachtwoord, deel het niet.',
                'Chat ID' => 'De chat waar meldingen naartoe gestuurd worden. Voor jezelf: een persoonlijk ID dat je via @userinfobot opvraagt. Voor een groep: een ID dat met -100 begint. De bot moet wel lid zijn van die groep.',
            ],
            tips: [
                'Wil je dat meerdere mensen meekijken? Maak een Telegram groep, voeg de bot toe en gebruik het group ID.',
                'Geen meldingen ontvangen? Controleer of de bot lid is van de groep, en of de chat ID precies klopt (inclusief het min-teken bij groepen).',
                'Het bot token is gevoelige informatie. Maak een nieuw token aan via @BotFather als het ergens uitlekt.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\ReviewSettingsPage::class,
            title: 'Reviews instellingen',
            intro: 'Bepaal op welke pagina van de website reviews getoond worden.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => 'Op deze pagina kies je welke pagina op de website dient als overzichtspagina voor reviews. Bezoekers die op een review-link klikken komen op deze pagina terecht. Deze instelling is per site configureerbaar.',
                ],
            ],
            fields: [
                'Reviews pagina' => 'De pagina waarop alle reviews van de website getoond worden. Maak eerst de pagina aan via Pagina\'s en koppel hem hier.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\SearchSettingsPage::class,
            title: 'Zoeken instellingen',
            intro: 'Bepaal op welke pagina zoekresultaten van de website verschijnen.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => 'Wanneer een bezoeker iets intypt in de zoekbalk komen ze op een zoekresultatenpagina terecht. Op deze pagina kies je welke pagina dat is. Deze instelling is per site configureerbaar.',
                ],
            ],
            fields: [
                'Zoek pagina' => 'De pagina waar bezoekers naartoe gaan nadat ze in de zoekbalk iets hebben getypt. Maak eerst de pagina aan via Pagina\'s en koppel hem hier.',
            ],
            tips: [
                'Zorg dat de gekozen pagina ook daadwerkelijk een zoekresultatenblok bevat, anders zien bezoekers niets terug.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage::class,
            title: 'SEO instellingen',
            intro: 'Standaard SEO-instellingen die op de hele website gelden, behalve op pagina\'s waar je expliciet eigen waarden invult.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => 'Op deze pagina stel je de standaard SEO en social media gegevens in die de website gebruikt als een specifieke pagina geen eigen waarden heeft. Denk aan de Twitter handle van het bedrijf en een fallback afbeelding voor wanneer je een link deelt op social media. Deze instellingen zijn per site configureerbaar.',
                ],
            ],
            fields: [
                'Twitter site handle' => 'De Twitter (X) handle van het bedrijf, inclusief @-teken. Bijvoorbeeld "@dashed.dev". Wordt meegestuurd in de meta tags zodat Twitter weet wie de site beheert.',
                'Twitter auteur handle' => 'De Twitter (X) handle van de auteur of contentmaker. Inclusief @-teken.',
                'Standaard social image' => 'De fallback afbeelding voor social media (Facebook, LinkedIn, Twitter). Wordt gebruikt op pagina\'s die geen eigen social image hebben. Bij voorkeur 1200x630 pixels voor het beste resultaat.',
            ],
            tips: [
                'Een goede fallback social image zorgt dat gedeelde links er altijd verzorgd uitzien, ook op pagina\'s waar nog geen eigen afbeelding is ingesteld.',
                'Heeft een specifieke pagina een eigen meta image of meta beschrijving? Die wint altijd van de waarden op deze pagina.',
            ],
        );

        Model::unguard();

        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'superadmin') {
                return true;
            }

            foreach ($user->roles as $role) {
                $perms = $role->extra_permissions ?? [];
                if (is_array($perms) && in_array($ability, $perms)) {
                    return true;
                }
            }

            return null;
        });

        Gate::policy(GlobalBlock::class, GlobalBlockPolicy::class);
        Gate::policy(NotFoundPage::class, NotFoundPagePolicy::class);
        Gate::policy(Redirect::class, RedirectPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        cms()->registerRolePermissions('Inhoud', [
            'view_global_block' => 'Globale blokken bekijken',
            'edit_global_block' => 'Globale blokken bewerken',
            'delete_global_block' => 'Globale blokken verwijderen',
        ]);

        cms()->registerRolePermissions('SEO', [
            'view_not_found_page' => 'Niet-gevonden pagina\'s bekijken',
            'edit_not_found_page' => 'Niet-gevonden pagina\'s bewerken',
            'delete_not_found_page' => 'Niet-gevonden pagina\'s verwijderen',
            'view_redirect' => 'Redirects bekijken',
            'edit_redirect' => 'Redirects bewerken',
            'delete_redirect' => 'Redirects verwijderen',
        ]);

        cms()->registerRolePermissions('Reviews', [
            'view_review' => 'Reviews bekijken',
            'edit_review' => 'Reviews bewerken',
            'delete_review' => 'Reviews verwijderen',
        ]);

        cms()->registerRolePermissions('Gebruikers', [
            'view_user' => 'Gebruikers bekijken',
            'edit_user' => 'Gebruikers bewerken',
            'delete_user' => 'Gebruikers verwijderen',
            'view_role' => 'Rollen bekijken',
            'edit_role' => 'Rollen bewerken',
            'delete_role' => 'Rollen verwijderen',
        ]);

        cms()->registerRolePermissions('Overige', [
            'view_horizon' => 'Horizon bekijken',
            'view_exports' => 'Exports bekijken',
        ]);

        Livewire::component('notification.toastr', Toastr::class);
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.forgot-password', ForgotPassword::class);
        Livewire::component('auth.reset-password', ResetPassword::class);
        Livewire::component('account.account', Account::class);
        //        Livewire::component('infolists.seo', SEOScoreInfoList::class);
        Livewire::component('search.search-results', SearchResults::class);
        Livewire::component('protection.password-protection', PasswordProtection::class);

        // Widgets
        Livewire::component('not-found-page-stats', NotFoundPageStats::class);
        Livewire::component('not-found-page-global-stats', NotFoundPageGlobalStats::class);

        // Horizon widgets
        Livewire::component('dashed.dashed-core.filament.widgets.horizon.horizon-overview-stats', HorizonOverviewStats::class);
        Livewire::component('dashed.dashed-core.filament.widgets.horizon.horizon-queue-stats', HorizonQueueStats::class);
        Livewire::component('dashed.dashed-core.filament.widgets.horizon.horizon-throughput-chart', HorizonThroughputChart::class);
        Livewire::component('dashed.dashed-core.filament.widgets.horizon.horizon-wait-time-chart', HorizonWaitTimeChart::class);
        Livewire::component('dashed.dashed-core.filament.widgets.horizon.horizon-failed-jobs-table', HorizonFailedJobsTable::class);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(CreateSitemap::class)->daily();
            $schedule->command(InvalidatePasswordResetTokens::class)->everyFifteenMinutes();
            $schedule->command(CleanupOldExports::class)->daily();
            $schedule->command(SyncGoogleReviews::class)->twiceDaily();
            //            $schedule->command(SeoScan::class)->daily();
            $schedule->command(AggregateWebVitalsCommand::class)->dailyAt('03:00');
            $schedule->command(PruneWebVitalsCommand::class)->dailyAt('03:15');
        });

        if (! $this->app->environment('production')) {
            Mail::alwaysFrom('info@dashed.nl');
            Mail::alwaysTo('info@dashed.nl');
        }

        $builderBlockClasses = [];
        //        if (config('dashed-core.registerDefaultBuilderBlocks', true)) {
        //            $builderBlockClasses[] = 'builderBlocks';
        //        }

        $builderBlockClasses[] = 'defaultPageBuilderBlocks';

        cms()->builder('builderBlockClasses', [
            self::class => $builderBlockClasses,
        ]);

        cms()->builder('createDefaultPages', [
            self::class => 'createDefaultPages',
        ]);

        cms()->builder('publishOnUpdate', [
            'dashed-core-config',
            'dashed-core-assets',
        ]);

        cms()->builder('blockDisabledForCache', [
            'reset-password-block',
            'forgot-password-block',
            'login-block',
            'account-block',
            'password-protection-block',
        ]);

        cms()->builder('plugins', [
            new DashedCorePlugin(),
        ]);

        cms()->builder('richEditorPlugins', [
            VideoEmbedPlugin::make(),
            MediaEmbedPlugin::make(),
            //            HtmlIdPlugin::make(),
        ]);

    }

    public static function builderBlocks()
    {
        $defaultBlocks = [
            Block::make('header')
                ->label('Header')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('toptitle')
                        ->label('Top title'),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle', 'Sub titel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding'),
                ]),
            Block::make('header-2')
                ->label('Header 2')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle', 'Sub titel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding'),
                ]),
            Block::make('header-3')
                ->label('Header 3')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle')
                        ->label('Sub titel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding'),
                    Repeater::make('usps')
                        ->label('USPs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            cms()->editorField('subtitle')
                                ->label('Subtitel')
                                ->required(),
                            TextInput::make('icon')
                                ->label('Icoon')
                                ->helperText('Lucide icons: https://lucide.dev/icons/, just paste the svg code here')
                                ->required(),
                        ]),
                ]),
            Block::make('header-4')
                ->label('Header 4')
                ->schema([
                    self::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle')
                        ->label('Subtitel'),
                    self::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Achtergrond afbeelding', isImage: true),
                    mediaHelper()->field('image-2', 'Afbeelding 2', isImage: true),
                    mediaHelper()->field('image-3', 'Afbeelding 3', isImage: true),
                    mediaHelper()->field('image-4', 'Afbeelding 4', isImage: true),
                    Repeater::make('usps')
                        ->label('USPs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            linkHelper()->field('url', true, 'URL'),
                            TextInput::make('icon')
                                ->label('Icoon')
                                ->helperText('Lucide icons: https://lucide.dev/icons/, just paste the svg code here')
                                ->required(),
                        ]),
                ]),
            Block::make('spacer')
                ->label('Spacer')
                ->schema([]),
            Block::make('small-spacer')
                ->label('Kleine spacer')
                ->schema([]),
            Block::make('content-with-image')
                ->label('Content with image')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    TextInput::make('subtitle')
                        ->label('Subtitel'),
                    Toggle::make('image-left')
                        ->label('Afbeelding links'),
                    cms()->editorField('content', 'Content'),
                    mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                ]),
            Block::make('content')
                ->label('Content')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Toggle::make('full-width')
                        ->label('Volledige breedte'),
                    cms()->editorField('content', 'Content')
                        ->required(),
                ]),
            Block::make('contact-form')
                ->label('Contact form')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('content', 'Content'),
                    Toggle::make('show_side_info')
                        ->default(true),
                    Forms::formSelecter(),
                    mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                ]),
            Block::make('usps-with-icon')
                ->label('USPs met iconen')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Repeater::make('usps')
                        ->label('USPs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            cms()->editorField('subtitle', 'Subtitel')
                                ->required(),
                            IconPicker::make('icon')
                                ->label('Icoon')
                                ->required(),
                        ]),
                ]),
            Block::make('image-blocks-with-info')
                ->label('Afbeelding blokken met info')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Repeater::make('blocks')
                        ->label('Blokken')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            TextInput::make('subtitle')
                                ->label('Subtitel')
                                ->required(),
                            mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                            AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                        ]),
                ]),
            Block::make('logo-cloud')
                ->label('Logo cloud')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    Repeater::make('logos')
                        ->label('Logos')
                        ->minItems(0)
                        ->maxItems(100)
                        ->schema([
                            mediaHelper()->field('image', 'Afbeelding', required: true, isImage: true),
                            linkHelper()->field('url', true),
                        ]),
                ]),
            Block::make('team')
                ->label('Team')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle', 'Subtitel'),

                    Repeater::make('team')
                        ->label('Team')
                        ->required()
                        ->minItems(1)
                        ->cloneable()
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam')
                                ->required(),
                            TextInput::make('function')
                                ->required()
                                ->label('Functie'),
                            mediaHelper()->field('image', 'Afbeelding', required: true, isImage: true),
                            mediaHelper()->field('image-2', 'Afbeelding 2', required: true, isImage: true),
                        ]),
                ]),
            Block::make('faq')
                ->label('FAQ')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel'),
                    TextInput::make('subtitle')
                        ->label('Subtitel'),
                    TextInput::make('columns')
                        ->numeric()
                        ->label('Aantal kolommen'),
                    Repeater::make('faqs')
                        ->label('FAQs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            cms()->editorField('content', 'Content')
                                ->required(),
                        ]),
                ]),
            Block::make('maps-embed')
                ->label('Maps embed')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                ]),
            Block::make('html')
                ->label('HTML')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Textarea::make('html')
                        ->label('HTML')
                        ->required()
                        ->rows(5),
                ]),
            Block::make('media')
                ->label('Afbeelding / video')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    mediaHelper()->field('media', 'Afbeelding / video', isImage: true, required: true),
                    TextInput::make('max_width_number')
                        ->label('Max breedte')
                        ->default(100)
                        ->integer()
                        ->minValue(0)
                        ->maxValue(10000),
                    Select::make('max_width_type')
                        ->label('Max breedte')
                        ->default('%')
                        ->options([
                            'px' => 'px',
                            '%' => '%',
                        ]),
                    Select::make('align')
                        ->label('Uitlijning')
                        ->default('center')
                        ->options([
                            'center' => 'Midden',
                            'left' => 'Links',
                            'right' => 'Rechts',
                        ]),
                ]),
            Block::make('search-results-block')
                ->label('Zoekresultaten')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                ]),
            Block::make('reviews-with-content')
                ->label('Reviews met content')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Toggle::make('hide_content')
                        ->label('Content verbergen')
                        ->default(false),
                    TextInput::make('title')
                        ->label('Titel')
                        ->visible(fn (Get $get) => ! $get('hide_content')),
                    TextInput::make('subtitle')
                        ->label('Subtitel')
                        ->visible(fn (Get $get) => ! $get('hide_content')),
                    cms()->editorField('content', 'Content')
                        ->visible(fn (Get $get) => ! $get('hide_content')),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons')
                        ->visible(fn (Get $get) => ! $get('hide_content')),
                    TextInput::make('amount_of_reviews')
                        ->label('Aantal reviews')
                        ->numeric()
                        ->default(12),
                    Toggle::make('random_reviews')
                        ->label('Random reviews'),
                    Select::make('min_stars')
                        ->label('Minimaal aantal sterren')
                        ->options([
                            0 => 'Alle reviews',
                            1 => '1 ster en hoger',
                            2 => '2 sterren en hoger',
                            3 => '3 sterren en hoger',
                            4 => '4 sterren en hoger',
                            5 => '5 sterren',
                        ]),
                ]),
            Block::make('reviews-slider')
                ->label('Reviews slider')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    TextInput::make('amount_of_reviews')
                        ->label('Aantal reviews')
                        ->numeric()
                        ->default(12),
                    Toggle::make('random_reviews')
                        ->label('Random reviews'),
                    Select::make('min_stars')
                        ->label('Minimaal aantal sterren')
                        ->options([
                            0 => 'Alle reviews',
                            1 => '1 ster en hoger',
                            2 => '2 sterren en hoger',
                            3 => '3 sterren en hoger',
                            4 => '4 sterren en hoger',
                            5 => '5 sterren',
                        ]),
                ]),
        ];

        cms()
            ->builder('blocks', $defaultBlocks);
    }

    public static function defaultPageBuilderBlocks()
    {
        $defaultBlocks = [
            Block::make('account-block')
                ->label('Account')
                ->schema([]),
            Block::make('login-block')
                ->label('Login')
                ->schema([]),
            Block::make('forgot-password-block')
                ->label('Wachtwoord vergeten')
                ->schema([]),
            Block::make('reset-password-block')
                ->label('Reset wachtwoord')
                ->schema([]),
            Block::make('password-protection-block')
                ->label('Wachtwoord beveiliging voor pagina\'s')
                ->schema([]),
        ];

        cms()
            ->builder('blocks', $defaultBlocks);
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        //        $this->loadViewsFrom(__DIR__ . '/../resources/views/frontend', 'dashed-core');

        $this->publishes([
            __DIR__.'/../resources/templates' => resource_path('views/'.config('dashed-core.site_theme', 'dashed')),
            __DIR__.'/../resources/component-templates' => resource_path('views/components'),
        ], 'dashed-templates');

        $this->publishes([
            __DIR__.'/../resources/views/docs' => resource_path('views/vendor/dashed-core/docs'),
        ], 'dashed-docs-views');

        cms()->registerSettingsPage(GeneralSettingsPage::class, 'Algemeen', 'cog', 'Algemene informatie van de website');
        cms()->registerSettingsPage(AccountSettingsPage::class, 'Account', 'user', 'Account instellingen van de website');
        cms()->registerSettingsPage(SEOSettingsPage::class, 'SEO', 'identification', 'SEO van de website');
        cms()->registerSettingsPage(ImageSettingsPage::class, 'Afbeelding', 'photo', 'Afbeelding van de website');
        cms()->registerSettingsPage(CacheSettingsPage::class, 'Cache', 'photo', 'Cache van de website');
        cms()->registerSettingsPage(SearchSettingsPage::class, 'Search', 'magnifying-glass', 'Zoek instellingen van de website');
        cms()->registerSettingsPage(ExportSettingsPage::class, 'Exports', 'document-arrow-down', 'Bewaartermijn van exports instellen');
        cms()->registerSettingsPage(EmailSettingsPage::class, 'E-mail', 'envelope', 'Kleuren en styling van verzonden e-mails');
        cms()->registerSettingsPage(\Dashed\DashedCore\Filament\Pages\Settings\NotificationSettingsPage::class, 'Notificaties', 'bell', 'Telegram kanaal voor admin notificaties');
        cms()->registerSettingsPage(ReviewSettingsPage::class, 'Review', 'star', 'Review instellingen van de website');

        $package
            ->name(static::$name)
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filament-forms-tinyeditor',
                //                'filament-tiptap-editor',
                'filesystems',
                'file-manager',
                'livewire',
                'laravellocalization',
                'flare',
                'dashed-core',
                //                'seo',
                'activitylog',
            ])
            ->hasRoutes([
                'frontend',
            ])
            ->hasViews()
            ->hasAssets()
            ->hasCommands([
                CreateAdminUser::class,
                InstallCommand::class,
                UpdateCommand::class,
                InvalidatePasswordResetTokens::class,
                CleanupOldExports::class,
                CreateSitemap::class,
                CreateVisitableModel::class,
                SyncGoogleReviews::class,
                CreateDefaultPages::class,
                ReplaceEditorStringsInFiles::class,
                MigrateToV4::class,
                MigrateDatabaseToV4::class,
                AggregateWebVitalsCommand::class,
                PruneWebVitalsCommand::class,
                GenerateFaviconsCommand::class,
            ]);

    }

    public static function createDefaultPages(): void
    {
        if (! Page::where('is_home', 1)->count()) {
            $page = new Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Home');
                $page->setTranslation('slug', $locale, 'home');
            }
            $page->is_home = 1;
            $page->save();

            $page = new Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Contact');
                $page->setTranslation('slug', $locale, 'contact');
            }
            $page->save();
        }

        if (! Customsetting::get('search_page_id')) {
            $page = new Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Zoek resultaten');
                $page->setTranslation('slug', $locale, 'zoeken');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                            'title' => 'Zoekresultaten',
                        ],
                        'type' => 'search-results-block',
                    ],
                ]);
            }
            $page->save();
            Customsetting::set('search_page_id', $page->id);
        }

        if (! Customsetting::get('login_page_id')) {
            $page = new Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Login');
                $page->setTranslation('slug', $locale, 'login');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'login-block',
                    ],
                ]);
            }
            $page->save();

            Customsetting::set('login_page_id', $page->id);
        }

        if (! Customsetting::get('account_page_id')) {
            $page = new Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Account');
                $page->setTranslation('slug', $locale, 'account');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'account-block',
                    ],
                ]);
            }
            $page->save();

            Customsetting::set('account_page_id', $page->id);

            $page->metadata()->create([
                'noindex' => true,
            ]);
        }

        if (! Customsetting::get('forgot_password_page_id')) {
            $page = new Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Wachtwoord vergeten');
                $page->setTranslation('slug', $locale, 'wachtwoord-vergeten');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'forgot-password-block',
                    ],
                ]);
            }
            $page->save();

            Customsetting::set('forgot_password_page_id', $page->id);
        }

        if (! Customsetting::get('reset_password_page_id')) {
            $page = new Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Reset wachtwoord');
                $page->setTranslation('slug', $locale, 'reset-wachtwoord');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'reset-password-block',
                    ],
                ]);
            }
            $page->save();

            Customsetting::set('reset_password_page_id', $page->id);

            $page->metadata()->create([
                'noindex' => true,
            ]);
        }
    }
}
