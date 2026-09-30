import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import { Badge, Card, Chip, PageHeader, PrimaryButton } from '../../src/ui/primitives';

type BoardModifier = { id?: number; name?: string; price_modifier?: number | string };
type BoardItem = {
  id: number;
  name: string;
  quantity: number;
  status: string;
  observations?: string | null;
  modifiers?: BoardModifier[];
};
type BoardOrder = {
  id: number;
  number: string;
  status: string;
  sent_at?: string;
  table?: string;
  waiter?: string;
  observations?: string | null;
  items?: BoardItem[];
};

type FilterKey = 'ALL' | 'PENDIENTES' | 'EN_PREPARACION' | 'LISTO';

const FILTERS: { key: FilterKey; label: string }[] = [
  { key: 'ALL', label: 'Todos' },
  { key: 'PENDIENTES', label: 'Pendientes' },
  { key: 'EN_PREPARACION', label: 'En prep' },
  { key: 'LISTO', label: 'Listos' },
];

function isUrgent(sentAt?: string): boolean {
  if (!sentAt) return false;
  const t = Date.parse(sentAt);
  if (Number.isNaN(t)) return false;
  return Date.now() - t > 15 * 60 * 1000;
}

function matchesFilter(order: BoardOrder, filter: FilterKey): boolean {
  if (filter === 'ALL') return true;
  if (filter === 'PENDIENTES') {
    return order.status === 'ENVIADO' || (order.items ?? []).some((i) => i.status === 'PENDIENTE');
  }
  if (filter === 'EN_PREPARACION') {
    return (
      order.status === 'EN_PREPARACION' ||
      (order.items ?? []).some((i) => i.status === 'EN_PREPARACION')
    );
  }
  if (filter === 'LISTO') {
    return order.status === 'LISTO' || (order.items ?? []).every((i) => i.status === 'LISTO' || i.status === 'ENTREGADO');
  }
  return true;
}

export default function CocinaScreen() {
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'kitchen.write');
  const [orders, setOrders] = useState<BoardOrder[]>([]);
  const [counts, setCounts] = useState<Record<string, number>>({});
  const [filter, setFilter] = useState<FilterKey>('ALL');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busyItem, setBusyItem] = useState<number | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const board = await api.kitchenBoard();
      setCounts(board.counts ?? {});
      setOrders((board.orders as BoardOrder[]) ?? []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error KDS');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
      const t = setInterval(() => void load(), 10000);
      return () => clearInterval(t);
    }, [load]),
  );

  const visible = useMemo(
    () => orders.filter((o) => matchesFilter(o, filter)),
    [orders, filter],
  );

  const setItem = async (itemId: number, status: string) => {
    setBusyItem(itemId);
    try {
      await api.kitchenItemStatus(itemId, status);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo actualizar');
    } finally {
      setBusyItem(null);
    }
  };

  if (loading) return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;

  return (
    <View style={styles.root}>
      <PageHeader title="Cocina" subtitle="Kanban en vivo" bi="egg-fried" />
      <View style={styles.counts}>
        {Object.entries(counts).map(([k, v]) => (
          <View key={k} style={styles.countBox}>
            <Text style={styles.countVal}>{v}</Text>
            <Text style={styles.countKey}>{k.replace(/_/g, ' ')}</Text>
          </View>
        ))}
      </View>
      <View style={styles.chips}>
        {FILTERS.map((f) => (
          <Chip
            key={f.key}
            label={f.label}
            selected={filter === f.key}
            onPress={() => setFilter(f.key)}
          />
        ))}
      </View>
      {error && <Text style={styles.err}>{error}</Text>}
      <FlatList
        data={visible}
        keyExtractor={(o) => String(o.id)}
        contentContainerStyle={{ padding: space.md }}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        ListEmptyComponent={<Text style={styles.empty}>Cocina libre</Text>}
        renderItem={({ item }) => {
          const urgent = isUrgent(item.sent_at);
          return (
            <Card style={[styles.card, urgent && styles.urgentCard]}>
              <View style={styles.rowBetween}>
                <Text style={styles.num}>{item.number}</Text>
                <View style={styles.rowBetween}>
                  {urgent ? <Badge label="URGENTE" /> : null}
                  <Badge label={item.status} />
                </View>
              </View>
              <Text style={styles.meta}>
                Mesa {item.table ?? '—'} · {item.waiter ?? ''}
              </Text>
              {item.observations ? (
                <Text style={styles.obs}>Pedido: {item.observations}</Text>
              ) : null}
              {(item.items ?? []).map((it) => (
                <View key={it.id} style={styles.itemRow}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.itemName}>
                      {it.quantity}× {it.name}
                    </Text>
                    {it.modifiers && it.modifiers.length > 0 ? (
                      <Text style={styles.mod}>
                        {it.modifiers.map((m) => m.name).filter(Boolean).join(', ')}
                      </Text>
                    ) : null}
                    {it.observations ? (
                      <Text style={styles.obs}>Obs: {it.observations}</Text>
                    ) : null}
                    <Badge label={it.status} />
                  </View>
                  {canWrite && (
                    <View style={styles.itemActions}>
                      {it.status !== 'EN_PREPARACION' &&
                        it.status !== 'LISTO' &&
                        it.status !== 'ENTREGADO' && (
                          <Pressable
                            style={styles.miniBtn}
                            disabled={busyItem === it.id}
                            onPress={() => void setItem(it.id, 'EN_PREPARACION')}
                          >
                            <Text style={styles.miniText}>Prep</Text>
                          </Pressable>
                        )}
                      {it.status !== 'LISTO' && it.status !== 'ENTREGADO' && (
                        <Pressable
                          style={[styles.miniBtn, styles.miniOk]}
                          disabled={busyItem === it.id}
                          onPress={() => void setItem(it.id, 'LISTO')}
                        >
                          <Text style={styles.miniText}>Listo</Text>
                        </Pressable>
                      )}
                      {it.status !== 'ENTREGADO' && (
                        <Pressable
                          style={[styles.miniBtn, styles.miniDeliver]}
                          disabled={busyItem === it.id}
                          onPress={() => void setItem(it.id, 'ENTREGADO')}
                        >
                          <Text style={styles.miniText}>ENTREGADO</Text>
                        </Pressable>
                      )}
                    </View>
                  )}
                </View>
              ))}
              {canWrite && (item.items ?? []).some((i) => i.status !== 'LISTO' && i.status !== 'ENTREGADO') && (
                <PrimaryButton
                  title="Marcar todo listo"
                  onPress={() => {
                    void Promise.all(
                      (item.items ?? [])
                        .filter((i) => i.status !== 'LISTO' && i.status !== 'ENTREGADO')
                        .map((i) => setItem(i.id, 'LISTO')),
                    );
                  }}
                />
              )}
            </Card>
          );
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  counts: { flexDirection: 'row', gap: 8, padding: space.md },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    paddingHorizontal: space.md,
    marginBottom: 4,
  },
  countBox: {
    flex: 1,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    padding: 10,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.gray100,
  },
  countVal: { fontSize: 20, fontWeight: '800', color: colors.teal500 },
  countKey: { fontSize: 10, color: colors.gray600, marginTop: 4, textAlign: 'center' },
  err: { color: colors.danger, paddingHorizontal: space.md },
  empty: { textAlign: 'center', marginTop: 40, color: colors.gray600 },
  card: { marginBottom: 12 },
  urgentCard: {
    borderColor: colors.danger,
    borderWidth: 2,
    backgroundColor: colors.dangerBg,
  },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 6 },
  num: { fontWeight: '800', fontSize: 18, color: colors.gray900 },
  meta: { color: colors.gray600, marginVertical: 8 },
  obs: { color: colors.amber, fontSize: 12, marginBottom: 4, fontWeight: '600' },
  mod: { color: colors.gray500, fontSize: 12, marginBottom: 2 },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 8,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  itemName: { fontWeight: '600', marginBottom: 4, color: colors.gray900 },
  itemActions: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, maxWidth: 160, justifyContent: 'flex-end' },
  miniBtn: {
    backgroundColor: colors.amber,
    borderRadius: radius.md,
    paddingHorizontal: 10,
    minHeight: 40,
    justifyContent: 'center',
  },
  miniOk: { backgroundColor: colors.teal500 },
  miniDeliver: { backgroundColor: colors.gray800 },
  miniText: { color: '#fff', fontWeight: '700', fontSize: 11 },
});
