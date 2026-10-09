import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  Image,
  Pressable,
  View,
  useWindowDimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AppHeader, { colors } from '../components/AppHeader';
import State, { Button } from '../components/State';
import MyPhotos from '../components/MyPhotos';
import { Settings, UserRound } from 'lucide-react-native';
import { mediaURL } from '../lib/config';
import { accountFieldsError } from '../lib/api';
import {
  AppSession,
  login,
  logout,
  restoreSession,
  saveSession,
} from '../lib/session';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { RootStack } from '../navigation';

export default function AccountScreen() {
  const avatarSize = Math.round(
    Math.min(useWindowDimensions().width * 0.2, 128),
  );
  const navigation = useNavigation<NativeStackNavigationProp<RootStack>>();
  const [session, setSession] = useState<AppSession | null>(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(true);
  const [checking, setChecking] = useState(true);
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
        setChecking(false);
      }
    }
  }, []);
  useEffect(() => {
    mounted.current = true;
    return () => {
      mounted.current = false;
    };
  }, []);
  useFocusEffect(
    useCallback(() => {
      restore();
    }, [restore]),
  );
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
      await saveSession(created.token, created.user.id);
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
  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.screen}>
      <AppHeader
        title="Mi cuenta"
        action={
          session ? (
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Configuración de mi cuenta"
              hitSlop={12}
              onPress={() => navigation.navigate('ConfiguracionCuenta')}
              style={styles.settings}
            >
              <Settings color="#FFFFFF" size={25} />
            </Pressable>
          ) : undefined
        }
      />
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView
          style={styles.body}
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
        >
          {checking && !session ? (
            <State loading />
          ) : session ? (
            <>
              <View style={styles.profileRow}>
                <Text style={styles.profileName}>{session.user.name}</Text>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Editar mi foto de perfil"
                  onPress={() => navigation.navigate('ConfiguracionCuenta')}
                  style={[
                    styles.avatar,
                    {
                      width: avatarSize,
                      height: avatarSize,
                      borderRadius: avatarSize / 2,
                    },
                  ]}
                >
                  {mediaURL(session.user.avatar) ? (
                    <Image
                      source={{ uri: mediaURL(session.user.avatar) }}
                      style={StyleSheet.absoluteFill}
                    />
                  ) : (
                    <UserRound color={colors.muted} size={avatarSize * 0.45} />
                  )}
                </Pressable>
              </View>
              <Button
                label="Subir foto"
                onPress={() => navigation.navigate('SubirFoto')}
              />
              <MyPhotos author={session.user.id} />
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
  settings: { padding: 8 },
  avatar: {
    backgroundColor: colors.line,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
    justifyContent: 'space-between',
  },
  profileName: { fontSize: 20, fontWeight: '600', color: colors.dark, flex: 1 },
  flex: { flex: 1 },
  body: { backgroundColor: colors.background },
  content: { padding: 20, gap: 16 },
  title: { fontSize: 24, color: colors.dark, fontWeight: '700' },
  label: { fontSize: 16, color: colors.dark, fontWeight: '600' },
  input: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 12,
    padding: 14,
    fontSize: 17,
    color: colors.dark,
  },
  error: { color: '#A12323', fontSize: 16 },
  text: { fontSize: 16, lineHeight: 25, color: colors.muted },
});
