<x-filament-panels::page>
    @php($summary = $this->summary)

    {{-- Datumnavigatie --}}
    <div class="flex items-center justify-between gap-3 mb-4">
        <x-filament::button color="gray" icon="heroicon-m-chevron-left" wire:click="previousDay">
            Vorige dag
        </x-filament::button>

        <div class="text-base font-semibold text-gray-950 dark:text-white">
            {{ $summary['label'] }}
        </div>

        <x-filament::button color="gray" icon="heroicon-m-chevron-right" icon-position="after"
            wire:click="nextDay" :disabled="$this->isToday">
            Volgende dag
        </x-filament::button>
    </div>

    @if (empty($summary['sections']))
        <x-filament::section>
            <div class="flex flex-col items-center justify-center gap-2 py-10 text-center">
                <x-filament::icon icon="heroicon-o-calendar" class="h-8 w-8 text-gray-400" />
                <p class="text-sm font-medium text-gray-950 dark:text-white">Geen activiteit op deze dag</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Er is voor deze dag niets te rapporteren.</p>
            </div>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($summary['sections'] as $section)
                <x-filament::section :heading="$section['title']">
                    <div class="space-y-3">
                        @foreach ($section['blocks'] as $block)
                            @php($data = $block['data'] ?? [])

                            @if ($block['type'] === 'stats')
                                <dl class="divide-y divide-gray-100 dark:divide-white/10">
                                    @foreach (($data['rows'] ?? []) as $row)
                                        <div class="flex items-center justify-between gap-4 py-1.5">
                                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $row['label'] ?? '' }}</dt>
                                            <dd class="text-sm font-semibold text-gray-950 dark:text-white">{{ $row['value'] ?? '' }}</dd>
                                        </div>
                                    @endforeach
                                </dl>

                            @elseif ($block['type'] === 'table')
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        @if (! empty($data['headers']))
                                            <thead>
                                                <tr class="border-b border-gray-200 dark:border-white/10">
                                                    @foreach ($data['headers'] as $header)
                                                        <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $header }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                        @endif
                                        <tbody>
                                            @foreach (($data['rows'] ?? []) as $row)
                                                <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                                    @foreach ($row as $cell)
                                                        <td class="px-2 py-1.5 text-gray-950 dark:text-white">{{ $cell }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            @elseif ($block['type'] === 'heading')
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ strip_tags($data['content'] ?? '') }}</h3>

                            @elseif ($block['type'] === 'paragraph')
                                <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300 dark:prose-invert">
                                    {!! $data['content'] ?? '' !!}
                                </div>

                            @elseif ($block['type'] === 'text')
                                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $data['content'] ?? '' }}</p>
                            @endif
                        @endforeach
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
