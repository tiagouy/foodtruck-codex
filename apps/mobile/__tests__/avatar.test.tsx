import React from 'react';
import Renderer, { act } from 'react-test-renderer';
import { Image } from 'react-native';
import Avatar from '../src/components/Avatar';
import ContentCard from '../src/components/ContentCard';
test('missing avatar uses the person icon', async () => {
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<Avatar url={null} />);
  });
  expect(tree.root.findAllByType(Image)).toHaveLength(0);
  expect(
    tree.root.findAllByProps({ testID: 'avatar-placeholder' }).length,
  ).toBeGreaterThan(0);
  await act(async () => tree.unmount());
});
test('broken avatar falls back to icon and a replacement URL is loaded', async () => {
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<Avatar url="https://example.test/old.jpg" />);
  });
  expect(tree.root.findAllByType(Image)).toHaveLength(1);
  await act(async () => tree.root.findByType(Image).props.onError());
  expect(tree.root.findAllByType(Image)).toHaveLength(0);
  await act(async () =>
    tree.update(<Avatar url="https://example.test/new.jpg" />),
  );
  expect(tree.root.findByType(Image).props.source.uri).toBe(
    'https://example.test/new.jpg',
  );
  await act(async () => tree.unmount());
});
test('community card uses the publication author avatar', async () => {
  const photo = {
    id: 1,
    image: { thumbnail: null, full: null },
    author: {
      id: 2,
      name: 'Fixture',
      avatar: 'https://example.test/author.jpg',
    },
    created_at: '2026-10-07 12:00:00',
    address: '',
    caption: 'Foto',
  };
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <ContentCard
        kind="publications"
        item={photo as any}
        onPress={jest.fn()}
      />,
    );
  });
  expect(tree.root.findByType(Avatar).props.url).toBe(photo.author.avatar);
  await act(async () => tree.unmount());
});
