import { Content, Event, Foodtruck, Kind, Publication } from './api';

export function plain(value?: string): string {
  return (value || '')
    .replace(/<br\s*\/?\s*>|<\/p>/gi, '\n')
    .replace(/<[^>]*>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#0?39;|&apos;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .trim();
}
export function dateLabel(value: string): string {
  const date = value.slice(0, 10);
  const [year, month, day] = date.split('-');
  return /^\d{4}-\d{2}-\d{2}$/.test(date) ? `${day}/${month}/${year}` : '';
}
// Calendar dates are not instants: UTC avoids shifting the displayed day on devices.
export function eventDateParts(value: string) {
  const date = value.slice(0, 10);
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
    return null;
  }
  const parsed = new Date(`${date}T12:00:00Z`);
  if (
    Number.isNaN(parsed.getTime()) ||
    parsed.toISOString().slice(0, 10) !== date
  ) {
    return null;
  }
  return {
    weekday: ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'][
      parsed.getUTCDay()
    ],
    day: String(parsed.getUTCDate()),
    month: [
      'ENE',
      'FEB',
      'MAR',
      'ABR',
      'MAY',
      'JUN',
      'JUL',
      'AGO',
      'SEP',
      'OCT',
      'NOV',
      'DIC',
    ][parsed.getUTCMonth()],
  };
}
export function cardData(kind: Kind, item: Content) {
  if (kind === 'events') {
    const event = item as Event;
    return {
      title: event.title,
      subtitle: `${dateLabel(event.start_date)} · ${
        event.venue || event.locality
      }`,
      image: event.image.thumbnail,
      key: event.slug,
    };
  }
  if (kind === 'foodtrucks') {
    const truck = item as Foodtruck;
    return {
      title: truck.name,
      subtitle: `${truck.locality}, ${truck.department}`,
      image:
        truck.images.find(img => img.role === 'truck_photo')?.url ||
        truck.images.find(img => img.role === 'logo')?.url,
      key: truck.slug,
    };
  }
  const photo = item as Publication;
  return {
    title: photo.author.name,
    subtitle: `${dateLabel(photo.created_at)}${
      photo.address ? ` · ${photo.address}` : ''
    }`,
    image: photo.image.thumbnail,
    key: String(photo.id),
  };
}
