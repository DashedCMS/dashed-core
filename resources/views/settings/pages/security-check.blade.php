<x-filament::page>
    @php
        $items = $this->items();
        $counts = \Dashed\DashedCore\Security\SecurityCheck::counts($items);
        $styles = [
            'ok' => ['label' => __('In orde'), 'badge' => 'success'],
            'warning' => ['label' => __('Let op'), 'badge' => 'warning'],
            'danger' => ['label' => __('Probleem'), 'badge' => 'danger'],
            'info' => ['label' => __('Ter info'), 'badge' => 'gray'],
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap gap-3">
            @foreach (['danger', 'warning', 'ok', 'info'] as $status)
                <x-filament::badge :color="$styles[$status]['badge']" size="lg">
                    {{ $counts[$status] ?? 0 }} {{ $styles[$status]['label'] }}
                </x-filament::badge>
            @endforeach
        </div>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Wat er aan beveiliging aan of uit staat op deze installatie, gelezen op het moment dat je deze pagina opent. Een probleem of aandachtspunt hier is meestal een instelling op de server (.env) of bij Instellingen, Beveiliging.') }}
        </p>

        <x-filament::section>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($items as $item)
                    <div class="flex items-start gap-4 py-3" data-check="{{ $item['key'] }}">
                        <div class="w-24 shrink-0 pt-0.5">
                            <x-filament::badge :color="$styles[$item['status']]['badge']">
                                {{ $styles[$item['status']]['label'] }}
                            </x-filament::badge>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $item['label'] }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 break-words">{{ $item['detail'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
