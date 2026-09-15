import { useCallback, useEffect, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { useRouter, useSegments } from 'expo-router';
import type { Href } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { api } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { colors, font, radius, space } from '../theme';
import { AppIcon } from './icons';
import { AppText } from './primitives';

/**
 * Topbar sticky — paridad web (.topbar): fondo blanco, badge rol teal, avatar + logout.
 */
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

  const initial = (user.name || user.username || '?').trim().charAt(0).toUpperCase();

  return (
    <View style={[styles.bar, { paddingTop: Math.max(insets.top, 8) }]}>
      <View style={styles.left}>
        <AppText weight="medium" style={styles.title}>
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
          <AppIcon bi="bell" size={18} color={colors.gray600} />
          {unread > 0 ? (
            <View style={styles.badge}>
              <AppText weight="bold" style={styles.badgeText}>
                {unread > 99 ? '99+' : String(unread)}
              </AppText>
            </View>
          ) : null}
        </Pressable>
        <View style={styles.userChip}>
          <View style={styles.avatar}>
            <AppText weight="semibold" style={styles.avatarText}>
              {initial}
            </AppText>
          </View>
          <AppText weight="medium" style={styles.user} numberOfLines={1}>
            {user.name || user.username}
          </AppText>
        </View>
        <Pressable onPress={() => void logout()} hitSlop={8} accessibilityRole="button" style={styles.logoutBtn}>
          <AppIcon bi="box-arrow-right" size={16} color={colors.teal600} />
          <AppText weight="semibold" style={styles.logout}>
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
    paddingHorizontal: space.lg,
    paddingBottom: 10,
    minHeight: 52,
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
  left: { flexDirection: 'row', alignItems: 'center', gap: 10, flexShrink: 1 },
  title: { color: colors.gray600, fontSize: 13, fontFamily: font.medium },
  rolePill: {
    backgroundColor: colors.teal500,
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 20,
  },
  roleText: { color: colors.white, fontSize: 11, letterSpacing: 0.2 },
  right: { flexDirection: 'row', alignItems: 'center', gap: 10, flexShrink: 1 },
  bellWrap: { position: 'relative', paddingRight: 4 },
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
  userChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    paddingVertical: 5,
    paddingHorizontal: 10,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray100,
  },
  avatar: {
    width: 24,
    height: 24,
    borderRadius: 6,
    backgroundColor: colors.teal500,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: colors.white, fontSize: 10 },
  user: { color: colors.gray700, fontSize: 13, maxWidth: 90 },
  logoutBtn: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  logout: { color: colors.teal600, fontSize: 13 },
});
