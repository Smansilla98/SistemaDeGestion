import { Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { fx } from '../../theme';
import { AppText } from '../primitives';

/**
 * Sheet modal con botón Cerrar claro (accesible para +60).
 */
export function ModalSheet({
  visible,
  title,
  onClose,
  children,
  maxHeight = '80%',
}: {
  visible: boolean;
  title: string;
  onClose: () => void;
  children: React.ReactNode;
  maxHeight?: `${number}%` | number;
}) {
  const insets = useSafeAreaInsets();

  return (
    <Modal visible={visible} animationType="fade" transparent onRequestClose={onClose}>
      <View style={styles.bg}>
        <Pressable style={styles.dismiss} onPress={onClose} accessibilityLabel="Cerrar" />
        <View style={[styles.sheet, { maxHeight, paddingBottom: 8 + insets.bottom }]}>
          <View style={styles.handle} />
          <View style={styles.head}>
            <AppText weight="bold" style={styles.title} numberOfLines={1}>
              {title}
            </AppText>
            <Pressable
              onPress={onClose}
              style={styles.closeBtn}
              accessibilityRole="button"
              accessibilityLabel="Cerrar"
              hitSlop={8}
            >
              <Ionicons name="close" size={22} color={fx.ink} />
            </Pressable>
          </View>
          <ScrollView
            style={styles.body}
            contentContainerStyle={styles.bodyContent}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            {children}
          </ScrollView>
          {/* El botón "Cerrar" es lo último tocable de la barra inferior — si
              el teléfono tiene gestos de navegación, sin el inset de acá
              queda justo debajo de esa franja y no registra el toque. */}
          <Pressable
            onPress={onClose}
            style={[styles.closeBar, { marginBottom: fx.space.md + insets.bottom }]}
            accessibilityRole="button"
          >
            <AppText weight="semibold" style={styles.closeBarText}>
              Cerrar
            </AppText>
          </Pressable>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  bg: {
    flex: 1,
    backgroundColor: 'rgba(3,26,22,0.4)',
    justifyContent: 'flex-end',
  },
  dismiss: { flex: 1 },
  sheet: {
    backgroundColor: fx.surface,
    borderTopLeftRadius: fx.radius.lg,
    borderTopRightRadius: fx.radius.lg,
    paddingBottom: 8,
    overflow: 'hidden',
  },
  handle: {
    alignSelf: 'center',
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: fx.hairline,
    marginTop: 10,
    marginBottom: 4,
  },
  head: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: fx.space.md,
    paddingVertical: 12,
    gap: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: fx.hairline,
  },
  title: {
    flex: 1,
    fontSize: 18,
    color: fx.ink,
  },
  closeBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: fx.canvas,
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: { flexGrow: 0 },
  bodyContent: {
    padding: fx.space.md,
    gap: 12,
    paddingBottom: 20,
  },
  closeBar: {
    marginHorizontal: fx.space.md,
    marginBottom: fx.space.md,
    marginTop: 4,
    minHeight: 48,
    borderRadius: fx.radius.md,
    backgroundColor: fx.canvas,
    alignItems: 'center',
    justifyContent: 'center',
  },
  closeBarText: {
    fontSize: 16,
    color: fx.inkMuted,
  },
});
