import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { StockResetPreview } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { font, fx } from '../../src/theme';
import { FxHeader, Surface } from '../../src/ui/fintech';
import { AppText, Field, PrimaryButton } from '../../src/ui/primitives';

type Product = StockResetPreview['products'][number];

/**
 * Puesta a punto (punto de partida): frena los pedidos en curso, baja lo activo
 * y deja el stock en las cantidades reales contadas.
 */
export default function StockResetScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const allowed = hasPermission(user, 'stock.reset');

  const [preview, setPreview] = useState<StockResetPreview | null>(null);
  // Texto por producto; vacío = no tocar ese stock.
  const [counts, setCounts] = useState<Record<number, string>>({});
  const [search, setSearch] = useState('');
  const [notes, setNotes] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      const data = await api.stockResetPreview();
      setPreview(data);
      setCounts(Object.fromEntries(data.products.map((p) => [p.id, String(p.current_stock)])));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo cargar la puesta a punto');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      if (allowed) void load();
      else setLoading(false);
    }, [allowed, load]),
  );

  const visible = useMemo(() => {
    const needle = search.trim().toLowerCase();
    const products = preview?.products ?? [];
    return needle ? products.filter((p) => p.name.toLowerCase().includes(needle)) : products;
  }, [preview, search]);

  const expected = preview?.confirmation ?? 'PUESTA A PUNTO';
  const confirmed = confirmation.trim().toUpperCase() === expected;

  const execute = async () => {
    if (!preview) return;
    setSaving(true);
    setError(null);
    try {
      const result = await api.stockReset({
        counts: preview.products
          .filter((p) => (counts[p.id] ?? '').trim() !== '')
          .map((p) => ({ product_id: p.id, quantity: Math.max(0, Math.floor(Number(counts[p.id]) || 0)) })),
        confirmation: confirmation.trim(),
        ...(notes.trim() ? { notes: notes.trim() } : {}),
      });
      Alert.alert(
        `Puesta a punto #${result.id} lista`,
        `${result.orders_cancelled} pedido(s) frenados, ${result.tables_freed} mesa(s) liberadas y ${result.products_adjusted} stock(s) ajustados.`,
        [{ text: 'Listo', onPress: () => router.back() }],
      );
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo hacer la puesta a punto');
    } finally {
      setSaving(false);
    }
  };

  const askConfirm = () =>
    Alert.alert(
      '¿Hacer la puesta a punto?',
      `Se anulan ${preview?.active_orders ?? 0} pedido(s) en curso y se liberan las mesas. No se puede deshacer.`,
      [
        { text: 'Cancelar', style: 'cancel' },
        { text: 'Hacer puesta a punto', style: 'destructive', onPress: () => void execute() },
      ],
    );

  const renderItem = ({ item }: { item: Product }) => {
    const raw = counts[item.id] ?? '';
    const diff = raw.trim() === '' ? null : (Math.floor(Number(raw)) || 0) - item.current_stock;
    return (
      <View style={styles.row}>
        <View style={styles.rowInfo}>
          <AppText weight="semibold" style={styles.rowName}>
            {item.name}
          </AppText>
          <AppText style={styles.rowMeta}>
            {item.type === 'INSUMO' ? 'Insumo' : 'Producto'} · en sistema {item.current_stock}
            {item.unit ? ` ${item.unit}` : ''}
          </AppText>
        </View>
        <View style={styles.rowInputWrap}>
          <TextInput
            style={styles.input}
            keyboardType="number-pad"
            value={raw}
            onChangeText={(t) => setCounts((prev) => ({ ...prev, [item.id]: t.replace(/[^0-9]/g, '') }))}
            placeholder="—"
            placeholderTextColor={fx.inkFaint}
            accessibilityLabel={`Stock real de ${item.name}`}
            selectTextOnFocus
          />
          <AppText
            weight="semibold"
            style={[
              styles.diff,
              diff !== null && diff > 0 && { color: fx.success },
              diff !== null && diff < 0 && { color: fx.danger },
            ]}
          >
            {diff === null ? 'sin cambios' : diff > 0 ? `+${diff}` : String(diff)}
          </AppText>
        </View>
      </View>
    );
  };

  if (loading) {
    return (
      <View style={[styles.root, styles.center]}>
        <ActivityIndicator color={fx.brand} />
      </View>
    );
  }

  if (!allowed) {
    return (
      <View style={styles.root}>
        <FxHeader title="Puesta a punto" onBack={() => router.back()} />
        <AppText style={styles.message}>No tenés permiso para hacer la puesta a punto.</AppText>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView style={styles.root} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <FxHeader title="Puesta a punto" subtitle="Punto de partida del local" onBack={() => router.back()} />
      <FlatList
        data={visible}
        keyExtractor={(p) => String(p.id)}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
        keyboardShouldPersistTaps="handled"
        ListHeaderComponent={
          <View style={styles.block}>
            {error ? (
              <AppText weight="medium" style={styles.error}>
                {error}
              </AppText>
            ) : null}
            <Surface style={styles.danger}>
              <AppText weight="bold" style={styles.dangerTitle}>
                Qué se va a bajar
              </AppText>
              {[
                ['Pedidos en curso', String(preview?.active_orders ?? 0)],
                ['Total sin cobrar', `$${((preview?.active_orders_total_cents ?? 0) / 100).toFixed(2)}`],
                ['Sesiones de mesa abiertas', String(preview?.open_sessions ?? 0)],
                ['Mesas ocupadas', String(preview?.busy_tables ?? 0)],
              ].map(([label, value]) => (
                <View key={label} style={styles.summaryRow}>
                  <AppText style={styles.summaryLabel}>{label}</AppText>
                  <AppText weight="bold" style={styles.summaryValue}>
                    {value}
                  </AppText>
                </View>
              ))}
              <AppText style={styles.dangerNote}>
                Los pedidos en curso se anulan y las mesas quedan libres. La caja no se toca.
              </AppText>
            </Surface>
            <AppText weight="bold" style={styles.sectionTitle}>
              Conteo real de stock
            </AppText>
            <AppText style={styles.hint}>
              Cada campo arranca con lo que figura hoy. Lo que dejes vacío no se modifica.
            </AppText>
            <Field placeholder="Buscar producto o insumo…" value={search} onChangeText={setSearch} />
          </View>
        }
        ListEmptyComponent={<AppText style={styles.message}>No hay productos con control de stock.</AppText>}
        ListFooterComponent={
          <View style={styles.block}>
            <Field label="Motivo (opcional)" value={notes} onChangeText={setNotes} placeholder="Ej. apertura de temporada" />
            <Field
              label={`Para confirmar, escribí ${expected}`}
              value={confirmation}
              onChangeText={setConfirmation}
              placeholder={expected}
              autoCapitalize="characters"
              autoCorrect={false}
            />
            <PrimaryButton
              title="Hacer puesta a punto"
              variant="danger"
              icon="flag-outline"
              onPress={askConfirm}
              disabled={!confirmed || saving}
              loading={saving}
            />
          </View>
        }
      />
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  center: { justifyContent: 'center', alignItems: 'center' },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 48 },
  block: { gap: 12, paddingVertical: 12 },
  error: { color: fx.danger },
  message: { color: fx.inkMuted, textAlign: 'center', padding: 24 },
  danger: { borderWidth: 1, borderColor: fx.danger, gap: 8 },
  dangerTitle: { color: fx.danger, fontSize: 16 },
  dangerNote: { color: fx.inkMuted, fontSize: 13, marginTop: 4 },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 12 },
  summaryLabel: { color: fx.ink, fontSize: 15, flex: 1 },
  summaryValue: { color: fx.ink, fontSize: 15 },
  sectionTitle: { color: fx.ink, fontSize: 18, marginTop: 8 },
  hint: { color: fx.inkMuted, fontSize: 13 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: fx.hairline,
  },
  rowInfo: { flex: 1, gap: 2 },
  rowName: { color: fx.ink, fontSize: 16 },
  rowMeta: { color: fx.inkMuted, fontSize: 13 },
  rowInputWrap: { alignItems: 'flex-end', gap: 4 },
  input: {
    width: 96,
    minHeight: 48,
    borderRadius: fx.radius.sm,
    borderWidth: 1,
    borderColor: fx.hairline,
    backgroundColor: fx.surface,
    paddingHorizontal: 12,
    fontSize: 17,
    textAlign: 'right',
    color: fx.ink,
    fontFamily: font.regular,
  },
  diff: { fontSize: 12, color: fx.inkFaint },
});
