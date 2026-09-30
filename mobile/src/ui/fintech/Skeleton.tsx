import { useEffect, useRef } from 'react';
import { Animated, StyleSheet, View, type ViewStyle } from 'react-native';
import { fx } from '../../theme';

/** Placeholder de carga — pulso suave, sin spinner de pantalla vacía. */
export function Skeleton({
  height = 16,
  width = '100%',
  radius = fx.radius.sm,
  style,
}: {
  height?: number;
  width?: number | `${number}%`;
  radius?: number;
  style?: ViewStyle;
}) {
  const opacity = useRef(new Animated.Value(0.35)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, {
          toValue: 0.75,
          duration: 700,
          useNativeDriver: true,
        }),
        Animated.timing(opacity, {
          toValue: 0.35,
          duration: 700,
          useNativeDriver: true,
        }),
      ]),
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <Animated.View
      style={[
        styles.bone,
        {
          height,
          width,
          borderRadius: radius,
          opacity,
        },
        style,
      ]}
    />
  );
}

export function SkeletonCard({ lines = 2 }: { lines?: number }) {
  return (
    <View style={styles.card}>
      <Skeleton height={12} width="40%" />
      <Skeleton height={28} width="55%" style={{ marginTop: 12 }} />
      {Array.from({ length: lines }).map((_, i) => (
        <Skeleton
          key={i}
          height={10}
          width={i % 2 === 0 ? '80%' : '60%'}
          style={{ marginTop: 10 }}
        />
      ))}
    </View>
  );
}

export function DashboardSkeleton() {
  return (
    <View style={styles.wrap}>
      <SkeletonCard lines={1} />
      <View style={styles.row}>
        <View style={styles.half}>
          <SkeletonCard lines={0} />
        </View>
        <View style={styles.half}>
          <SkeletonCard lines={0} />
        </View>
      </View>
      <SkeletonCard lines={3} />
    </View>
  );
}

export function MesasSkeleton() {
  return (
    <View style={styles.grid}>
      {Array.from({ length: 6 }).map((_, i) => (
        <View key={i} style={styles.mesaBone}>
          <Skeleton height={28} width={40} />
          <Skeleton height={10} width={56} style={{ marginTop: 12 }} />
        </View>
      ))}
    </View>
  );
}

export function OrderSkeleton() {
  return (
    <View style={styles.wrap}>
      <View style={styles.heroBone}>
        <Skeleton height={12} width="30%" />
        <Skeleton height={40} width="50%" style={{ marginTop: 12 }} />
      </View>
      {Array.from({ length: 4 }).map((_, i) => (
        <View key={i} style={styles.lineBone}>
          <Skeleton height={16} width="70%" />
          <Skeleton height={14} width={48} />
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  bone: {
    backgroundColor: '#DCE5E2',
  },
  wrap: { gap: fx.space.md, paddingHorizontal: fx.space.md },
  card: {
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    padding: fx.space.md,
  },
  row: { flexDirection: 'row', gap: fx.space.sm },
  half: { flex: 1 },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: fx.space.sm,
    paddingHorizontal: fx.space.md,
  },
  mesaBone: {
    width: '47%',
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    padding: fx.space.md,
    minHeight: 96,
    justifyContent: 'center',
  },
  heroBone: {
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    padding: fx.space.lg,
  },
  lineBone: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    padding: fx.space.md,
  },
});
