import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { DashboardPayload } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { canSeeAdminHub, hasPermission } from '../../src/auth/permissions';
import { flushOfflineQueue, getQueueSize } from '../../src/offline/queue';
import { colors, radius, space } from '../../src/theme';
import {
  Amount,
  AppText,
  Badge,
  Card,
  PageHeader,
  PrimaryButton,
  SectionLabel,
  StatTile,
} from '../../src/ui/primitives';

type QuickAction = {
  key: string;
  title: string;
  icon: keyof typeof Ionicons.glyphMap;
  href: Href;
  show: boolean;
  variant?: 'primary' | 'ghost' | 'amber';
};

function ActionGrid({ actions }: { actions: QuickAction[] }) {
  const router = useRouter();
  const visible = actions.filter((a) => a.show);
  return (
    <View style={styles.actionGrid}>
      {visible.map((a) => (
        <Pressable
          key={a.key}
          style={styles.actionBtn}
          onPress={() => router.push(a.href)}
        >
          <View style={styles.actionIcon}>
            <Ionicons name={a.icon} size={20} color={colors.teal500} />
          </View>
          <AppText weight="semibold" style={styles.actionLabel} numberOfLines={2}>
            {a.title}
          </AppText>
        </Pressable>
      ))}
    </View>
  );
}

export default function DashboardScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const [data, setData] = useState<DashboardPayload | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [queueSize, setQueueSize] = useState(0);

  const role = user?.role ?? '';
  const isAdmin = role === 'ADMIN';
  const isSuper = role === 'SUPERADMIN';
  const isManagerLayer = isAdmin || isSuper || role === 'GERENTE';

  const load = useCallback(async () => {
    setError(null);
    try {
      setData(await api.dashboard());
      await flushOfflineQueue().catch(() => null);
      setQueueSize(await getQueueSize());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error dashboard');
      setQueueSize(await getQueueSize().catch(() => 0));
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

  const ops = data?.operational;
  const mgmt = data?.management;
  const insights = data?.insights;

  const adminActions: QuickAction[] = [
    {
      key: 'admin',
      title: 'Admin',
      icon: 'settings',
      href: '/admin' as Href,
      show: canSeeAdminHub(user),
    },
    {
      key: 'product-new',
      title: 'Nuevo producto',
      icon: 'add-circle',
      href: '/products/new' as Href,
      show: isAdmin || isSuper,
    },
    {
      key: 'products',
      title: 'Productos',
      icon: 'pricetags',
      href: '/products' as Href,
      show: hasPermission(user, 'products.read') && isManagerLayer,
    },
    {
      key: 'stock',
      title: 'Ver stock',
      icon: 'cube',
      href: '/(tabs)/stock' as Href,
      show: hasPermission(user, 'stock.read'),
    },
    {
      key: 'cash',
      title: 'Gestionar cajas',
      icon: 'cash',
      href: '/(tabs)/caja' as Href,
      show: hasPermission(user, 'cash.read'),
    },
    {
      key: 'users',
      title: 'Usuarios',
      icon: 'people',
      href: '/users' as Href,
      show: hasPermission(user, 'users.read'),
    },
    {
      key: 'mesas',
      title: 'Mesas',
      icon: 'grid',
      href: '/(tabs)/mesas' as Href,
      show: hasPermission(user, 'tables.read'),
    },
    {
      key: 'pedidos',
      title: 'Pedidos',
      icon: 'receipt',
      href: '/(tabs)/pedidos' as Href,
      show: hasPermission(user, 'orders.read'),
    },
    {
      key: 'cocina',
      title: 'Cocina',
      icon: 'flame',
      href: '/(tabs)/cocina' as Href,
      show: hasPermission(user, 'kitchen.read'),
    },
    {
      key: 'pedido',
      title: 'Nuevo pedido',
      icon: 'cart',
      href: '/(tabs)/pedido' as Href,
      show: hasPermission(user, 'orders.write'),
    },
  ];

  return (
    <View style={styles.root}>
      <PageHeader
        title="Conurbania"
        subtitle={`${user?.name ?? ''} · ${user?.role ?? ''}`}
        bi="house-door"
      />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
      >
        {queueSize > 0 ? (
          <Pressable
            style={styles.offlineBanner}
            onPress={() => {
              void (async () => {
                await flushOfflineQueue().catch(() => null);
                setQueueSize(await getQueueSize());
              })();
            }}
          >
            <Ionicons name="cloud-offline-outline" size={16} color="#92400e" />
            <AppText weight="medium" style={styles.offlineText}>
              {queueSize} acción{queueSize === 1 ? '' : 'es'} pendiente
              {queueSize === 1 ? '' : 's'} sin conexión · Tocá para reintentar
            </AppText>
          </Pressable>
        ) : null}

        {error && (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        )}
        {loading && <ActivityIndicator color={colors.teal500} />}

        {/* ADMIN / SUPERADMIN: cards como PWA mobile */}
        {isManagerLayer && mgmt && (
          <>
            <Pressable onPress={() => router.push('/(tabs)/stock' as Href)}>
              <Card
                style={[
                  styles.wideCard,
                  mgmt.low_stock_products > 0 ? styles.cardAmber : styles.cardTeal,
                ]}
              >
                <AppText weight="semibold" style={styles.cardLabel}>
                  Stock bajo
                </AppText>
                <AppText weight="bold" style={styles.cardValue}>
                  {mgmt.low_stock_products}{' '}
                  <AppText weight="medium" style={styles.cardSmall}>
                    / {mgmt.stock_ok_products} ok
                  </AppText>
                </AppText>
                <AppText style={styles.cardSub}>
                  {mgmt.low_stock_products > 0
                    ? 'Hay productos por debajo del mínimo'
                    : 'Sin alertas de stock bajo'}
                </AppText>
              </Card>
            </Pressable>

            <Pressable onPress={() => router.push('/(tabs)/caja' as Href)}>
              <Card style={[styles.wideCard, styles.cardTeal]}>
                <AppText weight="semibold" style={styles.cardLabel}>
                  Cajas abiertas ahora
                </AppText>
                <AppText weight="bold" style={styles.cardValue}>
                  {mgmt.open_cash_sessions}
                </AppText>
                <AppText style={styles.cardSub}>
                  {(mgmt.open_cash_session_labels ?? []).length
                    ? (mgmt.open_cash_session_labels ?? []).join(' · ')
                    : 'Ninguna sesión abierta'}
                </AppText>
              </Card>
            </Pressable>

            <Card style={[styles.wideCard, styles.cardTeal]}>
              <AppText weight="semibold" style={styles.cardLabel}>
                Ventas del día
              </AppText>
              <Amount value={mgmt.ventas_hoy} style={styles.cardValueLg} />
              <AppText style={styles.cardSub}>
                {mgmt.tiene_sesion_abierta
                  ? `Sesión: $${Number(mgmt.ventas_sesion).toFixed(0)}`
                  : `${insights?.today_orders ?? 0} pedidos hoy`}
              </AppText>
            </Card>

            <SectionLabel>Acciones rápidas</SectionLabel>
            <ActionGrid actions={adminActions} />
          </>
        )}

        {/* Operativo siempre visible debajo / o solo si no manager */}
        {ops && !isManagerLayer && (
          <View style={styles.grid}>
            <StatTile label="Mesas libres" value={ops.mesas_libres} accent={colors.green} bi="table" />
            <StatTile label="Mesas ocupadas" value={ops.mesas_ocupadas} accent={colors.amber} bi="people" />
            <StatTile label="Pedidos activos" value={ops.pedidos_pendientes} bi="receipt" />
            <StatTile
              label="Ventas sesión"
              value={`$${Number(ops.ventas_sesion).toFixed(0)}`}
              bi="cash-coin"
              mono
            />
          </View>
        )}

        {ops && isManagerLayer && (
          <>
            <SectionLabel>Operación en vivo</SectionLabel>
            <View style={styles.grid}>
              <StatTile label="Mesas libres" value={ops.mesas_libres} accent={colors.green} bi="table" />
              <StatTile label="Ocupadas" value={ops.mesas_ocupadas} accent={colors.amber} bi="people" />
              <StatTile label="Pedidos activos" value={ops.pedidos_pendientes} bi="receipt" />
              <StatTile
                label="Pedidos hoy"
                value={insights?.today_orders ?? 0}
                bi="calendar"
              />
            </View>
          </>
        )}

        {insights && isManagerLayer && (
          <>
            {(insights.recent_orders?.length ?? 0) > 0 && (
              <Card style={{ marginTop: space.md }}>
                <View style={styles.rowBetween}>
                  <AppText weight="bold" style={styles.section}>
                    Pedidos recientes
                  </AppText>
                  <Pressable onPress={() => router.push('/(tabs)/pedidos' as Href)}>
                    <AppText weight="semibold" style={{ color: colors.teal600 }}>
                      Ver todos
                    </AppText>
                  </Pressable>
                </View>
                {insights.recent_orders.slice(0, 5).map((o) => (
                  <Pressable
                    key={o.id}
                    style={styles.listRow}
                    onPress={() => router.push(`/order/${o.id}` as Href)}
                  >
                    <View style={{ flex: 1 }}>
                      <AppText weight="semibold">{o.number}</AppText>
                      <AppText style={styles.meta}>
                        Mesa {o.table ?? '—'} · {o.waiter ?? ''}
                      </AppText>
                    </View>
                    <Badge label={o.status} />
                  </Pressable>
                ))}
              </Card>
            )}

            {(insights.active_tables?.length ?? 0) > 0 && (
              <Card style={{ marginTop: space.md }}>
                <View style={styles.rowBetween}>
                  <AppText weight="bold" style={styles.section}>
                    Mesas activas
                  </AppText>
                  <Pressable onPress={() => router.push('/(tabs)/mesas' as Href)}>
                    <AppText weight="semibold" style={{ color: colors.teal600 }}>
                      Ver todas
                    </AppText>
                  </Pressable>
                </View>
                <View style={styles.chipWrap}>
                  {insights.active_tables.map((t) => (
                    <View key={t.id} style={styles.tableChip}>
                      <AppText weight="bold">{t.number}</AppText>
                      {t.waiter ? (
                        <AppText style={styles.meta} numberOfLines={1}>
                          {t.waiter}
                        </AppText>
                      ) : null}
                    </View>
                  ))}
                </View>
              </Card>
            )}

            {(insights.top_products?.length ?? 0) > 0 && (
              <Card style={{ marginTop: space.md }}>
                <AppText weight="bold" style={styles.section}>
                  Top productos hoy
                </AppText>
                {insights.top_products.map((p) => (
                  <View key={p.name} style={styles.listRow}>
                    <AppText weight="medium" style={{ flex: 1 }}>
                      {p.name}
                    </AppText>
                    <AppText weight="bold" style={{ color: colors.teal600 }}>
                      ×{p.total_quantity}
                    </AppText>
                  </View>
                ))}
              </Card>
            )}

            {(insights.sales_by_waiter?.length ?? 0) > 0 && (
              <Card style={{ marginTop: space.md }}>
                <AppText weight="bold" style={styles.section}>
                  Ventas por mozo
                </AppText>
                {insights.sales_by_waiter.map((w) => (
                  <View key={w.name} style={styles.listRow}>
                    <AppText weight="medium" style={{ flex: 1 }}>
                      {w.name}
                    </AppText>
                    <Amount value={w.total_sales} />
                  </View>
                ))}
              </Card>
            )}

            {(insights.income_by_method?.length ?? 0) > 0 && (
              <Card style={{ marginTop: space.md }}>
                <AppText weight="bold" style={styles.section}>
                  Ingresos por método
                </AppText>
                {insights.income_by_method.map((m) => (
                  <View key={m.payment_method} style={styles.listRow}>
                    <Badge label={m.payment_method} />
                    <Amount value={m.total} />
                  </View>
                ))}
              </Card>
            )}

            {(insights.low_stock_list?.length ?? 0) > 0 && (
              <Card style={{ marginTop: space.md }}>
                <View style={styles.rowBetween}>
                  <AppText weight="bold" style={styles.section}>
                    Stock bajo
                  </AppText>
                  <Pressable onPress={() => router.push('/(tabs)/stock' as Href)}>
                    <AppText weight="semibold" style={{ color: colors.amber }}>
                      Ir a stock
                    </AppText>
                  </Pressable>
                </View>
                {insights.low_stock_list.map((p) => (
                  <View key={p.id} style={styles.listRow}>
                    <AppText weight="medium" style={{ flex: 1 }}>
                      {p.name}
                    </AppText>
                    <AppText style={{ color: colors.amber }}>
                      {p.current_stock}/{p.stock_minimum}
                    </AppText>
                  </View>
                ))}
              </Card>
            )}

            {(mgmt?.recent_stock_movements?.length ?? 0) > 0 && (
              <Card style={{ marginTop: space.md }}>
                <AppText weight="bold" style={styles.section}>
                  Últimos movimientos
                </AppText>
                {(mgmt?.recent_stock_movements ?? []).slice(0, 5).map((m) => (
                  <AppText key={m.id} style={styles.mov}>
                    {m.type} · {m.product} · x{m.quantity}
                  </AppText>
                ))}
              </Card>
            )}
          </>
        )}

        {!isManagerLayer && (
          <View style={styles.actions}>
            {hasPermission(user, 'tables.read') && (
              <PrimaryButton
                title="Ir a mesas"
                icon="grid"
                onPress={() => router.push('/(tabs)/mesas' as const)}
              />
            )}
            {hasPermission(user, 'stock.read') && (
              <PrimaryButton
                title="Stock"
                icon="cube-outline"
                variant="ghost"
                onPress={() => router.push('/(tabs)/stock' as never)}
              />
            )}
            {hasPermission(user, 'cash.read') && (
              <PrimaryButton
                title="Caja"
                icon="cash-outline"
                variant="ghost"
                onPress={() => router.push('/(tabs)/caja' as const)}
              />
            )}
          </View>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  body: { padding: space.lg, paddingBottom: 48 },
  topRow: { flexDirection: 'row', justifyContent: 'flex-end', marginBottom: 8 },
  logout: { color: colors.teal600 },
  offlineBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.amberBg,
    borderRadius: radius.md,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#fde68a',
  },
  offlineText: { color: '#92400e', fontSize: 13, flex: 1 },
  err: { color: colors.danger, marginBottom: 8 },
  grid: { flexDirection: 'column', gap: 10 },
  section: {
    fontSize: 12,
    color: colors.gray500,
    textTransform: 'uppercase',
    marginBottom: 8,
  },
  wideCard: { marginBottom: 10 },
  cardTeal: { borderColor: colors.teal100, backgroundColor: colors.teal50 },
  cardAmber: { borderColor: colors.amberBg, backgroundColor: colors.amberBg },
  cardLabel: { color: colors.gray600, fontSize: 12, textTransform: 'uppercase' },
  cardValue: { fontSize: 28, color: colors.gray900, marginTop: 4 },
  cardValueLg: { fontSize: 32, color: colors.teal600, marginTop: 4 },
  cardSmall: { fontSize: 14, color: colors.gray500 },
  cardSub: { color: colors.gray600, marginTop: 4, fontSize: 13 },
  actionGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  actionBtn: {
    width: '30%',
    minWidth: 100,
    minHeight: 92,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
    padding: 10,
    alignItems: 'flex-start',
    justifyContent: 'space-between',
  },
  actionIcon: {
    width: 34,
    height: 34,
    borderRadius: radius.md,
    backgroundColor: colors.teal50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionLabel: { fontSize: 12, color: colors.gray800 },
  rowBetween: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  listRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 8,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  meta: { color: colors.gray500, fontSize: 12, marginTop: 2 },
  chipWrap: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  tableChip: {
    minWidth: 72,
    padding: 10,
    borderRadius: radius.md,
    backgroundColor: colors.amberBg,
    borderWidth: 1,
    borderColor: '#fde68a',
  },
  mov: { color: colors.gray600, fontSize: 12, marginTop: 4 },
  actions: { marginTop: space.lg, gap: 10 },
});
