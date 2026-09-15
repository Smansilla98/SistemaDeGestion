import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { TableLayoutPayload } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission, isAdminRole } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import { AppText, Chip, Field, PageHeader, PrimaryButton } from '../../src/ui/primitives';
import { AppIcon } from '../../src/ui/icons';

type LocalTable = TableLayoutPayload['tables'][number];

function statusBg(status: string) {
  if (status === 'OCUPADA') return colors.amber;
  if (status === 'LIBRE') return colors.green;
  if (status === 'RESERVADA') return colors.teal500;
  return colors.gray400;
}

/**
 * Mapa liviano: grilla compacta (sin canvas 720×520).
 * Edición = reordenar con flechas sobre la celda seleccionada + guardar posiciones en grilla.
 */
export default function TablesMapScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canEdit = useMemo(
    () =>
      (isAdminRole(user?.role) || user?.role === 'GERENTE') &&
      hasPermission(user, 'tables.write'),
    [user],
  );

  const [sectors, setSectors] = useState<Array<{ id: number; name: string }>>([]);
  const [tables, setTables] = useState<LocalTable[]>([]);
  const [sectorId, setSectorId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editMode, setEditMode] = useState(false);
  const [dirty, setDirty] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [newNumber, setNewNumber] = useState('');
  const [newCapacity, setNewCapacity] = useState('4');

  const load = useCallback(async (sid?: number | null) => {
    setError(null);
    try {
      const payload = await api.tablesLayout(sid ?? undefined);
      setSectors(payload.sectors ?? []);
      setSectorId(payload.sector_id);
      setTables(payload.tables ?? []);
      setDirty(false);
      setSelectedId(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error al cargar mapa');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load(null);
    }, [load]),
  );

  const selectSector = (id: number) => {
    if (id === sectorId) return;
    if (dirty) {
      Alert.alert('Cambios sin guardar', 'Guardá o cancelá antes de cambiar de sector.');
      return;
    }
    setSectorId(id);
    setLoading(true);
    void load(id);
  };

  /** Reposiciona en grilla: 4 columnas × filas (liviano, sin drag). */
  const moveSelected = (dir: 'up' | 'down' | 'left' | 'right') => {
    if (!editMode || selectedId == null) return;
    const COLS = 4;
    const STEP = 80;
    setTables((prev) =>
      prev.map((t) => {
        if (t.id !== selectedId) return t;
        let x = t.position_x;
        let y = t.position_y;
        if (dir === 'left') x = Math.max(40, x - STEP);
        if (dir === 'right') x = Math.min(40 + (COLS - 1) * STEP, x + STEP);
        if (dir === 'up') y = Math.max(40, y - STEP);
        if (dir === 'down') y = y + STEP;
        return { ...t, position_x: x, position_y: y };
      }),
    );
    setDirty(true);
  };

  const saveLayout = async () => {
    if (sectorId == null) return;
    setSaving(true);
    setError(null);
    try {
      await api.saveTablesLayout({
        sector_id: sectorId,
        tables: tables.map((t) => ({
          id: t.id,
          position_x: Math.round(t.position_x),
          position_y: Math.round(t.position_y),
        })),
      });
      setDirty(false);
      setEditMode(false);
      await load(sectorId);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setSaving(false);
    }
  };

  const createTable = async () => {
    if (sectorId == null || !newNumber.trim()) return;
    setSaving(true);
    try {
      await api.createTable({
        sector_id: sectorId,
        number: newNumber.trim(),
        capacity: Math.max(1, Number(newCapacity) || 4),
        position_x: 40 + (tables.length % 4) * 80,
        position_y: 40 + Math.floor(tables.length / 4) * 80,
      });
      setShowCreate(false);
      setNewNumber('');
      setNewCapacity('4');
      await load(sectorId);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo crear');
    } finally {
      setSaving(false);
    }
  };

  const sorted = useMemo(
    () =>
      [...tables].sort(
        (a, b) => a.position_y - b.position_y || a.position_x - b.position_x || a.number.localeCompare(b.number),
      ),
    [tables],
  );

  return (
    <View style={styles.root}>
      <PageHeader title="Mapa del salón" subtitle="Vista liviana por sector" bi="table" />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={
          <RefreshControl
            refreshing={false}
            onRefresh={() => {
              if (dirty) return;
              setLoading(true);
              void load(sectorId);
            }}
          />
        }
      >
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />

        {canEdit ? (
          <View style={styles.editRow}>
            <PrimaryButton
              title={editMode ? 'Salir edición' : 'Editar layout'}
              variant={editMode ? 'ghost' : 'primary'}
              onPress={() => {
                if (editMode && dirty) {
                  Alert.alert('¿Descartar cambios?', undefined, [
                    { text: 'Cancelar', style: 'cancel' },
                    {
                      text: 'Descartar',
                      style: 'destructive',
                      onPress: () => {
                        setEditMode(false);
                        setDirty(false);
                        void load(sectorId);
                      },
                    },
                  ]);
                  return;
                }
                setEditMode((v) => !v);
              }}
            />
            {editMode ? (
              <>
                <PrimaryButton title="Nueva mesa" variant="ghost" onPress={() => setShowCreate(true)} />
                <PrimaryButton
                  title="Guardar"
                  loading={saving}
                  disabled={!dirty}
                  onPress={() => void saveLayout()}
                />
              </>
            ) : null}
          </View>
        ) : null}

        {showCreate ? (
          <View style={styles.createBox}>
            <Field label="Número" value={newNumber} onChangeText={setNewNumber} />
            <Field
              label="Capacidad"
              value={newCapacity}
              onChangeText={setNewCapacity}
              keyboardType="number-pad"
            />
            <PrimaryButton title="Crear" loading={saving} onPress={() => void createTable()} />
            <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setShowCreate(false)} />
          </View>
        ) : null}

        {sectors.length ? (
          <View style={styles.sectorRow}>
            {sectors.map((s) => (
              <Chip
                key={s.id}
                label={s.name}
                selected={sectorId === s.id}
                onPress={() => selectSector(s.id)}
              />
            ))}
          </View>
        ) : null}

        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {editMode && selectedId != null ? (
          <View style={styles.nudge}>
            <PrimaryButton title="↑" variant="ghost" onPress={() => moveSelected('up')} />
            <View style={styles.nudgeMid}>
              <PrimaryButton title="←" variant="ghost" onPress={() => moveSelected('left')} />
              <PrimaryButton title="→" variant="ghost" onPress={() => moveSelected('right')} />
            </View>
            <PrimaryButton title="↓" variant="ghost" onPress={() => moveSelected('down')} />
            <PrimaryButton
              title="Editar datos"
              variant="ghost"
              icon="create-outline"
              onPress={() => router.push(`/tables/edit?id=${selectedId}` as Href)}
            />
          </View>
        ) : null}

        {loading ? (
          <ActivityIndicator color={colors.teal500} style={{ marginTop: 24 }} />
        ) : (
          <View style={styles.grid}>
            {sorted.map((t) => {
              const selected = selectedId === t.id;
              return (
                <Pressable
                  key={t.id}
                  onPress={() => {
                    if (editMode) {
                      setSelectedId(t.id);
                      return;
                    }
                    router.push(`/table/${t.id}` as Href);
                  }}
                  style={[
                    styles.cell,
                    { backgroundColor: statusBg(t.status) },
                    selected && styles.cellSelected,
                  ]}
                >
                  <AppText weight="bold" style={styles.cellNum}>
                    {t.number}
                  </AppText>
                  <AppText style={styles.cellCap}>{t.capacity}p</AppText>
                </Pressable>
              );
            })}
            {sorted.length === 0 ? (
              <AppText style={styles.empty}>No hay mesas en este sector</AppText>
            ) : null}
          </View>
        )}

        <View style={styles.legend}>
          <View style={styles.legendItem}>
            <View style={[styles.dot, { backgroundColor: colors.green }]} />
            <AppText style={styles.meta}>Libre</AppText>
          </View>
          <View style={styles.legendItem}>
            <View style={[styles.dot, { backgroundColor: colors.amber }]} />
            <AppText style={styles.meta}>Ocupada</AppText>
          </View>
          <View style={styles.legendItem}>
            <View style={[styles.dot, { backgroundColor: colors.teal500 }]} />
            <AppText style={styles.meta}>Reservada</AppText>
          </View>
          <AppIcon bi="table" size={14} color={colors.gray500} />
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  body: { padding: space.lg, paddingBottom: 48, gap: 10 },
  editRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  createBox: {
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    padding: space.md,
    borderWidth: 1,
    borderColor: colors.gray100,
    gap: 8,
  },
  sectorRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  err: { color: colors.danger },
  nudge: { alignItems: 'center', gap: 4 },
  nudgeMid: { flexDirection: 'row', gap: 12 },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
    padding: space.md,
  },
  cell: {
    width: '22%',
    minWidth: 64,
    aspectRatio: 1,
    borderRadius: radius.md,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.45)',
  },
  cellSelected: { borderColor: colors.white, borderWidth: 3 },
  cellNum: { color: colors.white, fontSize: 16 },
  cellCap: { color: 'rgba(255,255,255,0.8)', fontSize: 10, marginTop: 2 },
  empty: { width: '100%', textAlign: 'center', color: colors.gray500, padding: 24 },
  legend: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  legendItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  dot: { width: 10, height: 10, borderRadius: 5 },
  meta: { color: colors.gray600, fontSize: 12 },
});
