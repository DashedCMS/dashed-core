<?php

namespace Dashed\DashedCore\Classes;

/**
 * Tracking- en meetscripts pas laden na de eerste interactie van de bezoeker (of 3 seconden
 * na load). De <script>-tags krijgen type="text/plain" en data-dashed-delayed; de lader in
 * de head zet ze op dat moment terug om. Stubs zoals fbq() en dataLayer blijven direct
 * beschikbaar, dus events die intussen worden gestuurd staan in de wachtrij.
 */
class DelayedScripts
{
    public static function rewrite(?string $html): string
    {
        $html = (string) $html;

        if ($html === '' || stripos($html, '<script') === false) {
            return $html;
        }

        return (string) preg_replace_callback('/<script\b([^>]*)>/i', function (array $match): string {
            $attributes = $match[1];

            if (preg_match('/data-dashed-delayed/i', $attributes)) {
                return $match[0];
            }

            if (preg_match('/\stype\s*=\s*["\']?([^"\'\s>]+)/i', $attributes, $type)) {
                $javascriptTypes = ['text/javascript', 'application/javascript', 'module', 'text/ecmascript', 'application/ecmascript'];

                if (! in_array(strtolower($type[1]), $javascriptTypes, true)) {
                    return $match[0];
                }

                $attributes = preg_replace('/\stype\s*=\s*["\']?[^"\'\s>]+["\']?/i', '', $attributes);
            }

            return '<script type="text/plain" data-dashed-delayed'.$attributes.'>';
        }, $html);
    }

    /**
     * De lader zelf: één keer per pagina in de head, vóór de scripts die hij moet activeren.
     */
    public static function loader(): string
    {
        return <<<'JS'
<script>
(function () {
    var done = false;
    var events = ['pointerdown', 'keydown', 'touchstart', 'scroll', 'wheel'];
    function run() {
        if (done) { return; }
        done = true;
        events.forEach(function (e) { window.removeEventListener(e, run); });
        (window.dashedDelayedLoaders || []).forEach(function (f) { try { f(); } catch (e) {} });
        window.dashedDelayedLoaders = { push: function (f) { try { f(); } catch (e) {} } };
        document.querySelectorAll('script[data-dashed-delayed]').forEach(function (s) {
            var n = document.createElement('script');
            for (var i = 0; i < s.attributes.length; i++) {
                var a = s.attributes[i];
                if (a.name === 'type' || a.name === 'data-dashed-delayed') { continue; }
                n.setAttribute(a.name, a.value);
            }
            if (! s.src) { n.text = s.textContent; }
            s.parentNode.replaceChild(n, s);
        });
    }
    events.forEach(function (e) { window.addEventListener(e, run, { passive: true }); });
    window.addEventListener('load', function () { setTimeout(run, 3000); });
    window.dashedLoadDelayedScripts = run;
})();
</script>
JS;
    }
}
