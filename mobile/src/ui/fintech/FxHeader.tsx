import { Pressable, StyleSheet, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { fx } from '../../theme';
import { AppText } from '../primitives';

/** Header liviano — más aire vertical y gap entre marca / título / acción. */
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
        {right ? <View style={styles.rightSlot}>{right}</View> : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    backgroundColor: fx.canvas,
    paddingHorizontal: fx.space.md,
    paddingTop: fx.space.lg,
    paddingBottom: fx.space.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  back: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: fx.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandMark: {
    width: 6,
    height: 36,
    borderRadius: 3,
    backgroundColor: fx.brand,
  },
  titles: {
    flex: 1,
    gap: 4,
    paddingVertical: 2,
  },
  title: {
    fontSize: 26,
    color: fx.ink,
    letterSpacing: -0.5,
    lineHeight: 30,
  },
  sub: {
    fontSize: 13,
    color: fx.inkMuted,
    lineHeight: 18,
  },
  rightSlot: {
    marginLeft: 4,
    paddingLeft: 8,
  },
});
