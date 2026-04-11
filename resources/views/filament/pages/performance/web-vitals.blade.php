<x-filament-panels::page>
    <div class="flex gap-2 mb-4">
        <button wire:click="$set('days', 7)" @class(['px-3 py-1 rounded', 'bg-primary-600 text-white' => $days === 7, 'bg-gray-200' => $days !== 7])>7 dagen</button>
        <button wire:click="$set('days', 30)" @class(['px-3 py-1 rounded', 'bg-primary-600 text-white' => $days === 30, 'bg-gray-200' => $days !== 30])>30 dagen</button>
        <button wire:click="$set('days', 90)" @class(['px-3 py-1 rounded', 'bg-primary-600 text-white' => $days === 90, 'bg-gray-200' => $days !== 90])>90 dagen</button>
    </div>

    @php $rows = $this->getRows(); @endphp

    @forelse($rows as $metric => $metricRows)
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-2">{{ $metric }}</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">URL pattern</th>
                        <th class="text-left py-2">Device</th>
                        <th class="text-right py-2">p75</th>
                        <th class="text-right py-2">Samples</th>
                        <th class="text-right py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metricRows as $row)
                        @php
                            $rating = $this->ratingFor($metric, (float) $row->p75);
                            $color = match($rating) {
                                'good' => 'bg-green-200 text-green-900',
                                'needs-improvement' => 'bg-amber-200 text-amber-900',
                                'poor' => 'bg-red-200 text-red-900',
                                default => 'bg-gray-200',
                            };
                        @endphp
                        <tr class="border-b">
                            <td class="py-1">{{ $row->url_pattern }}</td>
                            <td class="py-1">{{ $row->device }}</td>
                            <td class="py-1 text-right font-mono">{{ number_format($row->p75, $metric === 'CLS' ? 3 : 0) }}</td>
                            <td class="py-1 text-right">{{ $row->sample_count }}</td>
                            <td class="py-1 text-right">
                                <span class="px-2 py-0.5 rounded text-xs {{ $color }}">{{ $rating }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="p-8 text-center text-gray-500">
            Nog geen data. Web Vitals worden opgebouwd terwijl bezoekers de site gebruiken. Check morgenochtend — de rollup draait 's nachts.
        </div>
    @endforelse
</x-filament-panels::page>
