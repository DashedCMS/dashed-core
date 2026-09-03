<x-filament-widgets::widget>
    @php($cols = ['1' => 'md:col-span-1', '2' => 'md:col-span-2', '3' => 'md:col-span-3', '4' => 'md:col-span-4', 'full' => 'md:col-span-4'])
    <div
        x-data="dashedDashboardGrid({ statePath: 'orderedIds' })"
        wire:key="dashboard-grid-{{ $editing ? 'edit' : 'view' }}"
    >
        @if ($this->canEdit())
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-bottom:12px;">
                @if ($editing)
                    <x-filament::button size="sm" color="gray" wire:click="resetLayout" wire:confirm="Dashboard terugzetten naar standaard?">Standaard herstellen</x-filament::button>
                    <x-filament::button size="sm" wire:click="toggleEdit">Klaar</x-filament::button>
                @else
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="toggleEdit">Bewerken</x-filament::button>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6" x-ref="grid">
            @foreach ($items as $item)
                @if ($editing || $item['visible'])
                    <div class="{{ $cols[(string) $item['width']] ?? 'md:col-span-4' }}"
                         data-id="{{ $item['id'] }}"
                         @style(['opacity: .45' => $editing && ! $item['visible']])>
                        @if ($editing)
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; font-size:12px; color:#6b7280;">
                                <span class="dashed-grid__handle" style="cursor:grab;">&#9776;</span>
                                <strong style="flex:1;">{{ $item['label'] }}</strong>
                                <select wire:change="setWidth('{{ $item['id'] }}', $event.target.value)" style="font-size:12px;">
                                    @foreach (['1' => '1', '2' => '2', '3' => '3', '4' => '4', 'full' => 'Vol'] as $val => $lab)
                                        <option value="{{ $val }}" @selected((string) $item['width'] === $val)>{{ $lab }}</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="toggleWidget('{{ $item['id'] }}')" title="Tonen/verbergen">
                                    {{ $item['visible'] ? '👁' : '🚫' }}
                                </button>
                            </div>
                        @endif

                        @if ($item['visible'] || $editing)
                            {!! rescue(fn () => app('livewire')->mount($item['class'], \Dashed\DashedCore\Filament\Widgets\DashboardGrid::mountParamsFor($item['class']), $item['id']), '<div style="color:#b91c1c;font-size:12px;">Widget kon niet geladen worden.</div>', false) !!}
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
