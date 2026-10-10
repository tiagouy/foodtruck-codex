import React, { useEffect, useRef, useState } from 'react';
import {
  AccessibilityInfo,
  Animated,
  FlatList,
  Image,
  Pressable,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { Event } from '../lib/api';
import { mediaURL } from '../lib/config';
import { dateLabel, eventDateParts, plain } from '../lib/presentation';
import { colors } from './AppHeader';

export function carouselGeometry(width: number) {
  const card = Math.min(360, width * 0.8);
  const step = card + 12;
  return { card, step, inset: (width - step) / 2 };
}

function CalendarDate({ date }: { date: string }) {
  const parts = eventDateParts(date);
  if (!parts) {
    return null;
  }
  return (
    <View style={styles.calendar}>
      <Text style={styles.calendarLabel}>{parts.weekday}</Text>
      <Text numberOfLines={1} adjustsFontSizeToFit style={styles.calendarDay}>
        {parts.day}
      </Text>
      <Text style={styles.calendarLabel}>{parts.month}</Text>
    </View>
  );
}

function Poster({ event }: { event: Event }) {
  const [failed, setFailed] = useState(false);
  const uri = mediaURL(event.image.full || event.image.thumbnail);
  return (
    <View style={styles.poster}>
      {uri && !failed ? (
        <Image
          source={{ uri }}
          style={styles.image}
          resizeMode="contain"
          onError={() => setFailed(true)}
        />
      ) : (
        <Text style={styles.placeholder}>Foodtrucks UY</Text>
      )}
      {event.cancelled && (
        <View style={styles.badge}>
          <Text style={styles.badgeText}>Cancelado</Text>
        </View>
      )}
    </View>
  );
}

export default function EventCarousel({
  items,
  onPress,
  inverse = false,
}: {
  items: Event[];
  onPress: (event: Event) => void;
  inverse?: boolean;
}) {
  const dimensions = useWindowDimensions();
  const [width, setWidth] = useState(dimensions.width);
  const [index, setIndex] = useState(0);
  const [reduceMotion, setReduceMotion] = useState(false);
  const position = useRef(new Animated.Value(0)).current;
  const ref = useRef<FlatList<Event>>(null);
  const { card, step, inset } = carouselGeometry(width);
  useEffect(() => {
    let live = true;
    AccessibilityInfo.isReduceMotionEnabled()
      .then(value => {
        if (live) {
          setReduceMotion(value);
        }
      })
      .catch(() => {});
    const subscription = AccessibilityInfo.addEventListener(
      'reduceMotionChanged',
      setReduceMotion,
    );
    return () => {
      live = false;
      subscription.remove();
    };
  }, []);
  // A new filter/refresh or viewport size starts at the first real event.
  const first = items[0]?.id;
  useEffect(() => {
    setIndex(0);
    position.setValue(0);
    ref.current?.scrollToOffset({ offset: 0, animated: false });
  }, [first, width, position]);
  return (
    <View onLayout={event => setWidth(event.nativeEvent.layout.width)}>
      <Animated.FlatList
        ref={ref}
        horizontal
        data={items}
        keyExtractor={item => String(item.id)}
        showsHorizontalScrollIndicator={false}
        decelerationRate="fast"
        snapToInterval={step}
        snapToAlignment="start"
        disableIntervalMomentum
        contentContainerStyle={[
          styles.track,
          {
            paddingHorizontal: inset,
          },
        ]}
        getItemLayout={(_, itemIndex) => ({
          length: step,
          offset: step * itemIndex,
          index: itemIndex,
        })}
        scrollEventThrottle={16}
        onScroll={Animated.event(
          [{ nativeEvent: { contentOffset: { x: position } } }],
          { useNativeDriver: true },
        )}
        onMomentumScrollEnd={event =>
          setIndex(
            Math.max(
              0,
              Math.min(
                items.length - 1,
                Math.round(event.nativeEvent.contentOffset.x / step),
              ),
            ),
          )
        }
        renderItem={({ item, index: itemIndex }) => (
          <Animated.View
            style={[
              styles.slot,
              {
                width: step,
                transform: [
                  {
                    scale: reduceMotion
                      ? 1
                      : position.interpolate({
                          inputRange: [
                            (itemIndex - 1) * step,
                            itemIndex * step,
                            (itemIndex + 1) * step,
                          ],
                          outputRange: [0.9, 1, 0.9],
                          extrapolate: 'clamp',
                        }),
                  },
                ],
              },
            ]}
          >
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={`Ver ${plain(item.title)}, ${dateLabel(
                item.start_date,
              )}`}
              onPress={() => onPress(item)}
              style={[styles.card, { width: card }]}
            >
              <Poster event={item} />
              <View style={styles.info}>
                <CalendarDate date={item.start_date} />
                <View style={styles.metadata}>
                  <Text numberOfLines={2} style={styles.title}>
                    {plain(item.title)}
                  </Text>
                  <Text numberOfLines={2} style={styles.place}>
                    {plain(item.venue || item.locality || item.department)}
                  </Text>
                  <Text style={styles.date}>
                    {dateLabel(item.start_date)}
                    {item.end_date !== item.start_date
                      ? ` — ${dateLabel(item.end_date)}`
                      : ''}
                  </Text>
                </View>
              </View>
            </Pressable>
          </Animated.View>
        )}
      />
      {items.length > 1 && (
        <View style={styles.controls}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Evento anterior"
            disabled={index === 0}
            onPress={() => {
              setIndex(index - 1);
              ref.current?.scrollToOffset({
                offset: (index - 1) * step,
                animated: !reduceMotion,
              });
            }}
            style={styles.arrow}
          >
            <Text
              style={
                index === 0
                  ? styles.disabled
                  : [styles.active, inverse && styles.inverse]
              }
            >
              ‹
            </Text>
          </Pressable>
          <Text style={[styles.counter, inverse && styles.inverse]}>
            {index + 1} / {items.length}
          </Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Evento siguiente"
            disabled={index === items.length - 1}
            onPress={() => {
              setIndex(index + 1);
              ref.current?.scrollToOffset({
                offset: (index + 1) * step,
                animated: !reduceMotion,
              });
            }}
            style={styles.arrow}
          >
            <Text
              style={
                index === items.length - 1
                  ? styles.disabled
                  : [styles.active, inverse && styles.inverse]
              }
            >
              ›
            </Text>
          </Pressable>
        </View>
      )}
    </View>
  );
}
const styles = StyleSheet.create({
  track: { paddingVertical: 12 },
  slot: { paddingHorizontal: 6 },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.line,
  },
  poster: {
    aspectRatio: 4 / 5,
    backgroundColor: colors.soft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  image: { width: '100%', height: '100%' },
  placeholder: { color: colors.muted, fontSize: 20 },
  badge: {
    position: 'absolute',
    left: 12,
    top: 12,
    borderRadius: 20,
    backgroundColor: colors.dark,
    paddingVertical: 6,
    paddingHorizontal: 10,
  },
  badgeText: { color: '#FFFFFF', fontSize: 11, fontWeight: '700' },
  info: { padding: 14, minHeight: 134, flexDirection: 'row', gap: 12 },
  calendar: {
    width: 52,
    alignItems: 'center',
    borderRightWidth: 1,
    borderRightColor: colors.line,
    paddingRight: 8,
  },
  calendarLabel: { color: colors.button, fontSize: 12, fontWeight: '800' },
  calendarDay: {
    color: colors.button,
    fontSize: 30,
    fontWeight: '800',
    marginVertical: 2,
  },
  metadata: { flex: 1 },
  date: {
    color: colors.accentText,
    fontSize: 11,
    fontWeight: '700',
    marginTop: 7,
  },
  title: {
    color: colors.dark,
    fontSize: 18,
    fontWeight: '800',
    lineHeight: 23,
  },
  place: { color: colors.muted, fontSize: 13, lineHeight: 18, marginTop: 7 },
  controls: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  arrow: {
    minWidth: 44,
    minHeight: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  active: { fontSize: 28, color: colors.dark },
  inverse: { color: '#FFFFFF' },
  disabled: { fontSize: 28, color: colors.line },
  counter: {
    fontSize: 12,
    color: colors.muted,
    minWidth: 52,
    textAlign: 'center',
  },
});
