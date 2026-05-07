// dashed-core nestable sorting
// Defineert een Alpine x-data component die SortableJS koppelt aan een
// nested <ul>-tree binnen een Filament-modal en de boom-structuur als
// JSON terugschrijft naar een Hidden form-veld.

let sortablePromise = null;

function ensureSortable() {
    if (window.Sortable) return Promise.resolve();
    if (sortablePromise) return sortablePromise;
    sortablePromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load SortableJS'));
        document.head.appendChild(script);
    });
    return sortablePromise;
}

window.dashedNestableSorting = function ({ initial, statePath }) {
    return {
        statePath,
        async init() {
            await ensureSortable();
            this.syncStateFromDom();
            this.$nextTick(() => this.bindAll(this.$el));
        },
        bindAll(root) {
            const lists = root.querySelectorAll(
                'ul.dashed-nestable__root, ul.dashed-nestable__children'
            );
            lists.forEach((list) => {
                if (list.dataset.bound === '1') return;
                list.dataset.bound = '1';
                new window.Sortable(list, {
                    group: 'dashed-nestable',
                    handle: '.dashed-nestable__handle',
                    animation: 150,
                    fallbackOnBody: true,
                    invertSwap: true,
                    emptyInsertThreshold: 8,
                    onEnd: () => this.syncStateFromDom(),
                });
            });
        },
        syncStateFromDom() {
            const root = this.$el.querySelector('ul.dashed-nestable__root');
            if (!root) return;
            const tree = this.serialize(root);
            this.$wire.set(this.statePath, JSON.stringify(tree), false);
        },
        serialize(ul) {
            return Array.from(ul.children)
                .filter((el) => el.tagName === 'LI' && el.dataset.id)
                .map((li) => {
                    const childUl = li.querySelector(
                        ':scope > ul.dashed-nestable__children'
                    );
                    return {
                        id: parseInt(li.dataset.id, 10),
                        children: childUl ? this.serialize(childUl) : [],
                    };
                });
        },
    };
};
