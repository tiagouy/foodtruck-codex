import * as Keychain from 'react-native-keychain';
import { APIError, request } from './api';
import { deleteUserPushToken, syncPushTokenForUser } from './push/pushToken';

export type AccountUser = {
  id: number;
  name: string;
  first_name: string;
  last_name: string;
  email: string;
  avatar?: string | null;
};
export type AppSession = { token: string; user: AccountUser };
const service = 'foodtrucks-uy.session';
function userFrom(value: unknown): AccountUser {
  const user = value as AccountUser;
  if (
    !user ||
    !Number.isInteger(user.id) ||
    user.id < 1 ||
    !['name', 'email', 'first_name', 'last_name'].every(
      key =>
        typeof (user as unknown as Record<string, unknown>)[key] === 'string',
    )
  ) {
    throw new APIError('No pudimos validar tu cuenta.');
  }
  return user;
}
export async function validateSession(token: string): Promise<AppSession> {
  const result = (await request(
    'accounts/session',
    undefined,
    undefined,
    token,
  )) as { user: unknown };
  return { token, user: userFrom(result?.user) };
}
export async function updateProfile(
  token: string,
  first: string,
  last: string,
): Promise<AccountUser> {
  const result = (await request(
    'accounts/profile',
    undefined,
    { first_name: first.trim(), last_name: last.trim() },
    token,
  )) as { user: unknown };
  return userFrom(result?.user);
}
export type ProfilePhoto = { uri: string; type: string; name: string };
export async function uploadAvatar(
  token: string,
  photo: ProfilePhoto,
): Promise<AccountUser> {
  const form = new FormData();
  form.append('photo', photo as unknown as Blob);
  const result = (await request(
    'accounts/avatar',
    undefined,
    form,
    token,
    45000,
  )) as {
    user: unknown;
  };
  return userFrom(result?.user);
}
export async function login(
  email: string,
  password: string,
): Promise<AppSession> {
  const result = (await request('accounts/login', undefined, {
    email: email.trim().toLowerCase(),
    password,
  })) as { token: string; user: unknown };
  if (
    typeof result?.token !== 'string' ||
    !/^[1-9]\d*\.[a-f0-9]{64}$/.test(result.token)
  ) {
    throw new APIError('No pudimos validar la sesión.');
  }
  return { token: result.token, user: userFrom(result.user) };
}
export async function saveSession(token: string, userId?: number) {
  const saved = await Keychain.setGenericPassword('session', token, {
    service,
    accessible: Keychain.ACCESSIBLE.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
  });
  if (!saved) {
    throw new Error('No pudimos guardar la sesión de forma segura.');
  }
  if (userId) {
    await syncPushTokenForUser(String(userId)).catch(() => false);
  }
}
export async function restoreSession(): Promise<AppSession | null> {
  const saved = await Keychain.getGenericPassword({ service });
  if (!saved) {
    return null;
  }
  try {
    const session = await validateSession(saved.password);
    await syncPushTokenForUser(String(session.user.id)).catch(() => false);
    return session;
  } catch (error) {
    if (error instanceof APIError && error.status === 401) {
      await forgetSession();
      return null;
    }
    throw error;
  }
}
export async function forgetSession(pushAlreadyDeleted = false) {
  if (!pushAlreadyDeleted) {
    await deleteUserPushToken();
  }
  await Keychain.resetGenericPassword({ service });
}
export async function logout(token: string) {
  await deleteUserPushToken();
  try {
    await request('accounts/logout', undefined, {}, token);
  } catch (error) {
    if (!(error instanceof APIError && error.status === 401)) {
      throw error;
    }
  }
  await forgetSession(true);
}
