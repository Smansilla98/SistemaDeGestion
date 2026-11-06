import { LinearGradient } from 'expo-linear-gradient';
import { View, type StyleProp, type ViewStyle } from 'react-native';
import { gradients } from '../theme';

type Props = {
  colors: readonly string[];
  locations?: readonly number[];
  style?: StyleProp<ViewStyle>;
  children?: React.ReactNode;
};

/**
 * Gradiente real alineado a conurbania.css (135deg).
 */
export function AppGradient({ colors: stops, locations, style, children }: Props) {
  const list = (stops.length >= 2 ? [...stops] : [stops[0] ?? '#082822', '#155240']) as [
    string,
    string,
    ...string[],
  ];
  const locs =
    locations && locations.length === list.length
      ? ([...locations] as [number, number, ...number[]])
      : undefined;

  return (
    <LinearGradient
      colors={list}
      locations={locs}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={style}
    >
      {children}
    </LinearGradient>
  );
}

export function MosaicBackground({
  style,
  children,
}: {
  style?: StyleProp<ViewStyle>;
  children?: React.ReactNode;
}) {
  return (
    <AppGradient
      colors={gradients.mosaic}
      locations={gradients.mosaicLocations}
      style={[{ flex: 1 }, style]}
    >
      {children}
    </AppGradient>
  );
}

/** @deprecated usar AppGradient */
export function LinearGradientFallback(props: {
  colors: readonly string[];
  style?: StyleProp<ViewStyle>;
  children?: React.ReactNode;
}) {
  const isMosaic =
    props.colors.length >= 3 &&
    props.colors[0] === gradients.mosaic[0] &&
    props.colors[1] === gradients.mosaic[1];
  return (
    <AppGradient
      {...props}
      locations={isMosaic ? gradients.mosaicLocations : undefined}
    />
  );
}

export function Screen({
  children,
  style,
}: {
  children: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}) {
  return <View style={[{ flex: 1, backgroundColor: 'transparent' }, style]}>{children}</View>;
}
