import React, { useEffect, useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
} from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { RootStack } from '../navigation';
import {
  AppSession,
  logout,
  restoreSession,
  updateProfile,
} from '../lib/session';
import State, { Button } from '../components/State';
import { colors } from '../components/AppHeader';

export default function AccountSettingsScreen({
  navigation,
}: NativeStackScreenProps<RootStack, 'ConfiguracionCuenta'>) {
  const [session, setSession] = useState<AppSession | null>(null);
  const [first, setFirst] = useState('');
  const [last, setLast] = useState('');
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
    try {
      const user = await updateProfile(session.token, first, last);
      if (mounted.current) {
        setSession({ ...session, user });
        setNotice('Guardamos tu perfil.');
      }
    } catch (reason) {
      if (mounted.current) {
        setError(
          reason instanceof Error
            ? reason.message
            : 'No pudimos guardar el perfil.',
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
});
