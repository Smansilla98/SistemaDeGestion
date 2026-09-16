import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../src/auth/AuthContext';
import { canSeeAdminHub, hasPermission } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { AppText } from '../../src/ui/primitives';
import { FadeIn, FxHeader, Surface } from '../../src/ui/fintech';

type Link = {
  title: string;
  subtitle: string;
  href: Href;
  icon: keyof typeof Ionicons.glyphMap;
  show: boolean;
};

export default function AdminHubScreen() {
  const router = useRouter();
  const { user } = useAuth();

  if (!canSeeAdminHub(user)) {
    return (
      <View style={styles.root}>
        <FxHeader title="Admin" subtitle="Sin acceso" onBack={() => router.back()} />
        <View style={styles.body}>
          <Surface>
            <AppText style={styles.locked}>No tenés permisos para el hub de administración.</AppText>
          </Surface>
        </View>
      </View>
    );
  }

  const links: Link[] = [
    {
      title: 'Categorías',
      subtitle: 'Catálogo de productos',
      href: '/admin/categories' as Href,
      icon: 'folder-outline',
      show: hasPermission(user, 'catalog.read') || hasPermission(user, 'catalog.write'),
    },
    {
      title: 'Sectores',
      subtitle: 'Zonas de mesas',
      href: '/admin/sectors' as Href,
      icon: 'map-outline',
      show: hasPermission(user, 'catalog.read') || hasPermission(user, 'catalog.write'),
    },
    {
      title: 'Descuentos',
      subtitle: 'Tipos de descuento',
      href: '/admin/discounts' as Href,
      icon: 'pricetag-outline',
      show: hasPermission(user, 'catalog.read') || hasPermission(user, 'catalog.write'),
    },
    {
      title: 'Clientes',
      subtitle: 'Agenda de clientes',
      href: '/admin/clients' as Href,
      icon: 'people-outline',
      show: hasPermission(user, 'clients.read') || hasPermission(user, 'clients.write'),
    },
    {
      title: 'Reportes',
      subtitle: 'Ventas del período',
      href: '/admin/reports' as Href,
      icon: 'bar-chart-outline',
      show: hasPermission(user, 'reports.read'),
    },
    {
      title: 'Eventos',
      subtitle: 'Eventos y agenda',
      href: '/admin/events' as Href,
      icon: 'calendar-outline',
      show: hasPermission(user, 'events.read') || hasPermission(user, 'events.write'),
    },
    {
      title: 'Recurrentes',
      subtitle: 'Actividades semanales',
      href: '/admin/recurring' as Href,
      icon: 'repeat-outline',
      show: hasPermission(user, 'events.read') || hasPermission(user, 'events.write'),
    },
    {
      title: 'Gastos fijos',
      subtitle: 'Gastos e ingresos fijos',
      href: '/admin/expenses' as Href,
      icon: 'wallet-outline',
      show: hasPermission(user, 'expenses.read') || hasPermission(user, 'expenses.write'),
    },
    {
      title: 'Notificaciones',
      subtitle: 'Alertas del sistema',
      href: '/admin/notifications' as Href,
      icon: 'notifications-outline',
      show: true,
    },
    {
      title: 'Matriz de permisos',
      subtitle: 'Overrides por usuario y rol',
      href: '/admin/permissions' as Href,
      icon: 'key-outline',
      show: hasPermission(user, 'users.read') || hasPermission(user, 'users.write'),
    },
  ];

  const visible = links.filter((l) => l.show);

  return (
    <View style={styles.root}>
      <FxHeader
        title="Administración"
        subtitle="Catálogo, reportes y más"
        onBack={() => router.back()}
      />
      <ScrollView
        contentContainerStyle={styles.body}
        showsVerticalScrollIndicator={false}
      >
        <FadeIn>
          <AppText weight="semibold" style={styles.section}>
            Módulos
          </AppText>
          <View style={styles.grid}>
            {visible.map((l) => (
              <Pressable
                key={l.title}
                style={({ pressed }) => [styles.card, pressed && { opacity: 0.85 }]}
                onPress={() => router.push(l.href)}
              >
                <View style={styles.iconWrap}>
                  <Ionicons name={l.icon} size={24} color={fx.brand} />
                </View>
                <AppText weight="semibold" style={styles.cardTitle} numberOfLines={2}>
                  {l.title}
                </AppText>
                <AppText style={styles.sub} numberOfLines={2}>
                  {l.subtitle}
                </AppText>
              </Pressable>
            ))}
          </View>
        </FadeIn>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: fx.canvas },
  body: {
    paddingHorizontal: fx.space.md,
    paddingBottom: 56,
  },
  locked: { color: fx.inkMuted, fontSize: 15, lineHeight: 22 },
  section: {
    fontSize: 13,
    color: fx.inkMuted,
    marginBottom: 12,
    marginTop: 4,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 16,
  },
  card: {
    width: '47%',
    minHeight: 104,
    backgroundColor: fx.surface,
    borderRadius: fx.radius.md,
    paddingVertical: 18,
    paddingHorizontal: 12,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  iconWrap: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: fx.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  cardTitle: {
    fontSize: 15,
    color: fx.ink,
    textAlign: 'center',
  },
  sub: {
    color: fx.inkFaint,
    fontSize: 12,
    textAlign: 'center',
    lineHeight: 16,
  },
});
