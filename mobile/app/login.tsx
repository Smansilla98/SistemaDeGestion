import { useRef, useState } from 'react';
import {
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
  type TextInput as TextInputType,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../src/auth/AuthContext';
import { ApiError } from '../src/api/client';
import { colors, font, fx, radius, space } from '../src/theme';
import { AppText, Icon, PrimaryButton } from '../src/ui/primitives';
import { AppGradient } from '../src/ui/gradient';

/** Logo del local: resources/logo.png → assets/brand-logo.png (script blanco). */
const brandLogo = require('../assets/brand-logo.png');

/** Colores auth web (layouts/auth.blade.php) alineados al local. */
const brand = {
  primary: '#1e8081',
  secondary: '#22565e',
  dark: '#0a0c10',
  ink: '#262c3b',
} as const;

export default function LoginScreen() {
  const { login, offlineHint } = useAuth();
  const insets = useSafeAreaInsets();
  const passwordRef = useRef<TextInputType>(null);
  const scrollRef = useRef<ScrollView>(null);

  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
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

  const scrollToPassword = () => {
    // Deja el campo visible por encima del teclado
    setTimeout(() => {
      scrollRef.current?.scrollToEnd({ animated: true });
    }, 100);
  };

  return (
    <AppGradient
      colors={[brand.dark, brand.secondary, brand.primary]}
      style={styles.root}
    >
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 8 : 0}
      >
        <ScrollView
          ref={scrollRef}
          contentContainerStyle={[
            styles.scroll,
            {
              paddingTop: Math.max(insets.top, 24) + 12,
              paddingBottom: Math.max(insets.bottom, 24) + 24,
            },
          ]}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="on-drag"
          showsVerticalScrollIndicator={false}
          bounces
        >
          <View style={styles.hero}>
            <Image
              source={brandLogo}
              style={styles.logo}
              resizeMode="contain"
              accessibilityLabel="Conurbania"
            />
            <AppText style={styles.brandName}>
              Conurbania - Restaurante
            </AppText>
          </View>

          <View style={styles.card}>
            <AppText weight="bold" style={styles.cardTitle}>
              Iniciar sesión
            </AppText>
            <AppText style={styles.cardHint}>Ingresá tu usuario y contraseña</AppText>

            <AppText weight="semibold" style={styles.label}>
              Usuario
            </AppText>
            <TextInput
              style={styles.input}
              autoCapitalize="none"
              autoCorrect={false}
              autoComplete="username"
              textContentType="username"
              value={username}
              onChangeText={setUsername}
              placeholder="mozo1"
              placeholderTextColor={colors.gray400}
              returnKeyType="next"
              onSubmitEditing={() => passwordRef.current?.focus()}
              blurOnSubmit={false}
            />

            <AppText weight="semibold" style={styles.label}>
              Contraseña
            </AppText>
            <View style={styles.passwordRow}>
              <TextInput
                ref={passwordRef}
                style={[styles.input, styles.passwordInput]}
                secureTextEntry={!showPassword}
                value={password}
                onChangeText={setPassword}
                placeholder="Tu contraseña"
                placeholderTextColor={colors.gray400}
                autoComplete="password"
                textContentType="password"
                returnKeyType="go"
                onFocus={scrollToPassword}
                onSubmitEditing={() => void onSubmit()}
              />
              <Pressable
                onPress={() => setShowPassword((v) => !v)}
                style={styles.eyeBtn}
                accessibilityRole="button"
                accessibilityLabel={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                hitSlop={8}
              >
                <Icon
                  name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={22}
                  color={fx.inkMuted}
                />
              </Pressable>
            </View>

            {(error || offlineHint) && (
              <AppText weight="medium" style={styles.error}>
                {error ?? offlineHint}
              </AppText>
            )}

            <View style={styles.cta}>
              <PrimaryButton
                title="Entrar"
                icon="log-in-outline"
                onPress={() => void onSubmit()}
                loading={busy}
                disabled={busy}
              />
            </View>
          </View>

          <AppText style={styles.credit}>Desarrollado por: Santi Mansilla - 2026</AppText>
        </ScrollView>
      </KeyboardAvoidingView>
    </AppGradient>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1 },
  flex: { flex: 1 },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    paddingHorizontal: space.lg,
    gap: 28,
  },
  hero: {
    alignItems: 'center',
    gap: 12,
  },
  logo: {
    width: 160,
    height: 160,
  },
  brandName: {
    color: '#fff',
    fontSize: 20,
    letterSpacing: -0.4,
    fontFamily: font.regular,
  },
  card: {
    backgroundColor: colors.white,
    borderRadius: fx.radius.lg,
    padding: space.xl,
    gap: 6,
    shadowColor: '#000',
    shadowOpacity: 0.18,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 8 },
    elevation: 6,
  },
  cardTitle: {
    color: brand.ink,
    fontSize: 22,
    letterSpacing: -0.3,
  },
  cardHint: {
    color: colors.gray500,
    fontSize: 14,
    marginBottom: 12,
  },
  label: {
    color: colors.gray700,
    fontSize: 14,
    marginTop: 10,
    marginBottom: 8,
  },
  input: {
    backgroundColor: fx.canvas,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray200,
    minHeight: 52,
    paddingHorizontal: 14,
    fontSize: 17,
    color: colors.gray900,
    fontFamily: font.regular,
  },
  passwordRow: {
    position: 'relative',
    justifyContent: 'center',
  },
  passwordInput: {
    paddingRight: 52,
  },
  eyeBtn: {
    position: 'absolute',
    right: 6,
    height: 44,
    width: 44,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
  },
  error: {
    color: colors.danger,
    marginTop: 12,
    marginBottom: 4,
    fontSize: 14,
  },
  cta: {
    marginTop: 18,
  },
  credit: {
    color: 'rgba(255,255,255,0.85)',
    fontSize: 10,
    textAlign: 'center',
    marginTop: 8,
    fontFamily: font.regular,
    letterSpacing: 0.2,
  },
});
