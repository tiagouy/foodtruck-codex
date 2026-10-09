import React from 'react';
import Renderer, { act } from 'react-test-renderer';
import { Text, Image, Alert, StyleSheet } from 'react-native';
import HomeScreen from '../src/screens/HomeScreen';
import AccountScreen from '../src/screens/AccountScreen';
import AccountSettingsScreen from '../src/screens/AccountSettingsScreen';
import DetailScreen from '../src/screens/DetailScreen';
import AccountRequestScreen from '../src/screens/AccountRequestScreen';
import { list, detail, accountRequest, request } from '../src/lib/api';
import { TextInput } from 'react-native';
import { Button } from '../src/components/State';
import {
  login,
  logout,
  restoreSession,
  saveSession,
  updateProfile,
  uploadAvatar,
} from '../src/lib/session';
jest.mock('../src/lib/session', () => ({
  login: jest.fn(),
  logout: jest.fn(),
  restoreSession: jest.fn(),
  saveSession: jest.fn(),
  updateProfile: jest.fn(),
  uploadAvatar: jest.fn(),
}));
import { launchImageLibrary } from 'react-native-image-picker';
jest.mock('react-native-image-picker', () => ({
  launchImageLibrary: jest.fn(),
}));

jest.mock('../src/lib/api', () => ({
  ...jest.requireActual('../src/lib/api'),
  list: jest.fn(),
  detail: jest.fn(),
  accountRequest: jest.fn(),
  request: jest.fn(),
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
  (restoreSession as jest.Mock).mockResolvedValue(null);
  (login as jest.Mock).mockReset();
  (saveSession as jest.Mock).mockReset();
  (logout as jest.Mock).mockReset();
  (uploadAvatar as jest.Mock).mockReset();
  (launchImageLibrary as jest.Mock).mockResolvedValue({ didCancel: true });
  mockList.mockReset();
  mockList.mockResolvedValue({ items: [], total: 0, page: 1 });
  mockDetail.mockReset();
  (accountRequest as jest.Mock).mockReset();
});
test.each(['events', 'publications'] as const)(
  '%s detail keeps the complete image with its own aspect ratio',
  async kind => {
    mockDetail.mockResolvedValue({
      id: 12,
      title: 'Evento',
      slug: 'evento',
      author: { id: 2, name: 'Fixture' },
      image: { full: 'https://example.com/poster.jpg' },
      caption: 'Foto',
      created_at: '',
      address: '',
      start_date: '2026-10-10',
      end_date: '2026-10-11',
    });
    let tree!: Renderer.ReactTestRenderer;
    await act(async () => {
      tree = Renderer.create(
        <DetailScreen
          route={{ params: { kind, contentKey: '12' } }}
          navigation={{ setOptions: jest.fn(), navigate: jest.fn() } as any}
          {...({} as any)}
        />,
      );
    });
    const image = tree.root.findAllByType(Image)[0];
    expect(image.props.resizeMode).toBe('contain');
    expect(StyleSheet.flatten(image.props.style).aspectRatio).toBe(
      kind === 'events' ? 4 / 5 : 1,
    );
    await act(async () => tree.unmount());
  },
);
test('home works without community activity and distinguishes empty directory/history', async () => {
  mockList.mockResolvedValue({ items: [], total: 0, page: 1 });
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<HomeScreen />);
  });
  expect(texts(tree)).toContain('no hay próximos eventos');
  expect(texts(tree)).toContain('preparando el directorio');
  expect(texts(tree)).toContain('Todavía no hay fotos');
  expect(texts(tree)).not.toContain('Eventos, foodtrucks y momentos');
  const logo = tree.root.findByProps({ accessibilityLabel: 'Food Truck UY' });
  expect(logo.props.source).toBe(
    require('../assets/branding/splash-logo-1024.png'),
  );
  expect(logo.props.resizeMode).toBe('contain');
  expect(StyleSheet.flatten(logo.props.style).alignSelf).toBe('center');
  await act(async () => tree.unmount());
});
test('photo header confirms report and sends only authenticated publication reference', async () => {
  mockDetail.mockResolvedValue({
    id: 12,
    author: { id: 2, name: 'Fixture' },
    image: {},
    caption: 'Foto',
    created_at: '',
    address: '',
  });
  (restoreSession as jest.Mock).mockResolvedValue({ token: 'fixture-token' });
  (request as jest.Mock).mockResolvedValue({ received: true });
  const alerts = jest.spyOn(Alert, 'alert').mockImplementation(() => {});
  const navigation = { setOptions: jest.fn(), navigate: jest.fn() };
  let tree!: Renderer.ReactTestRenderer;
  let header!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <DetailScreen
        route={{ params: { kind: 'publications', contentKey: '12' } }}
        navigation={navigation as any}
        {...({} as any)}
      />,
    );
  });
  await act(async () => {
    header = Renderer.create(
      navigation.setOptions.mock.calls.at(-1)![0].headerRight(),
    );
  });
  const button = header.root.findByProps({
    accessibilityLabel: 'Denunciar publicación',
  });
  await act(async () => button.props.onPress());
  expect(request).not.toHaveBeenCalled();
  const confirm = alerts.mock.calls.at(-1)![2]![1].onPress!;
  await act(async () => {
    await confirm();
  });
  expect(request).toHaveBeenCalledWith(
    'publications/12/report',
    undefined,
    {},
    'fixture-token',
  );
  expect(alerts.mock.calls.at(-1)![0]).toBe('Denuncia recibida');
  (restoreSession as jest.Mock).mockResolvedValue(null);
  (request as jest.Mock).mockClear();
  await act(async () => {
    await confirm();
  });
  expect(request).not.toHaveBeenCalled();
  expect(alerts.mock.calls.at(-1)![0]).toBe('Ingresá a tu cuenta');
  await act(async () => {
    header.unmount();
    tree.unmount();
  });
  alerts.mockRestore();
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
test('account shows native email/password first without a website login button', async () => {
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<AccountScreen />);
  });
  expect(texts(tree)).toContain('Reactivar cuenta');
  expect(tree.root.findAllByType(TextInput)).toHaveLength(2);
  expect(tree.root.findAllByType(Button)[0].props.label).toBe('Ingresar');
  expect(texts(tree)).not.toContain('Ingresar en el sitio');
  await act(async () => tree.unmount());
});

test('native login opens own photo grid and moves logout out of the main account', async () => {
  const session = {
    token: 'fixture-token',
    user: { id: 9, name: 'Fixture', email: 'fixture@example.test' },
  };
  (login as jest.Mock).mockResolvedValue(session);
  (saveSession as jest.Mock).mockResolvedValue(undefined);
  (logout as jest.Mock).mockResolvedValue(undefined);
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<AccountScreen />);
  });
  await act(async () => {
    tree.root
      .findAllByType(TextInput)[0]
      .props.onChangeText('fixture@example.test');
    tree.root
      .findAllByType(TextInput)[1]
      .props.onChangeText('fixture-password');
  });
  await act(async () => tree.root.findAllByType(Button)[0].props.onPress());
  expect(saveSession).toHaveBeenCalledWith('fixture-token', 9);
  expect(texts(tree)).toContain('Fixture');
  expect(texts(tree)).toContain('Mis fotos');
  expect(texts(tree)).not.toContain('Cerrar sesión');
  expect(mockList).toHaveBeenCalledWith(
    'publications',
    1,
    '',
    12,
    expect.any(AbortSignal),
    9,
  );
  expect(tree.root.findAllByType(TextInput)).toHaveLength(0);
  await act(async () => tree.unmount());
});

test('account does not flash the login form while restoring a saved session', async () => {
  let resolve!: (value: unknown) => void;
  (restoreSession as jest.Mock).mockReturnValue(
    new Promise(done => {
      resolve = done;
    }),
  );
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<AccountScreen />);
  });
  expect(tree.root.findAllByType(TextInput)).toHaveLength(0);
  await act(async () => resolve(null));
  expect(tree.root.findAllByType(TextInput)).toHaveLength(2);
  await act(async () => tree.unmount());
});

test('saved account renders three own photos and has a settings control', async () => {
  (restoreSession as jest.Mock).mockResolvedValue({
    token: 'fixture',
    user: { id: 9, name: 'Fixture', email: 'fixture@example.test' },
  });
  mockList.mockResolvedValue({
    items: [1, 2, 3].map(id => ({
      id,
      caption: `Foto ${id}`,
      image: { full: 'https://example.test/photo.jpg', thumbnail: null },
    })),
    total: 3,
    page: 1,
  });
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(<AccountScreen />);
  });
  expect(tree.root.findAllByType(Image)).toHaveLength(3);
  expect(
    tree.root.findAllByProps({
      accessibilityLabel: 'Configuración de mi cuenta',
    }).length,
  ).toBeGreaterThan(0);
  await act(async () => tree.unmount());
});

test('settings edits names and closes the session from inside settings', async () => {
  const session = {
    token: 'fixture-token',
    user: {
      id: 9,
      name: 'Fixture Original',
      first_name: 'Fixture',
      last_name: 'Original',
      email: 'fixture@example.test',
    },
  };
  (restoreSession as jest.Mock).mockResolvedValue(session);
  (updateProfile as jest.Mock).mockResolvedValue({
    ...session.user,
    name: 'Fixture Nuevo',
    last_name: 'Nuevo',
  });
  (logout as jest.Mock).mockResolvedValue(undefined);
  const back = jest.fn();
  const navigation = { goBack: back };
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <AccountSettingsScreen navigation={navigation as any} {...({} as any)} />,
    );
  });
  await act(async () =>
    tree.root.findAllByType(TextInput)[1].props.onChangeText('Nuevo'),
  );
  await act(async () =>
    tree.root
      .findAllByType(Button)
      .find(button => button.props.label === 'Guardar cambios')!
      .props.onPress(),
  );
  expect(updateProfile).toHaveBeenCalledWith(
    'fixture-token',
    'Fixture',
    'Nuevo',
  );
  expect(texts(tree)).toContain('Guardamos tu nombre y apellido');
  await act(async () =>
    tree.root
      .findAllByType(Button)
      .find(button => button.props.label === 'Cerrar sesión')!
      .props.onPress(),
  );
  expect(logout).toHaveBeenCalledWith('fixture-token');
  expect(back).toHaveBeenCalled();
  await act(async () => tree.unmount());
});

test('iOS image/jpg is normalized to JPEG, previewed and uploaded only when saving', async () => {
  const user = {
    id: 9,
    name: 'Fixture',
    first_name: 'Fixture',
    last_name: '',
    email: 'fixture@example.test',
  };
  (restoreSession as jest.Mock).mockResolvedValue({
    token: 'fixture-token',
    user,
  });
  (launchImageLibrary as jest.Mock).mockResolvedValue({
    assets: [
      {
        uri: 'file:///fixture.jpg',
        fileName: 'fixture.jpg',
        type: 'image/jpg',
        fileSize: 1000,
      },
    ],
  });
  (updateProfile as jest.Mock).mockResolvedValue(user);
  (uploadAvatar as jest.Mock).mockResolvedValue({
    ...user,
    avatar: 'https://example.test/avatar.jpg',
  });
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <AccountSettingsScreen
        navigation={{ goBack: jest.fn() } as any}
        {...({} as any)}
      />,
    );
  });
  await act(async () =>
    tree.root
      .findAllByType(Button)
      .find(button => button.props.label === 'Cambiar foto de perfil')!
      .props.onPress(),
  );
  expect(uploadAvatar).not.toHaveBeenCalled();
  expect(texts(tree)).toContain('La foto se guardará');
  await act(async () =>
    tree.root
      .findAllByType(Button)
      .find(button => button.props.label === 'Guardar cambios')!
      .props.onPress(),
  );
  expect(uploadAvatar).toHaveBeenCalledWith('fixture-token', {
    uri: 'file:///fixture.jpg',
    name: 'fixture.jpg',
    type: 'image/jpeg',
  });
  expect(texts(tree)).toContain('Guardamos tu perfil');
  await act(async () => tree.unmount());
});

test('oversized profile photo is rejected before upload', async () => {
  (restoreSession as jest.Mock).mockResolvedValue({
    token: 'fixture',
    user: {
      id: 9,
      name: 'Fixture',
      first_name: 'Fixture',
      last_name: '',
      email: 'fixture@example.test',
    },
  });
  (launchImageLibrary as jest.Mock).mockResolvedValue({
    assets: [
      {
        uri: 'file:///large.jpg',
        type: 'image/jpeg',
        fileSize: 6 * 1024 * 1024,
      },
    ],
  });
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <AccountSettingsScreen
        navigation={{ goBack: jest.fn() } as any}
        {...({} as any)}
      />,
    );
  });
  await act(async () => tree.root.findAllByType(Button)[0].props.onPress());
  expect(texts(tree)).toContain('pesa demasiado');
  expect(uploadAvatar).not.toHaveBeenCalled();
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
        navigation={{ setOptions: jest.fn(), navigate: jest.fn() } as any}
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
