# Admin overview upgrade

The admin overview gained three building blocks in Bundle 3: an Integrations dashboard, a reusable "last edited" table column for any resource backed by activity-log, and stat widgets that drill down into a pre-filtered Filament list page. This doc covers the public API for each.

## Registering an integration

Provider packages declare themselves on the IntegrationsDashboard from their `bootingPackage()`:

```php
cms()->registerIntegration([
    'slug'          => 'mollie',                  // unique per integration
    'label'         => 'Mollie',
    'icon'          => 'heroicon-o-credit-card',
    'category'      => 'payment',                 // payment|shipping|marketplace|accounting|review|other
    'settings_page' => MollieSettingsPage::class, // optional, used for "Configure" link + permission
    'health_check'  => [Mollie::class, 'healthCheck'],
    'docs_url'      => 'https://...',             // optional
    'package'       => 'dashed-ecommerce-mollie', // optional
]);
```

Internally `cms()->registerIntegration()` builds an `IntegrationDefinition` and stores it on the `IntegrationRegistry`. If `permission` is omitted but a `settings_page` is given, the permission is derived from the settings page class.

### Returning a health probe

The `health_check` callable receives an optional `?string $siteId` and must return an `IntegrationHealth`. Four factories cover the standard states:

```php
use Dashed\DashedCore\Integrations\IntegrationHealth;

IntegrationHealth::ok($lastSuccessAt);          // green — last success timestamp is optional
IntegrationHealth::disabled();                  // grey — package present but switched off
IntegrationHealth::misconfigured('API key ontbreekt');
IntegrationHealth::failing('HTTP 500 from Mollie', $lastSuccessAt);
```

For the common "all I need is a couple of Customsetting keys" case there is a one-liner:

```php
'health_check' => fn (?string $siteId) =>
    IntegrationHealth::fromSettings(['mollie_api_key'], $siteId),
```

It returns `misconfigured()` if any key is empty, `ok()` otherwise. Cheap — it does not call the external API.

## HasLastEditedColumn trait

`Dashed\DashedCore\Filament\Concerns\HasLastEditedColumn` adds a togglable "Laatst bewerkt" column to any Filament resource whose model logs activity via `spatie/laravel-activitylog`.

The model needs:

- the `LogsActivity` trait;
- a `latestActivity` `MorphOne` relation to `Activity::class` (ordered by `id desc` so the latest log row is the one loaded).

Example wiring on the resource:

```php
use Dashed\DashedCore\Filament\Concerns\HasLastEditedColumn;

class OrderResource extends Resource
{
    use HasLastEditedColumn;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // … existing columns …
                static::lastEditedColumn(),
            ])
            ->modifyQueryUsing(fn (Builder $q) => static::modifyTableQueryForLastEdited($q));
    }
}
```

`lastEditedColumn()` returns a `LastEditedColumn` (toggleable, default hidden); `modifyTableQueryForLastEdited()` eager-loads `latestActivity` and `latestActivity.causer` so the column doesn't fire an N+1 per row.

## ResourceFilterUrl

`Dashed\DashedCore\Filament\Support\ResourceFilterUrl::for($resource, $filters)` builds a Filament list-page URL with pre-applied table filters. Use it on stat widgets so a click drills the operator straight into the matching list:

```php
use Dashed\DashedCore\Filament\Support\ResourceFilterUrl;
use Dashed\DashedEcommerceCore\Filament\Resources\OrderResource;

Stat::make('Unhandled orders', $count)
    ->url(ResourceFilterUrl::for(OrderResource::class, [
        'status' => 'unhandled',
    ]));
```

Filters are coerced into Filament's standard `tableFilters[name][value]=…` query-string shape. Pass an array value (`['values' => [...]]`) to forward a multi-select filter as `tableFilters[name][values][]=…`. The third argument `$page` defaults to `'index'` if the resource uses a non-standard list route.
