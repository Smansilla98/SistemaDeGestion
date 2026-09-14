import { Ionicons } from '@expo/vector-icons';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type TextInputProps,
  type TextProps,
  type TextStyle,
  type ViewStyle,
} from 'react-native';
import { LinearGradientFallback } from './gradient';
import { colors, font, radius, space, statusTone } from '../theme';

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

export function Icon({
  name,
  size = 18,
  color = colors.teal500,
}: {
  name: keyof typeof Ionicons.glyphMap;
  size?: number;
  color?: string;
}) {
  return <Ionicons name={name} size={size} color={color} />;
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

export function Card({ children, style }: { children: React.ReactNode; style?: ViewStyle }) {
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
  variant?: 'primary' | 'danger' | 'ghost' | 'amber';
  icon?: keyof typeof Ionicons.glyphMap;
  loading?: boolean;
}) {
  const bg =
    variant === 'danger'
      ? colors.danger
      : variant === 'amber'
        ? colors.amber
        : variant === 'ghost'
          ? colors.gray100
          : colors.teal500;
  const fg = variant === 'ghost' ? colors.gray900 : colors.white;
  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.btn,
        { backgroundColor: bg, opacity: disabled || loading ? 0.5 : pressed ? 0.9 : 1 },
      ]}
    >
      {loading ? (
        <ActivityIndicator color={fg} />
      ) : (
        <View style={styles.btnInner}>
          {icon ? <Icon name={icon} size={18} color={fg} /> : null}
          <AppText weight="bold" style={[styles.btnText, { color: fg }]}>
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
}: {
  title: string;
  subtitle?: string;
  icon?: keyof typeof Ionicons.glyphMap;
}) {
  return (
    <LinearGradientFallback colors={[colors.teal900, colors.teal700, colors.teal600]} style={styles.ph}>
      <View style={styles.phBlob} />
      <View style={styles.phInner}>
        <View style={styles.phTitleRow}>
          {icon ? (
            <View style={styles.phIconWrap}>
              <Icon name={icon} size={18} color={colors.white} />
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
    </LinearGradientFallback>
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
}: {
  label: string;
  value: string | number;
  accent?: string;
  icon?: keyof typeof Ionicons.glyphMap;
}) {
  return (
    <View style={styles.stat}>
      {icon ? (
        <View style={styles.statIcon}>
          <Icon name={icon} size={16} color={accent ?? colors.teal500} />
        </View>
      ) : null}
      <AppText weight="bold" style={[styles.statVal, accent ? { color: accent } : null]}>
        {value}
      </AppText>
      <AppText weight="medium" style={styles.statLabel}>
        {label}
      </AppText>
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
        selected && { backgroundColor: tone ?? colors.teal500, borderColor: tone ?? colors.teal500 },
      ]}
    >
      <AppText
        weight="bold"
        style={{ color: selected ? colors.white : colors.gray800, fontSize: 12 }}
      >
        {label}
      </AppText>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
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
    minHeight: 52,
    borderRadius: radius.lg,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: space.lg,
  },
  btnInner: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  btnText: { fontSize: 16 },
  ph: {
    paddingHorizontal: space.lg,
    paddingTop: space.lg,
    paddingBottom: space.xl,
    overflow: 'hidden',
  },
  phBlob: {
    position: 'absolute',
    right: -36,
    top: -40,
    width: 160,
    height: 160,
    borderRadius: 80,
    backgroundColor: 'rgba(29,158,117,0.18)',
  },
  phInner: { zIndex: 1 },
  phTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  phIconWrap: {
    width: 32,
    height: 32,
    borderRadius: radius.md,
    backgroundColor: 'rgba(29,158,117,0.35)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  phTitle: { color: colors.white, fontSize: 22, letterSpacing: -0.4 },
  phSub: { color: 'rgba(255,255,255,0.55)', marginTop: 4, fontSize: 13 },
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
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray200,
    minHeight: 48,
    paddingHorizontal: 14,
    fontSize: 16,
    color: colors.gray900,
    fontFamily: font.regular,
  },
  stat: {
    flex: 1,
    minWidth: '45%',
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    padding: space.md,
    borderWidth: 1,
    borderColor: colors.gray100,
  },
  statIcon: {
    width: 28,
    height: 28,
    borderRadius: radius.sm,
    backgroundColor: colors.teal50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  statVal: { fontSize: 22, color: colors.teal500 },
  statLabel: { marginTop: 4, fontSize: 12, color: colors.gray600 },
  chip: {
    minHeight: 40,
    paddingHorizontal: 12,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.gray200,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
