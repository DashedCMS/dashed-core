<x-filament-panels::page>
    @php
        $items = $this->items();
    @endphp

    <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Wat er verwijderd is, staat hier en kan terug. Pagina\'s, artikelen, menu\'s, globale blokken en doorverwijzingen gaan na :dagen dagen definitief weg; producten, kortingscodes, formulieren en popups blijven staan tot je ze hier zelf weggooit, omdat bestellingen, inzendingen en vertoningen ernaar verwijzen.', ['dagen' => \Dashed\DashedCore\Classes\Trash\Trash::PURGE_DAYS]) }}
        </p>

        <div class="max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="type">
                    <option value="">{{ __('Alle soorten') }}</option>
                    @foreach ($this->typeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <x-filament::section>
            @if ($items->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('De prullenbak is leeg.') }}</p>
            @else
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($items as $item)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3" data-trash-item="{{ class_basename($item['type']) }}:{{ $item['id'] }}" wire:key="trash-{{ md5($item['type']) }}-{{ $item['id'] }}">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $item['title'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item['label'] }} &middot; {{ __('verwijderd :tijd', ['tijd' => $item['deleted_at']?->diffForHumans()]) }}
                                    @if ($item['purge'] && $item['deleted_at'])
                                        &middot; {{ __('gaat :datum definitief weg', ['datum' => $item['deleted_at']->copy()->addDays(\Dashed\DashedCore\Classes\Trash\Trash::PURGE_DAYS)->translatedFormat('j F')]) }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-uturn-left" wire:click="restore(@js($item['type']), '{{ $item['id'] }}')">
                                    {{ __('Terugzetten') }}
                                </x-filament::button>
                                <x-filament::button size="sm" color="danger" icon="heroicon-o-trash" wire:click="forceDelete(@js($item['type']), '{{ $item['id'] }}')" wire:confirm="{{ __('Definitief verwijderen? Dit kan niet ongedaan worden.') }}">
                                    {{ __('Definitief verwijderen') }}
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
