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
import { useFocusEffect, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { CatalogSector, TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import { AppText, Badge, Chip, PageHeader, PrimaryButton } from '../../src/ui/primitives';

function statusColor(status: string) {
  if (status === 'OCUPADA') return colors.amber;
  if (status === 'LIBRE') return colors.green;
  return colors.gray500;
}

export default function MesasScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const canOccupy = hasPermission(user, 'tables.write');
  const canPay = hasPermission(user, 'cash.write');

  const [tables, setTables] = useState<TableRow[]>([]);
  const [sectors, setSectors] = useState<CatalogSector[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [statusFilter, setStatusFilter] = useState<'TODAS' | 'LIBRE' | 'OCUPADA' | 'RESERVADA'>('TODAS');
  const [sectorId, setSectorId] = useState<number | null>(null);
  const [transferFrom, setTransferFrom] = useState<TableRow | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [rows, layout] = await Promise.all([
        api.tables(sectorId ?? undefined),
        api.tablesLayout(sectorId ?? undefined).catch(() => null),
      ]);
      setTables(Array.isArray(rows) ? rows : []);
      const secs = (layout?.sectors ?? []).map((s) => ({
        id: s.id,
        name: s.name,
        description: null,
        is_active: true,
      }));
      setSectors(secs);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error al cargar mesas');
    } finally {
      setLoading(false);
    }
  }, [sectorId]);

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

  const visible = tables.filter((t) => statusFilter === 'TODAS' || t.status === statusFilter);

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
    Alert.alert(`Mesa ${t.number}`, t.sector ? `Sector: ${t.sector}` : undefined, buttons);
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Mesas" subtitle={user?.name} bi="table" />
      <View style={styles.top}>
        <View style={styles.filters}>
          {(['TODAS', 'LIBRE', 'OCUPADA', 'RESERVADA'] as const).map((f) => (
            <Chip key={f} label={f} selected={statusFilter === f} onPress={() => setStatusFilter(f)} />
          ))}
        </View>
      </View>

      {sectors.length > 0 ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.sectorRow}>
          <Chip label="Todos" selected={sectorId == null} onPress={() => setSectorId(null)} />
          {sectors.map((s) => (
            <Chip
              key={s.id}
              label={s.name}
              selected={sectorId === s.id}
              onPress={() => setSectorId(s.id)}
            />
          ))}
        </ScrollView>
      ) : null}

      <View style={styles.mapBtn}>
        <PrimaryButton
          title="Mapa del salón"
          icon="map-outline"
          variant="ghost"
          onPress={() => router.push('/tables/map' as Href)}
        />
      </View>

      {error ? (
        <AppText weight="medium" style={styles.error}>
          {error}
        </AppText>
      ) : null}

      {loading ? (
        <ActivityIndicator color={colors.teal500} style={{ marginTop: 40 }} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.grid}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        >
          {visible.map((t) => (
            <Pressable
              key={t.id}
              style={[styles.chip, { borderColor: statusColor(t.status) }]}
              onPress={() => router.push(`/table/${t.id}` as Href)}
              onLongPress={() => showActions(t)}
              disabled={busyId === t.id}
            >
              <AppText weight="bold" style={styles.chipNum}>
                {t.number}
              </AppText>
              <Badge label={t.status} />
              {t.sector ? (
                <AppText style={styles.sector}>{t.sector}</AppText>
              ) : null}
              {busyId === t.id && <ActivityIndicator size="small" color={colors.teal500} />}
            </Pressable>
          ))}
          {visible.length === 0 && (
            <AppText style={styles.empty}>No hay mesas</AppText>
          )}
        </ScrollView>
      )}

      <AppText style={styles.hint}>Mantener pulsado para acciones</AppText>

      <Modal visible={!!transferFrom} animationType="slide" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <AppText weight="bold" style={{ fontSize: 18 }}>
              Transferir mesa {transferFrom?.number}
            </AppText>
            <AppText style={styles.sector}>Elegí una mesa libre</AppText>
            <ScrollView style={{ maxHeight: 320 }}>
              {freeTables.map((t) => (
                <Pressable key={t.id} style={styles.pickRow} onPress={() => void doTransfer(t)}>
                  <AppText weight="bold">Mesa {t.number}</AppText>
                  {t.sector ? <AppText style={styles.sector}>{t.sector}</AppText> : null}
                </Pressable>
              ))}
              {freeTables.length === 0 ? (
                <AppText style={styles.empty}>No hay mesas libres</AppText>
              ) : null}
            </ScrollView>
            <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setTransferFrom(null)} />
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  top: {
    paddingHorizontal: space.md,
    paddingVertical: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
  filters: { flexDirection: 'row', gap: 6, flexWrap: 'wrap', flex: 1 },
  logout: { color: colors.teal600 },
  error: { color: colors.danger, padding: 12 },
  sectorRow: { paddingHorizontal: space.md, paddingVertical: 8, gap: 8 },
  mapBtn: { paddingHorizontal: space.md, paddingBottom: 4 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, padding: 16 },
  chip: {
    width: '30%',
    minWidth: 100,
    minHeight: 100,
    borderWidth: 2,
    borderRadius: radius.xl,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 8,
    gap: 6,
  },
  chipNum: { fontSize: 24, color: colors.gray900 },
  sector: { fontSize: 11, color: colors.gray500 },
  empty: { color: colors.gray600, padding: 24 },
  hint: { textAlign: 'center', color: colors.gray400, fontSize: 12, paddingBottom: 12 },
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
    gap: 8,
  },
  pickRow: {
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
});
