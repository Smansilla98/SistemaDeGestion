import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';
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

async function registerForPushSafe(): Promise<void> {
  try {
    const Notifications = await import('expo-notifications');
    Notifications.setNotificationHandler({
      handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
        shouldShowBanner: true,
        shouldShowList: true,
      }),
    });

    const { status: existing } = await Notifications.getPermissionsAsync();
    let finalStatus = existing;
    if (existing !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }
    if (finalStatus !== 'granted') return;

    // Sin EAS projectId real, getExpoPushTokenAsync falla en Expo Go — no bloquear login.
    const tokenData = await Notifications.getExpoPushTokenAsync();
    const platform = Platform.OS === 'ios' ? 'ios' : Platform.OS === 'android' ? 'android' : 'web';
    await api.registerDevice(tokenData.data, platform);
  } catch {
    // push opcional en dev / Expo Go
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
    let cancelled = false;
    const boot = async () => {
      try {
        await Promise.race([
          refreshMe(),
          new Promise<void>((resolve) => setTimeout(resolve, 8000)),
        ]);
      } catch {
        await clearTokens();
        if (!cancelled) setUser(null);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    void boot();
    return () => {
      cancelled = true;
    };
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
      void registerForPushSafe();
    } catch (e) {
      if (e instanceof ApiError && e.code === 'NETWORK') {
        setOfflineHint(e.message);
      }
      throw e;
    }
  }, []);

  const logout = useCallback(async () => {
    const refresh = await getRefreshToken();
    try {
      await api.logout(refresh);
    } finally {
      setUser(null);
    }
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
