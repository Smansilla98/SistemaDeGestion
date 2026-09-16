import { Pressable, StyleSheet, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { fx } from '../../theme';
import { AppText } from '../primitives';

/** Header liviano fintech — sin degradé pesado. */
export function FxHeader({
  title,
  subtitle,
  onBack,
  right,
}: {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  right?: React.ReactNode;
}) {
  return (
    <View style={styles.wrap}>
      <View style={styles.row}>
        {onBack ? (
          <Pressable
            onPress={onBack}
            hitSlop={10}
            style={styles.back}
            accessibilityRole="button"
          >
            <Ionicons name="chevron-back" size={22} color={fx.ink} />
          </Pressable>
        ) : (
          <View style={styles.brandMark} />
        )}
        <View style={styles.titles}>
          <AppText weight="bold" style={styles.title} numberOfLines={1}>
            {title}
          </AppText>
          {subtitle ? (
            <AppText style={styles.sub} numberOfLines={1}>
              {subtitle}
            </AppText>
          ) : null}
        </View>
        {right ?? <View style={{ width: 36 }} />}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    backgroundColor: fx.canvas,
    paddingHorizontal: fx.space.md,
    paddingTop: fx.space.sm,
    paddingBottom: fx.space.md,
  },
  row: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  back: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: fx.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandMark: {
    width: 8,
    height: 28,
    borderRadius: 4,
    backgroundColor: fx.brand,
  },
  titles: { flex: 1 },
  title: {
    fontSize: fx.type.title,
    color: fx.ink,
    letterSpacing: -0.4,
  },
  sub: {
    fontSize: fx.type.caption,
    color: fx.inkMuted,
    marginTop: 2,
  },
});
