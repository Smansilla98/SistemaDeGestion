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
import type { ExpenseRow } from '../../src/api/types';
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

const FREQUENCIES = ['MENSUAL', 'QUINCENAL', 'SEMANAL', 'DIARIO', 'ANUAL'] as const;

const emptyForm = () => ({
  name: '',
  amount: '',
  category: 'GENERAL',
  type: 'GASTO' as 'GASTO' | 'INGRESO',
  frequency: 'MENSUAL',
  is_active: true,
});

export default function AdminExpensesScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'expenses.write');
  const [rows, setRows] = useState<ExpenseRow[]>([]);
  const [form, setForm] = useState(emptyForm());
  const [editing, setEditing] = useState<ExpenseRow | null>(null);
  const [editForm, setEditForm] = useState(emptyForm());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows((await api.fixedExpenses()) as ExpenseRow[]);
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

  const openEdit = (item: ExpenseRow) => {
    setEditing(item);
    setEditForm({
      name: item.name ?? '',
      amount: String(item.amount ?? ''),
      category: item.category ?? 'GENERAL',
      type: (item.type === 'INGRESO' ? 'INGRESO' : 'GASTO') as 'GASTO' | 'INGRESO',
      frequency: item.frequency ?? 'MENSUAL',
      is_active: item.is_active !== false,
    });
  };

  const create = async () => {
    const value = Number(form.amount);
    if (!form.name.trim() || Number.isNaN(value)) {
      setError('Nombre y monto requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.storeFixedExpense({
        name: form.name.trim(),
        type: form.type,
        category: form.category,
        amount: value,
        frequency: form.frequency,
        start_date: new Date().toISOString().slice(0, 10),
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
    const value = Number(editForm.amount);
    if (!editForm.name.trim() || Number.isNaN(value)) {
      Alert.alert('Validación', 'Nombre y monto requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.updateFixedExpense(editing.id, {
        name: editForm.name.trim(),
        amount: value,
        category: editForm.category,
        type: editForm.type,
        frequency: editForm.frequency,
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

  const remove = (item: ExpenseRow) => {
    Alert.alert('Eliminar', `¿Eliminar “${item.name}”?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            setBusy(true);
            try {
              await api.deleteFixedExpense(item.id);
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
      <PageHeader title="Gastos fijos" subtitle="Gastos e ingresos recurrentes" icon="wallet" />
      <View style={{ padding: space.md }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <>
            <SectionLabel>Nuevo</SectionLabel>
            <Field
              label="Nombre"
              value={form.name}
              onChangeText={(v) => setForm((f) => ({ ...f, name: v }))}
            />
            <Field
              label="Monto"
              value={form.amount}
              onChangeText={(v) => setForm((f) => ({ ...f, amount: v }))}
              keyboardType="decimal-pad"
            />
            <Field
              label="Categoría"
              value={form.category}
              onChangeText={(v) => setForm((f) => ({ ...f, category: v }))}
            />
            <View style={styles.chips}>
              <Chip
                label="GASTO"
                selected={form.type === 'GASTO'}
                onPress={() => setForm((f) => ({ ...f, type: 'GASTO' }))}
              />
              <Chip
                label="INGRESO"
                selected={form.type === 'INGRESO'}
                onPress={() => setForm((f) => ({ ...f, type: 'INGRESO' }))}
              />
            </View>
            <View style={styles.chips}>
              {FREQUENCIES.map((f) => (
                <Chip
                  key={f}
                  label={f}
                  selected={form.frequency === f}
                  onPress={() => setForm((prev) => ({ ...prev, frequency: f }))}
                />
              ))}
            </View>
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
            <AppText style={{ textAlign: 'center', color: colors.gray500 }}>Sin registros</AppText>
          }
          renderItem={({ item }) => (
            <Pressable onPress={() => (canWrite ? openEdit(item) : undefined)}>
              <Card style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <AppText weight="bold" style={{ flex: 1 }}>
                    {item.name}
                  </AppText>
                  {item.type ? <Badge label={item.type} /> : null}
                </View>
                <AppText style={styles.meta}>
                  ${Number(item.amount ?? 0).toFixed(0)} · {item.frequency} · {item.category}
                  {item.is_active === false ? ' · Inactivo' : ''}
                </AppText>
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
                Editar gasto fijo
              </AppText>
              <Field
                label="Nombre"
                value={editForm.name}
                onChangeText={(v) => setEditForm((f) => ({ ...f, name: v }))}
              />
              <Field
                label="Monto"
                value={editForm.amount}
                onChangeText={(v) => setEditForm((f) => ({ ...f, amount: v }))}
                keyboardType="decimal-pad"
              />
              <Field
                label="Categoría"
                value={editForm.category}
                onChangeText={(v) => setEditForm((f) => ({ ...f, category: v }))}
              />
              <View style={styles.chips}>
                <Chip
                  label="GASTO"
                  selected={editForm.type === 'GASTO'}
                  onPress={() => setEditForm((f) => ({ ...f, type: 'GASTO' }))}
                />
                <Chip
                  label="INGRESO"
                  selected={editForm.type === 'INGRESO'}
                  onPress={() => setEditForm((f) => ({ ...f, type: 'INGRESO' }))}
                />
              </View>
              <View style={styles.chips}>
                {FREQUENCIES.map((f) => (
                  <Chip
                    key={f}
                    label={f}
                    selected={editForm.frequency === f}
                    onPress={() => setEditForm((prev) => ({ ...prev, frequency: f }))}
                  />
                ))}
              </View>
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
