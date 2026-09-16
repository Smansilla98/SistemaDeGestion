import { StyleSheet, View } from 'react-native';
import { RectButton, Swipeable } from 'react-native-gesture-handler';
import { fx } from '../../theme';
import { AppText } from '../primitives';

type Action = {
  label: string;
  onPress: () => void;
  tone?: 'brand' | 'danger' | 'neutral';
};

/**
 * Fila con gesto swipe (gesture-handler, sin Reanimated).
 * Acciones a la derecha (swipe left) / izquierda (swipe right).
 */
export function SwipeAction({
  children,
  leftActions,
  rightActions,
}: {
  children: React.ReactNode;
  leftActions?: Action[];
  rightActions?: Action[];
}) {
  const renderActions = (actions: Action[] | undefined, side: 'left' | 'right') => {
    if (!actions?.length) return null;
    return (
      <View style={[styles.actions, side === 'left' ? styles.left : styles.right]}>
        {actions.map((a) => (
          <RectButton
            key={a.label}
            style={[
              styles.btn,
              a.tone === 'danger' && styles.danger,
              a.tone === 'neutral' && styles.neutral,
              (!a.tone || a.tone === 'brand') && styles.brand,
            ]}
            onPress={a.onPress}
          >
            <AppText weight="semibold" style={styles.btnText}>
              {a.label}
            </AppText>
          </RectButton>
        ))}
      </View>
    );
  };

  return (
    <Swipeable
      friction={2}
      overshootFriction={8}
      renderLeftActions={() => renderActions(leftActions, 'left')}
      renderRightActions={() => renderActions(rightActions, 'right')}
    >
      {children}
    </Swipeable>
  );
}

const styles = StyleSheet.create({
  actions: {
    flexDirection: 'row',
    alignItems: 'stretch',
  },
  left: { marginRight: 4 },
  right: { marginLeft: 4 },
  btn: {
    justifyContent: 'center',
    paddingHorizontal: 18,
    minWidth: 84,
    borderRadius: fx.radius.md,
    marginVertical: 2,
  },
  brand: { backgroundColor: fx.brand },
  danger: { backgroundColor: fx.danger },
  neutral: { backgroundColor: fx.inkMuted },
  btnText: { color: '#fff', fontSize: 13 },
});
