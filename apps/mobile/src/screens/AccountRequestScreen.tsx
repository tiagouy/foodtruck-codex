import React, { useCallback, useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { accountFieldsError, accountRequest } from '../lib/api';
import { RootStack } from '../navigation';
import { colors } from '../components/AppHeader';
import State, { Button } from '../components/State';

const copy = {
  reactivate: {
    title: 'Reactivá tu cuenta',
    description:
      'Usá el email que tenías en la app anterior. Si corresponde, recibirás un enlace para elegir una contraseña nueva. Tus fotos siguen asociadas a tu cuenta.',
    button: 'Solicitar reactivación',
  },
  register: {
    title: 'Creá tu cuenta',
    description:
      'Ingresá tu nombre y email. Para confirmar la cuenta y elegir una contraseña, abrí el enlace del correo. Si ya tenías una cuenta, usá Reactivar cuenta o Recordar contraseña.',
    button: 'Solicitar registro',
  },
  'forgot-password': {
    title: 'Recordar contraseña',
    description:
      'Ingresá tu email. Si corresponde, recibirás un enlace para elegir una contraseña nueva. No te enviaremos tu contraseña anterior.',
    button: 'Solicitar enlace',
  },
};
export default function AccountRequestScreen({
  route,
  navigation,
}: NativeStackScreenProps<RootStack, 'CuentaSolicitud'>) {
  const { action } = route.params;
  const [email, setEmail] = useState('');
  const [name, setName] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const request = useRef<AbortController | null>(null);
  const live = useRef(false);
  useFocusEffect(
    useCallback(() => {
      live.current = true;
      setBusy(false);
      return () => {
        live.current = false;
        request.current?.abort();
        request.current = null;
      };
    }, []),
  );
  async function submit() {
    if (request.current) {
      return;
    }
    const invalid = accountFieldsError(action, email, name);
    if (invalid) {
      setError(invalid);
      return;
    }
    const controller = new AbortController();
    request.current = controller;
    setBusy(true);
    setError('');
    try {
      const message = await accountRequest(
        action,
        email,
        name,
        controller.signal,
      );
      if (live.current && request.current === controller) {
        setConfirmation(message);
        setEmail('');
        setName('');
      }
    } catch (failure) {
      if (
        live.current &&
        request.current === controller &&
        !controller.signal.aborted
      ) {
        setError((failure as Error).message);
      }
    } finally {
      if (request.current === controller) {
        request.current = null;
        if (live.current) {
          setBusy(false);
        }
      }
    }
  }
  return (
    <KeyboardAvoidingView
      style={styles.screen}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={styles.content}
      >
        <Text accessibilityRole="header" style={styles.title}>
          {copy[action].title}
        </Text>
        {confirmation ? (
          <>
            <State message={confirmation} />
            <Text style={styles.text}>
              Revisá el correo y spam. El enlace vence y solo puede usarse una
              vez.
            </Text>
            <Button
              label="Volver a Mi cuenta"
              onPress={() => navigation.goBack()}
            />
          </>
        ) : (
          <>
            <Text style={styles.text}>{copy[action].description}</Text>
            {action === 'register' && (
              <>
                <Text style={styles.label}>Nombre</Text>
                <TextInput
                  accessibilityLabel="Nombre"
                  value={name}
                  onChangeText={setName}
                  maxLength={200}
                  editable={!busy}
                  autoComplete="name"
                  style={styles.input}
                />
              </>
            )}
            <Text style={styles.label}>Email</Text>
            <TextInput
              accessibilityLabel="Email"
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              autoComplete="email"
              textContentType="emailAddress"
              maxLength={100}
              editable={!busy}
              returnKeyType="send"
              onSubmitEditing={submit}
              style={styles.input}
            />
            {error && <State message={error} />}
            {busy && <State loading />}
            <Button
              label={copy[action].button}
              disabled={busy}
              onPress={submit}
            />
          </>
        )}
        {__DEV__ && (
          <Text style={styles.local}>
            Pruebas locales: los correos se capturan en WordPress → Usuarios →
            Correos de cuentas; no salen emails reales.
          </Text>
        )}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: 22, paddingBottom: 45, gap: 16 },
  title: { fontSize: 26, fontWeight: '800', color: colors.dark },
  text: { fontSize: 16, lineHeight: 25, color: colors.muted },
  label: { fontSize: 15, fontWeight: '700', color: colors.dark },
  input: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 12,
    padding: 14,
    fontSize: 17,
    color: colors.dark,
  },
  local: { fontSize: 12, color: colors.muted, lineHeight: 19, marginTop: 16 },
});
