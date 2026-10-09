/**
 * @format
 */

import { AppRegistry } from 'react-native';
import messaging from '@react-native-firebase/messaging';
import App from './App';
import { name as appName } from './app.json';

AppRegistry.registerComponent(appName, () => App);
// OS displays notification payloads in background. Never log payloads/tokens.
messaging().setBackgroundMessageHandler(async () => {});
