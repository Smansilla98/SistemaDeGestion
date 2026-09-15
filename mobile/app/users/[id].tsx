import { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, StyleSheet, View } from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
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

export default function EditUserScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'users.write');
  const isSuperadmin = user?.role === 'SUPERADMIN';
  const roles = isSuperadmin ? ([...ROLES, 'SUPERADMIN'] as const) : ROLES;

  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState('MOZO');
  const [isActive, setIsActive] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [tempPassword, setTempPassword] = useState<string | null>(null);

  useFocusEffect(
    useCallback(() => {
      void (async () => {
        if (!id) return;
        setLoading(true);
        setError(null);
        setTempPassword(null);
        try {
          const row = await api.getUser(Number(id));
          setName(row.name ?? '');
          setUsername(row.username ?? '');
          setEmail(row.email ?? '');
          setRole(row.role ?? 'MOZO');
          setIsActive(row.is_active !== false);
        } catch (e) {
          setError(e instanceof ApiError ? e.message : 'Error al cargar');
        } finally {
          setLoading(false);
        }
      })();
    }, [id]),
  );

  const submit = async () => {
    if (!name.trim() || !username.trim()) {
      setError('Nombre y usuario requeridos');
      return;
    }
    if (password && password.length < 8) {
      setError('Contraseña mín. 8 caracteres');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const body: Record<string, unknown> = {
        name: name.trim(),
        username: username.trim(),
        email: email.trim() || null,
        role,
        is_active: isActive,
      };
      if (password) body.password = password;
      await api.updateUser(Number(id), body);
      router.replace('/users' as Href);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setBusy(false);
    }
  };

  const resetPassword = () => {
    Alert.alert(
      'Contraseña temporal',
      'Se generará una contraseña nueva. Solo se muestra una vez.',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Generar',
          onPress: () => {
            void (async () => {
              setBusy(true);
              setError(null);
              try {
                const res = await api.resetUserPassword(Number(id));
                setTempPassword(res.temporary_password);
                Alert.alert(
                  'Contraseña temporal',
                  res.temporary_password,
                  [{ text: 'OK' }],
                );
              } catch (e) {
                Alert.alert('Error', e instanceof ApiError ? e.message : 'No se pudo resetear');
              } finally {
                setBusy(false);
              }
            })();
          },
        },
      ],
    );
  };

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  return (
    <View style={styles.root}>
      <PageHeader title="Editar usuario" subtitle={`#${id}`} icon="create" />
      <ScrollView contentContainerStyle={{ padding: space.lg, paddingBottom: 40, gap: 8 }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        <Field label="Nombre" value={name} onChangeText={setName} editable={canWrite} />
        <Field
          label="Usuario"
          value={username}
          onChangeText={setUsername}
          autoCapitalize="none"
          editable={canWrite}
        />
        <Field
          label="Email"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          editable={canWrite}
        />
        {canWrite ? (
          <Field
            label="Nueva contraseña (opcional)"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />
        ) : null}
        <SectionLabel>Rol</SectionLabel>
        <View style={styles.row}>
          {roles.map((r) => (
            <Chip
              key={r}
              label={r}
              selected={role === r}
              onPress={() => (canWrite ? setRole(r) : undefined)}
            />
          ))}
        </View>
        <View style={styles.row}>
          <Chip
            label="Activo"
            selected={isActive}
            onPress={() => (canWrite ? setIsActive(true) : undefined)}
          />
          <Chip
            label="Inactivo"
            selected={!isActive}
            onPress={() => (canWrite ? setIsActive(false) : undefined)}
          />
        </View>
        {tempPassword ? (
          <AppText weight="bold" style={{ color: colors.teal600 }}>
            Temporal: {tempPassword}
          </AppText>
        ) : null}
        {error ? (
          <AppText weight="medium" style={{ color: colors.danger }}>
            {error}
          </AppText>
        ) : null}
        {canWrite ? (
          <>
            <PrimaryButton
              title="Guardar"
              icon="checkmark-circle"
              loading={busy}
              onPress={() => void submit()}
            />
            {isSuperadmin ? (
              <PrimaryButton
                title="Resetear contraseña"
                variant="amber"
                loading={busy}
                onPress={resetPassword}
              />
            ) : null}
          </>
        ) : null}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});
