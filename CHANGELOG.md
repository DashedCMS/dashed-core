# Changelog

All notable changes to `Dashed core` will be documented in this file.

## v4.6.0 - 2026-05-07

### Added
- `Dashed\DashedCore\Filament\Actions\NestableSortingAction::make()` — gedeelde drag-and-drop tree-sorter Action voor Filament 4. Werkt op zowel `ListRecords`-pages als `RelationManager`s. Parameters: query-builder + `parentColumn`/`labelColumn`/`orderColumn` + optionele `labelResolver` closure + aanpasbare lege/succes-labels.
- `Dashed\DashedCore\Filament\Concerns\HasNestableSortingAction` — trait voor `ListRecords`-pages die via `getNestableSortingHeaderAction()` automatisch de Sorteren-knop teruggeeft wanneer het Resource's model `IsVisitable` gebruikt en `canHaveParent() === true` retourneert. Multi-site `thisSite()` scope wordt automatisch toegepast wanneer aanwezig op het model.
- Filament-asset `nestable-sorting.js` (geregistreerd via `FilamentAsset::register`) en views `dashed-core::filament.nestable-sorting-modal` + `_nestable-sorting-node`. JS laadt SortableJS via CDN op eerste open.

## v4.5.2 - 2026-05-07

### Added
- `dashed:cleanup-old-not-found-page-occurrences` Artisan-command. Verwijdert (force-delete) records uit `dashed__not_found_page_occurrences` ouder dan de geconfigureerde bewaartermijn (`Customsetting('not_found_page_occurrences_retention_days')`, default 30 dagen). De `NotFoundPage`-records zelf blijven staan; alleen hun bezoek-historie wordt opgeschoond. `total_occurrences` en `last_occurrence` op `NotFoundPage` worden na de opschoning herberekend zodat de teller in het admin-overzicht klopt. Geregistreerd in de scheduler op `daily()`.
- `NotFoundPageSettingsPage` (Filament) onder Instellingen > "404-pagina" met één veld "Bewaartermijn 404-bezoeken (dagen)" (numeriek, 1–3650, default 30). Gebruikt dezelfde patroon als `ExportSettingsPage`. Schrijft per site naar `Customsetting('not_found_page_occurrences_retention_days')`.

## v4.5.1 - 2026-05-07

### Changed
- `NotificationSubscriptions` Filament-pagina is verplaatst naar de Settings-pagina's (via `cms()->registerSettingsPage()`) en niet meer als losse sidebar-knop in de "Overige"-groep. `shouldRegisterNavigation = false` zodat de pagina niet meer in de hoofd-navigatie verschijnt; admins openen 'm via Instellingen > "Mijn samenvattingen".

## v4.5.0 - 2026-05-07

### Changed
- **`dashed-core::emails.layout` "Bezoek :siteName:"-CTA-button is nu opt-in** via een nieuwe `$showVisitSiteCta` prop (default `false`). Voorheen werd de button standaard onder elke mail getoond, wat te dominant was voor mails als order-opvolg / review-request. De header blijft clickable en de domein-link onder de copyright-regel blijft staan, dus de site-link is nog steeds aanwezig in de footer. Mailables die de CTA-button willen tonen kunnen `'showVisitSiteCta' => true` meegeven aan `->view(...)->with([...])`.

### Added
- **Framework voor admin samenvatting-mails (Fase 1).** Eerste stap van een feature waarmee admins zich kunnen abonneren op periodieke samenvattings van data uit de dashed-modules (omzet, popup-stats, follow-up flows, MyParcel labels, marketing posts, etc).
  - Nieuwe migratie `dashed__summary_subscriptions` met unique index op (user_id, contributor_key) en een index op (next_send_at, frequency).
  - Eloquent model `Dashed\DashedCore\Models\SummarySubscription` met `due()`-scope voor de scheduler.
  - Contract `Dashed\DashedCore\Services\Summary\Contracts\SummaryContributorInterface` plus immutable DTOs `SummaryPeriod` en `SummarySection` zodat packages een sectie kunnen bijdragen.
  - Twee nieuwe email-blocks `stats` en `table` (geregistreerd in `cms()->emailBlocks()`), inline-styled en mail-client-veilig, met afwisselende rij-achtergronden.
  - Mailable `Dashed\DashedCore\Mail\SummaryMail` die de unified `dashed-core::emails.layout` gebruikt en secties stabiel sorteert op title.
  - Artisan command `dashed:dispatch-summary-mails` met `nextTickFor()`-helper. Geregistreerd in de scheduler op `everyFifteenMinutes()->withoutOverlapping()`. Dispatch-uur is configureerbaar via `Customsetting::get('summary_dispatch_hour', null, 9)`. Iedere contributor-aanroep en mail-verzending zit in zijn eigen try/catch + `report()` zodat een falende contributor de hele dispatch niet blokkeert.
  - Filament-pagina `Dashed\DashedCore\Filament\Pages\NotificationSubscriptions` op slug `me/summary-subscriptions` met een Select per geregistreerde contributor en een header-action "Stuur testmail nu" die synchronously alle actieve subscriptions van de huidige user mailt zonder `next_send_at` te updaten.
  - Bestaande `NotificationSettingsPage` uitgebreid met sectie "Samenvattings-defaults": per contributor 1 select die schrijft naar `Customsetting::set("summary_default_{key}", $value, $siteId)`.
  - Nieuwe builder-key `summaryContributors` zodat packages in hun eigen ServiceProvider hun contributor-class kunnen registreren via `cms()->builder('summaryContributors', [...])`.
- Fase 2 (de feitelijke contributors per package) volgt in aparte minor-bumps van `dashed-ecommerce-core`, `dashed-popups`, `dashed-forms`, `dashed-marketing` en `dashed-ecommerce-myparcel`.

## v4.4.0 - 2026-05-06

### Added
- **Inloggegevens-mail bij het aanmaken van een admin via het volledige formulier.** `CreateUser` (UserResource) verstuurt nu na save een `NewAdminAccountMail` met het zelf ingevulde plaintext-wachtwoord, mits de rol `admin` of `superadmin` is. Voorheen werd alleen via de "Admin user aanmaken"-quick-action op de lijst een mail verstuurd; bij het volledige create-formulier kreeg de nieuwe admin niets. Mail-fouten faillen niet de create maar tonen een waarschuwingsnotificatie.
- **"Nieuw wachtwoord versturen"-knop op de gebruiker-edit-pagina.** Filament header-action met confirmation-modal die een willekeurig wachtwoord (`bin2hex(random_bytes(8))`, 16 hex-chars) genereert, hash't, opslaat en direct via `NewAdminAccountMail` naar de gebruiker mailt. Werkt voor elke gebruiker, niet alleen admins. Faalt-veilig: als de mail niet weg kan rolt het opgeslagen wachtwoord niet terug, maar wordt een danger-notification getoond zodat de admin het handmatig kan delen.

## v4.3.6 - 2026-05-05

### Changed
- De gedeelde "Status"-kolom uit `HasVisitableTab::visitableTableColumns()` is nu sorteerbaar. De accessor `getStatusAttribute()` is geen echt DB-veld, dus de sortable-callback ordent via `orderByRaw` op een `CASE`-expressie die `public`, `start_date` en `end_date` in dezelfde combinatie evalueert als de accessor zelf. Werkt automatisch door in alle Resources die `visitableTableColumns()` gebruiken (Page, Article, ArticleCategory, Vacancy, VacancyCategory, Product, ProductGroup, ProductCategory).

## v4.3.5 - 2026-05-04

### Changed
- Standaard label van de afmeld-link in `dashed-core::emails.layout` ingekort van "Afmelden voor deze automatische e-mails" naar simpel **"Afmelden"**.

## v4.3.4 - 2026-05-04

### Added
- `dashed-core::emails.layout` accepteert nu optionele `$unsubscribeUrl` + `$unsubscribeLabel` props die als kleine onderlijnde link onderin de footer worden gerenderd (alleen zichtbaar wanneer `$unsubscribeUrl` is gezet). Mailables die in een automatische flow zitten kunnen hiermee een afmeld-link tonen zonder de layout-blade aan te passen.

## v4.3.3 - 2026-05-03

### Added
- **Website-link in alle systeem-mails** via `dashed-core::emails.layout`. De header (logo of site-naam tegen de primaryColor band) is nu een clickable link naar de website, en boven de footer komt een "Bezoek :siteName:" CTA-button + onder de © regel een onderlijnde domein-link. URL wordt in deze volgorde opgelost: `Customsetting::get('site_url')` -> `config('app.url')`. `EmailRenderer::render()` levert `$siteUrl` mee aan de layout. Geldt automatisch voor admin order confirmation, payment-link, password reset, abandoned-cart en alle andere mails die de unified layout gebruiken.

### Changed
- Em-dashes (U+2014) verwijderd uit alle source-bestanden, blade-templates en CHANGELOG-entries van deze package.

## v4.3.2 - 2026-05-03

### Changed
- `order-summary` email block toont nu de gebruikte kortingscode + percentage in het discount-label naast het kortingsbedrag (bv. "Korting (TERUG-ABCD1234 - 10%)" ipv enkel "Korting"). Wordt automatisch zichtbaar in alle emails die dit block gebruiken (admin order confirmation, payment link, cancelled order, etc.).

## v4.3.1 - 2026-05-01

### Changed
- Fullscreen-toggle weer als regulier item in de toolbar (eerste positie) i.p.v. absoluut gepositioneerd. Past nu netjes op één regel doordat undo/redo/strike/underline al uit de lijst zijn gehaald.

## v4.3.0 - 2026-05-01

### Added
- Schermvullende modus voor `cms()->editorField()`: een toggle-icoon rechtsboven in de toolbar opent de editor over het volledige scherm. Esc of nogmaals klikken sluit. Werkt automatisch op élke RichEditor in het Filament-panel via een MutationObserver - geen aanpassing aan losse veld-aanroepen nodig.
- Sticky toolbar in elke RichEditor: de knoppenbalk blijft `position: sticky; top: 0` zodat hij altijd zichtbaar is bij het scrollen door lange content.
- `FilamentRichContentServiceProvider` registreert nu een nieuwe `Css` en `Js` asset (`rich-editor-fullscreen`) die via `php artisan filament:assets` worden gepubliceerd.

### Changed
- `editorField()` toolbar opgeschoond: `undo`, `redo`, `strike` en `underline` verwijderd uit de hoofd-toolbar; `underline` en `strike` ook uit de paragraph floating-toolbar.

## v4.2.0 - 2026-04-27

### Added
- Nieuwe public API `cms()->registerNavigationGroup(string $name, int $sort = 100)` waarmee elke package zelf zijn Filament navigatiegroep registreert. Eerste registratie per naam wint op sort zodat plugins niet over elkaars positie strijden. Sortwaarden gaan in stappen van 10 (10 Content, 20 Artikelen, 30 E-commerce, 40 Producten, 50 Formulieren, 60 Marketing, 70 Gebruikers, 80 Performance, 90 Routes, 100 Overige, 110 Statistics, 120 Export) zodat tussenliggende ruimte vrij is voor nieuwe groepen.
- `CMSManager::getFilamentPanelItems()` leest nu `cms()->builder('navigationGroups')`, sorteert op `sort` en past `->navigationGroups([...])` toe op het Filament-panel. Roept `->navigationGroups()` alleen aan als er groepen geregistreerd zijn, dus geen lege call op een kale installatie.
- `DashedCoreServiceProvider::bootingPackage()` registreert de eigen groepen Content, Gebruikers, Performance, Routes en Overige.

## v4.1.0 - 2026-04-24

### Added
- `EmailTemplate`-model is nu vertaalbaar (`subject`, `from_name`, `blocks`) via `spatie/laravel-translatable`.
- `HasEmailTemplate`-trait-methods (`renderFromTemplate`, `templateSubject`, `templateFrom`) accepteren een optionele `?string $locale` parameter.
- Nieuwe `ResolvesEmailLocale`-trait voor Mailables om locale af te leiden uit order/customer-context.
- Filament-Resource heeft nu locale-tabs (via LaraZeus SpatieTranslatable).
- Nieuwe Filament-actions "Kopieer naar locale" en "Vertaal met DeepL" op de edit-page.
- Locale-completeness-badge op de tabel-lijst + waarschuwing op de edit-page bij ontbrekende vertalingen.

### Changed
- `EmailRenderer::render()` en `renderSubject()` accepteren een `?string $locale`; rendering scoped binnen een locale-savepoint om `__()`/`trans()` in block-renderers correct te laten werken.

### Fixed
- `name` en `mailable_key` blijven zichtbaar bij taalwisseling (`Placeholder` in plaats van disabled `TextInput`).
- "Kopieer naar locale" en "Vertaal met DeepL" modals: `from_locale` defaultet op de huidige actieve locale, `to_locales` op alle andere.
- `EmailTemplate::getFallbackLocale()` valt terug op een locale die daadwerkelijk inhoud heeft (niet blindelings op `config('app.fallback_locale')`), zodat renderen nooit naar een lege locale valt.
- Waarschuwingstekst en badge-tooltip tonen locale-codes in hoofdletters (NL, EN).

### Migration
- `2026_04_24_100000_make_email_templates_translatable.php`: converteert `subject` en `from_name` naar `longText` en wikkelt bestaande data onder `config('app.locale')`.
- `2026_04_24_120000_relocate_email_template_locale_key.php`: repair-migratie die op installs met `app.locale != app.fallback_locale` de door de eerste migratie onder `app.fallback_locale` gezette data terugzet onder `app.locale` - alleen wanneer de `app.locale`-key nog leeg is.

## v4.0.137 - 2026-04-22

### Added

- `CustomStructuredData` model plus `HasStructuredData` trait for morph-many
  JSON-LD snippets per subject. Stored in the new `dashed__custom_structured_data` table.
- Frontend head-partial now renders any `seo()->metaData('customStructuredData')`
  array entries as `<script type="application/ld+json">` blocks.

### Changed

- `HasEditableCMSActions` trait now injects `RequestSeoAuditAction` (from dashed-marketing)
  instead of `RequestSeoImprovementAction`. The class_exists guard still keeps the
  trait usable without dashed-marketing.

### Coordinated with

- Requires dashed-marketing v4.12.0 in the same release cycle. Consumers using
  dashed-marketing must upgrade both packages together; the old `RequestSeoImprovementAction`
  is gone from marketing and this trait must point at the new one.

## 1.0.0 - 202X-XX-XX

- initial release
