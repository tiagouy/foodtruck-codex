import React, { useCallback, useRef, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Content, Kind, list, mergeItems } from '../lib/api';
import { cardData } from '../lib/presentation';
import { RootStack } from '../navigation';
import AppHeader, { colors } from '../components/AppHeader';
import ContentCard from '../components/ContentCard';
import State, { Button } from '../components/State';

const labels = {
  events: 'Eventos',
  foodtrucks: 'Foodtrucks',
  publications: 'Fotos de la comunidad',
};
const empty = {
  events: 'No hay eventos en esta sección por ahora.',
  foodtrucks: 'Todavía no hay foodtrucks publicados.',
  publications: 'Todavía no hay fotos publicadas.',
};
export default function CatalogScreen({ kind }: { kind: Kind }) {
  const navigation = useNavigation<NativeStackNavigationProp<RootStack>>();
  const [view, setView] = useState('upcoming');
  const [items, setItems] = useState<Content[]>([]);
  const [page, setPage] = useState(0);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const request = useRef<AbortController | null>(null);
  const active = useRef(false);
  const load = useCallback(
    async (nextPage: number) => {
      if (nextPage > 1 && request.current) {
        return;
      }
      request.current?.abort();
      const controller = new AbortController();
      request.current = controller;
      setLoading(true);
      setError('');
      if (nextPage === 1) {
        setItems([]);
        setPage(0);
        setTotal(0);
      }
      try {
        const result = await list(kind, nextPage, view, 12, controller.signal);
        if (active.current && request.current === controller) {
          setItems(previous =>
            nextPage === 1 ? result.items : mergeItems(previous, result.items),
          );
          setPage(nextPage);
          setTotal(result.total);
        }
      } catch (failure) {
        if (
          active.current &&
          !controller.signal.aborted &&
          request.current === controller
        ) {
          setError((failure as Error).message);
        }
      } finally {
        if (request.current === controller) {
          request.current = null;
          if (active.current) {
            setLoading(false);
          }
        }
      }
    },
    [kind, view],
  );
  useFocusEffect(
    useCallback(() => {
      active.current = true;
      load(1);
      return () => {
        active.current = false;
        request.current?.abort();
        request.current = null;
      };
    }, [load]),
  );
  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.screen}>
      <AppHeader title={labels[kind]} />
      <FlatList
        style={styles.body}
        data={items}
        keyExtractor={item => String(item.id)}
        contentContainerStyle={styles.content}
        refreshing={loading && page === 0}
        onRefresh={() => load(1)}
        renderItem={({ item }) => (
          <ContentCard
            kind={kind}
            item={item}
            onPress={() =>
              navigation.navigate('Detalle', {
                kind,
                contentKey: cardData(kind, item).key,
              })
            }
          />
        )}
        ListHeaderComponent={
          <View>
            {kind === 'events' && (
              <View style={styles.filters}>
                <Button
                  label="Próximos"
                  disabled={view === 'upcoming'}
                  onPress={() => setView('upcoming')}
                />
                <Button
                  label="Pasados"
                  disabled={view === 'past'}
                  onPress={() => setView('past')}
                />
              </View>
            )}
            {kind === 'publications' && (
              <View style={styles.upload}>
                <Button
                  label="Subir foto"
                  onPress={() => navigation.navigate('SubirFoto')}
                />
                <Text style={styles.notice}>
                  Fotos compartidas por la comunidad.
                </Text>
              </View>
            )}
          </View>
        }
        ListEmptyComponent={
          <State
            loading={loading}
            message={error || empty[kind]}
            retry={error ? () => load(1) : undefined}
          />
        }
        ListFooterComponent={
          items.length > 0 ? (
            <View>
              {error ? (
                <State message={error} retry={() => load(page + 1)} />
              ) : loading ? (
                <State loading />
              ) : page * 12 < total ? (
                <Button label="Ver más" onPress={() => load(page + 1)} />
              ) : null}
            </View>
          ) : null
        }
      />
    </SafeAreaView>
  );
}
const styles = StyleSheet.create({
  upload: { gap: 12 },
  screen: { flex: 1, backgroundColor: colors.dark },
  body: { backgroundColor: colors.background },
  content: { padding: 18 },
  filters: { flexDirection: 'row', gap: 10, marginBottom: 18 },
  notice: { lineHeight: 21, color: colors.muted, marginBottom: 18 },
});
