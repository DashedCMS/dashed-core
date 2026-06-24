<x-filament-panels::page>
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Overzicht van ontbrekende content voor de actieve site. Klik een kaart om de items te zien.
        </p>
        <x-filament::button wire:click="rescan" color="gray" icon="heroicon-o-arrow-path">
            Opnieuw scannen
        </x-filament::button>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
        @foreach($this->cards as $key => $card)
            <button
                type="button"
                wire:click="selectCheck('{{ $key }}')"
                @class([
                    'fi-section rounded-xl bg-white p-4 text-left ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10',
                    'ring-2 ring-primary-500' => $selectedCheck === $key,
                    'opacity-50' => $card['count'] === 0,
                ])
            >
                <div @class([
                    'text-2xl font-bold',
                    'text-danger-600' => $card['count'] > 0,
                    'text-gray-400' => $card['count'] === 0,
                ])>{{ $card['count'] }}</div>
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</div>
            </button>
        @endforeach
    </div>

    @if($selectedCheck)
        <div class="fi-section rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @forelse($this->issues as $issue)
                <div
                    wire:key="issue-{{ $issue->checkKey }}-{{ $issue->mediaId ?? $issue->modelId }}"
                    class="flex items-center justify-between gap-3 border-b border-gray-100 p-3 last:border-0 dark:border-white/5"
                >
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $issue->title }}</div>
                        <div class="text-xs text-gray-500">{{ $issue->subtitle }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Inline/AI/bulk actions are wired in Tasks 8-10 --}}
                        @if($issue->editUrl)
                            <a href="{{ $issue->editUrl }}" class="text-sm text-primary-600 hover:underline">Bewerk</a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="p-4 text-sm text-gray-500">Geen problemen gevonden voor deze check.</p>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
