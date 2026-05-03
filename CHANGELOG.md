# Changelog

All notable changes to `Dashed core` will be documented in this file.

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
