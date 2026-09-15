import { useState } from 'react';
import { KeyboardAvoidingView, Platform, StyleSheet, View } from 'react-native';
import { useAuth } from '../src/auth/AuthContext';
import { ApiError } from '../src/api/client';
import { colors, gradients, radius, space } from '../src/theme';
import { AppText, Field, Icon, PrimaryButton } from '../src/ui/primitives';
import { LinearGradientFallback } from '../src/ui/gradient';

export default function LoginScreen() {
  const { login, offlineHint } = useAuth();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const onSubmit = async () => {
    setError(null);
    setBusy(true);
    try {
      await login(username, password);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'No se pudo iniciar sesión');
    } finally {
      setBusy(false);
    }
  };

  return (
    <LinearGradientFallback colors={gradients.mosaic} style={styles.root}>
      <KeyboardAvoidingView
        style={styles.inner}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <View style={styles.hero}>
          <View style={styles.mark}>
            <Icon name="restaurant" size={22} color={colors.white} />
          </View>
          <AppText weight="bold" style={styles.brand}>
            Conurbania
          </AppText>
          <AppText weight="medium" style={styles.sub}>
            Salón · Cocina · Caja
          </AppText>
        </View>

        <View style={styles.card}>
          <AppText weight="semibold" style={styles.cardTitle}>
            Iniciar sesión
          </AppText>
          <Field
            label="Usuario"
            autoCapitalize="none"
            autoCorrect={false}
            value={username}
            onChangeText={setUsername}
            placeholder="mozo1"
          />
          <Field
            label="Contraseña"
            secureTextEntry
            value={password}
            onChangeText={setPassword}
            placeholder="••••••••"
          />
          {(error || offlineHint) && (
            <AppText weight="medium" style={styles.error}>
              {error ?? offlineHint}
            </AppText>
          )}
          <PrimaryButton
            title="Entrar"
            icon="log-in-outline"
            onPress={() => void onSubmit()}
            loading={busy}
            disabled={busy}
          />
        </View>
      </KeyboardAvoidingView>
    </LinearGradientFallback>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1 },
  inner: { flex: 1, justifyContent: 'center', padding: space.xl },
  hero: { marginBottom: 28 },
  mark: {
    width: 44,
    height: 44,
    borderRadius: radius.md,
    backgroundColor: colors.teal500,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  brand: { color: colors.white, fontSize: 36, letterSpacing: -0.5 },
  sub: { color: colors.teal300, marginTop: 6, fontSize: 15 },
  card: {
    backgroundColor: colors.white,
    borderRadius: radius.xl,
    padding: space.xl,
    gap: 4,
  },
  cardTitle: { color: colors.gray800, fontSize: 16, marginBottom: 8 },
  error: { color: colors.danger, marginVertical: 8 },
});
