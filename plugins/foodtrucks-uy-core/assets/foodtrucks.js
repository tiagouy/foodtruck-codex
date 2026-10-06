(() => {
    'use strict';
    const form = document.querySelector('.ft-truck-form'); if (!form) return;
    form.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', () => {
            const tooLarge = Array.from(input.files || []).some(file => file.size > 5 * 1024 * 1024);
            input.setCustomValidity(tooLarge ? 'La imagen pesa demasiado. Elegí un archivo de hasta 5 MB.' : '');
            if (tooLarge) input.reportValidity();
        });
    });
    const button = form.querySelector('[data-instagram-lookup]');
    const output = form.querySelector('[data-instagram-result]');
    const status = form.querySelector('[data-instagram-status]');
    const field = name => form.elements.namedItem(name);
    field('instagram').addEventListener('input', () => { field('instagram_token').value = ''; output.replaceChildren(); });
    button.addEventListener('click', async () => {
        button.disabled = true; field('instagram_token').value = ''; output.replaceChildren(); status.textContent = 'Consultando el perfil público…';
        try {
            const response = await fetch(ftuyTruck.ajax, {method: 'POST', credentials: 'same-origin', body: new URLSearchParams({action: ftuyTruck.action || 'ftuy_instagram_preview', nonce: ftuyTruck.nonce, instagram: field('instagram').value})});
            const result = await response.json();
            if (!result.success) throw new Error(result.data?.message || 'Instagram no respondió. Completá los datos manualmente.');
            const data = result.data;
            const name = document.createElement('p'); name.textContent = `Nombre propuesto: ${data.name}`; output.append(name);
            if (data.image) { const image = document.createElement('img'); image.src = data.image; image.alt = `Imagen de perfil de ${data.name}`; image.width = 100; image.height = 100; image.referrerPolicy = 'no-referrer'; output.append(image); }
            const use = document.createElement('button'); use.type = 'button'; use.className = 'button'; use.textContent = data.image ? 'Usar nombre y foto como logo' : 'Usar este nombre';
            use.addEventListener('click', () => {
                field('name').value = data.name; field('instagram').value = data.instagram; field('instagram_token').value = data.image ? data.token : '';
                status.textContent = data.image ? 'Datos elegidos. El logo se guardará al guardar la ficha; revisá el nombre antes de continuar.' : 'Nombre completado. Podés corregirlo y cargar el logo manualmente.';
                use.disabled = true;
            });
            output.append(use); status.textContent = 'Confirmá si estos datos corresponden al negocio. La foto de perfil puede no ser su logo.';
        } catch (e) { status.textContent = e.message || 'No pudimos consultar Instagram. Usá la carga manual.'; }
        finally { button.disabled = false; }
    });
})();
