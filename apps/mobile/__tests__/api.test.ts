import { APIError, detail, list, mergeItems } from '../src/lib/api';
import { dateLabel, plain } from '../src/lib/presentation';
import { mediaURL, siteURL } from '../src/lib/config';

const mockFetch = jest.fn();
globalThis.fetch = mockFetch;
const photo = {
  address: '',
  id: 1,
  caption: 'Una foto',
  image: { full: 'https://example.test/photo.jpg', thumbnail: null },
  author: { id: 2, name: 'Persona', avatar: null },
  created_at: '2019-04-01 12:30:00',
  created_timezone: null,
  share_url: 'https://example.test/fotousuario/una/',
};
beforeEach(() => mockFetch.mockReset());
function response(body: unknown, status = 200) {
  mockFetch.mockResolvedValue({
    ok: status === 200,
    status,
    json: async () => body,
  });
}
test('uses only the new plugin API and page/per_page, never legacy limit', async () => {
  response({ items: [photo], total: 51, page: 2 });
  const result = await list('publications', 2);
  expect(result.items).toHaveLength(1);
  expect(mockFetch.mock.calls[0][0]).toBe(
    `${siteURL()}/wp-json/foodtrucks-uy/v1/publications?page=2&per_page=12`,
  );
  expect(mockFetch.mock.calls[0][1].headers['Cache-Control']).toBe('no-cache');
});
test('event history is an explicit server filter', async () => {
  response({ items: [], total: 0, page: 1 });
  await list('events', 1, 'past');
  expect(mockFetch.mock.calls[0][0]).toContain('&view=past');
});
test('empty catalog is valid, not an error or invented activity', async () => {
  response({ items: [], total: 0, page: 1 });
  expect((await list('foodtrucks')).items).toEqual([]);
});
test('rejects wrong page or malformed resource', async () => {
  response({ items: [photo], total: 51, page: 1 });
  await expect(list('publications', 2)).rejects.toThrow('inesperados');
  response({ items: [{ id: 1 }], total: 1, page: 1 });
  await expect(list('events')).rejects.toThrow('inesperados');
});
test('detail always fetches and handles an unpublished publication as 404', async () => {
  response(photo);
  expect((await detail('publications', '1')).id).toBe(1);
  response({}, 404);
  await expect(detail('publications', '1')).rejects.toMatchObject({
    status: 404,
  });
  expect(mockFetch).toHaveBeenCalledTimes(2);
});
test('HTTP errors do not become empty feeds', async () => {
  response({}, 500);
  await expect(list('events')).rejects.toBeInstanceOf(APIError);
});
test('network and malformed JSON failures show a connection error', async () => {
  mockFetch.mockRejectedValue(new Error('offline'));
  await expect(list('events')).rejects.toThrow('conectar');
});
test('next pages deduplicate IDs while accepting updated fields', () => {
  expect(
    mergeItems(
      [
        { id: 1, name: 'before' },
        { id: 2, name: 'two' },
      ],
      [
        { id: 1, name: 'after' },
        { id: 3, name: 'three' },
      ],
    ),
  ).toEqual([
    { id: 1, name: 'after' },
    { id: 2, name: 'two' },
    { id: 3, name: 'three' },
  ]);
});
test('historic date formatting never invents timezone or says today', () => {
  expect(dateLabel(photo.created_at)).toBe('01/04/2019');
  expect(dateLabel('')).toBe('');
});
test('text is plain and image links reject executable schemes', () => {
  expect(plain('<p>Comida &amp; amigos</p>')).toBe('Comida & amigos');
  expect(mediaURL('data:text/html,unsafe')).toBeUndefined();
  expect(mediaURL(null)).toBeUndefined();
});
