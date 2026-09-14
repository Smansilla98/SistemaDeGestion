import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';

const ACCESS = 'cx_access_token';
const REFRESH = 'cx_refresh_token';

async function getItem(key: string): Promise<string | null> {
  try {
    return await SecureStore.getItemAsync(key);
  } catch {
    return AsyncStorage.getItem(key);
  }
}

async function setItem(key: string, value: string): Promise<void> {
  try {
    await SecureStore.setItemAsync(key, value);
  } catch {
    await AsyncStorage.setItem(key, value);
  }
}

async function deleteItem(key: string): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(key);
  } catch {
    await AsyncStorage.removeItem(key);
  }
}

export async function getAccessToken(): Promise<string | null> {
  return getItem(ACCESS);
}

export async function getRefreshToken(): Promise<string | null> {
  return getItem(REFRESH);
}

export async function setTokens(access: string, refresh: string): Promise<void> {
  await setItem(ACCESS, access);
  await setItem(REFRESH, refresh);
}

export async function clearTokens(): Promise<void> {
  await deleteItem(ACCESS);
  await deleteItem(REFRESH);
}
