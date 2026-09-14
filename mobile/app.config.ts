import { ExpoConfig, ConfigContext } from 'expo/config';

const APP_ENV = process.env.APP_ENV ?? 'development';

/** Producción por defecto: API Railway. Override con EXPO_PUBLIC_API_URL. */
const apiUrls: Record<string, string> = {
  development: process.env.EXPO_PUBLIC_API_URL ?? 'http://127.0.0.1:8000/api',
  staging: process.env.EXPO_PUBLIC_API_URL ?? 'https://staging.example.com/api',
  production:
    process.env.EXPO_PUBLIC_API_URL ?? 'https://conurbaniabar.up.railway.app/api',
};

/**
 * EAS_PROJECT_ID: setear con `eas init` y/o secret EAS.
 * El fallback UUID evita crashear builds locales; en producción EAS debe inyectar el real.
 * Ver docs/STORE_CHECKLIST.md.
 */
const easProjectId =
  process.env.EAS_PROJECT_ID ?? '00000000-0000-0000-0000-000000000000';

export default ({ config }: ConfigContext): ExpoConfig => ({
  ...config,
  name: 'Conurbania',
  slug: 'conurbania',
  version: '1.0.0',
  orientation: 'portrait',
  scheme: 'conurbania',
  userInterfaceStyle: 'light',
  icon: './assets/icon.png',
  // owner: 'tu-cuenta-expo', // opcional: setear si el proyecto EAS tiene owner
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.conurbania.app',
    infoPlist: {
      UIBackgroundModes: ['remote-notification'],
    },
  },
  android: {
    package: 'com.conurbania.app',
    adaptiveIcon: {
      foregroundImage: './assets/android-icon-foreground.png',
      backgroundColor: '#1d9e75',
    },
  },
  plugins: [
    'expo-router',
    'expo-secure-store',
    'expo-font',
    [
      'expo-splash-screen',
      {
        backgroundColor: '#082822',
        image: './assets/splash-icon.png',
      },
    ],
    [
      'expo-notifications',
      {
        color: '#1d9e75',
      },
    ],
  ],
  extra: {
    appEnv: APP_ENV,
    apiUrl: apiUrls[APP_ENV] ?? apiUrls.development,
    eas: {
      projectId: easProjectId,
    },
  },
  experiments: {
    typedRoutes: true,
  },
});
