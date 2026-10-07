import React from 'react';
import Renderer, { act } from 'react-test-renderer';
import { Text } from 'react-native';
import HomeScreen from '../src/screens/HomeScreen';
import AccountScreen from '../src/screens/AccountScreen';
import DetailScreen from '../src/screens/DetailScreen';
import { list, detail } from '../src/lib/api';

jest.mock('../src/lib/api', () => ({ list: jest.fn(), detail: jest.fn() }));
jest.mock('@react-navigation/native', () => ({
  useNavigation: () => ({ navigate: jest.fn() }),
  useFocusEffect: (callback: () => void) => {
    const ReactModule = require('react');
    ReactModule.useEffect(callback, [callback]);
  },
}));
jest.mock('react-native-safe-area-context', () => ({
  SafeAreaView: require('react-native').View,
}));
const mockList = list as jest.Mock;
const mockDetail = detail as jest.Mock;
function texts(tree: Renderer.ReactTestRenderer) {
  return tree.root
    .findAllByType(Text)
    .map(node => node.props.children)
    .flat()
    .join(' ');
}
beforeEach(() => {
  mockList.mockReset();
  mockDetail.mockReset();
});
test('home works without community activity and distinguishes empty directory/history', async () => {
  mockList.mockResolvedValue({ items: [], total: 0, page: 1 });
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<HomeScreen />);
  });
  expect(texts(tree)).toContain('no hay próximos eventos');
  expect(texts(tree)).toContain('preparando el directorio');
  expect(texts(tree)).toContain('Todavía no hay fotos');
  await act(async () => tree.unmount());
});
test('one home resource failure does not hide the other resources', async () => {
  mockList.mockImplementation((kind: string) =>
    kind === 'events'
      ? Promise.reject(new Error('Error de agenda'))
      : Promise.resolve({ items: [], total: 0, page: 1 }),
  );
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<HomeScreen />);
  });
  expect(texts(tree)).toContain('Error de agenda');
  expect(texts(tree)).toContain('preparando el directorio');
  await act(async () => tree.unmount());
});
test('account is explicit about web fallback rather than pretending native login exists', async () => {
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<AccountScreen />);
  });
  expect(texts(tree)).toContain('Reactivar cuenta en el sitio');
  expect(texts(tree)).toContain('no inician una sesión en la app');
  await act(async () => tree.unmount());
});
test('unpublished detail shows no stale photo or caption', async () => {
  mockDetail.mockRejectedValue(
    new Error('Este contenido ya no está disponible.'),
  );
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <DetailScreen
        route={{ params: { kind: 'publications', contentKey: '1' } }}
        {...({} as any)}
      />,
    );
  });
  expect(texts(tree)).toContain('ya no está disponible');
  expect(tree.root.findAllByType(require('react-native').Image)).toHaveLength(
    0,
  );
  await act(async () => tree.unmount());
});
