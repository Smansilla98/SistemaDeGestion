import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { useRouter } from 'expo-router';
import type { Href } from 'expo-router';
import { useAuth } from '../../src/auth/AuthContext';
import { canSeeAdminHub, hasPermission } from '../../src/auth/permissions';
import { colors, radius, space } from '../../src/theme';
import { AppIcon } from '../../src/ui/icons';
import { AppText, PageHeader, PrimaryButton } from '../../src/ui/primitives';

type Link = {
  title: string;
  subtitle: string;
  href: Href;
  bi: string;
  show: boolean;
};

export default function AdminHubScreen() {
  const router = useRouter();
  const { user } = useAuth();

  if (!canSeeAdminHub(user)) {
    return (
      <View style={styles.root}>
        <PageHeader title="Admin" subtitle="Sin acceso" bi="lock-closed" />
        <View style={{ padding: space.lg }}>
          <AppText>No tenés permisos para el hub de administración.</AppText>
          <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        </View>
      </View>
    );
  }

  const links: Link[] = [
    {
      title: 'Categorías',
      subtitle: 'Catálogo de productos',
      href: '/admin/categories' as Href,
      bi: 'folder',
      show: hasPermission(user, 'catalog.read') || hasPermission(user, 'catalog.write'),
    },
    {
      title: 'Sectores',
      subtitle: 'Zonas de mesas',
      href: '/admin/sectors' as Href,
      bi: 'map',
      show: hasPermission(user, 'catalog.read') || hasPermission(user, 'catalog.write'),
    },
    {
      title: 'Descuentos',
      subtitle: 'Tipos de descuento',
      href: '/admin/discounts' as Href,
      bi: 'percent',
      show: hasPermission(user, 'catalog.read') || hasPermission(user, 'catalog.write'),
    },
    {
      title: 'Clientes',
      subtitle: 'Agenda de clientes',
      href: '/admin/clients' as Href,
      bi: 'people',
      show: hasPermission(user, 'clients.read') || hasPermission(user, 'clients.write'),
    },
    {
      title: 'Reportes',
      subtitle: 'Ventas del período',
      href: '/admin/reports' as Href,
      bi: 'graph-up',
      show: hasPermission(user, 'reports.read'),
    },
    {
      title: 'Eventos',
      subtitle: 'Eventos y agenda',
      href: '/admin/events' as Href,
      bi: 'calendar',
      show: hasPermission(user, 'events.read') || hasPermission(user, 'events.write'),
    },
    {
      title: 'Recurrentes',
      subtitle: 'Actividades semanales',
      href: '/admin/recurring' as Href,
      bi: 'repeat',
      show: hasPermission(user, 'events.read') || hasPermission(user, 'events.write'),
    },
    {
      title: 'Gastos fijos',
      subtitle: 'Gastos e ingresos fijos',
      href: '/admin/expenses' as Href,
      bi: 'wallet',
      show: hasPermission(user, 'expenses.read') || hasPermission(user, 'expenses.write'),
    },
    {
      title: 'Notificaciones',
      subtitle: 'Alertas del sistema',
      href: '/admin/notifications' as Href,
      bi: 'bell',
      show: true,
    },
    {
      title: 'Matriz de permisos',
      subtitle: 'Overrides por usuario y rol',
      href: '/admin/permissions' as Href,
      bi: 'key',
      show: hasPermission(user, 'users.read') || hasPermission(user, 'users.write'),
    },
  ];

  return (
    <View style={styles.root}>
      <PageHeader title="Administración" subtitle="Catálogo, reportes y más" bi="gear" />
      <ScrollView contentContainerStyle={styles.body}>
        <PrimaryButton title="Volver" variant="ghost" onPress={() => router.back()} />
        <View style={styles.grid}>
          {links
            .filter((l) => l.show)
            .map((l) => (
              <Pressable key={l.title} style={styles.card} onPress={() => router.push(l.href)}>
                <View style={styles.iconWrap}>
                  <AppIcon bi={l.bi} size={22} color={colors.teal500} />
                </View>
                <AppText weight="bold">{l.title}</AppText>
                <AppText style={styles.sub}>{l.subtitle}</AppText>
              </Pressable>
            ))}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: 'transparent' },
  body: { padding: space.lg, paddingBottom: 48 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginTop: 12 },
  card: {
    width: '47%',
    minHeight: 110,
    backgroundColor: colors.white,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.gray100,
    padding: space.md,
    gap: 4,
  },
  iconWrap: {
    width: 36,
    height: 36,
    borderRadius: radius.md,
    backgroundColor: colors.teal50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  sub: { color: colors.gray500, fontSize: 12 },
});
