/* Shared submission and moderation form. Never stores the Google key in source. */
(() => {
    'use strict';
    const root = document.querySelector('.ft-form-grid');
    if (!root) return;
    const form = root.closest('form');
    const field = name => form.elements.namedItem(name);
    const schedule = field('schedule_json');
    let saved = [];
    try { saved = JSON.parse(schedule.value); } catch (_) { /* Server validates malformed data. */ }
    const daily = root.querySelector('.ft-daily-hours');
    const sameFields = ['start_time', 'end_time'].map(n => root.querySelector(`.ft-field-${n}`));
    const isDaily = () => field('hours_mode').value === 'daily';
    const readRows = () => Array.from(daily.querySelectorAll('.ft-day-row')).map(row => ({date: row.dataset.date, start: row.querySelector('[data-time="start"]').value, end: row.querySelector('[data-time="end"]').value}));
    const sync = () => { schedule.value = JSON.stringify(isDaily() ? readRows().filter(r => r.start || r.end) : []); };
    const render = () => {
        if (daily.children.length) saved = readRows();
        daily.replaceChildren();
        daily.hidden = !isDaily();
        sameFields.forEach(el => { el.hidden = isDaily(); el.querySelector('input').disabled = isDaily(); });
        if (!isDaily()) { sync(); return; }
        const start = field('start_date').value, end = field('end_date').value;
        if (!start || !end || end < start) { daily.textContent = 'Seleccioná las fechas para completar los horarios de cada día.'; return; }
        const cursor = new Date(`${start}T12:00:00Z`), last = new Date(`${end}T12:00:00Z`);
        if ((last - cursor) / 86400000 >= 366) { daily.textContent = 'Para eventos de más de un año, usá el mismo horario o dejalo sin confirmar.'; field('hours_mode').value = 'same'; render(); return; }
        while (cursor <= last) {
            const date = cursor.toISOString().slice(0, 10);
            const previous = saved.find(r => r.date === date) || {start: '', end: ''};
            const row = document.createElement('div'); row.className = 'ft-day-row'; row.dataset.date = date;
            const heading = document.createElement('strong'); heading.textContent = cursor.toLocaleDateString('es-UY', {weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC'}); row.append(heading);
            ['start', 'end'].forEach(kind => {
                const label = document.createElement('label'); label.textContent = kind === 'start' ? 'Apertura' : 'Cierre';
                const input = document.createElement('input'); input.type = 'time'; input.dataset.time = kind; input.value = previous[kind]; input.setAttribute('aria-label', `${label.textContent} ${date}`); input.addEventListener('input', sync); label.append(input); row.append(label);
            });
            daily.append(row); cursor.setUTCDate(cursor.getUTCDate() + 1);
        }
        sync();
    };
    if (saved.length) field('hours_mode').value = 'daily';
    root.querySelectorAll('[name="hours_mode"], [name="start_date"], [name="end_date"]').forEach(el => el.addEventListener('change', render));
    render();
    const ticketField = root.querySelector('.ft-field-tickets_url');
    const updateEntry = () => {
        const paid = field('entry_type').value === 'paid';
        ticketField.hidden = !paid; field('tickets_url').required = paid; field('tickets_url').disabled = !paid;
    };
    root.querySelectorAll('[name="entry_type"]').forEach(el => el.addEventListener('change', updateEntry)); updateEntry();
    form.addEventListener('submit', sync);
    const status = root.querySelector('.ft-place-status');
    const normalize = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/^departamento (de|del) /, '').trim();
    // Clear coordinates if a user replaces the selected address manually.
    field('address').addEventListener('input', () => { field('latitude').value = ''; field('longitude').value = ''; status.textContent = 'Dirección manual: seleccioná una sugerencia de Google para guardar la ubicación exacta.'; });
    window.ftuyPlacesReady = async () => {
        try {
            const {PlaceAutocompleteElement} = await google.maps.importLibrary('places');
            const widget = new PlaceAutocompleteElement({includedRegionCodes: ['uy']});
            widget.setAttribute('aria-label', 'Buscar dirección en Google');
            widget.setAttribute('placeholder', 'Buscá una calle o un lugar en Uruguay');
            document.getElementById('ft-place-widget').append(widget);
            status.textContent = 'Seleccioná una sugerencia para completar dirección, departamento, localidad y ubicación.';
            widget.addEventListener('gmp-error', () => { status.textContent = 'Google no está disponible. Podés completar la dirección manualmente.'; });
            widget.addEventListener('gmp-select', async event => {
                try {
                    const place = event.placePrediction.toPlace();
                    await place.fetchFields({fields: ['displayName', 'formattedAddress', 'location', 'addressComponents']});
                    const components = place.addressComponents || [];
                    const component = type => components.find(c => c.types.includes(type))?.longText || '';
                    if (component('country') !== 'Uruguay') { status.textContent = 'Seleccioná una ubicación de Uruguay.'; return; }
                    const dep = ftuyForm.departments.find(d => normalize(d) === normalize(component('administrative_area_level_1')));
                    field('address').value = place.formattedAddress || '';
                    field('department').value = dep || '';
                    field('locality').value = component('locality') || component('postal_town') || component('administrative_area_level_2') || (dep === 'Montevideo' ? 'Montevideo' : '');
                    field('latitude').value = place.location?.lat() ?? ''; field('longitude').value = place.location?.lng() ?? '';
                    if (!field('venue').value && place.displayName) field('venue').value = place.displayName;
                    status.textContent = 'Ubicación guardada. Revisá el departamento y la localidad antes de enviar.';
                } catch (_) { status.textContent = 'No pudimos consultar ese lugar. Intentá otra búsqueda o completá la dirección manualmente.'; }
            });
        } catch (_) { status.textContent = 'Google no está disponible. Podés completar la dirección manualmente.'; }
    };
    window.gm_authFailure = () => { status.textContent = 'Google necesita habilitar el acceso para este sitio. Por ahora, completá la dirección manualmente.'; };
    if (typeof google !== 'undefined' && google.maps?.importLibrary) { window.ftuyPlacesReady(); }
    else if (ftuyForm.googleKey) {
        const script = document.createElement('script');
        const params = new URLSearchParams({key: ftuyForm.googleKey, loading: 'async', callback: 'ftuyPlacesReady', v: 'weekly', language: 'es', region: 'UY'});
        script.src = `https://maps.googleapis.com/maps/api/js?${params}`;
        script.onerror = () => { status.textContent = 'No se pudo cargar Google. Podés completar la dirección manualmente.'; };
        document.head.append(script);
    }
})();
