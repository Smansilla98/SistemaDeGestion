import { useCallback, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { OrderRow } from '../../src/api/types';
import { colors, space } from '../../src/theme';
import { Badge, Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

export default function OrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [order, setOrder] = useState<OrderRow | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    if (!id) return;
    setError(null);
    try {
      setOrder(await api.order(Number(id)));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

  const send = async () => {
    if (!order) return;
    try {
      await api.sendToKitchen(order.id);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo enviar');
    }
  };

  if (loading) return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;

  return (
    <ScrollView style={styles.root}>
      <PageHeader title={order?.number ?? 'Pedido'} subtitle="Detalle" icon="receipt" />
      <View style={{ padding: space.lg }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {error && <Text style={styles.err}>{error}</Text>}
        {order && (
          <Card style={{ marginTop: 12 }}>
            <Badge label={order.status} />
            <Text style={styles.total}>Total ${Number(order.total ?? 0).toFixed(2)}</Text>
            {(order.items ?? []).map((it) => (
              <View key={it.id} style={styles.item}>
                <Text style={styles.itemName}>
                  {it.quantity}× {it.name ?? it.product_name ?? 'Ítem'}
                </Text>
                {it.status ? <Badge label={it.status} /> : null}
              </View>
            ))}
            {order.status === 'ABIERTO' && (
              <PrimaryButton title="Enviar a cocina" onPress={() => void send()} />
            )}
          </Card>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  err: { color: colors.danger, marginTop: 8 },
  total: { fontSize: 20, fontWeight: '800', marginVertical: 12, color: colors.gray900 },
  item: {
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
    gap: 6,
  },
  itemName: { fontWeight: '600', color: colors.gray900 },
});
