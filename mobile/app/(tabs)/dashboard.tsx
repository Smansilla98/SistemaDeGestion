import { useCallback, useState } from 'react';
import {
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
import { fx } from '../../src/theme';
import { AppText, PrimaryButton } from '../../src/ui/primitives';
import {
  DashboardSkeleton,
  FadeIn,
  FxHeader,
  HeroMetric,
  StatusDot,
  Surface,
} from '../../src/ui/fintech';

type QuickAction = {
  key: string;
  title: string;
  icon: keyof typeof Ionicons.glyphMap;
  href: Href;
  show: boolean;
};

function ActionRow({ actions }: { actions: QuickAction[] }) {
  const router = useRouter();
  const visible = actions.filter((a) => a.show);
  return (
    <View style={styles.actionRow}>
      {visible.map((a) => (
        <Pressable
          key={a.key}
          style={({ pressed }) => [styles.actionBtn, pressed && { opacity: 0.85 }]}
          onPress={() => router.push(a.href)}
        >
          <View style={styles.actionIcon}>
            <Ionicons name={a.icon} size={24} color={fx.brand} />
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
      icon: 'settings-outline',
      href: '/admin' as Href,
      show: canSeeAdminHub(user),
    },
    {
      key: 'products',
      title: 'Productos',
      icon: 'pricetag-outline',
      href: '/products' as Href,
      show: hasPermission(user, 'products.read') && isManagerLayer,
    },
    {
      key: 'cash',
      title: 'Caja',
      icon: 'wallet-outline',
      href: '/(tabs)/caja' as Href,
      show: hasPermission(user, 'cash.read'),
    },
    {
      key: 'mesas',
      title: 'Mesas',
      icon: 'grid-outline',
      href: '/(tabs)/mesas' as Href,
      show: hasPermission(user, 'tables.read'),
    },
    {
      key: 'pedido',
      title: 'Nuevo',
      icon: 'add-outline',
      href: '/(tabs)/pedido' as Href,
      show: hasPermission(user, 'orders.write'),
    },
    {
      key: 'pedidos',
      title: 'Pedidos',
      icon: 'receipt-outline',
      href: '/(tabs)/pedidos' as Href,
      show: hasPermission(user, 'orders.read'),
    },
    {
      key: 'cocina',
      title: 'Cocina',
      icon: 'restaurant-outline',
      href: '/(tabs)/cocina' as Href,
      show: hasPermission(user, 'kitchen.read'),
    },
  ];

  const heroValue = isManagerLayer
    ? `$${Number(mgmt?.ventas_hoy ?? 0).toFixed(0)}`
    : `$${Number(ops?.ventas_sesion ?? 0).toFixed(0)}`;
  const heroHint = isManagerLayer
    ? mgmt?.tiene_sesion_abierta
      ? `Sesión · $${Number(mgmt.ventas_sesion).toFixed(0)}`
      : `${insights?.today_orders ?? 0} pedidos hoy`
    : 'Ventas de la sesión';

  return (
    <View style={styles.root}>
      <FxHeader
        title="Hola"
        subtitle={user?.name ?? user?.username ?? undefined}
      />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        showsVerticalScrollIndicator={false}
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
            <Ionicons name="cloud-offline-outline" size={16} color={fx.warning} />
            <AppText weight="medium" style={styles.offlineText}>
              {queueSize} pendiente{queueSize === 1 ? '' : 's'} · Reintentar
            </AppText>
          </Pressable>
        ) : null}

        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {loading && !data ? (
          <DashboardSkeleton />
        ) : (
          <FadeIn>
            <View style={styles.content}>
            <Surface style={styles.heroCard}>
              <HeroMetric
                label={isManagerLayer ? 'Ventas del día' : 'Ventas sesión'}
                value={heroValue}
                hint={heroHint}
                mono
              />
            </Surface>

            {ops ? (
              <View style={styles.metricsRow}>
                <Surface style={styles.metricHalf}>
                  <HeroMetric
                    label="Libres"
                    value={ops.mesas_libres}
                    hint="Mesas"
                    style={{ fontSize: 28, lineHeight: 32 }}
                  />
                </Surface>
                <Surface style={styles.metricHalf}>
                  <HeroMetric
                    label="Ocupadas"
                    value={ops.mesas_ocupadas}
                    hint="En salón"
                    style={{ fontSize: 28, lineHeight: 32 }}
                  />
                </Surface>
              </View>
            ) : null}

            {ops ? (
              <Surface style={styles.statsCard}>
                <View style={styles.inlineStat}>
                  <AppText style={styles.inlineLabel}>Pedidos activos</AppText>
                  <AppText weight="bold" style={styles.inlineValue}>
                    {ops.pedidos_pendientes}
                  </AppText>
                </View>
                {isManagerLayer && mgmt ? (
                  <View style={[styles.inlineStat, styles.inlineBorder]}>
                    <AppText style={styles.inlineLabel}>Cajas abiertas</AppText>
                    <AppText weight="bold" style={styles.inlineValue}>
                      {mgmt.open_cash_sessions}
                    </AppText>
                  </View>
                ) : null}
              </Surface>
            ) : null}

            <AppText weight="semibold" style={styles.section}>
              Accesos
            </AppText>
            <ActionRow
              actions={
                isManagerLayer
                  ? adminActions
                  : [
                      {
                        key: 'mesas',
                        title: 'Mesas',
                        icon: 'grid-outline',
                        href: '/(tabs)/mesas' as Href,
                        show: hasPermission(user, 'tables.read'),
                      },
                      {
                        key: 'cash',
                        title: 'Caja',
                        icon: 'wallet-outline',
                        href: '/(tabs)/caja' as Href,
                        show: hasPermission(user, 'cash.read'),
                      },
                      {
                        key: 'pedido',
                        title: 'Nuevo',
                        icon: 'add-outline',
                        href: '/(tabs)/pedido' as Href,
                        show: hasPermission(user, 'orders.write'),
                      },
                      {
                        key: 'pedidos',
                        title: 'Pedidos',
                        icon: 'receipt-outline',
                        href: '/(tabs)/pedidos' as Href,
                        show: hasPermission(user, 'orders.read'),
                      },
                      {
                        key: 'cocina',
                        title: 'Cocina',
                        icon: 'restaurant-outline',
                        href: '/(tabs)/cocina' as Href,
                        show: hasPermission(user, 'kitchen.read'),
                      },
                    ]
              }
            />

            {insights && isManagerLayer && (insights.recent_orders?.length ?? 0) > 0 ? (
              <Surface padded={false} style={styles.blockCard}>
                <View style={styles.listHead}>
                  <AppText weight="semibold" style={styles.sectionIn}>
                    Pedidos recientes
                  </AppText>
                  <Pressable onPress={() => router.push('/(tabs)/pedidos' as Href)} hitSlop={12}>
                    <AppText weight="medium" style={styles.link}>
                      Ver todos
                    </AppText>
                  </Pressable>
                </View>
                {insights.recent_orders.slice(0, 5).map((o, idx) => (
                  <Pressable
                    key={o.id}
                    style={[styles.listRow, idx > 0 && styles.listHairline]}
                    onPress={() => router.push(`/order/${o.id}` as Href)}
                  >
                    <View style={{ flex: 1 }}>
                      <AppText weight="semibold" style={styles.listTitle}>
                        {o.number}
                      </AppText>
                      <AppText style={styles.meta}>
                        Mesa {o.table ?? '—'}
                        {o.waiter ? ` · ${o.waiter}` : ''}
                      </AppText>
                    </View>
                    <StatusDot status={o.status} size="sm" />
                  </Pressable>
                ))}
              </Surface>
            ) : null}

            {insights && isManagerLayer && (insights.sales_by_waiter?.length ?? 0) > 0 ? (
              <Surface padded={false} style={styles.blockCard}>
                <View style={styles.listHead}>
                  <AppText weight="semibold" style={styles.sectionIn}>
                    Ventas por mozo
                  </AppText>
                </View>
                {insights.sales_by_waiter.map((w, idx) => (
                  <View
                    key={w.name}
                    style={[styles.listRow, idx > 0 && styles.listHairline]}
                  >
                    <AppText weight="medium" style={{ flex: 1, color: fx.ink }}>
                      {w.name}
                    </AppText>
                    <AppText weight="bold" style={styles.amount}>
                      ${Number(w.total_sales).toFixed(0)}
                    </AppText>
                  </View>
                ))}
              </Surface>
            ) : null}

            {!isManagerLayer ? (
              <View style={styles.cta}>
                {hasPermission(user, 'tables.read') ? (
                  <PrimaryButton
                    title="Ir a mesas"
                    icon="grid-outline"
                    onPress={() => router.push('/(tabs)/mesas' as const)}
                  />
                ) : null}
              </View>
            ) : null}
            </View>
          </FadeIn>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  body: {
    paddingHorizontal: fx.space.md,
    paddingBottom: 56,
  },
  /** Bloque desde ventas hacia abajo — aire amplio (usuarios 60+). */
  content: {
    gap: 22,
    paddingTop: 4,
  },
  offlineBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    paddingHorizontal: 14,
    paddingVertical: 14,
    marginBottom: 16,
  },
  offlineText: { color: fx.inkMuted, fontSize: 13, flex: 1 },
  err: { color: fx.danger, marginBottom: 12 },
  heroCard: {
    paddingVertical: 22,
    paddingHorizontal: 4,
  },
  metricsRow: { flexDirection: 'row', gap: 16 },
  metricHalf: { flex: 1, paddingVertical: 8 },
  statsCard: {
    paddingVertical: 8,
  },
  inlineStat: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
  },
  inlineBorder: {
    marginTop: 4,
    paddingTop: 16,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: fx.hairline,
  },
  inlineLabel: { fontSize: 14, color: fx.inkMuted },
  inlineValue: { fontSize: 20, color: fx.ink },
  inlineMuted: { fontSize: 14, color: fx.inkFaint, fontWeight: '400' },
  section: {
    fontSize: 13,
    color: fx.inkMuted,
    marginTop: 8,
    marginBottom: 2,
  },
  sectionIn: { fontSize: fx.type.body, color: fx.ink },
  actionRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 16,
  },
  actionBtn: {
    width: '47%',
    minHeight: 104,
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    paddingVertical: 18,
    paddingHorizontal: 12,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  actionIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: fx.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionLabel: {
    fontSize: 15,
    color: fx.ink,
    textAlign: 'center',
  },
  blockCard: {
    marginTop: 4,
  },
  listHead: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: fx.space.md,
    paddingTop: fx.space.lg,
    paddingBottom: 8,
  },
  link: { color: fx.brand, fontSize: 14 },
  listRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: fx.space.md,
    paddingVertical: 18,
  },
  listHairline: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: fx.hairline,
  },
  listTitle: { fontSize: 16, color: fx.ink },
  meta: { color: fx.inkFaint, fontSize: 13, marginTop: 4 },
  amount: { fontSize: 16, color: fx.ink, fontVariant: ['tabular-nums'] },
  cta: { marginTop: fx.space.md, gap: 14 },
});
