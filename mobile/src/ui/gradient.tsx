import { View, type StyleProp, type ViewStyle } from 'react-native';
import { colors } from '../theme';

/**
 * Gradiente Conurbania sin dependencia extra:
 * capas de color (mosaic / page-header web).
 */
export function LinearGradientFallback({
  colors: stops,
  style,
  children,
}: {
  colors: readonly string[];
  style?: StyleProp<ViewStyle>;
  children?: React.ReactNode;
}) {
  const a = stops[0] ?? colors.teal900;
  const b = stops[1] ?? colors.teal700;
  const c = stops[2] ?? colors.teal600;

  return (
    <View style={[{ backgroundColor: b, overflow: 'hidden' }, style]}>
      <View
        pointerEvents="none"
        style={{
          position: 'absolute',
          left: 0,
          top: 0,
          bottom: 0,
          width: '55%',
          backgroundColor: a,
          opacity: 0.95,
        }}
      />
      <View
        pointerEvents="none"
        style={{
          position: 'absolute',
          right: 0,
          top: 0,
          bottom: 0,
          width: '45%',
          backgroundColor: c,
          opacity: 0.55,
        }}
      />
      {children}
    </View>
  );
}
