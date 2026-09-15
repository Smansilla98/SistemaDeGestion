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
import type { ProductRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import { DataTable, type DataColumn } from '../../src/ui/DataTable';
import { Amount, AppText, Badge, PageHeader, PrimaryButton } from '../../src/ui/primitives';
import { AppIcon } from '../../src/ui/icons';

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

  const columns: DataColumn<ProductRow>[] = useMemo(
    () => [
      {
        key: 'name',
        title: 'Producto',
        flex: 1.6,
        bold: true,
        render: (p) => p.name,
      },
      {
        key: 'price',
        title: 'Precio',
        flex: 1,
        align: 'right',
        render: (p) => <Amount value={p.price} />,
      },
      {
        key: 'status',
        title: 'Estado',
        flex: 1,
        render: (p) => <Badge label={p.is_active === false ? 'INACTIVO' : 'ACTIVO'} />,
      },
      {
        key: 'act',
        title: '',
        flex: 1,
        render: (p) =>
          canWrite ? (
            <View style={styles.actRow}>
              <Pressable onPress={() => void toggleActive(p)} hitSlop={6}>
                <AppIcon bi={p.is_active === false ? 'checkmark-circle' : 'create'} size={16} color={colors.teal600} />
              </Pressable>
              <Pressable onPress={() => router.push(`/products/${p.id}` as Href)} hitSlop={6}>
                <AppIcon bi="pencil" size={16} color={colors.gray700} />
              </Pressable>
              <Pressable onPress={() => remove(p)} hitSlop={6}>
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
      <PageHeader title="Productos" subtitle="Catálogo del restaurante" bi="card-list" />
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
        <ScrollView
          contentContainerStyle={{ padding: space.md, paddingBottom: 40 }}
          refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}
        >
          <DataTable
            columns={columns}
            rows={rows}
            keyExtractor={(p) => p.id}
            onPressRow={(p) => {
              if (canWrite) router.push(`/products/${p.id}` as Href);
            }}
            emptyText="Sin productos"
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
