import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import { api, ApiError } from '../api/client';
import type { ApiUser } from '../api/types';
import { clearTokens, getAccessToken, getRefreshToken, setTokens } from './storage';

type AuthState = {
  user: ApiUser | null;
  loading: boolean;
  offlineHint: string | null;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshMe: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

async function registerForPush(): Promise<void> {
  try {
    const { status: existing } = await Notifications.getPermissionsAsync();
    let finalStatus = existing;
    if (existing !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }
    if (finalStatus !== 'granted') return;

    const tokenData = await Notifications.getExpoPushTokenAsync();
    const platform = Platform.OS === 'ios' ? 'ios' : Platform.OS === 'android' ? 'android' : 'web';
    await api.registerDevice(tokenData.data, platform);
  } catch {
    // push opcional en dev / simulador
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<ApiUser | null>(null);
  const [loading, setLoading] = useState(true);
  const [offlineHint, setOfflineHint] = useState<string | null>(null);

  const refreshMe = useCallback(async () => {
    const token = await getAccessToken();
    if (!token) {
      setUser(null);
      return;
    }
    const me = await api.me();
    setUser(me);
  }, []);

  useEffect(() => {
    (async () => {
      try {
        await refreshMe();
      } catch {
        await clearTokens();
        setUser(null);
      } finally {
        setLoading(false);
      }
    })();
  }, [refreshMe]);

  const login = useCallback(async (username: string, password: string) => {
    setOfflineHint(null);
    try {
      const payload = await api.login(username.trim(), password);
      if (!payload.refresh_token) {
        throw new ApiError('El servidor no devolvió refresh_token', 500);
      }
      await setTokens(payload.access_token, payload.refresh_token);
      setUser(payload.user);
      void registerForPush();
    } catch (e) {
      if (e instanceof ApiError && e.code === 'NETWORK') {
        setOfflineHint(e.message);
      }
      throw e;
    }
  }, []);

  const logout = useCallback(async () => {
    const refresh = await getRefreshToken();
    await api.logout(refresh);
    setUser(null);
  }, []);

  const value = useMemo(
    () => ({ user, loading, offlineHint, login, logout, refreshMe }),
    [user, loading, offlineHint, login, logout, refreshMe],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth fuera de AuthProvider');
  return ctx;
}
