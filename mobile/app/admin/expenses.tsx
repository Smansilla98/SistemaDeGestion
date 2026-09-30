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
import type { ExpenseRow } from '../../src/api/types';
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

const FREQUENCIES = ['MENSUAL', 'QUINCENAL', 'SEMANAL', 'DIARIO', 'ANUAL'] as const;

const emptyForm = () => ({
  name: '',
  amount: '',
  category: 'GENERAL',
  type: 'GASTO' as 'GASTO' | 'INGRESO',
  frequency: 'MENSUAL',
  description: '',
  due_day: '',
  end_date: '',
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
      description: item.description ?? '',
      due_day: item.due_day != null ? String(item.due_day) : '',
      end_date: item.end_date ? String(item.end_date).slice(0, 10) : '',
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
        description: form.description.trim() || null,
        due_day: form.due_day ? Number(form.due_day) : null,
        start_date: new Date().toISOString().slice(0, 10),
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
        description: editForm.description.trim() || null,
        due_day: editForm.due_day ? Number(editForm.due_day) : null,
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
      <FxHeader
        title="Gastos fijos"
        subtitle="Gastos e ingresos recurrentes"
        onBack={() => router.back()}
      />
      {loading && rows.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(e) => String(e.id)}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={<AppText style={styles.empty}>Sin registros</AppText>}
          ListHeaderComponent={
            <View style={styles.headerBlock}>
              {canWrite ? (
                <Surface style={styles.formCard}>
                  <AppText weight="semibold" style={styles.section}>
                    Nuevo
                  </AppText>
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
                  <Field
                    label="Día de cobro (1-31)"
                    value={form.due_day}
                    onChangeText={(v) => setForm((f) => ({ ...f, due_day: v }))}
                    keyboardType="number-pad"
                    placeholder="Opcional"
                  />
                  <Field
                    label="Fin (YYYY-MM-DD)"
                    value={form.end_date}
                    onChangeText={(v) => setForm((f) => ({ ...f, end_date: v }))}
                    placeholder="Opcional"
                  />
                  <Field
                    label="Descripción"
                    value={form.description}
                    onChangeText={(v) => setForm((f) => ({ ...f, description: v }))}
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
                  <AppText style={styles.meta}>
                    ${Number(item.amount ?? 0).toFixed(0)} · {item.frequency} · {item.category}
                    {item.is_active === false ? ' · Inactivo' : ''}
                  </AppText>
                </View>
                {item.type ? <Badge label={item.type} /> : null}
                {canWrite ? <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} /> : null}
              </View>
            </Surface>
          )}
        />
      )}

      <ModalSheet
        visible={!!editing}
        title="Editar gasto fijo"
        onClose={() => setEditing(null)}
        maxHeight="90%"
      >
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
        <Field
          label="Día de cobro (1-31)"
          value={editForm.due_day}
          onChangeText={(v) => setEditForm((f) => ({ ...f, due_day: v }))}
          keyboardType="number-pad"
        />
        <Field
          label="Fin (YYYY-MM-DD)"
          value={editForm.end_date}
          onChangeText={(v) => setEditForm((f) => ({ ...f, end_date: v }))}
        />
        <Field
          label="Descripción"
          value={editForm.description}
          onChangeText={(v) => setEditForm((f) => ({ ...f, description: v }))}
          multiline
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
