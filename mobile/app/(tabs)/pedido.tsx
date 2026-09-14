import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { ProductRow, TableRow } from '../../src/api/types';
import { colors } from '../../src/theme';

type CartItem = { product_id: number; name: string; quantity: number; price: number };

export default function PedidoScreen() {
  const [tables, setTables] = useState<TableRow[]>([]);
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [mesa, setMesa] = useState<number | null>(null);
  const [q, setQ] = useState('');
  const [cart, setCart] = useState<CartItem[]>([]);
  const [sendKitchen, setSendKitchen] = useState(true);
  const [msg, setMsg] = useState<string | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    try {
      const [t, p] = await Promise.all([api.tables(), api.products()]);
      setTables(Array.isArray(t) ? t : []);
      setProducts(Array.isArray(p) ? p : []);
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : 'Error de carga');
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  const filtered = useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return products.slice(0, 60);
    return products.filter((p) => p.name.toLowerCase().includes(needle)).slice(0, 60);
  }, [products, q]);

  const addProduct = (p: ProductRow) => {
    const price = Number(p.price);
    setCart((prev) => {
      const i = prev.findIndex((x) => x.product_id === p.id);
      if (i >= 0) {
        const copy = [...prev];
        copy[i] = { ...copy[i], quantity: copy[i].quantity + 1 };
        return copy;
      }
      return [...prev, { product_id: p.id, name: p.name, quantity: 1, price }];
    });
  };

  const bump = (idx: number, delta: number) => {
    setCart((prev) => {
      const copy = [...prev];
      const next = Math.max(1, copy[idx].quantity + delta);
      copy[idx] = { ...copy[idx], quantity: next };
      return copy;
    });
  };

  const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);

  const submit = async () => {
    if (!mesa || cart.length === 0) {
      setErr('Elegí mesa y al menos un producto');
      return;
    }
    setBusy(true);
    setErr(null);
    setMsg(null);
    try {
      const order = await api.createOrder({
        table_id: mesa,
        ensure_table_occupied: true,
        send_to_kitchen: sendKitchen,
        idempotency_key: `m-${Date.now()}-${mesa}`,
        items: cart.map((c) => ({ product_id: c.product_id, quantity: c.quantity })),
      });
      setMsg(`Pedido ${(order as { number?: string }).number ?? ''} listo`);
      setCart([]);
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : 'No se pudo crear el pedido');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScrollView style={styles.root} contentContainerStyle={{ padding: 16, paddingBottom: 40 }}>
      <Text style={styles.h}>Mesa</Text>
      <View style={styles.row}>
        {tables.map((t) => (
          <Pressable
            key={t.id}
            onPress={() => setMesa(t.id)}
            style={[styles.mesa, mesa === t.id && styles.mesaOn]}
          >
            <Text style={[styles.mesaText, mesa === t.id && { color: '#fff' }]}>{t.number}</Text>
          </Pressable>
        ))}
      </View>

      <Text style={styles.h}>Productos</Text>
      <TextInput
        style={styles.search}
        placeholder="Buscar…"
        value={q}
        onChangeText={setQ}
        placeholderTextColor={colors.gray600}
      />
      <View style={styles.row}>
        {filtered.map((p) => (
          <Pressable key={p.id} style={styles.prod} onPress={() => addProduct(p)}>
            <Text style={styles.prodName} numberOfLines={2}>
              {p.name}
            </Text>
            <Text style={styles.prodPrice}>${Number(p.price).toFixed(2)}</Text>
          </Pressable>
        ))}
      </View>

      <Text style={styles.h}>Carrito</Text>
      {cart.length === 0 ? (
        <Text style={styles.muted}>Todavía no agregaste productos.</Text>
      ) : (
        cart.map((c, idx) => (
          <View key={c.product_id} style={styles.cartRow}>
            <Text style={{ flex: 1 }}>{c.name}</Text>
            <Pressable onPress={() => bump(idx, -1)} style={styles.step}>
              <Text>−</Text>
            </Pressable>
            <Text style={{ width: 28, textAlign: 'center' }}>{c.quantity}</Text>
            <Pressable onPress={() => bump(idx, 1)} style={styles.step}>
              <Text>+</Text>
            </Pressable>
          </View>
        ))
      )}

      <Pressable onPress={() => setSendKitchen((v) => !v)} style={styles.toggle}>
        <Text>{sendKitchen ? '✓ Enviar a cocina' : '○ Solo guardar'}</Text>
      </Pressable>

      <Text style={styles.total}>Total ${total.toFixed(2)}</Text>
      {err && <Text style={styles.err}>{err}</Text>}
      {msg && <Text style={styles.ok}>{msg}</Text>}

      <Pressable style={styles.btn} onPress={() => void submit()} disabled={busy}>
        {busy ? <ActivityIndicator color="#fff" /> : <Text style={styles.btnText}>Confirmar pedido</Text>}
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  h: { fontWeight: '700', color: colors.gray600, textTransform: 'uppercase', fontSize: 12, marginTop: 12, marginBottom: 8 },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  mesa: {
    minWidth: 64,
    minHeight: 48,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.gray200,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  mesaOn: { backgroundColor: colors.teal700, borderColor: colors.teal700 },
  mesaText: { fontWeight: '700', color: colors.gray900 },
  search: {
    backgroundColor: colors.white,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.gray200,
    minHeight: 48,
    paddingHorizontal: 12,
    fontSize: 16,
    marginBottom: 8,
  },
  prod: {
    width: '47%',
    minHeight: 72,
    backgroundColor: colors.white,
    borderRadius: 12,
    padding: 10,
    borderWidth: 1,
    borderColor: colors.gray200,
  },
  prodName: { fontWeight: '600', color: colors.gray900 },
  prodPrice: { marginTop: 6, color: colors.teal700 },
  cartRow: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 8 },
  step: {
    width: 40,
    height: 40,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.gray200,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.white,
  },
  toggle: { marginTop: 12, padding: 12, backgroundColor: colors.white, borderRadius: 10 },
  total: { marginTop: 12, fontSize: 18, fontWeight: '800' },
  err: { color: colors.danger, marginTop: 8 },
  ok: { color: colors.green, marginTop: 8 },
  muted: { color: colors.gray600 },
  btn: {
    marginTop: 16,
    backgroundColor: colors.teal700,
    borderRadius: 12,
    minHeight: 52,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnText: { color: '#fff', fontWeight: '700' },
});
