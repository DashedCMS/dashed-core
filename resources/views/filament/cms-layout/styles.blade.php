{{--
    Aurora: de Dashed-huisstijl van het paneel (zie CmsLayout). Eén stijlblok,
    omdat niet elk klantproject vendor/dashed meecompileert; geen Vite, geen
    Tailwind-klassen in eigen views. De accentkleur komt altijd uit --primary-*
    (de hoofdkleur van de site); blue, purple, amber en red zijn vast.

    Eigen componenten (resources/views/components/aurora) gebruiken klassen met
    het voorvoegsel au-. De hulpklassen onderaan (au-grid-*, au-stack, au-muted,
    ...) mogen ook andere views gebruiken.
--}}
<style>
    /* ================================================================ */
    /* 1. Tokens: licht is de basis, donker staat onder :root.dark       */
    /* ================================================================ */
    :root {
        --au-bg: #F2F5F8;
        --au-bg-glow: color-mix(in oklab, var(--primary-200) 35%, transparent);
        --au-sidebar: #FBFCFD;
        --au-sidebar-2: #F3F6F8;
        --au-panel: #FFFFFF;
        --au-panel-2: #F7F9FB;
        --au-panel-3: #EEF2F5;
        --au-line: #D9E1E7;
        --au-line-2: #E6ECF0;
        --au-text: #0B1620;
        --au-text-soft: #2A3B48;
        --au-muted: #4D6170;
        --au-quiet: #5F7383;
        --au-hover: rgb(11 22 32 / .045);

        /* Accent volgt de site; tekst in accent is in licht donkerder voor 4,5:1 */
        --au-accent: var(--primary-700);
        --au-accent-solid: var(--primary-500);
        --au-accent-soft: color-mix(in oklab, var(--primary-500) 12%, transparent);
        --au-accent-line: color-mix(in oklab, var(--primary-500) 45%, transparent);
        --au-accent-glow: color-mix(in oklab, var(--primary-500) 22%, transparent);

        /* Vaste toonkleuren: -fg is tekst/icoon op een lichte ondergrond */
        --au-blue: #4F94FF;   --au-blue-fg: #1C5CC9;
        --au-purple: #AA79FA; --au-purple-fg: #6F3BC4;
        --au-amber: #F8B957;  --au-amber-fg: #8A5300;
        --au-red: #FF737F;    --au-red-fg: #BC2735;
        --au-mint: var(--primary-500); --au-mint-fg: var(--primary-700);
        --au-neutral: #8398A8; --au-neutral-fg: #445866;

        --au-radius: 14px;
        --au-radius-sm: 9px;
        --au-radius-xs: 6px;
        --au-shadow: 0 1px 2px rgb(11 22 32 / .05), 0 8px 22px -14px rgb(11 22 32 / .18);
        --au-shadow-pop: 0 18px 44px -14px rgb(11 22 32 / .28);
        --au-focus: 0 0 0 3px color-mix(in oklab, var(--primary-500) 32%, transparent);
        --au-card-bg: var(--au-panel);
        --au-on-mint: #04201C;

        /* Zijbalk in licht: licht vlak, zelfde accenten */
        --au-side-text: #1B2B37;
        --au-side-soft: #3A4D5B;
        --au-side-muted: #5A6E7D;
        --au-side-line: #E1E8ED;
        --au-side-hover: rgb(11 22 32 / .05);
        --au-side-accent: var(--primary-700);
        --au-side-active-bg: linear-gradient(100deg, color-mix(in oklab, var(--primary-500) 16%, #FFFFFF), color-mix(in oklab, var(--primary-500) 8%, #FFFFFF) 82%);
    }
    :root.dark {
        color-scheme: dark;
        --au-bg: #070E16;
        --au-bg-glow: #10283C;
        --au-sidebar: #08121C;
        --au-sidebar-2: #101F2A;
        --au-panel: #0E1B27;
        --au-panel-2: #101F2C;
        --au-panel-3: #122231;
        --au-line: #213343;
        --au-line-2: #1B2C3A;
        --au-text: #EAF4F9;
        --au-text-soft: #C8DAE4;
        --au-muted: #8398A8;
        --au-quiet: #7790A0;
        --au-hover: #172F3C;

        --au-accent: var(--primary-400);
        --au-accent-soft: color-mix(in oklab, var(--primary-500) 16%, transparent);
        --au-accent-line: color-mix(in oklab, var(--primary-500) 50%, transparent);
        --au-accent-glow: color-mix(in oklab, var(--primary-400) 18%, transparent);

        --au-blue-fg: #91BDFF;
        --au-purple-fg: #D8B3FF;
        --au-amber-fg: #FFD474;
        --au-red-fg: #FF9FA7;
        --au-mint-fg: var(--primary-300);
        --au-neutral-fg: #A6BCC5;

        --au-shadow: 0 10px 25px #01060B48;
        --au-shadow-pop: 0 24px 60px -10px #000000AA;
        --au-card-bg: linear-gradient(145deg, #101E2B 0%, #0D1B26 80%);

        --au-side-text: #EAF4F9;
        --au-side-soft: #A7B8C4;
        --au-side-muted: #6E8798;
        --au-side-line: #20313D;
        --au-side-hover: #152936;
        --au-side-accent: var(--primary-300);
        --au-side-active-bg: linear-gradient(100deg, color-mix(in oklab, var(--primary-500) 30%, #08121C), color-mix(in oklab, var(--primary-500) 18%, #08121C) 82%);

        /* Filament bouwt donkere vlakken uit gray-950 (pagina) en gray-900 (panelen) */
        --gray-950: var(--au-bg);
        --gray-900: var(--au-panel);
        --gray-800: var(--au-panel-3);
    }

    /* ================================================================ */
    /* 2. Pagina                                                         */
    /* ================================================================ */
    body.fi-body {
        --topbar-height: 4.125rem;
        background: radial-gradient(ellipse 80% 60% at 73% -30%, var(--au-bg-glow) 0%, transparent 65%) fixed, var(--au-bg) !important;
        color: var(--au-text); -webkit-font-smoothing: antialiased;
    }
    ::selection { background: color-mix(in oklab, var(--primary-500) 30%, transparent); }
    * { scrollbar-width: thin; scrollbar-color: var(--au-line) transparent; }
    .fi-main { max-width: 105rem; }

    /* ================================================================ */
    /* 3. Zijbalk (15rem), merk bovenin                                  */
    /* ================================================================ */
    .fi-sidebar.fi-main-sidebar {
        background: var(--au-sidebar) !important; border-inline-end: 1px solid var(--au-side-line); color: var(--au-side-text);
    }
    :root.dark .fi-sidebar.fi-main-sidebar { background: linear-gradient(155deg, #0C1923 0%, #07111A 65%, #071017 100%) !important; border-color: #243544; }
    .fi-sidebar-header { background: transparent !important; box-shadow: none !important; padding-inline: 1.25rem !important; }
    .fi-sidebar-header .fi-logo { margin-inline-start: 0 !important; }

    @media (min-width: 64rem) {
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-sidebar.fi-main-sidebar {
            top: 0; z-index: 31; height: 100dvh; margin-top: calc(-1 * var(--topbar-height));
        }
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-sidebar-header {
            display: flex; height: auto; min-height: calc(var(--topbar-height) + .6875rem); padding-block: .75rem;
            border-bottom: 1px solid var(--au-side-line);
        }
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-topbar-start { display: none; }
        .fi-body-has-topbar:not(.fi-body-has-top-navigation) .fi-topbar { padding-inline: calc(var(--sidebar-width) + 2.25rem) 2.25rem; }
    }

    .au-brand { display: flex; align-items: center; gap: .6875rem; min-width: 0; }
    .au-brand-mark {
        flex: none; width: 2.3125rem; height: 2.3125rem; border-radius: 11px; display: grid; place-items: center;
        color: var(--au-accent); border: 1px solid var(--au-accent-line);
        background: linear-gradient(135deg, color-mix(in oklab, var(--primary-500) 18%, var(--au-panel)), var(--au-panel));
        box-shadow: 0 0 24px var(--au-accent-glow);
    }
    .au-brand-mark svg { width: 1.25rem; height: 1.25rem; }
    .au-brand-text { display: grid; min-width: 0; line-height: 1.15; }
    .au-brand-name { font-size: 1rem; font-weight: 800; letter-spacing: -.03em; color: var(--au-side-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .au-brand-line { margin-top: 3px; font-size: .5625rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; white-space: nowrap; color: var(--au-side-accent); }

    .fi-sidebar-nav { padding: 1.125rem .75rem 1.5rem !important; row-gap: .75rem !important; }
    .fi-sidebar-nav-groups { row-gap: 1.25rem !important; margin-inline: 0 !important; }
    .fi-sidebar-group, .fi-sidebar-group-items { row-gap: .125rem !important; }
    .fi-sidebar-group-btn { padding: .25rem .625rem !important; }
    .fi-sidebar-group-label { font-size: .6875rem !important; line-height: 1rem; font-weight: 800 !important; letter-spacing: .13em; text-transform: uppercase; color: var(--au-side-muted) !important; }
    .fi-sidebar-group-btn .fi-icon, .fi-sidebar-group-collapse-btn, .fi-sidebar-group-collapse-btn .fi-icon { color: var(--au-side-muted) !important; }

    .fi-sidebar-item-btn {
        position: relative; column-gap: .6875rem !important; padding: .5625rem .75rem !important;
        border: 1px solid transparent; border-radius: var(--au-radius-sm) !important;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }
    .fi-sidebar-item-btn > .fi-icon { color: var(--au-side-muted) !important; }
    .fi-sidebar-item-label { color: var(--au-side-soft) !important; font-weight: 500 !important; font-size: .875rem; }
    .fi-sidebar-item-btn:hover { background-color: var(--au-side-hover) !important; }
    .fi-sidebar-item-btn:hover .fi-sidebar-item-label, .fi-sidebar-item-btn:hover > .fi-icon { color: var(--au-side-text) !important; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: var(--au-side-active-bg) !important;
        border-color: var(--au-accent-line);
        box-shadow: inset 3px 0 var(--primary-400), 0 0 24px var(--au-accent-glow);
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon { color: var(--au-side-accent) !important; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-label { color: var(--au-side-text) !important; font-weight: 600 !important; }
    .fi-sidebar-item-grouped-border-part { background-color: var(--au-side-line) !important; }
    .fi-sidebar-item-badge-ctn .fi-badge { font-variant-numeric: tabular-nums; }
    .fi-sidebar-footer { border-color: var(--au-side-line) !important; }

    /* Zoekveld bovenaan de zijbalk (navigation-search-trigger) */
    .fi-sidebar .dashed-navsearch-trigger-wrap { padding-bottom: .5rem; }
    .fi-sidebar .dashed-navsearch-trigger {
        height: 2.25rem; padding-inline: .6875rem; gap: .5625rem; font-size: .8125rem; opacity: 1;
        border: 1px solid var(--au-side-line) !important; border-radius: var(--au-radius-sm); background: var(--au-sidebar-2); color: var(--au-side-soft);
    }
    :root.dark .fi-sidebar .dashed-navsearch-trigger { border-color: #233744 !important; }
    .fi-sidebar .dashed-navsearch-trigger:hover { border-color: var(--au-accent-line) !important; color: var(--au-side-text); }
    .fi-sidebar .dashed-navsearch-trigger kbd { border-color: var(--au-side-line); color: var(--au-side-muted); font-size: .625rem; padding: 1px 6px; }

    /* ================================================================ */
    /* 4. Bovenbalk; breadcrumbs links met een huisje                     */
    /* ================================================================ */
    .fi-topbar {
        height: var(--topbar-height);
        background-color: color-mix(in oklab, var(--au-bg) 80%, transparent) !important;
        border-bottom: 1px solid var(--au-line); box-shadow: none !important;
        -webkit-backdrop-filter: saturate(1.4) blur(16px); backdrop-filter: saturate(1.4) blur(16px);
    }
    .fi-topbar-end { column-gap: 1rem; }
    .fi-global-search-field .fi-input-wrp { min-width: 15.5rem; }
    .fi-user-menu-trigger .fi-avatar { box-shadow: 0 0 0 2px var(--au-panel), 0 0 0 3.5px var(--au-accent-line); }

    .fi-breadcrumbs-list { align-items: center; }
    .fi-breadcrumbs-item-label { font-size: .8125rem; color: var(--au-quiet) !important; }
    .fi-breadcrumbs-item:last-child .fi-breadcrumbs-item-label { color: var(--au-text-soft) !important; font-weight: 600; }
    a.fi-breadcrumbs-item-label:hover { color: var(--au-accent) !important; }
    .fi-breadcrumbs-item-separator { color: var(--au-quiet) !important; }

    /* Breadcrumbs blijven in de paginakop (vast in de bovenbalk botste met
       banners boven de bovenbalk), met een huisje ervoor. */
    .fi-header .fi-breadcrumbs-list::before {
        content: ''; flex: none; width: 1rem; height: 1rem; margin-inline-end: .5rem; background-color: var(--au-quiet);
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 10.5 12 3l9 7.5'/%3E%3Cpath d='M5 9.5V20h14V9.5'/%3E%3Cpath d='M10 20v-6h4v6'/%3E%3C/svg%3E") center / contain no-repeat;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 10.5 12 3l9 7.5'/%3E%3Cpath d='M5 9.5V20h14V9.5'/%3E%3Cpath d='M10 20v-6h4v6'/%3E%3C/svg%3E") center / contain no-repeat;
    }

    /* ================================================================ */
    /* 5. Paginakop                                                      */
    /* ================================================================ */
    .fi-page-header-main-ctn { row-gap: 1.375rem; padding-block: 1.75rem 2.25rem !important; }
    .fi-header-heading { font-size: 1.75rem !important; line-height: 1.17 !important; font-weight: 800 !important; letter-spacing: -.0375em; color: var(--au-text); }
    .fi-header-subheading { margin-top: .5rem; font-size: .875rem !important; color: var(--au-muted) !important; }
    .fi-header-actions-ctn { gap: .5625rem; }

    /* ================================================================ */
    /* 6. Kaarten en secties: radius 14, rand, kop met scheidingslijn    */
    /* ================================================================ */
    .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-section.fi-aside:not(.fi-section-not-contained) > .fi-section-content-ctn,
    .fi-ta-ctn,
    .fi-empty-state:not(.fi-empty-state-not-contained),
    .fi-sc-tabs.fi-contained,
    .fi-sc-wizard.fi-contained,
    .fi-in-repeatable-item,
    .fi-wi-chart .fi-section {
        background: var(--au-card-bg) !important;
        border-radius: var(--au-radius) !important;
        box-shadow: 0 0 0 1px var(--au-line), var(--au-shadow) !important;
    }
    .fi-section .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-fo-repeater-item, .fi-fo-builder-item {
        background: var(--au-panel-2) !important; border-radius: var(--au-radius-sm) !important; box-shadow: 0 0 0 1px var(--au-line) !important;
    }
    .fi-section:not(.fi-section-not-contained):not(.fi-aside) > .fi-section-header { align-items: center; padding: 1.0625rem 1.25rem !important; }
    .fi-section.fi-section-has-header:not(.fi-collapsed):not(.fi-aside):not(.fi-section-not-contained) > .fi-section-content-ctn,
    .fi-section > .fi-section-content-ctn > .fi-section-footer { border-color: var(--au-line) !important; }
    .fi-section > .fi-section-header > .fi-icon:not(.fi-color) { color: var(--au-accent) !important; }
    .fi-section-header-heading { font-size: 1rem !important; font-weight: 750 !important; letter-spacing: -.01em; color: var(--au-text); }
    .fi-section-header-description { color: var(--au-muted) !important; }
    .fi-fieldset:not(.fi-fieldset-not-contained) { border-color: var(--au-line) !important; border-radius: var(--au-radius) !important; }

    /* ================================================================ */
    /* 7. Statistiektegels: toonkleuren mint, blue, purple, amber        */
    /* ================================================================ */
    .fi-wi-stats-overview-stat {
        --au-tone: var(--au-mint); --au-tone-fg: var(--au-mint-fg);
        position: relative; overflow: hidden; padding: 1.125rem 1.25rem !important;
        border-radius: 13px !important; background: var(--au-card-bg) !important;
        box-shadow: 0 0 0 1px var(--au-line), var(--au-shadow) !important;
    }
    :root.dark .fi-wi-stats-overview-stat { background: linear-gradient(137deg, #132734, #0C1A27 70%) !important; box-shadow: 0 0 0 1px #264353, var(--au-shadow) !important; }
    .fi-wi-stats-overview-stat:nth-child(4n+2), .fi-grid-col:nth-child(4n+2) > .fi-sc-component > .fi-wi-stats-overview-stat { --au-tone: var(--au-blue); --au-tone-fg: var(--au-blue-fg); }
    .fi-wi-stats-overview-stat:nth-child(4n+3), .fi-grid-col:nth-child(4n+3) > .fi-sc-component > .fi-wi-stats-overview-stat { --au-tone: var(--au-purple); --au-tone-fg: var(--au-purple-fg); }
    .fi-wi-stats-overview-stat:nth-child(4n+4), .fi-grid-col:nth-child(4n+4) > .fi-sc-component > .fi-wi-stats-overview-stat { --au-tone: var(--au-amber); --au-tone-fg: var(--au-amber-fg); }
    .fi-wi-stats-overview-stat::before {
        content: ''; position: absolute; width: 10.5rem; height: 10.5rem; inset-inline-end: -3.25rem; top: -3.25rem; border-radius: 50%; pointer-events: none;
        background: radial-gradient(circle, color-mix(in oklab, var(--au-tone) 26%, transparent), transparent 66%);
    }
    .fi-wi-stats-overview-stat > * { position: relative; }
    a.fi-wi-stats-overview-stat:hover { box-shadow: 0 0 0 1px color-mix(in oklab, var(--au-tone) 55%, transparent), var(--au-shadow-pop) !important; }
    .fi-wi-stats-overview-stat-label { font-size: .8125rem !important; font-weight: 500 !important; color: var(--au-text-soft) !important; }
    .fi-wi-stats-overview-stat-value { font-size: 1.875rem !important; line-height: 2.25rem; font-weight: 800 !important; letter-spacing: -.035em; color: var(--au-text) !important; font-variant-numeric: tabular-nums; }
    .fi-wi-stats-overview-stat-description:not(.fi-color) { color: var(--au-muted) !important; }
    .fi-wi-stats-overview-stat-label-ctn > .fi-icon {
        box-sizing: content-box; padding: .5rem; border-radius: var(--au-radius-sm);
        color: var(--au-tone-fg) !important; background: color-mix(in oklab, var(--au-tone) 18%, transparent);
    }

    /* ================================================================ */
    /* 8. Tabs als pillen met teller                                     */
    /* ================================================================ */
    .fi-tabs { column-gap: .375rem; }
    .fi-tabs:not(.fi-contained) {
        box-shadow: none !important; margin-inline: 0; padding: 0 0 .6875rem !important; background: transparent !important; border-radius: 0 !important;
        border-bottom: 1px solid var(--au-line);
    }
    .fi-tabs-item { padding: .5625rem .8125rem !important; border: 1px solid transparent; border-radius: 8px !important; background: transparent; }
    .fi-tabs-item:hover { background-color: var(--au-hover); }
    .fi-tabs-item .fi-tabs-item-label { color: var(--au-muted); font-weight: 700; font-size: .8125rem; }
    .fi-tabs-item .fi-badge { box-shadow: none !important; background: var(--au-panel-3) !important; color: var(--au-text-soft) !important; }
    .fi-tabs-item.fi-active { background-color: var(--au-accent-soft) !important; border-color: var(--au-accent-line); }
    .fi-tabs-item.fi-active .fi-tabs-item-label { color: var(--au-accent) !important; }
    .fi-tabs-item.fi-active > .fi-icon { color: var(--au-accent) !important; }
    .fi-tabs-item.fi-active .fi-badge { background: color-mix(in oklab, var(--primary-500) 28%, transparent) !important; color: var(--au-accent) !important; }
    .fi-sc-tabs.fi-contained .fi-tabs { border-bottom: 1px solid var(--au-line); padding: .625rem !important; }

    /* ================================================================ */
    /* 9. Tabellen: compact, kopregel in kleine kapitalen                */
    /* ================================================================ */
    .fi-ta-ctn:not(.fi-ta-ctn-with-header) { overflow: clip; }
    .fi-ta-ctn .fi-ta-header, .fi-ta-ctn .fi-ta-header-toolbar, .fi-ta-ctn .fi-pagination,
    .fi-ta-ctn .fi-ta-selection-indicator, .fi-ta-ctn .fi-ta-filter-indicators,
    .fi-ta-content-ctn, .fi-ta-table > :not(:last-child), .fi-ta-table > tbody > :not(:last-child) { border-color: var(--au-line) !important; }
    .fi-ta-header-toolbar { padding: .75rem 1rem !important; }
    .fi-ta-ctn .fi-ta-selection-indicator, .fi-ta-ctn .fi-ta-filter-indicators { background-color: var(--au-accent-soft) !important; }
    .fi-ta-table > thead > tr { background-color: var(--au-panel-2) !important; }
    :root.dark .fi-ta-table > thead > tr { background-color: #142532 !important; }
    .fi-ta-header-cell { padding-block: .8125rem !important; font-size: .6875rem !important; font-weight: 700 !important; letter-spacing: .05em; text-transform: uppercase; color: var(--au-muted) !important; }
    .fi-ta-header-cell-sort-btn { text-transform: uppercase; }
    .fi-ta-header-cell-sorted .fi-ta-header-cell-sort-btn { color: var(--au-accent) !important; }
    .fi-ta-row { transition: background-color .14s; }
    .fi-ta-row:not(.fi-ta-group-header-row):hover { background-color: var(--au-hover) !important; }
    .fi-ta-row.fi-selected { background-color: var(--au-accent-soft) !important; box-shadow: inset 3px 0 var(--au-accent-solid); }
    .fi-ta-cell { font-size: .8125rem; font-variant-numeric: tabular-nums; }
    .fi-ta-cell .fi-ta-text-item-label, .fi-ta-cell .fi-ta-text { font-size: .8125rem; }
    .fi-ta-group-header-row { background-color: var(--au-panel-3) !important; }
    .fi-ta-empty-state-icon-bg { background-color: var(--au-accent-soft) !important; }
    .fi-ta-empty-state-icon-bg .fi-icon { color: var(--au-accent) !important; }

    /* Filters: dropdown/modaal en filters boven de tabel als eigen balk */
    .fi-ta-filters-dropdown .fi-dropdown-panel, .fi-ta-filters { border-radius: var(--au-radius) !important; }
    .fi-ta-filters-above-content-ctn { background-color: var(--au-panel-2); border-color: var(--au-line) !important; padding: .875rem 1rem !important; }
    .fi-ta-filters-heading { font-size: .875rem; font-weight: 700; }
    .fi-ta-filter-indicators .fi-badge { background: var(--au-panel) !important; }

    /* Paginering */
    .fi-pagination { padding: .75rem 1.0625rem !important; }
    .fi-pagination-overview { font-size: .8125rem; color: var(--au-muted) !important; }
    .fi-pagination .fi-pagination-items { box-shadow: none !important; column-gap: .25rem; background: transparent !important; }
    .fi-pagination .fi-pagination-item { border: 0 !important; }
    .fi-pagination .fi-pagination-item-btn {
        min-width: 1.8125rem; height: 1.8125rem; padding-inline: .375rem; justify-content: center; font-size: .8125rem;
        border: 1px solid var(--au-line); border-radius: var(--au-radius-xs) !important; background-color: var(--au-panel);
    }
    .fi-pagination .fi-pagination-item-btn:enabled:hover { background-color: var(--au-hover); }
    .fi-pagination .fi-pagination-item.fi-active .fi-pagination-item-btn { background-color: var(--au-accent-soft); border-color: var(--au-accent-line); }
    .fi-pagination .fi-pagination-item.fi-active .fi-pagination-item-label { color: var(--au-accent) !important; font-weight: 700; }

    /* ================================================================ */
    /* 10. Knoppen: 9px; verloop en gloed alleen op de primaire knop     */
    /* ================================================================ */
    .fi-btn { border-radius: var(--au-radius-sm) !important; font-weight: 700 !important; transition: transform .08s ease, box-shadow .15s ease, filter .15s ease; }
    .fi-btn:not(.fi-size-xs):not(.fi-size-sm) { min-height: 2.3125rem; }
    .fi-btn:active:not(.fi-disabled):not([disabled]) { transform: translateY(1px); }
    .fi-btn.fi-color-primary:not(.fi-outlined) {
        background-image: linear-gradient(110deg, var(--primary-400), var(--primary-600)) !important;
        box-shadow: inset 0 1px 0 rgb(255 255 255 / .2), 0 5px 22px color-mix(in oklab, var(--primary-500) 26%, transparent) !important;
    }
    .fi-btn.fi-color-primary:not(.fi-outlined):hover:not(.fi-disabled):not([disabled]) { filter: brightness(1.06) saturate(1.05); }
    .fi-btn.fi-color:not(.fi-color-primary):not(.fi-color-gray):not(.fi-outlined) { box-shadow: inset 0 1px 0 rgb(255 255 255 / .16), 0 1px 2px rgb(11 22 32 / .15) !important; }
    .fi-btn.fi-color-gray:not(.fi-outlined), .fi-btn:not(.fi-color) {
        background: var(--au-panel) !important; box-shadow: 0 0 0 1px var(--au-line), var(--au-shadow) !important;
    }
    :root.dark .fi-btn.fi-color-gray:not(.fi-outlined), :root.dark .fi-btn:not(.fi-color) {
        background: linear-gradient(180deg, #182C3B, #10202D) !important; box-shadow: 0 0 0 1px #294252 !important; color: #C8DEE8;
    }
    .fi-btn.fi-outlined { box-shadow: 0 0 0 1px var(--au-line) !important; }
    .fi-btn.fi-disabled, .fi-btn[disabled] { filter: none !important; box-shadow: none !important; opacity: .55; }
    .fi-btn:focus-visible { box-shadow: var(--au-focus) !important; outline: 2px solid var(--au-accent-solid); outline-offset: 1px; }
    .fi-icon-btn { border-radius: var(--au-radius-sm) !important; }
    .fi-link { font-weight: 700; }

    /* ================================================================ */
    /* 11. Velden                                                         */
    /* ================================================================ */
    .fi-input-wrp {
        border-radius: 8px !important; background-color: var(--au-panel) !important;
        box-shadow: 0 0 0 1px var(--au-line) !important; transition: box-shadow .12s ease;
    }
    :root.dark .fi-input-wrp { background-color: #0B1925 !important; box-shadow: 0 0 0 1px #314856 !important; }
    .fi-input-wrp:focus-within:not(.fi-disabled) { box-shadow: 0 0 0 1px var(--au-accent-solid), var(--au-focus) !important; }
    .fi-input-wrp.fi-invalid { box-shadow: 0 0 0 1px var(--danger-500) !important; }
    .fi-input-wrp.fi-invalid:focus-within { box-shadow: 0 0 0 1px var(--danger-500), 0 0 0 3px color-mix(in oklab, var(--danger-500) 28%, transparent) !important; }
    .fi-input-wrp.fi-disabled { opacity: .7; background-color: var(--au-panel-2) !important; }
    .fi-input-wrp-prefix .fi-icon, .fi-input-wrp-suffix .fi-icon { color: var(--au-quiet); }
    .fi-input-wrp:focus-within .fi-input-wrp-prefix .fi-icon { color: var(--au-accent); }
    .fi-fo-field-label-content { font-weight: 600 !important; color: var(--au-text-soft); }
    .fi-checkbox-input, .fi-radio-input { border-color: var(--au-quiet); }
    .fi-checkbox-input { border-radius: 5px !important; }
    .fi-checkbox-input:checked, .fi-radio-input:checked { background-color: var(--au-accent-solid) !important; border-color: var(--au-accent-solid) !important; }
    .fi-checkbox-input:focus-visible, .fi-radio-input:focus-visible { box-shadow: var(--au-focus) !important; }
    .fi-toggle.fi-toggle-on { background-color: var(--au-accent-solid) !important; }

    /* Badges: kleur blijft van Filament, vorm wordt Aurora */
    .fi-badge { border-radius: var(--au-radius-xs) !important; font-weight: 700 !important; font-size: .6875rem !important; box-shadow: inset 0 0 0 1px color-mix(in oklab, currentColor 30%, transparent) !important; }

    /* ================================================================ */
    /* 12. Dropdowns, modals, meldingen                                   */
    /* ================================================================ */
    .fi-dropdown-panel { border-radius: 11px !important; background-color: var(--au-panel) !important; box-shadow: 0 0 0 1px var(--au-line), var(--au-shadow-pop) !important; }
    :root.dark .fi-dropdown-panel { background-color: #132432 !important; box-shadow: 0 0 0 1px #35505F, var(--au-shadow-pop) !important; }
    .fi-dropdown-list-item { border-radius: 7px !important; }
    .fi-dropdown-list-item:hover, .fi-dropdown-list-item:focus-visible { background-color: var(--au-accent-soft) !important; }
    .fi-dropdown-header { border-color: var(--au-line) !important; }
    .fi-modal-window { border-radius: 16px !important; background-color: var(--au-panel) !important; box-shadow: 0 0 0 1px var(--au-line), var(--au-shadow-pop) !important; }
    :root.dark .fi-modal-window { background-color: #132432 !important; box-shadow: 0 0 0 1px #35505F, 0 24px 90px #000A !important; }
    .fi-modal-slide-over .fi-modal-window { border-radius: 0 !important; }
    .fi-modal-heading { font-weight: 750 !important; letter-spacing: -.01em; }
    .fi-modal-close-overlay { background: rgb(1 7 12 / .62) !important; -webkit-backdrop-filter: blur(5px); backdrop-filter: blur(5px); }
    .fi-modal-footer { border-color: var(--au-line) !important; }
    .fi-no-notification { border-radius: 10px !important; background-color: var(--au-panel) !important; box-shadow: 0 0 0 1px var(--au-line), var(--au-shadow-pop) !important; }
    :root.dark .fi-no-notification { background-color: #132432 !important; }
    .fi-global-search-results-ctn { border-radius: 11px !important; box-shadow: 0 0 0 1px var(--au-line), var(--au-shadow-pop) !important; }

    /* ================================================================ */
    /* 13. Inloggen en andere losse pagina's (altijd donker, Aurora)     */
    /* ================================================================ */
    .fi-simple-layout {
        position: relative; isolation: isolate; display: flex !important; flex-direction: column; align-items: center; justify-content: center;
        gap: 1.75rem; min-height: 100dvh; padding: 2.5rem 1rem; overflow: hidden; background-color: #070E16;
    }
    .fi-simple-layout > .fi-simple-main-ctn { flex-grow: 0 !important; width: 100%; }
    .fi-simple-layout .fi-simple-main {
        margin-block: 0 !important; border-radius: 18px !important;
        border: 1px solid color-mix(in oklab, var(--primary-500) 55%, transparent);
        background: linear-gradient(145deg, #101E2BEE 0%, #0D1B26EE 80%) !important;
        -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
        box-shadow: 0 30px 80px -30px color-mix(in oklab, var(--primary-500) 45%, transparent), 0 0 0 1px rgb(255 255 255 / .03) inset !important;
    }
    .fi-simple-header .fi-logo { display: none !important; }
    .fi-simple-header-heading { font-size: 1.625rem !important; font-weight: 800 !important; letter-spacing: -.035em; }
    .fi-simple-header::after { content: ''; display: block; width: 2.5rem; height: 2px; margin: 1rem auto 0; border-radius: 1px; background-color: var(--primary-400); box-shadow: 0 0 12px var(--primary-400); }
    .fi-simple-main .fi-btn[type='submit'] { min-height: 2.875rem; font-size: .9375rem; }
    .fi-simple-layout > .fi-footer { order: 5; }

    .au-auth-backdrop {
        position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image:
            radial-gradient(60rem 30rem at 50% -10%, color-mix(in oklab, var(--primary-500) 26%, transparent), transparent 70%),
            radial-gradient(ellipse 80% 80% at 73% -30%, #10283C 0%, transparent 65%),
            linear-gradient(rgb(255 255 255 / .03) 1px, transparent 1px),
            linear-gradient(90deg, rgb(255 255 255 / .03) 1px, transparent 1px);
        background-size: auto, auto, 44px 44px, 44px 44px;
        -webkit-mask-image: radial-gradient(ellipse at 50% 40%, #000 45%, transparent 85%);
        mask-image: radial-gradient(ellipse at 50% 40%, #000 45%, transparent 85%);
    }
    .au-auth-brand { order: 1; display: grid; justify-items: center; gap: .375rem; text-align: center; }
    .fi-simple-layout > .fi-simple-main-ctn { order: 2; }
    .au-auth-mark {
        width: 3.75rem; height: 3.75rem; border-radius: 16px; display: grid; place-items: center; color: var(--primary-300);
        border: 1px solid color-mix(in oklab, var(--primary-400) 60%, transparent);
        background: linear-gradient(135deg, color-mix(in oklab, var(--primary-500) 22%, #0B1A21), #0B1A21);
        box-shadow: 0 0 36px color-mix(in oklab, var(--primary-500) 30%, transparent);
    }
    .au-auth-mark svg { width: 1.875rem; height: 1.875rem; }
    .au-auth-name { margin-top: .625rem; font-size: 1.875rem; line-height: 2.25rem; font-weight: 800; letter-spacing: -.035em; color: #EAF4F9; }
    .au-auth-line { font-size: .625rem; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; color: var(--primary-300); padding-inline-start: .2em; }
    .au-auth-features { order: 3; display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin: 0; padding: 0; list-style: none; }
    .au-auth-features > li {
        display: flex; align-items: center; gap: .75rem; min-width: 13rem; padding: .75rem 1rem;
        border: 1px solid #223746; border-radius: 13px;
        background: linear-gradient(145deg, #101E2BCC, #0D1B26CC); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
        font-size: .8125rem; color: #8398A8;
    }
    .au-auth-features svg { flex: none; width: 2.125rem; height: 2.125rem; padding: .5rem; box-sizing: border-box; border-radius: 9px; color: var(--primary-300); background: color-mix(in oklab, var(--primary-500) 18%, transparent); }
    .au-auth-features > li:nth-child(2) svg { color: #91BDFF; background: #143C6C; }
    .au-auth-features > li:nth-child(3) svg { color: #D8B3FF; background: #382353; }
    .au-auth-features strong { display: block; font-size: .875rem; font-weight: 700; color: #EAF4F9; }
    .au-auth-foot { order: 4; margin: 0; font-size: .75rem; color: #7790A0; }
    @media (max-width: 40rem) { .au-auth-features { display: none; } }

    /* ================================================================ */
    /* 14. Aurora-componenten (resources/views/components/aurora)        */
    /* ================================================================ */
    .au-tone-mint    { --au-tone: var(--au-mint);    --au-tone-fg: var(--au-mint-fg); }
    .au-tone-blue    { --au-tone: var(--au-blue);    --au-tone-fg: var(--au-blue-fg); }
    .au-tone-purple  { --au-tone: var(--au-purple);  --au-tone-fg: var(--au-purple-fg); }
    .au-tone-amber   { --au-tone: var(--au-amber);   --au-tone-fg: var(--au-amber-fg); }
    .au-tone-red     { --au-tone: var(--au-red);     --au-tone-fg: var(--au-red-fg); }
    .au-tone-neutral { --au-tone: var(--au-neutral); --au-tone-fg: var(--au-neutral-fg); }

    /* Badge */
    .au-badge {
        display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .4375rem; border-radius: var(--au-radius-xs);
        font-size: .6875rem; font-weight: 750; line-height: 1.2; white-space: nowrap; font-variant-numeric: tabular-nums;
        color: var(--au-tone-fg, var(--au-neutral-fg));
        background: color-mix(in oklab, var(--au-tone, var(--au-neutral)) 16%, transparent);
        border: 1px solid color-mix(in oklab, var(--au-tone, var(--au-neutral)) 42%, transparent);
    }

    /* Kaart */
    .au-card { min-width: 0; border: 1px solid var(--au-line); border-radius: var(--au-radius); background: var(--au-card-bg); box-shadow: var(--au-shadow); overflow: hidden; color: var(--au-text); }
    :root.dark .au-card { border-color: #223746; }
    .au-card-head { display: flex; align-items: center; justify-content: space-between; gap: .625rem; padding: 1.0625rem 1.25rem; border-bottom: 1px solid var(--au-line); }
    .au-card-title { display: flex; align-items: center; gap: .5625rem; min-width: 0; margin: 0; font-size: 1rem; font-weight: 750; letter-spacing: -.01em; }
    .au-card-icon { width: 1.125rem; height: 1.125rem; flex: none; color: var(--au-accent); }
    .au-card-actions { display: flex; align-items: center; gap: .5rem; flex: none; }
    .au-card-body { padding: 1.25rem; }
    .au-card-body-flush { padding: 0; }

    /* Voortgang */
    .au-stepper {
        display: flex; margin: 0; padding: 1.25rem 1.375rem; list-style: none; overflow-x: auto;
        border: 1px solid var(--au-line); border-radius: 13px; background: var(--au-card-bg); box-shadow: var(--au-shadow);
    }
    :root.dark .au-stepper { background: linear-gradient(120deg, #142B36, #0E1D29); border-color: #28404E; }
    .au-step { position: relative; flex: 1; min-width: 6.5rem; text-align: center; }
    .au-step::before { content: ''; position: absolute; inset-inline: 0; top: 15px; height: 2px; background: var(--au-line); }
    .au-step:first-child::before { inset-inline-start: 50%; }
    .au-step:last-child::before { inset-inline-end: 50%; }
    .au-step:only-child::before { display: none; }
    .au-step-done::before, .au-step-current::before { background: linear-gradient(90deg, var(--primary-600), var(--primary-400)); }
    .au-step-dot {
        position: relative; display: grid; place-items: center; width: 31px; height: 31px; margin: 0 auto .625rem;
        border: 2px solid var(--au-line); border-radius: 50%; background: var(--au-panel-3);
        font-size: .75rem; font-weight: 700; color: var(--au-muted); font-variant-numeric: tabular-nums;
    }
    :root.dark .au-step-dot { border-color: #445C6C; background: #162B38; color: #9CB6C3; }
    .au-step-icon { width: 15px; height: 15px; }
    .au-step-done .au-step-dot { background: var(--primary-500) !important; border-color: var(--primary-400) !important; color: #052A22 !important; }
    .au-step-current .au-step-dot { border-color: var(--primary-400) !important; color: var(--au-accent) !important; box-shadow: 0 0 0 5px var(--au-accent-soft); }
    .au-step-warning .au-step-dot { border-color: var(--au-amber) !important; background: color-mix(in oklab, var(--au-amber) 20%, var(--au-panel)) !important; color: var(--au-amber-fg) !important; }
    .au-step-danger .au-step-dot { border-color: var(--au-red) !important; background: color-mix(in oklab, var(--au-red) 20%, var(--au-panel)) !important; color: var(--au-red-fg) !important; }
    .au-step-label { display: block; font-size: .8125rem; font-weight: 750; color: var(--au-text); }
    .au-step-todo .au-step-label { color: var(--au-text-soft); font-weight: 600; }
    .au-step-sub { display: block; margin-top: .25rem; font-size: .6875rem; color: var(--au-muted); font-variant-numeric: tabular-nums; }

    /* KPI-rij en KPI-tegel */
    .au-kpi-row { display: grid; gap: 1.0625rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 40rem) { .au-kpi-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 80rem) { .au-kpi-row { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .au-kpi:not([class*="au-tone-"]) { --au-tone: var(--au-mint); --au-tone-fg: var(--au-mint-fg); }
    .au-kpi {
        position: relative; display: flex; flex-direction: column; min-width: 0; min-height: 8.25rem; padding: 1.125rem;
        border: 1px solid var(--au-line); border-radius: 13px; overflow: hidden; color: var(--au-text); text-decoration: none;
        background: var(--au-card-bg); box-shadow: var(--au-shadow); transition: border-color .15s, box-shadow .15s;
    }
    :root.dark .au-kpi { background: linear-gradient(137deg, #132734, #0C1A27 70%); border-color: #264353; }
    .au-kpi::before {
        content: ''; position: absolute; width: 10.625rem; height: 10.625rem; inset-inline-end: -3.125rem; top: -3.125rem; border-radius: 50%; pointer-events: none;
        background: radial-gradient(circle, color-mix(in oklab, var(--au-tone) 34%, transparent), transparent 66%);
    }
    .au-kpi::after {
        content: ''; position: absolute; inset-inline: 0; top: 0; height: 3px; pointer-events: none;
        background: linear-gradient(90deg, var(--au-tone), color-mix(in oklab, var(--au-tone) 25%, transparent));
    }
    a.au-kpi:hover { border-color: color-mix(in oklab, var(--au-tone) 55%, transparent); box-shadow: var(--au-shadow-pop); }
    a.au-kpi:focus-visible { outline: 2px solid var(--au-tone); outline-offset: 2px; }
    .au-kpi > * { position: relative; }
    .au-kpi-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .8125rem; color: var(--au-text-soft); }
    .au-kpi-label { min-width: 0; font-weight: 650; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .au-kpi-icon { display: grid; place-items: center; width: 2rem; height: 2rem; flex: none; border-radius: var(--au-radius-sm); color: var(--au-tone-fg); background: color-mix(in oklab, var(--au-tone) 18%, transparent); }
    .au-kpi-icon .fi-icon, .au-kpi-icon svg { width: 1.125rem; height: 1.125rem; }
    .au-kpi-value { margin: .6875rem 0 .375rem; font-size: 1.875rem; line-height: 1.15; font-weight: 850; letter-spacing: -.035em; font-variant-numeric: tabular-nums; }
    .au-kpi-foot { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; margin-top: auto; font-size: .75rem; color: var(--au-muted); }
    .au-kpi-delta { display: inline-flex; align-items: center; gap: .1875rem; font-weight: 700; color: var(--au-text-soft); }
    .au-kpi-delta-up { color: var(--au-mint-fg); }
    .au-kpi-delta-down { color: var(--au-red-fg); }
    .au-kpi-sub { color: var(--au-muted); }
    .au-kpi-delta-icon { width: .875rem; height: .875rem; }

    /* Prioriteitenlijst */
    .au-pq { margin: 0; padding: 0; list-style: none; }
    .au-pq-row + .au-pq-row { border-top: 1px solid var(--au-line); }
    .au-pq-item { display: flex; align-items: center; gap: .6875rem; padding: .75rem 1.25rem; color: var(--au-text); text-decoration: none; transition: background-color .14s; }
    .au-pq-item:hover { background-color: var(--au-hover); }
    .au-pq-item:focus-visible { outline: 2px solid var(--au-accent-solid); outline-offset: -2px; }
    .au-pq-mark { flex: none; width: .5rem; height: .5rem; border-radius: 50%; background: var(--au-tone); box-shadow: 0 0 10px color-mix(in oklab, var(--au-tone) 45%, transparent); }
    .au-pq-text { display: grid; gap: .25rem; min-width: 0; flex: 1; }
    .au-pq-title { font-size: .8125rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .au-pq-sub { font-size: .75rem; color: var(--au-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .au-pq-chevron { width: 1rem; height: 1rem; flex: none; color: var(--au-quiet); }
    .au-pq-empty { display: flex; align-items: center; gap: .5rem; margin: 0; padding: 1.25rem; font-size: .8125rem; color: var(--au-muted); }
    .au-pq-empty-icon { width: 1.25rem; height: 1.25rem; color: var(--au-accent); }

    /* Infoveld */
    .au-info-row { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; padding: .3125rem 0; font-size: .8125rem; }
    .au-info-label { color: var(--au-muted); }
    .au-info-value { color: var(--au-text); font-weight: 650; text-align: end; font-variant-numeric: tabular-nums; min-width: 0; overflow-wrap: anywhere; }

    /* Tijdlijn */
    .au-timeline { margin: 0; padding: .5rem 1.1875rem; list-style: none; }
    .au-tl-item { display: flex; gap: .75rem; padding: .8125rem 0 .875rem; }
    .au-tl-item + .au-tl-item { border-top: 1px solid var(--au-line); }
    .au-tl-pin { flex: none; width: 9px; height: 9px; margin-top: .25rem; border: 2px solid var(--au-tone); border-radius: 50%; background: color-mix(in oklab, var(--au-tone) 18%, var(--au-panel)); }
    .au-tl-text { display: grid; gap: .3125rem; min-width: 0; flex: 1; }
    .au-tl-title { font-size: .8125rem; font-weight: 700; }
    .au-tl-sub { font-size: .75rem; color: var(--au-muted); }
    .au-tl-time { flex: none; font-size: .6875rem; color: var(--au-quiet); white-space: nowrap; font-variant-numeric: tabular-nums; }
    .au-timeline-empty { padding: 1.25rem; margin: 0; }

    /* ================================================================ */
    /* 15. Hulpklassen voor andere views                                  */
    /* ================================================================ */
    .au-stack { display: grid; gap: 1rem; align-content: start; min-width: 0; }
    .au-stack-sm { display: grid; gap: .5rem; align-content: start; min-width: 0; }
    .au-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; min-width: 0; }
    .au-grid-2-1, .au-grid-2, .au-grid-3, .au-grid-4 { display: grid; gap: 1.0625rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 64rem) {
        .au-grid-2-1 { grid-template-columns: minmax(0, 1.65fr) minmax(18rem, 1fr); }
        .au-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .au-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (min-width: 40rem) { .au-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 80rem) { .au-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .au-muted { color: var(--au-muted); }
    .au-quiet { color: var(--au-quiet); }
    .au-accent { color: var(--au-accent); }
    .au-num { font-variant-numeric: tabular-nums; }
    .au-overline { font-size: .6875rem; font-weight: 750; letter-spacing: .1em; text-transform: uppercase; color: var(--au-muted); }
    .au-value-lg { font-size: 1.0625rem; font-weight: 750; letter-spacing: -.01em; color: var(--au-text); }
    .au-link { display: inline-flex; align-items: center; gap: .25rem; font-size: .8125rem; font-weight: 700; color: var(--au-accent); text-decoration: none; }
    .au-link:hover { text-decoration: underline; }
    .au-link:focus-visible { outline: 2px solid var(--au-accent-solid); outline-offset: 2px; border-radius: 3px; }
    .au-divider { height: 1px; margin: 1rem 0; border: 0; background: var(--au-line); }
    .au-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

    /* ================================================================ */
    /* 16. Toegankelijkheid                                               */
    /* ================================================================ */
    .fi-sidebar-item-btn:focus-visible { outline: 2px solid var(--primary-400); outline-offset: 1px; }
    .fi-tabs-item:focus-visible, .fi-icon-btn:focus-visible, .fi-pagination-item-btn:focus-visible, .dashed-navsearch-trigger:focus-visible { outline: 2px solid var(--au-accent-solid); outline-offset: 1px; }
    @media (prefers-reduced-motion: reduce) {
        .fi-btn, .fi-sidebar-item-btn, .fi-ta-row, .fi-input-wrp, .au-kpi, .au-pq-item { transition: none !important; }
    }

    /* Werkdag-banner (dashboard) en losse Aurora-knoppen */
    .au-workday {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;
        padding: 1rem 1.25rem; border-radius: var(--au-radius);
        border: 1px solid var(--au-accent-line);
        background: linear-gradient(100deg, color-mix(in oklab, var(--primary-500) 18%, var(--au-panel)), var(--au-panel) 75%);
        box-shadow: var(--au-shadow);
    }
    .au-workday__title { font-size: 1.0625rem; color: var(--au-text); }
    .au-workday__sub { font-size: .8125rem; color: var(--au-muted); }
    .au-btn {
        display: inline-flex; align-items: center; gap: .5rem; padding: .5rem .875rem; border-radius: var(--au-radius-sm);
        font-size: .875rem; font-weight: 600; text-decoration: none; border: 1px solid var(--au-line);
        background: var(--au-panel); color: var(--au-text); transition: filter .15s ease, box-shadow .15s ease;
    }
    .au-btn:hover { background: var(--au-hover); }
    .au-btn--primary {
        border-color: transparent; color: var(--au-on-mint) !important;
        background: linear-gradient(180deg, var(--primary-400), var(--primary-500)) !important;
        box-shadow: inset 0 1px 0 rgb(255 255 255 / .25), 0 8px 20px -8px var(--au-accent-glow);
    }
    .au-btn--primary:hover { filter: brightness(1.06); }
    .au-btn:focus-visible { outline: 2px solid var(--au-accent-solid); outline-offset: 2px; }
</style>
