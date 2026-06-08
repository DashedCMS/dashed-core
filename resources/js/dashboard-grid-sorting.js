async function ensureSortable() {
    if (window.Sortable) return;
    await new Promise((resolve) => {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
        s.onload = resolve;
        document.head.appendChild(s);
    });
}

window.dashedDashboardGrid = function ({ statePath }) {
    return {
        statePath,
        sortable: null,
        async init() {
            await ensureSortable();
            this.bind();
            // Rebind after every Livewire update (edit-mode toggle, reorder).
            Livewire.hook('morphed', () => this.bind());
        },
        bind() {
            const grid = this.$refs.grid;
            if (!grid) return;
            if (this.sortable) { this.sortable.destroy(); this.sortable = null; }
            // Only draggable in edit-mode (handles present).
            if (!grid.querySelector('.dashed-grid__handle')) return;
            this.sortable = new window.Sortable(grid, {
                handle: '.dashed-grid__handle',
                animation: 150,
                onEnd: () => {
                    const ids = Array.from(grid.children)
                        .map((el) => el.dataset.id)
                        .filter(Boolean);
                    this.$wire.call('reorder', ids);
                },
            });
        },
    };
};
