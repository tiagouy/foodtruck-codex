// Lifecycle adapted from pushv3-app-kit; isolated storage and serialized operations.
import { PermissionsAndroid, Platform } from 'react-native';
import messaging from '@react-native-firebase/messaging';
import * as Keychain from 'react-native-keychain';
import UsefulPush from './usefulPush';
const service = 'foodtrucks-uy.push';
type State = {
  token: string;
  previous: string;
  userId: string;
  disabled: boolean;
};
let cached: State | null = null;
let tail: Promise<unknown> = Promise.resolve();
let configured = false;
function serial<T>(task: () => Promise<T>): Promise<T> {
  const next = tail.then(task, task);
  tail = next.catch(() => undefined);
  return next;
}
function configure() {
  if (!configured) {
    // Local ignored file; bundled client credentials are not server secrets.
    UsefulPush.configure(require('../../../pushv3.local.json'));
    configured = true;
  }
}
async function state(): Promise<State> {
  if (!cached) {
    const saved = await Keychain.getGenericPassword({ service });
    try {
      const value = saved ? JSON.parse(saved.password) : {};
      cached = {
        token: typeof value.token === 'string' ? value.token : '',
        previous: typeof value.previous === 'string' ? value.previous : '',
        userId: typeof value.userId === 'string' ? value.userId : '',
        disabled: value.disabled === true,
      };
    } catch {
      cached = { token: '', previous: '', userId: '', disabled: false };
    }
  }
  return cached;
}
async function save(value: State) {
  const result = await Keychain.setGenericPassword(
    'push',
    JSON.stringify(value),
    {
      service,
      accessible: Keychain.ACCESSIBLE.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
    },
  );
  if (!result) {
    throw new Error('No pudimos guardar la configuración de notificaciones.');
  }
  cached = value;
}
async function permission(prompt: boolean) {
  if (Platform.OS === 'android') {
    if (Number(Platform.Version) < 33) {
      return true;
    }
    const allowed = await PermissionsAndroid.check(
      PermissionsAndroid.PERMISSIONS.POST_NOTIFICATIONS,
    );
    return (
      allowed ||
      (prompt &&
        (await PermissionsAndroid.request(
          PermissionsAndroid.PERMISSIONS.POST_NOTIFICATIONS,
        )) === PermissionsAndroid.RESULTS.GRANTED)
    );
  }
  const status = prompt
    ? await messaging().requestPermission()
    : await messaging().hasPermission();
  return (
    status === messaging.AuthorizationStatus.AUTHORIZED ||
    status === messaging.AuthorizationStatus.PROVISIONAL
  );
}
async function remember(token: string) {
  const current = await state();
  if (token !== current.token) {
    await save({
      ...current,
      token,
      previous: current.previous || current.token,
    });
  }
}
async function sync() {
  configure();
  const current = await state();
  if (current.disabled || !current.token) {
    return false;
  }
  if (current.previous && current.previous !== current.token) {
    if (
      !(await UsefulPush.updateToken(
        current.token,
        current.userId,
        current.previous,
      ))
    ) {
      return false;
    }
    await save({ ...current, previous: '' });
  }
  // Reassert association after rotation, including an existing authenticated user.
  return UsefulPush.register(current.token, current.userId || undefined);
}
async function acquire(prompt: boolean) {
  if (!(await permission(prompt))) {
    return false;
  }
  if (Platform.OS === 'ios') {
    await messaging().registerDeviceForRemoteMessages();
  }
  const token = await messaging().getToken();
  if (!token) {
    return false;
  }
  await remember(token);
  return sync();
}
export function syncPushTokenForUser(userId: string) {
  return serial(async () => {
    if (!/^[1-9]\d*$/.test(userId)) {
      throw new Error('Cuenta inválida para notificaciones.');
    }
    await save({ ...(await state()), userId, disabled: false });
    // Login does not repeatedly prompt if the person previously denied permission.
    return acquire(false);
  });
}
export function initializePush(userId?: string) {
  return serial(async () => {
    const current = await state();
    // Explicit logout suppresses guest re-registration on subsequent launches.
    if (current.disabled && !userId) {
      return false;
    }
    if (userId) {
      await save({ ...current, userId, disabled: false });
    }
    return acquire(true);
  });
}
export function retryPush() {
  return serial(async () => {
    if ((await state()).disabled) {
      return false;
    }
    return acquire(false);
  });
}
export function setupPushTokenRefresh() {
  return messaging().onTokenRefresh(token => {
    serial(async () => {
      await remember(token);
      return sync();
    }).catch(() => undefined);
  });
}
export function deleteUserPushToken() {
  return serial(async () => {
    const current = await state();
    configure();
    // If an offline rotation is pending, deactivate both possible registrations.
    for (const token of [...new Set([current.previous, current.token])].filter(
      Boolean,
    )) {
      if (!(await UsefulPush.deleteToken(token, current.userId || undefined))) {
        throw new Error(
          'No pudimos desactivar las notificaciones. Reintentá con conexión.',
        );
      }
    }
    await save({ token: '', previous: '', userId: '', disabled: true });
  });
}
