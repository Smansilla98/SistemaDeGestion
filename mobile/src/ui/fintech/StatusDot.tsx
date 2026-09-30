import { StyleSheet, View } from 'react-native';
import { fx, statusDot } from '../../theme';
import { AppText } from '../primitives';

/** Indicador de estado minimal: punto + texto gris (sin badge relleno). */
export function StatusDot({ status, size = 'md' }: { status: string; size?: 'sm' | 'md' }) {
  const { color, label } = statusDot(status);
  return (
    <View style={styles.row}>
      <View
        style={[
          styles.dot,
          size === 'sm' && styles.dotSm,
          { backgroundColor: color },
        ]}
      />
      <AppText
        weight="medium"
        style={[styles.label, size === 'sm' && styles.labelSm]}
        numberOfLines={1}
      >
        {label}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  dotSm: { width: 6, height: 6, borderRadius: 3 },
  label: {
    fontSize: fx.type.caption,
    color: fx.inkMuted,
    textTransform: 'capitalize',
  },
  labelSm: { fontSize: fx.type.micro },
});
