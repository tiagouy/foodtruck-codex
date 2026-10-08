import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Alert,
  Image,
  Pressable,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Flag } from 'lucide-react-native';
import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import {
  Content,
  detail,
  Event,
  Foodtruck,
  Publication,
  request,
} from '../lib/api';
import { restoreSession } from '../lib/session';
import { dateLabel, plain } from '../lib/presentation';
import { mediaURL, siteURL } from '../lib/config';
import { openLink } from '../lib/links';
import { RootStack } from '../navigation';
import { colors } from '../components/AppHeader';
import State, { Button } from '../components/State';
import Avatar from '../components/Avatar';

export default function DetailScreen({
  route,
  navigation,
}: NativeStackScreenProps<RootStack, 'Detalle'>) {
  const { kind, contentKey } = route.params;
  const [item, setItem] = useState<Content | null>(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshKey, refresh] = useState(0);
  const reporting = useRef(false);
  const photoId = kind === 'publications' ? item?.id : undefined;
  const sendReport = useCallback(async () => {
    if (!photoId || reporting.current) {
      return;
    }
    reporting.current = true;
    try {
      const session = await restoreSession();
      if (!session) {
        Alert.alert(
          'Ingresá a tu cuenta',
          'Para denunciar una publicación, ingresá o registrate.',
          [
            { text: 'Cancelar', style: 'cancel' },
            {
              text: 'Ingresar',
              onPress: () =>
                navigation.navigate('Principal', { screen: 'Cuenta' }),
            },
          ],
        );
        return;
      }
      await request(
        `publications/${photoId}/report`,
        undefined,
        {},
        session.token,
      );
      Alert.alert(
        'Denuncia recibida',
        'Gracias por avisarnos. Revisaremos esta publicación.',
      );
    } catch (failure) {
      Alert.alert(
        'No pudimos enviar la denuncia',
        failure instanceof Error ? failure.message : 'Intentá nuevamente.',
      );
    } finally {
      reporting.current = false;
    }
  }, [photoId, navigation]);
  useEffect(() => {
    navigation.setOptions({
      headerRight: photoId
        ? () => (
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Denunciar publicación"
              hitSlop={10}
              style={styles.reportButton}
              onPress={() =>
                Alert.alert(
                  '¿Denunciar esta publicación?',
                  'Nos avisarás para que revisemos la foto y su contenido.',
                  [
                    { text: 'Cancelar', style: 'cancel' },
                    {
                      text: 'Denunciar',
                      style: 'destructive',
                      onPress: sendReport,
                    },
                  ],
                )
              }
            >
              <Flag size={22} color={colors.dark} />
            </Pressable>
          )
        : undefined,
    });
  }, [navigation, photoId, sendReport]);
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
      {photo ? (
        <View style={styles.authorRow}>
          <Avatar url={photo.author.avatar} size={44} />
          <Text accessibilityRole="header" style={styles.authorName}>
            {plain(photo.author.name)}
          </Text>
        </View>
      ) : (
        <Text accessibilityRole="header" style={styles.title}>
          {plain(event?.title || truck?.name)}
        </Text>
      )}
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
  reportButton: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
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
  authorRow: { flexDirection: 'row', gap: 12, alignItems: 'center' },
  authorName: { flex: 1, fontSize: 20, fontWeight: '600', color: colors.dark },
});
