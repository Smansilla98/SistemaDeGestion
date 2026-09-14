import { useCallback, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { CashSessionDetail } from '../../src/api/types';
import { colors, space } from '../../src/theme';
import {
  AppText,
  Badge,
  Card,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

export default function CashSessionDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [data, setData] = useState<CashSessionDetail | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    if (!id) return;
    setError(null);
    try {
      setData(await api.cashSessionDetail(Number(id)));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error sesión');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  const session = data?.session as {
    status?: string;
    cash_register?: { name?: string };
    user?: { name?: string };
    initial_amount?: number;
    final_amount?: number | null;
    opened_at?: string;
  } | undefined;

  return (
    <View style={styles.root}>
      <PageHeader
        title={session?.cash_register?.name ?? `Sesión #${id}`}
        subtitle={session?.status}
        icon="cash"
      />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
      >
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}

        {data ? (
          <>
            <Card>
              {session?.status ? <Badge label={session.status} /> : null}
              <AppText style={styles.meta}>Cajero: {session?.user?.name ?? '—'}</AppText>
              <AppText>Inicial ${Number(session?.initial_amount ?? 0).toFixed(2)}</AppText>
              {session?.final_amount != null ? (
                <AppText>Final ${Number(session.final_amount).toFixed(2)}</AppText>
              ) : null}
              <AppText weight="bold" style={styles.total}>
                Esperado ${Number(data.expected_amount).toFixed(2)}
              </AppText>
              <AppText style={styles.meta}>
                Ventas ${Number(data.sales_total).toFixed(2)} · Ing ${Number(data.ingresos).toFixed(2)} ·
                Egr ${Number(data.egresos).toFixed(2)}
              </AppText>
            </Card>

            <SectionLabel>Pagos</SectionLabel>
            {data.payments.map((p) => (
              <Card key={p.id} style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <Badge label={p.payment_method} />
                  <AppText weight="bold">${Number(p.amount).toFixed(2)}</AppText>
                </View>
                <AppText style={styles.meta}>
                  {p.order_number ?? '—'} · Mesa {p.table ?? '—'}
                </AppText>
              </Card>
            ))}
            {data.payments.length === 0 ? (
              <AppText style={styles.meta}>Sin pagos</AppText>
            ) : null}

            <SectionLabel>Movimientos</SectionLabel>
            {data.movements.map((m) => (
              <Card key={m.id} style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <Badge label={m.type} />
                  <AppText weight="bold">${Number(m.amount).toFixed(2)}</AppText>
                </View>
                <AppText style={styles.meta}>{m.description}</AppText>
              </Card>
            ))}
            {data.movements.length === 0 ? (
              <AppText style={styles.meta}>Sin movimientos</AppText>
            ) : null}
          </>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, paddingBottom: 48, gap: 6 },
  err: { color: colors.danger },
  meta: { color: colors.gray500, fontSize: 13, marginTop: 4 },
  total: { fontSize: 22, color: colors.teal600, marginTop: 8 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
});
