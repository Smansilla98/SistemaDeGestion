import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { OrderRow } from '../../src/api/types';
import { colors, radius, space } from '../../src/theme';
import { Badge, Card, Chip, PageHeader, PrimaryButton } from '../../src/ui/primitives';

const STATUS_FILTERS = [
  { key: 'ALL', label: 'Todos' },
  { key: 'ABIERTO', label: 'Abiertos' },
  { key: 'ENVIADO', label: 'Enviados' },
  { key: 'EN_PREPARACION', label: 'En prep' },
  { key: 'LISTO', label: 'Listos' },
  { key: 'ENTREGADO', label: 'Entregados' },
  { key: 'CERRADO', label: 'Cerrados' },
] as const;

function tableLabel(order: OrderRow): string {
  if (typeof order.table === 'string') return order.table;
  if (order.table && typeof order.table === 'object' && order.table.number) {
    return String(order.table.number);
  }
  return '';
}

export default function PedidosScreen() {
  const router = useRouter();
  const [orders, setOrders] = useState<OrderRow[]>([]);
  const [statusFilter, setStatusFilter] = useState<string>('ALL');
  const [search, setSearch] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      const status = statusFilter === 'ALL' ? undefined : statusFilter;
      const rows = await api.orders(status);
      setOrders(Array.isArray(rows) ? rows : []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error');
    } finally {
      setLoading(false);
    }
  }, [statusFilter]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

  const filtered = useMemo(() => {
    const needle = search.trim().toLowerCase();
    if (!needle) return orders;
    return orders.filter((o) => {
      const num = String(o.number ?? '').toLowerCase();
      const table = tableLabel(o).toLowerCase();
      return num.includes(needle) || table.includes(needle);
    });
  }, [orders, search]);

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
      <View style={styles.filters}>
        <TextInput
          style={styles.search}
          placeholder="Buscar nº o mesa…"
          placeholderTextColor={colors.gray500}
          value={search}
          onChangeText={setSearch}
        />
        <View style={styles.chips}>
          {STATUS_FILTERS.map((f) => (
            <Chip
              key={f.key}
              label={f.label}
              selected={statusFilter === f.key}
              onPress={() => setStatusFilter(f.key)}
            />
          ))}
        </View>
      </View>
      <FlatList
        data={filtered}
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
              {tableLabel(item) ? (
                <Text style={styles.meta}>Mesa {tableLabel(item)}</Text>
              ) : null}
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
  filters: { paddingHorizontal: space.md, gap: 8 },
  search: {
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.lg,
    paddingHorizontal: 14,
    paddingVertical: 10,
    color: colors.gray900,
  },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  num: { fontWeight: '800', fontSize: 16, color: colors.gray900 },
  meta: { marginTop: 4, color: colors.gray500, fontSize: 13 },
  total: { marginTop: 8, marginBottom: 8, color: colors.gray600, fontWeight: '600' },
});
