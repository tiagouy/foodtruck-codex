import { Platform } from 'react-native';

// Public backend for store builds and current simulator/device testing.
export const RELEASE_SITE_URL = 'https://foodtruckuruguay.com';
// Opt in deliberately when developing against MAMP; releases always use HTTPS.
const USE_LOCAL_BACKEND = false;
const developmentOrigin =
  Platform.OS === 'android' ? 'http://10.0.2.2:8888' : 'http://localhost:8888';
export function siteURL(): string {
  if (__DEV__ && USE_LOCAL_BACKEND) {
    return `${developmentOrigin}/foodtruck`;
  }
  if (!RELEASE_SITE_URL.startsWith('https://')) {
    throw new Error('Falta configurar el servidor HTTPS de producción.');
  }
  return RELEASE_SITE_URL.replace(/\/$/, '');
}

export function mediaURL(value: string | null | undefined): string | undefined {
  if (!value) {
    return undefined;
  }
  if (__DEV__ && USE_LOCAL_BACKEND) {
    value = value.replace(
      /^http:\/\/(localhost|127\.0\.0\.1):8888(?=\/)/,
      developmentOrigin,
    );
  }
  return /^https?:\/\//.test(value) ? value : undefined;
}
