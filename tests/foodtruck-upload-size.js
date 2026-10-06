const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const input = {
    files: [], validity: '', reports: 0,
    addEventListener(event, handler) { this.change = handler; },
    setCustomValidity(message) { this.validity = message; },
    reportValidity() { this.reports++; }
};
const stub = { addEventListener() {} };
const form = { querySelectorAll: () => [input], querySelector: () => stub, elements: { namedItem: () => stub } };
vm.runInNewContext(fs.readFileSync(require.resolve('../plugins/foodtrucks-uy-core/assets/foodtrucks.js'), 'utf8'), { document: { querySelector: () => form } });
input.files = [{ size: 5 * 1024 * 1024 + 1 }]; input.change();
assert.equal(input.validity, 'La imagen pesa demasiado. Elegí un archivo de hasta 5 MB.');
assert.equal(input.reports, 1);
input.files = [{ size: 5 * 1024 * 1024 }]; input.change();
assert.equal(input.validity, '');
input.files = []; input.change(); assert.equal(input.validity, '');
console.log('OK: validación de peso y limpieza del error en navegador.');
