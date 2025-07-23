import { DarkTheme, DefaultTheme, ThemeProvider } from '@react-navigation/native';
import { useFonts } from 'expo-font';
import { Redirect, Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import 'react-native-reanimated';


import { useColorScheme } from '@/hooks/useColorScheme';

import { useContext } from 'react';
import { AuthContext } from '@/utils/authContext';



export default function ProtectedLayout() {

  const authState = useContext(AuthContext);
  if (!authState.isReady) {
    // Optionally, you can return a loading indicator here
    return null; // or a loading spinner
  }
  if (!authState.isLoggedIn) {
    // Redirect to login screen if not logged in
    return <Redirect href="/login" />;
  }
  const colorScheme = useColorScheme();
  const [loaded] = useFonts({
    SpaceMono: require('../../assets/fonts/SpaceMono-Regular.ttf'),
  });

  if (!loaded) {
    // Async font loading only occurs in development.
    return null;
  }

  return (
    // <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
      <Stack>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="+not-found" />
      </Stack>
    /* </ThemeProvider> */
  );
}
