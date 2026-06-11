@php
    // Cache-bust op de bestands-mtime: zo laadt de browser na een asset-update
    // (bijv. na een refactor) gegarandeerd de nieuwe JS/CSS i.p.v. een gecachete
    // oude versie die naar een verwijderde endpoint verwijst.
    $veCssPath = public_path('vendor/dashed-core/visual-editor.css');
    $veJsPath = public_path('vendor/dashed-core/visual-editor.js');
    $veCssHref = asset('vendor/dashed-core/visual-editor.css') . (is_file($veCssPath) ? '?v=' . filemtime($veCssPath) : '');
    $veJsSrc = asset('vendor/dashed-core/visual-editor.js') . (is_file($veJsPath) ? '?v=' . filemtime($veJsPath) : '');
@endphp
<link rel="stylesheet" href="{{ $veCssHref }}">
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
<script src="{{ $veJsSrc }}" defer></script>
