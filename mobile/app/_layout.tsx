import { Stack, useRouter, useSegments } from 'expo-router';
import { useEffect } from 'react';
import { ActivityIndicator, StatusBar, Text, View } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import * as SplashScreen from 'expo-splash-screen';
import { AuthProvider, useAuth } from '../src/auth/AuthContext';
import { homeHrefForRole } from '../src/auth/permissions';
import { colors, font } from '../src/theme';
import { useOutfitFonts } from '../src/ui/fonts';
import { LinearGradientFallback } from '../src/ui/gradient';

SplashScreen.preventAutoHideAsync().catch(() => {});

const stackHeader = {
  headerShown: true,
  headerStyle: { backgroundColor: colors.teal900 },
  headerTintColor: '#fff',
  headerTitleStyle: { fontFamily: font.semibold },
};

function AuthGate({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth();
  const segments = useSegments();
  const router = useRouter();
  const fontsReady = useOutfitFonts();

  useEffect(() => {
    if (loading || !fontsReady) return;
    SplashScreen.hideAsync().catch(() => {});
    const inAuth = segments[0] === 'login';
    if (!user && !inAuth) router.replace('/login');
    if (user && inAuth) router.replace(homeHrefForRole(user.role));
  }, [user, loading, fontsReady, segments, router]);

  if (loading || !fontsReady) {
    return (
      <LinearGradientFallback
        colors={colors.mosaic}
        style={{ flex: 1, justifyContent: 'center', alignItems: 'center', gap: 12 }}
      >
        <View
          style={{
            width: 48,
            height: 48,
            borderRadius: 12,
            backgroundColor: colors.teal500,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Text style={{ color: '#fff', fontSize: 20, fontWeight: '800' }}>C</Text>
        </View>
        <Text style={{ color: '#fff', fontSize: 28, fontWeight: '800', letterSpacing: -0.5 }}>
          Conurbania
        </Text>
        <ActivityIndicator color="#fff" size="large" />
        <Text style={{ color: colors.teal200, fontSize: 13 }}>Cargando…</Text>
      </LinearGradientFallback>
    );
  }

  return <>{children}</>;
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <StatusBar barStyle="light-content" backgroundColor={colors.teal900} />
        <AuthProvider>
          <AuthGate>
            <Stack
              screenOptions={{
                headerShown: false,
                headerTitleStyle: { fontFamily: font.bold },
                contentStyle: { backgroundColor: colors.gray50 },
              }}
            >
              <Stack.Screen name="login" />
              <Stack.Screen name="(tabs)" />
              <Stack.Screen name="order/[id]" options={{ ...stackHeader, title: 'Pedido' }} />
              <Stack.Screen name="table/[id]" options={{ ...stackHeader, title: 'Mesa' }} />
              <Stack.Screen name="tables/map" />
              <Stack.Screen name="cash/[id]" options={{ ...stackHeader, title: 'Sesión de caja' }} />
              <Stack.Screen name="products/index" />
              <Stack.Screen name="products/new" />
              <Stack.Screen name="products/[id]" />
              <Stack.Screen name="users/index" />
              <Stack.Screen name="users/new" />
              <Stack.Screen name="admin/index" />
              <Stack.Screen name="admin/categories" />
              <Stack.Screen name="admin/sectors" />
              <Stack.Screen name="admin/discounts" />
              <Stack.Screen name="admin/clients" />
              <Stack.Screen name="admin/reports" />
              <Stack.Screen name="admin/events" />
              <Stack.Screen name="admin/recurring" />
              <Stack.Screen name="admin/expenses" />
              <Stack.Screen name="admin/notifications" />
            </Stack>
          </AuthGate>
        </AuthProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
