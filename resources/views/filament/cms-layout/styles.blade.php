{{--
    Dashed-huisstijl van het paneel (zie CmsLayout). Eigen stijlblok, omdat niet
    elk klantproject vendor/dashed meecompileert. De accentkleur komt altijd uit
    --primary-* (de hoofdkleur van de site), nooit uit een vaste kleurcode.
--}}
<style>
    /* ================================================================ */
    /* 1. Tokens                                                         */
    /* ================================================================ */
    :root {
        --dcl-bg: #F2F4F5;
        --dcl-panel: #FFFFFF;
        --dcl-raised: #F7F8F9;
        --dcl-border: #E1E5E7;
        --dcl-border-strong: #CBD2D5;
        --dcl-text: #0F1A1D;
        --dcl-text-soft: #34413F;
        --dcl-muted: #5D6A6D;
        --dcl-hover: rgb(15 26 29 / .045);

        --dcl-accent: var(--primary-700);
        --dcl-accent-solid: var(--primary-500);
        --dcl-accent-soft: color-mix(in oklab, var(--primary-500) 12%, transparent);
        --dcl-accent-border: color-mix(in oklab, var(--primary-500) 55%, transparent);

        /* Zijbalk: altijd donker, ook in de lichte modus */
        --dcl-side-bg: #0C1518;
        --dcl-side-raised: #132024;
        --dcl-side-text: #E8EFF0;
        --dcl-side-soft: #B9C6C9;
        --dcl-side-muted: #7E9094;
        --dcl-side-hover: rgb(255 255 255 / .06);
        --dcl-side-line: rgb(255 255 255 / .08);
        --dcl-side-accent: var(--primary-400);

        --dcl-radius: .875rem;
        --dcl-radius-sm: .625rem;
        --dcl-radius-xs: .4375rem;
        --dcl-shadow: 0 1px 2px rgb(15 26 29 / .05);
        --dcl-shadow-pop: 0 16px 40px -12px rgb(15 26 29 / .22);
        --dcl-focus: 0 0 0 3px color-mix(in oklab, var(--primary-500) 30%, transparent);

        /* Toonkleuren voor tegels */
        --dcl-tone-1: var(--primary-500);
        --dcl-tone-2: var(--info-500);
        --dcl-tone-3: var(--success-500);
        --dcl-tone-4: var(--warning-500);
    }
    :root.dark {
        --dcl-bg: #080D0F;
        --dcl-panel: #0F1618;
        --dcl-raised: #151E21;
        --dcl-border: #1F2A2D;
        --dcl-border-strong: #2F3C40;
        --dcl-text: #ECF2F3;
        --dcl-text-soft: #C7D1D3;
        --dcl-muted: #93A1A4;
        --dcl-hover: rgb(255 255 255 / .05);

        --dcl-accent: var(--primary-400);
        --dcl-accent-soft: color-mix(in oklab, var(--primary-500) 16%, transparent);
        --dcl-accent-border: color-mix(in oklab, var(--primary-500) 65%, transparent);

        --dcl-side-bg: #060A0B;
        --dcl-side-raised: #0E1517;

        --dcl-shadow: 0 1px 2px rgb(0 0 0 / .35);
        --dcl-shadow-pop: 0 18px 44px -10px rgb(0 0 0 / .7);

        /* Filament bouwt donkere vlakken uit gray-950 (pagina) en gray-900 (panelen) */
        --gray-950: var(--dcl-bg);
        --gray-900: var(--dcl-panel);
    }

    /* ================================================================ */
    /* 2. Pagina                                                         */
    /* ================================================================ */
    body.fi-body { --topbar-height: 3.75rem; background-color: var(--dcl-bg) !important; color: var(--dcl-text); -webkit-font-smoothing: antialiased; }
    ::selection { background: color-mix(in oklab, var(--primary-500) 28%, transparent); }
    * { scrollbar-width: thin; scrollbar-color: var(--dcl-border-strong) transparent; }

    /* ================================================================ */
    /* 3. Zijbalk: donker, over de volle hoogte, merk bovenin            */
    /* ================================================================ */
    .fi-sidebar.fi-main-sidebar { background-color: var(--dcl-side-bg) !important; border-inline-end: 1px solid var(--dcl-side-line); color: var(--dcl-side-text); }
    .fi-sidebar-header { background: transparent !important; box-shadow: none !important; padding-inline: 1.125rem !important; }
    .fi-sidebar-header .fi-logo { margin-inline-start: 0 !important; }

    @media (min-width: 64rem) {
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-sidebar.fi-main-sidebar {
            top: 0; z-index: 31; height: 100dvh; margin-top: calc(-1 * var(--topbar-height));
        }
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-sidebar-header {
            display: flex; height: auto; min-height: 4.75rem; padding-block: .75rem;
            border-bottom: 1px solid var(--dcl-side-line);
        }
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-topbar-start { display: none; }
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-topbar { padding-inline: calc(var(--sidebar-width) + 1.5rem) 1.5rem; }
    }

    .dcl-brand { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .dcl-brand-mark {
        flex: none; width: 2.5rem; height: 2.5rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center;
        color: var(--primary-400);
        background: linear-gradient(145deg, color-mix(in oklab, var(--primary-500) 26%, #0C1518), #0C1518);
        box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--primary-400) 45%, transparent), 0 6px 18px -6px color-mix(in oklab, var(--primary-500) 55%, transparent);
    }
    .dcl-brand-mark svg { width: 1.25rem; height: 1.25rem; }
    .dcl-brand-text { display: grid; min-width: 0; line-height: 1.15; }
    .dcl-brand-name { font-size: 1rem; font-weight: 700; letter-spacing: -.015em; color: var(--dcl-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dcl-brand-line { font-size: .6875rem; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: var(--dcl-accent); }
    .fi-sidebar .dcl-brand-name { color: #FFFFFF; }
    .fi-sidebar .dcl-brand-line { color: var(--dcl-side-accent); }

    .fi-sidebar-nav { padding: 1rem .875rem 1.5rem !important; row-gap: 1rem !important; }
    .fi-sidebar-nav-groups { row-gap: 1.25rem !important; margin-inline: 0 !important; }
    .fi-sidebar-group, .fi-sidebar-group-items { row-gap: .125rem !important; }
    .fi-sidebar-group-btn { padding: .25rem .625rem !important; }
    .fi-sidebar-group-label { font-size: .6875rem !important; line-height: 1rem; font-weight: 600 !important; letter-spacing: .08em; text-transform: uppercase; color: var(--dcl-side-muted) !important; }
    .fi-sidebar-group-btn .fi-icon, .fi-sidebar-group-collapse-btn, .fi-sidebar-group-collapse-btn .fi-icon { color: var(--dcl-side-muted) !important; }

    .fi-sidebar-item-btn {
        position: relative; column-gap: .75rem !important; padding: .4375rem .625rem !important;
        border: 1px solid transparent; border-radius: var(--dcl-radius-sm) !important;
        transition: background-color .12s ease, border-color .12s ease;
    }
    .fi-sidebar-item-btn > .fi-icon { color: var(--dcl-side-muted) !important; }
    .fi-sidebar-item-label { color: var(--dcl-side-soft) !important; font-weight: 500 !important; }
    .fi-sidebar-item-btn:hover { background-color: var(--dcl-side-hover) !important; }
    .fi-sidebar-item-btn:hover .fi-sidebar-item-label, .fi-sidebar-item-btn:hover > .fi-icon { color: #FFFFFF !important; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: color-mix(in oklab, var(--primary-500) 18%, transparent) !important;
        border-color: color-mix(in oklab, var(--primary-400) 45%, transparent);
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn::before {
        content: ''; position: absolute; inset-block: .4375rem; inset-inline-start: -.5rem; width: 3px; border-radius: 999px;
        background-color: var(--primary-400);
        box-shadow: 0 0 10px color-mix(in oklab, var(--primary-400) 70%, transparent);
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon { color: var(--dcl-side-accent) !important; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-label { color: #FFFFFF !important; font-weight: 600 !important; }
    .fi-sidebar-item-grouped-border-part { background-color: var(--dcl-side-line) !important; }
    .fi-sidebar-item-badge-ctn .fi-badge { font-variant-numeric: tabular-nums; }
    .fi-sidebar-footer { border-color: var(--dcl-side-line) !important; }

    /* Zoekveld bovenaan de zijbalk (navigation-search-trigger) */
    .fi-sidebar .dashed-navsearch-trigger { border-color: var(--dcl-side-line) !important; background: var(--dcl-side-raised); color: var(--dcl-side-soft); opacity: 1; border-radius: var(--dcl-radius-sm); }
    .fi-sidebar .dashed-navsearch-trigger:hover { border-color: color-mix(in oklab, var(--primary-400) 45%, transparent) !important; color: #FFFFFF; }
    .fi-sidebar .dashed-navsearch-trigger kbd { border-color: var(--dcl-side-line); color: var(--dcl-side-muted); }

    /* ================================================================ */
    /* 4. Bovenbalk                                                      */
    /* ================================================================ */
    .fi-topbar {
        background-color: color-mix(in oklab, var(--dcl-bg) 85%, transparent) !important;
        border-bottom: 1px solid var(--dcl-border); box-shadow: none !important;
        -webkit-backdrop-filter: saturate(1.5) blur(12px); backdrop-filter: saturate(1.5) blur(12px);
    }
    .fi-topbar-end { column-gap: .75rem; }
    .fi-global-search-field .fi-input-wrp { min-width: 14rem; }
    .fi-user-menu-trigger .fi-avatar { box-shadow: 0 0 0 2px var(--dcl-panel), 0 0 0 3.5px var(--dcl-accent-border); }

    /* ================================================================ */
    /* 5. Paginakop                                                      */
    /* ================================================================ */
    .fi-page-header-main-ctn { row-gap: 1.25rem; padding-block: 1.5rem !important; }
    .fi-breadcrumbs ol li { color: var(--dcl-muted); }
    .fi-breadcrumbs ol li:last-child, .fi-breadcrumbs ol li a:hover { color: var(--dcl-accent); }
    .fi-header-heading { font-size: 1.75rem !important; line-height: 2.125rem !important; font-weight: 700 !important; letter-spacing: -.02em; color: var(--dcl-text); }
    .fi-header-subheading { margin-top: .25rem; font-size: .9375rem !important; color: var(--dcl-muted) !important; }

    /* ================================================================ */
    /* 6. Panelen                                                        */
    /* ================================================================ */
    .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-section.fi-aside:not(.fi-section-not-contained) > .fi-section-content-ctn,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat,
    .fi-empty-state:not(.fi-empty-state-not-contained),
    .fi-sc-tabs.fi-contained,
    .fi-sc-wizard.fi-contained,
    .fi-fo-repeater-item,
    .fi-fo-builder-item,
    .fi-in-repeatable-item {
        background-color: var(--dcl-panel) !important;
        border-radius: var(--dcl-radius) !important;
        box-shadow: 0 0 0 1px var(--dcl-border), var(--dcl-shadow) !important;
    }
    .fi-section .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-fo-repeater-item, .fi-fo-builder-item { background-color: var(--dcl-raised) !important; box-shadow: 0 0 0 1px var(--dcl-border) !important; }
    .fi-section:not(.fi-section-not-contained):not(.fi-aside) > .fi-section-header { align-items: center; padding: 1rem 1.25rem !important; }
    .fi-section.fi-section-has-header:not(.fi-collapsed):not(.fi-aside):not(.fi-section-not-contained) > .fi-section-content-ctn,
    .fi-section > .fi-section-content-ctn > .fi-section-footer { border-color: var(--dcl-border) !important; }
    .fi-section > .fi-section-header > .fi-icon:not(.fi-color) {
        color: var(--dcl-accent) !important; box-sizing: content-box; padding: .4375rem; border-radius: var(--dcl-radius-xs); background: var(--dcl-accent-soft);
    }
    .fi-section-header-heading { font-size: .9375rem !important; font-weight: 650 !important; color: var(--dcl-text); }
    .fi-section-header-description { color: var(--dcl-muted) !important; }
    .fi-fieldset:not(.fi-fieldset-not-contained) { border-color: var(--dcl-border) !important; border-radius: var(--dcl-radius) !important; }

    /* ================================================================ */
    /* 7. Statistiektegels: elke tegel een eigen toonkleur               */
    /* ================================================================ */
    .fi-wi-stats-overview-stat { --dcl-tone: var(--dcl-tone-1); position: relative; overflow: hidden; padding: 1.25rem 1.375rem !important; }
    .fi-wi-stats-overview-stat:nth-child(4n+2), .fi-grid-col:nth-child(4n+2) > .fi-sc-component > .fi-wi-stats-overview-stat { --dcl-tone: var(--dcl-tone-2); }
    .fi-wi-stats-overview-stat:nth-child(4n+3), .fi-grid-col:nth-child(4n+3) > .fi-sc-component > .fi-wi-stats-overview-stat { --dcl-tone: var(--dcl-tone-3); }
    .fi-wi-stats-overview-stat:nth-child(4n+4), .fi-grid-col:nth-child(4n+4) > .fi-sc-component > .fi-wi-stats-overview-stat { --dcl-tone: var(--dcl-tone-4); }
    .fi-wi-stats-overview-stat::before {
        content: ''; position: absolute; inset-inline: 0; top: 0; height: 3px;
        background: linear-gradient(90deg, var(--dcl-tone), color-mix(in oklab, var(--dcl-tone) 30%, transparent));
    }
    .fi-wi-stats-overview-stat::after {
        content: ''; position: absolute; width: 9rem; height: 9rem; inset-inline-end: -3rem; top: -3rem; border-radius: 999px; pointer-events: none;
        background: radial-gradient(closest-side, color-mix(in oklab, var(--dcl-tone) 14%, transparent), transparent);
    }
    a.fi-wi-stats-overview-stat:hover { box-shadow: 0 0 0 1px color-mix(in oklab, var(--dcl-tone) 55%, transparent), var(--dcl-shadow-pop) !important; }
    .fi-wi-stats-overview-stat-label { font-size: .8125rem !important; font-weight: 600 !important; color: var(--dcl-text-soft) !important; }
    .fi-wi-stats-overview-stat-value { font-size: 1.875rem !important; line-height: 2.25rem; font-weight: 700 !important; letter-spacing: -.025em; color: var(--dcl-text) !important; font-variant-numeric: tabular-nums; }
    .fi-wi-stats-overview-stat-description:not(.fi-color) { color: var(--dcl-muted) !important; }
    .fi-wi-stats-overview-stat-label-ctn > .fi-icon {
        box-sizing: content-box; padding: .4375rem; border-radius: var(--dcl-radius-xs);
        color: var(--dcl-tone) !important; background: color-mix(in oklab, var(--dcl-tone) 14%, transparent);
    }

    /* ================================================================ */
    /* 8. Tabs als pillen                                                */
    /* ================================================================ */
    .fi-tabs { column-gap: .5rem; }
    .fi-tabs:not(.fi-contained) { box-shadow: none !important; margin-inline: 0; padding: 2px !important; background: transparent !important; border-radius: 0 !important; }
    .fi-tabs-item { padding: .4375rem .875rem !important; border: 1px solid var(--dcl-border); border-radius: var(--dcl-radius-sm) !important; background-color: var(--dcl-panel); }
    .fi-tabs-item:hover { background-color: var(--dcl-raised); border-color: var(--dcl-border-strong); }
    .fi-tabs-item .fi-tabs-item-label { color: var(--dcl-text-soft); }
    .fi-tabs-item.fi-active { background-color: var(--dcl-accent-soft) !important; border-color: var(--dcl-accent-border); }
    .fi-tabs-item.fi-active .fi-tabs-item-label { color: var(--dcl-text) !important; font-weight: 600; }
    .fi-tabs-item.fi-active > .fi-icon { color: var(--dcl-accent) !important; }

    /* ================================================================ */
    /* 9. Tabellen                                                       */
    /* ================================================================ */
    .fi-ta-ctn:not(.fi-ta-ctn-with-header) { overflow: clip; }
    .fi-ta-ctn .fi-ta-header, .fi-ta-ctn .fi-ta-header-toolbar, .fi-ta-ctn .fi-pagination,
    .fi-ta-ctn .fi-ta-selection-indicator, .fi-ta-ctn .fi-ta-filter-indicators,
    .fi-ta-content-ctn, .fi-ta-table > :not(:last-child), .fi-ta-table > tbody > :not(:last-child) { border-color: var(--dcl-border) !important; }
    .fi-ta-ctn .fi-ta-selection-indicator, .fi-ta-ctn .fi-ta-filter-indicators { background-color: var(--dcl-accent-soft) !important; }
    .fi-ta-table > thead > tr { background-color: var(--dcl-raised) !important; }
    .fi-ta-header-cell { padding-block: .75rem !important; font-size: .8125rem !important; font-weight: 600 !important; color: var(--dcl-muted) !important; }
    .fi-ta-header-cell-sorted .fi-ta-header-cell-sort-btn { color: var(--dcl-accent) !important; }
    .fi-ta-row { transition: background-color 75ms; }
    .fi-ta-row:not(.fi-ta-group-header-row):hover { background-color: var(--dcl-hover) !important; }
    .fi-ta-row.fi-selected { background-color: var(--dcl-accent-soft) !important; }
    .fi-ta-cell { font-variant-numeric: tabular-nums; }

    .fi-pagination .fi-pagination-items { box-shadow: none !important; column-gap: .25rem; background: transparent !important; }
    .fi-pagination .fi-pagination-item { border: 0 !important; }
    .fi-pagination .fi-pagination-item-btn { min-width: 2.25rem; justify-content: center; border: 1px solid var(--dcl-border); border-radius: .5rem !important; background-color: var(--dcl-panel); }
    .fi-pagination .fi-pagination-item-btn:enabled:hover { background-color: var(--dcl-raised); border-color: var(--dcl-border-strong); }
    .fi-pagination .fi-pagination-item.fi-active .fi-pagination-item-btn { background-color: var(--dcl-accent-soft); border-color: var(--dcl-accent-border); }
    .fi-pagination .fi-pagination-item.fi-active .fi-pagination-item-label { color: var(--dcl-accent) !important; }

    /* ================================================================ */
    /* 10. Knoppen, velden, badges                                       */
    /* ================================================================ */
    .fi-btn { border-radius: var(--dcl-radius-sm) !important; font-weight: 600 !important; transition: transform .08s ease, box-shadow .15s ease, filter .15s ease; }
    .fi-btn:active:not(.fi-disabled) { transform: translateY(1px); }
    .fi-btn.fi-color-primary:not(.fi-outlined) {
        background-image: linear-gradient(180deg, var(--primary-400), var(--primary-600)) !important;
        box-shadow: inset 0 1px 0 rgb(255 255 255 / .22), 0 6px 16px -6px color-mix(in oklab, var(--primary-500) 70%, transparent) !important;
    }
    .fi-btn.fi-color-primary:not(.fi-outlined):hover { filter: brightness(1.06) saturate(1.05); }
    .fi-btn.fi-color:not(.fi-color-primary):not(.fi-color-gray):not(.fi-outlined) { box-shadow: inset 0 1px 0 rgb(255 255 255 / .18), 0 1px 2px rgb(15 26 29 / .15) !important; }
    .fi-btn.fi-color-gray:not(.fi-outlined), .fi-btn:not(.fi-color) { background-color: var(--dcl-panel) !important; box-shadow: 0 0 0 1px var(--dcl-border-strong), var(--dcl-shadow) !important; }
    .fi-btn:focus-visible { box-shadow: var(--dcl-focus) !important; }
    .fi-icon-btn { border-radius: var(--dcl-radius-xs) !important; }

    .fi-input-wrp {
        border-radius: var(--dcl-radius-sm) !important; background-color: var(--dcl-panel) !important;
        box-shadow: 0 0 0 1px var(--dcl-border-strong), var(--dcl-shadow) !important; transition: box-shadow .12s ease;
    }
    .fi-input-wrp:focus-within { box-shadow: 0 0 0 1px var(--dcl-accent-solid), var(--dcl-focus) !important; }
    .fi-input-wrp-prefix .fi-icon, .fi-input-wrp-suffix .fi-icon { color: var(--dcl-muted); }
    .fi-input-wrp:focus-within .fi-input-wrp-prefix .fi-icon { color: var(--dcl-accent); }
    .fi-fo-field-label-content { font-weight: 600 !important; color: var(--dcl-text-soft); }
    .fi-checkbox-input:checked, .fi-radio-input:checked { background-color: var(--dcl-accent-solid) !important; }

    .fi-badge { border-radius: var(--dcl-radius-xs) !important; font-weight: 600 !important; box-shadow: inset 0 0 0 1px color-mix(in oklab, currentColor 22%, transparent) !important; }

    /* ================================================================ */
    /* 11. Menu's, modals, meldingen                                     */
    /* ================================================================ */
    .fi-dropdown-panel { border-radius: .75rem !important; background-color: var(--dcl-panel) !important; box-shadow: 0 0 0 1px var(--dcl-border), var(--dcl-shadow-pop) !important; }
    .fi-dropdown-list-item { border-radius: .5rem !important; }
    .fi-dropdown-list-item:hover { background-color: var(--dcl-accent-soft) !important; }
    .fi-modal-window { border-radius: 1.125rem !important; background-color: var(--dcl-panel) !important; box-shadow: 0 0 0 1px var(--dcl-border), var(--dcl-shadow-pop) !important; }
    .fi-modal-close-overlay { background: rgb(6 10 11 / .5) !important; -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
    .fi-no-notification { border-radius: .875rem !important; background-color: var(--dcl-panel) !important; box-shadow: 0 0 0 1px var(--dcl-border), var(--dcl-shadow-pop) !important; }

    /* ================================================================ */
    /* 12. Inloggen en andere losse pagina's (altijd donker)             */
    /* ================================================================ */
    .fi-simple-layout {
        position: relative; isolation: isolate; display: flex !important; flex-direction: column; align-items: center; justify-content: center;
        gap: 1.75rem; min-height: 100dvh; padding: 2.5rem 1rem; overflow: hidden; background-color: #05090A;
    }
    .fi-simple-layout > .fi-simple-main-ctn { flex-grow: 0 !important; width: 100%; }
    .fi-simple-layout .fi-simple-main {
        margin-block: 0 !important; border-radius: 1.25rem !important;
        border: 1px solid color-mix(in oklab, var(--primary-500) 45%, transparent);
        background-color: color-mix(in oklab, #0F1618 86%, transparent) !important;
        -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
        box-shadow: 0 30px 80px -30px color-mix(in oklab, var(--primary-500) 45%, transparent), 0 0 0 1px rgb(255 255 255 / .03) inset !important;
    }
    .fi-simple-header .fi-logo { display: none !important; }
    .fi-simple-header-heading { font-size: 1.75rem !important; font-weight: 700 !important; letter-spacing: -.02em; }
    .fi-simple-header::after { content: ''; display: block; width: 2.5rem; height: 2px; margin: 1rem auto 0; border-radius: 1px; background-color: var(--primary-500); }
    .fi-simple-main .fi-btn[type='submit'] { min-height: 3rem; font-size: 1rem; }
    .fi-simple-layout > .fi-footer { order: 5; }

    .dcl-auth-backdrop {
        position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image:
            radial-gradient(60rem 30rem at 50% -10%, color-mix(in oklab, var(--primary-500) 30%, transparent), transparent 70%),
            radial-gradient(30rem 30rem at 100% 100%, color-mix(in oklab, var(--primary-600) 18%, transparent), transparent 70%),
            linear-gradient(rgb(255 255 255 / .035) 1px, transparent 1px),
            linear-gradient(90deg, rgb(255 255 255 / .035) 1px, transparent 1px);
        background-size: auto, auto, 44px 44px, 44px 44px;
        -webkit-mask-image: radial-gradient(ellipse at 50% 40%, #000 45%, transparent 85%);
        mask-image: radial-gradient(ellipse at 50% 40%, #000 45%, transparent 85%);
    }
    .dcl-auth-brand { order: 1; display: grid; justify-items: center; gap: .5rem; text-align: center; }
    .fi-simple-layout > .fi-simple-main-ctn { order: 2; }
    .dcl-auth-mark {
        width: 4rem; height: 4rem; border-radius: 1.125rem; display: flex; align-items: center; justify-content: center; color: var(--primary-400);
        background: linear-gradient(145deg, color-mix(in oklab, var(--primary-500) 28%, #0C1518), #0A1214);
        box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--primary-400) 50%, transparent), 0 12px 32px -8px color-mix(in oklab, var(--primary-500) 65%, transparent);
    }
    .dcl-auth-mark svg { width: 2rem; height: 2rem; }
    .dcl-auth-name { margin-top: .5rem; font-size: 2rem; line-height: 2.25rem; font-weight: 700; letter-spacing: -.02em; color: #FFFFFF; }
    .dcl-auth-line { font-size: .75rem; letter-spacing: .42em; text-transform: uppercase; color: #93A1A4; padding-inline-start: .42em; }
    .dcl-auth-features { order: 3; display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin: 0; padding: 0; list-style: none; }
    .dcl-auth-features > li {
        display: flex; align-items: center; gap: .75rem; min-width: 13rem; padding: .75rem 1rem;
        border: 1px solid rgb(255 255 255 / .08); border-radius: var(--dcl-radius-sm);
        background-color: rgb(15 22 24 / .75); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
        font-size: .8125rem; color: #93A1A4;
    }
    .dcl-auth-features svg { flex: none; width: 2.25rem; height: 2.25rem; padding: .5rem; box-sizing: border-box; border-radius: var(--dcl-radius-xs); color: var(--primary-400); background: color-mix(in oklab, var(--primary-500) 16%, transparent); }
    .dcl-auth-features strong { display: block; font-size: .875rem; font-weight: 600; color: #ECF2F3; }
    .dcl-auth-foot { order: 4; margin: 0; font-size: .75rem; color: #6C7B7E; }
    @media (max-width: 40rem) { .dcl-auth-features { display: none; } }

    /* ================================================================ */
    /* 13. Toegankelijkheid                                              */
    /* ================================================================ */
    .fi-sidebar-item-btn:focus-visible { outline: none; border-color: var(--primary-400); }
    .fi-tabs-item:focus-visible, .fi-icon-btn:focus-visible { outline: 2px solid var(--dcl-accent-solid); outline-offset: 1px; }
    @media (prefers-reduced-motion: reduce) {
        .fi-btn, .fi-sidebar-item-btn, .fi-ta-row, .fi-input-wrp { transition: none !important; }
    }
</style>
