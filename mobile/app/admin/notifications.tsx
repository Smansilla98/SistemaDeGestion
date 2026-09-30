import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { NotificationRow } from '../../src/api/types';
import { fx } from '../../src/theme';
import { formatDateDMY } from '../../src/ui/formatDate';
import { AppText, PrimaryButton } from '../../src/ui/primitives';
import { FadeIn, FxHeader, StatusDot, Surface } from '../../src/ui/fintech';

function notificationMessage(item: NotificationRow): string {
  const data = item.data ?? {};
  if (typeof data.message === 'string' && data.message.trim()) return data.message;
  if (typeof data.title === 'string' && data.title.trim()) return data.title;
  const entries = Object.entries(data).filter(
    ([, v]) => typeof v === 'string' || typeof v === 'number',
  );
  if (entries.length) return entries.map(([, v]) => String(v)).join(' · ');
  return 'Sin detalle';
}

function notificationTitle(item: NotificationRow): string {
  const raw = item.type?.split('\\').pop() ?? item.type ?? 'Alerta';
  return raw.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/_/g, ' ');
}

function formatWhen(value?: string | null): string {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return formatDateDMY(value);
  const hh = String(d.getHours()).padStart(2, '0');
  const mm = String(d.getMinutes()).padStart(2, '0');
  return `${formatDateDMY(value)} · ${hh}:${mm}`;
}

export default function AdminNotificationsScreen() {
  const router = useRouter();
  const [rows, setRows] = useState<NotificationRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows(await api.notifications());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

  const markOne = async (id: string) => {
    try {
      await api.markNotificationRead(id);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error');
    }
  };

  const markAll = async () => {
    try {
      await api.markAllNotificationsRead();
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error');
    }
  };

  const unread = rows.filter((n) => !n.read_at).length;

  return (
    <View style={styles.root}>
      <FxHeader
        title="Notificaciones"
        subtitle={unread > 0 ? `${unread} sin leer` : 'Alertas del sistema'}
        onBack={() => router.back()}
        right={
          unread > 0 ? (
            <Pressable onPress={() => void markAll()} hitSlop={10} style={styles.markAll}>
              <AppText weight="medium" style={styles.markAllText}>
                Marcar todas
              </AppText>
            </Pressable>
          ) : null
        }
      />

      {error ? (
        <AppText weight="medium" style={styles.err}>
          {error}
        </AppText>
      ) : null}

      {loading && rows.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(n) => n.id}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <FadeIn>
              <Surface style={styles.emptyCard}>
                <View style={styles.emptyIcon}>
                  <Ionicons name="notifications-outline" size={28} color={fx.brand} />
                </View>
                <AppText weight="semibold" style={styles.emptyTitle}>
                  Sin notificaciones
                </AppText>
                <AppText style={styles.emptyMeta}>Cuando haya alertas, aparecerán aquí.</AppText>
              </Surface>
            </FadeIn>
          }
          ListHeaderComponent={
            unread > 0 ? (
              <View style={styles.toolbar}>
                <PrimaryButton title="Marcar todas como leídas" variant="outline" onPress={() => void markAll()} />
              </View>
            ) : null
          }
          renderItem={({ item, index }) => {
            const unreadItem = !item.read_at;
            return (
              <FadeIn>
                <Surface
                  padded={false}
                  style={[styles.card, index > 0 && styles.cardGap]}
                  onPress={unreadItem ? () => void markOne(item.id) : undefined}
                >
                  <View style={styles.row}>
                    <View style={[styles.dotWrap, unreadItem && styles.dotWrapLive]}>
                      <StatusDot status={unreadItem ? 'ABIERTO' : 'CERRADO'} size="sm" />
                    </View>
                    <View style={styles.body}>
                      <View style={styles.titleRow}>
                        <AppText weight="semibold" style={styles.title} numberOfLines={1}>
                          {notificationTitle(item)}
                        </AppText>
                        <View style={[styles.badge, unreadItem ? styles.badgeNew : styles.badgeRead]}>
                          <AppText weight="medium" style={unreadItem ? styles.badgeNewText : styles.badgeReadText}>
                            {unreadItem ? 'Nueva' : 'Leída'}
                          </AppText>
                        </View>
                      </View>
                      <AppText style={styles.message} numberOfLines={3}>
                        {notificationMessage(item)}
                      </AppText>
                      <AppText style={styles.meta}>{formatWhen(item.created_at)}</AppText>
                    </View>
                    {unreadItem ? (
                      <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} />
                    ) : null}
                  </View>
                </Surface>
              </FadeIn>
            );
          }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  list: {
    paddingHorizontal: fx.space.md,
    paddingBottom: 56,
    flexGrow: 1,
  },
  toolbar: { marginBottom: 16 },
  err: { color: fx.danger, paddingHorizontal: fx.space.md, marginBottom: 8 },
  markAll: { paddingVertical: 8, paddingHorizontal: 4 },
  markAllText: { color: fx.brand, fontSize: 13 },
  card: { overflow: 'hidden' },
  cardGap: { marginTop: 12 },
  row: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    paddingHorizontal: fx.space.md,
    paddingVertical: 18,
  },
  dotWrap: {
    paddingTop: 4,
  },
  dotWrapLive: {},
  body: { flex: 1, gap: 4 },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  title: { flex: 1, fontSize: 16, color: fx.ink },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  badgeNew: { backgroundColor: fx.brandSoft },
  badgeRead: { backgroundColor: fx.canvas },
  badgeNewText: { fontSize: 11, color: fx.brandInk },
  badgeReadText: { fontSize: 11, color: fx.inkMuted },
  message: { color: fx.inkMuted, fontSize: 14, lineHeight: 20 },
  meta: { color: fx.inkFaint, fontSize: 12, marginTop: 4 },
  emptyCard: {
    alignItems: 'center',
    paddingVertical: 36,
    gap: 8,
    marginTop: 12,
  },
  emptyIcon: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: fx.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  emptyTitle: { fontSize: 16, color: fx.ink },
  emptyMeta: { color: fx.inkMuted, fontSize: 13, textAlign: 'center' },
});
