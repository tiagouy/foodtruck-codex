// Read-only integration: run the actual mobile transport/parser against local WordPress.
// Node substitutes only native configuration; it does not emulate iOS/Android networking.
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
const require = createRequire(new URL('../apps/mobile/package.json', import.meta.url));
const ts = require('typescript');
const source = readFileSync(new URL('../apps/mobile/src/lib/api.ts', import.meta.url), 'utf8');
const compiled = ts.transpileModule(source, {compilerOptions: {module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022}}).outputText;
const exports = {};
new Function('require', 'exports', compiled)(name => {
  assert.equal(name, './config'); return {siteURL: () => 'http://localhost:8888/foodtruck'};
}, exports);
let checks = 0;
for (const view of ['upcoming', 'past']) {
  const page = await exports.list('events', 1, view);
  assert.ok(page.total >= 0); checks++;
  if (page.items.length) {
    const detail = await exports.detail('events', page.items[0].slug);
    assert.equal(detail.id, page.items[0].id); checks++;
  }
}
const trucks = await exports.list('foodtrucks'); assert.ok(trucks.total >= 0); checks++;
let photos = []; let pageNumber = 1; let total = 0;
do {
  const page = await exports.list('publications', pageNumber);
  total = page.total; photos = exports.mergeItems(photos, page.items); checks++;
  if (!page.items.length) { break; }
  pageNumber++;
} while ((pageNumber - 1) * 12 < total);
assert.equal(photos.length, total); checks++;
assert.equal(new Set(photos.map(item => item.id)).size, total); checks++;
if (photos.length) {
  const detail = await exports.detail('publications', String(photos[0].id));
  assert.equal(detail.id, photos[0].id); checks++;
  for (const key of ['email', 'legacy_author_id', 'legacy_metadata', 'notes', 'reports']) { assert.equal(key in detail, false); checks++; }
}
await assert.rejects(exports.detail('publications', '999999999'), error => error.status === 404); checks++;
console.log(JSON.stringify({checks, publications: total, upcomingAndHistory: true, publicOnly: true}));
