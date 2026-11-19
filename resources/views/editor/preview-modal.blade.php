{{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
<style>
    .dsh-preview__bar { display: flex; gap: .5rem; justify-content: center; margin-bottom: .75rem; }
    .dsh-preview__btn { padding: .35rem .85rem; border-radius: .5rem; border: 1px solid rgb(127 127 127 / .3); background: transparent; color: inherit; cursor: pointer; font-size: .875rem; }
    .dsh-preview__btn[aria-pressed="true"] { background: rgb(37 99 235); border-color: rgb(37 99 235); color: #fff; }
    .dsh-preview__stage { display: flex; justify-content: center; background: rgb(127 127 127 / .08); border-radius: .75rem; padding: 1rem; overflow: auto; }
    .dsh-preview__frame { border: 1px solid rgb(127 127 127 / .3); border-radius: .5rem; background: #fff; height: 75vh; transition: width .2s ease; max-width: 100%; }
</style>

<div x-data="{ device: 'desktop', widths: { phone: '390px', tablet: '820px', desktop: '100%' } }" data-editor-preview>
    <div class="dsh-preview__bar" role="group" aria-label="{{ __('Schermformaat') }}">
        <button type="button" class="dsh-preview__btn" x-on:click="device = 'phone'" x-bind:aria-pressed="device === 'phone'">{{ __('Telefoon') }}</button>
        <button type="button" class="dsh-preview__btn" x-on:click="device = 'tablet'" x-bind:aria-pressed="device === 'tablet'">{{ __('Tablet') }}</button>
        <button type="button" class="dsh-preview__btn" x-on:click="device = 'desktop'" x-bind:aria-pressed="device === 'desktop'">{{ __('Desktop') }}</button>
        <a class="dsh-preview__btn" href="{{ $url }}" target="_blank" rel="noopener">{{ __('Open in nieuw tabblad') }}</a>
    </div>
    <div class="dsh-preview__stage">
        <iframe class="dsh-preview__frame" src="{{ $url }}" x-bind:style="'width: ' + widths[device]" title="{{ __('Voorvertoning') }}"></iframe>
    </div>
</div>
