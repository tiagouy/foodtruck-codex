// Adapted from pushv3-app-kit 8ec6ee9; same HTTP contract, no payload logging.
import { Platform } from 'react-native';
export type UsefulPushConfig = {
  apiUrl: string;
  apiKey: string;
  appId: string;
  appToken: string;
};
let config: UsefulPushConfig | null = null;
async function send(
  method: 'register' | 'updateToken' | 'removeToken',
  token: string,
  userId?: string,
  previous?: string,
) {
  if (!config || !token) {
    return false;
  }
  const data = new FormData();
  const values: Record<string, string> = {
    key: config.apiKey,
    token,
    idapp: config.appId,
    tokenApp: config.appToken,
    metodo: method,
    ...(method !== 'removeToken' ? { device: Platform.OS } : {}),
    ...(userId ? { user: userId } : {}),
    ...(method === 'updateToken'
      ? { tokenAnterior: previous || '', user: userId || '' }
      : {}),
  };
  Object.entries(values).forEach(([key, value]) => data.append(key, value));
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 15000);
  try {
    const response = await fetch(config.apiUrl, {
      method: 'POST',
      body: data,
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    });
    if (!response.ok) {
      return false;
    }
    const body = await response.json();
    const field =
      method === 'register'
        ? 'register'
        : method === 'updateToken'
        ? 'update'
        : 'delete';
    return body?.[field] === 'yes';
  } catch {
    return false;
  } finally {
    clearTimeout(timeout);
  }
}
const UsefulPush = {
  configure(next: UsefulPushConfig) {
    if (
      !next.apiKey ||
      !next.appToken ||
      !next.appId ||
      !next.apiUrl.startsWith('https://')
    ) {
      throw new Error('Configuración push incompleta.');
    }
    config = next;
  },
  register: (token: string, userId?: string) => send('register', token, userId),
  updateToken: (token: string, userId: string, previous: string) =>
    send('updateToken', token, userId, previous),
  deleteToken: (token: string, userId?: string) =>
    send('removeToken', token, userId),
};
export default UsefulPush;
