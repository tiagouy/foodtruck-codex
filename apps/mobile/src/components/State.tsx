import React from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { colors } from './AppHeader';
export function Button({
  label,
  onPress,
  disabled = false,
}: {
  label: string;
  onPress: () => void;
  disabled?: boolean;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled}
      onPress={onPress}
      style={[styles.button, disabled && styles.disabled]}
    >
      <Text style={styles.buttonText}>{label}</Text>
    </Pressable>
  );
}
export default function State({
  loading,
  message,
  retry,
}: {
  loading?: boolean;
  message?: string;
  retry?: () => void;
}) {
  return (
    <View style={styles.state}>
      {loading ? (
        <ActivityIndicator color={colors.accent} />
      ) : (
        <Text accessibilityLiveRegion="polite" style={styles.message}>
          {message}
        </Text>
      )}
      {!loading && retry && <Button label="Reintentar" onPress={retry} />}
    </View>
  );
}
export const styles = StyleSheet.create({
  state: { paddingVertical: 24, gap: 16 },
  message: { color: colors.muted, lineHeight: 23, fontSize: 16 },
  button: {
    padding: 14,
    borderRadius: 12,
    backgroundColor: colors.accent,
    alignItems: 'center',
  },
  buttonText: { color: '#FFFFFF', fontWeight: '700', fontSize: 15 },
  disabled: { opacity: 0.5 },
});
