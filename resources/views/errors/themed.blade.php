{{--
    Foutpagina in de <x-master> van het project: met de navigatie, de footer en
    de fonts van de site. De opmaak staat in een eigen stijlblok met dve-klassen
    en niet in Tailwind-klassen, want niet elk klantproject scant vendor/dashed
    in zijn Tailwind-build. De kleur komt uit de Tailwind-theme van het project
    (--color-primary), met de mailkleur uit de instellingen als terugval.

    Een project dat het anders wil zet een eigen view neer op
    resources/views/<thema>/errors/show.blade.php (of errors/<code>.blade.php).
--}}
<x-master>
    <style>
        .dve-section {
            --dve-primary: var(--color-primary-500, var(--color-primary, {{ $branding['primaryColor'] }}));
            display: flex; align-items: center; justify-content: center;
            min-height: 60vh; padding: 64px 24px;
        }
        .dve-card { max-width: 560px; text-align: center; }
        .dve-code { color: var(--dve-primary); font-weight: 600; font-size: 14px; letter-spacing: .08em; margin: 0 0 8px; }
        .dve-title { font-size: clamp(28px, 5vw, 40px); font-weight: 700; line-height: 1.15; margin: 0; }
        .dve-message { margin: 16px 0 0; font-size: 17px; line-height: 1.6; opacity: .8; }
        .dve-actions { margin-top: 36px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .dve-button {
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px;
            background: var(--dve-primary); color: #ffffff; font-weight: 600; text-decoration: none; border: 0;
            font-size: 16px; cursor: pointer; font-family: inherit;
        }
        .dve-button:hover { filter: brightness(.92); }
        .dve-button--secondary { background: transparent; color: inherit; border: 1px solid currentColor; opacity: .8; }
        .dve-button--secondary:hover { opacity: 1; filter: none; }
    </style>

    <section class="dve-section" {{ \Dashed\DashedCore\Classes\ErrorPages\ErrorPageRenderer::MARKER }}="{{ $statusCode }}">
        <div class="dve-card">
            <p class="dve-code">{{ $statusCode }}</p>
            <h1 class="dve-title">{{ Translation::get('title-' . $statusCode, 'errors', $title) }}</h1>
            <p class="dve-message">{{ Translation::get('message-' . $statusCode, 'errors', $message) }}</p>

            <div class="dve-actions">
                @if($statusCode === 419)
                    <button type="button" class="dve-button" onclick="history.back()">{{ Translation::get('go-back', 'errors', __('Ga terug')) }}</button>
                @endif
                <a href="{{ $homeUrl }}" class="dve-button{{ $statusCode === 419 ? ' dve-button--secondary' : '' }}">
                    {{ Translation::get('back-to-home', 'errors', __('Terug naar de homepagina')) }}
                </a>
            </div>
        </div>
    </section>
</x-master>
