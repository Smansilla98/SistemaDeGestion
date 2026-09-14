import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { CashSessionRow, TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
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

const METHODS = ['EFECTIVO', 'DEBITO', 'CREDITO', 'TRANSFERENCIA', 'QR', 'OTRO'] as const;

type PayLine = { payment_method: (typeof METHODS)[number]; amount: string };

export default function CajaScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const params = useLocalSearchParams<{ tableId?: string }>();
  const canWrite = hasPermission(user, 'cash.write');

  const [summary, setSummary] = useState<{
    session: Record<string, unknown> | null;
    sales_total: number;
    payments_count: number;
    expected_amount?: number;
  } | null>(null);
  const [registers, setRegisters] = useState<Array<{ id: number; name: string }>>([]);
  const [registerId, setRegisterId] = useState<number | null>(null);
  const [tables, setTables] = useState<TableRow[]>([]);
  const [sessions, setSessions] = useState<CashSessionRow[]>([]);
  const [initial, setInitial] = useState('0');
  const [finalAmount, setFinalAmount] = useState('');
  const [payTableId, setPayTableId] = useState<number | null>(null);
  const [payLines, setPayLines] = useState<PayLine[]>([{ payment_method: 'EFECTIVO', amount: '' }]);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [movOpen, setMovOpen] = useState(false);
  const [movType, setMovType] = useState<'INGRESO' | 'EGRESO'>('INGRESO');
  const [movAmount, setMovAmount] = useState('');
  const [movDesc, setMovDesc] = useState('');

  const load = useCallback(async () => {
    setError(null);
    try {
      const [s, r, t, sess] = await Promise.all([
        api.cashSummary(),
        api.cashRegisters(),
        api.tables(),
        api.cashSessions().catch(() => [] as CashSessionRow[]),
      ]);
      setSummary(s);
      setRegisters(Array.isArray(r) ? r : []);
      if (!registerId && r?.[0]) setRegisterId(r[0].id);
      setTables((Array.isArray(t) ? t : []).filter((x) => x.status === 'OCUPADA'));
      setSessions(Array.isArray(sess) ? sess : []);
      if (s.expected_amount != null) setFinalAmount(String(s.expected_amount));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error caja');
    } finally {
      setLoading(false);
    }
  }, [registerId]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load]),
  );

  useEffect(() => {
    if (params.tableId) setPayTableId(Number(params.tableId));
  }, [params.tableId]);

  const openCash = async () => {
    if (!registerId) {
      setError('Elegí una caja');
      return;
    }
    try {
      await api.openCash(registerId, Number(initial) || 0);
      setMsg('Caja abierta');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo abrir');
    }
  };

  const close = async () => {
    const value = Number(finalAmount);
    if (Number.isNaN(value) || value < 0) {
      setError('Monto final inválido');
      return;
    }
    try {
      await api.closeCash(value);
      setMsg('Caja cerrada');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo cerrar');
    }
  };

  const pay = async () => {
    if (!payTableId) return;
    const payments = payLines
      .map((l) => ({ payment_method: l.payment_method, amount: Number(l.amount) }))
      .filter((p) => p.amount > 0);
    if (payments.length === 0) {
      setError('Ingresá al menos un monto');
      return;
    }
    try {
      await api.payTable(payTableId, payments);
      setMsg('Mesa cobrada');
      setPayLines([{ payment_method: 'EFECTIVO', amount: '' }]);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Cobro falló');
    }
  };

  const saveMovement = async () => {
    const amount = Number(movAmount);
    if (!amount || amount <= 0 || !movDesc.trim()) {
      setError('Completá monto y descripción');
      return;
    }
    try {
      await api.cashMovement({
        type: movType,
        amount,
        description: movDesc.trim(),
      });
      setMsg(`${movType} registrado`);
      setMovOpen(false);
      setMovAmount('');
      setMovDesc('');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Movimiento falló');
    }
  };

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  return (
    <ScrollView
      style={styles.root}
      contentContainerStyle={{ paddingBottom: 48 }}
      refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
    >
      <PageHeader title="Caja" subtitle="Sesión, cobros y movimientos" icon="cash" />
      <View style={{ padding: space.lg, gap: 6 }}>
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}
        {msg ? (
          <AppText weight="medium" style={styles.ok}>
            {msg}
          </AppText>
        ) : null}

        <SectionLabel>Sesión actual</SectionLabel>
        {summary?.session ? (
          <Card>
            <AppText weight="bold">Caja abierta</AppText>
            <AppText style={styles.meta}>
              {(summary.session as { cash_register?: { name?: string } }).cash_register?.name ??
                'Sesión'}
            </AppText>
            <AppText>Ventas ${Number(summary.sales_total).toFixed(2)}</AppText>
            <AppText>{summary.payments_count} pagos</AppText>
            <AppText weight="semibold">
              Esperado ${Number(summary.expected_amount ?? 0).toFixed(2)}
            </AppText>
            {canWrite ? (
              <>
                <Field
                  label="Monto final contado"
                  keyboardType="decimal-pad"
                  value={finalAmount}
                  onChangeText={setFinalAmount}
                />
                <View style={styles.row}>
                  <View style={{ flex: 1 }}>
                    <PrimaryButton title="Movimiento" variant="ghost" onPress={() => setMovOpen(true)} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <PrimaryButton title="Cerrar caja" variant="danger" onPress={() => void close()} />
                  </View>
                </View>
              </>
            ) : null}
          </Card>
        ) : (
          <Card>
            <AppText>No hay caja abierta</AppText>
            {canWrite ? (
              <>
                <SectionLabel>Elegir caja</SectionLabel>
                <View style={styles.chips}>
                  {registers.map((r) => (
                    <Chip
                      key={r.id}
                      label={r.name}
                      selected={registerId === r.id}
                      onPress={() => setRegisterId(r.id)}
                    />
                  ))}
                </View>
                <Field
                  label="Monto inicial"
                  keyboardType="decimal-pad"
                  value={initial}
                  onChangeText={setInitial}
                />
                <PrimaryButton title="Abrir caja" onPress={() => void openCash()} />
              </>
            ) : null}
          </Card>
        )}

        {canWrite && summary?.session ? (
          <>
            <SectionLabel>Cobrar mesa (multi-pago)</SectionLabel>
            <View style={styles.chips}>
              {tables.map((t) => (
                <Chip
                  key={t.id}
                  label={String(t.number)}
                  selected={payTableId === t.id}
                  onPress={() => setPayTableId(t.id)}
                />
              ))}
            </View>
            {payLines.map((line, idx) => (
              <Card key={idx} style={{ marginBottom: 8 }}>
                <View style={styles.chips}>
                  {METHODS.map((m) => (
                    <Chip
                      key={m}
                      label={m}
                      selected={line.payment_method === m}
                      onPress={() =>
                        setPayLines((prev) =>
                          prev.map((p, i) => (i === idx ? { ...p, payment_method: m } : p)),
                        )
                      }
                    />
                  ))}
                </View>
                <Field
                  label="Monto"
                  keyboardType="decimal-pad"
                  value={line.amount}
                  onChangeText={(v) =>
                    setPayLines((prev) =>
                      prev.map((p, i) => (i === idx ? { ...p, amount: v } : p)),
                    )
                  }
                />
              </Card>
            ))}
            <PrimaryButton
              title="Agregar método"
              variant="ghost"
              onPress={() =>
                setPayLines((prev) => [...prev, { payment_method: 'EFECTIVO', amount: '' }])
              }
            />
            <PrimaryButton title="Cobrar" onPress={() => void pay()} disabled={!payTableId} />
          </>
        ) : null}

        <SectionLabel>Sesiones recientes</SectionLabel>
        {sessions.map((s) => (
          <Pressable key={s.id} onPress={() => router.push(`/cash/${s.id}` as Href)}>
            <Card style={{ marginBottom: 8 }}>
              <View style={styles.rowBetween}>
                <AppText weight="bold">{s.register ?? `Sesión #${s.id}`}</AppText>
                <Badge label={s.status} />
              </View>
              <AppText style={styles.meta}>
                {s.user ?? '—'} · Ini ${Number(s.initial_amount).toFixed(0)}
                {s.final_amount != null ? ` · Fin $${Number(s.final_amount).toFixed(0)}` : ''}
              </AppText>
              <AppText style={styles.meta}>{s.opened_at?.slice(0, 16) ?? ''}</AppText>
            </Card>
          </Pressable>
        ))}
        {sessions.length === 0 ? (
          <AppText style={styles.meta}>Sin sesiones</AppText>
        ) : null}
      </View>

      <Modal visible={movOpen} animationType="slide" transparent>
        <View style={styles.modalBg}>
          <View style={styles.modalCard}>
            <AppText weight="bold" style={{ fontSize: 18 }}>
              Movimiento de caja
            </AppText>
            <View style={styles.chips}>
              <Chip
                label="INGRESO"
                selected={movType === 'INGRESO'}
                onPress={() => setMovType('INGRESO')}
                tone={colors.green}
              />
              <Chip
                label="EGRESO"
                selected={movType === 'EGRESO'}
                onPress={() => setMovType('EGRESO')}
                tone={colors.danger}
              />
            </View>
            <Field
              label="Monto"
              keyboardType="decimal-pad"
              value={movAmount}
              onChangeText={setMovAmount}
            />
            <Field
              label="Descripción"
              value={movDesc}
              onChangeText={setMovDesc}
              placeholder="Ej. Retiro, propina, etc."
            />
            <PrimaryButton title="Guardar" onPress={() => void saveMovement()} />
            <PrimaryButton title="Cancelar" variant="ghost" onPress={() => setMovOpen(false)} />
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  err: { color: colors.danger },
  ok: { color: colors.green },
  meta: { color: colors.gray500, fontSize: 13, marginTop: 4 },
  row: { flexDirection: 'row', gap: 8, marginTop: 8 },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginVertical: 6 },
  modalBg: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'flex-end' },
  modalCard: {
    backgroundColor: colors.white,
    borderTopLeftRadius: radius.xl,
    borderTopRightRadius: radius.xl,
    padding: space.lg,
    gap: 8,
  },
});
