import * as Keychain from 'react-native-keychain';
import { APIError, request } from '../src/lib/api';
import {
  deleteUserPushToken,
  syncPushTokenForUser,
} from '../src/lib/push/pushToken';
import {
  login,
  restoreSession,
  saveSession,
  logout,
  uploadAvatar,
} from '../src/lib/session';
jest.mock('react-native-keychain', () => ({
  ACCESSIBLE: { WHEN_UNLOCKED_THIS_DEVICE_ONLY: 'device-only' },
  getGenericPassword: jest.fn(),
  setGenericPassword: jest.fn(),
  resetGenericPassword: jest.fn(),
}));
jest.mock('../src/lib/api', () => ({
  ...jest.requireActual('../src/lib/api'),
  request: jest.fn(),
}));
jest.mock('../src/lib/push/pushToken', () => ({
  syncPushTokenForUser: jest.fn().mockResolvedValue(true),
  deleteUserPushToken: jest.fn().mockResolvedValue(undefined),
}));
const token = '7.' + 'a'.repeat(64);
const user = {
  id: 7,
  name: 'Fixture',
  email: 'fixture@example.test',
  first_name: 'Fixture',
  last_name: '',
};
beforeEach(() => jest.clearAllMocks());
test('avatar upload uses bearer and multipart without accepting a user ID', async () => {
  (request as jest.Mock).mockResolvedValue({ user });
  await uploadAvatar(token, {
    uri: 'file:///fixture.jpg',
    type: 'image/jpeg',
    name: 'fixture.jpg',
  });
  expect(request).toHaveBeenCalledWith(
    'accounts/avatar',
    undefined,
    expect.any(FormData),
    token,
    45000,
  );
});
test('login normalizes email but never trims a password', async () => {
  (request as jest.Mock).mockResolvedValue({ token, user });
  expect(await login(' Fixture@Example.test ', ' spaced ')).toEqual({
    token,
    user,
  });
  expect(request).toHaveBeenCalledWith('accounts/login', undefined, {
    email: 'fixture@example.test',
    password: ' spaced ',
  });
});
test('only bearer session is stored on this device, never password', async () => {
  (Keychain.setGenericPassword as jest.Mock).mockResolvedValue({
    service: 'ok',
  });
  await saveSession(token);
  expect(Keychain.setGenericPassword).toHaveBeenCalledWith('session', token, {
    service: 'foodtrucks-uy.session',
    accessible: 'device-only',
  });
});
test('revoked sessions are removed from secure storage', async () => {
  (Keychain.getGenericPassword as jest.Mock).mockResolvedValue({
    password: token,
  });
  (request as jest.Mock).mockRejectedValue(new APIError('Invalid', 401));
  expect(await restoreSession()).toBeNull();
  expect(Keychain.resetGenericPassword).toHaveBeenCalled();
});
test('network failures do not erase a valid saved session', async () => {
  (Keychain.getGenericPassword as jest.Mock).mockResolvedValue({
    password: token,
  });
  (request as jest.Mock).mockRejectedValue(new APIError('Network', 0));
  await expect(restoreSession()).rejects.toThrow('Network');
  expect(Keychain.resetGenericPassword).not.toHaveBeenCalled();
});
test('logout revokes server session before clearing storage', async () => {
  (request as jest.Mock).mockResolvedValue({ message: 'ok' });
  await logout(token);
  expect(request).toHaveBeenCalledWith('accounts/logout', undefined, {}, token);
  expect(Keychain.resetGenericPassword).toHaveBeenCalled();
});
test('saving authenticated session associates push without failing login on push outage', async () => {
  (syncPushTokenForUser as jest.Mock).mockRejectedValueOnce(
    new Error('offline'),
  );
  await expect(saveSession(token, user.id)).resolves.toBeUndefined();
  expect(syncPushTokenForUser).toHaveBeenCalledWith('7');
});
test('push deletion failure prevents revocation and local clearing', async () => {
  (deleteUserPushToken as jest.Mock).mockRejectedValueOnce(
    new Error('offline'),
  );
  await expect(logout(token)).rejects.toThrow('offline');
  expect(request).not.toHaveBeenCalled();
  expect(Keychain.resetGenericPassword).not.toHaveBeenCalled();
});
