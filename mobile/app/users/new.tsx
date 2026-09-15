import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';
import { colors, space } from '../../src/theme';
import {
  AppText,
  Chip,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

const ROLES = ['MOZO', 'CAJERO', 'COCINA', 'ENCARGADO', 'GERENTE', 'ADMIN'] as const;

export default function NewUserScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const roles =
    user?.role === 'SUPERADMIN' ? ([...ROLES, 'SUPERADMIN'] as const) : ROLES;

  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState<string>('MOZO');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    if (!name.trim() || !username.trim() || password.length < 8) {
      setError('Completá nombre, usuario y contraseña (mín. 8)');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await api.createUser({
        name: name.trim(),
        username: username.trim(),
        password,
        role,
        email: email.trim() || undefined,
        is_active: true,
      });
      router.replace('/users' as Href);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo crear');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Nuevo usuario" subtitle="Alta de personal" icon="person-add" />
      <ScrollView contentContainerStyle={{ padding: space.lg, paddingBottom: 40, gap: 8 }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        <Field label="Nombre" value={name} onChangeText={setName} />
        <Field
          label="Usuario"
          value={username}
          onChangeText={setUsername}
          autoCapitalize="none"
        />
        <Field
          label="Email"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
        />
        <Field
          label="Contraseña"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
        />
        <SectionLabel>Rol</SectionLabel>
        <View style={styles.row}>
          {roles.map((r) => (
            <Chip key={r} label={r} selected={role === r} onPress={() => setRole(r)} />
          ))}
        </View>
        {error ? (
          <AppText weight="medium" style={{ color: colors.danger }}>
            {error}
          </AppText>
        ) : null}
        <PrimaryButton
          title="Crear usuario"
          icon="checkmark-circle"
          loading={busy}
          onPress={() => void submit()}
        />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});
