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
import { Badge, Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

type MovType = 'ENTRADA' | 'SALIDA' | 'AJUSTE';

export default function StockScreen() {
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'stock.write');
  const isMozo = user?.role === 'MOZO';

  const [tab, setTab] = useState<'productos' | 'movimientos'>('productos');
  const [rows, setRows] = useState<StockRow[]>([]);
  const [movements, setMovements] = useState<StockMovementRow[]>([]);
  const [search, setSearch] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState<StockRow | null>(null);
  const [type, setType] = useState<MovType>('ENTRADA');
  const [qty, setQty] = useState('1');
  const [reason, setReason] = useState('');
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
        api.stockMovements(),
      ]);
      setRows(Array.isArray(stock) ? stock : []);
      setMovements(Array.isArray(movs) ? movs : []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error stock');
    } finally {
      setLoading(false);
    }
  }, [search]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

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
      await api.createStockMovement({
        product_id: modal.id,
        type,
        quantity,
        reason: reason || undefined,
      });
      setModal(null);
      setQty('1');
      setReason('');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo registrar');
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
      </View>

      {error && <Text style={styles.err}>{error}</Text>}

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
                    setModal(item);
                  }}
                />
              )}
            </Card>
          )}
        />
      ) : (
        <FlatList
          data={movements}
          keyExtractor={(m) => String(m.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
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
            </Card>
          )}
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
            <PrimaryButton title={busy ? 'Guardando…' : 'Registrar'} onPress={() => void submit()} disabled={busy} />
            <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setModal(null)} />
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
  tabText: { fontWeight: '600', color: colors.gray600 },
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
