import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../src/api/client';
import type { CashSessionRow, PaymentMethodConfig, TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { formatDateDMY } from '../../src/ui/formatDate';
import { AppText, Chip, Field, PrimaryButton } from '../../src/ui/primitives';
import {
  FadeIn,
  FxHeader,
  HeroMetric,
  ModalSheet,
  StatusDot,
  Surface,
} from '../../src/ui/fintech';

type PayLine = { payment_method: string; amount: string };

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
    payment_breakdown?: Record<string, number>;
    open_sessions?: CashSessionRow[];
  } | null>(null);
  const [registers, setRegisters] = useState<Array<{ id: number; name: string }>>([]);
  const [registerId, setRegisterId] = useState<number | null>(null);
  const [tables, setTables] = useState<TableRow[]>([]);
  const [sessions, setSessions] = useState<CashSessionRow[]>([]);
  const [activeSessionId, setActiveSessionId] = useState<number | null>(null);
  const [initial, setInitial] = useState('0');
  const [finalAmount, setFinalAmount] = useState('');
  const [closeNotes, setCloseNotes] = useState('');
  const [payTableId, setPayTableId] = useState<number | null>(null);
  const [methods, setMethods] = useState<PaymentMethodConfig[]>([]);
  const [payLines, setPayLines] = useState<PayLine[]>([{ payment_method: 'EFECTIVO', amount: '' }]);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [movOpen, setMovOpen] = useState(false);
  const [movType, setMovType] = useState<'INGRESO' | 'EGRESO'>('INGRESO');
  const [movAmount, setMovAmount] = useState('');
  const [movDesc, setMovDesc] = useState('');
  const [movRef, setMovRef] = useState('');

  const load = useCallback(async () => {
    setError(null);
    try {
      const [s, r, t, sess, pm] = await Promise.all([
        api.cashSummary(activeSessionId ?? undefined),
        api.cashRegisters(),
        api.tables(),
        api.cashSessions().catch(() => [] as CashSessionRow[]),
        api.paymentMethodsActive().catch(() => [] as PaymentMethodConfig[]),
      ]);
      setSummary(s);
      setRegisters(Array.isArray(r) ? r : []);
      setRegisterId((prev) => prev ?? r?.[0]?.id ?? null);
      setTables((Array.isArray(t) ? t : []).filter((x) => x.status === 'OCUPADA'));
      setSessions(Array.isArray(sess) ? sess : []);
      setMethods(Array.isArray(pm) ? pm : []);
      setPayLines((prev) => {
        if (prev.length !== 1 || prev[0].amount) return prev;
        const first = pm[0]?.type ?? 'EFECTIVO';
        return [{ payment_method: first, amount: '' }];
      });
      const open = s.open_sessions ?? [];
      setActiveSessionId((prev) => {
        if (prev && open.some((o) => o.id === prev)) return prev;
        if (open[0]) return open[0].id;
        const sid = (s.session as { id?: number } | null)?.id;
        return typeof sid === 'number' ? sid : null;
      });
      if (s.expected_amount != null) setFinalAmount(String(s.expected_amount));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error caja');
    } finally {
      setLoading(false);
    }
  }, [activeSessionId]);

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
      await api.closeCash(value, closeNotes.trim() || undefined, activeSessionId ?? undefined);
      setMsg('Caja cerrada');
      setCloseNotes('');
      setActiveSessionId(null);
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
      setPayLines([{ payment_method: methods[0]?.type ?? 'EFECTIVO', amount: '' }]);
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
        reference: movRef.trim() || undefined,
        session_id: activeSessionId ?? undefined,
      });
      setMsg(`${movType} registrado`);
      setMovOpen(false);
      setMovAmount('');
      setMovDesc('');
      setMovRef('');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Movimiento falló');
    }
  };

  const openSessions = summary?.open_sessions ?? [];

  if (loading) {
    return (
      <View style={styles.root}>
        <FxHeader title="Caja" />
        <ActivityIndicator style={{ marginTop: 40 }} color={fx.brand} />
      </View>
    );
  }

  return (
    <View style={styles.root}>
      <FxHeader title="Caja" subtitle="Sesión y cobros" />
      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        showsVerticalScrollIndicator={false}
      >
        <FadeIn>
          <View style={styles.content}>
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

            {summary?.session ? (
              <Surface style={styles.heroPad}>
                <HeroMetric
                  label="Ventas de la sesión"
                  value={`$${Number(summary.sales_total).toFixed(0)}`}
                  hint={`${summary.payments_count} pagos · Efectivo esperado $${Number(summary.expected_amount ?? 0).toFixed(0)}`}
                  mono
                />
                {summary.payment_breakdown && Object.keys(summary.payment_breakdown).length > 0 ? (
                  <View style={styles.breakdownRow}>
                    {Object.entries(summary.payment_breakdown).map(([method, amount]) => (
                      <AppText key={method} style={styles.meta}>
                        {method}: ${Number(amount).toFixed(0)}
                      </AppText>
                    ))}
                  </View>
                ) : null}
                {openSessions.length > 1 ? (
                  <View style={styles.chips}>
                    {openSessions.map((s) => (
                      <Chip
                        key={s.id}
                        label={s.register ?? `#${s.id}`}
                        selected={activeSessionId === s.id}
                        onPress={() => setActiveSessionId(s.id)}
                      />
                    ))}
                  </View>
                ) : null}
                <AppText style={styles.meta}>
                  {(summary.session as { cash_register?: { name?: string } }).cash_register?.name ??
                    'Sesión abierta'}
                </AppText>
                {canWrite ? (
                  <View style={styles.blockGap}>
                    <Field
                      label="Monto final contado"
                      keyboardType="decimal-pad"
                      value={finalAmount}
                      onChangeText={setFinalAmount}
                    />
                    <Field
                      label="Notas de cierre (opcional)"
                      value={closeNotes}
                      onChangeText={setCloseNotes}
                      placeholder="Observaciones al cerrar"
                    />
                    <View style={styles.row}>
                      <View style={{ flex: 1 }}>
                        <PrimaryButton
                          title="Movimiento"
                          variant="ghost"
                          onPress={() => setMovOpen(true)}
                        />
                      </View>
                      <View style={{ flex: 1 }}>
                        <PrimaryButton
                          title="Cerrar caja"
                          variant="danger"
                          onPress={() => void close()}
                        />
                      </View>
                    </View>
                    {activeSessionId ? (
                      <PrimaryButton
                        title="Ver órdenes de la sesión"
                        variant="outline"
                        icon="receipt-outline"
                        onPress={() => router.push(`/cash/${activeSessionId}` as Href)}
                      />
                    ) : null}
                  </View>
                ) : null}
              </Surface>
            ) : (
              <Surface>
                <AppText weight="bold" style={styles.blockTitle}>
                  No hay caja abierta
                </AppText>
                {canWrite ? (
                  <View style={styles.blockGap}>
                    <AppText style={styles.section}>Elegir caja</AppText>
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
                  </View>
                ) : null}
              </Surface>
            )}

            {canWrite && summary?.session ? (
              <Surface>
                <AppText weight="bold" style={styles.blockTitle}>
                  Cobrar mesa
                </AppText>
                <View style={styles.chips}>
                  {tables.map((t) => (
                    <Chip
                      key={t.id}
                      label={`Mesa ${t.number}`}
                      selected={payTableId === t.id}
                      onPress={() => setPayTableId(t.id)}
                    />
                  ))}
                </View>
                {tables.length === 0 ? (
                  <AppText style={styles.meta}>No hay mesas ocupadas</AppText>
                ) : null}
                {payLines.map((line, idx) => {
                  const config = methods.find((m) => m.type === line.payment_method);
                  return (
                    <View key={idx} style={styles.payBlock}>
                      <View style={styles.chips}>
                        {methods.map((m) => (
                          <Chip
                            key={m.type}
                            label={m.label}
                            selected={line.payment_method === m.type}
                            onPress={() =>
                              setPayLines((prev) =>
                                prev.map((p, i) => (i === idx ? { ...p, payment_method: m.type } : p)),
                              )
                            }
                          />
                        ))}
                      </View>
                      {config?.type === 'TRANSFERENCIA' &&
                      (config.alias || config.cvu || config.cbu) ? (
                        <Surface style={styles.transferDetail}>
                          {config.alias ? (
                            <AppText style={styles.transferLine}>Alias: {config.alias}</AppText>
                          ) : null}
                          {config.cvu ? (
                            <AppText style={styles.transferLine}>CVU: {config.cvu}</AppText>
                          ) : null}
                          {config.cbu ? (
                            <AppText style={styles.transferLine}>CBU: {config.cbu}</AppText>
                          ) : null}
                          {config.account_holder ? (
                            <AppText style={styles.transferLine}>Titular: {config.account_holder}</AppText>
                          ) : null}
                          {config.instructions ? (
                            <AppText style={styles.meta}>{config.instructions}</AppText>
                          ) : null}
                        </Surface>
                      ) : null}
                      {config?.type === 'QR' ? (
                        <Surface style={styles.transferDetail}>
                          {config.qr_image_url ? (
                            <Image
                              source={{ uri: config.qr_image_url }}
                              style={styles.qrImage}
                              resizeMode="contain"
                            />
                          ) : (
                            <AppText style={styles.meta}>
                              No hay QR cargado — configuralo desde la web (Configuración → Medios de
                              cobro).
                            </AppText>
                          )}
                          {config.instructions ? (
                            <AppText style={styles.meta}>{config.instructions}</AppText>
                          ) : null}
                        </Surface>
                      ) : null}
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
                    </View>
                  );
                })}
                <PrimaryButton
                  title="Agregar método"
                  variant="ghost"
                  onPress={() =>
                    setPayLines((prev) => [
                      ...prev,
                      { payment_method: methods[0]?.type ?? 'EFECTIVO', amount: '' },
                    ])
                  }
                />
                <PrimaryButton title="Cobrar" onPress={() => void pay()} disabled={!payTableId} />
              </Surface>
            ) : null}

            <AppText weight="semibold" style={styles.section}>
              Sesiones recientes
            </AppText>
            <Surface padded={false}>
              {sessions.map((s, idx) => (
                <Pressable
                  key={s.id}
                  onPress={() => router.push(`/cash/${s.id}` as Href)}
                  style={[styles.sessionRow, idx > 0 && styles.hairline]}
                >
                  <View style={{ flex: 1, gap: 4 }}>
                    <AppText weight="bold" style={styles.sessionDate}>
                      {formatDateDMY(s.opened_at)}
                    </AppText>
                    <StatusDot status={s.status} size="sm" />
                  </View>
                  <Ionicons name="chevron-forward" size={18} color={fx.inkFaint} />
                </Pressable>
              ))}
              {sessions.length === 0 ? (
                <AppText style={[styles.meta, { padding: fx.space.md }]}>Sin sesiones</AppText>
              ) : null}
            </Surface>
          </View>
        </FadeIn>
      </ScrollView>

      <ModalSheet
        visible={movOpen}
        title="Movimiento de caja"
        onClose={() => setMovOpen(false)}
      >
        <View style={styles.chips}>
          <Chip
            label="INGRESO"
            selected={movType === 'INGRESO'}
            onPress={() => setMovType('INGRESO')}
            tone={fx.success}
          />
          <Chip
            label="EGRESO"
            selected={movType === 'EGRESO'}
            onPress={() => setMovType('EGRESO')}
            tone={fx.danger}
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
        <Field
          label="Referencia (opcional)"
          value={movRef}
          onChangeText={setMovRef}
          placeholder="Ej. Recibo Nº 001"
        />
        <PrimaryButton title="Guardar" onPress={() => void saveMovement()} />
      </ModalSheet>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  body: { paddingHorizontal: fx.space.md, paddingBottom: 56 },
  content: { gap: 20 },
  err: { color: fx.danger },
  ok: { color: fx.success },
  heroPad: { paddingVertical: 8 },
  meta: { color: fx.inkFaint, fontSize: 13, marginTop: 8 },
  blockTitle: { fontSize: 17, color: fx.ink, marginBottom: 12 },
  blockGap: { gap: 12, marginTop: 12 },
  section: {
    fontSize: 13,
    color: fx.inkMuted,
    marginBottom: -4,
  },
  row: { flexDirection: 'row', gap: 12 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginVertical: 8 },
  payBlock: {
    marginBottom: 12,
    paddingTop: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: fx.hairline,
  },
  transferDetail: { marginBottom: 12, gap: 4 },
  breakdownRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 12, marginTop: 4 },
  transferLine: { fontSize: 14, color: fx.ink },
  qrImage: { width: '100%', height: 180, marginBottom: 8 },
  sessionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: fx.space.md,
    paddingVertical: 18,
    gap: 12,
  },
  sessionDate: { fontSize: 18, color: fx.ink, letterSpacing: -0.3 },
  hairline: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: fx.hairline,
  },
});
