import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { api, ApiError } from '../../src/api/client';
import type { DiscountTypeRow } from '../../src/api/types';
import { useAuth } from '../../src/auth/AuthContext';
import { hasPermission } from '../../src/auth/permissions';
import { colors, space } from '../../src/theme';
import {
  AppText,
  Badge,
  Card,
  Field,
  PageHeader,
  PrimaryButton,
  SectionLabel,
} from '../../src/ui/primitives';

export default function AdminDiscountsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canWrite = hasPermission(user, 'catalog.write');
  const [rows, setRows] = useState<DiscountTypeRow[]>([]);
  const [name, setName] = useState('');
  const [percentage, setPercentage] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      setRows(await api.discountTypes());
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
    const pct = Number(percentage);
    if (!name.trim() || Number.isNaN(pct)) {
      setError('Nombre y porcentaje requeridos');
      return;
    }
    setBusy(true);
    try {
      await api.createDiscountType({ name: name.trim(), percentage: pct });
      setName('');
      setPercentage('');
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo crear');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.root}>
      <PageHeader title="Descuentos" subtitle="Tipos de descuento" icon="pricetag" />
      <View style={{ padding: space.md }}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        {canWrite ? (
          <>
            <SectionLabel>Nuevo descuento</SectionLabel>
            <Field label="Nombre" value={name} onChangeText={setName} />
            <Field
              label="Porcentaje"
              value={percentage}
              onChangeText={setPercentage}
              keyboardType="decimal-pad"
              placeholder="10"
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
          renderItem={({ item }) => (
            <Card style={{ marginBottom: 8 }}>
              <View style={styles.row}>
                <AppText weight="bold" style={{ flex: 1 }}>
                  {item.name} · {Number(item.percentage)}%
                </AppText>
                <Badge label={item.is_active === false ? 'INACTIVO' : 'ACTIVO'} />
              </View>
              {canWrite ? (
                <PrimaryButton
                  title="Eliminar"
                  variant="danger"
                  onPress={() =>
                    Alert.alert('Eliminar', item.name, [
                      { text: 'Cancelar', style: 'cancel' },
                      {
                        text: 'Eliminar',
                        style: 'destructive',
                        onPress: () =>
                          void (async () => {
                            try {
                              await api.deleteDiscountType(item.id);
                              await load();
                            } catch (e) {
                              setError(e instanceof ApiError ? e.message : 'Error');
                            }
                          })(),
                      },
                    ])
                  }
                />
              ) : null}
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
  row: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
});
