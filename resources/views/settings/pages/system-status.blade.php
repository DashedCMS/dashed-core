<x-filament::page>
    @php
        $items = $this->items();
        $styles = [
            'ok' => ['label' => __('In orde'), 'badge' => 'success'],
            'warning' => ['label' => __('Let op'), 'badge' => 'warning'],
            'danger' => ['label' => __('Probleem'), 'badge' => 'danger'],
        ];
    @endphp

    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Wat er op deze installatie stil kan falen: de scheduler, de wachtrij, migraties die na een update zijn blijven liggen, en instellingen die op productie nog op test staan. Superadmins zien een probleem hier ook als balk bovenin elk scherm.') }}
        </p>

        <x-filament::section>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($items as $item)
                    <div class="flex items-start gap-4 py-3" data-check="{{ $item['key'] }}">
                        <div class="w-24 shrink-0 pt-0.5">
                            <x-filament::badge :color="$styles[$item['status']]['badge'] ?? 'gray'">
                                {{ $styles[$item['status']]['label'] ?? $item['status'] }}
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

        <x-filament::button color="gray" size="sm" wire:click="recheckMigrations">
            {{ __('Migraties opnieuw controleren') }}
        </x-filament::button>
    </div>
</x-filament::page>
