import { siteURL } from './config';

export type ImageURLs = { thumbnail: string | null; full: string | null };
export type Event = {
  id: number;
  slug: string;
  title: string;
  summary: string;
  start_date: string;
  end_date: string;
  start_time: string;
  end_time: string;
  venue: string;
  address: string;
  department: string;
  locality: string;
  temporal_status: string;
  cancelled: boolean;
  image: ImageURLs;
  schedule: { date: string; start: string; end: string }[];
  entry_type: string;
  description?: string;
  instagram?: string;
  website?: string;
  tickets_url?: string;
};
export type Foodtruck = {
  id: number;
  slug: string;
  name: string;
  description: string;
  food_offering: string;
  department: string;
  locality: string;
  instagram: string;
  whatsapp: string;
  serves_events: boolean;
  serves_private_events: boolean;
  has_fixed_location: boolean;
  cuisines: { id: string | number; name: string }[];
  images: { role: string; url: string | null }[];
};
export type Publication = {
  id: number;
  caption: string;
  address: string;
  created_at: string;
  created_timezone: string | null;
  share_url: string;
  image: ImageURLs;
  author: { id: number; name: string; avatar: string | null };
};
export type Page<T> = {
  items: T[];
  total: number;
  page: number;
  pages?: number;
};
export type Kind = 'events' | 'foodtrucks' | 'publications';
export type Content = Event | Foodtruck | Publication;
export class APIError extends Error {
  constructor(message: string, public status = 0) {
    super(message);
  }
}

export function validateContent(kind: Kind, item: unknown): item is Content {
  if (!item || typeof item !== 'object') {
    return false;
  }
  const row = item as Record<string, unknown>;
  if (!Number.isInteger(row.id) || Number(row.id) < 1) {
    return false;
  }
  const strings = (keys: string[]) =>
    keys.every(key => typeof row[key] === 'string');
  const images = row.image as ImageURLs | undefined;
  const validImages =
    images &&
    [images.full, images.thumbnail].every(
      url => url === null || typeof url === 'string',
    );
  if (kind === 'events') {
    return Boolean(
      validImages &&
        strings([
          'slug',
          'title',
          'summary',
          'start_date',
          'end_date',
          'start_time',
          'end_time',
          'venue',
          'address',
          'department',
          'locality',
          'temporal_status',
          'entry_type',
        ]) &&
        typeof row.cancelled === 'boolean' &&
        Array.isArray(row.schedule) &&
        row.schedule.every(
          day =>
            day &&
            ['date', 'start', 'end'].every(key => typeof day[key] === 'string'),
        ),
    );
  }
  if (kind === 'foodtrucks') {
    return (
      strings([
        'slug',
        'name',
        'description',
        'food_offering',
        'department',
        'locality',
        'instagram',
        'whatsapp',
      ]) &&
      ['serves_events', 'serves_private_events', 'has_fixed_location'].every(
        key => typeof row[key] === 'boolean',
      ) &&
      Array.isArray(row.images) &&
      row.images.every(
        image =>
          image &&
          typeof image.role === 'string' &&
          (image.url === null || typeof image.url === 'string'),
      ) &&
      Array.isArray(row.cuisines) &&
      row.cuisines.every(cuisine => cuisine && typeof cuisine.name === 'string')
    );
  }
  const author = row.author as Publication['author'] | undefined;
  return Boolean(
    validImages &&
      strings(['caption', 'address', 'created_at', 'share_url']) &&
      (row.created_timezone === null ||
        typeof row.created_timezone === 'string') &&
      author &&
      Number.isInteger(author.id) &&
      typeof author.name === 'string' &&
      (author.avatar === null || typeof author.avatar === 'string'),
  );
}

export async function request(
  path: string,
  signal?: AbortSignal,
  body?: Record<string, string> | FormData,
  token?: string,
  timeoutMs = 15000,
): Promise<unknown> {
  const controller = new AbortController();
  const abort = () => controller.abort();
  signal?.addEventListener('abort', abort);
  if (signal?.aborted) {
    controller.abort();
  }
  const timeout = setTimeout(abort, timeoutMs);
  const multipart = body instanceof FormData;
  try {
    const response = await fetch(
      `${siteURL()}/wp-json/foodtrucks-uy/v1/${path}`,
      {
        signal: controller.signal,
        credentials: 'omit',
        method: body ? 'POST' : 'GET',
        body: body ? (multipart ? body : JSON.stringify(body)) : undefined,
        headers: {
          Accept: 'application/json',
          'Cache-Control': 'no-cache',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
          ...(body && !multipart ? { 'Content-Type': 'application/json' } : {}),
        },
      },
    );
    if (!response.ok) {
      if (body) {
        let message = 'No pudimos completar la solicitud. Probá nuevamente.';
        try {
          const error = await response.json();
          if (
            typeof error?.message === 'string' &&
            [400, 401, 413, 429, 503].includes(response.status)
          ) {
            message = error.message.replace(/<[^>]*>/g, '').slice(0, 500);
          }
        } catch {
          /* Keep the generic error if the server returned HTML. */
        }
        throw new APIError(message, response.status);
      }
      throw new APIError(
        response.status === 404
          ? 'Este contenido ya no está disponible.'
          : 'No pudimos cargar los datos. Probá nuevamente.',
        response.status,
      );
    }
    return await response.json();
  } catch (error) {
    if (error instanceof APIError) {
      throw error;
    }
    if (signal?.aborted) {
      throw error;
    }
    throw new APIError(
      'No pudimos conectar. Revisá tu conexión y probá nuevamente.',
    );
  } finally {
    clearTimeout(timeout);
    signal?.removeEventListener('abort', abort);
  }
}

export type AccountAction = 'register' | 'reactivate' | 'forgot-password';
export function accountFieldsError(
  action: AccountAction,
  email: string,
  name = '',
): string {
  if (
    email.trim().length > 100 ||
    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())
  ) {
    return 'Ingresá un email válido.';
  }
  if (action === 'register' && (!name.trim() || name.trim().length > 200)) {
    return 'Ingresá tu nombre.';
  }
  return '';
}
export async function accountRequest(
  action: AccountAction,
  email: string,
  name = '',
  signal?: AbortSignal,
): Promise<string> {
  const error = accountFieldsError(action, email, name);
  if (error) {
    throw new APIError(error, 400);
  }
  const body = {
    email: email.trim().toLowerCase(),
    ...(action === 'register' ? { name: name.trim() } : {}),
  };
  const response = (await request(`accounts/${action}`, signal, body)) as {
    message?: unknown;
  };
  if (!response || typeof response.message !== 'string') {
    throw new APIError('El servidor respondió con datos inesperados.');
  }
  // Do not expose or persist identities, credentials or links in the client response.
  return response.message;
}

export async function list<T extends Content>(
  kind: Kind,
  page = 1,
  view = 'upcoming',
  perPage = 12,
  signal?: AbortSignal,
  author?: number,
): Promise<Page<T>> {
  if (
    author !== undefined &&
    (kind !== 'publications' || !Number.isInteger(author) || author < 1)
  ) {
    throw new APIError('No pudimos identificar tus fotos.');
  }
  const value = (await request(
    `${kind}?page=${page}&per_page=${perPage}${
      kind === 'events' ? `&view=${encodeURIComponent(view)}` : ''
    }${author !== undefined ? `&author=${author}` : ''}`,
    signal,
  )) as Page<T>;
  if (
    !value ||
    !Array.isArray(value.items) ||
    !Number.isInteger(value.total) ||
    value.total < 0 ||
    value.page !== page ||
    !value.items.every(item => validateContent(kind, item)) ||
    (author !== undefined &&
      value.items.some(item => (item as Publication).author.id !== author))
  ) {
    throw new APIError('El servidor respondió con datos inesperados.');
  }
  return value;
}
export async function detail(
  kind: Kind,
  key: string,
  signal?: AbortSignal,
): Promise<Content> {
  const value = await request(`${kind}/${encodeURIComponent(key)}`, signal);
  if (!validateContent(kind, value)) {
    throw new APIError('El servidor respondió con datos inesperados.');
  }
  return value;
}
export function mergeItems<T extends { id: number }>(
  previous: T[],
  incoming: T[],
): T[] {
  const rows = new Map(previous.map(row => [row.id, row]));
  incoming.forEach(row => rows.set(row.id, row));
  return [...rows.values()];
}
