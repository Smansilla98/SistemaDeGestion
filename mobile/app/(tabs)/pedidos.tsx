import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { OrderRow } from '../../src/api/types';
import { colors, radius, space } from '../../src/theme';
import { DataTable, type DataColumn } from '../../src/ui/DataTable';
import { Amount, Badge, Chip, PageHeader, PrimaryButton, AppText } from '../../src/ui/primitives';

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
  return '—';
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

  const columns: DataColumn<OrderRow>[] = useMemo(
    () => [
      {
        key: 'number',
        title: 'Nº',
        flex: 1.1,
        bold: true,
        mono: true,
        render: (o) => String(o.number ?? o.id),
      },
      {
        key: 'mesa',
        title: 'Mesa',
        flex: 0.8,
        mono: true,
        render: (o) => tableLabel(o),
      },
      {
        key: 'status',
        title: 'Estado',
        flex: 1.4,
        render: (o) => <Badge label={o.status} />,
      },
      {
        key: 'total',
        title: 'Total',
        flex: 1,
        align: 'right',
        render: (o) => <Amount value={o.total ?? 0} />,
      },
      {
        key: 'act',
        title: '',
        flex: 1.2,
        render: (o) =>
          o.status === 'ABIERTO' ? (
            <PrimaryButton title="Cocina" variant="ghost" onPress={() => void send(o.id)} />
          ) : null,
      },
    ],
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [orders],
  );

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  return (
    <View style={styles.root}>
      <PageHeader title="Pedidos" subtitle="Lista operativa" bi="receipt" />
      {error ? (
        <AppText weight="medium" style={{ color: colors.danger, marginHorizontal: space.md }}>
          {error}
        </AppText>
      ) : null}
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
      >
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
        <DataTable
          columns={columns}
          rows={filtered}
          keyExtractor={(o) => o.id}
          onPressRow={(o) => router.push(`/order/${o.id}` as never)}
          emptyText="Sin pedidos"
        />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  body: { padding: space.md, gap: 10, paddingBottom: 40 },
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
});
