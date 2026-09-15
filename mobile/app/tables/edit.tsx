import { useCallback, useEffect, useState } from 'react';
import { Alert, ScrollView, StyleSheet, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { CatalogSector } from '../../src/api/types';
import { hasPermission } from '../../src/auth/permissions';
import { useAuth } from '../../src/auth/AuthContext';
import { colors, space } from '../../src/theme';
import { AppText, Chip, Field, PageHeader, PrimaryButton } from '../../src/ui/primitives';

const STATUSES = ['LIBRE', 'OCUPADA', 'RESERVADA', 'CERRADA'] as const;

type SectorOpt = Pick<CatalogSector, 'id' | 'name'>;

export default function EditTableScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const tableId = Number(id);
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'tables.write');

  const [number, setNumber] = useState('');
  const [capacity, setCapacity] = useState('4');
  const [sectorId, setSectorId] = useState<number | null>(null);
  const [status, setStatus] = useState<string>('LIBRE');
  const [sectors, setSectors] = useState<SectorOpt[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!tableId) return;
    setError(null);
    try {
      const [detail, layout] = await Promise.all([
        api.tableDetail(tableId),
        api.tablesLayout(undefined),
      ]);
      setNumber(String(detail.table.number ?? ''));
      setCapacity(String(detail.table.capacity ?? 4));
      setSectorId(detail.table.sector_id ?? null);
      setStatus(detail.table.status ?? 'LIBRE');
      setSectors((layout.sectors ?? []).map((s) => ({ id: s.id, name: s.name })));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error al cargar');
    } finally {
      setLoading(false);
    }
  }, [tableId]);

  useEffect(() => {
    void load();
  }, [load]);

  const save = async () => {
    if (!canWrite) return;
    setSaving(true);
    setError(null);
    try {
      await api.updateTable(tableId, {
        number: number.trim(),
        capacity: Math.max(1, Number(capacity) || 1),
        sector_id: sectorId,
        status,
      });
      router.back();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setSaving(false);
    }
  };

  const remove = () => {
    Alert.alert('Eliminar mesa', '¿Confirmás borrar esta mesa?', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            try {
              await api.deleteTable(tableId);
              router.replace('/(tabs)/mesas');
            } catch (e) {
              setError(e instanceof ApiError ? e.message : 'No se pudo eliminar');
            }
          })();
        },
      },
    ]);
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Editar mesa" subtitle={tableId ? `#${tableId}` : ''} icon="create" />
      <ScrollView contentContainerStyle={styles.body}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {loading ? (
          <AppText>Cargando…</AppText>
        ) : (
          <>
            {error ? <AppText style={styles.err}>{error}</AppText> : null}
            <Field label="Número / nombre" value={number} onChangeText={setNumber} />
            <Field
              label="Capacidad"
              value={capacity}
              onChangeText={setCapacity}
              keyboardType="number-pad"
            />
            <AppText weight="semibold" style={styles.label}>
              Sector
            </AppText>
            <View style={styles.chips}>
              {sectors.map((s) => (
                <Chip
                  key={s.id}
                  label={s.name}
                  selected={sectorId === s.id}
                  onPress={() => setSectorId(s.id)}
                />
              ))}
            </View>
            <AppText weight="semibold" style={styles.label}>
              Estado
            </AppText>
            <View style={styles.chips}>
              {STATUSES.map((s) => (
                <Chip key={s} label={s} selected={status === s} onPress={() => setStatus(s)} />
              ))}
            </View>
            {canWrite ? (
              <>
                <PrimaryButton title="Guardar" loading={saving} onPress={() => void save()} />
                <PrimaryButton title="Eliminar mesa" variant="ghost" onPress={remove} />
              </>
            ) : (
              <AppText>Sin permiso tables.write</AppText>
            )}
          </>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, gap: 10, paddingBottom: 48 },
  err: { color: colors.danger },
  label: { color: colors.gray600, marginTop: 4 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});
