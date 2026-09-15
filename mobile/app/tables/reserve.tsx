import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { hasPermission } from '../../src/auth/permissions';
import { useAuth } from '../../src/auth/AuthContext';
import { colors, space } from '../../src/theme';
import { AppText, Field, PageHeader, PrimaryButton } from '../../src/ui/primitives';

function todayISO() {
  const d = new Date();
  return d.toISOString().slice(0, 10);
}

export default function ReserveTableScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const tableId = Number(id);
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'tables.write');

  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('');
  const [date, setDate] = useState(todayISO());
  const [time, setTime] = useState('20:00');
  const [guests, setGuests] = useState('2');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    if (!canWrite || !tableId) return;
    setSaving(true);
    setError(null);
    try {
      await api.reserveTable(tableId, {
        customer_name: customerName.trim(),
        customer_phone: customerPhone.trim(),
        reservation_date: date.trim(),
        reservation_time: time.trim(),
        number_of_guests: Math.max(1, Number(guests) || 1),
      });
      router.replace(`/table/${tableId}`);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo reservar');
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Reservar mesa" subtitle={tableId ? `Mesa #${tableId}` : ''} icon="calendar" />
      <ScrollView contentContainerStyle={styles.body}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {!canWrite ? (
          <AppText>Necesitás permiso tables.write</AppText>
        ) : (
          <>
            {error ? <AppText style={styles.err}>{error}</AppText> : null}
            <Field label="Nombre del cliente" value={customerName} onChangeText={setCustomerName} />
            <Field
              label="Teléfono"
              value={customerPhone}
              onChangeText={setCustomerPhone}
              keyboardType="phone-pad"
            />
            <Field label="Fecha (YYYY-MM-DD)" value={date} onChangeText={setDate} />
            <Field label="Hora (HH:MM)" value={time} onChangeText={setTime} />
            <Field
              label="Comensales"
              value={guests}
              onChangeText={setGuests}
              keyboardType="number-pad"
            />
            <PrimaryButton title="Confirmar reserva" loading={saving} onPress={() => void submit()} />
          </>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  body: { padding: space.lg, gap: 10, paddingBottom: 48 },
  err: { color: colors.danger },
});
