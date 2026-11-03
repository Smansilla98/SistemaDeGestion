import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { AttendanceSheet } from '../../src/api/types';
import { fx } from '../../src/theme';
import { AppText, PrimaryButton } from '../../src/ui/primitives';
import { FxHeader, Surface } from '../../src/ui/fintech';

const SHORT: Record<string, string> = { PRESENTE: 'P', TARDE: 'T', AUSENTE: 'A', JUSTIFICADA: 'J' };

/** Planilla de asistencia de una clase: un toque por alumno; «Todos presentes» para empezar rápido. */
export default function AttendanceScreen() {
  const { id, session } = useLocalSearchParams<{ id: string; session: string }>();
  const router = useRouter();
  const [sheet, setSheet] = useState<AttendanceSheet | null>(null);
  const [marks, setMarks] = useState<Record<number, string>>({});
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState<string | null>(null);

  useEffect(() => {
    api.attendance(Number(id), Number(session))
      .then((s) => {
        setSheet(s);
        const initial: Record<number, string> = {};
        s.students.forEach((st) => {
          if (st.status) initial[st.contact_id] = st.status;
        });
        setMarks(initial);
      })
      .catch((e) => setError(e instanceof ApiError ? e.message : 'No se pudo cargar la planilla'));
  }, [id, session]);

  const allPresent = () => {
    const next: Record<number, string> = {};
    sheet?.students.forEach((st) => {
      next[st.contact_id] = 'PRESENTE';
    });
    setMarks(next);
  };

  const save = async () => {
    setSaving(true);
    setError(null);
    try {
      const res = (await api.saveAttendance(Number(id), Number(session), marks)) as { alerts?: number };
      setSaved(res?.alerts ? `Guardada. ${res.alerts} alumno(s) con faltas seguidas: le avisamos a coordinación.` : 'Asistencia guardada.');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setSaving(false);
    }
  };

  const statuses = Object.keys(sheet?.statuses ?? SHORT);

  return (
    <View style={styles.root}>
      <FxHeader title="Asistencia" subtitle={sheet ? `${sheet.session.date} · ${sheet.session.start}` : undefined} onBack={() => router.back()} />
      {error ? <AppText weight="medium" style={styles.err}>{error}</AppText> : null}
      {!sheet && !error ? <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} /> : null}
      {sheet ? (
        <ScrollView contentContainerStyle={styles.list}>
          <PrimaryButton title="Todos presentes" variant="outline" onPress={allPresent} />
          {sheet.students.length === 0 ? <AppText style={styles.meta}>No hay alumnos inscriptos.</AppText> : null}
          {sheet.students.map((st) => (
            <Surface key={st.contact_id} style={styles.student}>
              <AppText weight="semibold" style={styles.name}>{st.name}</AppText>
              <View style={styles.options}>
                {statuses.map((k) => {
                  const on = marks[st.contact_id] === k;
                  return (
                    <Pressable
                      key={k}
                      onPress={() => setMarks((m) => ({ ...m, [st.contact_id]: k }))}
                      style={[styles.opt, on && styles.optOn]}
                      accessibilityRole="button"
                      accessibilityState={{ selected: on }}
                      accessibilityLabel={`${st.name}: ${sheet.statuses[k] ?? k}`}
                    >
                      <AppText weight="bold" style={[styles.optText, on && styles.optTextOn]}>{SHORT[k] ?? k}</AppText>
                    </Pressable>
                  );
                })}
              </View>
            </Surface>
          ))}
          <AppText style={styles.meta}>P presente · T tarde · A ausente · J justificada</AppText>
          {saved ? <AppText weight="medium" style={styles.ok}>{saved}</AppText> : null}
          <PrimaryButton title="Guardar asistencia" onPress={() => void save()} loading={saving} disabled={saving || Object.keys(marks).length === 0} />
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 56, gap: 10 },
  err: { color: fx.danger, paddingHorizontal: fx.space.md, marginBottom: 8 },
  meta: { fontSize: 12, color: fx.inkMuted },
  ok: { fontSize: 14, color: fx.success },
  student: { gap: 10 },
  name: { fontSize: 15, color: fx.ink },
  options: { flexDirection: 'row', gap: 8 },
  opt: { flex: 1, height: 44, borderRadius: fx.radius.sm, borderWidth: 1, borderColor: fx.hairline, alignItems: 'center', justifyContent: 'center', backgroundColor: fx.surface },
  optOn: { backgroundColor: fx.ink, borderColor: fx.ink },
  optText: { fontSize: 15, color: fx.ink },
  optTextOn: { color: fx.surface },
});
