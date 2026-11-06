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
 * Project ID real de @smansilla/conurbania (eas init).
 * Override con EAS_PROJECT_ID en .env / EAS secrets si hace falta.
 */
const easProjectId =
  (process.env.EAS_PROJECT_ID ?? '').trim() ||
  '8079310e-3205-4c1e-88d3-22964fb97c60';

export default ({ config }: ConfigContext): ExpoConfig => ({
  ...config,
  name: 'Conurbania',
  slug: 'conurbania',
  version: '1.0.0',
  orientation: 'portrait',
  scheme: 'conurbania',
  userInterfaceStyle: 'light',
  icon: './assets/icon.png',
  owner: 'smansilla',
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.conurbania.app',
    infoPlist: {
      UIBackgroundModes: ['remote-notification'],
      ITSAppUsesNonExemptEncryption: false,
    },
  },
  android: {
    package: 'com.conurbania.app',
    softwareKeyboardLayoutMode: 'resize',
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
