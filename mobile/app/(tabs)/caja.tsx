import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  ScrollView,
} from 'react-native';
import { useFocusEffect } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { TableRow } from '../../src/api/types';
import { colors } from '../../src/theme';

export default function CajaScreen() {
  const [summary, setSummary] = useState<{
    session: Record<string, unknown> | null;
    sales_total: number;
    payments_count: number;
  } | null>(null);
  const [registers, setRegisters] = useState<Array<{ id: number; name: string }>>([]);
  const [tables, setTables] = useState<TableRow[]>([]);
  const [initial, setInitial] = useState('0');
  const [payTableId, setPayTableId] = useState<number | null>(null);
  const [amount, setAmount] = useState('');
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

  const pay = async () => {
    if (!payTableId) return;
    const value = Number(amount);
    if (!value || value <= 0) {
      setError('Monto inválido');
      return;
    }
    try {
      await api.payTable(payTableId, [{ payment_method: 'EFECTIVO', amount: value }]);
      setMsg('Mesa cobrada');
      setAmount('');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Cobro falló');
    }
  };

  if (loading) return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal700} />;

  return (
    <ScrollView style={styles.root} contentContainerStyle={{ padding: 16 }}>
      {error && <Text style={styles.err}>{error}</Text>}
      {msg && <Text style={styles.ok}>{msg}</Text>}

      <Text style={styles.h}>Sesión</Text>
      {summary?.session ? (
        <View style={styles.card}>
          <Text style={styles.bold}>Caja abierta</Text>
          <Text>Ventas ${Number(summary.sales_total).toFixed(2)}</Text>
          <Text>{summary.payments_count} pagos</Text>
        </View>
      ) : (
        <View style={styles.card}>
          <Text>No hay caja abierta</Text>
          <TextInput
            style={styles.input}
            keyboardType="decimal-pad"
            value={initial}
            onChangeText={setInitial}
            placeholder="Monto inicial"
          />
          <Pressable style={styles.btn} onPress={() => void openFirst()}>
            <Text style={styles.btnText}>Abrir {registers[0]?.name ?? 'caja'}</Text>
          </Pressable>
        </View>
      )}

      <Text style={styles.h}>Cobrar mesa</Text>
      <View style={styles.row}>
        {tables.map((t) => (
          <Pressable
            key={t.id}
            style={[styles.chip, payTableId === t.id && styles.chipOn]}
            onPress={() => setPayTableId(t.id)}
          >
            <Text style={{ color: payTableId === t.id ? '#fff' : colors.gray900 }}>{t.number}</Text>
          </Pressable>
        ))}
      </View>
      <TextInput
        style={styles.input}
        keyboardType="decimal-pad"
        value={amount}
        onChangeText={setAmount}
        placeholder="Monto efectivo"
      />
      <Pressable style={styles.btn} onPress={() => void pay()} disabled={!payTableId}>
        <Text style={styles.btnText}>Cobrar</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  h: { fontWeight: '700', color: colors.gray600, textTransform: 'uppercase', fontSize: 12, marginTop: 12, marginBottom: 8 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.gray200,
  },
  bold: { fontWeight: '800', marginBottom: 6 },
  input: {
    marginTop: 10,
    backgroundColor: colors.white,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.gray200,
    minHeight: 48,
    paddingHorizontal: 12,
    fontSize: 16,
  },
  btn: {
    marginTop: 12,
    backgroundColor: colors.teal700,
    borderRadius: 12,
    minHeight: 48,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnText: { color: '#fff', fontWeight: '700' },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    minWidth: 56,
    minHeight: 44,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.gray200,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  chipOn: { backgroundColor: colors.teal700, borderColor: colors.teal700 },
  err: { color: colors.danger, marginBottom: 8 },
  ok: { color: colors.green, marginBottom: 8 },
});
