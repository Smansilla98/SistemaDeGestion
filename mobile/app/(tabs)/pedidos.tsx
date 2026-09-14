import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { OrderRow } from '../../src/api/types';
import { colors, space } from '../../src/theme';
import { Badge, Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

export default function PedidosScreen() {
  const router = useRouter();
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
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  return (
    <View style={styles.root}>
      <PageHeader title="Pedidos" subtitle="Lista operativa" icon="receipt" />
      {error && <Text style={styles.err}>{error}</Text>}
      <FlatList
        data={orders}
        keyExtractor={(o) => String(o.id)}
        contentContainerStyle={{ padding: space.md }}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        ListEmptyComponent={<Text style={styles.empty}>Sin pedidos</Text>}
        renderItem={({ item }) => (
          <Pressable onPress={() => router.push(`/order/${item.id}` as never)}>
            <Card style={{ marginBottom: 10 }}>
              <View style={styles.row}>
                <Text style={styles.num}>{item.number}</Text>
                <Badge label={item.status} />
              </View>
              <Text style={styles.total}>${Number(item.total ?? 0).toFixed(2)}</Text>
              {item.status === 'ABIERTO' && (
                <PrimaryButton title="Enviar cocina" onPress={() => void send(item.id)} />
              )}
            </Card>
          </Pressable>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  err: { color: colors.danger, margin: 12 },
  empty: { textAlign: 'center', color: colors.gray600, marginTop: 40 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  num: { fontWeight: '800', fontSize: 16, color: colors.gray900 },
  total: { marginTop: 8, marginBottom: 8, color: colors.gray600, fontWeight: '600' },
});
