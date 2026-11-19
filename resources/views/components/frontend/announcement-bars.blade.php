{{--
    Actiebalken (AnnouncementBars). Deze HTML zit in de responscache en is
    voor elke bezoeker gelijk: wegklikken en de countdown gebeuren hieronder
    in de browser. Eigen stijlblok met prefix dab-, want klantprojecten
    scannen vendor/dashed niet in hun Tailwind-build.
--}}
@php
    $closeLabel = class_exists(\Dashed\DashedTranslations\Models\Translation::class)
        ? \Dashed\DashedTranslations\Models\Translation::get('close', 'announcement-bar', 'Sluiten')
        : 'Sluiten';
    $dayLabel = class_exists(\Dashed\DashedTranslations\Models\Translation::class)
        ? \Dashed\DashedTranslations\Models\Translation::get('days-short', 'announcement-bar', 'd')
        : 'd';
@endphp
<style>
    .dab-bar{position:relative;display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;padding:8px 44px;font-size:14px;line-height:1.4;text-align:center;font-family:inherit}
    .dab-bar[hidden]{display:none}
    .dab-bar a.dab-link{color:inherit;font-weight:600;text-decoration:underline;text-underline-offset:2px}
    .dab-countdown{font-variant-numeric:tabular-nums;font-weight:700;white-space:nowrap}
    .dab-close{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:transparent;border:0;color:inherit;cursor:pointer;font-size:20px;line-height:1;padding:6px;opacity:.8}
    .dab-close:hover,.dab-close:focus-visible{opacity:1}
</style>
@foreach($bars as $bar)
    @php
        $text = $bar->textFor($locale);
        $url = \Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars::safeUrl($bar->linkUrlFor($locale));
        $label = $bar->linkLabelFor($locale);
        $background = \Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars::color($bar->background_color, \Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars::DEFAULT_BACKGROUND);
        $color = \Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars::color($bar->text_color, \Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars::DEFAULT_TEXT);
    @endphp
    <div class="dab-bar" role="region" aria-label="{{ $text }}"
         data-dab-key="{{ $bar->dismissKey() }}"
         @if($bar->ends_at) data-dab-ends="{{ $bar->ends_at->toIso8601String() }}" @endif
         style="background-color: {{ $background }}; color: {{ $color }};">
        <span class="dab-text">
            @if($url && $label === '')
                <a class="dab-link" href="{{ $url }}">{{ $text }}</a>
            @else
                {{ $text }}
            @endif
        </span>
        @if($bar->countdown && $bar->ends_at)
            <span class="dab-countdown" data-dab-countdown aria-live="off"></span>
        @endif
        @if($url && $label !== '')
            <a class="dab-link" href="{{ $url }}">{{ $label }}</a>
        @endif
        @if($bar->dismissible)
            <button type="button" class="dab-close" data-dab-close aria-label="{{ $closeLabel }}">&times;</button>
        @endif
    </div>
@endforeach
<script>
    (function () {
        var dayLabel = @json($dayLabel);
        var store = {
            get: function (key) { try { return window.localStorage.getItem(key); } catch (e) { return null; } },
            set: function (key) { try { window.localStorage.setItem(key, '1'); } catch (e) {} }
        };
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };

        document.querySelectorAll('.dab-bar[data-dab-key]').forEach(function (bar) {
            var key = bar.getAttribute('data-dab-key');
            if (store.get(key)) {
                bar.hidden = true;
                return;
            }

            var close = bar.querySelector('[data-dab-close]');
            if (close) {
                close.addEventListener('click', function () {
                    store.set(key);
                    bar.hidden = true;
                });
            }

            var ends = bar.getAttribute('data-dab-ends');
            if (!ends) {
                return;
            }
            var end = new Date(ends).getTime();
            var target = bar.querySelector('[data-dab-countdown]');

            // De HTML kan uit de cache komen: een verlopen balk verdwijnt ook
            // als de server hem nog niet heeft weggehaald.
            var tick = function () {
                var left = Math.floor((end - Date.now()) / 1000);
                if (left <= 0) {
                    bar.hidden = true;
                    return false;
                }
                if (target) {
                    var d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
                    target.textContent = (d > 0 ? d + dayLabel + ' ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
                }
                return true;
            };

            if (tick()) {
                var timer = setInterval(function () {
                    if (!tick()) {
                        clearInterval(timer);
                    }
                }, 1000);
            }
        });
    })();
</script>
