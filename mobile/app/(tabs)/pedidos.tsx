import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { OrderRow } from '../../src/api/types';
import { colors } from '../../src/theme';

export default function PedidosScreen() {
  const [orders, setOrders] = useState<OrderRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      const rows = await api.orders();
      setOrders(Array.isArray(rows) ? rows : []);
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

  const send = async (id: number) => {
    try {
      await api.sendToKitchen(id);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo enviar');
    }
  };

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal700} />;
  }

  return (
    <View style={styles.root}>
      {error && <Text style={styles.err}>{error}</Text>}
      <FlatList
        data={orders}
        keyExtractor={(o) => String(o.id)}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        ListEmptyComponent={<Text style={styles.empty}>Sin pedidos</Text>}
        renderItem={({ item }) => (
          <View style={styles.card}>
            <Text style={styles.num}>{item.number}</Text>
            <Text style={styles.status}>{item.status}</Text>
            <Text style={styles.total}>${Number(item.total ?? 0).toFixed(2)}</Text>
            {item.status === 'ABIERTO' && (
              <Pressable style={styles.btn} onPress={() => void send(item.id)}>
                <Text style={styles.btnText}>Enviar cocina</Text>
              </Pressable>
            )}
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50, padding: 12 },
  err: { color: colors.danger, marginBottom: 8 },
  empty: { textAlign: 'center', color: colors.gray600, marginTop: 40 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.gray200,
  },
  num: { fontWeight: '800', fontSize: 16 },
  status: { color: colors.teal700, marginTop: 4 },
  total: { marginTop: 4, color: colors.gray600 },
  btn: {
    marginTop: 10,
    backgroundColor: colors.teal700,
    borderRadius: 10,
    minHeight: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnText: { color: '#fff', fontWeight: '700' },
});
