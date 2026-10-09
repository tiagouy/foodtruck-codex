import React from 'react';
import Renderer, { act } from 'react-test-renderer';
import { Animated, Image, StyleSheet } from 'react-native';
import EventCarousel, {
  carouselGeometry,
} from '../src/components/EventCarousel';
import { Event } from '../src/lib/api';

const events = [1, 2, 3].map(
  id =>
    ({
      id,
      slug: `evento-${id}`,
      title: `Evento ${id}`,
      start_date: '2026-10-12',
      end_date: '2026-10-12',
      venue: 'Plaza',
      entry_type: 'free',
      cancelled: false,
      image: { full: 'https://example.com/poster.jpg', thumbnail: null },
    } as Event),
);

test('carousel centers portrait posters with room for neighboring cards at phone and tablet widths', () => {
  [320, 402, 820].forEach(width => {
    const { card, step, inset } = carouselGeometry(width);
    expect(card).toBeLessThanOrEqual(360);
    expect(step + inset * 2).toBeCloseTo(width);
    expect(inset).toBeGreaterThan(0);
  });
});

test('carousel opens the chosen real event, shows date and uses snap intervals', async () => {
  const open = jest.fn();
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<EventCarousel items={events} onPress={open} />);
  });
  const list = tree.root.findByType(Animated.FlatList);
  expect(list.props.horizontal).toBe(true);
  expect(list.props.snapToInterval).toBeGreaterThan(0);
  const card = tree.root.findByProps({
    accessibilityLabel: 'Ver Evento 2, 12/10/2026',
  });
  await act(async () => card.props.onPress());
  expect(open).toHaveBeenCalledWith(events[1]);
  expect(tree.root.findAllByType(Image).length).toBe(3);
  const imageWrap = tree.root.findAllByType(Image)[0].parent!;
  expect(StyleSheet.flatten(imageWrap.props.style).aspectRatio).toBe(4 / 5);
  expect(tree.root.findAllByType(Image)[0].props.resizeMode).toBe('contain');
  await act(async () =>
    list.props.onMomentumScrollEnd({
      nativeEvent: { contentOffset: { x: list.props.snapToInterval } },
    }),
  );
  expect(
    tree.root.findByProps({ accessibilityLabel: 'Evento anterior' }).props
      .disabled,
  ).toBe(false);
  await act(async () => tree.unmount());
});
