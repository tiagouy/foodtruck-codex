import { Alert, Linking } from 'react-native';
import { mediaURL } from './config';
export async function openLink(value: string): Promise<void> {
  const url = mediaURL(value);
  if (!url) {
    Alert.alert('Enlace no disponible');
    return;
  }
  try {
    await Linking.openURL(url);
  } catch {
    Alert.alert('No pudimos abrir el enlace.');
  }
}
