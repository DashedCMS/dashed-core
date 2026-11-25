{{--
    Speldjes per menu-item: het eerste zet het item bovenaan zijn kopje, het
    tweede helemaal bovenaan het menu, direct onder Dashboard en Mijn taken;
    allebei per beheerder (zie NavigationUsage). Een item dat helemaal bovenaan
    staat heeft alleen de knop om het daar weer weg te halen. Eigen script en
    stijlblok, net als de zoekpopup, omdat niet elk klantproject de views uit
    vendor/dashed in zijn Tailwind-build meeneemt.
--}}
@php
    $pinUrl = route('filament.' . \Filament\Facades\Filament::getCurrentOrDefaultPanel()->getId() . '.navigation.pin');
@endphp
<style>
    .fi-sidebar-group-items > .fi-sidebar-item { position: relative; }
    .dashed-nav-pins {
        position: absolute; inset-block: 0; inset-inline-end: .25rem; margin-block: auto;
        display: flex; align-items: center; gap: .125rem; height: 1.75rem;
    }
    .dashed-nav-pin {
        display: none; align-items: center; justify-content: center;
        width: 1.75rem; height: 1.75rem; border-radius: .375rem;
        color: inherit; opacity: .55; background: transparent; cursor: pointer;
    }
    .dashed-nav-pin svg { width: 1rem; height: 1rem; }
    .dashed-nav-pin:hover { opacity: 1; background: rgba(127, 127, 127, .15); }
    .fi-sidebar-group-items > .fi-sidebar-item:hover .dashed-nav-pin,
    .fi-sidebar-group-items > .fi-sidebar-item:focus-within .dashed-nav-pin,
    .dashed-nav-pin.dashed-nav-pin-active { display: flex; }
    .dashed-nav-pin.dashed-nav-pin-active { opacity: .9; }
    /* Ruimte voor de speldjes op elk item dat ze krijgt, ook als ze pas bij
       hover verschijnen; !important omdat de Aurora-stijl de padding van
       .fi-sidebar-item-btn met !important vastzet. */
    .fi-sidebar-group-items > .dashed-nav-has-pin > .fi-sidebar-item-btn { padding-inline-end: 2.25rem !important; }
    .fi-sidebar-group-items > .dashed-nav-has-pins > .fi-sidebar-item-btn { padding-inline-end: 4rem !important; }
</style>
<script>
    (() => {
        const pinned = new Set(@json($pinned));
        const pinnedTop = new Set(@json($pinnedTop));
        const anchors = new Set(@json($anchors));
        const url = @json($pinUrl);
        const label = {
            pin: @json(__('Bovenaan in dit kopje vastpinnen')),
            unpin: @json(__('Losmaken')),
            pinTop: @json(__('Helemaal bovenaan vastpinnen')),
            unpinTop: @json(__('Niet meer helemaal bovenaan')),
        };
        const pinIcon = (filled) => `<svg viewBox="0 0 24 24" fill="${filled ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4h6l-1 5 3 3v2H7v-2l3-3-1-5z"/><path d="M12 14v6"/></svg>`;
        const topIcon = (active) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${active ? '2.4' : '1.8'}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h14"/><path d="M12 20V9"/><path d="m7 13 5-5 5 5"/></svg>`;
        const keyFor = (href) => {
            try { return '/' + new URL(href, location.origin).pathname.replace(/^\/+|\/+$/g, ''); } catch (e) { return null; }
        };

        const makeButton = (link, { active, title, icon, top }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dashed-nav-pin' + (active ? ' dashed-nav-pin-active' : '');
            button.title = title;
            button.setAttribute('aria-label', title);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.innerHTML = icon;
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                button.disabled = true;
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({ url: link.getAttribute('href'), top }),
                    });
                    if (response.ok) { location.reload(); return; }
                } catch (e) {}
                button.disabled = false;
            });

            return button;
        };

        const decorate = () => {
            document.querySelectorAll('.fi-sidebar-group-items > .fi-sidebar-item > a.fi-sidebar-item-btn[href]').forEach((link) => {
                const item = link.parentElement;
                if (item.querySelector(':scope > .dashed-nav-pins')) return;

                const key = keyFor(link.getAttribute('href'));
                const wrapper = document.createElement('span');
                wrapper.className = 'dashed-nav-pins';

                if (pinnedTop.has(key)) {
                    // Staat helemaal bovenaan: alleen terug naar het eigen kopje.
                    wrapper.appendChild(makeButton(link, { active: true, title: label.unpinTop, icon: topIcon(true), top: true }));
                } else {
                    const isPinned = pinned.has(key);
                    wrapper.appendChild(makeButton(link, { active: isPinned, title: isPinned ? label.unpin : label.pin, icon: pinIcon(isPinned), top: false }));

                    // Dashboard en Mijn taken staan al bovenaan.
                    if (! anchors.has(key)) {
                        wrapper.appendChild(makeButton(link, { active: false, title: label.pinTop, icon: topIcon(false), top: true }));
                    }
                }

                item.classList.add(wrapper.children.length > 1 ? 'dashed-nav-has-pins' : 'dashed-nav-has-pin');
                item.appendChild(wrapper);
            });
        };

        decorate();
        document.addEventListener('livewire:navigated', decorate);
    })();
</script>
