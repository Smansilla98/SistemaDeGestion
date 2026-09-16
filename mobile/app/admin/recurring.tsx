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
import type { RecurringRow } from '../../src/api/types';
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

const DAYS = [
  { key: 'MONDAY', label: 'Lun' },
  { key: 'TUESDAY', label: 'Mar' },
  { key: 'WEDNESDAY', label: 'Mié' },
  { key: 'THURSDAY', label: 'Jue' },
  { key: 'FRIDAY', label: 'Vie' },
  { key: 'SATURDAY', label: 'Sáb' },
  { key: 'SUNDAY', label: 'Dom' },
] as const;

const dayLabel = (key: string) => DAYS.find((d) => d.key === key)?.label ?? key;

const emptyForm = () => ({
  name: '',
  day_of_week: 'FRIDAY',
  start_time: '21:00',
  end_time: '23:00',
  description: '',
  expected_attendance: '',
  start_date: '',
  end_date: '',
  is_active: true,
});

export default function AdminRecurringScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'events.write');
  const [rows, setRows] = useState<RecurringRow[]>([]);
  const [form, setForm] = useState(emptyForm());
  const [editing, setEditing] = useState<RecurringRow | null>(null);
  const [editForm, setEditForm] = useState(emptyForm());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows((await api.recurring()) as RecurringRow[]);
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

  const openEdit = (item: RecurringRow) => {
    setEditing(item);
    setEditForm({
      name: item.name ?? '',
      day_of_week: item.day_of_week ?? 'FRIDAY',
      start_time: item.start_time ?? '',
      end_time: item.end_time ?? '',
      description: item.description ?? '',
      expected_attendance:
        item.expected_attendance != null ? String(item.expected_attendance) : '',
      start_date: item.start_date ? String(item.start_date).slice(0, 10) : '',
      end_date: item.end_date ? String(item.end_date).slice(0, 10) : '',
      is_active: item.is_active !== false,
    });
  };

  const create = async () => {
    if (!form.name.trim() || !form.start_time) {
      setError('Nombre y horario de inicio requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.storeRecurring({
        name: form.name.trim(),
        day_of_week: form.day_of_week,
        start_time: form.start_time,
        end_time: form.end_time || null,
        description: form.description.trim() || null,
        expected_attendance: form.expected_attendance
          ? Number(form.expected_attendance)
          : null,
        start_date: form.start_date || null,
        end_date: form.end_date || null,
        is_active: true,
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
    if (!editForm.name.trim() || !editForm.start_time) {
      Alert.alert('Validación', 'Nombre y horario de inicio requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.updateRecurring(editing.id, {
        name: editForm.name.trim(),
        day_of_week: editForm.day_of_week,
        start_time: editForm.start_time,
        end_time: editForm.end_time || null,
        description: editForm.description.trim() || null,
        expected_attendance: editForm.expected_attendance
          ? Number(editForm.expected_attendance)
          : null,
        start_date: editForm.start_date || null,
        end_date: editForm.end_date || null,
        is_active: editForm.is_active,
      });
      setEditing(null);
      await load();
    } catch (e) {
      Alert.alert('Error', e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setBusy(false);
    }
  };

  const remove = (item: RecurringRow) => {
    Alert.alert('Eliminar', `¿Eliminar “${item.name}”?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            setBusy(true);
            try {
              await api.deleteRecurring(item.id);
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
      <FxHeader title="Recurrentes" subtitle="Actividades semanales" onBack={() => router.back()} />
      {loading && rows.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(e) => String(e.id)}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={<AppText style={styles.empty}>Sin actividades</AppText>}
          ListHeaderComponent={
            <View style={styles.headerBlock}>
              {canWrite ? (
                <Surface style={styles.formCard}>
                  <AppText weight="semibold" style={styles.section}>
                    Nueva actividad
                  </AppText>
                  <Field
                    label="Nombre"
                    value={form.name}
                    onChangeText={(v) => setForm((f) => ({ ...f, name: v }))}
                  />
                  <AppText weight="semibold" style={styles.fieldHint}>
                    Día de la semana
                  </AppText>
                  <View style={styles.chips}>
                    {DAYS.map((d) => (
                      <Chip
                        key={d.key}
                        label={d.label}
                        selected={form.day_of_week === d.key}
                        onPress={() => setForm((f) => ({ ...f, day_of_week: d.key }))}
                      />
                    ))}
                  </View>
                  <Field
                    label="Inicio (HH:MM)"
                    value={form.start_time}
                    onChangeText={(v) => setForm((f) => ({ ...f, start_time: v }))}
                  />
                  <Field
                    label="Fin (HH:MM)"
                    value={form.end_time}
                    onChangeText={(v) => setForm((f) => ({ ...f, end_time: v }))}
                  />
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
                  />
                  <Field
                    label="Inicio vigencia (YYYY-MM-DD)"
                    value={form.start_date}
                    onChangeText={(v) => setForm((f) => ({ ...f, start_date: v }))}
                    placeholder="Opcional"
                  />
                  <Field
                    label="Fin vigencia (YYYY-MM-DD)"
                    value={form.end_date}
                    onChangeText={(v) => setForm((f) => ({ ...f, end_date: v }))}
                    placeholder="Opcional"
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
                    {item.start_time}
                    {item.end_time ? ` – ${item.end_time}` : ''}
                  </AppText>
                </View>
                <Badge label={item.is_active === false ? 'INACTIVO' : dayLabel(item.day_of_week)} />
                {canWrite ? <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} /> : null}
              </View>
            </Surface>
          )}
        />
      )}

      <ModalSheet
        visible={!!editing}
        title="Editar actividad"
        onClose={() => setEditing(null)}
        maxHeight="90%"
      >
        <Field
          label="Nombre"
          value={editForm.name}
          onChangeText={(v) => setEditForm((f) => ({ ...f, name: v }))}
        />
        <AppText weight="semibold" style={styles.fieldHint}>
          Día de la semana
        </AppText>
        <View style={styles.chips}>
          {DAYS.map((d) => (
            <Chip
              key={d.key}
              label={d.label}
              selected={editForm.day_of_week === d.key}
              onPress={() => setEditForm((f) => ({ ...f, day_of_week: d.key }))}
            />
          ))}
        </View>
        <Field
          label="Inicio (HH:MM)"
          value={editForm.start_time}
          onChangeText={(v) => setEditForm((f) => ({ ...f, start_time: v }))}
        />
        <Field
          label="Fin (HH:MM)"
          value={editForm.end_time}
          onChangeText={(v) => setEditForm((f) => ({ ...f, end_time: v }))}
        />
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
        <Field
          label="Inicio vigencia (YYYY-MM-DD)"
          value={editForm.start_date}
          onChangeText={(v) => setEditForm((f) => ({ ...f, start_date: v }))}
        />
        <Field
          label="Fin vigencia (YYYY-MM-DD)"
          value={editForm.end_date}
          onChangeText={(v) => setEditForm((f) => ({ ...f, end_date: v }))}
        />
        <View style={styles.chips}>
          <Chip
            label="Activo"
            selected={editForm.is_active}
            onPress={() => setEditForm((f) => ({ ...f, is_active: true }))}
          />
          <Chip
            label="Inactivo"
            selected={!editForm.is_active}
            onPress={() => setEditForm((f) => ({ ...f, is_active: false }))}
          />
        </View>
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
  fieldHint: { color: fx.inkMuted, marginBottom: 6, fontSize: 13 },
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
