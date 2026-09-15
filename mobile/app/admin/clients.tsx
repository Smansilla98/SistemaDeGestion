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
import type { ClientRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import {
  AppText,
  Card,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

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
      <PageHeader title="Clientes" subtitle="Agenda" icon="people" />
      <View style={{ padding: space.md }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <>
            <SectionLabel>Nuevo cliente</SectionLabel>
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
          keyExtractor={(c) => String(c.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <AppText style={{ textAlign: 'center', color: colors.gray500 }}>Sin clientes</AppText>
          }
          renderItem={({ item }) => (
            <Pressable onPress={() => (canWrite ? openEdit(item) : undefined)}>
              <Card style={{ marginBottom: 8 }}>
                <AppText weight="bold">{item.name}</AppText>
                {item.phone ? <AppText style={styles.meta}>{item.phone}</AppText> : null}
                {item.email ? <AppText style={styles.meta}>{item.email}</AppText> : null}
                {item.notes ? (
                  <AppText style={styles.meta} numberOfLines={2}>
                    {item.notes}
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
                Editar cliente
              </AppText>
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
  meta: { color: colors.gray500, marginTop: 4, fontSize: 13 },
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
