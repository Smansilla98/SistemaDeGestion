import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { EventRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import {
  AppText,
  Badge,
  Card,
  Chip,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

const STATUSES = ['PROGRAMADO', 'EN_CURSO', 'FINALIZADO', 'CANCELADO'] as const;

const emptyForm = () => ({
  name: '',
  date: new Date().toISOString().slice(0, 10),
  time: '20:00',
  status: 'PROGRAMADO' as string,
  description: '',
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
      <PageHeader title="Eventos" subtitle="Agenda" icon="calendar" />
      <View style={{ padding: space.md }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <>
            <SectionLabel>Nuevo evento</SectionLabel>
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
            <PrimaryButton title="Crear" loading={busy} onPress={() => void create()} />
          </>
        ) : null}
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}
      </View>
      {loading ? (
        <ActivityIndicator color={colors.teal500} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(e) => String(e.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <AppText style={{ textAlign: 'center', color: colors.gray500 }}>Sin eventos</AppText>
          }
          renderItem={({ item }) => (
            <Pressable onPress={() => (canWrite ? openEdit(item) : undefined)}>
              <Card style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <AppText weight="bold" style={{ flex: 1 }}>
                    {item.name}
                  </AppText>
                  {item.status ? <Badge label={item.status} /> : null}
                </View>
                <AppText style={styles.meta}>
                  {item.date}
                  {item.time ? ` · ${item.time}` : ''}
                </AppText>
                {item.description ? (
                  <AppText style={styles.meta} numberOfLines={2}>
                    {item.description}
                  </AppText>
                ) : null}
              </Card>
            </Pressable>
          )}
        />
      )}

      <Modal visible={!!editing} animationType="slide" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <ScrollView keyboardShouldPersistTaps="handled">
              <AppText weight="bold" style={{ fontSize: 18, marginBottom: 8 }}>
                Editar evento
              </AppText>
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
              <PrimaryButton title="Guardar" loading={busy} onPress={() => void saveEdit()} />
              {editing ? (
                <PrimaryButton
                  title="Eliminar"
                  variant="danger"
                  onPress={() => remove(editing)}
                />
              ) : null}
              <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setEditing(null)} />
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  err: { color: colors.danger, marginTop: 8 },
  meta: { color: colors.gray500, marginTop: 4 },
  row: { flexDirection: 'row', alignItems: 'center' },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginVertical: 6 },
  modalBg: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    backgroundColor: colors.white,
    borderTopLeftRadius: radius.xl,
    borderTopRightRadius: radius.xl,
    padding: space.lg,
    maxHeight: '90%',
    gap: 8,
  },
});
