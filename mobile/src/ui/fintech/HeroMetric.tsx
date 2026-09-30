import { StyleSheet, View, type StyleProp, type TextStyle } from 'react-native';
import { font, fx } from '../../theme';
import { AppText, MonoText } from '../primitives';

/** Dato principal grande + label secundaria (patrón fintech). */
export function HeroMetric({
  label,
  value,
  hint,
  mono,
  style,
}: {
  label: string;
  value: string | number;
  hint?: string;
  mono?: boolean;
  style?: StyleProp<TextStyle>;
}) {
  return (
    <View>
      <AppText weight="medium" style={styles.label}>
        {label}
      </AppText>
      {mono ? (
        <MonoText medium style={[styles.value, style]}>
          {value}
        </MonoText>
      ) : (
        <AppText weight="bold" style={[styles.value, style]}>
          {value}
        </AppText>
      )}
      {hint ? (
        <AppText style={styles.hint} numberOfLines={1}>
          {hint}
        </AppText>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  label: {
    fontSize: fx.type.micro,
    color: fx.inkMuted,
    letterSpacing: 0.6,
    textTransform: 'uppercase',
    marginBottom: 4,
  },
  value: {
    fontSize: fx.type.hero,
    color: fx.ink,
    letterSpacing: -1.2,
    lineHeight: 40,
    fontFamily: font.bold,
  },
  hint: {
    marginTop: 4,
    fontSize: fx.type.caption,
    color: fx.inkFaint,
  },
});
