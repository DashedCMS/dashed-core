@props(['show' => true])
@if($show)
    <div class="w-full max-w-7xl mx-auto px-4">
        {{ $slot }}
    </div>
@else
    {{ $slot }}
@endif
