import { useCallback, useMemo, useState } from 'react';
import {
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
import { canSeeAdminHub, hasPermission, isAdminRole } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import {
  AppText,
  Chip,
  Field,
  PrimaryButton,
} from '../../src/ui/primitives';
import {
  FadeIn,
  FxHeader,
  HeroMetric,
  OrderSkeleton,
  StatusDot,
  Surface,
  SwipeAction,
} from '../../src/ui/fintech';

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

function isOutOfStock(p: ProductRow): boolean {
  return typeof p.current_stock === 'number' && p.current_stock <= 0;
}

export default function OrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'orders.write');
  const canDelete =
    isAdminRole(user?.role) || canSeeAdminHub(user) || user?.role === 'ENCARGADO';

  const [order, setOrder] = useState<OrderRow | null>(null);
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [discounts, setDiscounts] = useState<DiscountTypeRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const [addOpen, setAddOpen] = useState(false);
  const [replaceOpen, setReplaceOpen] = useState(false);
  const [replaceItemId, setReplaceItemId] = useState<number | null>(null);
  const [discountOpen, setDiscountOpen] = useState(false);
  const [notes, setNotes] = useState('');
  const [itemObs, setItemObs] = useState('');
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

  const loadProducts = async () => {
    const [prods, stock] = await Promise.all([
      api.products({ activeOnly: true }),
      api.stock().catch(() => [] as import('../../src/api/types').StockRow[]),
    ]);
    const stockMap = new Map(
      (Array.isArray(stock) ? stock : []).map((s) => [s.id, s.current_stock]),
    );
    return (Array.isArray(prods) ? prods : []).map((p) => {
      const qtyStock = stockMap.get(p.id);
      return qtyStock != null ? { ...p, current_stock: qtyStock } : p;
    });
  };

  const openAdd = async () => {
    try {
      const [prods, discs] = await Promise.all([
        loadProducts(),
        api.discountTypes().catch(() => [] as DiscountTypeRow[]),
      ]);
      setProducts(prods);
      setDiscounts(discs);
      setItemObs('');
      setQty('1');
      setAddOpen(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudieron cargar productos');
    }
  };

  const openReplace = async (itemId: number) => {
    try {
      setProducts(await loadProducts());
      setReplaceItemId(itemId);
      setItemObs('');
      setQty('1');
      setReplaceOpen(true);
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

  if (loading && !order) {
    return (
      <View style={styles.root}>
        <FxHeader title="Pedido" onBack={() => router.back()} />
        <OrderSkeleton />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <FxHeader
        title={order?.number ?? 'Pedido'}
        subtitle="Detalle"
        onBack={() => router.back()}
        right={order ? <StatusDot status={order.status} /> : null}
      />
      <ScrollView contentContainerStyle={styles.body} showsVerticalScrollIndicator={false}>
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {order ? (
          <FadeIn>
            <Surface style={styles.hero}>
              <HeroMetric
                label="Total"
                value={`$${Number(order.total ?? 0).toFixed(2)}`}
                hint={
                  Number(order.discount ?? 0) > 0
                    ? `Subtotal $${Number(order.subtotal ?? 0).toFixed(2)} · Desc. $${Number(order.discount).toFixed(2)}`
                    : `${(order.items ?? []).length} ítem(s)`
                }
                mono
              />
            </Surface>

            <AppText weight="semibold" style={styles.section}>
              Ítems
            </AppText>
            {(order.items ?? []).map((it) => {
              const line = (
                <Surface style={styles.itemCard} padded>
                  <View style={styles.itemRow}>
                    <View style={{ flex: 1 }}>
                      <AppText weight="semibold" style={styles.itemTitle}>
                        {it.quantity}× {itemLabel(it)}
                      </AppText>
                      {it.observations ? (
                        <AppText style={styles.meta}>Obs: {it.observations}</AppText>
                      ) : null}
                      {it.unit_price != null ? (
                        <AppText style={styles.meta}>
                          ${Number(it.unit_price).toFixed(2)} c/u
                        </AppText>
                      ) : null}
                    </View>
                    {it.status ? <StatusDot status={it.status} size="sm" /> : null}
                  </View>
                </Surface>
              );

              if (!canWrite || locked) return <View key={it.id}>{line}</View>;

              return (
                <View key={it.id} style={{ marginBottom: 8 }}>
                  <SwipeAction
                    rightActions={[
                      {
                        label: 'Cambiar',
                        tone: 'brand',
                        onPress: () => void openReplace(it.id),
                      },
                      {
                        label: 'Quitar',
                        tone: 'danger',
                        onPress: () =>
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
                          ]),
                      },
                    ]}
                  >
                    {line}
                  </SwipeAction>
                </View>
              );
            })}
            {(order.items ?? []).length === 0 ? (
              <AppText style={styles.meta}>Sin ítems</AppText>
            ) : null}

            {canWrite && !locked ? (
              <>
                <AppText weight="semibold" style={styles.section}>
                  Acciones
                </AppText>
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
                  {order.status !== 'CERRADO' ? (
                    <PrimaryButton
                      title="Cerrar pedido"
                      icon="checkmark-done"
                      loading={busy}
                      onPress={() =>
                        Alert.alert(
                          'Cerrar pedido',
                          'Se cierra el pedido y se libera la mesa asociada.',
                          [
                            { text: 'Cancelar', style: 'cancel' },
                            {
                              text: 'Cerrar',
                              onPress: () =>
                                void run(async () => {
                                  await api.closeOrder(order.id);
                                }),
                            },
                          ],
                        )
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
                  {canDelete ? (
                    <PrimaryButton
                      title="Eliminar pedido"
                      variant="danger"
                      onPress={() =>
                        Alert.alert(
                          'Eliminar pedido',
                          'Esta acción borra el pedido. ¿Continuar?',
                          [
                            { text: 'Cancelar', style: 'cancel' },
                            {
                              text: 'Eliminar',
                              style: 'destructive',
                              onPress: () =>
                                void run(async () => {
                                  await api.deleteOrder(order.id);
                                  router.back();
                                }),
                            },
                          ],
                        )
                      }
                    />
                  ) : null}
                </View>

                <AppText weight="semibold" style={styles.section}>
                  Estado
                </AppText>
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

                <AppText weight="semibold" style={styles.section}>
                  Notas
                </AppText>
                <Surface>
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
                </Surface>
              </>
            ) : null}
          </FadeIn>
        ) : null}
      </ScrollView>

      <Modal visible={addOpen} animationType="fade" transparent>
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
            <Field
              label="Observaciones"
              value={itemObs}
              onChangeText={setItemObs}
              placeholder="Sin cebolla, extra salsa…"
            />
            <ScrollView style={{ maxHeight: 320 }}>
              {products.map((p) => {
                const out = isOutOfStock(p);
                return (
                  <Pressable
                    key={p.id}
                    style={[styles.pickRow, out && { opacity: 0.45 }]}
                    disabled={out || busy}
                    onPress={() =>
                      void run(async () => {
                        await api.addOrderItem(Number(id), {
                          product_id: p.id,
                          quantity: Math.max(1, Number(qty) || 1),
                          ...(itemObs.trim() ? { observations: itemObs.trim() } : {}),
                        });
                        setAddOpen(false);
                        setQty('1');
                        setItemObs('');
                      })
                    }
                  >
                    <AppText weight="semibold" style={{ flex: 1, color: fx.ink }}>
                      {p.name}
                      {out ? ' (sin stock)' : ''}
                    </AppText>
                    <AppText weight="bold" style={{ color: fx.brand }}>
                      ${Number(p.price).toFixed(2)}
                    </AppText>
                  </Pressable>
                );
              })}
            </ScrollView>
            <PrimaryButton title="Cerrar" variant="ghost" onPress={() => setAddOpen(false)} />
          </View>
        </View>
      </Modal>

      <Modal visible={replaceOpen} animationType="fade" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <AppText weight="bold" style={styles.modalTitle}>
              Reemplazar ítem
            </AppText>
            <Field
              label="Cantidad"
              value={qty}
              onChangeText={setQty}
              keyboardType="number-pad"
            />
            <Field
              label="Observaciones"
              value={itemObs}
              onChangeText={setItemObs}
              placeholder="Opcional"
            />
            <ScrollView style={{ maxHeight: 320 }}>
              {products.map((p) => {
                const out = isOutOfStock(p);
                return (
                  <Pressable
                    key={p.id}
                    style={[styles.pickRow, out && { opacity: 0.45 }]}
                    disabled={out || busy || replaceItemId == null}
                    onPress={() =>
                      void run(async () => {
                        if (replaceItemId == null) return;
                        await api.replaceOrderItem(Number(id), {
                          order_item_id: replaceItemId,
                          product_id: p.id,
                          quantity: Math.max(1, Number(qty) || 1),
                          ...(itemObs.trim() ? { observations: itemObs.trim() } : {}),
                        });
                        setReplaceOpen(false);
                        setReplaceItemId(null);
                      })
                    }
                  >
                    <AppText weight="semibold" style={{ flex: 1, color: fx.ink }}>
                      {p.name}
                      {out ? ' (sin stock)' : ''}
                    </AppText>
                    <AppText weight="bold" style={{ color: fx.brand }}>
                      ${Number(p.price).toFixed(2)}
                    </AppText>
                  </Pressable>
                );
              })}
            </ScrollView>
            <PrimaryButton
              title="Cerrar"
              variant="ghost"
              onPress={() => {
                setReplaceOpen(false);
                setReplaceItemId(null);
              }}
            />
          </View>
        </View>
      </Modal>

      <Modal visible={discountOpen} animationType="fade" transparent>
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
                <AppText weight="semibold" style={{ color: fx.ink }}>
                  Quitar descuento
                </AppText>
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
                  <AppText weight="semibold" style={{ flex: 1, color: fx.ink }}>
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
  root: { flex: 1, backgroundColor: fx.canvas },
  body: { paddingHorizontal: fx.space.md, paddingBottom: 48, gap: 4 },
  err: { color: fx.danger, marginBottom: 8 },
  hero: { paddingVertical: fx.space.lg, marginBottom: fx.space.sm },
  section: {
    marginTop: fx.space.md,
    marginBottom: fx.space.sm,
    fontSize: fx.type.caption,
    color: fx.inkMuted,
  },
  itemCard: { marginBottom: 0 },
  itemRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  itemTitle: { fontSize: 15, color: fx.ink },
  meta: { color: fx.inkFaint, fontSize: 12, marginTop: 4 },
  actions: { gap: 10 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  modalBg: {
    flex: 1,
    backgroundColor: 'rgba(3,26,22,0.35)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    backgroundColor: fx.surface,
    borderTopLeftRadius: fx.radius.lg,
    borderTopRightRadius: fx.radius.lg,
    padding: fx.space.lg,
    maxHeight: '80%',
    gap: 8,
  },
  modalTitle: { fontSize: 18, marginBottom: 4, color: fx.ink },
  pickRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: fx.hairline,
    gap: 8,
  },
});
