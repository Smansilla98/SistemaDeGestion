import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import { Badge, Chip, PageHeader } from '../../src/ui/primitives';

function statusColor(status: string) {
  if (status === 'OCUPADA') return colors.amber;
  if (status === 'LIBRE') return colors.green;
  return colors.gray500;
}

export default function MesasScreen() {
  const { logout, user } = useAuth();
  const router = useRouter();
  const canOccupy = hasPermission(user, 'tables.write');
  const [tables, setTables] = useState<TableRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [filter, setFilter] = useState<'TODAS' | 'LIBRE' | 'OCUPADA'>('TODAS');

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

  const onPressTable = async (t: TableRow) => {
    if (t.status === 'LIBRE' && canOccupy) {
      setBusyId(t.id);
      try {
        await api.occupyTable(t.id);
        await load();
        router.push({ pathname: '/(tabs)/pedido', params: { tableId: String(t.id) } });
      } catch (e) {
        setError(e instanceof ApiError ? e.message : 'No se pudo ocupar');
      } finally {
        setBusyId(null);
      }
      return;
    }
    if (t.status === 'OCUPADA') {
      router.push({ pathname: '/(tabs)/pedido', params: { tableId: String(t.id) } });
    }
  };

  const visible = tables.filter((t) => filter === 'TODAS' || t.status === filter);

  return (
    <View style={styles.root}>
      <PageHeader title="Mesas" subtitle={user?.name} icon="grid" />
      <View style={styles.top}>
        <View style={styles.filters}>
          {(['TODAS', 'LIBRE', 'OCUPADA'] as const).map((f) => (
            <Chip key={f} label={f} selected={filter === f} onPress={() => setFilter(f)} />
          ))}
        </View>
        <Pressable onPress={() => void logout()}>
          <Text style={styles.logout}>Salir</Text>
        </Pressable>
      </View>

      {error && <Text style={styles.error}>{error}</Text>}

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
              onPress={() => void onPressTable(t)}
              disabled={busyId === t.id}
            >
              <Text style={styles.chipNum}>{t.number}</Text>
              <Badge label={t.status} />
              {t.sector ? <Text style={styles.sector}>{t.sector}</Text> : null}
              {busyId === t.id && <ActivityIndicator size="small" color={colors.teal500} />}
            </Pressable>
          ))}
          {visible.length === 0 && <Text style={styles.empty}>No hay mesas</Text>}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
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
  filters: { flexDirection: 'row', gap: 6 },
  logout: { color: colors.teal600, fontWeight: '700' },
  error: { color: colors.danger, padding: 12 },
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
  chipNum: { fontSize: 24, fontWeight: '800', color: colors.gray900 },
  sector: { fontSize: 11, color: colors.gray500 },
  empty: { color: colors.gray600, padding: 24 },
});
