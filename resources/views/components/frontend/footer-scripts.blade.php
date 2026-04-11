@php
    $store = app(\Dashed\DashedCore\Performance\Scripts\DeferredScriptStore::class);
@endphp

{!! $store->render() !!}

@stack('defer-scripts')
