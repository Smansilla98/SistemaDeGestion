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
import type { DashboardPayload } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import { Badge, Card, PageHeader, PrimaryButton, StatTile } from '../../src/ui/primitives';

export default function DashboardScreen() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const [data, setData] = useState<DashboardPayload | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      setData(await api.dashboard());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error dashboard');
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

  const ops = data?.operational;
  const mgmt = data?.management;

  return (
    <View style={styles.root}>
      <PageHeader
        title="Conurbania"
        subtitle={`${user?.name ?? ''} · ${user?.role ?? ''}`}
        icon="home"
      />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
      >
        <View style={styles.topRow}>
          <Pressable onPress={() => void logout()}>
            <Text style={styles.logout}>Cerrar sesión</Text>
          </Pressable>
        </View>

        {error && <Text style={styles.err}>{error}</Text>}
        {loading && <ActivityIndicator color={colors.teal500} />}

        {ops && (
          <View style={styles.grid}>
            <StatTile label="Mesas libres" value={ops.mesas_libres} accent={colors.green} icon="grid-outline" />
            <StatTile label="Mesas ocupadas" value={ops.mesas_ocupadas} accent={colors.amber} icon="people-outline" />
            <StatTile label="Pedidos activos" value={ops.pedidos_pendientes} icon="receipt-outline" />
            <StatTile
              label="Ventas sesión"
              value={`$${Number(ops.ventas_sesion).toFixed(0)}`}
              icon="cash-outline"
            />
          </View>
        )}

        {ops && (
          <Card style={{ marginTop: space.md }}>
            <Text style={styles.section}>Estado</Text>
            <View style={styles.row}>
              <Badge label={ops.tiene_sesion_abierta ? 'CAJA ABIERTA' : 'SIN CAJA'} />
              {ops.low_stock_products > 0 && (
                <Badge label={`${ops.low_stock_products} STOCK BAJO`} />
              )}
            </View>
          </Card>
        )}

        {mgmt && (
          <Card style={{ marginTop: space.md }}>
            <Text style={styles.section}>Gerencia</Text>
            <Text style={styles.line}>Ventas hoy: ${Number(mgmt.ventas_hoy).toFixed(2)}</Text>
            <Text style={styles.line}>
              Stock OK / bajo: {mgmt.stock_ok_products} / {mgmt.low_stock_products}
            </Text>
            <Text style={styles.line}>Cajas abiertas: {mgmt.open_cash_sessions}</Text>
            {(mgmt.recent_stock_movements ?? []).slice(0, 3).map((m) => (
              <Text key={m.id} style={styles.mov}>
                {m.type} · {m.product} · x{m.quantity}
              </Text>
            ))}
          </Card>
        )}

        <View style={styles.actions}>
          {hasPermission(user, 'tables.read') && (
            <PrimaryButton title="Ir a mesas" icon="grid" onPress={() => router.push('/(tabs)/mesas' as const)} />
          )}
          {hasPermission(user, 'stock.read') && (
            <PrimaryButton title="Stock" icon="cube-outline" variant="ghost" onPress={() => router.push('/(tabs)/stock' as never)} />
          )}
          {hasPermission(user, 'cash.read') && (
            <PrimaryButton title="Caja" icon="cash-outline" variant="ghost" onPress={() => router.push('/(tabs)/caja' as const)} />
          )}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, paddingBottom: 40 },
  topRow: { flexDirection: 'row', justifyContent: 'flex-end', marginBottom: 8 },
  logout: { color: colors.teal600, fontWeight: '700' },
  err: { color: colors.danger, marginBottom: 8 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  section: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.gray500,
    textTransform: 'uppercase',
    marginBottom: 8,
  },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  line: { color: colors.gray800, marginBottom: 4 },
  mov: { color: colors.gray600, fontSize: 12, marginTop: 4 },
  actions: { marginTop: space.lg, gap: 10 },
});
