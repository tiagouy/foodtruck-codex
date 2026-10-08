import { Publication } from './api';
export function photoMapURL(
  photo: Pick<Publication, 'address' | 'latitude' | 'longitude'>,
): string | null {
  const { latitude: lat, longitude: lng } = photo;
  const coordinates =
    typeof lat === 'number' &&
    Number.isFinite(lat) &&
    Math.abs(lat) <= 90 &&
    typeof lng === 'number' &&
    Number.isFinite(lng) &&
    Math.abs(lng) <= 180;
  const query = coordinates ? `${lat},${lng}` : photo.address.trim();
  return query
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
        query,
      )}`
    : null;
}
