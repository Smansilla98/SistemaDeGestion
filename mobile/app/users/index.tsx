import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import { DataTable, type DataColumn } from '../../src/ui/DataTable';
import { AppText, Badge, PageHeader, PrimaryButton } from '../../src/ui/primitives';
import { AppIcon } from '../../src/ui/icons';

type UserRow = {
  id: number;
  name: string;
  username: string;
  role: string;
  is_active: boolean;
};

export default function UsersScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'users.write');
  const [rows, setRows] = useState<UserRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      const data = await api.users();
      setRows(Array.isArray(data) ? data : []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error usuarios');
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

  const remove = (u: UserRow) => {
    Alert.alert('Eliminar usuario', `¿Eliminar a ${u.name}?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () =>
          void (async () => {
            try {
              await api.deleteUser(u.id);
              await load();
            } catch (e) {
              Alert.alert('Error', e instanceof ApiError ? e.message : 'No se pudo eliminar');
            }
          })(),
      },
    ]);
  };

  const columns: DataColumn<UserRow>[] = useMemo(
    () => [
      {
        key: 'name',
        title: 'Nombre',
        flex: 1.4,
        bold: true,
        render: (u) => u.name,
      },
      {
        key: 'user',
        title: 'Usuario',
        flex: 1.1,
        mono: true,
        render: (u) => `@${u.username}`,
      },
      {
        key: 'role',
        title: 'Rol',
        flex: 1.1,
        render: (u) => <Badge label={u.role} />,
      },
      {
        key: 'active',
        title: 'Estado',
        flex: 1,
        render: (u) => <Badge label={u.is_active ? 'ACTIVO' : 'INACTIVO'} />,
      },
      {
        key: 'act',
        title: '',
        flex: 0.9,
        render: (u) =>
          canWrite ? (
            <View style={styles.actRow}>
              <Pressable onPress={() => router.push(`/users/${u.id}` as Href)} hitSlop={6}>
                <AppIcon bi="pencil" size={16} color={colors.teal600} />
              </Pressable>
              <Pressable onPress={() => remove(u)} hitSlop={6}>
                <AppIcon bi="trash" size={16} color={colors.danger} />
              </Pressable>
            </View>
          ) : null,
      },
    ],
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [canWrite],
  );

  return (
    <View style={styles.root}>
      <PageHeader title="Usuarios" subtitle="Equipo del restaurante" bi="people" />
      <View style={styles.bar}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <PrimaryButton
            title="Nuevo"
            icon="person-add"
            onPress={() => router.push('/users/new' as Href)}
          />
        ) : null}
      </View>
      {error ? (
        <AppText weight="medium" style={styles.err}>
          {error}
        </AppText>
      ) : null}
      {loading ? (
        <ActivityIndicator color={colors.teal500} />
      ) : (
        <ScrollView
          contentContainerStyle={{ padding: space.md, paddingBottom: 40 }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        >
          <DataTable
            columns={columns}
            rows={rows}
            keyExtractor={(u) => u.id}
            onPressRow={(u) => router.push(`/users/${u.id}` as Href)}
            emptyText="Sin usuarios"
          />
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  bar: { flexDirection: 'row', gap: 8, padding: space.md },
  err: { color: colors.danger, paddingHorizontal: space.md },
  actRow: { flexDirection: 'row', gap: 12, alignItems: 'center' },
});
