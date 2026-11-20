{{--
    Tellers en effecten in het hele paneel (zie CmsLayout). Eén script voor
    alles, zodat geen enkel pakket er iets voor hoeft te doen:

    - Optellen vanaf nul zodra een cijfer in beeld komt: Filament-stats,
      Aurora-KPI's, elk element met data-dashed-count, en een los groot
      cijfer (text-2xl en groter) waar alleen een getal in staat.
    - Meeschuiven naar de nieuwe waarde als een cijfer verandert (poll,
      Livewire-update, lui geladen widget), met een korte kleurflits:
      groen omhoog, rood omlaag.
    - Badges in menu, tabs en bovenbalk tellen alleen bij een wijziging en
      springen dan even op. data-dashed-count="change" doet hetzelfde voor
      een eigen element.
    - Stattegels en KPI's schuiven bij het laden een voor een omhoog.

    De eindtekst is altijd letterlijk die van de server; tussenstanden worden
    in hetzelfde formaat gezet (zelfde voor- en achtervoegsel, decimalen en
    scheidingstekens). Een tekst met meer dan één getal (een datum, "3 / 10")
    blijft ongemoeid. Met prefers-reduced-motion gebeurt er niets.
--}}
<style>
    @media (prefers-reduced-motion: no-preference) {
        @keyframes dashed-rise {
            from { opacity: 0; transform: translateY(10px) scale(.985); }
            to { opacity: 1; transform: none; }
        }
        @keyframes dashed-flash-up {
            0% { color: var(--au-up, #16a34a); text-shadow: 0 0 18px color-mix(in oklab, var(--au-up, #16a34a) 45%, transparent); }
            100% { text-shadow: none; }
        }
        @keyframes dashed-flash-down {
            0% { color: var(--au-down, #dc2626); text-shadow: 0 0 18px color-mix(in oklab, var(--au-down, #dc2626) 45%, transparent); }
            100% { text-shadow: none; }
        }
        @keyframes dashed-pop {
            0% { transform: scale(1); }
            35% { transform: scale(1.28); }
            65% { transform: scale(.94); }
            100% { transform: scale(1); }
        }

        .fi-wi-stats-overview-stat, .au-kpi { animation: dashed-rise .55s cubic-bezier(.2, .8, .2, 1) both; }
        .fi-wi-stats-overview-stat:nth-child(2), .au-kpi:nth-child(2) { animation-delay: .05s; }
        .fi-wi-stats-overview-stat:nth-child(3), .au-kpi:nth-child(3) { animation-delay: .1s; }
        .fi-wi-stats-overview-stat:nth-child(4), .au-kpi:nth-child(4) { animation-delay: .15s; }
        .fi-wi-stats-overview-stat:nth-child(5), .au-kpi:nth-child(5) { animation-delay: .2s; }
        .fi-wi-stats-overview-stat:nth-child(6), .au-kpi:nth-child(6) { animation-delay: .25s; }
        .fi-wi-stats-overview-stat:nth-child(n+7), .au-kpi:nth-child(n+7) { animation-delay: .3s; }

        .dashed-count { font-variant-numeric: tabular-nums; }
        .dashed-count-up { animation: dashed-flash-up 1.2s ease-out; }
        .dashed-count-down { animation: dashed-flash-down 1.2s ease-out; }
        .dashed-count-pop { display: inline-flex; animation: dashed-pop .5s cubic-bezier(.3, 1.5, .5, 1); }
    }
</style>
<script>
    (() => {
        if (window.__dashedCounters) return;
        window.__dashedCounters = true;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const COUNT_UP = '.fi-wi-stats-overview-stat-value, .au-kpi-value, [data-dashed-count]:not([data-dashed-count="change"])';
        const BIG = '.text-2xl, .text-3xl, .text-4xl, .text-5xl';
        const CHANGE = '.fi-sidebar .fi-badge, .fi-tabs .fi-badge, .fi-topbar .fi-badge, [data-dashed-count="change"]';
        const ALL = COUNT_UP + ', ' + BIG + ', ' + CHANGE;
        // Een los getal met hooguit een kort voor- of achtervoegsel: "€ 1.234,56", "45%", "+12".
        const LONE = /^[^\d\n]{0,4}-?\d[\d.,]*[^\d\n]{0,6}$/;

        const states = new WeakMap();

        const parse = (text) => {
            const tokens = text.match(/-?\d[\d.,]*/g);
            if (!tokens || tokens.length !== 1) return null;
            const token = tokens[0];
            if (/[.,]$/.test(token)) return null;
            const at = text.indexOf(token);
            const negative = token[0] === '-';
            const body = negative ? token.slice(1) : token;
            const lastDot = body.lastIndexOf('.');
            const lastComma = body.lastIndexOf(',');
            let decimal = null;
            let group = null;
            if (lastDot > -1 && lastComma > -1) {
                decimal = lastDot > lastComma ? '.' : ',';
                group = decimal === '.' ? ',' : '.';
            } else if (lastDot > -1 || lastComma > -1) {
                const sep = lastDot > -1 ? '.' : ',';
                const parts = body.split(sep);
                if (parts.length === 2 && parts[1].length !== 3) decimal = sep;
                else group = sep;
            }
            const decimals = decimal ? body.length - body.lastIndexOf(decimal) - 1 : 0;
            let plain = group ? body.split(group).join('') : body;
            if (decimal) plain = plain.replace(decimal, '.');
            const value = parseFloat(plain) * (negative ? -1 : 1);
            if (!isFinite(value)) return null;
            const commaDecimal = decimal === ',' || (!decimal && group === '.');
            const dotDecimal = decimal === '.' || (!decimal && group === ',');
            const locale = commaDecimal ? 'nl-NL' : (dotDecimal ? 'en-US' : (document.documentElement.lang || 'nl'));
            const format = new Intl.NumberFormat(locale, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
                useGrouping: group !== null || !decimal,
            });
            return { value, prefix: text.slice(0, at), suffix: text.slice(at + token.length), format };
        };

        // Dezelfde tekst met een ander getal op de plek van het getal.
        const withValue = (text, parsed, value) => text.replace(/-?\d[\d.,]*/, parsed.format.format(value));

        // Het ene tekstknooppunt met het getal; meerdere of geen = niet animeren.
        const textNode = (el) => {
            const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
            let found = null;
            while (walker.nextNode()) {
                if (/\d/.test(walker.currentNode.nodeValue)) {
                    if (found) return null;
                    found = walker.currentNode;
                }
            }
            return found;
        };

        const write = (el, state, text) => {
            const node = textNode(el);
            if (!node) return;
            state.written = text;
            node.nodeValue = text;
        };

        const animate = (el, state, from, parsed, finalText) => {
            cancelAnimationFrame(state.raf);
            const to = parsed.value;
            const distance = Math.abs(to - from);
            const duration = Math.min(1600, 700 + Math.log10(distance + 1) * 160);
            const start = performance.now();
            const frame = (now) => {
                const p = Math.min(1, Math.max(0, (now - start) / duration));
                const eased = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
                state.shown = from + (to - from) * eased;
                if (p < 1) {
                    write(el, state, withValue(finalText, parsed, state.shown));
                    state.raf = requestAnimationFrame(frame);
                } else {
                    state.shown = to;
                    write(el, state, finalText);
                }
            };
            state.raf = requestAnimationFrame(frame);
        };

        const flash = (el, kind) => {
            const cls = 'dashed-count-' + kind;
            el.classList.remove('dashed-count-up', 'dashed-count-down', 'dashed-count-pop');
            void el.offsetWidth;
            el.classList.add(cls);
            el.addEventListener('animationend', () => el.classList.remove(cls), { once: true });
        };

        const seen = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                seen.unobserve(entry.target);
                const state = states.get(entry.target);
                if (!state || !state.pending) return;
                state.pending = false;
                const node = textNode(entry.target);
                const finalText = state.finalText;
                const parsed = parse(finalText);
                if (!node || !parsed) return;
                animate(entry.target, state, 0, parsed, finalText);
            });
        }, { threshold: 0.4 });

        const mode = (el) => {
            if (el.closest('input, textarea, [contenteditable="true"], .fi-modal, .fi-ta-cell')) return null;
            if (el.matches(CHANGE)) return 'change';
            if (el.matches(COUNT_UP)) return 'up';
            if (el.matches(BIG) && !el.hasAttribute('x-text') && !el.hasAttribute('x-html') && LONE.test(el.textContent.trim())) return 'up';
            return null;
        };

        const register = (el) => {
            const kind = mode(el);
            if (!kind || !textNode(el)) return;
            const text = textNode(el).nodeValue;
            const parsed = parse(text);
            if (!parsed) return;
            const state = { kind, value: parsed.value, shown: parsed.value, written: text, finalText: text, raf: 0, pending: false };
            states.set(el, state);
            el.classList.add('dashed-count');
            if (kind === 'up' && parsed.value !== 0) {
                state.pending = true;
                state.shown = 0;
                write(el, state, withValue(text, parsed, 0));
                seen.observe(el);
            }
        };

        const update = (el) => {
            const state = states.get(el);
            if (!state) return register(el);
            const node = textNode(el);
            if (!node) return;
            const text = node.nodeValue;
            if (text === state.written) return;
            const parsed = parse(text);
            if (!parsed) {
                cancelAnimationFrame(state.raf);
                states.delete(el);
                return;
            }
            state.finalText = text;
            if (state.pending) {
                // Nog niet in beeld geweest: blijf op nul staan tot hij dat wel is.
                state.value = parsed.value;
                write(el, state, withValue(text, parsed, 0));
                return;
            }
            if (parsed.value === state.value) {
                state.written = text;
                return;
            }
            const from = state.shown;
            state.value = parsed.value;
            flash(el, state.kind === 'change' ? 'pop' : (parsed.value > from ? 'up' : 'down'));
            write(el, state, withValue(text, parsed, from));
            animate(el, state, from, parsed, text);
        };

        const dirty = new Set();
        let scheduled = false;
        const flush = () => {
            scheduled = false;
            dirty.forEach((el) => el.isConnected && update(el));
            dirty.clear();
        };
        const mark = (el) => {
            if (!el) return;
            dirty.add(el);
            if (!scheduled) {
                scheduled = true;
                requestAnimationFrame(flush);
            }
        };
        const scan = (root) => {
            if (root.nodeType !== 1) return;
            if (root.matches(ALL)) mark(root);
            root.querySelectorAll(ALL).forEach(mark);
        };

        new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                const target = m.target.nodeType === 1 ? m.target : m.target.parentElement;
                const owner = target && target.closest(ALL);
                if (owner) mark(owner);
                m.addedNodes.forEach(scan);
            });
        }).observe(document.body, { subtree: true, childList: true, characterData: true });

        // Direct en niet via de volgende frame, anders staat de eindwaarde één frame in beeld.
        document.body.querySelectorAll(ALL).forEach(register);
    })();
</script>
