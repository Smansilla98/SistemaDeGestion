import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { CashSessionDetail } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { formatDateDMY } from '../../src/ui/formatDate';
import { AppText, PrimaryButton } from '../../src/ui/primitives';
import {
  FadeIn,
  FxHeader,
  HeroMetric,
  OrderSkeleton,
  StatusDot,
  Surface,
} from '../../src/ui/fintech';

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

  const session = data?.session as {
    status?: string;
    cash_register?: { name?: string };
    user?: { name?: string };
    initial_amount?: number;
    final_amount?: number | null;
    opened_at?: string;
  } | undefined;

  if (loading && !data) {
    return (
      <View style={styles.root}>
        <FxHeader title="Sesión" onBack={() => router.back()} />
        <OrderSkeleton />
      </View>
    );
  }

  const orders = data?.sales_detail ?? [];

  return (
    <View style={styles.root}>
      <FxHeader
        title={session?.cash_register?.name ?? `Sesión #${id}`}
        subtitle={formatDateDMY(session?.opened_at)}
        onBack={() => router.back()}
        right={session?.status ? <StatusDot status={session.status} /> : null}
      />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        showsVerticalScrollIndicator={false}
      >
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {data ? (
          <FadeIn>
            <View style={styles.content}>
              <Surface style={styles.heroPad}>
                <HeroMetric
                  label="Total esperado"
                  value={`$${Number(data.expected_amount).toFixed(2)}`}
                  hint={`Ventas $${Number(data.sales_total).toFixed(0)} · Ing $${Number(data.ingresos).toFixed(0)} · Egr $${Number(data.egresos).toFixed(0)}`}
                  mono
                />
                <AppText style={styles.meta}>
                  Cajero: {session?.user?.name ?? '—'}
                  {' · '}Ini ${Number(session?.initial_amount ?? 0).toFixed(0)}
                  {session?.final_amount != null
                    ? ` · Fin $${Number(session.final_amount).toFixed(0)}`
                    : ''}
                </AppText>
              </Surface>

              <AppText weight="semibold" style={styles.section}>
                Órdenes de la sesión
              </AppText>
              <Surface padded={false}>
                {orders.map((order, idx) => (
                  <Pressable
                    key={order.id}
                    onPress={() => router.push(`/order/${order.id}` as Href)}
                    style={[styles.listRow, idx > 0 && styles.hairline]}
                  >
                    <View style={{ flex: 1, gap: 4 }}>
                      <AppText weight="semibold" style={styles.listTitle}>
                        {order.number ?? `Pedido #${order.id}`}
                      </AppText>
                      <AppText style={styles.meta}>
                        {order.table != null ? `Mesa ${order.table}` : 'Pedido rápido'}
                        {order.user ? ` · ${order.user}` : ''}
                        {order.created_at ? ` · ${formatDateDMY(order.created_at)}` : ''}
                      </AppText>
                      {order.items.slice(0, 3).map((item) => (
                        <AppText key={item.id} style={styles.itemLine}>
                          {item.quantity}× {item.product ?? 'Producto'}
                        </AppText>
                      ))}
                      {order.items.length > 3 ? (
                        <AppText style={styles.meta}>+{order.items.length - 3} más</AppText>
                      ) : null}
                    </View>
                    <View style={styles.listRight}>
                      <AppText weight="bold" style={styles.amount}>
                        ${Number(order.total).toFixed(2)}
                      </AppText>
                      <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} />
                    </View>
                  </Pressable>
                ))}
                {orders.length === 0 ? (
                  <AppText style={[styles.meta, { padding: fx.space.md }]}>
                    Sin órdenes en esta sesión
                  </AppText>
                ) : null}
              </Surface>

              <AppText weight="semibold" style={styles.section}>
                Pagos
              </AppText>
              <Surface padded={false}>
                {data.payments.map((p, idx) => (
                  <View key={p.id} style={[styles.listRow, idx > 0 && styles.hairline]}>
                    <View style={{ flex: 1, gap: 4 }}>
                      <StatusDot status={p.payment_method} size="sm" />
                      <AppText style={styles.meta}>
                        {p.order_number ?? '—'} · Mesa {p.table ?? '—'}
                      </AppText>
                    </View>
                    <AppText weight="bold" style={styles.amount}>
                      ${Number(p.amount).toFixed(2)}
                    </AppText>
                  </View>
                ))}
                {data.payments.length === 0 ? (
                  <AppText style={[styles.meta, { padding: fx.space.md }]}>Sin pagos</AppText>
                ) : null}
              </Surface>

              <AppText weight="semibold" style={styles.section}>
                Movimientos
              </AppText>
              <Surface padded={false}>
                {data.movements.map((m, idx) => (
                  <View key={m.id} style={[styles.listRow, idx > 0 && styles.hairline]}>
                    <View style={{ flex: 1, gap: 4 }}>
                      <StatusDot status={m.type} size="sm" />
                      <AppText style={styles.meta}>{m.description}</AppText>
                      {m.reference ? (
                        <AppText style={styles.meta}>Ref: {m.reference}</AppText>
                      ) : null}
                      {canWrite && m.can_delete ? (
                        <PrimaryButton
                          title={busyId === m.id ? 'Eliminando…' : 'Eliminar'}
                          variant="danger"
                          onPress={() => deleteMovement(m.id)}
                          disabled={busyId === m.id}
                        />
                      ) : null}
                    </View>
                    <AppText weight="bold" style={styles.amount}>
                      ${Number(m.amount).toFixed(2)}
                    </AppText>
                  </View>
                ))}
                {data.movements.length === 0 ? (
                  <AppText style={[styles.meta, { padding: fx.space.md }]}>
                    Sin movimientos
                  </AppText>
                ) : null}
              </Surface>
            </View>
          </FadeIn>
        ) : (
          <ActivityIndicator color={fx.brand} />
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  body: { paddingHorizontal: fx.space.md, paddingBottom: 56 },
  content: { gap: 20 },
  err: { color: fx.danger, marginBottom: 8 },
  heroPad: { paddingVertical: 8 },
  meta: { color: fx.inkFaint, fontSize: 13 },
  itemLine: { color: fx.inkMuted, fontSize: 13 },
  section: { fontSize: 13, color: fx.inkMuted, marginBottom: -8 },
  listRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    paddingHorizontal: fx.space.md,
    paddingVertical: 16,
  },
  listRight: { alignItems: 'flex-end', gap: 6 },
  listTitle: { fontSize: 16, color: fx.ink },
  amount: { fontSize: 16, color: fx.ink },
  hairline: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: fx.hairline,
  },
});
