import Constants from 'expo-constants';

type Extra = {
  apiUrl?: string;
  appEnv?: string;
};

const extra = (Constants.expoConfig?.extra ?? {}) as Extra;

export const API_URL = extra.apiUrl ?? process.env.EXPO_PUBLIC_API_URL ?? 'http://127.0.0.1:8000/api';
export const APP_ENV = extra.appEnv ?? 'development';
