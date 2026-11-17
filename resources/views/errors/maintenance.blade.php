{{--
    Onderhoudspagina (503, `php artisan down`): een losse kaart in de huisstijl van de site.
    Net als de standalone-pagina zonder layout, database of Vite-build: alleen wat
    ErrorPageRenderer::branding() in rescue() kon ophalen (naam, logo, kleur, mailadres).
    Een project overschrijft hem met <thema>.errors.503.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('We zijn zo terug') }} - {{ $branding['siteName'] }}</title>
    <style>
        :root {
            --dvm-primary: {{ $branding['primaryColor'] }};
            --dvm-on-primary: {{ $branding['onPrimaryColor'] }};
            --dvm-text: #1f2937;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--dvm-text);
            background: #f7f5f2;
            background: color-mix(in srgb, var(--dvm-primary) 7%, #f9f8f6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            -webkit-font-smoothing: antialiased;
        }
        .dvm-card {
            position: relative;
            width: 100%;
            max-width: 560px;
            background: #ffffff;
            border-radius: 24px;
            padding: 48px 32px 40px;
            text-align: center;
            box-shadow: 0 20px 60px -20px rgba(17, 24, 39, .22);
            overflow: hidden;
        }
        .dvm-band { position: absolute; inset: 0 0 auto 0; height: 8px; background: var(--dvm-primary); }
        .dvm-logo { height: 44px; max-width: 220px; width: auto; object-fit: contain; margin: 0 auto 32px; display: block; }
        .dvm-wordmark { font-size: 28px; font-weight: 700; margin: 0 0 32px; }
        .dvm-badge {
            display: inline-block;
            background: #eef2f0;
            background: color-mix(in srgb, var(--dvm-primary) 22%, #ffffff);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 999px;
        }
        h1 { font-size: clamp(28px, 6vw, 42px); font-weight: 700; line-height: 1.1; margin: 20px 0 16px; }
        .dvm-message { font-size: 16px; line-height: 1.6; margin: 0 auto; max-width: 420px; color: #4b5563; }
        .dvm-button {
            display: inline-block;
            margin-top: 32px;
            background: var(--dvm-primary);
            color: var(--dvm-on-primary);
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 999px;
        }
        .dvm-button:hover { filter: brightness(.92); }
        .dvm-small { margin: 24px 0 0; font-size: 13px; color: #6b7280; }
        .dvm-small a { color: inherit; }
    </style>
</head>
<body {{ \Dashed\DashedCore\Classes\ErrorPages\ErrorPageRenderer::MARKER }}="{{ $statusCode }}">
    <main class="dvm-card">
        <div class="dvm-band" aria-hidden="true"></div>

        @if($branding['logoUrl'])
            <img class="dvm-logo" src="{{ $branding['logoUrl'] }}" alt="{{ $branding['siteName'] }}">
        @else
            <div class="dvm-wordmark">{{ $branding['siteName'] }}</div>
        @endif

        <span class="dvm-badge">{{ __('Even onderhoud') }}</span>
        <h1>{{ __('We zijn zo terug') }}</h1>
        <p class="dvm-message">{{ $message }}</p>

        <a class="dvm-button" href="{{ request()->fullUrl() }}">{{ __('Probeer het opnieuw') }}</a>

        @if($branding['contactEmail'])
            <p class="dvm-small">{{ __('Vragen? Mail ons op') }} <a href="mailto:{{ $branding['contactEmail'] }}">{{ $branding['contactEmail'] }}</a></p>
        @endif
    </main>
</body>
</html>
