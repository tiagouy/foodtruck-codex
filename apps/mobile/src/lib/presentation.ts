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
