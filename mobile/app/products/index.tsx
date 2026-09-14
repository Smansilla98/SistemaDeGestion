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
import type { ProductRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import { AppText, Badge, Card, PageHeader, PrimaryButton } from '../../src/ui/primitives';

export default function ProductsScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const canWrite = hasPermission(user, 'products.write');
  const [rows, setRows] = useState<ProductRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setError(null);
    try {
      const data = await api.products({ activeOnly: false, perPage: 200 });
      setRows(Array.isArray(data) ? data : []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Error productos');
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

  const toggleActive = async (p: ProductRow) => {
    try {
      await api.updateProduct(p.id, { is_active: !(p.is_active !== false) });
      await load();
    } catch (e) {
      Alert.alert('Error', e instanceof ApiError ? e.message : 'No se pudo actualizar');
    }
  };

  const remove = (p: ProductRow) => {
    Alert.alert('Eliminar', `¿Eliminar ${p.name}?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () =>
          void (async () => {
            try {
              await api.deleteProduct(p.id);
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
      <PageHeader title="Productos" subtitle="Catálogo del restaurante" icon="pricetags" />
      <View style={styles.bar}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <PrimaryButton
            title="Nuevo"
            icon="add"
            onPress={() => router.push('/products/new' as Href)}
          />
        ) : null}
      </View>
      {error ? (
        <AppText weight="medium" style={styles.err}>
          {error}
        </AppText>
      ) : null}
      {loading ? (
        <ActivityIndicator style={{ marginTop: 24 }} color={colors.teal500} />
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(p) => String(p.id)}
          contentContainerStyle={{ padding: space.md }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
          ListEmptyComponent={
            <AppText style={{ textAlign: 'center', color: colors.gray500, marginTop: 40 }}>
              Sin productos
            </AppText>
          }
          renderItem={({ item }) => (
            <Pressable onPress={() => canWrite && router.push(`/products/${item.id}` as Href)}>
              <Card style={{ marginBottom: 10 }}>
                <View style={styles.row}>
                  <AppText weight="bold" style={{ flex: 1 }}>
                    {item.name}
                  </AppText>
                  <Badge label={item.is_active === false ? 'INACTIVO' : 'ACTIVO'} />
                </View>
                <AppText style={styles.price}>${Number(item.price).toFixed(2)}</AppText>
                {canWrite ? (
                  <View style={styles.actions}>
                    <Pressable onPress={() => void toggleActive(item)}>
                      <AppText weight="bold" style={{ color: colors.teal600, fontSize: 13 }}>
                        {item.is_active === false ? 'Activar' : 'Desactivar'}
                      </AppText>
                    </Pressable>
                    <Pressable onPress={() => router.push(`/products/${item.id}` as Href)}>
                      <AppText weight="bold" style={{ color: colors.gray700, fontSize: 13 }}>
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
  row: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  price: { marginTop: 8, color: colors.teal600 },
  actions: { flexDirection: 'row', gap: 16, marginTop: 12 },
});
