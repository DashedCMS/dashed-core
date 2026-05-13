@livewire('dashed.dashed-core.livewire.admin.editing-presence-banner', [
    'resourceKey' => $resourceKey,
    'recordKey' => $recordKey,
], key('edit-presence-' . sha1($resourceKey . ':' . $recordKey)))
