@php
    /** @var array|null $doc */
@endphp

<div class="space-y-6">
    @if ($doc === null)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Voor deze pagina is nog geen uitleg beschikbaar.
        </p>
    @else
        @if (! empty($doc['intro']))
            <div class="flex items-start gap-3 rounded-lg border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-950">
                <x-filament::icon
                    icon="heroicon-o-information-circle"
                    class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary-600 dark:text-primary-400"
                />
                <p class="text-sm leading-relaxed text-primary-900 dark:text-primary-100">
                    {{ $doc['intro'] }}
                </p>
            </div>
        @endif

        @if (! empty($doc['sections']))
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($doc['sections'] as $section)
                    <div class="space-y-2 py-4 first:pt-0 last:pb-0">
                        @if (! empty($section['heading']))
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                                {{ $section['heading'] }}
                            </h3>
                        @endif

                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            {!! $section['body'] !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! empty($doc['fields']))
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-950 dark:text-white">
                                Veld
                            </th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-950 dark:text-white">
                                Uitleg
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($doc['fields'] as $label => $explanation)
                            <tr>
                                <td class="w-1/3 px-4 py-2 align-top font-medium text-gray-950 dark:text-white">
                                    {{ $label }}
                                </td>
                                <td class="px-4 py-2 align-top text-gray-700 dark:text-gray-300">
                                    {{ $explanation }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (! empty($doc['tips']))
            <div class="rounded-lg border-l-4 border-warning-400 bg-warning-50 p-4 dark:bg-warning-950">
                <div class="flex items-start gap-3">
                    <x-filament::icon
                        icon="heroicon-o-light-bulb"
                        class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-600 dark:text-warning-400"
                    />
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-warning-900 dark:text-warning-100">
                            Tips
                        </p>
                        <ul class="list-inside list-disc space-y-1 text-sm text-warning-900 dark:text-warning-100">
                            @foreach ($doc['tips'] as $tip)
                                <li>{{ $tip }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
