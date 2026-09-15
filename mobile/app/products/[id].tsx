import { useCallback, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, View } from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import { colors, space } from '../../src/theme';
import {
  AppText,
  Chip,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

export default function EditProductScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [categories, setCategories] = useState<Array<{ id: number; name: string }>>([]);
  const [name, setName] = useState('');
  const [price, setPrice] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [hasStock, setHasStock] = useState(true);
  const [stockMin, setStockMin] = useState('0');
  const [isActive, setIsActive] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      void (async () => {
        if (!id) return;
        setLoading(true);
        setError(null);
        try {
          const [cats, product] = await Promise.all([api.categories(), api.product(Number(id))]);
          setCategories(Array.isArray(cats) ? cats : []);
          setName(product.name ?? '');
          setPrice(String(product.price ?? ''));
          setCategoryId(product.category_id ?? cats?.[0]?.id ?? null);
          setHasStock(product.has_stock !== false);
          setStockMin(String(product.stock_minimum ?? 0));
          setIsActive(product.is_active !== false);
        } catch (e) {
          setError(e instanceof ApiError ? e.message : 'Error al cargar');
        } finally {
          setLoading(false);
        }
      })();
    }, [id]),
  );

  const submit = async () => {
    if (!name.trim()) {
      setError('Nombre requerido');
      return;
    }
    const value = Number(price);
    if (Number.isNaN(value) || value < 0) {
      setError('Precio inválido');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await api.updateProduct(Number(id), {
        name: name.trim(),
        price: value,
        category_id: categoryId,
        has_stock: hasStock,
        stock_minimum: Number(stockMin) || 0,
        is_active: isActive,
      });
      router.replace('/products' as Href);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo guardar');
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return <ActivityIndicator style={{ marginTop: 40 }} color={colors.teal500} />;
  }

  return (
    <View style={styles.root}>
      <PageHeader title="Editar producto" subtitle={`#${id}`} icon="create" />
      <ScrollView contentContainerStyle={{ padding: space.lg, paddingBottom: 40, gap: 8 }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        <Field label="Nombre" value={name} onChangeText={setName} />
        <Field
          label="Precio"
          value={price}
          onChangeText={setPrice}
          keyboardType="decimal-pad"
        />
        <SectionLabel>Categoría</SectionLabel>
        <View style={styles.row}>
          {categories.map((c) => (
            <Chip
              key={c.id}
              label={c.name}
              selected={categoryId === c.id}
              onPress={() => setCategoryId(c.id)}
            />
          ))}
        </View>
        <Chip
          label={hasStock ? 'Con stock' : 'Sin stock'}
          selected={hasStock}
          onPress={() => setHasStock((v) => !v)}
        />
        {hasStock ? (
          <Field
            label="Stock mínimo"
            value={stockMin}
            onChangeText={setStockMin}
            keyboardType="number-pad"
          />
        ) : null}
        <Chip
          label={isActive ? 'Activo' : 'Inactivo'}
          selected={isActive}
          onPress={() => setIsActive((v) => !v)}
        />
        {error ? (
          <AppText weight="medium" style={{ color: colors.danger }}>
            {error}
          </AppText>
        ) : null}
        <PrimaryButton
          title="Guardar cambios"
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
