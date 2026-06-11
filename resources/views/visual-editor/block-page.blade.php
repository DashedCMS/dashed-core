<x-filament-panels::page>
    <script>
        if (window.self !== window.top) {
            document.documentElement.classList.add('dashed-ve-framed');
        }
    </script>
    <style>
        .dashed-ve-framed .fi-topbar,
        .dashed-ve-framed .fi-sidebar { display: none !important; }
        .dashed-ve-framed .fi-main-ctn { padding: 0 !important; }
        .dashed-ve-framed .fi-main { max-width: none !important; padding: 1.5rem !important; }
    </style>

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
