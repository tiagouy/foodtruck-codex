import React, { useCallback, useState } from 'react';
import { Image, ScrollView, Share, StyleSheet, Text } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Content, detail, Event, Foodtruck, Publication } from '../lib/api';
import { dateLabel, plain } from '../lib/presentation';
import { mediaURL, siteURL } from '../lib/config';
import { openLink } from '../lib/links';
import { RootStack } from '../navigation';
import { colors } from '../components/AppHeader';
import State, { Button } from '../components/State';

export default function DetailScreen({
  route,
}: NativeStackScreenProps<RootStack, 'Detalle'>) {
  const { kind, contentKey } = route.params;
  const [item, setItem] = useState<Content | null>(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshKey, refresh] = useState(0);
  useFocusEffect(
    useCallback(() => {
      const controller = new AbortController();
      let live = true;
      // Always revalidate: a shared or previously listed photo may have been unpublished.
      setItem(null);
      setError('');
      setLoading(true);
      detail(kind, contentKey, controller.signal)
        .then(value => {
          if (live) {
            setItem(value);
          }
        })
        .catch(failure => {
          if (live) {
            setError(failure.message);
          }
        })
        .finally(() => {
          if (live) {
            setLoading(false);
          }
        });
      return () => {
        live = false;
        controller.abort();
      };
      // Explicit refresh is an invalidation trigger, not a value read by the request.
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [kind, contentKey, refreshKey]),
  );
  if (!item) {
    return (
      <ScrollView contentContainerStyle={styles.content}>
        <State
          loading={loading}
          message={error}
          retry={!loading ? () => refresh(key => key + 1) : undefined}
        />
      </ScrollView>
    );
  }
  const event = kind === 'events' ? (item as Event) : null;
  const truck = kind === 'foodtrucks' ? (item as Foodtruck) : null;
  const photo = kind === 'publications' ? (item as Publication) : null;
  const image = mediaURL(
    event?.image.full ||
      photo?.image.full ||
      truck?.images.find(img => img.role === 'truck_photo')?.url ||
      truck?.images[0]?.url,
  );
  return (
    <ScrollView contentContainerStyle={styles.content}>
      {image && (
        <Image
          source={{ uri: image }}
          style={styles.image}
          resizeMode="contain"
        />
      )}
      <Text accessibilityRole="header" style={styles.title}>
        {plain(event?.title || truck?.name || photo?.author.name)}
      </Text>
      {event && (
        <>
          <Text style={styles.meta}>
            {event.cancelled ? 'Cancelado · ' : ''}
            {dateLabel(event.start_date)}
            {event.end_date !== event.start_date
              ? ` — ${dateLabel(event.end_date)}`
              : ''}
          </Text>
          <Text style={styles.text}>
            {event.venue}
            {'\n'}
            {event.address}
            {'\n'}
            {event.locality}, {event.department}
          </Text>
          <Text style={styles.text}>
            {plain(event.description || event.summary)}
          </Text>
          {!event.schedule?.length && event.start_time && (
            <Text style={styles.meta}>
              Horario general: {event.start_time}
              {event.end_time ? ` — ${event.end_time}` : ''}
            </Text>
          )}
          {event.schedule?.map(day => (
            <Text key={day.date} style={styles.meta}>
              {dateLabel(day.date)} {day.start}
              {day.end ? ` — ${day.end}` : ''}
            </Text>
          ))}
          {event.entry_type === 'free' && (
            <Text style={styles.meta}>Entrada gratuita</Text>
          )}
          {event.instagram && (
            <Button
              label="Instagram"
              onPress={() => openLink(event.instagram!)}
            />
          )}
          {event.website && (
            <Button
              label="Sitio web"
              onPress={() => openLink(event.website!)}
            />
          )}
          {event.tickets_url && (
            <Button
              label="Entradas"
              onPress={() => openLink(event.tickets_url!)}
            />
          )}
        </>
      )}
      {truck && (
        <>
          <Text style={styles.meta}>
            Base: {truck.locality}, {truck.department}
          </Text>
          <Text style={styles.text}>{plain(truck.description)}</Text>
          <Text style={styles.title}>Qué sirven</Text>
          <Text style={styles.text}>{plain(truck.food_offering)}</Text>
          <Text style={styles.meta}>
            {truck.cuisines.map(cuisine => cuisine.name).join(' · ')}
          </Text>
          <Text style={styles.text}>
            {[
              truck.serves_events && 'Eventos',
              truck.serves_private_events && 'Catering / privados',
              truck.has_fixed_location && 'Punto fijo',
            ]
              .filter(Boolean)
              .join(' · ')}
          </Text>
          {truck.instagram && (
            <Button
              label="Instagram"
              onPress={() => openLink(truck.instagram)}
            />
          )}
          {truck.whatsapp && (
            <Button
              label="WhatsApp"
              onPress={() =>
                openLink(`https://wa.me/${truck.whatsapp.replace(/\D/g, '')}`)
              }
            />
          )}
        </>
      )}
      {photo && (
        <>
          <Text style={styles.meta}>{dateLabel(photo.created_at)}</Text>
          <Text style={styles.text}>{plain(photo.caption)}</Text>
          <Text style={styles.meta}>{photo.address}</Text>
          <Button
            label="Compartir"
            onPress={() => {
              Share.share({ message: photo.share_url }).catch(() => {});
            }}
          />
        </>
      )}
      {event && (
        <Button
          label="Ver en el sitio"
          onPress={() => openLink(`${siteURL()}/evento/${event.slug}/`)}
        />
      )}
    </ScrollView>
  );
}
const styles = StyleSheet.create({
  content: { padding: 20, gap: 18, paddingBottom: 44 },
  image: {
    width: '100%',
    aspectRatio: 1,
    backgroundColor: '#EEE5DB',
    borderRadius: 16,
  },
  title: { fontSize: 25, fontWeight: '800', color: colors.dark },
  text: { fontSize: 16, lineHeight: 26, color: colors.dark },
  meta: { fontSize: 15, color: colors.muted, lineHeight: 23 },
});
