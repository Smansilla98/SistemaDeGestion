import { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useAuth } from '../src/auth/AuthContext';
import { ApiError } from '../src/api/client';
import { colors } from '../src/theme';

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
    <KeyboardAvoidingView
      style={styles.root}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <View style={styles.hero}>
        <Text style={styles.brand}>Conurbania</Text>
        <Text style={styles.sub}>Salón · Cocina · Caja</Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.label}>Usuario</Text>
        <TextInput
          autoCapitalize="none"
          autoCorrect={false}
          style={styles.input}
          value={username}
          onChangeText={setUsername}
          placeholder="mozo1"
          placeholderTextColor={colors.gray600}
        />
        <Text style={styles.label}>Contraseña</Text>
        <TextInput
          secureTextEntry
          style={styles.input}
          value={password}
          onChangeText={setPassword}
          placeholder="••••••••"
          placeholderTextColor={colors.gray600}
        />
        {(error || offlineHint) && (
          <Text style={styles.error}>{error ?? offlineHint}</Text>
        )}
        <Pressable style={styles.btn} onPress={onSubmit} disabled={busy}>
          {busy ? <ActivityIndicator color="#fff" /> : <Text style={styles.btnText}>Entrar</Text>}
        </Pressable>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.teal950, justifyContent: 'center', padding: 24 },
  hero: { marginBottom: 28 },
  brand: { color: colors.white, fontSize: 36, fontWeight: '800', letterSpacing: -0.5 },
  sub: { color: colors.teal500, marginTop: 6, fontSize: 16 },
  card: { backgroundColor: colors.white, borderRadius: 16, padding: 20 },
  label: { color: colors.gray600, marginBottom: 6, marginTop: 10, fontWeight: '600' },
  input: {
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: 12,
    paddingHorizontal: 14,
    minHeight: 48,
    fontSize: 16,
    color: colors.gray900,
  },
  error: { color: colors.danger, marginTop: 12 },
  btn: {
    marginTop: 20,
    backgroundColor: colors.teal700,
    borderRadius: 12,
    minHeight: 52,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnText: { color: '#fff', fontWeight: '700', fontSize: 16 },
});
