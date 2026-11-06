import Constants from 'expo-constants';

type Extra = {
  apiUrl?: string;
  appEnv?: string;
};

const extra = (Constants.expoConfig?.extra ?? {}) as Extra;

/** Preferir siempre EXPO_PUBLIC_* del .env (Expo Go / Metro). */
export const API_URL =
  process.env.EXPO_PUBLIC_API_URL ??
  extra.apiUrl ??
  'https://conurbaniabar.up.railway.app/api';

export const APP_ENV = process.env.APP_ENV ?? extra.appEnv ?? 'development';

if (__DEV__) {
  // eslint-disable-next-line no-console
  console.log('[Conurbania] API_URL =', API_URL);
}
