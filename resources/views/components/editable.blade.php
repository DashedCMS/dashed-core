@props(['field', 'type' => 'text'])
@php
    $dashedEditor = app(\Dashed\DashedCore\Services\VisualEditor\VisualEditor::class);
    $dashedCtx = $dashedEditor->isActive()
        ? app(\Dashed\DashedCore\Services\VisualEditor\EditContextStack::class)->current()
        : null;
@endphp
@if ($dashedCtx)
    <span data-dashed-editable
          data-field="{{ $field }}"
          data-block="{{ $dashedCtx['block'] }}"
          data-model-type="{{ $dashedCtx['model_type'] }}"
          data-model-id="{{ $dashedCtx['model_id'] }}"
          data-locale="{{ $dashedCtx['locale'] }}"
          data-fieldtype="{{ $type }}">{{ $slot }}</span>
@else
{{ $slot }}@endif
