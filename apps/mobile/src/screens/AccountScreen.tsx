import React from 'react';
import { ScrollView, StyleSheet, Text } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AppHeader, { colors } from '../components/AppHeader';
import { Button } from '../components/State';
import { siteURL } from '../lib/config';
import { openLink } from '../lib/links';

export default function AccountScreen() {
  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.screen}>
      <AppHeader title="Mi cuenta" />
      <ScrollView style={styles.body} contentContainerStyle={styles.content}>
        <Text style={styles.title}>¿Ya usabas la app anterior?</Text>
        <Text style={styles.text}>
          Tu cuenta y tus fotos se conservan. Reactivá tu cuenta por email y
          elegí una contraseña nueva.
        </Text>
        <Button
          label="Reactivar cuenta en el sitio"
          onPress={() => openLink(`${siteURL()}/reactivar-cuenta/`)}
        />
        <Text style={styles.text}>
          En esta primera versión podés explorar sin ingresar. El ingreso y la
          publicación de fotos dentro de la app están en preparación. Estos
          botones abren la web; no inician una sesión en la app.
        </Text>
        <Button
          label="Ingresar en el sitio"
          onPress={() => openLink(`${siteURL()}/ingresar/`)}
        />
        <Button
          label="Registrarme en el sitio"
          onPress={() => openLink(`${siteURL()}/registro/`)}
        />
        <Button
          label="Recordar contraseña en el sitio"
          onPress={() => openLink(`${siteURL()}/recordar-contrasena/`)}
        />
      </ScrollView>
    </SafeAreaView>
  );
}
const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.dark },
  body: { backgroundColor: colors.background },
  content: { padding: 20, gap: 18 },
  title: { fontSize: 24, color: colors.dark, fontWeight: '700' },
  text: { fontSize: 16, lineHeight: 25, color: colors.muted },
});
