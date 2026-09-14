import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { ClientRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import {
  AppText,
  Card,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

export default function AdminClientsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'clients.write');
  const [rows, setRows] = useState<ClientRow[]>([]);
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows(await api.clients());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error');
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

  const create = async () => {
    if (!name.trim()) {
      setError('Nombre requerido');
      return;
    }
    setBusy(true);
    try {
      await api.createClient({
        name: name.trim(),
        phone: phone.trim() || undefined,
        email: email.trim() || undefined,
      });
      setName('');
      setPhone('');
      setEmail('');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo crear');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Clientes" subtitle="Agenda" icon="people" />
      <View style={{ padding: space.md }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <>
            <SectionLabel>Nuevo cliente</SectionLabel>
            <Field label="Nombre" value={name} onChangeText={setName} />
            <Field label="Teléfono" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
            <Field
              label="Email"
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
            />
            <PrimaryButton title="Crear" loading={busy} onPress={() => void create()} />
          </>
        ) : null}
        {error ? (
          <AppText weight="medium" style={styles.err}>
            {error}
          </AppText>
        ) : null}
      </View>
      {loading ? (
        <ActivityIndicator color={colors.teal500} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(c) => String(c.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <AppText style={{ textAlign: 'center', color: colors.gray500 }}>Sin clientes</AppText>
          }
          renderItem={({ item }) => (
            <Card style={{ marginBottom: 8 }}>
              <AppText weight="bold">{item.name}</AppText>
              {item.phone ? <AppText style={styles.meta}>{item.phone}</AppText> : null}
              {item.email ? <AppText style={styles.meta}>{item.email}</AppText> : null}
            </Card>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  err: { color: colors.danger, marginTop: 8 },
  meta: { color: colors.gray500, marginTop: 4, fontSize: 13 },
});
