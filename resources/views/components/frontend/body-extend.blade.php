<x-impersonate::banner/>

@php
    $adminBarEnabled = \Dashed\DashedCore\Models\Customsetting::get('admin_bar_enabled', default: true);
    $showAdminBar = $adminBarEnabled && auth()->check() && auth()->user()->role === 'superadmin';

    if ($showAdminBar) {
        $adminBarModel = View::shared('model');
        $adminBarEditUrl = null;
        if ($adminBarModel) {
            $resource = str(class_basename($adminBarModel))->snake()->replace('_', '-')->plural()->toString();
            try {
                $adminBarEditUrl = route("filament.dashed.resources.{$resource}.edit", ['record' => $adminBarModel->id]);
            } catch (\Exception $e) {}
        }
    }
@endphp

@if($showAdminBar)
    <div style="position: relative; z-index: 9999; background-color: #030712; border-bottom: 1px solid #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #fff;">
        <div style="max-width: 1280px; margin: 0 auto; padding: 8px 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                {{-- Greeting --}}
                <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Hallo {{ auth()->user()->first_name ?? auth()->user()->name }}
                </div>

                <span style="color: #374151;">|</span>

                {{-- Model info --}}
                @if($adminBarModel ?? null)
                    <span style="color: #d1d5db; font-size: 12px;">{{ $adminBarModel->name ?? class_basename($adminBarModel) }}</span>
                    @if($adminBarEditUrl)
                        <a href="{{ $adminBarEditUrl }}" target="_blank"
                           style="display: inline-flex; align-items: center; gap: 6px; border-radius: 6px; background-color: #2563eb; padding: 4px 10px; font-size: 12px; font-weight: 600; color: #fff; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 12px; height: 12px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Bewerken
                        </a>
                    @endif
                @else
                    <span style="color: #4b5563; font-size: 12px; font-style: italic;">Geen model op deze pagina</span>
                @endif
            </div>

            {{-- Dashboard link --}}
            <a href="{{ route('filament.dashed.pages.dashboard') }}"
               style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #d1d5db; text-decoration: none;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

        </div>
    </div>
@endif

@php
    $tracking = $trackingSettings ?? [];

    $googleTagmanagerId = $tracking['google_tagmanager_id'] ?? null;

    $facebookEnabled = !empty($tracking['facebook_pixel_conversion_id'] ?? null)
        || !empty($tracking['facebook_pixel_site_id'] ?? null)
        || !empty($tracking['trigger_facebook_events'] ?? false);

    $extraBody = $extraBodyScripts ?? '';
@endphp

@if(app()->isProduction() && $googleTagmanagerId)
    <noscript>
        <iframe
            src="https://www.googletagmanager.com/ns.html?id={{ $googleTagmanagerId }}"
            height="0"
            width="0"
            style="display:none;visibility:hidden"
        ></iframe>
    </noscript>
@endif

{!! $extraBody !!}

@if(isset($model))
    {!! $model->metaData->top_body_scripts ?? '' !!}
@endif

<script>
    document.addEventListener('livewire:init', () => {
        const tracking = {
            facebook: @json($facebookEnabled),
        };

        Livewire.on('formSubmitted', (event) => {
            const payload = event[0];

            if (tracking.facebook && typeof fbq !== 'undefined') {
                setTimeout(() => {
                    fbq('track', 'Contact');
                }, 1000);
            }

            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    event: 'formSubmit',
                    formId: payload.formId,
                    formName: payload.formName,
                });
            }
        });

        Livewire.on('searchInitiated', () => {
            if (tracking.facebook && typeof fbq !== 'undefined') {
                setTimeout(() => {
                    fbq('track', 'Search');
                }, 1000);
            }
        });
    });
</script>

{{--@include('cookie-consent::index')--}}

@if(class_exists(\Dashed\DashedPopups\Models\Popup::class))
    @php
        // Defensief: er hoort maximaal 1 actieve popup te zijn (boot-hook op
        // Popup model bewaakt dat), maar voor het geval er om welke reden
        // dan ook meerdere actieve popups in de DB staan, mounten we slechts
        // de meest recente. De Livewire-component in dashed-popups doet
        // daarna nog eigen targeting + per-sessie-suppression.
        $activePopup = \Dashed\DashedPopups\Models\Popup::query()
            ->where('active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('id')
            ->first();
    @endphp
    @if($activePopup)
        <livewire:dashed-popups.popup :popupId="(string) $activePopup->id" :key="'popup-'.$activePopup->id"/>
    @endif
@endif

@if(isset($model) && $model && method_exists($model, 'breadcrumbs'))
    <x-dashed-core::frontend.breadcrumbs.schema :breadcrumbs="$model->breadcrumbs()" />
@endif
