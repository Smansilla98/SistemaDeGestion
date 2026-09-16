import { useCallback, useEffect, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { useRouter, useSegments } from 'expo-router';
import type { Href } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { api } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { fx } from '../theme';
import { AppIcon } from './icons';
import { AppText } from './primitives';

/**
 * Topbar con aire: marca + rol en bloque izquierdo,
 * acciones con padding claro entre sí (sin amontonar).
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

  return (
    <View style={[styles.wrap, { paddingTop: Math.max(insets.top, 10) }]}>
      <View style={styles.bar}>
        <View style={styles.brandBlock}>
          <AppText weight="semibold" style={styles.brand}>
            Conurbania
          </AppText>
          <View style={styles.rolePill}>
            <AppText weight="semibold" style={styles.roleText}>
              {user.role}
            </AppText>
          </View>
        </View>

        <View style={styles.actions}>
          <Pressable
            onPress={() => router.push('/admin/notifications' as Href)}
            accessibilityRole="button"
            style={styles.actionBtn}
          >
            <AppIcon bi="bell" size={20} color={fx.inkMuted} />
            {unread > 0 ? (
              <View style={styles.badge}>
                <AppText weight="bold" style={styles.badgeText}>
                  {unread > 9 ? '9+' : String(unread)}
                </AppText>
              </View>
            ) : null}
          </Pressable>

          <Pressable
            onPress={() => void logout()}
            accessibilityRole="button"
            style={styles.logoutBtn}
          >
            <AppIcon bi="box-arrow-right" size={18} color={fx.brand} />
            <AppText weight="semibold" style={styles.logoutText}>
              Salir
            </AppText>
          </Pressable>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    backgroundColor: fx.canvas,
    paddingHorizontal: fx.space.md,
    paddingBottom: fx.space.md,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: fx.hairline,
  },
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: fx.space.lg,
    minHeight: 52,
  },
  brandBlock: {
    flexShrink: 1,
    gap: 10,
    paddingRight: fx.space.sm,
  },
  brand: {
    color: fx.ink,
    fontSize: 16,
    letterSpacing: -0.2,
  },
  rolePill: {
    alignSelf: 'flex-start',
    backgroundColor: fx.brand,
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: fx.radius.pill,
  },
  roleText: {
    color: '#fff',
    fontSize: 11,
    letterSpacing: 0.3,
  },
  actions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    flexShrink: 0,
  },
  actionBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: fx.surface,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  badge: {
    position: 'absolute',
    top: 6,
    right: 6,
    minWidth: 14,
    height: 14,
    borderRadius: 7,
    backgroundColor: fx.danger,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 3,
  },
  badgeText: { color: '#fff', fontSize: 8, lineHeight: 11 },
  logoutBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 11,
    paddingHorizontal: 14,
    borderRadius: 14,
    backgroundColor: fx.brandSoft,
  },
  logoutText: { color: fx.brand, fontSize: 14 },
});
