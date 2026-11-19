<div>
    @livewire('dashed.dashed-core.livewire.admin.record-history', [
        'modelClass' => $modelClass,
        'recordKey' => $recordKey,
        'returnUrl' => $returnUrl,
    ], key('record-history-' . md5($modelClass . $recordKey)))
</div>
