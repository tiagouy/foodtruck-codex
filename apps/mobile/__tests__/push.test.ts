const mockStore = new Map<string, string>();
let mockRefresh: (token: string) => void;
const mockMessaging = {
  getToken: jest.fn().mockResolvedValue('fcm-fixture-a'),
  requestPermission: jest.fn().mockResolvedValue(1),
  hasPermission: jest.fn().mockResolvedValue(1),
  registerDeviceForRemoteMessages: jest.fn().mockResolvedValue(undefined),
  onTokenRefresh: jest.fn(callback => {
    mockRefresh = callback;
    return jest.fn();
  }),
};
jest.mock('@react-native-firebase/messaging', () => ({
  __esModule: true,
  default: Object.assign(() => mockMessaging, {
    AuthorizationStatus: { AUTHORIZED: 1, PROVISIONAL: 2 },
  }),
}));
jest.mock('react-native-keychain', () => ({
  ACCESSIBLE: { WHEN_UNLOCKED_THIS_DEVICE_ONLY: 'device' },
  getGenericPassword: jest.fn(async ({ service }) =>
    mockStore.has(service) ? { password: mockStore.get(service) } : false,
  ),
  setGenericPassword: jest.fn(async (_name, password, { service }) => {
    mockStore.set(service, password);
    return true;
  }),
}));
// Never read production/local credentials in tests.
jest.mock(
  '../pushv3.local.json',
  () => ({
    apiUrl: 'https://example.test/api/',
    appId: '12',
    apiKey: 'fixture-key',
    appToken: 'fixture-app',
  }),
  { virtual: true },
);
import {
  initializePush,
  syncPushTokenForUser,
  setupPushTokenRefresh,
  deleteUserPushToken,
  retryPush,
} from '../src/lib/push/pushToken';
import UsefulPush from '../src/lib/push/usefulPush';
const fetchMock = jest.fn();
globalThis.fetch = fetchMock;
function sent() {
  return fetchMock.mock.calls.map(([, options]) =>
    Object.fromEntries(options.body.entries()),
  );
}
beforeEach(() => {
  fetchMock.mockClear();
  fetchMock.mockImplementation(async () => ({
    ok: true,
    json: async () => ({ register: 'yes', update: 'yes', delete: 'yes' }),
  }));
});
test('first launch registers a device without requiring user ID', async () => {
  await initializePush();
  expect(sent()[0]).toMatchObject({
    metodo: 'register',
    token: 'fcm-fixture-a',
    idapp: '12',
  });
  expect(sent()[0]).not.toHaveProperty('user');
});
test('login associates existing token with WordPress user ID', async () => {
  await syncPushTokenForUser('100');
  expect(sent().at(-1)).toMatchObject({
    metodo: 'register',
    token: 'fcm-fixture-a',
    user: '100',
  });
});
test('rotation preserves previous token and then reasserts current user', async () => {
  setupPushTokenRefresh();
  mockRefresh('fcm-fixture-b');
  mockMessaging.getToken.mockResolvedValue('fcm-fixture-b');
  await retryPush();
  expect(sent()[0]).toMatchObject({
    metodo: 'updateToken',
    tokenAnterior: 'fcm-fixture-a',
    token: 'fcm-fixture-b',
    user: '100',
  });
  expect(sent()[1]).toMatchObject({
    metodo: 'register',
    token: 'fcm-fixture-b',
    user: '100',
  });
});
test('failed deletion retains token and identity for retry', async () => {
  fetchMock.mockResolvedValue({
    ok: true,
    json: async () => ({ delete: 'no' }),
  });
  await expect(deleteUserPushToken()).rejects.toThrow('desactivar');
  expect(JSON.parse(mockStore.get('foodtrucks-uy.push')!).userId).toBe('100');
});
test('logout deactivates token and does not register guest again on app launch', async () => {
  await deleteUserPushToken();
  expect(sent()[0]).toMatchObject({
    metodo: 'removeToken',
    user: '100',
    token: 'fcm-fixture-b',
  });
  fetchMock.mockClear();
  await initializePush();
  expect(fetchMock).not.toHaveBeenCalled();
});
test('re-login enables push; malformed response is not accepted and client does not log payloads', async () => {
  const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
  await syncPushTokenForUser('101');
  expect(sent().at(-1)).toMatchObject({ user: '101', token: 'fcm-fixture-b' });
  fetchMock.mockResolvedValue({
    ok: true,
    json: async () => ({ error: 'auth' }),
  });
  expect(await UsefulPush.register('fixture')).toBe(false);
  expect(warn).not.toHaveBeenCalled();
  warn.mockRestore();
});
test('failed rotation keeps tokenAnterior for retry and never falls back to a duplicate registration', async () => {
  fetchMock.mockResolvedValue({
    ok: true,
    json: async () => ({ update: 'no' }),
  });
  mockRefresh('fcm-fixture-c');
  mockMessaging.getToken.mockResolvedValue('fcm-fixture-c');
  await retryPush();
  expect(sent().every(row => row.metodo === 'updateToken')).toBe(true);
  expect(JSON.parse(mockStore.get('foodtrucks-uy.push')!).previous).toBe(
    'fcm-fixture-b',
  );
  fetchMock.mockImplementation(async () => ({
    ok: true,
    json: async () => ({ update: 'yes', register: 'yes' }),
  }));
  await retryPush();
  expect(JSON.parse(mockStore.get('foodtrucks-uy.push')!).previous).toBe('');
});
test('denied permission does not acquire or register a token', async () => {
  mockMessaging.requestPermission.mockResolvedValueOnce(0);
  fetchMock.mockClear();
  await initializePush('101');
  expect(fetchMock).not.toHaveBeenCalled();
});
test('guest rotation uses documented updateToken before registration without user', async () => {
  mockStore.clear();
  let guest!: typeof import('../src/lib/push/pushToken');
  jest.isolateModules(() => {
    guest = require('../src/lib/push/pushToken');
  });
  mockMessaging.getToken.mockResolvedValue('guest-token-a');
  await guest.initializePush();
  guest.setupPushTokenRefresh();
  fetchMock.mockClear();
  mockRefresh('guest-token-b');
  mockMessaging.getToken.mockResolvedValue('guest-token-b');
  await guest.retryPush();
  expect(sent()[0]).toMatchObject({
    metodo: 'updateToken',
    tokenAnterior: 'guest-token-a',
    token: 'guest-token-b',
    user: '',
  });
  expect(sent()[1]).toMatchObject({
    metodo: 'register',
    token: 'guest-token-b',
  });
  expect(sent()[1]).not.toHaveProperty('user');
});
