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
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { downloadAndShare } from '../../src/api/download';
import type {
  CashSessionRow,
  ProductsReport,
  SalesReport,
  StaffReport,
} from '../../src/api/types';
import { fx } from '../../src/theme';
import {
  AppText,
  Badge,
  Chip,
  Field,
  PrimaryButton,
} from '../../src/ui/primitives';
import { FxHeader, HeroMetric, Surface } from '../../src/ui/fintech';

type Tab = 'ventas' | 'productos' | 'mozos' | 'caja';

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
  const [sessions, setSessions] = useState<CashSessionRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [s, p, st, sess] = await Promise.all([
        api.reportsSales(from, to),
        api.reportsProducts(from, to),
        api.reportsStaff(from, to),
        api.cashSessions().catch(() => [] as CashSessionRow[]),
      ]);
      setSales(s);
      setProducts(p);
      setStaff(st);
      setSessions(Array.isArray(sess) ? sess : []);
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
      <FxHeader title="Reportes" subtitle="Ventas, productos y mozos" onBack={() => router.back()} />
      <ScrollView
        contentContainerStyle={styles.body}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
      >
        <View style={styles.tabs}>
          <Chip label="Ventas" selected={tab === 'ventas'} onPress={() => setTab('ventas')} />
          <Chip
            label="Productos"
            selected={tab === 'productos'}
            onPress={() => setTab('productos')}
          />
          <Chip label="Mozos" selected={tab === 'mozos'} onPress={() => setTab('mozos')} />
          <Chip label="Caja" selected={tab === 'caja'} onPress={() => setTab('caja')} />
        </View>

        {tab !== 'caja' ? (
          <Surface style={styles.formCard}>
            <Field label="Desde (YYYY-MM-DD)" value={from} onChangeText={setFrom} />
            <Field label="Hasta (YYYY-MM-DD)" value={to} onChangeText={setTo} />
            <PrimaryButton
              title="Consultar"
              onPress={() => {
                setLoading(true);
                void load();
              }}
            />
          </Surface>
        ) : null}

        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}
        {loading ? <ActivityIndicator color={fx.brand} /> : null}

        {!loading && tab === 'ventas' && sales ? (
          <View style={styles.block}>
            <Surface style={styles.heroPad}>
              <HeroMetric
                label="Total ventas"
                value={`$${Number(sales.total_sales).toFixed(0)}`}
                hint={`${sales.total_orders} pedidos · ${sales.date_from} → ${sales.date_to}`}
                mono
              />
            </Surface>
            <AppText weight="semibold" style={styles.section}>
              Por método
            </AppText>
            <Surface padded={false}>
              {sales.sales_by_method.map((m, idx) => (
                <View key={m.payment_method} style={[styles.listRow, idx > 0 && styles.hairline]}>
                  <Badge label={m.payment_method} />
                  <AppText weight="bold" style={styles.amount}>
                    ${Number(m.total).toFixed(0)}
                  </AppText>
                </View>
              ))}
            </Surface>
            <AppText weight="semibold" style={styles.section}>
              Por día
            </AppText>
            <Surface padded={false}>
              {sales.sales_by_day.slice(-14).map((d, idx) => (
                <View key={d.day} style={[styles.listRow, idx > 0 && styles.hairline]}>
                  <AppText style={{ flex: 1, color: fx.ink }}>{d.day}</AppText>
                  <AppText style={styles.meta}>{d.count} ped.</AppText>
                  <AppText weight="bold" style={styles.amount}>
                    ${Number(d.total).toFixed(0)}
                  </AppText>
                </View>
              ))}
            </Surface>
            <AppText weight="semibold" style={styles.section}>
              Exportar
            </AppText>
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
              variant="outline"
              icon="document-outline"
              loading={exporting}
              onPress={() =>
                void exportFile(`/reports/sales/export-pdf?${qs(from, to)}`, 'ventas.pdf')
              }
            />
          </View>
        ) : null}

        {!loading && tab === 'productos' && products ? (
          <View style={styles.block}>
            <AppText style={styles.meta}>
              {products.date_from} → {products.date_to}
            </AppText>
            <AppText weight="semibold" style={styles.section}>
              Top productos
            </AppText>
            {products.top_products.length === 0 ? (
              <AppText style={styles.meta}>Sin datos en el período</AppText>
            ) : (
              <Surface padded={false}>
                {products.top_products.map((p, idx) => (
                  <View key={p.id} style={[styles.listRow, idx > 0 && styles.hairline]}>
                    <View style={{ flex: 1, gap: 2 }}>
                      <AppText weight="semibold" style={styles.itemTitle}>
                        {p.name}
                      </AppText>
                      <AppText style={styles.meta}>
                        ${Number(p.total_revenue).toFixed(0)} ingresos
                      </AppText>
                    </View>
                    <AppText weight="bold" style={styles.brandAmount}>
                      ×{p.total_quantity}
                    </AppText>
                  </View>
                ))}
              </Surface>
            )}
            <AppText weight="semibold" style={styles.section}>
              Exportar
            </AppText>
            <PrimaryButton
              title="Excel productos"
              icon="download-outline"
              loading={exporting}
              onPress={() =>
                void exportFile(`/reports/products/export?${qs(from, to)}`, 'productos.xlsx')
              }
            />
          </View>
        ) : null}

        {!loading && tab === 'mozos' && staff ? (
          <View style={styles.block}>
            <AppText style={styles.meta}>
              {staff.date_from} → {staff.date_to}
            </AppText>
            <AppText weight="semibold" style={styles.section}>
              Ventas por mozo
            </AppText>
            {staff.sales_by_staff.length === 0 ? (
              <AppText style={styles.meta}>Sin datos en el período</AppText>
            ) : (
              <Surface padded={false}>
                {staff.sales_by_staff.map((w, idx) => (
                  <View key={w.id} style={[styles.listRow, idx > 0 && styles.hairline]}>
                    <View style={{ flex: 1, gap: 2 }}>
                      <AppText weight="semibold" style={styles.itemTitle}>
                        {w.name}
                      </AppText>
                      <AppText style={styles.meta}>{w.total_orders} pedidos</AppText>
                    </View>
                    <AppText weight="bold" style={styles.amount}>
                      ${Number(w.total_sales).toFixed(0)}
                    </AppText>
                  </View>
                ))}
              </Surface>
            )}
            <AppText weight="semibold" style={styles.section}>
              Exportar
            </AppText>
            <PrimaryButton
              title="Excel mozos"
              icon="download-outline"
              loading={exporting}
              onPress={() =>
                void exportFile(`/reports/staff/export?${qs(from, to)}`, 'mozos.xlsx')
              }
            />
          </View>
        ) : null}

        {!loading && tab === 'caja' ? (
          <View style={styles.block}>
            <AppText weight="semibold" style={styles.section}>
              Sesiones de caja
            </AppText>
            {sessions.length === 0 ? (
              <AppText style={styles.meta}>Sin sesiones recientes</AppText>
            ) : (
              sessions.slice(0, 20).map((s, idx) => (
                <Surface key={s.id} padded={false} style={idx > 0 ? styles.itemGap : undefined}>
                  <View style={styles.listRow}>
                    <View style={{ flex: 1, gap: 4 }}>
                      <AppText weight="semibold" style={styles.itemTitle}>
                        {s.register ?? `Sesión #${s.id}`}
                      </AppText>
                      <AppText style={styles.meta}>
                        {s.user ? `${s.user} · ` : ''}
                        Inicial ${Number(s.initial_amount ?? 0).toFixed(0)}
                        {s.final_amount != null
                          ? ` · Final $${Number(s.final_amount).toFixed(0)}`
                          : ''}
                      </AppText>
                      {s.opened_at ? (
                        <AppText style={styles.meta}>{String(s.opened_at).slice(0, 16)}</AppText>
                      ) : null}
                    </View>
                    <Badge label={s.status} />
                  </View>
                  <View style={styles.itemActions}>
                    <PrimaryButton
                      title="Ver detalle"
                      variant="outline"
                      onPress={() => router.push(`/cash/${s.id}` as Href)}
                    />
                  </View>
                </Surface>
              ))
            )}
          </View>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  body: {
    paddingHorizontal: fx.space.md,
    paddingBottom: 56,
    gap: 16,
  },
  tabs: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  formCard: { gap: 4 },
  block: { gap: 12 },
  heroPad: { paddingVertical: 8 },
  section: { fontSize: 13, color: fx.inkMuted, marginTop: 4 },
  err: { color: fx.danger },
  meta: { color: fx.inkMuted, fontSize: 13 },
  listRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: fx.space.md,
    paddingVertical: 16,
  },
  hairline: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: fx.hairline,
  },
  itemTitle: { fontSize: 15, color: fx.ink },
  amount: { fontSize: 15, color: fx.ink, fontVariant: ['tabular-nums'] },
  brandAmount: { fontSize: 16, color: fx.brand },
  itemGap: { marginTop: 12 },
  itemActions: { paddingHorizontal: fx.space.md, paddingBottom: 14 },
});
