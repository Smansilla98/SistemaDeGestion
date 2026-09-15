import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { TableDetail, TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import {
  AppText,
  Badge,
  Card,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

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

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  return (
    <View style={styles.root}>
      <PageHeader
        title={table ? `Mesa ${table.number}` : 'Mesa'}
        subtitle={table?.sector ?? table?.status}
        icon="grid"
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

        {table ? (
          <Card>
            <View style={styles.rowBetween}>
              <Badge label={table.status} />
              {table.waiter ? (
                <AppText style={styles.meta}>Mozo: {table.waiter}</AppText>
              ) : null}
            </View>
            <AppText weight="bold" style={styles.total}>
              Total ${Number(receiptTotal).toFixed(2)}
            </AppText>
            {detail?.receipt?.subtotal != null ? (
              <AppText style={styles.meta}>
                Subtotal ${Number(detail.receipt.subtotal).toFixed(2)}
                {detail.receipt.discount
                  ? ` · Desc. $${Number(detail.receipt.discount).toFixed(2)}`
                  : ''}
              </AppText>
            ) : null}
          </Card>
        ) : null}

        <SectionLabel>Pedidos abiertos</SectionLabel>
        {(detail?.orders ?? []).map((o) => (
          <Pressable key={o.id} onPress={() => router.push(`/order/${o.id}` as Href)}>
            <Card style={{ marginBottom: 8 }}>
              <View style={styles.rowBetween}>
                <AppText weight="bold">{o.number}</AppText>
                <Badge label={o.status} />
              </View>
              <AppText style={styles.meta}>
                {(o.items ?? []).length} ítems · ${Number(o.total ?? 0).toFixed(2)}
              </AppText>
            </Card>
          </Pressable>
        ))}
        {(detail?.orders ?? []).length === 0 ? (
          <AppText style={styles.meta}>Sin pedidos abiertos</AppText>
        ) : null}

        <SectionLabel>Acciones</SectionLabel>
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
              <PrimaryButton title="Transferir" variant="amber" onPress={() => void openTransfer()} />
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
      </ScrollView>

      <Modal visible={transferOpen} animationType="slide" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <AppText weight="bold" style={{ fontSize: 18 }}>
              Transferir a…
            </AppText>
            <ScrollView style={{ maxHeight: 320 }}>
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
                  <AppText weight="bold">Mesa {t.number}</AppText>
                </Pressable>
              ))}
            </ScrollView>
            <PrimaryButton title="Cerrar" variant="ghost" onPress={() => setTransferOpen(false)} />
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, paddingBottom: 48, gap: 6 },
  err: { color: colors.danger },
  meta: { color: colors.gray500, fontSize: 13, marginTop: 4 },
  total: { fontSize: 26, color: colors.teal600, marginTop: 8 },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  actions: { gap: 10, marginTop: 8 },
  modalBg: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'flex-end' },
  modalCard: {
    backgroundColor: colors.white,
    borderTopLeftRadius: radius.xl,
    borderTopRightRadius: radius.xl,
    padding: space.lg,
    gap: 8,
  },
  pickRow: {
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
});
