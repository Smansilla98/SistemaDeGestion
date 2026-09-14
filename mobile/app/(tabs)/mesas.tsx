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
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { colors } from '../../src/theme';

function statusColor(status: string) {
  if (status === 'OCUPADA') return colors.amber;
  if (status === 'LIBRE') return colors.green;
  return colors.gray600;
}

export default function MesasScreen() {
  const { logout, user } = useAuth();
  const [tables, setTables] = useState<TableRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);

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

  const occupy = async (id: number) => {
    setBusyId(id);
    try {
      await api.occupyTable(id);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo ocupar');
    } finally {
      setBusyId(null);
    }
  };

  return (
    <View style={styles.root}>
      <View style={styles.top}>
        <Text style={styles.hello}>{user?.name}</Text>
        <Pressable onPress={() => void logout()}>
          <Text style={styles.logout}>Salir</Text>
        </Pressable>
      </View>

      {error && <Text style={styles.error}>{error}</Text>}

      {loading ? (
        <ActivityIndicator color={colors.teal700} style={{ marginTop: 40 }} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.grid}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        >
          {tables.map((t) => (
            <Pressable
              key={t.id}
              style={[styles.chip, { borderColor: statusColor(t.status) }]}
              onPress={() => void occupy(t.id)}
              disabled={busyId === t.id}
            >
              <Text style={styles.chipNum}>{t.number}</Text>
              <Text style={[styles.chipStatus, { color: statusColor(t.status) }]}>{t.status}</Text>
              {busyId === t.id && <ActivityIndicator size="small" color={colors.teal700} />}
            </Pressable>
          ))}
          {tables.length === 0 && <Text style={styles.empty}>No hay mesas</Text>}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  top: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray200,
  },
  hello: { fontWeight: '700', color: colors.gray900 },
  logout: { color: colors.teal700, fontWeight: '600' },
  error: { color: colors.danger, padding: 12 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, padding: 16 },
  chip: {
    width: '30%',
    minWidth: 96,
    minHeight: 88,
    borderWidth: 2,
    borderRadius: 14,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 8,
  },
  chipNum: { fontSize: 22, fontWeight: '800', color: colors.gray900 },
  chipStatus: { fontSize: 11, marginTop: 4, fontWeight: '600' },
  empty: { color: colors.gray600, padding: 24 },
});
