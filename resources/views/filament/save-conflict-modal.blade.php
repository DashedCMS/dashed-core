{{-- Opslagconflict (zie SaveConflictGuard): eigen stijlblok, want niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
<style>
    .dsc-backdrop { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(17, 24, 39, .55); }
    .dsc-modal { width: 100%; max-width: 32rem; border-radius: .75rem; background: #fff; color: #111827; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,.2); }
    .dark .dsc-modal { background: #18181b; color: #f4f4f5; }
    .dsc-modal h2 { margin: 0 0 .5rem; font-size: 1.125rem; font-weight: 600; }
    .dsc-modal p { margin: 0 0 .75rem; font-size: .875rem; line-height: 1.45; }
    .dsc-modal ul { margin: 0 0 1rem; padding-left: 1.25rem; list-style: disc; font-size: .875rem; }
    .dsc-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; }
    .dsc-btn { border: 0; border-radius: .5rem; padding: .5rem .875rem; font-size: .875rem; font-weight: 600; cursor: pointer; }
    .dsc-btn--primary { background: #2563eb; color: #fff; }
    .dsc-btn--danger { background: #dc2626; color: #fff; }
    .dsc-btn--ghost { background: transparent; color: inherit; }
</style>
<div
    x-data="{ open: false, id: null, by: null, at: null, fields: [] }"
    x-on:dashed-save-conflict.window="open = true; id = $event.detail.id; by = $event.detail.by; at = $event.detail.at; fields = $event.detail.fields || []"
    x-on:keydown.escape.window="open = false"
    data-save-conflict-modal
>
    <template x-if="open">
        <div class="dsc-backdrop" role="dialog" aria-modal="true">
            <div class="dsc-modal">
                <h2>{{ __('Iemand anders heeft dit intussen opgeslagen') }}</h2>
                <p>
                    <span x-show="by" x-text="by"></span>
                    <span x-show="! by">{{ __('Iemand anders') }}</span>
                    <span>{{ __('heeft dit record opgeslagen nadat jij het opende') }}</span><span x-show="at"> (<span x-text="at"></span>)</span>.
                    {{ __('Jouw wijzigingen zijn nog niet opgeslagen.') }}
                </p>
                <template x-if="fields.length">
                    <div>
                        <p>{{ __('Bij de ander gewijzigd:') }}</p>
                        <ul><template x-for="field in fields"><li x-text="field"></li></template></ul>
                    </div>
                </template>
                <p>{{ __('Herladen toont hun versie, maar jouw wijzigingen gaan dan verloren. Overschrijven slaat jouw versie op en haalt hun wijzigingen weg.') }}</p>
                <div class="dsc-actions">
                    <button type="button" class="dsc-btn dsc-btn--ghost" x-on:click="open = false">{{ __('Annuleren') }}</button>
                    <button type="button" class="dsc-btn dsc-btn--primary" x-on:click="window.location.reload()">{{ __('Herladen') }}</button>
                    <button type="button" class="dsc-btn dsc-btn--danger" x-on:click="open = false; Livewire.find(id)?.call('{{ \Dashed\DashedCore\Livewire\Hooks\SaveConflictGuard::FORCE_METHOD }}')">{{ __('Toch overschrijven') }}</button>
                </div>
            </div>
        </div>
    </template>
</div>
