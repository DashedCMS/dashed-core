{{-- Volledigheid van het record bovenaan de bewerkpagina (zie Completeness). Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
<style>
    .dsh-complete { margin-bottom: 1rem; padding: .75rem 1rem; border-radius: .75rem; border: 1px solid rgb(0 0 0 / .08); background: #fff; font-size: .875rem; }
    .dark .dsh-complete { background: rgb(24 24 27); border-color: rgb(255 255 255 / .1); }
    .dsh-complete__row { display: flex; align-items: center; gap: .75rem; }
    .dsh-complete__bar { flex: 1; height: 8px; border-radius: 4px; background: rgb(127 127 127 / .18); overflow: hidden; }
    .dsh-complete__bar span { display: block; height: 100%; border-radius: 4px; }
    .dsh-complete--success .dsh-complete__bar span { background: #16a34a; }
    .dsh-complete--warning .dsh-complete__bar span { background: #d97706; }
    .dsh-complete--danger .dsh-complete__bar span { background: #dc2626; }
    .dsh-complete__label { font-weight: 600; white-space: nowrap; }
    .dsh-complete__missing { margin-top: .4rem; opacity: .75; }
    .dsh-complete__blocking { color: #b91c1c; font-weight: 600; }
    .dark .dsh-complete__blocking { color: #f87171; }
</style>
<div class="dsh-complete dsh-complete--{{ $result->color() }}" data-completeness="{{ $result->percentage }}">
    <div class="dsh-complete__row">
        <span class="dsh-complete__label">{{ __(':procent% compleet', ['procent' => $result->percentage]) }}</span>
        <div class="dsh-complete__bar"><span style="width: {{ $result->percentage }}%"></span></div>
    </div>
    @if ($result->missing)
        <div class="dsh-complete__missing">
            {{ __('Ontbreekt') }}:
            @foreach ($result->missing as $key => $label)
                <span @class(['dsh-complete__blocking' => isset($result->blocking[$key])])>{{ $label }}</span>@if (! $loop->last), @endif
            @endforeach
        </div>
    @endif
</div>
