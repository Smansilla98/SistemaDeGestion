import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { TableRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import { Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

const METHODS = ['EFECTIVO', 'DEBITO', 'CREDITO', 'TRANSFERENCIA'] as const;

export default function CajaScreen() {
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'cash.write');
  const [summary, setSummary] = useState<{
    session: Record<string, unknown> | null;
    sales_total: number;
    payments_count: number;
    expected_amount?: number;
  } | null>(null);
  const [registers, setRegisters] = useState<Array<{ id: number; name: string }>>([]);
  const [tables, setTables] = useState<TableRow[]>([]);
  const [initial, setInitial] = useState('0');
  const [finalAmount, setFinalAmount] = useState('');
  const [payTableId, setPayTableId] = useState<number | null>(null);
  const [amount, setAmount] = useState('');
  const [method, setMethod] = useState<(typeof METHODS)[number]>('EFECTIVO');
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [s, r, t] = await Promise.all([api.cashSummary(), api.cashRegisters(), api.tables()]);
      setSummary(s);
      setRegisters(Array.isArray(r) ? r : []);
      setTables((Array.isArray(t) ? t : []).filter((x) => x.status === 'OCUPADA'));
      if (s.expected_amount != null) setFinalAmount(String(s.expected_amount));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error caja');
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

  const openFirst = async () => {
    if (!registers[0]) {
      setError('No hay cajas configuradas');
      return;
    }
    try {
      await api.openCash(registers[0].id, Number(initial) || 0);
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
    const value = Number(amount);
    if (!value || value <= 0) {
      setError('Monto inválido');
      return;
    }
    try {
      await api.payTable(payTableId, [{ payment_method: method, amount: value }]);
      setMsg('Mesa cobrada');
      setAmount('');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Cobro falló');
    }
  };

  if (loading) return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;

  return (
    <ScrollView style={styles.root} contentContainerStyle={{ paddingBottom: 40 }}>
      <PageHeader title="Caja" subtitle="Sesión y cobros" icon="cash" />
      <View style={{ padding: space.lg }}>
        {error && <Text style={styles.err}>{error}</Text>}
        {msg && <Text style={styles.ok}>{msg}</Text>}

        <Text style={styles.h}>Sesión</Text>
        {summary?.session ? (
          <Card>
            <Text style={styles.bold}>Caja abierta</Text>
            <Text>Ventas ${Number(summary.sales_total).toFixed(2)}</Text>
            <Text>{summary.payments_count} pagos</Text>
            <Text>Esperado ${Number(summary.expected_amount ?? 0).toFixed(2)}</Text>
            {canWrite && (
              <>
                <TextInput
                  style={styles.input}
                  keyboardType="decimal-pad"
                  value={finalAmount}
                  onChangeText={setFinalAmount}
                  placeholder="Monto final contado"
                />
                <PrimaryButton title="Cerrar caja" variant="danger" onPress={() => void close()} />
              </>
            )}
          </Card>
        ) : (
          <Card>
            <Text>No hay caja abierta</Text>
            {canWrite && (
              <>
                <TextInput
                  style={styles.input}
                  keyboardType="decimal-pad"
                  value={initial}
                  onChangeText={setInitial}
                  placeholder="Monto inicial"
                />
                <PrimaryButton title={`Abrir ${registers[0]?.name ?? 'caja'}`} onPress={() => void openFirst()} />
              </>
            )}
          </Card>
        )}

        {canWrite && (
          <>
            <Text style={styles.h}>Cobrar mesa</Text>
            <View style={styles.row}>
              {tables.map((t) => (
                <Pressable
                  key={t.id}
                  style={[styles.chip, payTableId === t.id && styles.chipOn]}
                  onPress={() => setPayTableId(t.id)}
                >
                  <Text style={{ color: payTableId === t.id ? '#fff' : colors.gray900, fontWeight: '700' }}>
                    {t.number}
                  </Text>
                </Pressable>
              ))}
            </View>
            <View style={styles.row}>
              {METHODS.map((m) => (
                <Pressable
                  key={m}
                  style={[styles.chip, method === m && styles.chipOn]}
                  onPress={() => setMethod(m)}
                >
                  <Text style={{ color: method === m ? '#fff' : colors.gray800, fontSize: 11, fontWeight: '700' }}>
                    {m}
                  </Text>
                </Pressable>
              ))}
            </View>
            <TextInput
              style={styles.input}
              keyboardType="decimal-pad"
              value={amount}
              onChangeText={setAmount}
              placeholder="Monto"
            />
            <PrimaryButton title="Cobrar" onPress={() => void pay()} disabled={!payTableId} />
          </>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  h: {
    fontWeight: '700',
    color: colors.gray500,
    textTransform: 'uppercase',
    fontSize: 12,
    marginTop: 16,
    marginBottom: 8,
  },
  bold: { fontWeight: '800', marginBottom: 6, color: colors.gray900 },
  input: {
    marginTop: 10,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray200,
    minHeight: 48,
    paddingHorizontal: 12,
    fontSize: 16,
  },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 8 },
  chip: {
    minWidth: 56,
    minHeight: 44,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray200,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  chipOn: { backgroundColor: colors.teal500, borderColor: colors.teal500 },
  err: { color: colors.danger, marginBottom: 8 },
  ok: { color: colors.green, marginBottom: 8, fontWeight: '600' },
});
