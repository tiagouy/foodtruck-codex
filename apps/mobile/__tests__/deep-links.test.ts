import { photoLinkTarget, photoLinking } from '../src/lib/deep-links';

test.each(['fotousuario/p-75/', '/fotousuario/p-75'])('canonical photo %s', path => {
  expect(photoLinkTarget(path)).toEqual({ slug: 'p-75' });
});
test('legacy numeric slug remains a slug, not a new ID', () => {
  expect(photoLinkTarget('fotosusuarios/oferta.php?slug=123')).toEqual({
    slug: '123',
  });
});
test('list and a photo named list are distinct', () => {
  expect(photoLinkTarget('/fotosusuarios/')).toEqual({ list: true });
  expect(photoLinkTarget('/fotousuario/list/')).toEqual({ slug: 'list' });
});
test.each([
  'elegir-contrasena/?key=secret&login=person',
  'fotousuario/%2Fprivate/',
  'fotousuario/%zz/',
  'fotousuario/../',
  'fotosusuarios/oferta.php?slug=one&slug=two',
  'fotousuario/' + 'a'.repeat(201),
  'wp-admin/',
])('rejects invalid/private route %s', path => {
  expect(photoLinkTarget(path)).toBeUndefined();
});
test('photo detail has a Fotos screen behind it for back navigation', () => {
  const state = photoLinking.getStateFromPath?.('fotousuario/p-75/');
  expect(state?.routes).toEqual([
    { name: 'Principal', state: { routes: [{ name: 'Fotos' }] } },
    {
      name: 'Detalle',
      params: {
        kind: 'publications',
        contentKey: 'p-75',
        publicationSlug: true,
      },
    },
  ]);
});
