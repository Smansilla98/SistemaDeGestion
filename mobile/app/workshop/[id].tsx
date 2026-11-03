import { useCallback, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, View } from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter, type Href } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { WorkshopDetail } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { AppText } from '../../src/ui/primitives';
import { FxHeader, Surface } from '../../src/ui/fintech';

const STATUS_LABEL: Record<string, string> = { PROGRAMADA: '', CANCELADA: 'Suspendida', REPROGRAMADA: 'Reprogramada' };

function fmt(date: string): string {
  const d = new Date(`${date}T12:00:00`);
  return d.toLocaleDateString('es-AR', { weekday: 'short', day: 'numeric', month: 'short' });
}

/** Ficha del taller: horarios y clases (dos semanas atrás, cuatro adelante) para tomar asistencia. */
export default function WorkshopScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const canAttend = hasPermission(user, 'workshops.attendance');
  const [data, setData] = useState<WorkshopDetail | null>(null);
  const [error, setError] = useState<string | null>(null);

  useFocusEffect(
    useCallback(() => {
      let alive = true;
      api.workshop(Number(id))
        .then((d) => alive && setData(d))
        .catch((e) => alive && setError(e instanceof ApiError ? e.message : 'No se pudo cargar el taller'));
      return () => {
        alive = false;
      };
    }, [id]),
  );

  const today = new Date().toISOString().slice(0, 10);

  return (
    <View style={styles.root}>
      <FxHeader title={data?.name ?? 'Taller'} subtitle={data?.schedules.join(' · ')} onBack={() => router.back()} />
      {error ? <AppText weight="medium" style={styles.err}>{error}</AppText> : null}
      {!data && !error ? <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} /> : null}
      {data ? (
        <ScrollView contentContainerStyle={styles.list}>
          <Surface>
            <AppText style={styles.meta}>
              {[data.teacher && `Con ${data.teacher}`, data.room, `${data.active_count}${data.capacity ? `/${data.capacity}` : ''} inscriptos`]
                .filter(Boolean).join(' · ')}
            </AppText>
            {data.description ? <AppText style={styles.desc}>{data.description}</AppText> : null}
          </Surface>
          <AppText weight="bold" style={styles.section}>Clases</AppText>
          {data.sessions.length === 0 ? (
            <AppText style={styles.meta}>No hay clases en estas semanas.</AppText>
          ) : (
            data.sessions.map((s) => {
              const cancelled = s.status === 'CANCELADA';
              const tappable = canAttend && !cancelled && s.date <= today;
              return (
                <Surface
                  key={s.id}
                  style={styles.session}
                  onPress={tappable ? () => router.push(`/workshop/attendance?id=${data.id}&session=${s.id}` as Href) : undefined}
                >
                  <View style={styles.row}>
                    <View style={{ flex: 1 }}>
                      <AppText weight="semibold" style={[styles.title, cancelled && styles.cancelled]}>
                        {fmt(s.date)} · {s.start}
                      </AppText>
                      {STATUS_LABEL[s.status] || s.note ? (
                        <AppText style={styles.meta}>{[STATUS_LABEL[s.status], s.note].filter(Boolean).join(': ')}</AppText>
                      ) : null}
                    </View>
                    {s.attendance_taken ? <Ionicons name="checkmark-circle" size={20} color={fx.success} /> : null}
                    {tappable ? <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} /> : null}
                  </View>
                </Surface>
              );
            })
          )}
          {canAttend ? <AppText style={styles.hint}>Tocá una clase de hoy o anterior para tomar asistencia.</AppText> : null}
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 56, gap: 8 },
  err: { color: fx.danger, paddingHorizontal: fx.space.md, marginBottom: 8 },
  meta: { fontSize: 13, color: fx.inkMuted },
  desc: { fontSize: 14, color: fx.ink, marginTop: 8, lineHeight: 20 },
  section: { fontSize: 13, color: fx.inkMuted, textTransform: 'uppercase', letterSpacing: 0.6, marginTop: 14 },
  session: {},
  row: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  title: { fontSize: 15, color: fx.ink },
  cancelled: { textDecorationLine: 'line-through', color: fx.inkFaint },
  hint: { fontSize: 12, color: fx.inkFaint, marginTop: 8 },
});
