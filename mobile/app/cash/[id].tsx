import { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { CashSessionDetail } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import {
  AppText,
  Badge,
  Card,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

export default function CashSessionDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'cash.write');
  const [data, setData] = useState<CashSessionDetail | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState<number | null>(null);

  const load = useCallback(async () => {
    if (!id) return;
    setError(null);
    try {
      setData(await api.cashSessionDetail(Number(id)));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error sesión');
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

  const deleteMovement = (movementId: number) => {
    Alert.alert('Eliminar movimiento', '¿Confirmás eliminar este movimiento de caja?', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            setBusyId(movementId);
            setError(null);
            try {
              await api.deleteCashMovement(movementId);
              await load();
            } catch (e) {
              setError(e instanceof ApiError ? e.message : 'No se pudo eliminar');
            } finally {
              setBusyId(null);
            }
          })();
        },
      },
    ]);
  };

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  const session = data?.session as {
    status?: string;
    cash_register?: { name?: string };
    user?: { name?: string };
    initial_amount?: number;
    final_amount?: number | null;
    opened_at?: string;
  } | undefined;

  return (
    <View style={styles.root}>
      <PageHeader
        title={session?.cash_register?.name ?? `Sesión #${id}`}
        subtitle={session?.status}
        icon="cash"
      />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
      >
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {data ? (
          <>
            <Card>
              {session?.status ? <Badge label={session.status} /> : null}
              <AppText style={styles.meta}>Cajero: {session?.user?.name ?? '—'}</AppText>
              <AppText>Inicial ${Number(session?.initial_amount ?? 0).toFixed(2)}</AppText>
              {session?.final_amount != null ? (
                <AppText>Final ${Number(session.final_amount).toFixed(2)}</AppText>
              ) : null}
              <AppText weight="bold" style={styles.total}>
                Esperado ${Number(data.expected_amount).toFixed(2)}
              </AppText>
              <AppText style={styles.meta}>
                Ventas ${Number(data.sales_total).toFixed(2)} · Ing ${Number(data.ingresos).toFixed(2)} ·
                Egr ${Number(data.egresos).toFixed(2)}
              </AppText>
            </Card>

            <SectionLabel>Detalle de ventas</SectionLabel>
            {(data.sales_detail ?? []).map((order) => (
              <Card key={order.id} style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <AppText weight="bold">{order.number ?? `Pedido #${order.id}`}</AppText>
                  <AppText weight="bold">${Number(order.total).toFixed(2)}</AppText>
                </View>
                <AppText style={styles.meta}>
                  {order.table != null ? `Mesa ${order.table}` : 'Pedido rápido'}
                  {order.user ? ` · ${order.user}` : ''}
                </AppText>
                {order.items.map((item) => (
                  <AppText key={item.id} style={styles.itemLine}>
                    {item.quantity}× {item.product ?? 'Producto'} · $
                    {Number(item.subtotal).toFixed(2)}
                  </AppText>
                ))}
              </Card>
            ))}
            {(data.sales_detail ?? []).length === 0 ? (
              <AppText style={styles.meta}>Sin detalle de ventas</AppText>
            ) : null}

            <SectionLabel>Pagos</SectionLabel>
            {data.payments.map((p) => (
              <Card key={p.id} style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <Badge label={p.payment_method} />
                  <AppText weight="bold">${Number(p.amount).toFixed(2)}</AppText>
                </View>
                <AppText style={styles.meta}>
                  {p.order_number ?? '—'} · Mesa {p.table ?? '—'}
                </AppText>
              </Card>
            ))}
            {data.payments.length === 0 ? (
              <AppText style={styles.meta}>Sin pagos</AppText>
            ) : null}

            <SectionLabel>Movimientos</SectionLabel>
            {data.movements.map((m) => (
              <Card key={m.id} style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <Badge label={m.type} />
                  <AppText weight="bold">${Number(m.amount).toFixed(2)}</AppText>
                </View>
                <AppText style={styles.meta}>{m.description}</AppText>
                {m.reference ? <AppText style={styles.meta}>Ref: {m.reference}</AppText> : null}
                {canWrite && m.can_delete ? (
                  <PrimaryButton
                    title={busyId === m.id ? 'Eliminando…' : 'Eliminar'}
                    variant="danger"
                    onPress={() => deleteMovement(m.id)}
                    disabled={busyId === m.id}
                  />
                ) : null}
              </Card>
            ))}
            {data.movements.length === 0 ? (
              <AppText style={styles.meta}>Sin movimientos</AppText>
            ) : null}
          </>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, paddingBottom: 48, gap: 6 },
  err: { color: colors.danger },
  meta: { color: colors.gray500, fontSize: 13, marginTop: 4 },
  itemLine: { color: colors.gray700, fontSize: 13, marginTop: 4 },
  total: { fontSize: 22, color: colors.teal600, marginTop: 8 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
});
