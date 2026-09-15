import { useCallback, useEffect, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { useRouter, useSegments } from 'expo-router';
import type { Href } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { api } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { colors, font, space } from '../theme';
import { AppText } from './primitives';

/** Topbar sticky (paridad web: título + rol + usuario + logout). */
export function AppTopbar() {
  const { user, logout } = useAuth();
  const insets = useSafeAreaInsets();
  const router = useRouter();
  const segments = useSegments();
  const [unread, setUnread] = useState(0);

  const refreshUnread = useCallback(async () => {
    if (!user) {
      setUnread(0);
      return;
    }
    try {
      const rows = await api.notifications();
      setUnread(rows.filter((n) => !n.read_at).length);
    } catch {
      setUnread(0);
    }
  }, [user]);

  useEffect(() => {
    void refreshUnread();
  }, [refreshUnread, segments]);

  if (!user) return null;

  return (
    <View style={[styles.bar, { paddingTop: Math.max(insets.top, 6) }]}>
      <View style={styles.left}>
        <AppText weight="bold" style={styles.brand}>
          Conurbania
        </AppText>
        <View style={styles.rolePill}>
          <AppText weight="semibold" style={styles.roleText}>
            {user.role}
          </AppText>
        </View>
      </View>
      <View style={styles.right}>
        <Pressable
          onPress={() => router.push('/admin/notifications' as Href)}
          hitSlop={8}
          accessibilityRole="button"
          style={styles.bellWrap}
        >
          <AppText weight="bold" style={styles.bell}>
            ✉
          </AppText>
          {unread > 0 ? (
            <View style={styles.badge}>
              <AppText weight="bold" style={styles.badgeText}>
                {unread > 99 ? '99+' : String(unread)}
              </AppText>
            </View>
          ) : null}
        </Pressable>
        <AppText weight="medium" style={styles.user} numberOfLines={1}>
          {user.name || user.username}
        </AppText>
        <Pressable onPress={() => void logout()} hitSlop={8} accessibilityRole="button">
          <AppText weight="bold" style={styles.logout}>
            Salir
          </AppText>
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: space.md,
    paddingBottom: 8,
    backgroundColor: colors.teal900,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.08)',
  },
  left: { flexDirection: 'row', alignItems: 'center', gap: 8, flexShrink: 1 },
  brand: { color: colors.white, fontSize: 15, letterSpacing: -0.2, fontFamily: font.bold },
  rolePill: {
    backgroundColor: 'rgba(29,158,117,0.35)',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  roleText: { color: colors.teal200, fontSize: 10, letterSpacing: 0.4 },
  right: { flexDirection: 'row', alignItems: 'center', gap: 12, flexShrink: 1 },
  bellWrap: { position: 'relative', paddingRight: 4 },
  bell: { color: colors.teal200, fontSize: 16 },
  badge: {
    position: 'absolute',
    top: -6,
    right: -6,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: colors.danger,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 3,
  },
  badgeText: { color: '#fff', fontSize: 9, lineHeight: 12 },
  user: { color: 'rgba(255,255,255,0.7)', fontSize: 13, maxWidth: 100 },
  logout: { color: colors.teal200, fontSize: 13 },
});
