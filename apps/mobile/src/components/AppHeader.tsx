// Adapted from BuenCafe AppHeader: same shared-header structure, Foodtrucks identity.
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { colors } from '../theme';
export { colors } from '../theme';
export default function AppHeader({
  title,
  action,
}: {
  title: string;
  action?: React.ReactNode;
}) {
  return (
    <View style={styles.header}>
      <Text style={styles.brand}>FOODTRUCKS UY</Text>
      <Text accessibilityRole="header" style={styles.title}>
        {title}
      </Text>
      {action ? <View style={styles.action}>{action}</View> : null}
    </View>
  );
}
const styles = StyleSheet.create({
  header: {
    backgroundColor: colors.dark,
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  brand: {
    color: colors.accent,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 2,
  },
  title: { color: '#FFFFFF', fontSize: 25, fontWeight: '700', marginTop: 5 },
  action: { position: 'absolute', right: 16, top: 20 },
});
