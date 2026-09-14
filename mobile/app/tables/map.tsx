import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
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
import { colors, radius, space } from '../../src/theme';
import { AppText, Chip, PageHeader, PrimaryButton } from '../../src/ui/primitives';

const CANVAS_W = 720;
const CANVAS_H = 520;
const TABLE_SIZE = 56;

function tableColor(status: string) {
  if (status === 'OCUPADA') return colors.amber;
  if (status === 'LIBRE') return colors.green;
  return colors.gray400;
}

export default function TablesMapScreen() {
  const router = useRouter();
  const { width } = useWindowDimensions();
  const pad = space.lg * 2;
  const scale = Math.max(0.4, (width - pad) / CANVAS_W);
  const canvasW = CANVAS_W * scale;
  const canvasH = CANVAS_H * scale;
  const tablePx = TABLE_SIZE * scale;

  const [data, setData] = useState<TableLayoutPayload | null>(null);
  const [sectorId, setSectorId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (sid?: number | null) => {
    setError(null);
    try {
      const payload = await api.tablesLayout(sid ?? undefined);
      setData(payload);
      setSectorId(payload.sector_id);
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
    setSectorId(id);
    setLoading(true);
    void load(id);
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Mapa del salón" subtitle="Tocá una mesa para abrirla" icon="map" />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={
          <RefreshControl
            refreshing={false}
            onRefresh={() => {
              setLoading(true);
              void load(sectorId);
            }}
          />
        }
      >
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />

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

        {loading ? (
          <ActivityIndicator color={colors.teal500} style={{ marginTop: 24 }} />
        ) : (
          <View style={[styles.canvas, { width: canvasW, height: canvasH }]}>
            {(data?.tables ?? []).map((t) => {
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
              return (
                <Pressable
                  key={t.id}
                  onPress={() => router.push(`/table/${t.id}` as Href)}
                  style={[
                    styles.table,
                    {
                      left,
                      top,
                      width: size,
                      height: size,
                      borderRadius: round,
                      backgroundColor: bg,
                    },
                  ]}
                >
                  <AppText weight="bold" style={styles.tableNum}>
                    {t.number}
                  </AppText>
                </Pressable>
              );
            })}
            {(data?.tables?.length ?? 0) === 0 ? (
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
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, paddingBottom: 48, gap: 10 },
  sectorRow: { gap: 8, paddingVertical: 4 },
  err: { color: colors.danger },
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
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.55)',
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
  legend: { flexDirection: 'row', gap: 16, justifyContent: 'center', marginTop: 8 },
  legendItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  dot: { width: 12, height: 12, borderRadius: 6 },
  meta: { color: colors.gray600, fontSize: 13 },
});
