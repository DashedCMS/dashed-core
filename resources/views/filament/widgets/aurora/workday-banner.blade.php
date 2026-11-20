@php($open = $this->getOpenCount())
<x-filament-widgets::widget>
    @if ($open > 0)
        <section class="au-workday" aria-labelledby="au-workday-title"
                 style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:16px 20px; border-radius:14px; border:1px solid color-mix(in oklab, var(--primary-500) 45%, transparent); background:linear-gradient(90deg, color-mix(in oklab, var(--primary-500) 22%, transparent), color-mix(in oklab, var(--primary-500) 8%, transparent));">
            <div>
                <h2 id="au-workday-title" class="au-workday__title" style="margin:0; font-weight:700;">
                    {{ __('Jouw werkdag in één oogopslag') }}
                </h2>
                <p class="au-workday__sub" style="margin:4px 0 0;">
                    {{ $open === 1 ? __('1 actie vraagt aandacht.') : __(':aantal acties vragen aandacht.', ['aantal' => $open]) }}
                </p>
            </div>
            <a href="#dashboard-priorities" class="au-btn au-btn--primary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:9px; font-weight:600; background:var(--primary-500); color:#04130e; text-decoration:none;">
                <x-filament::icon icon="heroicon-o-arrow-right" style="width:16px;height:16px;" aria-hidden="true" />
                <span>{{ __('Bekijk acties') }}</span>
            </a>
        </section>
    @endif
</x-filament-widgets::widget>
