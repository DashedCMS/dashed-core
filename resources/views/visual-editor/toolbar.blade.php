<link rel="stylesheet" href="{{ asset('vendor/dashed-core/visual-editor.css') }}">
<div data-dashed-visual-editor-toolbar data-ve-save-url="{{ route('dashed.visual-editor.save') }}" class="dashed-ve-toolbar">
    @if ($active)
        <button type="button" data-ve-save>Opslaan</button>
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
        saveUrl: @json(route('dashed.visual-editor.save')),
        csrf: @json(csrf_token()),
        locale: @json(app()->getLocale()),
    };
</script>
<script src="{{ asset('vendor/dashed-core/visual-editor.js') }}" defer></script>
