import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  useWindowDimensions,
} from 'react-native';
import { MapPin } from 'lucide-react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useFocusEffect } from '@react-navigation/native';
import { RootStack } from '../navigation';
import { AppSession, ProfilePhoto, restoreSession } from '../lib/session';
import { APIError } from '../lib/api';
import {
  choosePublicationPhoto,
  PhotoLocation,
  PlaceSuggestion,
  publishPhoto,
  selectPlace,
  suggestions,
  uploadID,
} from '../lib/publication-upload';
import State, { Button } from '../components/State';
import { colors } from '../components/AppHeader';

export default function PublicationUploadScreen({
  navigation,
}: NativeStackScreenProps<RootStack, 'SubirFoto'>) {
  const previewEdge = Math.min(
    420,
    Math.max(1, useWindowDimensions().width - 40),
  );
  const [session, setSession] = useState<AppSession | null>(null);
  const [checking, setChecking] = useState(true);
  const [busy, setBusy] = useState(false);
  const [attempted, setAttempted] = useState(false);
  const [photo, setPhoto] = useState<ProfilePhoto | null>(null);
  const [caption, setCaption] = useState('');
  const [address, setAddress] = useState('');
  const [location, setLocation] = useState<PhotoLocation | null>(null);
  const [places, setPlaces] = useState<PlaceSuggestion[]>([]);
  const [placeError, setPlaceError] = useState('');
  const [searching, setSearching] = useState(false);
  const [error, setError] = useState('');
  const [saved, setSaved] = useState<number | null>(null);
  const [requestId] = useState(uploadID);
  const searchId = useRef(uploadID());
  const live = useRef(true);
  const working = useRef(false);
  const search = useRef<AbortController | null>(null);
  const revision = useRef(0);
  useEffect(() => {
    live.current = true;
    return () => {
      live.current = false;
      search.current?.abort();
    };
  }, []);
  useFocusEffect(
    useCallback(() => {
      let active = true;
      setChecking(true);
      restoreSession()
        .then(value => {
          if (active) {
            setSession(value);
            setError('');
          }
        })
        .catch(failure => {
          if (active) {
            setError(failure.message);
          }
        })
        .finally(() => {
          if (active) {
            setChecking(false);
          }
        });
      return () => {
        active = false;
      };
    }, []),
  );
  useEffect(() => {
    search.current?.abort();
    if (
      !session ||
      address.trim().length < 3 ||
      location ||
      busy ||
      attempted ||
      saved
    ) {
      setPlaces([]);
      setSearching(false);
      return;
    }
    const controller = new AbortController();
    search.current = controller;
    const timer = setTimeout(() => {
      setSearching(true);
      suggestions(session.token, searchId.current, address, controller.signal)
        .then(rows => {
          if (!controller.signal.aborted && live.current) {
            setPlaces(rows);
            setPlaceError(
              rows.length
                ? ''
                : 'No encontramos ese lugar. Podés completar la dirección manualmente.',
            );
          }
        })
        .catch(failure => {
          if (!controller.signal.aborted && live.current) {
            setPlaces([]);
            setPlaceError(failure.message);
          }
        })
        .finally(() => {
          if (!controller.signal.aborted && live.current) {
            setSearching(false);
          }
        });
    }, 450);
    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [address, session, location, busy, attempted, saved]);
  const choose = async () => {
    if (working.current || attempted) {
      return;
    }
    working.current = true;
    setBusy(true);
    setError('');
    try {
      const selected = await choosePublicationPhoto();
      if (selected && live.current) {
        setPhoto(selected);
      }
    } catch (failure) {
      if (live.current) {
        setError((failure as Error).message);
      }
    } finally {
      working.current = false;
      if (live.current) {
        setBusy(false);
      }
    }
  };
  const choosePlace = async (p: PlaceSuggestion) => {
    if (!session || working.current) {
      return;
    }
    working.current = true;
    setBusy(true);
    setPlaceError('');
    setPlaces([]);
    const current = revision.current;
    try {
      const value = await selectPlace(session.token, searchId.current, p.id);
      if (live.current && current === revision.current) {
        const name = p.label.split(',')[0].trim();
        setAddress(name);
        setLocation({ ...value, name });
        searchId.current = uploadID();
      }
    } catch (failure) {
      if (live.current) {
        setPlaceError((failure as Error).message);
      }
    } finally {
      working.current = false;
      if (live.current) {
        setBusy(false);
      }
    }
  };
  const publish = async () => {
    if (!session || working.current || saved) {
      return;
    }
    if (!photo || !caption.trim() || !address.trim()) {
      setError('Elegí una foto y completá el texto y la dirección.');
      return;
    }
    working.current = true;
    setBusy(true);
    setAttempted(true);
    setError('');
    try {
      const id = await publishPhoto(
        session.token,
        requestId,
        photo,
        caption,
        address,
        location,
      );
      if (live.current) {
        setSaved(id);
      }
    } catch (failure) {
      if (live.current) {
        const definite =
          failure instanceof APIError &&
          [400, 401, 413].includes(failure.status);
        setAttempted(!definite);
        setError(
          `${(failure as Error).message}${
            definite
              ? ''
              : ' Reintentá sin cambiar los datos: comprobaremos la publicación sin duplicarla.'
          }`,
        );
        if (failure instanceof APIError && failure.status === 401) {
          setSession(null);
        }
      }
    } finally {
      working.current = false;
      if (live.current) {
        setBusy(false);
      }
    }
  };
  if (checking) {
    return <State loading />;
  }
  if (saved) {
    return (
      <View style={styles.success}>
        <Text style={styles.title}>¡Foto publicada!</Text>
        <Text style={styles.text}>
          Ya forma parte de las fotos de la comunidad y de Mis fotos.
        </Text>
        <Button
          label="Ver mi foto"
          onPress={() =>
            navigation.replace('Detalle', {
              kind: 'publications',
              contentKey: String(saved),
            })
          }
        />
        <Button
          label="Ir a Mis fotos"
          onPress={() => navigation.navigate('Principal', { screen: 'Cuenta' })}
        />
      </View>
    );
  }
  if (!session) {
    return (
      <View style={styles.success}>
        <Text style={styles.title}>Compartí tu foto</Text>
        <Text style={styles.text}>
          Para publicar una foto necesitás ingresar o registrarte.
        </Text>
        {error ? <Text style={styles.error}>{error}</Text> : null}
        <Button
          label="Ingresar o registrarme"
          onPress={() => navigation.navigate('Principal', { screen: 'Cuenta' })}
        />
      </View>
    );
  }
  const editable = !busy && !attempted;
  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      keyboardVerticalOffset={90}
    >
      <ScrollView
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
      >
        {photo ? (
          <Image
            source={{ uri: photo.uri }}
            style={[styles.photo, { width: previewEdge, height: previewEdge }]}
            resizeMode="contain"
            accessibilityLabel="Vista previa de tu foto"
          />
        ) : null}
        <Button
          label={photo ? 'Cambiar foto' : 'Elegir foto'}
          disabled={!editable}
          onPress={choose}
        />
        <Text style={styles.label}>Texto</Text>
        <TextInput
          accessibilityLabel="Texto de la foto"
          value={caption}
          onChangeText={setCaption}
          multiline
          maxLength={10000}
          editable={editable}
          placeholder="Contanos algo sobre tu foto"
          placeholderTextColor={colors.muted}
          style={[styles.input, styles.caption]}
        />
        <Text style={styles.label}>¿Dónde fue?</Text>
        <TextInput
          accessibilityLabel="Dirección de la foto"
          value={address}
          onChangeText={value => {
            revision.current++;
            search.current?.abort();
            setAddress(value);
            setLocation(null);
            setPlaces([]);
            setPlaceError('');
          }}
          maxLength={255}
          editable={editable}
          placeholder="Buscá un lugar o escribí la dirección"
          placeholderTextColor={colors.muted}
          style={styles.input}
        />
        {searching ? (
          <Text style={styles.text}>Buscando dirección…</Text>
        ) : null}
        {places.length ? (
          <View style={styles.suggestions}>
            {places.map((p, index) => (
              <Pressable
                key={p.id}
                accessibilityRole="button"
                accessibilityLabel={p.label}
                disabled={!editable}
                onPress={() => choosePlace(p)}
                style={({ pressed }) => [
                  styles.suggestionRow,
                  index > 0 && styles.suggestionDivider,
                  pressed && styles.suggestionPressed,
                ]}
              >
                <MapPin size={19} color={colors.muted} />
                <View style={styles.suggestionText}>
                  <Text style={styles.suggestionName}>
                    {p.label.split(',')[0].trim()}
                  </Text>
                  {p.label.includes(',') ? (
                    <Text style={styles.suggestionAddress}>
                      {p.label.slice(p.label.indexOf(',') + 1).trim()}
                    </Text>
                  ) : null}
                </View>
              </Pressable>
            ))}
            <View style={styles.suggestionFooter}>
              <Text style={styles.google}>Google Maps</Text>
            </View>
          </View>
        ) : null}
        {location ? (
          <Text style={styles.google}>
            Ubicación seleccionada · Google Maps
          </Text>
        ) : null}
        {placeError ? <Text style={styles.text}>{placeError}</Text> : null}
        <Text style={styles.text}>
          Tu foto, texto y dirección serán públicos. No compartas direcciones
          privadas ni datos personales.
        </Text>
        {attempted && !busy ? (
          <Text style={styles.text}>
            Conservamos los datos para reintentar sin duplicar la foto.
          </Text>
        ) : null}
        {error ? (
          <Text accessibilityLiveRegion="polite" style={styles.error}>
            {error}
          </Text>
        ) : null}
        <Button
          label={
            busy
              ? 'Procesando…'
              : attempted
              ? 'Reintentar publicación'
              : 'Publicar foto'
          }
          disabled={busy}
          onPress={publish}
        />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
const styles = StyleSheet.create({
  flex: { flex: 1 },
  content: { padding: 20, gap: 14, paddingBottom: 44 },
  success: { padding: 24, gap: 18 },
  title: { fontSize: 23, fontWeight: '700', color: colors.dark },
  label: { fontSize: 16, fontWeight: '600', color: colors.dark },
  text: { fontSize: 15, lineHeight: 22, color: colors.muted },
  error: { fontSize: 16, color: '#A12323' },
  input: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 12,
    padding: 14,
    fontSize: 16,
    color: colors.dark,
  },
  caption: { minHeight: 100, textAlignVertical: 'top' },
  photo: {
    alignSelf: 'center',
    borderRadius: 16,
    backgroundColor: colors.line,
  },
  suggestions: {
    marginTop: -12,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#DADCE0',
    borderRadius: 8,
    overflow: 'hidden',
  },
  suggestionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    minHeight: 60,
    paddingHorizontal: 14,
    paddingVertical: 11,
  },
  suggestionDivider: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: '#E8EAED',
  },
  suggestionPressed: { backgroundColor: '#F1F3F4' },
  suggestionText: { flex: 1, gap: 3 },
  suggestionName: { fontSize: 16, color: '#202124' },
  suggestionAddress: { fontSize: 13, lineHeight: 18, color: '#5F6368' },
  suggestionFooter: {
    alignItems: 'flex-end',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: '#E8EAED',
  },
  google: { fontSize: 12, fontWeight: '400', color: '#5E5E5E' },
});
