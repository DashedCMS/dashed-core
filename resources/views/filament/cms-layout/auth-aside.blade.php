{{--
    Inlogpagina en de andere losse pagina's (zie CmsLayout), in Aurora: altijd
    donker, met een raster en een gloed in de hoofdkleur, het merk boven de
    kaart met de accentrand en drie tegels eronder. Na het inloggen bepaalt Filament het thema weer zelf.
--}}
<script>
    (() => {
        // Filament zet de klasse na het laden terug naar het gekozen thema;
        // op deze pagina's houden we hem vast zonder die keuze te wijzigen.
        const html = document.documentElement;
        const force = () => { if (! html.classList.contains('dark')) html.classList.add('dark'); };
        force();
        new MutationObserver(force).observe(html, { attributes: true, attributeFilter: ['class'] });
    })();
</script>
<div class="au-auth-backdrop" aria-hidden="true"></div>
<div class="au-auth-brand">
    <span class="au-auth-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M5 4h7a8 8 0 010 16H5z" fill="none" stroke="currentColor" stroke-width="2.2"/></svg>
    </span>
    <span class="au-auth-name">{{ $siteName }}</span>
    <span class="au-auth-line">Dashed CMS · Aurora</span>
</div>
<ul class="au-auth-features" aria-label="{{ __('Wat je hier beheert') }}">
    <li>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6.5a3 3 0 016 0V8"/></svg>
        <span><strong>{{ __('Webshop') }}</strong>{{ __('Bestellingen en producten') }}</span>
    </li>
    <li>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="3.5" width="16" height="17" rx="2"/><path d="M8 8.5h8M8 12h8M8 15.5h5"/></svg>
        <span><strong>{{ __('Content') }}</strong>{{ __('Pagina\'s, artikelen en media') }}</span>
    </li>
    <li>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 10v4h3l6 4V6L7 10H4z"/><path d="M17 9a4 4 0 010 6"/></svg>
        <span><strong>{{ __('Marketing') }}</strong>{{ __('Nieuwsbrieven en campagnes') }}</span>
    </li>
</ul>
<p class="au-auth-foot">&copy; {{ now()->year }} Dashed CMS</p>
