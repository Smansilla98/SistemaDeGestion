import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import { AppText, Badge, Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

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

  return (
    <View style={styles.root}>
      <PageHeader title="Usuarios" subtitle="Equipo del restaurante" icon="people" />
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
        <FlatList
          data={rows}
          keyExtractor={(u) => String(u.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <AppText style={{ textAlign: 'center', color: colors.gray500 }}>Sin usuarios</AppText>
          }
          renderItem={({ item }) => (
            <Pressable
              onPress={() => router.push(`/users/${item.id}` as Href)}
            >
              <Card style={{ marginBottom: 10 }}>
                <AppText weight="bold">{item.name}</AppText>
                <AppText style={styles.meta}>@{item.username}</AppText>
                <View style={styles.row}>
                  <Badge label={item.role} />
                  <Badge label={item.is_active ? 'ACTIVO' : 'INACTIVO'} />
                </View>
                {canWrite ? (
                  <View style={styles.actions}>
                    <Pressable onPress={() => router.push(`/users/${item.id}` as Href)}>
                      <AppText weight="bold" style={{ color: colors.teal600, fontSize: 13 }}>
                        Editar
                      </AppText>
                    </Pressable>
                    <Pressable onPress={() => remove(item)}>
                      <AppText weight="bold" style={{ color: colors.danger, fontSize: 13 }}>
                        Eliminar
                      </AppText>
                    </Pressable>
                  </View>
                ) : null}
              </Card>
            </Pressable>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  bar: { flexDirection: 'row', gap: 8, padding: space.md },
  err: { color: colors.danger, paddingHorizontal: space.md },
  meta: { color: colors.gray500, marginTop: 4, marginBottom: 8 },
  row: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  actions: {
    flexDirection: 'row',
    gap: 16,
    marginTop: 10,
  },
});
