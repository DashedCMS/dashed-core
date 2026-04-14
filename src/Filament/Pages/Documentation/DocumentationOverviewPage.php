<?php

namespace Dashed\DashedCore\Filament\Pages\Documentation;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Collection;
use Dashed\DashedCore\Services\DocsRegistry;

class DocumentationOverviewPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Documentatie';

    protected static ?string $title = 'Documentatie';

    protected static string | UnitEnum | null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 100001;

    protected string $view = 'dashed-core::docs.overview';

    public string $search = '';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    /**
     * Livewire computed property: $this->groupedDocs
     *
     * Returns docs grouped by module name, e.g.:
     * [
     *   'Dashed Core'  => Collection<doc>,
     *   'Dashed Shop'  => Collection<doc>,
     *   'Algemeen'     => Collection<doc>,    // for string-key topics
     * ]
     */
    public function getGroupedDocsProperty(): Collection
    {
        $registry = app(DocsRegistry::class);
        $all = $registry->all();
        $search = Str::lower(trim($this->search));

        $entries = collect();

        foreach ($all['resources'] as $key => $doc) {
            $entries->push($this->normalize('resource', $key, $doc));
        }
        foreach ($all['settingsPages'] as $key => $doc) {
            $entries->push($this->normalize('settings', $key, $doc));
        }
        foreach ($all['topics'] as $key => $doc) {
            $entries->push($this->normalize('topic', $key, $doc));
        }

        if ($search !== '') {
            $entries = $entries->filter(function (array $entry) use ($search): bool {
                $haystack = Str::lower(implode(' ', [
                    $entry['title'] ?? '',
                    $entry['intro'] ?? '',
                    $entry['module'] ?? '',
                    $entry['key'] ?? '',
                ]));

                return Str::contains($haystack, $search);
            });
        }

        return $entries
            ->sortBy(fn (array $entry): string => $entry['module'].'|'.($entry['title'] ?? ''))
            ->groupBy('module');
    }

    protected function normalize(string $type, string $key, array $doc): array
    {
        $sections = [];
        foreach ($doc['sections'] ?? [] as $section) {
            $heading = $section['heading'] ?? '';
            $body = $section['body'] ?? '';

            $sections[] = [
                'heading' => $heading,
                'body' => $body !== '' ? new HtmlString(Str::markdown($body)) : new HtmlString(''),
            ];
        }

        return [
            'type' => $type,
            'key' => $key,
            'module' => $this->resolveModule($key),
            'title' => $doc['title'] ?? $key,
            'intro' => $doc['intro'] ?? null,
            'sections' => $sections,
            'tips' => $doc['tips'] ?? [],
            'fields' => $doc['fields'] ?? [],
        ];
    }

    protected function resolveModule(string $key): string
    {
        if (! str_contains($key, '\\')) {
            return 'Algemeen';
        }

        $segments = explode('\\', $key);

        // Class names are typically Vendor\Package\... — take the package segment.
        $package = $segments[1] ?? $segments[0];

        // Humanise: "DashedCore" → "Dashed Core"
        return trim((string) preg_replace('/(?<!^)([A-Z])/', ' $1', $package));
    }
}
