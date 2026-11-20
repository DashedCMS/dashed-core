@php($queue = $this->getQueue())
<x-filament-widgets::widget>
    <div id="dashboard-priorities" tabindex="-1" style="height:100%;">
        <x-dashed-core::aurora.card
            :title="__('Heeft aandacht nodig')"
            icon="heroicon-o-clipboard-document-check"
            :badge="$queue['total'] > 0 ? __(':aantal openstaand', ['aantal' => $queue['total']]) : null"
            badge-tone="amber"
            :flush="true"
        >
            <x-dashed-core::aurora.priority-queue
                :items="$queue['items']"
                :empty="__('Niets dat nu aandacht nodig heeft.')"
            />
        </x-dashed-core::aurora.card>
    </div>
</x-filament-widgets::widget>
