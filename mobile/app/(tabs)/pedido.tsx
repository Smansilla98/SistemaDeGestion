import { useCallback, useMemo, useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { ProductRow, TableRow } from '../../src/api/types';
import { colors, radius, space } from '../../src/theme';
import {
  AppText,
  Chip,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

type CartItem = {
  product_id: number;
  name: string;
  quantity: number;
  price: number;
  observations: string;
};

function isOutOfStock(p: ProductRow): boolean {
  return typeof p.current_stock === 'number' && p.current_stock <= 0;
}

export default function PedidoScreen() {
  const router = useRouter();
  const params = useLocalSearchParams<{ tableId?: string }>();
  const [tables, setTables] = useState<TableRow[]>([]);
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [mesa, setMesa] = useState<number | null>(params.tableId ? Number(params.tableId) : null);
  const [customerName, setCustomerName] = useState('');
  const [orderObs, setOrderObs] = useState('');
  const [q, setQ] = useState('');
  const [cart, setCart] = useState<CartItem[]>([]);
  const [sendKitchen, setSendKitchen] = useState(true);
  const [err, setErr] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    try {
      const [t, p, stock] = await Promise.all([
        api.tables(),
        api.products(),
        api.stock().catch(() => [] as import('../../src/api/types').StockRow[]),
      ]);
      const stockMap = new Map(
        (Array.isArray(stock) ? stock : []).map((s) => [s.id, s.current_stock]),
      );
      const productsWithStock = (Array.isArray(p) ? p : []).map((prod) => {
        const qty = stockMap.get(prod.id);
        return qty != null ? { ...prod, current_stock: qty } : prod;
      });
      setTables(Array.isArray(t) ? t : []);
      setProducts(productsWithStock);
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : 'Error de carga');
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void load();
      if (params.tableId) setMesa(Number(params.tableId));
    }, [load, params.tableId]),
  );

  const filtered = useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return products.slice(0, 60);
    return products.filter((p) => p.name.toLowerCase().includes(needle)).slice(0, 60);
  }, [products, q]);

  const addProduct = (p: ProductRow) => {
    if (isOutOfStock(p)) return;
    const price = Number(p.price);
    setCart((prev) => {
      const i = prev.findIndex((x) => x.product_id === p.id);
      if (i >= 0) {
        const copy = [...prev];
        copy[i] = { ...copy[i], quantity: copy[i].quantity + 1 };
        return copy;
      }
      return [...prev, { product_id: p.id, name: p.name, quantity: 1, price, observations: '' }];
    });
  };

  const bump = (idx: number, delta: number) => {
    setCart((prev) => {
      const copy = [...prev];
      const next = copy[idx].quantity + delta;
      if (next <= 0) return prev.filter((_, i) => i !== idx);
      copy[idx] = { ...copy[idx], quantity: next };
      return copy;
    });
  };

  const setItemObs = (idx: number, observations: string) => {
    setCart((prev) => {
      const copy = [...prev];
      copy[idx] = { ...copy[idx], observations };
      return copy;
    });
  };

  const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);

  const submit = async () => {
    if (cart.length === 0) {
      setErr('Agregá al menos un producto');
      return;
    }
    if (!mesa && !customerName.trim()) {
      setErr('Sin mesa: ingresá el nombre del cliente');
      return;
    }
    setBusy(true);
    setErr(null);
    try {
      const order = await api.createOrder({
        ...(mesa
          ? { table_id: mesa, ensure_table_occupied: true }
          : { customer_name: customerName.trim(), ensure_table_occupied: false }),
        observations: orderObs.trim() || undefined,
        send_to_kitchen: sendKitchen,
        idempotency_key: `m-${Date.now()}-${mesa ?? 'q'}`,
        items: cart.map((c) => ({
          product_id: c.product_id,
          quantity: c.quantity,
          ...(c.observations.trim() ? { observations: c.observations.trim() } : {}),
        })),
      });
      const orderId = Number((order as { id?: number }).id);
      setCart([]);
      setCustomerName('');
      setOrderObs('');
      if (orderId) {
        router.push(`/order/${orderId}` as never);
      }
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : 'No se pudo crear el pedido');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Nuevo pedido" subtitle="Salón · toma rápida" bi="plus-square" />
      <ScrollView contentContainerStyle={{ padding: space.lg, paddingBottom: 48 }}>
        <SectionLabel>Mesa (opcional)</SectionLabel>
        <View style={styles.row}>
          <Chip
            label="Sin mesa"
            selected={mesa === null}
            onPress={() => setMesa(null)}
            tone={colors.amber}
          />
          {tables.map((t) => (
            <Chip
              key={t.id}
              label={t.number}
              selected={mesa === t.id}
              onPress={() => setMesa(t.id)}
              tone={t.status === 'OCUPADA' ? colors.amber : colors.teal500}
            />
          ))}
        </View>

        {mesa === null ? (
          <Field
            label="Cliente"
            placeholder="Nombre del cliente"
            value={customerName}
            onChangeText={setCustomerName}
          />
        ) : null}

        <Field
          label="Observaciones del pedido"
          placeholder="Notas generales…"
          value={orderObs}
          onChangeText={setOrderObs}
          multiline
        />

        <SectionLabel>Productos</SectionLabel>
        <Field placeholder="Buscar…" value={q} onChangeText={setQ} />
        <View style={styles.row}>
          {filtered.map((p) => {
            const out = isOutOfStock(p);
            return (
              <Pressable
                key={p.id}
                style={[styles.prod, out && styles.prodDisabled]}
                onPress={() => addProduct(p)}
                disabled={out}
              >
                <AppText weight="semibold" style={styles.prodName} numberOfLines={2}>
                  {p.name}
                </AppText>
                <AppText weight="bold" style={[styles.prodPrice, out && { color: colors.danger }]}>
                  {out ? 'Sin stock' : `$${Number(p.price).toFixed(2)}`}
                </AppText>
              </Pressable>
            );
          })}
        </View>

        <SectionLabel>Carrito</SectionLabel>
        {cart.length === 0 ? (
          <AppText style={styles.muted}>Todavía no agregaste productos.</AppText>
        ) : (
          cart.map((c, idx) => (
            <View key={c.product_id} style={styles.cartBlock}>
              <View style={styles.cartRow}>
                <AppText weight="medium" style={{ flex: 1 }}>
                  {c.name}
                </AppText>
                <Pressable onPress={() => bump(idx, -1)} style={styles.step}>
                  <AppText weight="bold">−</AppText>
                </Pressable>
                <AppText weight="bold" style={{ width: 28, textAlign: 'center' }}>
                  {c.quantity}
                </AppText>
                <Pressable onPress={() => bump(idx, 1)} style={styles.step}>
                  <AppText weight="bold">+</AppText>
                </Pressable>
              </View>
              <Field
                placeholder="Obs. del ítem…"
                value={c.observations}
                onChangeText={(t) => setItemObs(idx, t)}
              />
            </View>
          ))
        )}

        <Pressable onPress={() => setSendKitchen((v) => !v)} style={styles.toggle}>
          <AppText weight="medium">
            {sendKitchen ? '✓ Enviar a cocina' : '○ Solo guardar'}
          </AppText>
        </Pressable>

        <AppText weight="bold" style={styles.total}>
          Total ${total.toFixed(2)}
        </AppText>
        {err && (
          <AppText weight="medium" style={styles.err}>
            {err}
          </AppText>
        )}

        <PrimaryButton
          title="Confirmar pedido"
          icon="checkmark-circle"
          onPress={() => void submit()}
          loading={busy}
          disabled={busy}
        />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  prod: {
    width: '47%',
    minHeight: 78,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    padding: 12,
    borderWidth: 1,
    borderColor: colors.gray100,
  },
  prodDisabled: { opacity: 0.55, borderColor: colors.dangerBg },
  prodName: { color: colors.gray900 },
  prodPrice: { marginTop: 8, color: colors.teal500 },
  cartBlock: {
    marginBottom: 8,
    paddingBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
  cartRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 6,
  },
  step: {
    width: 40,
    height: 40,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray200,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.white,
  },
  toggle: {
    marginTop: 14,
    padding: 14,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
  },
  total: { marginTop: 14, marginBottom: 8, fontSize: 20, color: colors.gray900 },
  err: { color: colors.danger, marginBottom: 8 },
  muted: { color: colors.gray500 },
});
