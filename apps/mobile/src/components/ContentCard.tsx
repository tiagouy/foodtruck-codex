// Card/image/loading pattern adapted from BuenCafe HomeFeedItem; no legacy rating fields.
import React, { useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Content, Kind, Publication } from '../lib/api';
import { cardData, plain } from '../lib/presentation';
import { mediaURL } from '../lib/config';
import { colors } from './AppHeader';
import Avatar from './Avatar';
import PhotoMeta from './PhotoMeta';

export default function ContentCard({
  kind,
  item,
  onPress,
}: {
  kind: Kind;
  item: Content;
  onPress: () => void;
}) {
  const data = cardData(kind, item);
  const uri = mediaURL(data.image);
  const [loading, setLoading] = useState(Boolean(uri));
  const [failed, setFailed] = useState(false);
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Ver ${plain(data.title)}`}
      onPress={onPress}
      style={styles.card}
    >
      <View style={styles.imageWrap}>
        {uri && !failed ? (
          <Image
            source={{ uri }}
            style={styles.image}
            resizeMode="cover"
            onLoadEnd={() => setLoading(false)}
            onError={() => {
              setFailed(true);
              setLoading(false);
            }}
          />
        ) : (
          <Text style={styles.placeholder}>Foodtrucks UY</Text>
        )}
        {loading && (
          <ActivityIndicator
            style={StyleSheet.absoluteFill}
            color={colors.accent}
          />
        )}
      </View>
      <View style={styles.body}>
        {kind === 'publications' ? (
          <View style={styles.authorRow}>
            <Avatar url={(item as Publication).author.avatar} size={36} />
            <Text style={[styles.title, styles.authorName]}>
              {plain(data.title)}
            </Text>
          </View>
        ) : (
          <Text style={styles.title}>{plain(data.title)}</Text>
        )}
        {kind === 'publications' ? (
          <PhotoMeta photo={item as Publication} />
        ) : (
          <Text style={styles.meta}>{plain(data.subtitle)}</Text>
        )}
        {kind === 'publications' && (
          <Text numberOfLines={3} style={styles.caption}>
            {plain((item as Publication).caption)}
          </Text>
        )}
      </View>
    </Pressable>
  );
}
const styles = StyleSheet.create({
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.line,
    marginBottom: 16,
  },
  imageWrap: {
    aspectRatio: 1.35,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#EEE5DB',
  },
  image: { width: '100%', height: '100%' },
  placeholder: { fontSize: 20, color: colors.muted, fontWeight: '700' },
  body: { padding: 15 },
  title: { fontSize: 19, fontWeight: '700', color: colors.dark },
  meta: { fontSize: 13, color: colors.muted, marginTop: 6 },
  caption: { color: colors.dark, marginTop: 12, lineHeight: 22 },
  authorRow: { flexDirection: 'row', gap: 10, alignItems: 'center' },
  authorName: { flex: 1, fontSize: 17 },
});
