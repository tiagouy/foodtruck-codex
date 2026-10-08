import React from 'react';
import Renderer, { act } from 'react-test-renderer';
import PhotoMeta from '../src/components/PhotoMeta';
import { photoMapURL } from '../src/lib/photo-map';
import { openLink } from '../src/lib/links';
jest.mock('../src/lib/links', () => ({ openLink: jest.fn() }));
test('map URL prefers valid coordinates, supports zero, falls back to encoded place and omits empty locations', () => {
  expect(
    photoMapURL({ address: 'Expo Café', latitude: -34.9, longitude: -56.2 }),
  ).toContain('query=-34.9%2C-56.2');
  expect(
    photoMapURL({ address: 'Lugar', latitude: 0, longitude: 0 }),
  ).toContain('query=0%2C0');
  expect(
    photoMapURL({
      address: 'Expo Café & Uruguay',
      latitude: 200,
      longitude: null,
    }),
  ).toContain('query=Expo%20Caf%C3%A9%20%26%20Uruguay');
  expect(
    photoMapURL({ address: '', latitude: null, longitude: null }),
  ).toBeNull();
});
test('place link opens Maps without bubbling to the photo detail', async () => {
  let tree!: Renderer.ReactTestRenderer;
  const photo = {
    address: 'Expo Café Uruguay',
    latitude: -34.9,
    longitude: -56.2,
    created_at: '2026-10-08 12:00:00',
  };
  await act(async () => {
    tree = Renderer.create(<PhotoMeta photo={photo as any} />);
  });
  const stopPropagation = jest.fn();
  await act(async () =>
    tree.root
      .findByProps({ accessibilityRole: 'link' })
      .props.onPress({ stopPropagation }),
  );
  expect(stopPropagation).toHaveBeenCalledTimes(1);
  expect(openLink).toHaveBeenCalledWith(photoMapURL(photo));
  await act(async () => tree.unmount());
});
