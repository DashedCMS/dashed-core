{{--
    Zoekpopup voor het menu: "/" of Ctrl/Cmd+K opent hem, ook via het zoekveld
    bovenaan de zijbalk (event dashed-navigation-search). De lijst komt uit
    NavigationSearch::items() en is al op rechten gefilterd; hier wordt alleen
    nog op tekst gefilterd. Eigen stijl in plaats van Tailwind-klassen, want
    Filaments meegeleverde CSS bevat niet elke utility.
--}}
<div
    x-data="{
        open: false,
        query: '',
        active: 0,
        items: @js(\Dashed\DashedCore\Classes\NavigationSearch::items()),
        storageKey: 'dashed-navsearch-history',
        history: {},
        init() {
            this.history = this.readHistory();
            this.recordVisit();
        },
        normalize(text) {
            return (text || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        },
        path(url) {
            try { return new URL(url, window.location.origin).pathname.replace(/\/+$/, ''); } catch (e) { return url; }
        },
        readHistory() {
            // Alleen een gemak per browser: zonder localStorage (privevenster)
            // werkt de popup gewoon, alleen zonder Recent.
            try { return JSON.parse(window.localStorage.getItem(this.storageKey) || '{}') || {}; } catch (e) { return {}; }
        },
        writeHistory() {
            try { window.localStorage.setItem(this.storageKey, JSON.stringify(this.history)); } catch (e) {}
        },
        recordVisit() {
            // De huidige pagina telt voor het item met het langste passende
            // pad, zodat /bestellingen/12/bekijken bij Bestellingen hoort.
            const current = this.path(window.location.href);
            let match = null;
            for (const item of this.items) {
                const itemPath = this.path(item.url);
                if (itemPath && (current === itemPath || current.startsWith(itemPath + '/')) && (! match || itemPath.length > this.path(match.url).length)) {
                    match = item;
                }
            }
            if (! match) return;
            const entry = this.history[match.url] || { count: 0, last: 0 };
            this.history[match.url] = { count: Math.min(entry.count + 1, 1000), last: Date.now() };
            const urls = Object.keys(this.history).sort((a, b) => this.history[b].last - this.history[a].last);
            for (const url of urls.slice(100)) delete this.history[url];
            this.writeHistory();
        },
        visits(item) {
            return (this.history[item.url] || {}).count || 0;
        },
        rank(item) {
            // Gewicht van het pakket eerst, dan hoe vaak je hem opent, dan
            // menu boven instellingen.
            return item.weight * 1000 + Math.min(this.visits(item), 999) + (item.kind === 'menu' ? 0.5 : 0);
        },
        get results() {
            const words = this.normalize(this.query).split(/\s+/).filter(Boolean);
            if (! words.length) {
                const recent = this.items
                    .filter((item) => this.history[item.url])
                    .sort((a, b) => this.history[b.url].last - this.history[a.url].last)
                    .slice(0, 8);
                const recentUrls = new Set(recent.map((item) => item.url));
                const rest = this.items
                    .filter((item) => ! recentUrls.has(item.url))
                    .sort((a, b) => this.rank(b) - this.rank(a));
                return [
                    ...recent.map((item, i) => ({ ...item, section: i === 0 ? @js(__('Recent')) : null })),
                    ...rest.slice(0, 50).map((item, i) => ({ ...item, section: i === 0 && recent.length ? @js(__('Alles')) : null })),
                ];
            }
            return this.items
                .map((item) => {
                    const label = this.normalize(item.label);
                    const haystack = label + ' ' + this.normalize(item.group);
                    if (! words.every((word) => haystack.includes(word))) {
                        return null;
                    }
                    let match = 0;
                    if (label === words.join(' ')) match += 4;
                    if (label.startsWith(words[0])) match += 3;
                    if (words.every((word) => label.includes(word))) match += 2;
                    return { item, score: match * 1000000 + this.rank(item) };
                })
                .filter(Boolean)
                .sort((a, b) => b.score - a.score || a.item.label.localeCompare(b.item.label))
                .slice(0, 50)
                .map((result) => ({ ...result.item, section: null }));
        },
        show() {
            this.open = true;
            this.query = '';
            this.active = 0;
            // Pas focussen als het veld echt zichtbaar is: bij de eerste
            // opening zet de overgang het venster een frame later neer.
            let tries = 0;
            const focus = () => {
                const input = document.querySelector('.dashed-navsearch-input input');
                if (input && input.offsetParent) {
                    input.focus();
                } else if (tries++ < 30) {
                    requestAnimationFrame(focus);
                }
            };
            requestAnimationFrame(focus);
        },
        move(step) {
            const count = this.results.length;
            if (! count) return;
            this.active = (this.active + step + count) % count;
            // Geen refs: die reiken niet over x-teleport heen.
            this.$nextTick(() => document.querySelector('.dashed-navsearch-item[data-active=true]')?.scrollIntoView({ block: 'nearest' }));
        },
        go(item, newTab = false) {
            if (! item) return;
            if (newTab) {
                window.open(item.url, '_blank');
                return;
            }
            window.location.href = item.url;
        },
        typing(event) {
            const el = event.target;
            return el && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName));
        },
    }"
    x-on:dashed-navigation-search.window="show()"
    x-on:keydown.window="
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); show(); return; }
        if (event.key === '/' && ! open && ! typing(event) && ! event.metaKey && ! event.ctrlKey && ! event.altKey) { event.preventDefault(); show(); }
    "
    class="dashed-navsearch"
>
    <style>
        .dashed-navsearch-backdrop { position: fixed; inset: 0; z-index: 60; background: rgb(0 0 0 / .45); display: flex; align-items: flex-start; justify-content: center; padding: 12vh 16px 16px; }
        .dashed-navsearch-panel { width: 100%; max-width: 560px; background: #fff; color: #18181b; border-radius: 14px; box-shadow: 0 20px 50px rgb(0 0 0 / .25); overflow: hidden; }
        .dark .dashed-navsearch-panel { background: #18181b; color: #f4f4f5; }
        .dashed-navsearch-input { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-bottom: 1px solid rgb(0 0 0 / .08); }
        .dark .dashed-navsearch-input { border-color: rgb(255 255 255 / .1); }
        .dashed-navsearch-input input { flex: 1; border: 0; outline: none; background: transparent; font-size: 16px; color: inherit; box-shadow: none; padding: 0; }
        .dashed-navsearch-input svg { width: 20px; height: 20px; opacity: .5; flex: none; }
        .dashed-navsearch-list { max-height: 55vh; overflow-y: auto; padding: 6px; margin: 0; list-style: none; }
        .dashed-navsearch-item { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 9px 12px; border-radius: 9px; cursor: pointer; }
        .dashed-navsearch-item[data-active=true] { background: var(--primary-50, #f4f4f5); color: var(--primary-700, #18181b); }
        .dark .dashed-navsearch-item[data-active=true] { background: rgb(255 255 255 / .08); color: var(--primary-400, #fff); }
        .dashed-navsearch-label { font-weight: 500; }
        .dashed-navsearch-group { font-size: 12px; opacity: .6; white-space: nowrap; }
        .dashed-navsearch-section { margin: 8px 12px 4px; font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; opacity: .5; }
        .dashed-navsearch-empty { padding: 18px 16px; font-size: 14px; opacity: .7; }
        .dashed-navsearch-foot { display: flex; gap: 14px; padding: 8px 16px; font-size: 12px; opacity: .6; border-top: 1px solid rgb(0 0 0 / .08); }
        .dark .dashed-navsearch-foot { border-color: rgb(255 255 255 / .1); }
        .dashed-navsearch kbd { font-family: inherit; font-size: 11px; padding: 1px 5px; border-radius: 4px; border: 1px solid rgb(127 127 127 / .35); }
    </style>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity.duration.100ms
            class="dashed-navsearch dashed-navsearch-backdrop"
            x-on:click.self="open = false"
            x-on:keydown.escape.window="open = false"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Zoeken in het menu') }}"
        >
            <div class="dashed-navsearch-panel">
                <label class="dashed-navsearch-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input
                        x-model="query"
                        x-on:input="active = 0"
                        x-on:keydown.arrow-down.prevent="move(1)"
                        x-on:keydown.arrow-up.prevent="move(-1)"
                        x-on:keydown.enter.prevent="go(results[active], $event.metaKey || $event.ctrlKey)"
                        type="text"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="{{ __('Zoek een pagina of instelling...') }}"
                    >
                </label>

                <div class="dashed-navsearch-list" role="listbox">
                    <template x-for="(item, index) in results" :key="item.url">
                        <div>
                            <p class="dashed-navsearch-section" x-show="item.section" x-text="item.section"></p>
                            <div
                                class="dashed-navsearch-item"
                                role="option"
                                :data-active="index === active"
                                :aria-selected="index === active"
                                x-on:mouseenter="active = index"
                                x-on:click="go(item, $event.metaKey || $event.ctrlKey)"
                            >
                                <span class="dashed-navsearch-label" x-text="item.label"></span>
                                <span class="dashed-navsearch-group" x-text="item.group"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <p class="dashed-navsearch-empty" x-show="! results.length">{{ __('Niets gevonden') }}</p>

                <div class="dashed-navsearch-foot">
                    <span><kbd>↑</kbd> <kbd>↓</kbd> {{ __('kiezen') }}</span>
                    <span><kbd>Enter</kbd> {{ __('openen') }}</span>
                    <span><kbd>Esc</kbd> {{ __('sluiten') }}</span>
                </div>
            </div>
        </div>
    </template>
</div>
