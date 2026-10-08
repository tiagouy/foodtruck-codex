import React from 'react';
import { StyleSheet, Text } from 'react-native';
import { Publication } from '../lib/api';
import { dateLabel } from '../lib/presentation';
import { photoMapURL } from '../lib/photo-map';
import { openLink } from '../lib/links';
import { colors } from './AppHeader';
export default function PhotoMeta({ photo }: { photo: Publication }) {
  const url = photoMapURL(photo);
  return (
    <Text style={styles.meta}>
      {dateLabel(photo.created_at)}
      {photo.address ? ' · ' : ''}
      {photo.address ? (
        <Text
          accessibilityRole="link"
          accessibilityLabel={`Abrir ${photo.address} en Google Maps`}
          style={styles.place}
          onPress={event => {
            event.stopPropagation();
            if (url) {
              openLink(url);
            }
          }}
        >
          {photo.address}
        </Text>
      ) : null}
    </Text>
  );
}
const styles = StyleSheet.create({
  meta: { fontSize: 13, lineHeight: 19, color: colors.muted, marginTop: 6 },
  place: { color: colors.muted, textDecorationLine: 'underline' },
});
