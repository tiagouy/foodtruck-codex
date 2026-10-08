import React, { useCallback, useRef, useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { RootStack } from '../navigation';
import { list, mergeItems, Publication } from '../lib/api';
import { mediaURL } from '../lib/config';
import { colors } from './AppHeader';
import State, { Button } from './State';

export default function MyPhotos({ author }: { author: number }) {
  const navigation = useNavigation<NativeStackNavigationProp<RootStack>>();
  const [items, setItems] = useState<Publication[]>([]);
  const [page, setPage] = useState(0);
  const [total, setTotal] = useState(0);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState('');
  const [width, setWidth] = useState(0);
  const pending = useRef<AbortController | null>(null);
  const load = useCallback(
    async (next: number) => {
      if (pending.current) {
        return;
      }
      const controller = new AbortController();
      pending.current = controller;
      setBusy(true);
      setError('');
      if (next === 1) {
        setItems([]);
        setPage(0);
        setTotal(0);
      }
      try {
        const data = await list<Publication>(
          'publications',
          next,
          '',
          12,
          controller.signal,
          author,
        );
        if (!controller.signal.aborted) {
          setItems(previous =>
            next === 1 ? data.items : mergeItems(previous, data.items),
          );
          setPage(data.page);
          setTotal(data.total);
        }
      } catch (reason) {
        if (!controller.signal.aborted) {
          setError(
            reason instanceof Error
              ? reason.message
              : 'No pudimos cargar tus fotos.',
          );
        }
      } finally {
        if (pending.current === controller) {
          pending.current = null;
        }
        if (!controller.signal.aborted) {
          setBusy(false);
        }
      }
    },
    [author],
  );
  useFocusEffect(
    useCallback(() => {
      load(1);
      return () => {
        pending.current?.abort();
        pending.current = null;
      };
    }, [load]),
  );
  return (
    <View style={styles.section}>
      <Text accessibilityRole="header" style={styles.title}>
        Mis fotos{total > 0 ? ` (${total})` : ''}
      </Text>
      <View
        style={styles.grid}
        onLayout={event => setWidth(event.nativeEvent.layout.width)}
      >
        {items.map(item => (
          <Pressable
            key={item.id}
            style={[
              styles.tile,
              width > 0
                ? { width: (width - 12) / 2, height: (width - 12) / 2 }
                : undefined,
            ]}
            accessibilityRole="button"
            accessibilityLabel={`Ver mi foto: ${
              item.caption || item.address || 'Sin texto'
            }`}
            onPress={() =>
              navigation.navigate('Detalle', {
                kind: 'publications',
                contentKey: String(item.id),
              })
            }
          >
            {mediaURL(item.image.thumbnail || item.image.full) ? (
              <Image
                source={{
                  uri: mediaURL(item.image.thumbnail || item.image.full),
                }}
                resizeMode="cover"
                style={StyleSheet.absoluteFill}
              />
            ) : (
              <Text style={styles.placeholder}>Foto</Text>
            )}
          </Pressable>
        ))}
      </View>
      {busy ? (
        <State loading />
      ) : error ? (
        <State message={error} retry={() => load(page + 1)} />
      ) : items.length === 0 ? (
        <State message="Todavía no tenés fotos publicadas." />
      ) : null}
      {!busy && !error && items.length < total ? (
        <Button label="Ver más fotos" onPress={() => load(page + 1)} />
      ) : null}
    </View>
  );
}
const styles = StyleSheet.create({
  section: { gap: 14 },
  title: { fontSize: 22, fontWeight: '700', color: colors.dark },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    rowGap: 12,
  },
  tile: {
    width: '48%',
    aspectRatio: 1,
    backgroundColor: colors.line,
    borderRadius: 12,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholder: { color: colors.muted },
});
