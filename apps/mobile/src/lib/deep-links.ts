import type { LinkingOptions } from '@react-navigation/native';
import type { RootStack } from '../navigation';

// Numeric historical slugs are NOT current publication IDs: resolve them on the server.
export function photoLinkTarget(
  path: string,
): { slug: string } | { list: true } | undefined {
  if (path.length > 1000 || path.includes('#')) {
    path = path.split('#')[0];
  }
  if (path.length > 1000) {
    return undefined;
  }
  const [rawPath, query = ''] = path.split('?');
  const route = rawPath.replace(/^\/+|\/+$/g, '');
  if (route === 'fotosusuarios' || route === 'fotosusuarios/index.php') {
    return { list: true };
  }
  let slug: string | undefined;
  if (route === 'fotosusuarios/oferta.php') {
    const values = query.split('&').filter(pair => pair.split('=')[0] === 'slug');
    if (values.length !== 1) {
      return undefined;
    }
    slug = values[0].slice('slug='.length);
  } else {
    slug = /^fotousuario\/([^/]+)$/.exec(route)?.[1];
  }
  if (!slug) {
    return undefined;
  }
  try {
    slug = decodeURIComponent(slug);
  } catch {
    return undefined;
  }
  return /^[a-zA-Z0-9-]{1,200}$/.test(slug) ? { slug } : undefined;
}

export const photoLinking: LinkingOptions<RootStack> = {
  prefixes: ['https://foodtruckuruguay.com'],
  getStateFromPath(path) {
    const target = photoLinkTarget(path);
    if (!target) {
      return undefined;
    }
    const principal = {
      name: 'Principal',
      state: { routes: [{ name: 'Fotos' }] },
    };
    return 'list' in target
      ? { routes: [principal] }
      : {
          index: 1,
          routes: [
            principal,
            {
              name: 'Detalle',
              params: {
                kind: 'publications',
                contentKey: target.slug,
                publicationSlug: true,
              },
            },
          ],
        };
  },
};
