{{--
    Op zichzelf staande foutpagina: geen layout van het project, geen database,
    geen Vite-build. Alleen wat ErrorPageRenderer::branding() al in rescue() kon
    ophalen (sitenaam, logo, huisstijlkleur), zodat hij ook werkt als precies
    dat de storing is.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} - {{ $branding['siteName'] }}</title>
    <style>
        :root { --dve-primary: {{ $branding['primaryColor'] }}; }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1f2937;
            background: #ffffff;
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .dve-header { border-bottom: 1px solid #e5e7eb; padding: 20px 24px; }
        .dve-header a { display: inline-flex; align-items: center; gap: 10px; color: inherit; text-decoration: none; font-weight: 700; font-size: 18px; }
        .dve-header img { height: 40px; width: auto; display: block; }
        .dve-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 64px 24px; }
        .dve-card { max-width: 560px; text-align: center; }
        .dve-code { color: var(--dve-primary); font-weight: 600; font-size: 14px; letter-spacing: .08em; margin: 0 0 8px; }
        .dve-title { font-size: clamp(28px, 5vw, 40px); font-weight: 700; line-height: 1.15; margin: 0; color: #111827; }
        .dve-message { margin: 16px 0 0; font-size: 17px; line-height: 1.6; color: #4b5563; }
        .dve-actions { margin-top: 36px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .dve-button {
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px;
            background: var(--dve-primary); color: #ffffff; font-weight: 600; text-decoration: none; border: 0;
            font-size: 16px; cursor: pointer; font-family: inherit;
        }
        .dve-button:hover { filter: brightness(.92); }
        .dve-button--secondary { background: transparent; color: #374151; border: 1px solid #d1d5db; }
        .dve-button--secondary:hover { background: #f9fafb; filter: none; }
        .dve-footer { border-top: 1px solid #e5e7eb; padding: 20px 24px; text-align: center; font-size: 14px; color: #6b7280; }
    </style>
</head>
<body {{ \Dashed\DashedCore\Classes\ErrorPages\ErrorPageRenderer::MARKER }}="{{ $statusCode }}">
    <header class="dve-header">
        <a href="{{ $homeUrl }}">
            @if($branding['logoUrl'])
                <img src="{{ $branding['logoUrl'] }}" alt="{{ $branding['siteName'] }}">
            @else
                {{ $branding['siteName'] }}
            @endif
        </a>
    </header>

    <main class="dve-main">
        <div class="dve-card">
            <p class="dve-code">{{ $statusCode }}</p>
            <h1 class="dve-title">{{ $title }}</h1>
            <p class="dve-message">{{ $message }}</p>

            <div class="dve-actions">
                @if($statusCode === 419)
                    <button type="button" class="dve-button" onclick="history.back()">{{ __('Ga terug') }}</button>
                @endif
                @if($isCms && $cmsUrl)
                    <a href="{{ $cmsUrl }}" class="dve-button{{ $statusCode === 419 ? ' dve-button--secondary' : '' }}">{{ __('Naar het beheer') }}</a>
                    <a href="{{ $homeUrl }}" class="dve-button dve-button--secondary">{{ __('Naar de website') }}</a>
                @else
                    <a href="{{ $homeUrl }}" class="dve-button{{ $statusCode === 419 ? ' dve-button--secondary' : '' }}">{{ __('Terug naar de homepagina') }}</a>
                @endif
            </div>
        </div>
    </main>

    <footer class="dve-footer">
        &copy; {{ now()->year }} {{ $branding['siteName'] }}
    </footer>
</body>
</html>
