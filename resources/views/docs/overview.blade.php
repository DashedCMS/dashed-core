<x-filament::page>

    <div class="space-y-6">
        <div class="flex items-start gap-3 rounded-lg border border-warning-200 bg-warning-50 p-4 dark:border-warning-800 dark:bg-warning-950">
            <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-600 dark:text-warning-400" />
            <p class="text-sm text-warning-900 dark:text-warning-100">
                <strong>Let op:</strong> deze documentatie is gegenereerd met AI en kan fouten bevatten. Controleer belangrijke stappen altijd in de praktijk voordat je er op vertrouwt.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-gray-500 text-xs font-medium uppercase tracking-wide">Documentatie</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Alle uitleg per module. Zoek of blader.
                </p>
            </div>

            <div class="w-full sm:max-w-sm">
                <label for="docs-search" class="sr-only">Zoeken</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                    </div>

                    <input
                        id="docs-search"
                        type="text"
                        wire:model.live.debounce.200ms="search"
                        placeholder="Zoek in documentatie…"
                        class="block w-full rounded-lg border-gray-300 pl-10 pr-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-4 bg-white border-2 text-gray-600"
                    />

                    @if(strlen($search ?? '') > 0)
                        <button
                            type="button"
                            wire:click="$set('search','')"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                            <span class="sr-only">Wis</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @php
            $groupedDocs = $this->groupedDocs;
        @endphp

        @if($groupedDocs->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600 dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
                @if(strlen($search ?? '') > 0)
                    Geen resultaten voor: <span class="font-medium text-gray-900 dark:text-white">{{ $search }}</span>
                @else
                    Er is nog geen documentatie geregistreerd. Packages kunnen docs registreren via
                    <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">cms()->registerResourceDocs(...)</code>.
                @endif
            </div>
        @else
            @foreach($groupedDocs as $module => $docs)
                <section class="space-y-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $module }}
                    </h3>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        @foreach($docs as $doc)
                            <details class="group rounded-lg border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                                <summary class="flex cursor-pointer items-start gap-3 p-4 list-none [&::-webkit-details-marker]:hidden">
                                    <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-md bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                                        @if($doc['type'] === 'resource')
                                            <x-heroicon-o-rectangle-stack class="h-5 w-5" />
                                        @elseif($doc['type'] === 'settings')
                                            <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
                                        @else
                                            <x-heroicon-o-book-open class="h-5 w-5" />
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                                            {{ $doc['title'] }}
                                        </h4>
                                        @if(! empty($doc['intro']))
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $doc['intro'] }}
                                            </p>
                                        @endif
                                    </div>

                                    <x-heroicon-o-chevron-down class="mt-1 h-5 w-5 flex-shrink-0 text-gray-400 transition-transform group-open:rotate-180" />
                                </summary>

                                <div class="space-y-4 border-t border-gray-200 px-4 py-4 dark:border-white/10">
                                    @if(! empty($doc['sections']))
                                        <div class="divide-y divide-gray-200 dark:divide-white/10">
                                            @foreach($doc['sections'] as $section)
                                                <div class="space-y-2 py-3 first:pt-0 last:pb-0">
                                                    @if(! empty($section['heading']))
                                                        <h5 class="text-sm font-semibold text-gray-950 dark:text-white">
                                                            {{ $section['heading'] }}
                                                        </h5>
                                                    @endif
                                                    <div class="prose prose-sm max-w-none dark:prose-invert">
                                                        {!! $section['body'] !!}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(! empty($doc['fields']))
                                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-50 dark:bg-white/5">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white">Veld</th>
                                                        <th class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white">Uitleg</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                                    @foreach($doc['fields'] as $label => $explanation)
                                                        <tr>
                                                            <td class="w-1/3 px-3 py-2 align-top font-medium text-gray-950 dark:text-white">{{ $label }}</td>
                                                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">{{ $explanation }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    @if(! empty($doc['tips']))
                                        <div class="rounded-lg border-l-4 border-warning-400 bg-warning-50 p-3 dark:bg-warning-950">
                                            <div class="flex items-start gap-2">
                                                <x-heroicon-o-light-bulb class="mt-0.5 h-4 w-4 flex-shrink-0 text-warning-600 dark:text-warning-400" />
                                                <ul class="list-inside list-disc space-y-1 text-xs text-warning-900 dark:text-warning-100">
                                                    @foreach($doc['tips'] as $tip)
                                                        <li>{{ $tip }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif

                                    @if(empty($doc['sections']) && empty($doc['fields']) && empty($doc['tips']))
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Geen aanvullende details geregistreerd.
                                        </p>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>

</x-filament::page>
