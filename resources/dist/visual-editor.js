(function () {
    var cfg = window.dashedVisualEditor;
    if (!cfg || !cfg.active) return;

    var overlay = null;

    function buildUrl(el) {
        var d = el.dataset;
        return cfg.blockEditorUrl
            + '?model_type=' + encodeURIComponent(d.modelType)
            + '&model_id=' + encodeURIComponent(d.modelId)
            + '&locale=' + encodeURIComponent(d.locale || cfg.locale)
            + '&block=' + encodeURIComponent(d.block);
    }

    function closeOverlay() {
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
        overlay = null;
    }

    function openOverlay(url) {
        closeOverlay();
        overlay = document.createElement('div');
        overlay.className = 'dashed-ve-overlay';
        overlay.innerHTML =
            '<div class="dashed-ve-overlay-backdrop" data-ve-close></div>' +
            '<div class="dashed-ve-overlay-panel">' +
                '<button type="button" class="dashed-ve-overlay-close" data-ve-close aria-label="Sluiten">&times;</button>' +
                '<iframe class="dashed-ve-overlay-frame" src="' + url + '"></iframe>' +
            '</div>';
        overlay.querySelectorAll('[data-ve-close]').forEach(function (b) {
            b.addEventListener('click', closeOverlay);
        });
        document.body.appendChild(overlay);
    }

    document.querySelectorAll('[data-dashed-block]').forEach(function (el) {
        el.classList.add('dashed-ve-block');
        el.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openOverlay(buildUrl(el));
        });
    });

    window.addEventListener('message', function (e) {
        if (e.origin !== window.location.origin) return;
        if (e.data === 'dashed-block-saved') {
            closeOverlay();
            window.location.reload();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeOverlay();
    });
})();
