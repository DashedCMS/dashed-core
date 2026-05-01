(function () {
    const TOGGLE_CLASS = 'dashed-editor-fullscreen';
    const BODY_CLASS = 'dashed-editor-fullscreen-active';
    const ATTACHED_FLAG = 'dashedFullscreenAttached';

    const ICON_EXPAND = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9V4.5h4.5M20.25 9V4.5h-4.5M3.75 15v4.5h4.5M20.25 15v4.5h-4.5"/></svg>';
    const ICON_COLLAPSE = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H4.5v4.5M15 3.75h4.5v4.5M9 20.25H4.5v-4.5M15 20.25h4.5v-4.5"/></svg>';

    function buildToggle(wrapper) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dashed-editor-fullscreen-toggle';
        button.title = 'Schermvullend (Esc om te sluiten)';
        button.setAttribute('aria-label', 'Schermvullend');
        button.innerHTML = ICON_EXPAND;

        button.addEventListener('click', function () {
            const isFullscreen = wrapper.classList.toggle(TOGGLE_CLASS);
            document.body.classList.toggle(BODY_CLASS, hasAnyFullscreenEditor());
            button.innerHTML = isFullscreen ? ICON_COLLAPSE : ICON_EXPAND;
            button.title = isFullscreen ? 'Verlaat schermvullende modus' : 'Schermvullend (Esc om te sluiten)';
        });

        return button;
    }

    function hasAnyFullscreenEditor() {
        return !!document.querySelector('.fi-fo-rich-editor.' + TOGGLE_CLASS);
    }

    function enhance(root) {
        const editors = (root || document).querySelectorAll
            ? (root || document).querySelectorAll('.fi-fo-rich-editor')
            : [];

        editors.forEach(function (wrapper) {
            if (wrapper.dataset[ATTACHED_FLAG]) return;
            const toolbar = wrapper.querySelector('.fi-fo-rich-editor-toolbar');
            if (!toolbar) return;

            wrapper.dataset[ATTACHED_FLAG] = '1';
            toolbar.insertBefore(buildToggle(wrapper), toolbar.firstChild);
        });
    }

    function init() {
        enhance(document);

        const observer = new MutationObserver(function (mutations) {
            for (const mutation of mutations) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.classList && node.classList.contains('fi-fo-rich-editor')) {
                        enhance(node.parentNode || node);
                    } else {
                        enhance(node);
                    }
                });
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            const active = document.querySelectorAll('.fi-fo-rich-editor.' + TOGGLE_CLASS);
            if (!active.length) return;
            active.forEach(function (wrapper) {
                wrapper.classList.remove(TOGGLE_CLASS);
                const button = wrapper.querySelector('.dashed-editor-fullscreen-toggle');
                if (button) {
                    button.innerHTML = ICON_EXPAND;
                    button.title = 'Schermvullend (Esc om te sluiten)';
                }
            });
            document.body.classList.toggle(BODY_CLASS, hasAnyFullscreenEditor());
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
