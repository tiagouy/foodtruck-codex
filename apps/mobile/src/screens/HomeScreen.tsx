import React, { useCallback, useState } from 'react';
import {
  Image,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Content, Event, Kind, list } from '../lib/api';
import { cardData } from '../lib/presentation';
import { RootStack, Tabs } from '../navigation';
import AppHeader, { colors } from '../components/AppHeader';
import ContentCard from '../components/ContentCard';
import EventCarousel from '../components/EventCarousel';
import State, { Button } from '../components/State';

type Block = { kind: Kind; title: string; tab: keyof Tabs; empty: string };
const blocks: Block[] = [
  {
    kind: 'events',
    title: 'Próximos eventos',
    tab: 'Eventos',
    empty:
      'Por ahora no hay próximos eventos. Podés consultar los pasados en la agenda.',
  },
  {
    kind: 'foodtrucks',
    title: 'Descubrí foodtrucks',
    tab: 'Foodtrucks',
    empty: 'Estamos preparando el directorio de foodtrucks.',
  },
  {
    kind: 'publications',
    title: 'Fotos de la comunidad',
    tab: 'Fotos',
    empty: 'Todavía no hay fotos publicadas.',
  },
];
type Result = { items: Content[]; error?: string };
export default function HomeScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStack>>();
  const [results, setResults] = useState<Partial<Record<Kind, Result>>>({});
  const [refreshKey, refresh] = useState(0);
  const [loading, setLoading] = useState(true);
  useFocusEffect(
    useCallback(() => {
      const controller = new AbortController();
      let live = true;
      setLoading(true);
      setResults({});
      Promise.all(
        blocks.map(async block => {
          try {
            const result = await list(
              block.kind,
              1,
              'upcoming',
              3,
              controller.signal,
            );
            if (live) {
              setResults(previous => ({
                ...previous,
                [block.kind]: { items: result.items },
              }));
            }
          } catch (failure) {
            if (live) {
              setResults(previous => ({
                ...previous,
                [block.kind]: { items: [], error: (failure as Error).message },
              }));
            }
          }
        }),
      ).finally(() => {
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
    }, [refreshKey]),
  );
  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.screen}>
      <AppHeader title="Salí a descubrir" />
      <ScrollView
        style={styles.body}
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={loading}
            onRefresh={() => refresh(key => key + 1)}
          />
        }
      >
        <View style={styles.logo}>
          <Image
            source={require('../../assets/branding/home-logo-horizontal.png')}
            accessibilityLabel="Food Truck UY"
            accessible
            resizeMode="contain"
            style={StyleSheet.absoluteFill}
          />
        </View>
        {blocks.map(block => {
          const result = results[block.kind];
          return (
            <View key={block.kind} style={styles.section}>
              <Text accessibilityRole="header" style={styles.title}>
                {block.title}
              </Text>
              {!result ? (
                <State loading />
              ) : result.error ? (
                <State
                  message={result.error}
                  retry={() => refresh(key => key + 1)}
                />
              ) : result.items.length ? (
                block.kind === 'events' ? (
                  <View style={styles.carousel}>
                    <EventCarousel
                      items={result.items as Event[]}
                      onPress={item =>
                        navigation.navigate('Detalle', {
                          kind: 'events',
                          contentKey: item.slug,
                        })
                      }
                    />
                  </View>
                ) : (
                  result.items.map(item => (
                    <ContentCard
                      key={item.id}
                      kind={block.kind}
                      item={item}
                      onPress={() =>
                        navigation.navigate('Detalle', {
                          kind: block.kind,
                          contentKey: cardData(block.kind, item).key,
                        })
                      }
                    />
                  ))
                )
              ) : (
                <State message={block.empty} />
              )}
              <Button
                label={`Ver ${block.tab.toLowerCase()}`}
                onPress={() =>
                  navigation.navigate('Principal', { screen: block.tab })
                }
              />
            </View>
          );
        })}
      </ScrollView>
    </SafeAreaView>
  );
}
const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.dark },
  body: { backgroundColor: colors.background },
  content: { padding: 18 },
  logo: {
    width: '90%',
    aspectRatio: 760 / 200,
    alignSelf: 'center',
    marginBottom: 16,
  },
  section: { marginBottom: 32 },
  carousel: { marginHorizontal: -18 },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.dark,
    marginBottom: 15,
  },
});
