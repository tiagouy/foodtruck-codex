// Adapted from BuenCafe AppHeader: same shared-header structure, Foodtrucks identity.
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';

export const colors = {
  accent: '#C34416',
  dark: '#28231F',
  background: '#FAF7F2',
  muted: '#675E56',
  line: '#E9DFD3',
};
export default function AppHeader({ title }: { title: string }) {
  return (
    <View style={styles.header}>
      <Text style={styles.brand}>FOODTRUCKS UY</Text>
      <Text accessibilityRole="header" style={styles.title}>
        {title}
      </Text>
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
    color: '#F9B17B',
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 2,
  },
  title: { color: '#FFFFFF', fontSize: 25, fontWeight: '700', marginTop: 5 },
});
