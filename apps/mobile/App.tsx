import React from 'react';
import { StatusBar } from 'react-native';
import { DefaultTheme, NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { CalendarDays, Camera, House, Truck, User } from 'lucide-react-native';
import HomeScreen from './src/screens/HomeScreen';
import CatalogScreen from './src/screens/CatalogScreen';
import DetailScreen from './src/screens/DetailScreen';
import AccountScreen from './src/screens/AccountScreen';
import AccountRequestScreen from './src/screens/AccountRequestScreen';
import AccountSettingsScreen from './src/screens/AccountSettingsScreen';
import { colors } from './src/components/AppHeader';
import { RootStack, Tabs } from './src/navigation';

const Tab = createBottomTabNavigator<Tabs>();
const Stack = createNativeStackNavigator<RootStack>();
type IconProps = { color: string; size: number };
const tabIcons = {
  Inicio: ({ color, size }: IconProps) => <House color={color} size={size} />,
  Eventos: ({ color, size }: IconProps) => (
    <CalendarDays color={color} size={size} />
  ),
  Foodtrucks: ({ color, size }: IconProps) => (
    <Truck color={color} size={size} />
  ),
  Fotos: ({ color, size }: IconProps) => <Camera color={color} size={size} />,
  Cuenta: ({ color, size }: IconProps) => <User color={color} size={size} />,
};
function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor: colors.accent,
        tabBarInactiveTintColor: colors.muted,
        tabBarIcon: tabIcons[route.name],
        tabBarStyle: { backgroundColor: '#FFFFFF' },
      })}
    >
      <Tab.Screen name="Inicio" component={HomeScreen} />
      <Tab.Screen name="Eventos">
        {() => <CatalogScreen kind="events" />}
      </Tab.Screen>
      <Tab.Screen name="Foodtrucks">
        {() => <CatalogScreen kind="foodtrucks" />}
      </Tab.Screen>
      <Tab.Screen name="Fotos">
        {() => <CatalogScreen kind="publications" />}
      </Tab.Screen>
      <Tab.Screen
        name="Cuenta"
        component={AccountScreen}
        options={{ title: 'Mi cuenta' }}
      />
    </Tab.Navigator>
  );
}
export default function App() {
  return (
    <SafeAreaProvider>
      <StatusBar barStyle="light-content" backgroundColor={colors.dark} />
      <NavigationContainer
        theme={{
          ...DefaultTheme,
          colors: {
            ...DefaultTheme.colors,
            background: colors.background,
            primary: colors.accent,
          },
        }}
      >
        <Stack.Navigator
          initialRouteName="Principal"
          screenOptions={{
            headerStyle: { backgroundColor: colors.dark },
            headerTintColor: '#FFFFFF',
          }}
        >
          <Stack.Screen
            name="ConfiguracionCuenta"
            component={AccountSettingsScreen}
            options={{ title: 'Configuración', headerBackTitle: 'Volver' }}
          />
          <Stack.Screen
            name="CuentaSolicitud"
            component={AccountRequestScreen}
            options={{ title: 'Mi cuenta', headerBackTitle: 'Volver' }}
          />
          <Stack.Screen
            name="Principal"
            component={MainTabs}
            options={{ headerShown: false }}
          />
          <Stack.Screen
            name="Detalle"
            component={DetailScreen}
            options={{ title: 'Detalle', headerBackTitle: 'Volver' }}
          />
        </Stack.Navigator>
      </NavigationContainer>
    </SafeAreaProvider>
  );
}
