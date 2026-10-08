import React, { useEffect, useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  Image,
  Pressable,
} from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { RootStack } from '../navigation';
import {
  AppSession,
  logout,
  restoreSession,
  updateProfile,
  uploadAvatar,
  ProfilePhoto,
} from '../lib/session';
import State, { Button } from '../components/State';
import { colors } from '../components/AppHeader';
import { mediaURL } from '../lib/config';
import { launchImageLibrary } from 'react-native-image-picker';
import { UserRound } from 'lucide-react-native';

export default function AccountSettingsScreen({
  navigation,
}: NativeStackScreenProps<RootStack, 'ConfiguracionCuenta'>) {
  const [session, setSession] = useState<AppSession | null>(null);
  const [first, setFirst] = useState('');
  const [last, setLast] = useState('');
  const [photo, setPhoto] = useState<ProfilePhoto | null>(null);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const mounted = useRef(true);
  const working = useRef(false);
  useEffect(() => {
    mounted.current = true;
    restoreSession()
      .then(saved => {
        if (!mounted.current) {
          return;
        }
        if (!saved) {
          navigation.goBack();
          return;
        }
        setSession(saved);
        setFirst(saved.user.first_name || saved.user.name);
        setLast(saved.user.last_name);
      })
      .catch(() => {
        if (mounted.current) {
          setError(
            'No pudimos comprobar tu cuenta. Volvé e intentá nuevamente.',
          );
        }
      })
      .finally(() => {
        if (mounted.current) {
          setBusy(false);
        }
      });
    return () => {
      mounted.current = false;
    };
  }, [navigation]);
  const selectPhoto = async () => {
    if (working.current || !session) {
      return;
    }
    working.current = true;
    setBusy(true);
    setError('');
    setNotice('');
    try {
      const result = await launchImageLibrary({
        mediaType: 'photo',
        selectionLimit: 1,
        assetRepresentationMode: 'compatible',
        maxWidth: 1600,
        maxHeight: 1600,
        quality: 0.9,
      });
      if (!mounted.current || result.didCancel) {
        return;
      }
      if (result.errorCode) {
        throw new Error(
          'No pudimos abrir tus fotos. Revisá los permisos y volvé a intentar.',
        );
      }
      const asset = result.assets?.[0];
      if (!asset?.uri) {
        throw new Error('No pudimos leer esa foto. Elegí otra.');
      }
      if ((asset.fileSize || 0) > 5 * 1024 * 1024) {
        throw new Error('La imagen pesa demasiado. Elegí una de hasta 5 MB.');
      }
      if (
        !['image/jpeg', 'image/png', 'image/webp'].includes(
          asset.type || 'image/jpeg',
        )
      ) {
        throw new Error(
          'No pudimos leer esa imagen. Elegí una foto JPG, PNG o WebP.',
        );
      }
      setPhoto({
        uri: asset.uri,
        type: asset.type || 'image/jpeg',
        name: asset.fileName || 'perfil.jpg',
      });
    } catch (reason) {
      if (mounted.current) {
        setError(
          reason instanceof Error
            ? reason.message
            : 'No pudimos seleccionar la foto.',
        );
      }
    } finally {
      working.current = false;
      if (mounted.current) {
        setBusy(false);
      }
    }
  };
  const save = async () => {
    if (!session || working.current) {
      return;
    }
    if (!first.trim()) {
      setError('Ingresá tu nombre.');
      return;
    }
    working.current = true;
    setBusy(true);
    setError('');
    setNotice('');
    let namesSaved = false;
    try {
      let user = await updateProfile(session.token, first, last);
      namesSaved = true;
      if (mounted.current) {
        setSession({ ...session, user });
      }
      if (photo) {
        user = await uploadAvatar(session.token, photo);
      }
      if (mounted.current) {
        setSession({ ...session, user });
        setPhoto(null);
        setNotice('Guardamos tu perfil.');
      }
    } catch (reason) {
      if (mounted.current) {
        setError(
          (namesSaved && photo
            ? 'Guardamos tu nombre y apellido, pero no la foto. '
            : '') +
            (reason instanceof Error
              ? reason.message
              : 'No pudimos guardar el perfil.'),
        );
      }
    } finally {
      working.current = false;
      if (mounted.current) {
        setBusy(false);
      }
    }
  };
  const exit = async () => {
    if (!session || working.current) {
      return;
    }
    working.current = true;
    setBusy(true);
    setError('');
    setNotice('');
    try {
      await logout(session.token);
      if (mounted.current) {
        navigation.goBack();
      }
    } catch {
      if (mounted.current) {
        setError(
          'No pudimos cerrar la sesión. Revisá tu conexión y reintentá.',
        );
      }
    } finally {
      working.current = false;
      if (mounted.current) {
        setBusy(false);
      }
    }
  };
  return (
    <KeyboardAvoidingView
      style={styles.screen}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={styles.content}
      >
        <Text style={styles.title}>Editar perfil</Text>
        {session ? (
          <>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Elegir foto de perfil"
              disabled={busy}
              onPress={selectPhoto}
              style={styles.avatar}
            >
              {photo?.uri || mediaURL(session.user.avatar) ? (
                <Image
                  source={{ uri: photo?.uri || mediaURL(session.user.avatar) }}
                  style={StyleSheet.absoluteFill}
                />
              ) : (
                <UserRound color={colors.muted} size={38} />
              )}
            </Pressable>
            <Button
              label={photo ? 'Elegir otra foto' : 'Cambiar foto de perfil'}
              disabled={busy}
              onPress={selectPhoto}
            />
            {photo ? (
              <Text style={styles.text}>
                La foto se guardará al tocar Guardar cambios.
              </Text>
            ) : null}
            <Text style={styles.label}>Nombre</Text>
            <TextInput
              accessibilityLabel="Nombre"
              value={first}
              onChangeText={setFirst}
              editable={!busy}
              maxLength={100}
              autoComplete="given-name"
              style={styles.input}
            />
            <Text style={styles.label}>Apellido</Text>
            <TextInput
              accessibilityLabel="Apellido"
              value={last}
              onChangeText={setLast}
              editable={!busy}
              maxLength={100}
              autoComplete="family-name"
              style={styles.input}
            />
            <Text style={styles.label}>Email</Text>
            <Text style={styles.text}>{session.user.email}</Text>
            <Text style={styles.text}>El email no se modifica desde acá.</Text>
            <Button
              label={busy ? 'Procesando…' : 'Guardar cambios'}
              disabled={busy}
              onPress={save}
            />
            <Button label="Cerrar sesión" disabled={busy} onPress={exit} />
          </>
        ) : busy ? (
          <State loading />
        ) : null}
        {notice ? (
          <Text accessibilityLiveRegion="polite" style={styles.text}>
            {notice}
          </Text>
        ) : null}
        {error ? (
          <Text accessibilityLiveRegion="polite" style={styles.error}>
            {error}
          </Text>
        ) : null}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: 20, gap: 16 },
  title: { fontSize: 24, fontWeight: '700', color: colors.dark },
  label: { fontSize: 16, fontWeight: '600', color: colors.dark },
  input: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 12,
    padding: 14,
    fontSize: 17,
    color: colors.dark,
  },
  text: { fontSize: 16, color: colors.muted },
  error: { fontSize: 16, color: '#A12323' },
  avatar: {
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: colors.line,
    overflow: 'hidden',
    alignSelf: 'center',
    alignItems: 'center',
    justifyContent: 'center',
  },
});
