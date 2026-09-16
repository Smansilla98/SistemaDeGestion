import { useCallback, useMemo, useState } from 'react';
import {
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
import type { TableDetail, TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { AppText, PrimaryButton } from '../../src/ui/primitives';
import {
  FadeIn,
  FxHeader,
  HeroMetric,
  ModalSheet,
  OrderSkeleton,
  StatusDot,
  Surface,
} from '../../src/ui/fintech';

export default function TableDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'tables.write');
  const canPay = hasPermission(user, 'cash.write');
  const canOrder = hasPermission(user, 'orders.write');

  const [detail, setDetail] = useState<TableDetail | null>(null);
  const [freeTables, setFreeTables] = useState<TableRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [transferOpen, setTransferOpen] = useState(false);

  const load = useCallback(async () => {
    if (!id) return;
    setError(null);
    try {
      setDetail(await api.tableDetail(Number(id)));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error al cargar mesa');
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

  const table = detail?.table;
  const receiptTotal = useMemo(() => {
    const r = detail?.receipt;
    if (r && typeof r.total === 'number') return r.total;
    return (detail?.orders ?? []).reduce((acc, o) => acc + Number(o.total ?? 0), 0);
  }, [detail]);

  const run = async (fn: () => Promise<void>) => {
    setBusy(true);
    setError(null);
    try {
      await fn();
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Operación fallida');
    } finally {
      setBusy(false);
    }
  };

  const openTransfer = async () => {
    try {
      const all = await api.tables();
      setFreeTables(all.filter((t) => t.status === 'LIBRE' && t.id !== Number(id)));
      setTransferOpen(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error');
    }
  };

  if (loading && !detail) {
    return (
      <View style={styles.root}>
        <FxHeader title="Mesa" onBack={() => router.back()} />
        <OrderSkeleton />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <FxHeader
        title={table ? `Mesa ${table.number}` : 'Mesa'}
        subtitle={table?.waiter ? `Mozo · ${table.waiter}` : undefined}
        onBack={() => router.back()}
        right={table ? <StatusDot status={table.status} /> : null}
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

        <FadeIn>
          <View style={styles.content}>
            {table ? (
              <Surface style={styles.heroPad}>
                <HeroMetric
                  label="Total"
                  value={`$${Number(receiptTotal).toFixed(2)}`}
                  hint={
                    detail?.receipt?.subtotal != null
                      ? `Subtotal $${Number(detail.receipt.subtotal).toFixed(2)}${
                          detail.receipt.discount
                            ? ` · Desc. $${Number(detail.receipt.discount).toFixed(2)}`
                            : ''
                        }`
                      : undefined
                  }
                  mono
                />
              </Surface>
            ) : null}

            <AppText weight="semibold" style={styles.section}>
              Pedidos abiertos
            </AppText>
            <Surface padded={false}>
              {(detail?.orders ?? []).map((o, idx) => (
                <Pressable
                  key={o.id}
                  onPress={() => router.push(`/order/${o.id}` as Href)}
                  style={[styles.listRow, idx > 0 && styles.hairline]}
                >
                  <View style={{ flex: 1, gap: 4 }}>
                    <AppText weight="semibold" style={styles.listTitle}>
                      {o.number}
                    </AppText>
                    <AppText style={styles.meta}>
                      {(o.items ?? []).length} ítems · ${Number(o.total ?? 0).toFixed(2)}
                    </AppText>
                  </View>
                  <StatusDot status={o.status} size="sm" />
                  <Ionicons name="chevron-forward" size={16} color={fx.inkFaint} />
                </Pressable>
              ))}
              {(detail?.orders ?? []).length === 0 ? (
                <AppText style={[styles.meta, { padding: fx.space.md }]}>
                  Sin pedidos abiertos
                </AppText>
              ) : null}
            </Surface>

            <AppText weight="semibold" style={styles.section}>
              Acciones
            </AppText>
            <View style={styles.actions}>
              {canWrite && table?.status === 'LIBRE' ? (
                <>
                  <PrimaryButton
                    title="Ocupar mesa"
                    loading={busy}
                    onPress={() =>
                      void run(async () => {
                        await api.occupyTable(table.id);
                      })
                    }
                  />
                  <PrimaryButton
                    title="Reservar"
                    variant="ghost"
                    onPress={() => router.push(`/tables/reserve?id=${table.id}` as Href)}
                  />
                </>
              ) : null}
              {canWrite && table?.status === 'RESERVADA' ? (
                <PrimaryButton
                  title="Ocupar (desde reserva)"
                  loading={busy}
                  onPress={() =>
                    void run(async () => {
                      await api.occupyTable(table.id);
                    })
                  }
                />
              ) : null}
              {canWrite && table?.status === 'OCUPADA' ? (
                <>
                  <PrimaryButton
                    title="Liberar mesa"
                    variant="danger"
                    loading={busy}
                    onPress={() =>
                      Alert.alert('Liberar', '¿Liberar esta mesa?', [
                        { text: 'Cancelar', style: 'cancel' },
                        {
                          text: 'Liberar',
                          style: 'destructive',
                          onPress: () =>
                            void run(async () => {
                              await api.freeTable(table.id);
                            }),
                        },
                      ])
                    }
                  />
                  <PrimaryButton
                    title="Transferir"
                    variant="amber"
                    onPress={() => void openTransfer()}
                  />
                </>
              ) : null}
              {canWrite && table ? (
                <PrimaryButton
                  title="Editar mesa"
                  variant="ghost"
                  onPress={() => router.push(`/tables/edit?id=${table.id}` as Href)}
                />
              ) : null}
              {canOrder && table ? (
                <PrimaryButton
                  title="Nuevo pedido"
                  icon="cart"
                  onPress={() =>
                    router.push({
                      pathname: '/(tabs)/pedido',
                      params: { tableId: String(table.id) },
                    })
                  }
                />
              ) : null}
              {canPay && table?.status === 'OCUPADA' ? (
                <PrimaryButton
                  title="Cobrar en caja"
                  icon="cash"
                  onPress={() =>
                    router.push({
                      pathname: '/(tabs)/caja',
                      params: { tableId: String(table.id) },
                    } as never)
                  }
                />
              ) : null}
            </View>
          </View>
        </FadeIn>
      </ScrollView>

      <ModalSheet
        visible={transferOpen}
        title="Transferir a…"
        onClose={() => setTransferOpen(false)}
      >
        {freeTables.map((t) => (
          <Pressable
            key={t.id}
            style={styles.pickRow}
            onPress={() =>
              void run(async () => {
                await api.transferTable(Number(id), t.id);
                setTransferOpen(false);
                router.replace(`/table/${t.id}` as Href);
              })
            }
          >
            <AppText weight="bold" style={{ fontSize: 17, color: fx.ink }}>
              Mesa {t.number}
            </AppText>
            <Ionicons name="chevron-forward" size={18} color={fx.inkFaint} />
          </Pressable>
        ))}
        {freeTables.length === 0 ? (
          <AppText style={styles.meta}>No hay mesas libres</AppText>
        ) : null}
      </ModalSheet>
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
  section: { fontSize: 13, color: fx.inkMuted, marginBottom: -8 },
  listRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: fx.space.md,
    paddingVertical: 16,
  },
  listTitle: { fontSize: 16, color: fx.ink },
  hairline: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: fx.hairline,
  },
  actions: { gap: 12 },
  pickRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 16,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: fx.hairline,
  },
});
