<x-filament::page>
    <form wire:submit.prevent="submit" method="POST">
        {{ $this->form }}
        <button type="submit" class="mt-4 py-2 px-4 text-center bg-primary-600 rounded-md text-white text-sm hover:bg-primary-500">
            Vertalingen opslaan
        </button>
    </form>
</x-filament::page>
