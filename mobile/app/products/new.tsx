import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
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

export default function NewProductScreen() {
  const router = useRouter();
  const [categories, setCategories] = useState<Array<{ id: number; name: string }>>([]);
  const [name, setName] = useState('');
  const [price, setPrice] = useState('');
  const [description, setDescription] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [hasStock, setHasStock] = useState(true);
  const [stockMin, setStockMin] = useState('0');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useFocusEffect(
    useCallback(() => {
      void (async () => {
        try {
          const cats = await api.categories();
          setCategories(Array.isArray(cats) ? cats : []);
          if (cats?.[0]) setCategoryId(cats[0].id);
        } catch {
          // sin categorías
        }
      })();
    }, []),
  );

  const submit = async () => {
    if (!name.trim()) {
      setError('Nombre requerido');
      return;
    }
    if (!categoryId) {
      setError('Elegí una categoría');
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
      await api.createProduct({
        type: 'PRODUCT',
        name: name.trim(),
        price: value,
        description: description.trim() || null,
        category_id: categoryId,
        has_stock: hasStock,
        stock_minimum: Number(stockMin) || 0,
        is_active: true,
      });
      router.replace('/products' as Href);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo crear');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Nuevo producto" subtitle="Alta rápida" icon="add-circle" />
      <ScrollView contentContainerStyle={{ padding: space.lg, paddingBottom: 40, gap: 8 }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        <Field label="Nombre" value={name} onChangeText={setName} placeholder="Ej. Pizza muzzarella" />
        <Field
          label="Precio"
          value={price}
          onChangeText={setPrice}
          keyboardType="decimal-pad"
          placeholder="0.00"
        />
        <Field
          label="Descripción"
          value={description}
          onChangeText={setDescription}
          placeholder="Opcional"
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
        {categories.length === 0 ? (
          <AppText style={{ color: colors.gray500 }}>
            No hay categorías. Creá una desde Admin → Categorías.
          </AppText>
        ) : null}
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
        {error ? (
          <AppText weight="medium" style={{ color: colors.danger }}>
            {error}
          </AppText>
        ) : null}
        <PrimaryButton
          title="Crear producto"
          icon="checkmark-circle"
          loading={busy}
          onPress={() => void submit()}
        />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.gray50 },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});
