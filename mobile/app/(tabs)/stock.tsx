import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { StockMovementRow, StockRow } from '../../src/api/types';
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

type MovType = 'ENTRADA' | 'SALIDA' | 'AJUSTE';

export default function StockScreen() {
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'stock.write');
  const canMozoInsumo =
    hasPermission(user, 'stock_mozo.create') || hasPermission(user, 'stock.write');
  const isMozo = user?.role === 'MOZO';

  const [tab, setTab] = useState<'productos' | 'movimientos' | 'insumos'>('productos');
  const [rows, setRows] = useState<StockRow[]>([]);
  const [movements, setMovements] = useState<StockMovementRow[]>([]);
  const [insumos, setInsumos] = useState<
    Array<{ id: number; name: string; unit?: string | null; current_stock?: number }>
  >([]);
  const [suppliers, setSuppliers] = useState<Array<{ id: number; name: string }>>([]);
  const [search, setSearch] = useState('');
  const [filterProductId, setFilterProductId] = useState<number | null>(null);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState<StockRow | null>(null);
  const [type, setType] = useState<MovType>('ENTRADA');
  const [qty, setQty] = useState('1');
  const [reason, setReason] = useState('');
  const [supplierId, setSupplierId] = useState<number | null>(null);
  const [unitCost, setUnitCost] = useState('');
  const [purchaseDate, setPurchaseDate] = useState('');
  const [invoice, setInvoice] = useState('');
  const [mozoProductId, setMozoProductId] = useState<number | null>(null);
  const [mozoQty, setMozoQty] = useState('1');
  const [mozoReason, setMozoReason] = useState('');
  const [busy, setBusy] = useState(false);

  const allowedTypes = useMemo<MovType[]>(
    () => (isMozo ? ['ENTRADA', 'SALIDA'] : ['ENTRADA', 'SALIDA', 'AJUSTE']),
    [isMozo],
  );

  const load = useCallback(async () => {
    setError(null);
    try {
      const [stock, movs] = await Promise.all([
        api.stock(search || undefined),
        api.stockMovements({
          productId: filterProductId ?? undefined,
          dateFrom: dateFrom.trim() || undefined,
          dateTo: dateTo.trim() || undefined,
        }),
      ]);
      setRows(Array.isArray(stock) ? stock : []);
      setMovements(Array.isArray(movs) ? movs : []);
      if (canWrite) {
        const s = await api.suppliers().catch(() => []);
        setSuppliers(Array.isArray(s) ? s : []);
      }
      if (canMozoInsumo) {
        const ins = await api.mozoInsumos().catch(() => []);
        setInsumos(Array.isArray(ins) ? ins : []);
        setMozoProductId((prev) => prev ?? ins?.[0]?.id ?? null);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error stock');
    } finally {
      setLoading(false);
    }
  }, [search, filterProductId, dateFrom, dateTo, canWrite, canMozoInsumo]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

  const resetPurchase = () => {
    setSupplierId(null);
    setUnitCost('');
    setPurchaseDate('');
    setInvoice('');
  };

  const submit = async () => {
    if (!modal) return;
    const quantity = Number(qty);
    if (!quantity || quantity < 1) {
      setError('Cantidad inválida');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const body: Parameters<typeof api.createStockMovement>[0] = {
        product_id: modal.id,
        type,
        quantity,
        reason: reason || undefined,
      };
      if (type === 'ENTRADA' && canWrite) {
        if (supplierId) body.supplier_id = supplierId;
        if (unitCost.trim()) body.unit_cost = Number(unitCost);
        if (purchaseDate.trim()) body.purchase_date = purchaseDate.trim();
        if (invoice.trim()) body.invoice_number = invoice.trim();
      }
      await api.createStockMovement(body);
      setModal(null);
      setQty('1');
      setReason('');
      resetPurchase();
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo registrar');
    } finally {
      setBusy(false);
    }
  };

  const submitMozoInsumo = async () => {
    if (!mozoProductId) {
      setError('Elegí un insumo');
      return;
    }
    const quantity = Number(mozoQty);
    if (!quantity || quantity < 1) {
      setError('Cantidad inválida');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await api.createMozoInsumo({
        product_id: mozoProductId,
        quantity,
        reason: mozoReason.trim() || undefined,
      });
      setMozoQty('1');
      setMozoReason('');
      setError(null);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo registrar el insumo');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Stock" subtitle="Consulta y movimientos" icon="cube" />
      <View style={styles.tabs}>
        <Pressable style={[styles.tab, tab === 'productos' && styles.tabOn]} onPress={() => setTab('productos')}>
          <Text style={[styles.tabText, tab === 'productos' && styles.tabTextOn]}>Productos</Text>
        </Pressable>
        <Pressable
          style={[styles.tab, tab === 'movimientos' && styles.tabOn]}
          onPress={() => setTab('movimientos')}
        >
          <Text style={[styles.tabText, tab === 'movimientos' && styles.tabTextOn]}>Movimientos</Text>
        </Pressable>
        {canMozoInsumo ? (
          <Pressable
            style={[styles.tab, tab === 'insumos' && styles.tabOn]}
            onPress={() => setTab('insumos')}
          >
            <Text style={[styles.tabText, tab === 'insumos' && styles.tabTextOn]}>Insumos</Text>
          </Pressable>
        ) : null}
      </View>

      {error ? <Text style={styles.err}>{error}</Text> : null}

      {tab === 'productos' && (
        <TextInput
          style={styles.search}
          placeholder="Buscar producto"
          placeholderTextColor={colors.gray400}
          value={search}
          onChangeText={setSearch}
          onSubmitEditing={() => void load()}
        />
      )}

      {loading ? (
        <ActivityIndicator style={{ marginTop: 24 }} color={colors.teal500} />
      ) : tab === 'productos' ? (
        <FlatList
          data={rows}
          keyExtractor={(r) => String(r.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={<Text style={styles.empty}>Sin productos con stock</Text>}
          renderItem={({ item }) => (
            <Card style={{ marginBottom: 10 }}>
              <View style={styles.rowBetween}>
                <Text style={styles.name}>{item.name}</Text>
                {item.is_low_stock ? <Badge label="BAJO" /> : <Badge label="OK" />}
              </View>
              <Text style={styles.meta}>
                Stock {item.current_stock}
                {item.unit ? ` ${item.unit}` : ''} · mín {item.stock_minimum}
              </Text>
              {canWrite && (
                <PrimaryButton
                  title="Movimiento"
                  onPress={() => {
                    setType('ENTRADA');
                    resetPurchase();
                    setModal(item);
                  }}
                />
              )}
            </Card>
          )}
        />
      ) : tab === 'movimientos' ? (
        <FlatList
          data={movements}
          keyExtractor={(m) => String(m.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListHeaderComponent={
            <View style={{ marginBottom: 12, gap: 8 }}>
              <SectionLabel>Filtros</SectionLabel>
              <View style={styles.chips}>
                <Chip
                  label="Todos"
                  selected={filterProductId == null}
                  onPress={() => setFilterProductId(null)}
                />
                {rows.slice(0, 12).map((p) => (
                  <Chip
                    key={p.id}
                    label={p.name}
                    selected={filterProductId === p.id}
                    onPress={() => setFilterProductId(p.id)}
                  />
                ))}
              </View>
              <Field
                label="Desde (YYYY-MM-DD)"
                value={dateFrom}
                onChangeText={setDateFrom}
                placeholder="2026-01-01"
                autoCapitalize="none"
              />
              <Field
                label="Hasta (YYYY-MM-DD)"
                value={dateTo}
                onChangeText={setDateTo}
                placeholder="2026-12-31"
                autoCapitalize="none"
              />
              <PrimaryButton title="Aplicar filtros" variant="ghost" onPress={() => void load()} />
            </View>
          }
          ListEmptyComponent={<Text style={styles.empty}>Sin movimientos</Text>}
          renderItem={({ item }) => (
            <Card style={{ marginBottom: 10 }}>
              <View style={styles.rowBetween}>
                <Badge label={item.type} />
                <Text style={styles.qty}>x{item.quantity}</Text>
              </View>
              <Text style={styles.name}>{item.product ?? 'Producto'}</Text>
              <Text style={styles.meta}>
                {item.previous_stock ?? '—'} → {item.new_stock ?? '—'} · {item.user ?? ''}
              </Text>
              {item.reason ? <Text style={styles.meta}>{item.reason}</Text> : null}
              {item.purchase ? (
                <Text style={styles.meta}>
                  Compra: {item.purchase.supplier ?? '—'}
                  {item.purchase.unit_cost != null
                    ? ` · $${Number(item.purchase.unit_cost).toFixed(2)}`
                    : ''}
                  {item.purchase.purchase_date ? ` · ${item.purchase.purchase_date}` : ''}
                  {item.purchase.invoice_number ? ` · Fac ${item.purchase.invoice_number}` : ''}
                </Text>
              ) : null}
            </Card>
          )}
        />
      ) : (
        <FlatList
          data={[]}
          keyExtractor={() => 'insumos-form'}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListHeaderComponent={
            <Card>
              <AppText weight="bold">Ingreso de insumos</AppText>
              <AppText style={styles.meta}>Entrada simple sin datos de compra</AppText>
              <SectionLabel>Insumo</SectionLabel>
              <View style={styles.chips}>
                {insumos.map((p) => (
                  <Chip
                    key={p.id}
                    label={`${p.name}${p.current_stock != null ? ` (${p.current_stock})` : ''}`}
                    selected={mozoProductId === p.id}
                    onPress={() => setMozoProductId(p.id)}
                  />
                ))}
              </View>
              {insumos.length === 0 ? (
                <AppText style={styles.meta}>No hay insumos con stock</AppText>
              ) : null}
              <Field
                label="Cantidad"
                keyboardType="number-pad"
                value={mozoQty}
                onChangeText={setMozoQty}
              />
              <Field
                label="Motivo (opcional)"
                value={mozoReason}
                onChangeText={setMozoReason}
              />
              <PrimaryButton
                title={busy ? 'Guardando…' : 'Registrar ingreso'}
                onPress={() => void submitMozoInsumo()}
                disabled={busy || !mozoProductId}
              />
            </Card>
          }
          renderItem={() => null}
        />
      )}

      <Modal visible={!!modal} transparent animationType="slide">
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <Text style={styles.name}>{modal?.name}</Text>
            <View style={styles.typeRow}>
              {allowedTypes.map((t) => (
                <Pressable
                  key={t}
                  style={[styles.typeChip, type === t && styles.typeOn]}
                  onPress={() => setType(t)}
                >
                  <Text style={{ color: type === t ? '#fff' : colors.gray800, fontWeight: '700' }}>
                    {t}
                  </Text>
                </Pressable>
              ))}
            </View>
            <TextInput
              style={styles.search}
              keyboardType="number-pad"
              value={qty}
              onChangeText={setQty}
              placeholder="Cantidad"
            />
            <TextInput
              style={styles.search}
              value={reason}
              onChangeText={setReason}
              placeholder="Motivo (opcional)"
            />
            {type === 'ENTRADA' && canWrite ? (
              <>
                <SectionLabel>Compra (opcional)</SectionLabel>
                <View style={styles.chips}>
                  {suppliers.map((s) => (
                    <Chip
                      key={s.id}
                      label={s.name}
                      selected={supplierId === s.id}
                      onPress={() => setSupplierId(s.id)}
                    />
                  ))}
                </View>
                <Field
                  label="Costo unitario"
                  keyboardType="decimal-pad"
                  value={unitCost}
                  onChangeText={setUnitCost}
                />
                <Field
                  label="Fecha compra (YYYY-MM-DD)"
                  value={purchaseDate}
                  onChangeText={setPurchaseDate}
                  placeholder="2026-09-15"
                  autoCapitalize="none"
                />
                <Field
                  label="Factura (opcional)"
                  value={invoice}
                  onChangeText={setInvoice}
                />
              </>
            ) : null}
            <PrimaryButton title={busy ? 'Guardando…' : 'Registrar'} onPress={() => void submit()} disabled={busy} />
            <PrimaryButton
              title="Cancelar"
              variant="ghost"
              onPress={() => {
                setModal(null);
                resetPurchase();
              }}
            />
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  tabs: { flexDirection: 'row', padding: space.md, gap: 8 },
  tab: {
    flex: 1,
    minHeight: 44,
    borderRadius: radius.md,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabOn: { backgroundColor: colors.teal500, borderColor: colors.teal500 },
  tabText: { fontWeight: '600', color: colors.gray600, fontSize: 12 },
  tabTextOn: { color: '#fff' },
  search: {
    marginHorizontal: space.md,
    marginBottom: space.sm,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray200,
    minHeight: 48,
    paddingHorizontal: 12,
    fontSize: 16,
    color: colors.gray900,
  },
  err: { color: colors.danger, paddingHorizontal: space.md },
  empty: { textAlign: 'center', color: colors.gray500, marginTop: 40 },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  name: { fontWeight: '800', fontSize: 16, color: colors.gray900, flex: 1, marginRight: 8 },
  meta: { color: colors.gray600, marginVertical: 8 },
  qty: { fontWeight: '800', color: colors.teal600 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginVertical: 6 },
  modalBg: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  modalCard: {
    backgroundColor: colors.white,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    padding: space.lg,
    gap: 10,
  },
  typeRow: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  typeChip: {
    paddingHorizontal: 12,
    minHeight: 40,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray200,
    alignItems: 'center',
    justifyContent: 'center',
  },
  typeOn: { backgroundColor: colors.teal500, borderColor: colors.teal500 },
});
