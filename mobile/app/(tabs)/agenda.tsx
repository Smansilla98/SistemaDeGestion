import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, SectionList, StyleSheet, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { AgendaDay, AgendaItem } from '../../src/api/types';
import { fx } from '../../src/theme';
import { AppText } from '../../src/ui/primitives';
import { FadeIn, FxHeader, Surface } from '../../src/ui/fintech';

const DAYS = 14;

function iso(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function dayTitle(date: string): string {
  const d = new Date(`${date}T12:00:00`);
  const today = iso(new Date());
  const tomorrow = iso(new Date(Date.now() + 86400000));
  const label = d.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'short' });
  if (date === today) return `Hoy · ${label}`;
  if (date === tomorrow) return `Mañana · ${label}`;
  return label.charAt(0).toUpperCase() + label.slice(1);
}

/** Agenda del espacio: clases, eventos, entregas y avisos. Alumnos y profes ven lo suyo. */
export default function AgendaScreen() {
  const [from, setFrom] = useState(() => new Date());
  const [days, setDays] = useState<AgendaDay[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async (start: Date) => {
    setError(null);
    try {
      const data = await api.agenda(iso(start), DAYS);
      setDays(data.days ?? []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo cargar la agenda');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load(from);
    }, [load, from]),
  );

  const move = (delta: number) => setFrom((d) => new Date(d.getTime() + delta * DAYS * 86400000));

  return (
    <View style={styles.root}>
      <FxHeader
        title="Agenda"
        subtitle={`${dayTitle(iso(from)).replace(/^Hoy · /, '')} · ${DAYS} días`}
        right={
          <View style={styles.nav}>
            <Pressable onPress={() => move(-1)} hitSlop={10} accessibilityLabel="Anterior">
              <Ionicons name="chevron-back" size={22} color={fx.ink} />
            </Pressable>
            <Pressable onPress={() => setFrom(new Date())} hitSlop={10} accessibilityLabel="Hoy">
              <AppText weight="medium" style={styles.today}>Hoy</AppText>
            </Pressable>
            <Pressable onPress={() => move(1)} hitSlop={10} accessibilityLabel="Siguiente">
              <Ionicons name="chevron-forward" size={22} color={fx.ink} />
            </Pressable>
          </View>
        }
      />
      {error ? <AppText weight="medium" style={styles.err}>{error}</AppText> : null}
      {loading && days.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <SectionList
          sections={days.map((d) => ({ title: d.date, data: d.items }))}
          keyExtractor={(item, i) => `${item.type}-${item.title}-${i}`}
          contentContainerStyle={styles.list}
          stickySectionHeadersEnabled={false}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load(from)} />}
          renderSectionHeader={({ section }) => (
            <AppText weight="bold" style={styles.day}>{dayTitle(section.title)}</AppText>
          )}
          renderItem={({ item }) => <AgendaRow item={item} />}
          ListEmptyComponent={
            <FadeIn>
              <Surface style={styles.empty}>
                <Ionicons name="calendar-outline" size={28} color={fx.brand} />
                <AppText weight="semibold" style={styles.emptyTitle}>Nada agendado</AppText>
                <AppText style={styles.emptyMeta}>No hay clases, eventos ni avisos en estos días.</AppText>
              </Surface>
            </FadeIn>
          }
        />
      )}
    </View>
  );
}

function AgendaRow({ item }: { item: AgendaItem }) {
  return (
    <Surface padded={false} style={styles.card}>
      <View style={[styles.bar, { backgroundColor: item.color || fx.ink }]} />
      <View style={styles.time}>
        <AppText weight="bold" style={styles.timeText}>{item.time ?? '—'}</AppText>
      </View>
      <View style={styles.body}>
        <AppText weight="semibold" style={[styles.title, item.cancelled && styles.cancelled]} numberOfLines={2}>
          {item.title}
        </AppText>
        <AppText style={styles.meta} numberOfLines={2}>
          {[item.kind, item.category, item.detail].filter(Boolean).join(' · ')}
        </AppText>
      </View>
    </Surface>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 56, flexGrow: 1 },
  nav: { flexDirection: 'row', alignItems: 'center', gap: 14 },
  today: { color: fx.brand, fontSize: 14 },
  err: { color: fx.danger, paddingHorizontal: fx.space.md, marginBottom: 8 },
  day: { fontSize: 13, color: fx.inkMuted, textTransform: 'uppercase', letterSpacing: 0.6, marginTop: 18, marginBottom: 8 },
  card: { flexDirection: 'row', alignItems: 'stretch', marginBottom: 8, overflow: 'hidden' },
  bar: { width: 4 },
  time: { width: 64, paddingVertical: 14, alignItems: 'center' },
  timeText: { fontSize: 14, color: fx.ink },
  body: { flex: 1, paddingVertical: 12, paddingRight: fx.space.md, gap: 2 },
  title: { fontSize: 15, color: fx.ink },
  cancelled: { textDecorationLine: 'line-through', color: fx.inkFaint },
  meta: { fontSize: 13, color: fx.inkMuted },
  empty: { alignItems: 'center', paddingVertical: 36, gap: 8, marginTop: 12 },
  emptyTitle: { fontSize: 16, color: fx.ink },
  emptyMeta: { color: fx.inkMuted, fontSize: 13, textAlign: 'center' },
});
