import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AppHeader, { colors } from '../components/AppHeader';
import { Button } from '../components/State';
import { accountFieldsError } from '../lib/api';
import {
  AppSession,
  login,
  logout,
  restoreSession,
  saveSession,
} from '../lib/session';
import { useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { RootStack } from '../navigation';

export default function AccountScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStack>>();
  const [session, setSession] = useState<AppSession | null>(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState('');
  const mounted = useRef(true);
  const working = useRef(false);
  const restore = useCallback(async () => {
    if (working.current) {
      return;
    }
    working.current = true;
    setBusy(true);
    setError('');
    try {
      const saved = await restoreSession();
      if (mounted.current) {
        setSession(saved);
      }
    } catch {
      if (mounted.current) {
        setError(
          'No pudimos comprobar tu sesión. Revisá tu conexión o volvé a ingresar.',
        );
      }
    } finally {
      working.current = false;
      if (mounted.current) {
        setBusy(false);
      }
    }
  }, []);
  useEffect(() => {
    mounted.current = true;
    restore();
    return () => {
      mounted.current = false;
    };
  }, [restore]);
  const enter = async () => {
    if (working.current) {
      return;
    }
    const invalid =
      accountFieldsError('reactivate', email) ||
      (!password ? 'Ingresá tu contraseña.' : '');
    if (invalid) {
      setError(invalid);
      return;
    }
    working.current = true;
    setBusy(true);
    setError('');
    let created: AppSession | null = null;
    try {
      created = await login(email, password);
      await saveSession(created.token);
      if (mounted.current) {
        setSession(created);
        setEmail('');
      }
    } catch (reason) {
      if (created) {
        try {
          await logout(created.token);
        } catch {
          /* Session expires server-side. */
        }
      }
      if (mounted.current) {
        setError(
          reason instanceof Error ? reason.message : 'No pudimos ingresar.',
        );
      }
    } finally {
      working.current = false;
      if (mounted.current) {
        setPassword('');
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
    try {
      await logout(session.token);
      if (mounted.current) {
        setSession(null);
      }
    } catch {
      if (mounted.current) {
        setError(
          'No pudimos cerrar la sesión en el servidor. Revisá tu conexión y reintentá.',
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
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.screen}>
      <AppHeader title="Mi cuenta" />
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView
          style={styles.body}
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
        >
          {session ? (
            <>
              <Text style={styles.title}>Hola, {session.user.name}</Text>
              <Text style={styles.text}>{session.user.email}</Text>
              <Text style={styles.text}>
                Ya ingresaste con tu cuenta. La subida de fotos y la edición del
                perfil serán el próximo paso.
              </Text>
              <Button
                label={busy ? 'Cerrando…' : 'Cerrar sesión'}
                disabled={busy}
                onPress={exit}
              />
            </>
          ) : (
            <>
              <Text style={styles.title}>Ingresar</Text>
              <Text style={styles.text}>
                Podés explorar sin cuenta. Ingresá para participar.
              </Text>
              <Text style={styles.label}>Email</Text>
              <TextInput
                accessibilityLabel="Email"
                value={email}
                onChangeText={setEmail}
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="email-address"
                autoComplete="email"
                textContentType="username"
                maxLength={100}
                editable={!busy}
                style={styles.input}
              />
              <Text style={styles.label}>Contraseña</Text>
              <TextInput
                accessibilityLabel="Contraseña"
                value={password}
                onChangeText={setPassword}
                secureTextEntry
                autoCapitalize="none"
                autoCorrect={false}
                autoComplete="current-password"
                textContentType="password"
                maxLength={4096}
                editable={!busy}
                style={styles.input}
                onSubmitEditing={enter}
              />
              <Button
                label={busy ? 'Comprobando…' : 'Ingresar'}
                disabled={busy}
                onPress={enter}
              />
              <Button
                label="Recordar contraseña"
                disabled={busy}
                onPress={() =>
                  navigation.navigate('CuentaSolicitud', {
                    action: 'forgot-password',
                  })
                }
              />
              <Text style={styles.title}>¿Ya usabas la app anterior?</Text>
              <Text style={styles.text}>
                Tu cuenta y tus fotos se conservan. Reactivá tu cuenta por email
                y elegí una contraseña nueva.
              </Text>
              <Button
                label="Reactivar cuenta"
                disabled={busy}
                onPress={() =>
                  navigation.navigate('CuentaSolicitud', {
                    action: 'reactivate',
                  })
                }
              />
              <Text style={styles.text}>¿Sos nuevo en Foodtrucks UY?</Text>
              <Button
                label="Registrarme"
                disabled={busy}
                onPress={() =>
                  navigation.navigate('CuentaSolicitud', { action: 'register' })
                }
              />
            </>
          )}
          {error ? (
            <Text accessibilityLiveRegion="polite" style={styles.error}>
              {error}
            </Text>
          ) : null}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.dark },
  flex: { flex: 1 },
  body: { backgroundColor: colors.background },
  content: { padding: 20, gap: 16 },
  title: { fontSize: 24, color: colors.dark, fontWeight: '700' },
  label: { fontSize: 16, color: colors.dark, fontWeight: '600' },
  input: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#DDD',
    borderRadius: 12,
    padding: 14,
    fontSize: 17,
    color: colors.dark,
  },
  error: { color: '#A12323', fontSize: 16 },
  text: { fontSize: 16, lineHeight: 25, color: colors.muted },
});
