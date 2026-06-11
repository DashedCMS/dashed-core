<link rel="stylesheet" href="{{ asset('vendor/dashed-core/visual-editor.css') }}">
<div data-dashed-visual-editor-toolbar class="dashed-ve-toolbar">
    @if ($active)
        <span class="dashed-ve-hint">Klik een blok om te bewerken</span>
        <form method="POST" action="{{ route('dashed.visual-editor.toggle') }}" style="display:inline">
            @csrf
            <input type="hidden" name="enable" value="0">
            <button type="submit">Klaar</button>
        </form>
    @else
        <form method="POST" action="{{ route('dashed.visual-editor.toggle') }}" style="display:inline">
            @csrf
            <input type="hidden" name="enable" value="1">
            <button type="submit">Bewerk pagina</button>
        </form>
    @endif
</div>
<script>
    window.dashedVisualEditor = {
        active: @json($active),
        blockEditorUrl: @json(route('filament.dashed.pages.visual-editor.block')),
        locale: @json(app()->getLocale()),
    };
</script>
<script src="{{ asset('vendor/dashed-core/visual-editor.js') }}" defer></script>
