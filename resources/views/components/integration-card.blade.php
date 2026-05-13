@props([
    'definition',
    'health',
])

<div class="fi-section rounded-xl bg-white p-5 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 {{ $health->status->borderClass() }}">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span
                class="mt-1 inline-block size-2.5 rounded-full {{ $health->status->dotColor() }}"
                @if($health->message) title="{{ $health->message }}" @endif
            ></span>
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    @if($definition->icon)
                        <x-filament::icon :icon="$definition->icon" class="me-1 inline size-4 text-gray-500" />
                    @endif
                    {{ $definition->label }}
                </h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @if($health->lastSuccessAt)
                        Laatst geslaagd {{ $health->lastSuccessAt->diffForHumans() }}
                    @else
                        Nog niet getest
                    @endif
                </p>
                @if($health->message)
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $health->message }}</p>
                @endif
            </div>
        </div>

        <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $health->status->pillClasses() }}">
            {{ $health->status->label() }}
        </span>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        @if($definition->settingsPage && method_exists($definition->settingsPage, 'getUrl'))
            <x-filament::link
                :href="$definition->settingsPage::getUrl()"
                icon="heroicon-o-cog-6-tooth"
                size="sm"
            >
                Open instellingen
            </x-filament::link>
        @endif

        <x-filament::button
            wire:click="refreshIntegration('{{ $definition->slug }}')"
            icon="heroicon-o-arrow-path"
            color="gray"
            size="xs"
        >
            Opnieuw testen
        </x-filament::button>

        @if($definition->docsUrl)
            <x-filament::link
                :href="$definition->docsUrl"
                icon="heroicon-o-book-open"
                size="xs"
                target="_blank"
            >
                Documentatie
            </x-filament::link>
        @endif
    </div>
</div>
