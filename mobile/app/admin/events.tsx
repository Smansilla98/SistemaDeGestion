import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { EventRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import {
  AppText,
  Badge,
  Chip,
  Field,
  PrimaryButton,
} from '../../src/ui/primitives';
import { FxHeader, ModalSheet, Surface } from '../../src/ui/fintech';

const STATUSES = ['PROGRAMADO', 'EN_CURSO', 'FINALIZADO', 'CANCELADO'] as const;

const emptyForm = () => ({
  name: '',
  date: new Date().toISOString().slice(0, 10),
  time: '20:00',
  status: 'PROGRAMADO' as string,
  description: '',
  expected_attendance: '',
});

export default function AdminEventsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'events.write');
  const [rows, setRows] = useState<EventRow[]>([]);
  const [form, setForm] = useState(emptyForm());
  const [editing, setEditing] = useState<EventRow | null>(null);
  const [editForm, setEditForm] = useState(emptyForm());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows((await api.events()) as EventRow[]);
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

  const openEdit = (item: EventRow) => {
    setEditing(item);
    setEditForm({
      name: item.name ?? '',
      date: (item.date ?? '').slice(0, 10),
      time: item.time ?? '',
      status: item.status ?? 'PROGRAMADO',
      description: item.description ?? '',
      expected_attendance:
        item.expected_attendance != null ? String(item.expected_attendance) : '',
    });
  };

  const create = async () => {
    if (!form.name.trim() || !form.date) {
      setError('Nombre y fecha requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.storeEvent({
        name: form.name.trim(),
        date: form.date,
        time: form.time || undefined,
        status: form.status,
        description: form.description.trim() || undefined,
        expected_attendance: form.expected_attendance
          ? Number(form.expected_attendance)
          : undefined,
      });
      setForm(emptyForm());
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo crear');
    } finally {
      setBusy(false);
    }
  };

  const saveEdit = async () => {
    if (!editing) return;
    if (!editForm.name.trim() || !editForm.date) {
      Alert.alert('Validación', 'Nombre y fecha requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.updateEvent(editing.id, {
        name: editForm.name.trim(),
        date: editForm.date,
        time: editForm.time || null,
        status: editForm.status,
        description: editForm.description.trim() || null,
        expected_attendance: editForm.expected_attendance
          ? Number(editForm.expected_attendance)
          : null,
      });
      setEditing(null);
      await load();
    } catch (e) {
      Alert.alert('Error', e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setBusy(false);
    }
  };

  const remove = (item: EventRow) => {
    Alert.alert('Eliminar evento', `¿Eliminar “${item.name}”?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            setBusy(true);
            try {
              await api.deleteEvent(item.id);
              setEditing(null);
              await load();
            } catch (e) {
              Alert.alert('Error', e instanceof ApiError ? e.message : 'No se pudo eliminar');
            } finally {
              setBusy(false);
            }
          })();
        },
      },
    ]);
  };

  return (
    <View style={styles.root}>
      <FxHeader title="Eventos" subtitle="Agenda" onBack={() => router.back()} />
      {loading && rows.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(e) => String(e.id)}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={<AppText style={styles.empty}>Sin eventos</AppText>}
          ListHeaderComponent={
            <View style={styles.headerBlock}>
              {canWrite ? (
                <Surface style={styles.formCard}>
                  <AppText weight="semibold" style={styles.section}>
                    Nuevo evento
                  </AppText>
                  <Field
                    label="Nombre"
                    value={form.name}
                    onChangeText={(v) => setForm((f) => ({ ...f, name: v }))}
                  />
                  <Field
                    label="Fecha (YYYY-MM-DD)"
                    value={form.date}
                    onChangeText={(v) => setForm((f) => ({ ...f, date: v }))}
                  />
                  <Field
                    label="Hora"
                    value={form.time}
                    onChangeText={(v) => setForm((f) => ({ ...f, time: v }))}
                    placeholder="20:00"
                  />
                  <View style={styles.chips}>
                    {STATUSES.map((s) => (
                      <Chip
                        key={s}
                        label={s.replace(/_/g, ' ')}
                        selected={form.status === s}
                        onPress={() => setForm((f) => ({ ...f, status: s }))}
                      />
                    ))}
                  </View>
                  <Field
                    label="Descripción"
                    value={form.description}
                    onChangeText={(v) => setForm((f) => ({ ...f, description: v }))}
                    multiline
                  />
                  <Field
                    label="Asistencia esperada"
                    value={form.expected_attendance}
                    onChangeText={(v) => setForm((f) => ({ ...f, expected_attendance: v }))}
                    keyboardType="number-pad"
                    placeholder="0"
                  />
                  <PrimaryButton title="Crear" loading={busy} onPress={() => void create()} />
                </Surface>
              ) : null}
              {error ? (
                <AppText weight="medium" style={styles.err}>
                  {error}
                </AppText>
              ) : null}
              <AppText weight="semibold" style={styles.section}>
                Listado
              </AppText>
            </View>
          }
          renderItem={({ item, index }) => (
            <Surface
              padded={false}
              style={[styles.itemCard, index > 0 && styles.itemGap]}
              onPress={canWrite ? () => openEdit(item) : undefined}
            >
              <View style={styles.row}>
                <View style={{ flex: 1, gap: 4 }}>
                  <AppText weight="semibold" style={styles.itemTitle}>
                    {item.name}
                  </AppText>
                  <AppText style={styles.meta}>
                    {item.date}
                    {item.time ? ` · ${item.time}` : ''}
                    {item.expected_attendance != null
                      ? ` · ${item.expected_attendance} pers.`
                      : ''}
                  </AppText>
                  {item.description ? (
                    <AppText style={styles.meta} numberOfLines={2}>
                      {item.description}
                    </AppText>
                  ) : null}
                </View>
                {item.status ? <Badge label={item.status} /> : null}
                {canWrite ? <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} /> : null}
              </View>
            </Surface>
          )}
        />
      )}

      <ModalSheet
        visible={!!editing}
        title="Editar evento"
        onClose={() => setEditing(null)}
        maxHeight="90%"
      >
        <Field
          label="Nombre"
          value={editForm.name}
          onChangeText={(v) => setEditForm((f) => ({ ...f, name: v }))}
        />
        <Field
          label="Fecha (YYYY-MM-DD)"
          value={editForm.date}
          onChangeText={(v) => setEditForm((f) => ({ ...f, date: v }))}
        />
        <Field
          label="Hora"
          value={editForm.time}
          onChangeText={(v) => setEditForm((f) => ({ ...f, time: v }))}
        />
        <View style={styles.chips}>
          {STATUSES.map((s) => (
            <Chip
              key={s}
              label={s.replace(/_/g, ' ')}
              selected={editForm.status === s}
              onPress={() => setEditForm((f) => ({ ...f, status: s }))}
            />
          ))}
        </View>
        <Field
          label="Descripción"
          value={editForm.description}
          onChangeText={(v) => setEditForm((f) => ({ ...f, description: v }))}
          multiline
        />
        <Field
          label="Asistencia esperada"
          value={editForm.expected_attendance}
          onChangeText={(v) => setEditForm((f) => ({ ...f, expected_attendance: v }))}
          keyboardType="number-pad"
        />
        <PrimaryButton title="Guardar" loading={busy} onPress={() => void saveEdit()} />
        {editing ? (
          <PrimaryButton title="Eliminar" variant="danger" onPress={() => remove(editing)} />
        ) : null}
      </ModalSheet>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 56, flexGrow: 1 },
  headerBlock: { gap: 12, marginBottom: 8 },
  formCard: { gap: 4 },
  section: { fontSize: 13, color: fx.inkMuted, marginBottom: 4 },
  err: { color: fx.danger },
  empty: { textAlign: 'center', color: fx.inkMuted, marginTop: 24 },
  itemCard: { overflow: 'hidden' },
  itemGap: { marginTop: 12 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: fx.space.md,
    paddingVertical: 16,
  },
  itemTitle: { fontSize: 16, color: fx.ink },
  meta: { color: fx.inkMuted, fontSize: 13 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginVertical: 4 },
});
