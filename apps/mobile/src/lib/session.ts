import * as Keychain from 'react-native-keychain';
import { APIError, request } from './api';

export type AccountUser = {
  id: number;
  name: string;
  first_name: string;
  last_name: string;
  email: string;
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
export async function saveSession(token: string) {
  const saved = await Keychain.setGenericPassword('session', token, {
    service,
    accessible: Keychain.ACCESSIBLE.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
  });
  if (!saved) {
    throw new Error('No pudimos guardar la sesión de forma segura.');
  }
}
export async function restoreSession(): Promise<AppSession | null> {
  const saved = await Keychain.getGenericPassword({ service });
  if (!saved) {
    return null;
  }
  try {
    return await validateSession(saved.password);
  } catch (error) {
    if (error instanceof APIError && error.status === 401) {
      await forgetSession();
      return null;
    }
    throw error;
  }
}
export async function forgetSession() {
  await Keychain.resetGenericPassword({ service });
}
export async function logout(token: string) {
  try {
    await request('accounts/logout', undefined, {}, token);
  } catch (error) {
    if (!(error instanceof APIError && error.status === 401)) {
      throw error;
    }
  }
  await forgetSession();
}
