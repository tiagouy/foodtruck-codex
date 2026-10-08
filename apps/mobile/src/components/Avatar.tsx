import React, { useState } from 'react';
import { Image, StyleSheet, View } from 'react-native';
import { UserRound } from 'lucide-react-native';
import { mediaURL } from '../lib/config';
import { colors } from './AppHeader';

export default function Avatar({
  url,
  size = 40,
}: {
  url?: string | null;
  size?: number;
}) {
  const uri = mediaURL(url);
  const [failedURI, setFailedURI] = useState<string>();
  return (
    <View
      style={[
        styles.circle,
        { width: size, height: size, borderRadius: size / 2 },
      ]}
    >
      {uri && uri !== failedURI ? (
        <Image
          source={{ uri }}
          style={StyleSheet.absoluteFill}
          resizeMode="cover"
          onError={() => setFailedURI(uri)}
        />
      ) : (
        <UserRound
          testID="avatar-placeholder"
          color={colors.muted}
          size={size * 0.5}
        />
      )}
    </View>
  );
}
const styles = StyleSheet.create({
  circle: {
    backgroundColor: colors.line,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
});
