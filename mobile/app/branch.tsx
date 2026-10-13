import { useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { ApiError } from '../src/api/client';
import type { Branch } from '../src/api/types';
import { useAuth } from '../src/auth/AuthContext';
import { homeHrefForRole } from '../src/auth/permissions';
import { fx } from '../src/theme';
import { AppIcon } from '../src/ui/icons';
import { AppText } from '../src/ui/primitives';

/**
 * Elección de sucursal. Aparece después del login para quien opera más de una
 * (admin) y también desde el topbar para cambiar de sucursal sin cerrar sesión.
 */
export default function BranchScreen() {
  const { user, selectBranch, logout } = useAuth();
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const [busyId, setBusyId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  const branches = user?.branches ?? [];
  const mustChoose = user?.requires_branch_selection === true;

  const choose = async (branch: Branch) => {
    if (busyId !== null) return;
    setError(null);
    setBusyId(branch.id);
    try {
      await selectBranch(branch.id);
      router.replace(homeHrefForRole(user?.role));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo cambiar de sucursal');
    } finally {
      setBusyId(null);
    }
  };

  return (
    <ScrollView
      style={styles.root}
      contentContainerStyle={[
        styles.content,
        { paddingTop: Math.max(insets.top, 24) + 16, paddingBottom: Math.max(insets.bottom, 24) },
      ]}
    >
      <AppText weight="bold" style={styles.title}>
        ¿En qué sucursal vas a trabajar?
      </AppText>
      <AppText style={styles.subtitle}>
        Mesas, pedidos, caja y stock son de la sucursal que elijas.
      </AppText>

      {error ? (
        <AppText weight="medium" style={styles.error}>
          {error}
        </AppText>
      ) : null}

      {branches.map((branch) => {
        const current = !mustChoose && branch.id === user?.restaurant_id;
        return (
          <Pressable
            key={branch.id}
            onPress={() => void choose(branch)}
            disabled={busyId !== null}
            accessibilityRole="button"
            accessibilityState={{ selected: current, busy: busyId === branch.id }}
            style={({ pressed }) => [styles.card, current && styles.cardCurrent, pressed && styles.cardPressed]}
          >
            <View style={styles.icon}>
              <AppIcon bi="shop" size={22} color={fx.brand} />
            </View>
            <View style={styles.cardBody}>
              <AppText weight="bold" style={styles.name}>
                {branch.name}
              </AppText>
              <AppText style={styles.meta}>
                {[branch.is_main ? 'Casa matriz' : null, branch.address].filter(Boolean).join(' · ') ||
                  'Sucursal'}
              </AppText>
            </View>
            {busyId === branch.id ? (
              <ActivityIndicator color={fx.brand} />
            ) : current ? (
              <AppText weight="semibold" style={styles.currentTag}>
                Actual
              </AppText>
            ) : (
              <AppIcon name="chevron-forward" size={18} color={fx.inkFaint} />
            )}
          </Pressable>
        );
      })}

      <Pressable
        onPress={() => (mustChoose ? void logout() : router.back())}
        accessibilityRole="button"
        style={styles.secondary}
      >
        <AppText weight="semibold" style={styles.secondaryText}>
          {mustChoose ? 'Cerrar sesión' : 'Volver'}
        </AppText>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  content: { paddingHorizontal: fx.space.md, gap: 12 },
  title: { fontSize: 24, color: fx.ink, letterSpacing: -0.4 },
  subtitle: { fontSize: 15, color: fx.inkMuted, marginBottom: 8 },
  error: { color: fx.danger },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    borderWidth: 1,
    borderColor: fx.hairline,
    padding: fx.space.md,
    minHeight: 76,
  },
  cardCurrent: { borderColor: fx.brand },
  cardPressed: { opacity: 0.7 },
  icon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: fx.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardBody: { flex: 1, gap: 2 },
  name: { fontSize: 17, color: fx.ink },
  meta: { fontSize: 13, color: fx.inkMuted },
  currentTag: { color: fx.brand, fontSize: 13 },
  secondary: { alignSelf: 'center', paddingVertical: 16, paddingHorizontal: 24 },
  secondaryText: { color: fx.inkMuted, fontSize: 15 },
});
