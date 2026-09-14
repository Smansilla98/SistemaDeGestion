import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { DiscountTypeRow, OrderRow, ProductRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import {
  AppText,
  Badge,
  Card,
  Chip,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

const STATUS_FLOW = [
  'ABIERTO',
  'ENVIADO',
  'EN_PREPARACION',
  'LISTO',
  'ENTREGADO',
  'CERRADO',
] as const;

function itemLabel(it: NonNullable<OrderRow['items']>[number]) {
  return it.name ?? it.product_name ?? it.product?.name ?? 'Ítem';
}

export default function OrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'orders.write');

  const [order, setOrder] = useState<OrderRow | null>(null);
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [discounts, setDiscounts] = useState<DiscountTypeRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const [addOpen, setAddOpen] = useState(false);
  const [discountOpen, setDiscountOpen] = useState(false);
  const [notes, setNotes] = useState('');
  const [qty, setQty] = useState('1');

  const load = useCallback(async () => {
    if (!id) return;
    setError(null);
    try {
      const o = await api.order(Number(id));
      setOrder(o);
      setNotes(o.observations ?? '');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error al cargar pedido');
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

  const locked = useMemo(
    () => order?.status === 'CERRADO' || order?.status === 'CANCELADO',
    [order?.status],
  );

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

  const openAdd = async () => {
    try {
      const [prods, discs] = await Promise.all([
        api.products({ activeOnly: true }),
        api.discountTypes().catch(() => [] as DiscountTypeRow[]),
      ]);
      setProducts(prods);
      setDiscounts(discs);
      setAddOpen(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudieron cargar productos');
    }
  };

  const openDiscount = async () => {
    try {
      setDiscounts(await api.discountTypes());
      setDiscountOpen(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudieron cargar descuentos');
    }
  };

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  return (
    <View style={styles.root}>
      <PageHeader
        title={order?.number ?? 'Pedido'}
        subtitle={order ? `Estado · ${order.status}` : 'Detalle'}
        icon="receipt"
      />
      <ScrollView contentContainerStyle={styles.body}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {order ? (
          <>
            <Card style={{ marginTop: 12 }}>
              <View style={styles.rowBetween}>
                <Badge label={order.status} />
                <AppText weight="bold" style={styles.total}>
                  ${Number(order.total ?? 0).toFixed(2)}
                </AppText>
              </View>
              {Number(order.discount ?? 0) > 0 ? (
                <AppText style={styles.meta}>
                  Subtotal ${Number(order.subtotal ?? 0).toFixed(2)} · Desc. $
                  {Number(order.discount).toFixed(2)}
                </AppText>
              ) : null}

              <SectionLabel>Ítems</SectionLabel>
              {(order.items ?? []).map((it) => (
                <View key={it.id} style={styles.item}>
                  <View style={{ flex: 1 }}>
                    <AppText weight="semibold">
                      {it.quantity}× {itemLabel(it)}
                    </AppText>
                    {it.unit_price != null ? (
                      <AppText style={styles.meta}>
                        ${Number(it.unit_price).toFixed(2)} c/u
                      </AppText>
                    ) : null}
                  </View>
                  {it.status ? <Badge label={it.status} /> : null}
                  {canWrite && !locked ? (
                    <Pressable
                      onPress={() =>
                        Alert.alert('Quitar ítem', `¿Eliminar ${itemLabel(it)}?`, [
                          { text: 'Cancelar', style: 'cancel' },
                          {
                            text: 'Quitar',
                            style: 'destructive',
                            onPress: () =>
                              void run(async () => {
                                await api.removeOrderItems(order.id, [it.id]);
                              }),
                          },
                        ])
                      }
                    >
                      <AppText weight="bold" style={{ color: colors.danger, fontSize: 12 }}>
                        Quitar
                      </AppText>
                    </Pressable>
                  ) : null}
                </View>
              ))}
              {(order.items ?? []).length === 0 ? (
                <AppText style={styles.meta}>Sin ítems</AppText>
              ) : null}
            </Card>

            {canWrite && !locked ? (
              <>
                <SectionLabel>Acciones</SectionLabel>
                <View style={styles.actions}>
                  <PrimaryButton title="Agregar producto" icon="add" onPress={() => void openAdd()} />
                  {order.status === 'ABIERTO' ? (
                    <PrimaryButton
                      title="Enviar a cocina"
                      icon="flame"
                      loading={busy}
                      onPress={() =>
                        void run(async () => {
                          await api.sendToKitchen(order.id);
                        })
                      }
                    />
                  ) : null}
                  <PrimaryButton
                    title="Aplicar descuento"
                    variant="amber"
                    onPress={() => void openDiscount()}
                  />
                  <PrimaryButton
                    title="Anular pedido"
                    variant="danger"
                    onPress={() =>
                      Alert.alert('Anular pedido', '¿Confirmás la anulación?', [
                        { text: 'Cancelar', style: 'cancel' },
                        {
                          text: 'Anular',
                          style: 'destructive',
                          onPress: () =>
                            void run(async () => {
                              await api.cancelOrder(order.id, 'Anulado desde mobile');
                            }),
                        },
                      ])
                    }
                  />
                </View>

                <SectionLabel>Cambiar estado</SectionLabel>
                <View style={styles.chips}>
                  {STATUS_FLOW.map((s) => (
                    <Chip
                      key={s}
                      label={s.replace(/_/g, ' ')}
                      selected={order.status === s}
                      onPress={() =>
                        void run(async () => {
                          await api.transitionOrder(order.id, s);
                        })
                      }
                    />
                  ))}
                </View>

                <SectionLabel>Notas</SectionLabel>
                <Field
                  label="Observaciones"
                  value={notes}
                  onChangeText={setNotes}
                  placeholder="Notas del pedido"
                  multiline
                />
                <PrimaryButton
                  title="Guardar notas"
                  variant="ghost"
                  loading={busy}
                  onPress={() =>
                    void run(async () => {
                      await api.updateOrder(order.id, { observations: notes });
                    })
                  }
                />
              </>
            ) : null}
          </>
        ) : null}
      </ScrollView>

      <Modal visible={addOpen} animationType="slide" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <AppText weight="bold" style={styles.modalTitle}>
              Agregar producto
            </AppText>
            <Field
              label="Cantidad"
              value={qty}
              onChangeText={setQty}
              keyboardType="number-pad"
            />
            <ScrollView style={{ maxHeight: 320 }}>
              {products.map((p) => (
                <Pressable
                  key={p.id}
                  style={styles.pickRow}
                  onPress={() =>
                    void run(async () => {
                      await api.addOrderItem(Number(id), {
                        product_id: p.id,
                        quantity: Math.max(1, Number(qty) || 1),
                      });
                      setAddOpen(false);
                      setQty('1');
                    })
                  }
                >
                  <AppText weight="semibold" style={{ flex: 1 }}>
                    {p.name}
                  </AppText>
                  <AppText weight="bold" style={{ color: colors.teal600 }}>
                    ${Number(p.price).toFixed(2)}
                  </AppText>
                </Pressable>
              ))}
            </ScrollView>
            <PrimaryButton title="Cerrar" variant="ghost" onPress={() => setAddOpen(false)} />
          </View>
        </View>
      </Modal>

      <Modal visible={discountOpen} animationType="slide" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <AppText weight="bold" style={styles.modalTitle}>
              Tipo de descuento
            </AppText>
            <ScrollView style={{ maxHeight: 320 }}>
              <Pressable
                style={styles.pickRow}
                onPress={() =>
                  void run(async () => {
                    await api.applyDiscount(Number(id), null, 'Sin descuento');
                    setDiscountOpen(false);
                  })
                }
              >
                <AppText weight="semibold">Quitar descuento</AppText>
              </Pressable>
              {discounts.map((d) => (
                <Pressable
                  key={d.id}
                  style={styles.pickRow}
                  onPress={() =>
                    void run(async () => {
                      await api.applyDiscount(Number(id), d.id);
                      setDiscountOpen(false);
                    })
                  }
                >
                  <AppText weight="semibold" style={{ flex: 1 }}>
                    {d.name}
                  </AppText>
                  <AppText weight="bold">{Number(d.percentage)}%</AppText>
                </Pressable>
              ))}
            </ScrollView>
            <PrimaryButton title="Cerrar" variant="ghost" onPress={() => setDiscountOpen(false)} />
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, paddingBottom: 48, gap: 4 },
  err: { color: colors.danger, marginTop: 8 },
  total: { fontSize: 22, color: colors.teal600 },
  meta: { color: colors.gray500, fontSize: 13, marginTop: 4 },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  actions: { gap: 10 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  modalBg: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    backgroundColor: colors.white,
    borderTopLeftRadius: radius.xl,
    borderTopRightRadius: radius.xl,
    padding: space.lg,
    maxHeight: '80%',
    gap: 8,
  },
  modalTitle: { fontSize: 18, marginBottom: 4 },
  pickRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
    gap: 8,
  },
});
