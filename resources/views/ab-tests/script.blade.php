<style>
    .dab-test, .dab-variant { display: contents; }
    .dab-variant[hidden] { display: none !important; }
</style>
<script>
    (function () {
        if (window.dashedAbTests) {
            return;
        }
        window.dashedAbTests = true;

        var endpoint = @json(route('dashed.ab-tests.event'));
        var storeKey = 'dashed_ab';

        function read() {
            try {
                return JSON.parse(window.localStorage.getItem(storeKey) || '{}') || {};
            } catch (e) {
                return {};
            }
        }

        function write(state) {
            try {
                window.localStorage.setItem(storeKey, JSON.stringify(state));
            } catch (e) {
            }
        }

        function send(test, variant, event) {
            var body = JSON.stringify({test: Number(test), variant: variant, event: event});
            try {
                if (navigator.sendBeacon && navigator.sendBeacon(endpoint, new Blob([body], {type: 'application/json'}))) {
                    return;
                }
            } catch (e) {
            }
            try {
                fetch(endpoint, {method: 'POST', keepalive: true, headers: {'Content-Type': 'application/json'}, body: body});
            } catch (e) {
            }
        }

        // Toewijzingen van tests met een bestel- of formulierdoel gaan als
        // cookie mee naar de server; die koppelt ze aan de order of inzending.
        function writeCookie(state) {
            var parts = [];
            Object.keys(state).forEach(function (id) {
                var entry = state[id];
                if (entry && (entry.g === 'order' || entry.g === 'form') && /^\d+$/.test(id) && (entry.v === 'a' || entry.v === 'b')) {
                    parts.push(id + entry.v);
                }
            });
            document.cookie = 'dashed_ab=' + parts.join('.') + '; path=/; max-age=' + (parts.length ? 2592000 : 0) + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        }

        function show(el, variant) {
            el.querySelectorAll(':scope > [data-dab-variant]').forEach(function (child) {
                child.hidden = child.getAttribute('data-dab-variant') !== variant;
            });
        }

        function run() {
            var state = read();

            document.querySelectorAll('[data-dab-test]').forEach(function (el) {
                if (el.getAttribute('data-dab-started')) {
                    return;
                }
                el.setAttribute('data-dab-started', '1');

                var id = el.getAttribute('data-dab-test');
                var split = Number(el.getAttribute('data-dab-split')) || 50;
                var goal = el.getAttribute('data-dab-goal');
                var entry = state[id];

                if (!entry || (entry.v !== 'a' && entry.v !== 'b')) {
                    entry = {v: Math.random() * 100 < split ? 'b' : 'a'};
                }
                entry.g = goal;
                state[id] = entry;

                show(el, entry.v);

                if (!entry.s) {
                    entry.s = 1;
                    send(id, entry.v, 'view');
                }

                var shown = el.querySelector(':scope > [data-dab-variant="' + entry.v + '"]');
                if (shown) {
                    shown.addEventListener('click', function (event) {
                        var target = event.target && event.target.closest ? event.target.closest('a, button') : null;
                        var current = read();
                        var known = current[id] || entry;
                        if (!target || known.c) {
                            return;
                        }
                        known.c = 1;
                        current[id] = known;
                        write(current);
                        send(id, known.v, 'click');
                    });
                }
            });

            write(state);
            writeCookie(state);
        }

        function start() {
            // Met toestemmingsbeheer alleen na toestemming voor analytics;
            // tot die tijd blijft variant A staan en wordt er niets gemeten.
            if (window.DashedConsent && typeof window.DashedConsent.whenConsented === 'function') {
                window.DashedConsent.whenConsented('analytics', run);
            } else {
                run();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    })();
</script>
