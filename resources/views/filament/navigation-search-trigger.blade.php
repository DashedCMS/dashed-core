{{-- Zoekveld bovenaan de zijbalk; opent de zoekpopup (navigation-search). --}}
<div class="dashed-navsearch-trigger-wrap" x-data x-show="$store.sidebar?.isOpen ?? true">
    <style>
        .dashed-navsearch-trigger-wrap { padding: 0 0 12px; }
        .dashed-navsearch-trigger { display: flex; width: 100%; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 8px; border: 1px solid rgb(0 0 0 / .1); background: transparent; color: inherit; font-size: 14px; opacity: .8; cursor: pointer; text-align: left; }
        .dashed-navsearch-trigger:hover { opacity: 1; border-color: rgb(0 0 0 / .2); }
        .dark .dashed-navsearch-trigger { border-color: rgb(255 255 255 / .12); }
        .dashed-navsearch-trigger svg { width: 16px; height: 16px; opacity: .6; flex: none; }
        .dashed-navsearch-trigger span { flex: 1; }
        .dashed-navsearch-trigger kbd { font-family: inherit; font-size: 11px; padding: 0 5px; border-radius: 4px; border: 1px solid rgb(127 127 127 / .35); }
    </style>
    <button type="button" class="dashed-navsearch-trigger" x-on:click="$dispatch('dashed-navigation-search')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <span>{{ __('Zoeken in menu') }}</span>
        <kbd>/</kbd>
    </button>
</div>
