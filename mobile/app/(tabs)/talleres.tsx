import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, View } from 'react-native';
import { useFocusEffect, useRouter, type Href } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { WorkshopRow } from '../../src/api/types';
import { fx } from '../../src/theme';
import { AppText } from '../../src/ui/primitives';
import { FadeIn, FxHeader, Surface } from '../../src/ui/fintech';

/** Talleres abiertos o en curso. El profe ve sólo los que dicta. */
export default function TalleresScreen() {
  const router = useRouter();
  const [rows, setRows] = useState<WorkshopRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows(await api.workshops());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudieron cargar los talleres');
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

  return (
    <View style={styles.root}>
      <FxHeader title="Talleres" subtitle="Clases y asistencia" />
      {error ? <AppText weight="medium" style={styles.err}>{error}</AppText> : null}
      {loading && rows.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(w) => String(w.id)}
          contentContainerStyle={styles.list}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <FadeIn>
              <Surface style={styles.empty}>
                <Ionicons name="easel-outline" size={28} color={fx.brand} />
                <AppText weight="semibold" style={styles.emptyTitle}>Sin talleres activos</AppText>
              </Surface>
            </FadeIn>
          }
          renderItem={({ item }) => (
            <Surface style={styles.card} onPress={() => router.push(`/workshop/${item.id}` as Href)}>
              <View style={styles.row}>
                <View style={{ flex: 1, gap: 2 }}>
                  <AppText weight="semibold" style={styles.title}>{item.name}</AppText>
                  <AppText style={styles.meta} numberOfLines={2}>
                    {[item.schedules.join(' · '), item.teacher, item.room].filter(Boolean).join(' · ')}
                  </AppText>
                </View>
                <View style={styles.count}>
                  <AppText weight="bold" style={styles.countN}>
                    {item.active_count}{item.capacity ? `/${item.capacity}` : ''}
                  </AppText>
                  <AppText style={styles.countL}>inscriptos</AppText>
                </View>
                <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} />
              </View>
            </Surface>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 56, gap: 10, flexGrow: 1 },
  err: { color: fx.danger, paddingHorizontal: fx.space.md, marginBottom: 8 },
  card: {},
  row: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  title: { fontSize: 16, color: fx.ink },
  meta: { fontSize: 13, color: fx.inkMuted },
  count: { alignItems: 'flex-end' },
  countN: { fontSize: 16, color: fx.ink },
  countL: { fontSize: 11, color: fx.inkFaint },
  empty: { alignItems: 'center', paddingVertical: 36, gap: 8, marginTop: 12 },
  emptyTitle: { fontSize: 16, color: fx.ink },
});
