import { useCallback, useState } from 'react';
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
import { Badge, Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

type BoardItem = { id: number; name: string; quantity: number; status: string };
type BoardOrder = {
  id: number;
  number: string;
  status: string;
  table?: string;
  waiter?: string;
  items?: BoardItem[];
};

export default function CocinaScreen() {
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'kitchen.write');
  const [orders, setOrders] = useState<BoardOrder[]>([]);
  const [counts, setCounts] = useState<Record<string, number>>({});
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
      <PageHeader title="Cocina" subtitle="Kanban en vivo" icon="flame" />
      <View style={styles.counts}>
        {Object.entries(counts).map(([k, v]) => (
          <View key={k} style={styles.countBox}>
            <Text style={styles.countVal}>{v}</Text>
            <Text style={styles.countKey}>{k.replace(/_/g, ' ')}</Text>
          </View>
        ))}
      </View>
      {error && <Text style={styles.err}>{error}</Text>}
      <FlatList
        data={orders}
        keyExtractor={(o) => String(o.id)}
        contentContainerStyle={{ padding: space.md }}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        ListEmptyComponent={<Text style={styles.empty}>Cocina libre</Text>}
        renderItem={({ item }) => (
          <Card style={{ marginBottom: 12 }}>
            <View style={styles.rowBetween}>
              <Text style={styles.num}>{item.number}</Text>
              <Badge label={item.status} />
            </View>
            <Text style={styles.meta}>
              Mesa {item.table ?? '—'} · {item.waiter ?? ''}
            </Text>
            {(item.items ?? []).map((it) => (
              <View key={it.id} style={styles.itemRow}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.itemName}>
                    {it.quantity}× {it.name}
                  </Text>
                  <Badge label={it.status} />
                </View>
                {canWrite && (
                  <View style={styles.itemActions}>
                    {it.status !== 'EN_PREPARACION' && it.status !== 'LISTO' && (
                      <Pressable
                        style={styles.miniBtn}
                        disabled={busyItem === it.id}
                        onPress={() => void setItem(it.id, 'EN_PREPARACION')}
                      >
                        <Text style={styles.miniText}>Prep</Text>
                      </Pressable>
                    )}
                    {it.status !== 'LISTO' && (
                      <Pressable
                        style={[styles.miniBtn, styles.miniOk]}
                        disabled={busyItem === it.id}
                        onPress={() => void setItem(it.id, 'LISTO')}
                      >
                        <Text style={styles.miniText}>Listo</Text>
                      </Pressable>
                    )}
                  </View>
                )}
              </View>
            ))}
            {canWrite && (item.items ?? []).some((i) => i.status !== 'LISTO') && (
              <PrimaryButton
                title="Marcar todo listo"
                onPress={() => {
                  void Promise.all(
                    (item.items ?? [])
                      .filter((i) => i.status !== 'LISTO')
                      .map((i) => setItem(i.id, 'LISTO')),
                  );
                }}
              />
            )}
          </Card>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  counts: { flexDirection: 'row', gap: 8, padding: space.md },
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
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  num: { fontWeight: '800', fontSize: 18, color: colors.gray900 },
  meta: { color: colors.gray600, marginVertical: 8 },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 8,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  itemName: { fontWeight: '600', marginBottom: 4, color: colors.gray900 },
  itemActions: { flexDirection: 'row', gap: 6 },
  miniBtn: {
    backgroundColor: colors.amber,
    borderRadius: radius.md,
    paddingHorizontal: 10,
    minHeight: 40,
    justifyContent: 'center',
  },
  miniOk: { backgroundColor: colors.teal500 },
  miniText: { color: '#fff', fontWeight: '700', fontSize: 12 },
});
