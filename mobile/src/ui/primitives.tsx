import { Ionicons } from '@expo/vector-icons';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type StyleProp,
  type TextInputProps,
  type TextProps,
  type TextStyle,
  type ViewStyle,
} from 'react-native';
import { AppGradient } from './gradient';
import { AppIcon } from './icons';
import { colors, font, gradients, radius, space, statusTone } from '../theme';

type Weight = 'regular' | 'medium' | 'semibold' | 'bold';

const weightMap: Record<Weight, string> = {
  regular: font.regular,
  medium: font.medium,
  semibold: font.semibold,
  bold: font.bold,
};

export function AppText({
  children,
  weight = 'regular',
  style,
  ...rest
}: TextProps & { weight?: Weight }) {
  return (
    <Text {...rest} style={[{ fontFamily: weightMap[weight], color: colors.gray900 }, style]}>
      {children}
    </Text>
  );
}

/** Texto DM Mono — montos y códigos (.td-mono / .td-amount). */
export function MonoText({
  children,
  medium,
  style,
  ...rest
}: TextProps & { medium?: boolean }) {
  return (
    <Text
      {...rest}
      style={[
        {
          fontFamily: medium ? font.monoMedium : font.mono,
          color: colors.gray600,
          fontSize: 12,
        },
        style,
      ]}
    >
      {children}
    </Text>
  );
}

export function Amount({
  value,
  style,
}: {
  value: number | string;
  style?: StyleProp<TextStyle>;
}) {
  const n = typeof value === 'number' ? value : Number(value);
  return (
    <MonoText medium style={[{ fontSize: 13, color: colors.gray900, fontWeight: '600' }, style]}>
      ${Number.isFinite(n) ? n.toFixed(2) : '0.00'}
    </MonoText>
  );
}

export function Icon({
  name,
  bi,
  size = 18,
  color = colors.teal500,
}: {
  name?: keyof typeof Ionicons.glyphMap;
  /** Bootstrap icon name (sin bi-), mapeado a Ionicons */
  bi?: string;
  size?: number;
  color?: string;
}) {
  if (bi) return <AppIcon bi={bi} size={size} color={color} />;
  return <Ionicons name={name ?? 'ellipse-outline'} size={size} color={color} />;
}

export function Badge({ label }: { label: string }) {
  const tone = statusTone(label);
  return (
    <View style={[styles.badge, { backgroundColor: tone.bg }]}>
      <AppText weight="semibold" style={[styles.badgeText, { color: tone.fg }]}>
        {label.replace(/_/g, ' ')}
      </AppText>
    </View>
  );
}

export function Card({
  children,
  style,
}: {
  children: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}) {
  return <View style={[styles.card, style]}>{children}</View>;
}

export function PrimaryButton({
  title,
  onPress,
  disabled,
  variant = 'primary',
  icon,
  loading,
}: {
  title: string;
  onPress: () => void;
  disabled?: boolean;
  variant?: 'primary' | 'danger' | 'ghost' | 'amber' | 'outline';
  icon?: keyof typeof Ionicons.glyphMap;
  loading?: boolean;
}) {
  const bg =
    variant === 'danger'
      ? colors.dangerBg
      : variant === 'amber'
        ? colors.amber
        : variant === 'ghost'
          ? 'transparent'
          : variant === 'outline'
            ? colors.white
            : colors.btnPrimary;
  const fg =
    variant === 'danger'
      ? colors.dangerFg
      : variant === 'ghost'
        ? colors.gray600
        : variant === 'outline'
          ? colors.gray700
          : colors.white;
  const borderColor =
    variant === 'outline'
      ? colors.gray200
      : variant === 'danger'
        ? colors.dangerBg
        : variant === 'ghost'
          ? 'transparent'
          : bg;

  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.btn,
        {
          backgroundColor: bg,
          borderColor,
          opacity: disabled || loading ? 0.5 : pressed ? 0.9 : 1,
        },
      ]}
    >
      {loading ? (
        <ActivityIndicator color={fg} />
      ) : (
        <View style={styles.btnInner}>
          {icon ? <Icon name={icon} size={14} color={fg} /> : null}
          <AppText weight="medium" style={[styles.btnText, { color: fg }]}>
            {title}
          </AppText>
        </View>
      )}
    </Pressable>
  );
}

export function PageHeader({
  title,
  subtitle,
  icon,
  bi,
}: {
  title: string;
  subtitle?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  /** Bootstrap icon name (web) → Ionicons */
  bi?: string;
}) {
  const showIcon = Boolean(icon || bi);
  return (
    <AppGradient colors={gradients.pageHeader} style={styles.ph}>
      <View style={styles.phBlob} />
      <View style={styles.phInner}>
        <View style={styles.phTitleRow}>
          {showIcon ? (
            <View style={styles.phIconWrap}>
              <Icon name={icon} bi={bi} size={20} color="rgba(255,255,255,0.9)" />
            </View>
          ) : null}
          <AppText weight="bold" style={styles.phTitle}>
            {title}
          </AppText>
        </View>
        {subtitle ? (
          <AppText weight="regular" style={styles.phSub}>
            {subtitle}
          </AppText>
        ) : null}
      </View>
    </AppGradient>
  );
}

export function SectionLabel({ children }: { children: string }) {
  return (
    <AppText weight="bold" style={styles.section}>
      {children}
    </AppText>
  );
}

export function Field({
  label,
  ...props
}: TextInputProps & { label?: string }) {
  return (
    <View style={styles.field}>
      {label ? (
        <AppText weight="semibold" style={styles.fieldLabel}>
          {label}
        </AppText>
      ) : null}
      <TextInput
        placeholderTextColor={colors.gray400}
        {...props}
        style={[styles.input, props.style as TextStyle]}
      />
    </View>
  );
}

export function StatTile({
  label,
  value,
  accent,
  icon,
  bi,
  mono,
}: {
  label: string;
  value: string | number;
  accent?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  bi?: string;
  /** Montos / códigos en DM Mono */
  mono?: boolean;
}) {
  const useMono =
    mono ??
    (typeof value === 'string' && value.trim().startsWith('$'));
  return (
    <View style={styles.stat}>
      <View style={[styles.statAccent, { backgroundColor: accent ?? colors.teal500 }]} />
      {icon || bi ? (
        <View style={styles.statIcon}>
          <Icon name={icon} bi={bi} size={18} color={accent ?? colors.teal500} />
        </View>
      ) : null}
      <AppText weight="bold" style={styles.statLabel}>
        {label}
      </AppText>
      {useMono ? (
        <MonoText medium style={[styles.statVal, accent ? { color: accent } : null]}>
          {value}
        </MonoText>
      ) : (
        <AppText weight="bold" style={[styles.statVal, accent ? { color: accent } : null]}>
          {value}
        </AppText>
      )}
    </View>
  );
}

export function Chip({
  label,
  selected,
  onPress,
  tone,
}: {
  label: string;
  selected?: boolean;
  onPress?: () => void;
  tone?: string;
}) {
  return (
    <Pressable
      onPress={onPress}
      style={[
        styles.chip,
        selected && styles.chipOn,
        tone ? { borderColor: tone } : null,
      ]}
    >
      <AppText
        weight={selected ? 'semibold' : 'medium'}
        style={[styles.chipText, selected && styles.chipTextOn]}
      >
        {label}
      </AppText>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 20,
  },
  badgeText: { fontSize: 11, letterSpacing: 0.2 },
  card: {
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    padding: space.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
  },
  btn: {
    minHeight: 40,
    borderRadius: radius.md,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderWidth: 1,
  },
  btnInner: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  btnText: { fontSize: 13 },
  ph: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 18,
    overflow: 'hidden',
  },
  phBlob: {
    position: 'absolute',
    right: -40,
    top: -40,
    width: 200,
    height: 200,
    borderRadius: 100,
    backgroundColor: 'rgba(29,158,117,0.12)',
  },
  phInner: { zIndex: 1 },
  phTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  phIconWrap: {
    width: 32,
    height: 32,
    borderRadius: radius.md,
    backgroundColor: 'rgba(29,158,117,0.25)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  phTitle: {
    color: colors.white,
    fontSize: 24,
    letterSpacing: -0.5,
  },
  phSub: { color: 'rgba(255,255,255,0.5)', marginTop: 2, fontSize: 13 },
  section: {
    fontSize: 11,
    color: colors.gray500,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    marginTop: space.md,
    marginBottom: space.sm,
  },
  field: { marginBottom: space.sm },
  fieldLabel: { color: colors.gray600, marginBottom: 6, fontSize: 13 },
  input: {
    backgroundColor: colors.white,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray200,
    minHeight: 40,
    paddingHorizontal: 12,
    fontSize: 13,
    color: colors.gray800,
    fontFamily: font.regular,
  },
  stat: {
    width: '100%',
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    padding: 18,
    borderWidth: 1,
    borderColor: colors.gray100,
    overflow: 'hidden',
    position: 'relative',
  },
  statAccent: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 3,
  },
  statIcon: {
    width: 38,
    height: 38,
    borderRadius: radius.md,
    backgroundColor: colors.teal50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  statVal: {
    fontSize: 28,
    color: colors.gray900,
    letterSpacing: -1,
    lineHeight: 32,
    marginTop: 2,
  },
  statLabel: {
    fontSize: 11,
    color: colors.gray400,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray200,
    backgroundColor: colors.white,
  },
  chipOn: {
    backgroundColor: colors.teal50,
    borderColor: colors.teal400,
  },
  chipText: { fontSize: 13, color: colors.gray600 },
  chipTextOn: { color: colors.teal700 },
});
