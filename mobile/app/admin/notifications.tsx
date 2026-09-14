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
import { api, ApiError } from '../../src/api/client';
import type { NotificationRow } from '../../src/api/types';
import { colors, space } from '../../src/theme';
import { AppText, Badge, Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

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

  return (
    <View style={styles.root}>
      <PageHeader title="Notificaciones" subtitle="Alertas" icon="notifications" />
      <View style={styles.bar}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        <PrimaryButton title="Marcar todas" variant="amber" onPress={() => void markAll()} />
      </View>
      {error ? (
        <AppText weight="medium" style={styles.err}>
          {error}
        </AppText>
      ) : null}
      {loading ? (
        <ActivityIndicator color={colors.teal500} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(n) => n.id}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <AppText style={{ textAlign: 'center', color: colors.gray500 }}>
              Sin notificaciones
            </AppText>
          }
          renderItem={({ item }) => (
            <Pressable onPress={() => !item.read_at && void markOne(item.id)}>
              <Card style={{ marginBottom: 8, opacity: item.read_at ? 0.65 : 1 }}>
                <View style={styles.row}>
                  <AppText weight="bold" style={{ flex: 1 }}>
                    {item.type}
                  </AppText>
                  <Badge label={item.read_at ? 'LEÍDA' : 'NUEVA'} />
                </View>
                <AppText style={styles.meta}>
                  {typeof item.data?.message === 'string'
                    ? item.data.message
                    : JSON.stringify(item.data ?? {})}
                </AppText>
                <AppText style={styles.meta}>{item.created_at?.slice(0, 16)}</AppText>
              </Card>
            </Pressable>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  bar: { flexDirection: 'row', gap: 8, padding: space.md },
  err: { color: colors.danger, paddingHorizontal: space.md },
  meta: { color: colors.gray500, marginTop: 4, fontSize: 13 },
  row: { flexDirection: 'row', alignItems: 'center' },
});
