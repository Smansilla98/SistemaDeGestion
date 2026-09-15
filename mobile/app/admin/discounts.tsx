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
import type { DiscountTypeRow } from '../../src/api/types';
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

const emptyForm = () => ({
  name: '',
  percentage: '',
  description: '',
  is_active: true,
});

export default function AdminDiscountsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'catalog.write');
  const [rows, setRows] = useState<DiscountTypeRow[]>([]);
  const [form, setForm] = useState(emptyForm());
  const [editing, setEditing] = useState<DiscountTypeRow | null>(null);
  const [editForm, setEditForm] = useState(emptyForm());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows(await api.discountTypes());
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

  const openEdit = (item: DiscountTypeRow) => {
    setEditing(item);
    setEditForm({
      name: item.name ?? '',
      percentage: String(item.percentage ?? ''),
      description: item.description ?? '',
      is_active: item.is_active !== false,
    });
  };

  const create = async () => {
    const pct = Number(form.percentage);
    if (!form.name.trim() || Number.isNaN(pct)) {
      setError('Nombre y porcentaje requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.createDiscountType({
        name: form.name.trim(),
        percentage: pct,
        description: form.description.trim() || undefined,
        is_active: form.is_active,
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
    const pct = Number(editForm.percentage);
    if (!editForm.name.trim() || Number.isNaN(pct)) {
      Alert.alert('Validación', 'Nombre y porcentaje requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.updateDiscountType(editing.id, {
        name: editForm.name.trim(),
        percentage: pct,
        description: editForm.description.trim() || null,
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

  return (
    <View style={styles.root}>
      <PageHeader title="Descuentos" subtitle="Tipos de descuento" icon="pricetag" />
      <View style={{ padding: space.md }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <>
            <SectionLabel>Nuevo descuento</SectionLabel>
            <Field
              label="Nombre"
              value={form.name}
              onChangeText={(v) => setForm((f) => ({ ...f, name: v }))}
            />
            <Field
              label="Porcentaje"
              value={form.percentage}
              onChangeText={(v) => setForm((f) => ({ ...f, percentage: v }))}
              keyboardType="decimal-pad"
              placeholder="10"
            />
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
          keyExtractor={(c) => String(c.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          renderItem={({ item }) => (
            <Pressable onPress={() => (canWrite ? openEdit(item) : undefined)}>
              <Card style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <AppText weight="bold" style={{ flex: 1 }}>
                    {item.name} · {Number(item.percentage)}%
                  </AppText>
                  <Badge label={item.is_active === false ? 'INACTIVO' : 'ACTIVO'} />
                </View>
                {canWrite ? (
                  <PrimaryButton
                    title="Eliminar"
                    variant="danger"
                    onPress={() =>
                      Alert.alert('Eliminar', item.name, [
                        { text: 'Cancelar', style: 'cancel' },
                        {
                          text: 'Eliminar',
                          style: 'destructive',
                          onPress: () =>
                            void (async () => {
                              try {
                                await api.deleteDiscountType(item.id);
                                await load();
                              } catch (e) {
                                setError(e instanceof ApiError ? e.message : 'Error');
                              }
                            })(),
                        },
                      ])
                    }
                  />
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
                Editar descuento
              </AppText>
              <Field
                label="Nombre"
                value={editForm.name}
                onChangeText={(v) => setEditForm((f) => ({ ...f, name: v }))}
              />
              <Field
                label="Porcentaje"
                value={editForm.percentage}
                onChangeText={(v) => setEditForm((f) => ({ ...f, percentage: v }))}
                keyboardType="decimal-pad"
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
              <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setEditing(null)} />
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  err: { color: colors.danger, marginTop: 8 },
  row: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
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
