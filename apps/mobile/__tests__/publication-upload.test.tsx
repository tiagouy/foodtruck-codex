import React from 'react';
import Renderer, { act } from 'react-test-renderer';
import { TextInput, Text } from 'react-native';
import { launchImageLibrary } from 'react-native-image-picker';
import ImageCropPicker from 'react-native-image-crop-picker';
import { APIError, request } from '../src/lib/api';
import { restoreSession } from '../src/lib/session';
import {
  choosePublicationPhoto,
  publishPhoto,
} from '../src/lib/publication-upload';
import PublicationUploadScreen from '../src/screens/PublicationUploadScreen';
import { Button } from '../src/components/State';
jest.mock('react-native-image-picker', () => ({
  launchImageLibrary: jest.fn(),
}));
jest.mock('react-native-image-crop-picker', () => ({
  openCropper: jest.fn(),
}));
jest.mock('../src/lib/api', () => ({
  ...jest.requireActual('../src/lib/api'),
  request: jest.fn(),
}));
jest.mock('../src/lib/session', () => ({ restoreSession: jest.fn() }));
jest.mock('@react-navigation/native', () => ({
  useFocusEffect: (callback: () => void) =>
    require('react').useEffect(callback, [callback]),
}));
const photo = {
  uri: 'file:///fixture.jpg',
  type: 'image/jpeg',
  name: 'fixture.jpg',
};
const navigation = {
  navigate: jest.fn(),
  replace: jest.fn(),
  goBack: jest.fn(),
};
test('autocomplete uses plain rows and selecting one fills the address', async () => {
  jest.useFakeTimers();
  (request as jest.Mock).mockImplementation((path: string) =>
    Promise.resolve(
      path === 'places/autocomplete'
        ? {
            suggestions: [
              { id: 'fixture-place', label: 'Villa Dolores, Montevideo' },
            ],
          }
        : {
            address: 'Calle de prueba 123, Montevideo',
            latitude: -34.9,
            longitude: -56.2,
          },
    ),
  );
  let tree!: Renderer.ReactTestRenderer;
  try {
    await act(async () => {
      tree = Renderer.create(
        <PublicationUploadScreen
          navigation={navigation as any}
          {...({} as any)}
        />,
      );
    });
    await act(async () => {
      tree.root
        .findByProps({ accessibilityLabel: 'Dirección de la foto' })
        .props.onChangeText('Villa');
    });
    await act(async () => {
      jest.advanceTimersByTime(450);
    });
    expect(request).toHaveBeenCalled();
    await act(async () => {});
    const row = tree.root.findByProps({
      accessibilityLabel: 'Villa Dolores, Montevideo',
    });
    expect(row).toBeDefined();
    expect(
      tree.root
        .findAllByType(Button)
        .some(b => b.props.label === 'Villa Dolores, Montevideo'),
    ).toBe(false);
    await act(async () => {
      await row.props.onPress();
    });
    expect(
      tree.root.findByProps({ accessibilityLabel: 'Dirección de la foto' })
        .props.value,
    ).toBe('Villa Dolores');
    expect(
      (request as jest.Mock).mock.calls.some(
        c => c[0] === 'places/details' && c[2].place_id === 'fixture-place',
      ),
    ).toBe(true);
  } finally {
    if (tree) {
      await act(async () => tree.unmount());
    }
    jest.useRealTimers();
  }
});
beforeEach(() => {
  jest.clearAllMocks();
  (restoreSession as jest.Mock).mockResolvedValue({ token: 'fixture' });
  (launchImageLibrary as jest.Mock).mockResolvedValue({
    assets: [{ ...photo, fileSize: 1000 }],
  });
  (ImageCropPicker.openCropper as jest.Mock).mockResolvedValue({
    path: '/tmp/fixture-cropped.jpg',
    mime: 'image/jpeg',
    width: 900,
    height: 900,
    size: 1000,
  });
});
test('picker normalizes iOS JPEG, cancels safely and rejects oversized files', async () => {
  (launchImageLibrary as jest.Mock).mockResolvedValue({
    assets: [{ ...photo, type: 'image/jpg' }],
  });
  expect((await choosePublicationPhoto())?.type).toBe('image/jpeg');
  expect(ImageCropPicker.openCropper).toHaveBeenCalledWith(
    expect.objectContaining({
      path: photo.uri,
      width: 900,
      height: 900,
      freeStyleCropEnabled: false,
      cropperChooseText: 'Usar foto',
    }),
  );
  (launchImageLibrary as jest.Mock).mockResolvedValue({ didCancel: true });
  expect(await choosePublicationPhoto()).toBeNull();
  (launchImageLibrary as jest.Mock).mockResolvedValue({
    assets: [{ ...photo, fileSize: 6 * 1024 * 1024 }],
  });
  await expect(choosePublicationPhoto()).rejects.toThrow('pesa demasiado');
});
test('uses the actual cropped file, handles crop cancellation and rejects invalid crop results', async () => {
  expect(await choosePublicationPhoto()).toEqual({
    uri: 'file:///tmp/fixture-cropped.jpg',
    type: 'image/jpeg',
    name: 'foto-recortada.jpg',
  });
  (ImageCropPicker.openCropper as jest.Mock).mockRejectedValue({
    code: 'E_PICKER_CANCELLED',
  });
  expect(await choosePublicationPhoto()).toBeNull();
  (ImageCropPicker.openCropper as jest.Mock).mockResolvedValue({
    path: '/tmp/crop.jpg',
    mime: 'image/jpeg',
    width: 900,
    height: 600,
    size: 1000,
  });
  await expect(choosePublicationPhoto()).rejects.toThrow('recortar');
  (ImageCropPicker.openCropper as jest.Mock).mockRejectedValue(
    new Error('Native failure'),
  );
  await expect(choosePublicationPhoto()).rejects.toThrow('recortar');
});
test('canceling a replacement crop preserves the previous preview', async () => {
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <PublicationUploadScreen
        navigation={navigation as any}
        {...({} as any)}
      />,
    );
  });
  const button = (label: string) =>
    tree.root.findAllByType(Button).find(b => b.props.label === label)!;
  await act(async () => {
    await button('Elegir foto').props.onPress();
  });
  const preview = () =>
    tree.root.findByProps({ accessibilityLabel: 'Vista previa de tu foto' });
  expect(preview().props.source.uri).toBe('file:///tmp/fixture-cropped.jpg');
  (ImageCropPicker.openCropper as jest.Mock).mockRejectedValue({
    code: 'E_PICKER_CANCELLED',
  });
  await act(async () => {
    await button('Cambiar foto').props.onPress();
  });
  expect(preview().props.source.uri).toBe('file:///tmp/fixture-cropped.jpg');
  expect(request).not.toHaveBeenCalled();
  await act(async () => tree.unmount());
});
test('multipart contains no author, status or rating; coordinates match only unchanged address', async () => {
  (request as jest.Mock).mockResolvedValue({ publication_id: 25 });
  expect(
    await publishPhoto(
      'fixture',
      'request-fixture-123456',
      photo,
      'Texto',
      'Nueva dirección',
      { address: 'Vieja dirección', latitude: -34, longitude: -56 },
    ),
  ).toBe(25);
  const form = (request as jest.Mock).mock.calls[0][2];
  const fields = Array.from(form.keys());
  expect(fields).toEqual(['request_id', 'photo', 'caption', 'address']);
  (request as jest.Mock).mockResolvedValue({ publication_id: 'incorrecto' });
  await expect(
    publishPhoto(
      'fixture',
      'request-fixture-123456',
      photo,
      'Texto',
      'Lugar',
      null,
    ),
  ).rejects.toThrow('confirmar');
});
test('guest is invited to sign in instead of seeing upload controls', async () => {
  (restoreSession as jest.Mock).mockResolvedValue(null);
  let tree!: Renderer.ReactTestRenderer;
  await act(async () => {
    tree = Renderer.create(
      <PublicationUploadScreen
        navigation={navigation as any}
        {...({} as any)}
      />,
    );
  });
  expect(tree.root.findAllByType(TextInput)).toHaveLength(0);
  await act(async () => tree.root.findByType(Button).props.onPress());
  expect(navigation.navigate).toHaveBeenCalledWith('Principal', {
    screen: 'Cuenta',
  });
  await act(async () => tree.unmount());
});
test('upload keeps selected place name, postal address and coordinates separately', async () => {
  (request as jest.Mock).mockResolvedValue({ publication_id: 25 });
  await publishPhoto(
    'fixture',
    'request-fixture-123456',
    photo,
    'Texto',
    'Expo Café Uruguay',
    {
      name: 'Expo Café Uruguay',
      address: 'Calle 123, Montevideo',
      latitude: -34.9,
      longitude: -56.2,
    },
  );
  const form = (request as jest.Mock).mock.calls[0][2];
  expect(form.get('address')).toBe('Expo Café Uruguay');
  expect(form.get('street_address')).toBe('Calle 123, Montevideo');
  expect(form.get('latitude')).toBe('-34.9');
  expect(form.get('longitude')).toBe('-56.2');
});
test('upload validates, publishes directly and retries unknown outcome with the same request id', async () => {
  let tree!: Renderer.ReactTestRenderer;
  (request as jest.Mock).mockImplementation((path: string) =>
    path === 'publications/upload'
      ? Promise.reject(new APIError('Sin conexión'))
      : Promise.resolve({ suggestions: [] }),
  );
  await act(async () => {
    tree = Renderer.create(
      <PublicationUploadScreen
        navigation={navigation as any}
        {...({} as any)}
      />,
    );
  });
  const button = (label: string) =>
    tree.root.findAllByType(Button).find(b => b.props.label === label)!;
  await act(async () => {
    await button('Publicar foto').props.onPress();
  });
  expect(request).not.toHaveBeenCalled();
  await act(async () => {
    await button('Elegir foto').props.onPress();
  });
  await act(async () => {
    tree.root
      .findByProps({ accessibilityLabel: 'Texto de la foto' })
      .props.onChangeText('Texto');
    tree.root
      .findByProps({ accessibilityLabel: 'Dirección de la foto' })
      .props.onChangeText('Dirección manual');
  });
  await act(async () => {
    await button('Publicar foto').props.onPress();
  });
  expect(
    tree.root.findByProps({ accessibilityLabel: 'Texto de la foto' }).props
      .editable,
  ).toBe(false);
  const first = (request as jest.Mock).mock.calls
    .find(c => c[0] === 'publications/upload')![2]
    .get('request_id');
  (request as jest.Mock).mockRejectedValue(
    new APIError('Hay una foto procesándose.', 429),
  );
  await act(async () => {
    await button('Reintentar publicación').props.onPress();
  });
  expect(
    tree.root.findByProps({ accessibilityLabel: 'Texto de la foto' }).props
      .editable,
  ).toBe(false);
  (request as jest.Mock).mockResolvedValue({ publication_id: 25 });
  await act(async () => {
    await button('Reintentar publicación').props.onPress();
  });
  const calls = (request as jest.Mock).mock.calls.filter(
    c => c[0] === 'publications/upload',
  );
  expect(calls[1][2].get('request_id')).toBe(first);
  expect(calls[2][2].get('request_id')).toBe(first);
  expect(
    tree.root
      .findAllByType(Text)
      .some(t => t.props.children === '¡Foto publicada!'),
  ).toBe(true);
  await act(async () => button('Ver mi foto').props.onPress());
  expect(navigation.replace).toHaveBeenCalledWith('Detalle', {
    kind: 'publications',
    contentKey: '25',
  });
  await act(async () => tree.unmount());
});
