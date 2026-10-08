import { launchImageLibrary } from 'react-native-image-picker';
import { APIError, request } from './api';
import { ProfilePhoto } from './session';

// A retry identifier, not an authentication secret. Identity always comes from Keychain.
export const uploadID = () =>
  `${Date.now().toString(36)}_${Math.random()
    .toString(36)
    .slice(2)}_${Math.random().toString(36).slice(2)}`;
export async function choosePublicationPhoto(): Promise<ProfilePhoto | null> {
  const result = await launchImageLibrary({
    mediaType: 'photo',
    selectionLimit: 1,
    assetRepresentationMode: 'compatible',
    maxWidth: 1600,
    maxHeight: 1600,
    quality: 0.9,
  });
  if (result.didCancel) {
    return null;
  }
  if (result.errorCode) {
    throw new Error(
      'No pudimos abrir tus fotos. Revisá los permisos y volvé a intentar.',
    );
  }
  const asset = result.assets?.[0];
  if (!asset?.uri) {
    throw new Error('No pudimos leer esa foto. Elegí otra.');
  }
  if ((asset.fileSize || 0) > 5 * 1024 * 1024) {
    throw new Error('La imagen pesa demasiado. Elegí una de hasta 5 MB.');
  }
  const source = (asset.type || 'image/jpeg').trim().toLowerCase();
  const type = source === 'image/jpg' ? 'image/jpeg' : source;
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(type)) {
    throw new Error('Elegí una foto JPG, PNG o WebP.');
  }
  return { uri: asset.uri, type, name: asset.fileName || 'foto.jpg' };
}
export type PhotoLocation = {
  address: string;
  latitude: number;
  longitude: number;
};
export type PlaceSuggestion = { id: string; label: string };
export async function suggestions(
  token: string,
  session: string,
  input: string,
  signal?: AbortSignal,
): Promise<PlaceSuggestion[]> {
  const data = (await request(
    'places/autocomplete',
    signal,
    { session_id: session, input },
    token,
  )) as { suggestions: PlaceSuggestion[] };
  if (
    !Array.isArray(data?.suggestions) ||
    data.suggestions.length > 5 ||
    !data.suggestions.every(
      p => typeof p.id === 'string' && typeof p.label === 'string',
    )
  ) {
    throw new Error('No pudimos leer las sugerencias.');
  }
  return data.suggestions;
}
export async function selectPlace(
  token: string,
  session: string,
  id: string,
): Promise<PhotoLocation> {
  const data = (await request(
    'places/details',
    undefined,
    { session_id: session, place_id: id },
    token,
  )) as PhotoLocation;
  if (
    typeof data?.address !== 'string' ||
    !Number.isFinite(data.latitude) ||
    !Number.isFinite(data.longitude) ||
    Math.abs(data.latitude) > 90 ||
    Math.abs(data.longitude) > 180
  ) {
    throw new Error('No pudimos ubicar esa dirección.');
  }
  return data;
}
export async function publishPhoto(
  token: string,
  id: string,
  photo: ProfilePhoto,
  caption: string,
  address: string,
  location: PhotoLocation | null,
): Promise<number> {
  const body = new FormData();
  body.append('request_id', id);
  body.append('photo', photo as unknown as Blob);
  body.append('caption', caption.trim());
  body.append('address', address.trim());
  if (location && location.address === address) {
    body.append('latitude', String(location.latitude));
    body.append('longitude', String(location.longitude));
  }
  const result = (await request(
    'publications/upload',
    undefined,
    body,
    token,
    60000,
  )) as { publication_id: number };
  if (!Number.isInteger(result?.publication_id) || result.publication_id < 1) {
    throw new APIError(
      'No pudimos confirmar la publicación. Reintentá para comprobarla sin duplicarla.',
    );
  }
  return result.publication_id;
}
