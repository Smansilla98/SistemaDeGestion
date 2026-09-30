import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { AppText, Chip } from '../../src/ui/primitives';
import {
  FadeIn,
  FxHeader,
  MesasSkeleton,
  ModalSheet,
  StatusDot,
  Surface,
  SwipeAction,
} from '../../src/ui/fintech';

export default function MesasScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const canOccupy = hasPermission(user, 'tables.write');
  const canPay = hasPermission(user, 'cash.write');

  const [tables, setTables] = useState<TableRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [statusFilter, setStatusFilter] = useState<'TODAS' | 'LIBRE' | 'OCUPADA' | 'RESERVADA'>('TODAS');
  const [transferFrom, setTransferFrom] = useState<TableRow | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const rows = await api.tables();
      setTables(Array.isArray(rows) ? rows : []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error al cargar mesas');
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

  const freeTables = useMemo(
    () => tables.filter((t) => t.status === 'LIBRE'),
    [tables],
  );

  const visible = useMemo(
    () => tables.filter((t) => statusFilter === 'TODAS' || t.status === statusFilter),
    [tables, statusFilter],
  );

  const occupy = async (t: TableRow) => {
    setBusyId(t.id);
    try {
      await api.occupyTable(t.id);
      await load();
      router.push(`/table/${t.id}` as Href);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo ocupar');
    } finally {
      setBusyId(null);
    }
  };

  const free = async (t: TableRow) => {
    setBusyId(t.id);
    try {
      await api.freeTable(t.id);
      await load();
    } catch (e) {
      Alert.alert('No se pudo liberar', e instanceof ApiError ? e.message : 'Error');
    } finally {
      setBusyId(null);
    }
  };

  const doTransfer = async (target: TableRow) => {
    if (!transferFrom) return;
    setBusyId(transferFrom.id);
    try {
      await api.transferTable(transferFrom.id, target.id);
      setTransferFrom(null);
      await load();
      router.push(`/table/${target.id}` as Href);
    } catch (e) {
      Alert.alert('Transferencia', e instanceof ApiError ? e.message : 'Error');
    } finally {
      setBusyId(null);
    }
  };

  const showActions = (t: TableRow) => {
    const buttons: Array<{ text: string; style?: 'cancel' | 'destructive'; onPress?: () => void }> = [
      { text: 'Ver detalle', onPress: () => router.push(`/table/${t.id}` as Href) },
    ];
    if (t.status === 'LIBRE' && canOccupy) {
      buttons.push({ text: 'Ocupar', onPress: () => void occupy(t) });
      buttons.push({
        text: 'Reservar',
        onPress: () => router.push(`/tables/reserve?id=${t.id}` as Href),
      });
    }
    if (t.status === 'OCUPADA' && canOccupy) {
      buttons.push({ text: 'Liberar', style: 'destructive', onPress: () => void free(t) });
      buttons.push({ text: 'Transferir', onPress: () => setTransferFrom(t) });
      buttons.push({
        text: 'Nuevo pedido',
        onPress: () =>
          router.push({ pathname: '/(tabs)/pedido', params: { tableId: String(t.id) } }),
      });
    }
    if (t.status === 'OCUPADA' && canPay) {
      buttons.push({
        text: 'Cobrar (caja)',
        onPress: () =>
          router.push({ pathname: '/(tabs)/caja', params: { tableId: String(t.id) } } as never),
      });
    }
    if (canOccupy) {
      buttons.push({
        text: 'Editar mesa',
        onPress: () => router.push(`/tables/edit?id=${t.id}` as Href),
      });
    }
    buttons.push({ text: 'Cancelar', style: 'cancel' });
    Alert.alert(`Mesa ${t.number}`, undefined, buttons);
  };

  const swipeFor = (t: TableRow) => {
    const left: Array<{ label: string; onPress: () => void; tone?: 'brand' | 'danger' | 'neutral' }> = [];
    const right: Array<{ label: string; onPress: () => void; tone?: 'brand' | 'danger' | 'neutral' }> = [];

    if (t.status === 'LIBRE' && canOccupy) {
      left.push({ label: 'Ocupar', onPress: () => void occupy(t), tone: 'brand' });
    }
    if (t.status === 'OCUPADA' && canOccupy) {
      left.push({
        label: 'Pedido',
        onPress: () =>
          router.push({ pathname: '/(tabs)/pedido', params: { tableId: String(t.id) } }),
        tone: 'brand',
      });
      right.push({ label: 'Liberar', onPress: () => void free(t), tone: 'danger' });
    }
    if (t.status === 'OCUPADA' && canPay) {
      right.push({
        label: 'Cobrar',
        onPress: () =>
          router.push({ pathname: '/(tabs)/caja', params: { tableId: String(t.id) } } as never),
        tone: 'neutral',
      });
    }
    return { left, right };
  };

  const renderItem = ({ item: t }: { item: TableRow }) => {
    const { left, right } = swipeFor(t);
    return (
      <View style={styles.itemWrap}>
        <SwipeAction leftActions={left} rightActions={right}>
          <Surface style={styles.card} onPress={() => showActions(t)}>
            <View style={styles.cardInner}>
              <View style={styles.cardTop}>
                <AppText weight="bold" style={styles.num}>
                  {t.number}
                </AppText>
                {busyId === t.id ? (
                  <ActivityIndicator size="small" color={fx.brand} />
                ) : (
                  <StatusDot status={t.status} />
                )}
              </View>
              <AppText style={styles.cap}>
                {t.capacity ? `${t.capacity} pers.` : '—'}
              </AppText>
            </View>
          </Surface>
        </SwipeAction>
      </View>
    );
  };

  return (
    <View style={styles.root}>
      <FxHeader title="Mesas" subtitle={user?.name ?? undefined} />

      <View style={styles.filters}>
        {(['TODAS', 'LIBRE', 'OCUPADA', 'RESERVADA'] as const).map((f) => (
          <Chip key={f} label={f} selected={statusFilter === f} onPress={() => setStatusFilter(f)} />
        ))}
      </View>

      {error ? (
        <AppText weight="medium" style={styles.error}>
          {error}
        </AppText>
      ) : null}

      {loading && tables.length === 0 ? (
        <MesasSkeleton />
      ) : (
        <FadeIn style={{ flex: 1 }}>
          <FlatList
            data={visible}
            keyExtractor={(t) => String(t.id)}
            numColumns={2}
            columnWrapperStyle={styles.cols}
            contentContainerStyle={styles.list}
            refreshControl={
              <RefreshControl refreshing={false} onRefresh={() => void load()} />
            }
            ListEmptyComponent={
              <AppText style={styles.empty}>No hay mesas</AppText>
            }
            ListFooterComponent={
              <AppText style={styles.hint}>Tocá una mesa para ver acciones · Deslizá para atajos</AppText>
            }
            renderItem={renderItem}
            initialNumToRender={12}
            windowSize={7}
            removeClippedSubviews
          />
        </FadeIn>
      )}

      <ModalSheet
        visible={!!transferFrom}
        title={`Transferir mesa ${transferFrom?.number ?? ''}`}
        onClose={() => setTransferFrom(null)}
      >
        <AppText style={styles.cap}>Elegí una mesa libre</AppText>
        {freeTables.map((t) => (
          <Pressable key={t.id} style={styles.pickRow} onPress={() => void doTransfer(t)}>
            <AppText weight="bold" style={{ color: fx.ink, fontSize: 17 }}>
              Mesa {t.number}
            </AppText>
          </Pressable>
        ))}
        {freeTables.length === 0 ? (
          <AppText style={styles.empty}>No hay mesas libres</AppText>
        ) : null}
      </ModalSheet>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  filters: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    paddingHorizontal: fx.space.md,
    paddingBottom: 12,
  },
  error: { color: fx.danger, paddingHorizontal: fx.space.md },
  list: { paddingHorizontal: fx.space.md, paddingBottom: 32, gap: 0 },
  cols: { gap: 14, marginBottom: 14 },
  itemWrap: { flex: 1 },
  card: { minHeight: 110 },
  cardInner: { gap: 10 },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  num: {
    fontSize: 28,
    color: fx.ink,
    letterSpacing: -0.8,
  },
  cap: { fontSize: 13, color: fx.inkFaint },
  empty: { color: fx.inkMuted, padding: 24, textAlign: 'center' },
  hint: {
    textAlign: 'center',
    color: fx.inkFaint,
    fontSize: 12,
    paddingVertical: 16,
  },
  pickRow: {
    paddingVertical: 16,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: fx.hairline,
  },
});
