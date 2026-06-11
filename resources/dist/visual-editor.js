(function () {
    var cfg = window.dashedVisualEditor;
    if (!cfg || !cfg.active) return;

    var dirty = {}; // key -> {block, field, value, fieldtype, modelType, modelId, locale}

    function key(el) {
        return [el.dataset.modelType, el.dataset.modelId, el.dataset.locale, el.dataset.block, el.dataset.field].join('|');
    }

    document.querySelectorAll('[data-dashed-editable]').forEach(function (el) {
        el.setAttribute('contenteditable', 'true');
        el.classList.add('dashed-ve-editable');
        el.addEventListener('input', function () {
            dirty[key(el)] = {
                block: parseInt(el.dataset.block, 10),
                field: el.dataset.field,
                fieldtype: el.dataset.fieldtype || 'text',
                value: el.dataset.fieldtype === 'rich' ? el.innerHTML : el.innerText,
                modelType: el.dataset.modelType,
                modelId: el.dataset.modelId,
                locale: el.dataset.locale,
            };
        });
    });

    var saveBtn = document.querySelector('[data-ve-save]');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            var changes = Object.values(dirty);
            if (!changes.length) return;

            // Group by model (model_type+model_id+locale); send one request per group.
            var groups = {};
            changes.forEach(function (c) {
                var gk = c.modelType + '|' + c.modelId + '|' + c.locale;
                (groups[gk] = groups[gk] || { model_type: c.modelType, model_id: c.modelId, locale: c.locale, changes: [] })
                    .changes.push({ block: c.block, field: c.field, value: c.value, fieldtype: c.fieldtype });
            });

            saveBtn.disabled = true;
            Promise.all(Object.values(groups).map(function (payload) {
                return fetch(cfg.saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
            })).then(function () {
                dirty = {};
                saveBtn.disabled = false;
                saveBtn.textContent = 'Opgeslagen';
                setTimeout(function () { saveBtn.textContent = 'Opslaan'; }, 1500);
            }).catch(function () {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Fout bij opslaan';
            });
        });
    }
})();
