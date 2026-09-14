import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { colors } from '../../src/theme';

type BoardOrder = {
  id: number;
  number: string;
  status: string;
  table?: string;
  waiter?: string;
  items_count?: number;
};

export default function CocinaScreen() {
  const [orders, setOrders] = useState<BoardOrder[]>([]);
  const [counts, setCounts] = useState<Record<string, number>>({});
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

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
      const t = setInterval(() => void load(), 12000);
      return () => clearInterval(t);
    }, [load]),
  );

  if (loading) return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal700} />;

  return (
    <View style={styles.root}>
      <View style={styles.counts}>
        {Object.entries(counts).map(([k, v]) => (
          <View key={k} style={styles.countBox}>
            <Text style={styles.countVal}>{v}</Text>
            <Text style={styles.countKey}>{k.replace('_', ' ')}</Text>
          </View>
        ))}
      </View>
      {error && <Text style={styles.err}>{error}</Text>}
      <FlatList
        data={orders}
        keyExtractor={(o) => String(o.id)}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        ListEmptyComponent={<Text style={styles.empty}>Cocina libre</Text>}
        renderItem={({ item }) => (
          <View style={styles.card}>
            <Text style={styles.num}>{item.number}</Text>
            <Text>Mesa {item.table ?? '—'} · {item.status}</Text>
            <Text style={styles.meta}>{item.waiter} · {item.items_count ?? 0} ítems</Text>
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50, padding: 12 },
  counts: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  countBox: {
    flex: 1,
    backgroundColor: colors.white,
    borderRadius: 12,
    padding: 10,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.gray200,
  },
  countVal: { fontSize: 20, fontWeight: '800', color: colors.teal700 },
  countKey: { fontSize: 10, color: colors.gray600, marginTop: 4, textAlign: 'center' },
  err: { color: colors.danger },
  empty: { textAlign: 'center', marginTop: 40, color: colors.gray600 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.gray200,
  },
  num: { fontWeight: '800', fontSize: 16, marginBottom: 4 },
  meta: { color: colors.gray600, marginTop: 4 },
});
