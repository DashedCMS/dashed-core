<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div class="mt-4">
            <x-filament::button type="submit">Opslaan</x-filament::button>
        </div>
    </form>

    {{-- Sein de parent (publieke pagina) dat er opgeslagen is, zodat de overlay sluit en herlaadt (Phase B). --}}
    <script>
        window.addEventListener('dashed-block-saved', function () {
            if (window.parent !== window) {
                window.parent.postMessage('dashed-block-saved', window.location.origin);
            }
        });
    </script>
</x-filament-panels::page>
