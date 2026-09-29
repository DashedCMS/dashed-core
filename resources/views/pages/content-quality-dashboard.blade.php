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
        @if($this->aiAvailable($selectedCheck) && in_array('bulk_ai', $this->cards[$selectedCheck]['resolutions'] ?? []) && ($this->cards[$selectedCheck]['count'] ?? 0) > 0)
            <div class="mb-3">
                <x-filament::button wire:click="bulkAiFix" icon="heroicon-o-sparkles" wire:loading.attr="disabled">
                    Bulk: AI voor alle {{ $this->cards[$selectedCheck]['count'] }}
                </x-filament::button>
            </div>
        @endif
        @php($shown = $this->issues->count())
        @php($total = $this->cards[$selectedCheck]['count'] ?? $shown)
        @if($shown < $total)
            <p class="text-xs text-gray-500">Toont de eerste {{ $shown }} van {{ $total }}. Verfijn of los deze eerst op om de rest te zien.</p>
        @endif
        <div class="fi-section rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @forelse($this->issues as $issue)
                @php($issueArgs = "'" . $issue->checkKey . "', " . ($issue->mediaId ?: 'null') . ', ' . ($issue->modelClass ? "'" . addslashes($issue->modelClass) . "'" : 'null') . ', ' . ($issue->modelId ? "'" . $issue->modelId . "'" : 'null') . ', ' . ($issue->field ? "'" . $issue->field . "'" : 'null'))
                <div
                    wire:key="issue-{{ $issue->checkKey }}-{{ $issue->mediaId ?? $issue->modelId }}-{{ $issue->field }}"
                    class="flex items-center justify-between gap-3 border-b border-gray-100 p-3 last:border-0 dark:border-white/5"
                >
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $issue->title }}</div>
                        <div class="text-xs text-gray-500">{{ $issue->subtitle }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(in_array('inline', $this->cards[$selectedCheck]['resolutions'] ?? []))
                            <button type="button" class="text-sm text-primary-600 hover:underline"
                                wire:click="editInline({{ $issueArgs }})">
                                Inline
                            </button>
                        @endif
                        @if($this->aiAvailable($selectedCheck) && in_array('ai', $this->cards[$selectedCheck]['resolutions'] ?? []))
                            <button type="button" class="text-sm font-medium text-primary-600 hover:underline"
                                wire:click="aiFix({{ $issueArgs }})"
                                wire:loading.attr="disabled">
                                Fix met AI
                            </button>
                        @endif
                        @if($issue->editUrl)
                            <a href="{{ $issue->editUrl }}" class="text-sm text-primary-600 hover:underline">Bewerk</a>
                        @endif
                    </div>
                </div>
                @if($inlineTarget && ($inlineTarget['mediaId'] ?? null) == ($issue->mediaId ?? null) && (string) ($inlineTarget['modelId'] ?? '') === (string) ($issue->modelId ?? '') && ($inlineTarget['checkKey'] ?? '') === $issue->checkKey && ($inlineTarget['field'] ?? null) === $issue->field)
                    @php($limit = \Dashed\DashedCore\ContentQuality\Checks\MetaLengthCheck::LIMITS[$issue->field] ?? null)
                    <div class="bg-gray-50 p-3 dark:bg-white/5" wire:key="inline-{{ $issue->checkKey }}-{{ $issue->mediaId ?? $issue->modelId }}-{{ $issue->field }}">
                        @foreach($inlineValues as $localeKey => $val)
                            <div x-data="{ value: @entangle('inlineValues.' . $localeKey), limit: {{ $limit ?? 'null' }} }">
                                <div class="mb-1 flex items-center justify-between text-xs uppercase text-gray-500">
                                    <label>{{ strtoupper($localeKey) }}</label>
                                    <template x-if="limit">
                                        <span :class="(value ?? '').length > limit ? 'text-danger-600 font-semibold' : 'text-gray-500'"
                                              x-text="(value ?? '').length + ' / ' + limit"></span>
                                    </template>
                                </div>
                                @if($issue->field === 'description')
                                    <textarea x-model="value" rows="3"
                                        class="mb-2 w-full rounded border-gray-300 text-sm dark:bg-gray-800"></textarea>
                                @else
                                    <input type="text" x-model="value"
                                        class="mb-2 w-full rounded border-gray-300 text-sm dark:bg-gray-800" />
                                @endif
                            </div>
                        @endforeach
                        <div class="flex gap-2">
                            <x-filament::button size="sm" wire:click="saveInline">Opslaan</x-filament::button>
                            <x-filament::button size="sm" color="gray" wire:click="saveInline(true)" icon="heroicon-o-arrow-right" icon-position="after">
                                Opslaan en volgende
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            @empty
                <p class="p-4 text-sm text-gray-500">Geen problemen gevonden voor deze check.</p>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
