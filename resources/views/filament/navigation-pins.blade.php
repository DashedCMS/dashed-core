{{--
    Speldje per menu-item: vastpinnen zet het item bovenaan zijn kopje, voor
    deze beheerder (zie NavigationUsage). Eigen script en stijlblok, net als de
    zoekpopup, omdat niet elk klantproject de views uit vendor/dashed in zijn
    Tailwind-build meeneemt.
--}}
@php
    $pinUrl = route('filament.' . \Filament\Facades\Filament::getCurrentOrDefaultPanel()->getId() . '.navigation.pin');
@endphp
<style>
    .fi-sidebar-group-items > .fi-sidebar-item { position: relative; }
    .dashed-nav-pin {
        position: absolute; inset-block: 0; inset-inline-end: .25rem; margin-block: auto;
        display: none; align-items: center; justify-content: center;
        width: 1.75rem; height: 1.75rem; border-radius: .375rem;
        color: inherit; opacity: .55; background: transparent; cursor: pointer;
    }
    .dashed-nav-pin svg { width: 1rem; height: 1rem; }
    .dashed-nav-pin:hover { opacity: 1; background: rgba(127, 127, 127, .15); }
    .fi-sidebar-group-items > .fi-sidebar-item:hover > .dashed-nav-pin,
    .fi-sidebar-group-items > .fi-sidebar-item:focus-within > .dashed-nav-pin,
    .dashed-nav-pinned > .dashed-nav-pin { display: flex; }
    .dashed-nav-pinned > .dashed-nav-pin { opacity: .9; }
    .dashed-nav-pinned > .fi-sidebar-item-btn { padding-inline-end: 2.25rem; }
</style>
<script>
    (() => {
        const pinned = new Set(@json($pinned));
        const url = @json($pinUrl);
        const label = { pin: @json(__('Bovenaan vastpinnen')), unpin: @json(__('Losmaken')) };
        const icon = (filled) => `<svg viewBox="0 0 24 24" fill="${filled ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4h6l-1 5 3 3v2H7v-2l3-3-1-5z"/><path d="M12 14v6"/></svg>`;
        const keyFor = (href) => {
            try { return '/' + new URL(href, location.origin).pathname.replace(/^\/+|\/+$/g, ''); } catch (e) { return null; }
        };

        const decorate = () => {
            document.querySelectorAll('.fi-sidebar-group-items > .fi-sidebar-item > a.fi-sidebar-item-btn[href]').forEach((link) => {
                const item = link.parentElement;
                if (item.querySelector(':scope > .dashed-nav-pin')) return;

                const isPinned = pinned.has(keyFor(link.getAttribute('href')));
                item.classList.toggle('dashed-nav-pinned', isPinned);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'dashed-nav-pin';
                button.title = isPinned ? label.unpin : label.pin;
                button.setAttribute('aria-label', button.title);
                button.innerHTML = icon(isPinned);
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
                            body: JSON.stringify({ url: link.getAttribute('href') }),
                        });
                        if (response.ok) { location.reload(); return; }
                    } catch (e) {}
                    button.disabled = false;
                });
                item.appendChild(button);
            });
        };

        decorate();
        document.addEventListener('livewire:navigated', decorate);
    })();
</script>
