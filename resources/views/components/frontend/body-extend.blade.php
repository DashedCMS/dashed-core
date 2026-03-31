<x-impersonate::banner/>

@php
    $adminBarEnabled = \Dashed\DashedCore\Models\Customsetting::get('admin_bar_enabled', default: true);
    $showAdminBar = $adminBarEnabled && auth()->check() && auth()->user()->role === 'admin';

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
    <div class="relative z-[9999] bg-gray-950 border-b border-gray-800 font-sans text-sm text-white">
        <div class="mx-auto max-w-screen-xl px-4 py-2 flex items-center justify-between gap-4 flex-wrap">

            <div class="flex items-center gap-3 flex-wrap">
                {{-- Greeting --}}
                <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Hallo {{ auth()->user()->first_name ?? auth()->user()->name }}
                </div>

                <span class="text-gray-700">|</span>

                {{-- Model info --}}
                @if($adminBarModel ?? null)
                    <span class="text-gray-300 text-xs">{{ $adminBarModel->name ?? class_basename($adminBarModel) }}</span>
                    @if($adminBarEditUrl)
                        <a href="{{ $adminBarEditUrl }}" target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-white ring-1 ring-primary/50 hover:opacity-80 transition-opacity">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Bewerken
                        </a>
                    @endif
                @else
                    <span class="text-gray-600 text-xs italic">Geen model op deze pagina</span>
                @endif
            </div>

            {{-- Dashboard link --}}
            <a href="{{ route('filament.dashed.pages.dashboard') }}"
               class="inline-flex items-center gap-1.5 text-xs text-gray-300 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
