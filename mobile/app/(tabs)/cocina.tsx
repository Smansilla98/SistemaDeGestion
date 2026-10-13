import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { useKeepAwake } from 'expo-keep-awake';
import { api, ApiError } from '../../src/api/client';

type BoardItem = {
  id: number;
  name: string;
  quantity: number;
  status: string;
  observations?: string | null;
  modifiers?: Array<{ id?: number; name?: string }>;
};
type BoardOrder = {
  id: number;
  number: string;
  status: string;
  sent_at?: string;
  place?: string;
  table?: string;
  sector?: string | null;
  waiter?: string;
  observations?: string | null;
  items?: BoardItem[];
};

const REFRESH_MS = 5000;
const WARN_MIN = 10;
const LATE_MIN = 15;

/** Paleta propia: pantalla de pared, oscura y de alto contraste (igual que el KDS web). */
const kd = {
  bg: '#0e1416',
  panel: '#172124',
  panel2: '#1f2c30',
  line: '#2c3d42',
  ink: '#f2f6f7',
  muted: '#9db0b5',
  new: '#3aa0b3',
  prep: '#f0a72e',
  ready: '#37b36b',
  late: '#e5484d',
  note: '#ffe2a3',
  noteBg: '#3a2f12',
  mods: '#8fd3e0',
} as const;

const STATUS: Record<string, { label: string; color: string }> = {
  ENVIADO: { label: 'NUEVO', color: kd.new },
  EN_PREPARACION: { label: 'EN PREPARACIÓN', color: kd.prep },
  LISTO: { label: 'LISTO', color: kd.ready },
};

function elapsed(sentAt: string | undefined, now: number): { minutes: number; label: string } {
  const start = sentAt ? Date.parse(sentAt) : NaN;
  if (Number.isNaN(start)) return { minutes: 0, label: '0:00' };
  const seconds = Math.max(0, Math.floor((now - start) / 1000));
  const minutes = Math.floor(seconds / 60);
  const label =
    minutes >= 60
      ? `${Math.floor(minutes / 60)}h ${String(minutes % 60).padStart(2, '0')}m`
      : `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
  return { minutes, label };
}

/**
 * Cocina: pantalla de solo lectura. No hay acciones; se refresca sola
 * y mantiene el dispositivo encendido mientras está a la vista.
 */
export default function CocinaScreen() {
  useKeepAwake();
  const { width } = useWindowDimensions();
  const columns = width >= 1100 ? 3 : width >= 700 ? 2 : 1;

  const [orders, setOrders] = useState<BoardOrder[]>([]);
  const [counts, setCounts] = useState<Record<string, number>>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [now, setNow] = useState(() => Date.now());
  const lastOkAt = useRef<number | null>(null);
  // Diferencia entre el reloj del servidor y el del dispositivo, para que los tiempos no mientan.
  const clockOffset = useRef(0);

  const load = useCallback(async () => {
    try {
      const board = await api.kitchenBoard();
      if (board.server_time) {
        const server = Date.parse(board.server_time);
        if (!Number.isNaN(server)) clockOffset.current = server - Date.now();
      }
      setCounts(board.counts ?? {});
      setOrders((board.orders as BoardOrder[]) ?? []);
      lastOkAt.current = Date.now();
      setError(null);
    } catch (e) {
      // Se conserva el último tablero conocido y se avisa arriba.
      setError(e instanceof ApiError ? e.message : 'Sin conexión');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void load();
      const t = setInterval(() => void load(), REFRESH_MS);
      return () => clearInterval(t);
    }, [load]),
  );

  useEffect(() => {
    const t = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(t);
  }, []);

  // Lo más viejo primero: es lo que hay que sacar antes.
  const sorted = useMemo(
    () =>
      [...orders].sort(
        (a, b) => (Date.parse(a.sent_at ?? '') || 0) - (Date.parse(b.sent_at ?? '') || 0),
      ),
    [orders],
  );

  const serverNow = now + clockOffset.current;
  const stale = lastOkAt.current !== null && now - lastOkAt.current > REFRESH_MS * 3;

  if (loading) {
    return (
      <View style={[styles.root, styles.center]}>
        <ActivityIndicator color={kd.ink} size="large" />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <View style={styles.top}>
        <View style={styles.counts}>
          {(['ENVIADO', 'EN_PREPARACION', 'LISTO'] as const).map((key) => (
            <View key={key} style={[styles.count, { borderLeftColor: STATUS[key].color }]}>
              <Text style={styles.countValue}>{counts[key] ?? 0}</Text>
              <Text style={styles.countLabel}>{STATUS[key].label}</Text>
            </View>
          ))}
        </View>
        <View style={styles.live} accessibilityLiveRegion="polite">
          <View style={[styles.dot, { backgroundColor: error || stale ? kd.late : kd.ready }]} />
          <Text style={[styles.liveText, (error || stale) && styles.liveTextOff]}>
            {error || stale ? `${error ?? 'Sin conexión'} — reintentando` : 'En vivo'}
          </Text>
        </View>
      </View>

      <FlatList
        key={columns}
        data={sorted}
        numColumns={columns}
        keyExtractor={(o) => String(o.id)}
        contentContainerStyle={styles.list}
        columnWrapperStyle={columns > 1 ? styles.columns : undefined}
        ListEmptyComponent={<Text style={styles.empty}>Cocina al día: no hay pedidos pendientes</Text>}
        renderItem={({ item }) => {
          const time = elapsed(item.sent_at, serverNow);
          const status = STATUS[item.status] ?? { label: item.status, color: kd.new };
          const ready = item.status === 'LISTO';
          const late = !ready && time.minutes >= LATE_MIN;
          const warn = !ready && !late && time.minutes >= WARN_MIN;
          const meta = [item.number, item.sector, item.waiter].filter(Boolean).join(' · ');

          return (
            <View
              style={[
                styles.card,
                { borderTopColor: late ? kd.late : status.color },
                late && styles.cardLate,
              ]}
            >
              <View style={styles.head}>
                <View style={styles.headLeft}>
                  <Text style={styles.place}>
                    {item.place ?? (item.table ? `Mesa ${item.table}` : 'Barra')}
                  </Text>
                  {meta ? <Text style={styles.meta}>{meta}</Text> : null}
                  <Text style={styles.chip}>{status.label}</Text>
                </View>
                <Text
                  style={[styles.timer, warn && styles.timerWarn, late && styles.timerLate]}
                  accessibilityLabel={`Hace ${time.minutes} minutos`}
                >
                  {time.label}
                </Text>
              </View>

              {item.observations ? <Text style={styles.note}>{item.observations}</Text> : null}

              {(item.items ?? []).map((it, index) => {
                const done = it.status === 'LISTO' || it.status === 'ENTREGADO';
                const mods = (it.modifiers ?? []).map((m) => m.name).filter(Boolean).join(' · ');
                return (
                  <View key={it.id} style={[styles.item, index === 0 && styles.itemFirst]}>
                    <Text style={[styles.qty, done && styles.done]}>{it.quantity}×</Text>
                    <View style={styles.itemBody}>
                      <Text style={[styles.name, done && styles.doneName]}>{it.name}</Text>
                      {mods ? <Text style={[styles.mods, done && styles.done]}>{mods}</Text> : null}
                      {it.observations ? <Text style={styles.obs}>{it.observations}</Text> : null}
                    </View>
                  </View>
                );
              })}
            </View>
          );
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: kd.bg },
  center: { justifyContent: 'center', alignItems: 'center' },
  top: {
    backgroundColor: kd.panel,
    borderBottomWidth: 1,
    borderBottomColor: kd.line,
    paddingHorizontal: 12,
    paddingVertical: 10,
    gap: 8,
  },
  counts: { flexDirection: 'row', gap: 8 },
  count: {
    flex: 1,
    backgroundColor: kd.panel2,
    borderLeftWidth: 6,
    borderRadius: 12,
    paddingVertical: 8,
    paddingHorizontal: 10,
  },
  countValue: { color: kd.ink, fontSize: 28, fontWeight: '800', fontVariant: ['tabular-nums'] },
  countLabel: { color: kd.muted, fontSize: 11, fontWeight: '700', letterSpacing: 0.4 },
  live: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  dot: { width: 10, height: 10, borderRadius: 5 },
  liveText: { color: kd.muted, fontSize: 13 },
  liveTextOff: { color: '#ffb4b6', fontWeight: '700' },
  list: { padding: 12, paddingBottom: 32 },
  columns: { gap: 12 },
  empty: { color: kd.muted, textAlign: 'center', marginTop: 80, fontSize: 20, fontWeight: '600' },
  card: {
    flex: 1,
    backgroundColor: kd.panel,
    borderWidth: 1,
    borderColor: kd.line,
    borderTopWidth: 10,
    borderRadius: 16,
    padding: 14,
    marginBottom: 12,
    gap: 10,
  },
  cardLate: { borderColor: kd.late },
  head: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 10 },
  headLeft: { flex: 1, gap: 4 },
  place: { color: kd.ink, fontSize: 30, fontWeight: '800', letterSpacing: -0.5 },
  meta: { color: kd.muted, fontSize: 14 },
  chip: {
    alignSelf: 'flex-start',
    color: kd.ink,
    backgroundColor: kd.panel2,
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 0.5,
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 999,
    overflow: 'hidden',
  },
  timer: {
    color: kd.ink,
    backgroundColor: kd.panel2,
    fontSize: 26,
    fontWeight: '800',
    fontVariant: ['tabular-nums'],
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 10,
    overflow: 'hidden',
  },
  timerWarn: { backgroundColor: kd.prep, color: '#1b1300' },
  timerLate: { backgroundColor: kd.late, color: '#fff' },
  note: {
    color: kd.note,
    backgroundColor: kd.noteBg,
    fontSize: 16,
    fontWeight: '600',
    padding: 10,
    borderRadius: 10,
    overflow: 'hidden',
  },
  item: {
    flexDirection: 'row',
    gap: 12,
    alignItems: 'flex-start',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: kd.line,
  },
  itemFirst: { borderTopWidth: 0, paddingTop: 0 },
  qty: {
    minWidth: 54,
    textAlign: 'center',
    color: kd.ink,
    backgroundColor: kd.panel2,
    fontSize: 26,
    fontWeight: '800',
    fontVariant: ['tabular-nums'],
    borderRadius: 10,
    paddingHorizontal: 6,
    paddingVertical: 2,
    overflow: 'hidden',
  },
  itemBody: { flex: 1, gap: 2 },
  name: { color: kd.ink, fontSize: 22, fontWeight: '700' },
  mods: { color: kd.mods, fontSize: 17, fontWeight: '600' },
  obs: { color: kd.note, fontSize: 16, fontWeight: '600' },
  done: { color: kd.muted },
  doneName: { color: kd.muted, textDecorationLine: 'line-through' },
});
