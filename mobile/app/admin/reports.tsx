import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { downloadAndShare } from '../../src/api/download';
import type { ProductsReport, SalesReport, StaffReport } from '../../src/api/types';
import { colors, space } from '../../src/theme';
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

type Tab = 'ventas' | 'productos' | 'mozos';

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

function daysAgoISO(n: number) {
  const d = new Date();
  d.setDate(d.getDate() - n);
  return d.toISOString().slice(0, 10);
}

function qs(from: string, to: string) {
  return `date_from=${encodeURIComponent(from)}&date_to=${encodeURIComponent(to)}`;
}

export default function AdminReportsScreen() {
  const router = useRouter();
  const [tab, setTab] = useState<Tab>('ventas');
  const [from, setFrom] = useState(daysAgoISO(30));
  const [to, setTo] = useState(todayISO());
  const [sales, setSales] = useState<SalesReport | null>(null);
  const [products, setProducts] = useState<ProductsReport | null>(null);
  const [staff, setStaff] = useState<StaffReport | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [s, p, st] = await Promise.all([
        api.reportsSales(from, to),
        api.reportsProducts(from, to),
        api.reportsStaff(from, to),
      ]);
      setSales(s);
      setProducts(p);
      setStaff(st);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error reportes');
    } finally {
      setLoading(false);
    }
  }, [from, to]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

  const exportFile = async (path: string, filename: string) => {
    setExporting(true);
    try {
      await downloadAndShare(path, filename);
    } catch (e) {
      Alert.alert('Exportar', e instanceof Error ? e.message : 'No se pudo exportar');
    } finally {
      setExporting(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Reportes" subtitle="Ventas, productos y mozos" icon="bar-chart" />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
      >
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />

        <View style={styles.tabs}>
          <Chip label="Ventas" selected={tab === 'ventas'} onPress={() => setTab('ventas')} />
          <Chip
            label="Productos"
            selected={tab === 'productos'}
            onPress={() => setTab('productos')}
          />
          <Chip label="Mozos" selected={tab === 'mozos'} onPress={() => setTab('mozos')} />
        </View>

        <Field label="Desde (YYYY-MM-DD)" value={from} onChangeText={setFrom} />
        <Field label="Hasta (YYYY-MM-DD)" value={to} onChangeText={setTo} />
        <PrimaryButton
          title="Consultar"
          onPress={() => {
            setLoading(true);
            void load();
          }}
        />

        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}
        {loading ? <ActivityIndicator color={colors.teal500} /> : null}

        {!loading && tab === 'ventas' && sales ? (
          <>
            <Card>
              <AppText weight="bold" style={styles.big}>
                ${Number(sales.total_sales).toFixed(0)}
              </AppText>
              <AppText style={styles.meta}>
                {sales.total_orders} pedidos · {sales.date_from} → {sales.date_to}
              </AppText>
            </Card>
            <SectionLabel>Por método</SectionLabel>
            {sales.sales_by_method.map((m) => (
              <Card key={m.payment_method} style={{ marginBottom: 8 }}>
                <View style={styles.row}>
                  <Badge label={m.payment_method} />
                  <AppText weight="bold">${Number(m.total).toFixed(0)}</AppText>
                </View>
              </Card>
            ))}
            <SectionLabel>Por día</SectionLabel>
            {sales.sales_by_day.slice(-14).map((d) => (
              <View key={d.day} style={styles.dayRow}>
                <AppText style={{ flex: 1 }}>{d.day}</AppText>
                <AppText style={styles.meta}>{d.count} ped.</AppText>
                <AppText weight="bold">${Number(d.total).toFixed(0)}</AppText>
              </View>
            ))}
            <SectionLabel>Exportar</SectionLabel>
            <PrimaryButton
              title="Excel ventas"
              icon="download-outline"
              loading={exporting}
              onPress={() =>
                void exportFile(`/reports/sales/export?${qs(from, to)}`, 'ventas.xlsx')
              }
            />
            <PrimaryButton
              title="PDF ventas"
              variant="ghost"
              icon="document-outline"
              loading={exporting}
              onPress={() =>
                void exportFile(`/reports/sales/export-pdf?${qs(from, to)}`, 'ventas.pdf')
              }
            />
          </>
        ) : null}

        {!loading && tab === 'productos' && products ? (
          <>
            <AppText style={styles.meta}>
              {products.date_from} → {products.date_to}
            </AppText>
            <SectionLabel>Top productos</SectionLabel>
            {products.top_products.length === 0 ? (
              <AppText style={styles.meta}>Sin datos en el período</AppText>
            ) : (
              products.top_products.map((p) => (
                <Card key={p.id} style={{ marginBottom: 8 }}>
                  <View style={styles.row}>
                    <AppText weight="bold" style={{ flex: 1 }}>
                      {p.name}
                    </AppText>
                    <AppText weight="bold" style={{ color: colors.teal600 }}>
                      ×{p.total_quantity}
                    </AppText>
                  </View>
                  <AppText style={styles.meta}>
                    ${Number(p.total_revenue).toFixed(0)} ingresos
                  </AppText>
                </Card>
              ))
            )}
            <SectionLabel>Exportar</SectionLabel>
            <PrimaryButton
              title="Excel productos"
              icon="download-outline"
              loading={exporting}
              onPress={() =>
                void exportFile(`/reports/products/export?${qs(from, to)}`, 'productos.xlsx')
              }
            />
          </>
        ) : null}

        {!loading && tab === 'mozos' && staff ? (
          <>
            <AppText style={styles.meta}>
              {staff.date_from} → {staff.date_to}
            </AppText>
            <SectionLabel>Ventas por mozo</SectionLabel>
            {staff.sales_by_staff.length === 0 ? (
              <AppText style={styles.meta}>Sin datos en el período</AppText>
            ) : (
              staff.sales_by_staff.map((w) => (
                <Card key={w.id} style={{ marginBottom: 8 }}>
                  <View style={styles.row}>
                    <AppText weight="bold" style={{ flex: 1 }}>
                      {w.name}
                    </AppText>
                    <AppText weight="bold">${Number(w.total_sales).toFixed(0)}</AppText>
                  </View>
                  <AppText style={styles.meta}>{w.total_orders} pedidos</AppText>
                </Card>
              ))
            )}
            <SectionLabel>Exportar</SectionLabel>
            <PrimaryButton
              title="Excel mozos"
              icon="download-outline"
              loading={exporting}
              onPress={() =>
                void exportFile(`/reports/staff/export?${qs(from, to)}`, 'mozos.xlsx')
              }
            />
          </>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, paddingBottom: 48, gap: 8 },
  tabs: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  err: { color: colors.danger },
  big: { fontSize: 32, color: colors.teal600 },
  meta: { color: colors.gray500, marginTop: 4 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  dayRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
});
