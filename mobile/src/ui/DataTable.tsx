import type { ReactNode } from 'react';
import { Pressable, ScrollView, StyleSheet, View, type ViewStyle } from 'react-native';
import { colors, radius, space } from '../theme';
import { AppText, MonoText } from './primitives';

export type DataColumn<T> = {
  key: string;
  title: string;
  flex?: number;
  mono?: boolean;
  bold?: boolean;
  align?: 'left' | 'right';
  render: (row: T) => string | number | ReactNode;
};

type Props<T> = {
  columns: DataColumn<T>[];
  rows: T[];
  keyExtractor: (row: T) => string | number;
  onPressRow?: (row: T) => void;
  emptyText?: string;
  style?: ViewStyle;
};

/**
 * Tabla densa estilo web (.conurbania-app thead/tbody).
 */
export function DataTable<T>({
  columns,
  rows,
  keyExtractor,
  onPressRow,
  emptyText = 'Sin datos',
  style,
}: Props<T>) {
  return (
    <View style={[styles.wrap, style]}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false}>
        <View style={styles.inner}>
          <View style={styles.head}>
            {columns.map((c) => (
              <View
                key={c.key}
                style={[
                  styles.th,
                  { flex: c.flex ?? 1, minWidth: 72 },
                  c.align === 'right' && styles.alignRight,
                ]}
              >
                <AppText weight="semibold" style={styles.thText}>
                  {c.title}
                </AppText>
              </View>
            ))}
          </View>
          {rows.length === 0 ? (
            <View style={styles.empty}>
              <AppText style={styles.emptyText}>{emptyText}</AppText>
            </View>
          ) : (
            rows.map((row) => {
              const cells = (
                <View style={styles.tr}>
                  {columns.map((c) => {
                    const val = c.render(row);
                    const isNode = typeof val !== 'string' && typeof val !== 'number';
                    return (
                      <View
                        key={c.key}
                        style={[
                          styles.td,
                          { flex: c.flex ?? 1, minWidth: 72 },
                          c.align === 'right' && styles.alignRight,
                        ]}
                      >
                        {isNode ? (
                          val
                        ) : c.mono ? (
                          <MonoText medium={c.bold} style={styles.tdMono}>
                            {String(val)}
                          </MonoText>
                        ) : (
                          <AppText
                            weight={c.bold ? 'semibold' : 'regular'}
                            style={[styles.tdText, c.bold && styles.tdBold]}
                          >
                            {String(val)}
                          </AppText>
                        )}
                      </View>
                    );
                  })}
                </View>
              );
              if (!onPressRow) {
                return <View key={String(keyExtractor(row))}>{cells}</View>;
              }
              return (
                <Pressable
                  key={String(keyExtractor(row))}
                  onPress={() => onPressRow(row)}
                  style={({ pressed }) => [pressed && styles.trPressed]}
                >
                  {cells}
                </Pressable>
              );
            })
          )}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
    overflow: 'hidden',
  },
  inner: { minWidth: '100%' },
  head: {
    flexDirection: 'row',
    backgroundColor: colors.gray50,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
    paddingVertical: 10,
    paddingHorizontal: space.md,
  },
  th: { paddingHorizontal: 4 },
  thText: {
    fontSize: 10.5,
    color: colors.gray400,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  tr: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    borderBottomColor: colors.gray50,
    paddingVertical: 11,
    paddingHorizontal: space.md,
    alignItems: 'center',
  },
  trPressed: { backgroundColor: colors.gray50 },
  td: { paddingHorizontal: 4 },
  tdText: { fontSize: 13, color: colors.gray700 },
  tdBold: { color: colors.gray900 },
  tdMono: { fontSize: 12, color: colors.gray600 },
  alignRight: { alignItems: 'flex-end' },
  empty: { padding: space.xl, alignItems: 'center' },
  emptyText: { color: colors.gray500, fontSize: 13 },
});
