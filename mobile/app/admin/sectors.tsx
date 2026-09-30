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
import type { CatalogSector } from '../../src/api/types';
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

const emptyForm = () => ({
  name: '',
  description: '',
  is_active: true,
});

export default function AdminSectorsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'catalog.write');
  const [rows, setRows] = useState<CatalogSector[]>([]);
  const [form, setForm] = useState(emptyForm());
  const [editing, setEditing] = useState<CatalogSector | null>(null);
  const [editForm, setEditForm] = useState(emptyForm());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows(await api.catalogSectors());
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

  const openEdit = (item: CatalogSector) => {
    setEditing(item);
    setEditForm({
      name: item.name ?? '',
      description: item.description ?? '',
      is_active: item.is_active !== false,
    });
  };

  const create = async () => {
    if (!form.name.trim()) return;
    setBusy(true);
    try {
      await api.createCatalogSector({
        name: form.name.trim(),
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
    if (!editForm.name.trim()) {
      Alert.alert('Validación', 'Nombre requerido');
      return;
    }
    setBusy(true);
    try {
      await api.updateCatalogSector(editing.id, {
        name: editForm.name.trim(),
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
      <FxHeader title="Sectores" subtitle="Zonas de mesas" onBack={() => router.back()} />
      {loading && rows.length === 0 ? (
        <ActivityIndicator color={fx.brand} style={{ marginTop: 32 }} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(c) => String(c.id)}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListHeaderComponent={
            <View style={styles.headerBlock}>
              {canWrite ? (
                <Surface style={styles.formCard}>
                  <AppText weight="semibold" style={styles.section}>
                    Nuevo sector
                  </AppText>
                  <Field
                    label="Nombre"
                    value={form.name}
                    onChangeText={(v) => setForm((f) => ({ ...f, name: v }))}
                    placeholder="Ej. Terraza"
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
                  {item.description ? (
                    <AppText style={styles.meta} numberOfLines={2}>
                      {item.description}
                    </AppText>
                  ) : null}
                </View>
                <Badge label={item.is_active === false ? 'INACTIVO' : 'ACTIVO'} />
                {canWrite ? <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} /> : null}
              </View>
              {canWrite ? (
                <View style={styles.itemActions}>
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
                                await api.deleteCatalogSector(item.id);
                                await load();
                              } catch (e) {
                                setError(e instanceof ApiError ? e.message : 'Error');
                              }
                            })(),
                        },
                      ])
                    }
                  />
                </View>
              ) : null}
            </Surface>
          )}
        />
      )}

      <ModalSheet
        visible={!!editing}
        title="Editar sector"
        onClose={() => setEditing(null)}
        maxHeight="90%"
      >
        <Field
          label="Nombre"
          value={editForm.name}
          onChangeText={(v) => setEditForm((f) => ({ ...f, name: v }))}
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
      </ModalSheet>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 56 },
  headerBlock: { gap: 12, marginBottom: 8 },
  formCard: { gap: 4 },
  section: { fontSize: 13, color: fx.inkMuted, marginBottom: 4 },
  err: { color: fx.danger },
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
  itemActions: { paddingHorizontal: fx.space.md, paddingBottom: 14 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});
