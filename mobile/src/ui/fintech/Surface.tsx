import { Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { fx } from '../../theme';

/** Card fintech: superficie blanca, aire, sin borde duro. */
export function Surface({
  children,
  style,
  onPress,
  padded = true,
}: {
  children: React.ReactNode;
  style?: StyleProp<ViewStyle>;
  onPress?: () => void;
  padded?: boolean;
}) {
  const body = (
    <View style={[styles.surface, padded && styles.pad, style]}>{children}</View>
  );
  if (!onPress) return body;
  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1 }]}
    >
      {body}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  surface: {
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    shadowColor: fx.shadow.color,
    shadowOpacity: fx.shadow.opacity,
    shadowRadius: fx.shadow.radius,
    shadowOffset: fx.shadow.offset,
    elevation: fx.shadow.elevation,
  },
  pad: {
    paddingHorizontal: fx.space.md,
    paddingVertical: fx.space.md,
  },
});
