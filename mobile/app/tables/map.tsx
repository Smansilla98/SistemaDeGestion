import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  useWindowDimensions,
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

const CANVAS_W = 720;
const CANVAS_H = 520;
const TABLE_SIZE = 56;

type LocalTable = TableLayoutPayload['tables'][number] & {
  position_x: number;
  position_y: number;
};

function tableColor(status: string) {
  if (status === 'OCUPADA') return colors.amber;
  if (status === 'LIBRE') return colors.green;
  if (status === 'RESERVADA') return colors.teal500;
  return colors.gray400;
}

export default function TablesMapScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const { width } = useWindowDimensions();
  const pad = space.lg * 2;
  const scale = Math.max(0.4, (width - pad) / CANVAS_W);
  const canvasW = CANVAS_W * scale;
  const canvasH = CANVAS_H * scale;
  const tablePx = TABLE_SIZE * scale;

  const canEdit = useMemo(
    () =>
      (isAdminRole(user?.role) || user?.role === 'GERENTE') &&
      hasPermission(user, 'tables.write'),
    [user],
  );

  const [data, setData] = useState<TableLayoutPayload | null>(null);
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
      setData(payload);
      setSectorId(payload.sector_id);
      setTables(payload.tables.map((t) => ({ ...t })));
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
      Alert.alert('Cambios sin guardar', 'Guardá o cancelá la edición antes de cambiar de sector.');
      return;
    }
    setSectorId(id);
    setLoading(true);
    void load(id);
  };

  const nudge = (dx: number, dy: number) => {
    if (!editMode || selectedId == null) return;
    setTables((prev) =>
      prev.map((t) => {
        if (t.id !== selectedId) return t;
        const nx = Math.max(0, Math.min(CANVAS_W, t.position_x + dx));
        const ny = Math.max(0, Math.min(CANVAS_H, t.position_y + dy));
        return { ...t, position_x: nx, position_y: ny };
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
        position_x: 80,
        position_y: 80,
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

  return (
    <View style={styles.root}>
      <PageHeader
        title="Mapa del salón"
        subtitle={editMode ? 'Modo edición · mové con los botones' : 'Tocá una mesa para abrirla'}
        icon="map"
      />
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
                <PrimaryButton
                  title="Nueva mesa"
                  variant="ghost"
                  onPress={() => setShowCreate(true)}
                />
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

        {data?.sectors?.length ? (
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.sectorRow}
          >
            {data.sectors.map((s) => (
              <Chip
                key={s.id}
                label={s.name}
                selected={sectorId === s.id}
                onPress={() => selectSector(s.id)}
              />
            ))}
          </ScrollView>
        ) : null}

        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {editMode && selectedId != null ? (
          <View style={styles.nudge}>
            <PrimaryButton title="↑" variant="ghost" onPress={() => nudge(0, -20)} />
            <View style={styles.nudgeMid}>
              <PrimaryButton title="←" variant="ghost" onPress={() => nudge(-20, 0)} />
              <PrimaryButton title="→" variant="ghost" onPress={() => nudge(20, 0)} />
            </View>
            <PrimaryButton title="↓" variant="ghost" onPress={() => nudge(0, 20)} />
            <PrimaryButton
              title="Editar datos"
              variant="ghost"
              onPress={() => router.push(`/tables/edit?id=${selectedId}` as Href)}
            />
          </View>
        ) : null}

        {loading ? (
          <ActivityIndicator color={colors.teal500} style={{ marginTop: 24 }} />
        ) : (
          <View style={[styles.canvas, { width: canvasW, height: canvasH }]}>
            {tables.map((t) => {
              const size = tablePx;
              const left = Math.min(
                canvasW - size,
                Math.max(0, t.position_x * scale - size / 2),
              );
              const top = Math.min(
                canvasH - size,
                Math.max(0, t.position_y * scale - size / 2),
              );
              const bg = tableColor(t.status);
              const round = (t.capacity ?? 4) <= 2 ? size / 2 : radius.lg;
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
                    styles.table,
                    {
                      left,
                      top,
                      width: size,
                      height: size,
                      borderRadius: round,
                      backgroundColor: bg,
                      borderColor: selected ? colors.white : 'rgba(255,255,255,0.55)',
                      borderWidth: selected ? 3 : 2,
                    },
                  ]}
                >
                  <AppText weight="bold" style={styles.tableNum}>
                    {t.number}
                  </AppText>
                </Pressable>
              );
            })}
            {tables.length === 0 ? (
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
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
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
  sectorRow: { gap: 8, paddingVertical: 4 },
  err: { color: colors.danger },
  nudge: { alignItems: 'center', gap: 4 },
  nudgeMid: { flexDirection: 'row', gap: 12 },
  canvas: {
    alignSelf: 'center',
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
    overflow: 'hidden',
    position: 'relative',
  },
  table: {
    position: 'absolute',
    alignItems: 'center',
    justifyContent: 'center',
  },
  tableNum: { color: colors.white, fontSize: 14 },
  empty: {
    position: 'absolute',
    top: '45%',
    alignSelf: 'center',
    width: '100%',
    textAlign: 'center',
    color: colors.gray500,
  },
  legend: { flexDirection: 'row', flexWrap: 'wrap', gap: 16, justifyContent: 'center', marginTop: 8 },
  legendItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  dot: { width: 12, height: 12, borderRadius: 6 },
  meta: { color: colors.gray600, fontSize: 13 },
});
