import React from 'react';
import Renderer, { act } from 'react-test-renderer';
import { Text } from 'react-native';
import HomeScreen from '../src/screens/HomeScreen';
import AccountScreen from '../src/screens/AccountScreen';
import DetailScreen from '../src/screens/DetailScreen';
import AccountRequestScreen from '../src/screens/AccountRequestScreen';
import { list, detail, accountRequest } from '../src/lib/api';
import { TextInput } from 'react-native';
import { Button } from '../src/components/State';

jest.mock('../src/lib/api', () => ({
  ...jest.requireActual('../src/lib/api'),
  list: jest.fn(),
  detail: jest.fn(),
  accountRequest: jest.fn(),
}));
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
  (accountRequest as jest.Mock).mockReset();
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
  expect(texts(tree)).toContain('Reactivar cuenta');
  expect(texts(tree)).toContain('sesión en la app');
  await act(async () => tree.unmount());
});

test('native reactivate sends email without password and shows a generic confirmation', async () => {
  (accountRequest as jest.Mock).mockResolvedValue(
    'Si corresponde, te enviamos un correo.',
  );
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <AccountRequestScreen
        route={{ params: { action: 'reactivate' } }}
        navigation={{ goBack: jest.fn() }}
        {...({} as any)}
      />,
    );
  });
  expect(tree.root.findAllByType(TextInput)).toHaveLength(1);
  await act(async () =>
    tree.root.findByType(TextInput).props.onChangeText('fixture@example.test'),
  );
  await act(async () => tree.root.findByType(Button).props.onPress());
  expect(accountRequest).toHaveBeenCalledWith(
    'reactivate',
    'fixture@example.test',
    '',
    expect.any(AbortSignal),
  );
  expect(texts(tree)).toContain('Si corresponde');
  expect(tree.root.findAllByType(TextInput)).toHaveLength(0);
  await act(async () => tree.unmount());
});
test('native register validates name before contacting the server', async () => {
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <AccountRequestScreen
        route={{ params: { action: 'register' } }}
        navigation={{ goBack: jest.fn() }}
        {...({} as any)}
      />,
    );
  });
  const email = tree.root
    .findAllByType(TextInput)
    .find(input => input.props.accessibilityLabel === 'Email')!;
  await act(async () => email.props.onChangeText('fixture@example.test'));
  await act(async () => tree.root.findByType(Button).props.onPress());
  expect(texts(tree)).toContain('Ingresá tu nombre');
  expect(accountRequest).not.toHaveBeenCalled();
  await act(async () => tree.unmount());
});
test('native password recovery keeps the form usable after an error', async () => {
  (accountRequest as jest.Mock).mockRejectedValue(
    new Error('Probá nuevamente más tarde.'),
  );
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <AccountRequestScreen
        route={{ params: { action: 'forgot-password' } }}
        navigation={{ goBack: jest.fn() }}
        {...({} as any)}
      />,
    );
  });
  await act(async () =>
    tree.root.findByType(TextInput).props.onChangeText('fixture@example.test'),
  );
  await act(async () => tree.root.findByType(Button).props.onPress());
  expect(texts(tree)).toContain('Probá nuevamente más tarde');
  expect(tree.root.findByType(TextInput).props.editable).toBe(true);
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
