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

/**
 * Variante de marca. APP_VARIANT=patuka arma la app de Patuka Espacio Cultural: otro nombre,
 * identificadores de tienda, servidor, ícono y colores (blanco y negro). Necesita su propio
 * proyecto EAS: EAS_PROJECT_ID_PATUKA (ver docs/PATUKA.md → App).
 */
const VARIANT = (process.env.APP_VARIANT ?? '').trim();

type Brand = {
  name: string; slug: string; scheme: string; bundle: string; projectId: string; apiUrl: string;
  icon: string; adaptive: string; adaptiveBg: string; splash: string; splashBg: string; color: string; brandColor?: string;
};

const brands: Record<string, Brand> = {
  patuka: {
    name: 'Patuka',
    slug: 'patuka',
    scheme: 'patuka',
    bundle: 'com.patuka.app',
    projectId: (process.env.EAS_PROJECT_ID_PATUKA ?? '').trim(),
    apiUrl: process.env.EXPO_PUBLIC_API_URL ?? 'https://patuka.up.railway.app/api',
    icon: './assets/patuka/icon.png',
    adaptive: './assets/patuka/android-icon-foreground.png',
    adaptiveBg: '#000000',
    splash: './assets/patuka/splash-icon.png',
    splashBg: '#000000',
    color: '#111111',
    brandColor: '#111111',
  },
};

const brand: Brand = brands[VARIANT] ?? {
  name: 'Al Toque',
  slug: 'conurbania',
  scheme: 'conurbania',
  bundle: 'com.conurbania.app',
  projectId: easProjectId,
  apiUrl: apiUrls[APP_ENV] ?? apiUrls.development,
  icon: './assets/icon.png',
  adaptive: './assets/android-icon-foreground.png',
  adaptiveBg: '#4e8d99',
  splash: './assets/splash-icon.png',
  splashBg: '#16343b',
  color: '#4e8d99',
};

export default ({ config }: ConfigContext): ExpoConfig => ({
  ...config,
  name: brand.name,
  slug: brand.slug,
  version: '1.0.0',
  orientation: 'portrait',
  scheme: brand.scheme,
  userInterfaceStyle: 'light',
  icon: brand.icon,
  owner: 'smansilla',
  ios: {
    supportsTablet: true,
    bundleIdentifier: brand.bundle,
    infoPlist: {
      UIBackgroundModes: ['remote-notification'],
      ITSAppUsesNonExemptEncryption: false,
    },
  },
  android: {
    package: brand.bundle,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: brand.adaptive,
      backgroundColor: brand.adaptiveBg,
    },
  },
  plugins: [
    'expo-router',
    'expo-secure-store',
    'expo-font',
    [
      'expo-splash-screen',
      {
        backgroundColor: brand.splashBg,
        image: brand.splash,
      },
    ],
    [
      'expo-notifications',
      {
        color: brand.color,
      },
    ],
  ],
  extra: {
    appEnv: APP_ENV,
    apiUrl: brand.apiUrl,
    brandColor: brand.brandColor,
    variant: VARIANT || 'altoque',
    ...(brand.projectId ? { eas: { projectId: brand.projectId } } : {}),
  },
  experiments: {
    typedRoutes: true,
  },
});
