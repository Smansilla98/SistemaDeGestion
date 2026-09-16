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
import type { ClientRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { AppText, Field, PrimaryButton } from '../../src/ui/primitives';
import { FxHeader, ModalSheet, Surface } from '../../src/ui/fintech';

const emptyForm = () => ({
  name: '',
  phone: '',
  email: '',
  notes: '',
});

export default function AdminClientsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'clients.write');
  const [rows, setRows] = useState<ClientRow[]>([]);
  const [form, setForm] = useState(emptyForm());
  const [editing, setEditing] = useState<ClientRow | null>(null);
  const [editForm, setEditForm] = useState(emptyForm());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows(await api.clients());
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

  const openEdit = (item: ClientRow) => {
    setEditing(item);
    setEditForm({
      name: item.name ?? '',
      phone: item.phone ?? '',
      email: item.email ?? '',
      notes: item.notes ?? '',
    });
  };

  const create = async () => {
    if (!form.name.trim()) {
      setError('Nombre requerido');
      return;
    }
    setBusy(true);
    try {
      await api.createClient({
        name: form.name.trim(),
        phone: form.phone.trim() || undefined,
        email: form.email.trim() || undefined,
        notes: form.notes.trim() || undefined,
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
    if (!editForm.name.trim()) {
      Alert.alert('Validación', 'Nombre requerido');
      return;
    }
    setBusy(true);
    try {
      await api.updateClient(editing.id, {
        name: editForm.name.trim(),
        phone: editForm.phone.trim() || null,
        email: editForm.email.trim() || null,
        notes: editForm.notes.trim() || null,
      });
      setEditing(null);
      await load();
    } catch (e) {
      Alert.alert('Error', e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setBusy(false);
    }
  };

  const remove = (item: ClientRow) => {
    Alert.alert('Eliminar cliente', `¿Eliminar a ${item.name}?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            setBusy(true);
            try {
              await api.deleteClient(item.id);
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
      <FxHeader title="Clientes" subtitle="Agenda" onBack={() => router.back()} />
      {loading && rows.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(c) => String(c.id)}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <AppText style={styles.empty}>Sin clientes</AppText>
          }
          ListHeaderComponent={
            <View style={styles.headerBlock}>
              {canWrite ? (
                <Surface style={styles.formCard}>
                  <AppText weight="semibold" style={styles.section}>
                    Nuevo cliente
                  </AppText>
                  <Field
                    label="Nombre"
                    value={form.name}
                    onChangeText={(v) => setForm((f) => ({ ...f, name: v }))}
                  />
                  <Field
                    label="Teléfono"
                    value={form.phone}
                    onChangeText={(v) => setForm((f) => ({ ...f, phone: v }))}
                    keyboardType="phone-pad"
                  />
                  <Field
                    label="Email"
                    value={form.email}
                    onChangeText={(v) => setForm((f) => ({ ...f, email: v }))}
                    keyboardType="email-address"
                    autoCapitalize="none"
                  />
                  <Field
                    label="Notas"
                    value={form.notes}
                    onChangeText={(v) => setForm((f) => ({ ...f, notes: v }))}
                    multiline
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
                  {item.phone ? <AppText style={styles.meta}>{item.phone}</AppText> : null}
                  {item.email ? <AppText style={styles.meta}>{item.email}</AppText> : null}
                  {item.notes ? (
                    <AppText style={styles.meta} numberOfLines={2}>
                      {item.notes}
                    </AppText>
                  ) : null}
                </View>
                {canWrite ? <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} /> : null}
              </View>
            </Surface>
          )}
        />
      )}

      <ModalSheet
        visible={!!editing}
        title="Editar cliente"
        onClose={() => setEditing(null)}
        maxHeight="90%"
      >
        <Field
          label="Nombre"
          value={editForm.name}
          onChangeText={(v) => setEditForm((f) => ({ ...f, name: v }))}
        />
        <Field
          label="Teléfono"
          value={editForm.phone}
          onChangeText={(v) => setEditForm((f) => ({ ...f, phone: v }))}
          keyboardType="phone-pad"
        />
        <Field
          label="Email"
          value={editForm.email}
          onChangeText={(v) => setEditForm((f) => ({ ...f, email: v }))}
          keyboardType="email-address"
          autoCapitalize="none"
        />
        <Field
          label="Notas"
          value={editForm.notes}
          onChangeText={(v) => setEditForm((f) => ({ ...f, notes: v }))}
          multiline
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
});
