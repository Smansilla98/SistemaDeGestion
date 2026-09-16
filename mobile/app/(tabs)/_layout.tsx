import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../src/auth/AuthContext';
import { isPrimaryTab } from '../../src/auth/permissions';
import { fx } from '../../src/theme';
import { tabIcons } from '../../src/ui/icons';
import { AppText } from '../../src/ui/primitives';

function TabLabel({ label, focused }: { label: string; focused: boolean }) {
  return (
    <AppText
      weight={focused ? 'bold' : 'medium'}
      style={{
        fontSize: 11,
        color: focused ? fx.brand : fx.inkFaint,
        marginTop: 1,
      }}
    >
      {label}
    </AppText>
  );
}

function tabIcon(
  active: keyof typeof Ionicons.glyphMap,
  inactive: keyof typeof Ionicons.glyphMap,
  focused: boolean,
) {
  return (
    <Ionicons
      name={focused ? active : inactive}
      size={22}
      color={focused ? fx.brand : fx.inkFaint}
    />
  );
}

/**
 * Orden visual: Pedidos → Mesas → Caja (mozo).
 * Inicio / Cocina / Nuevo / Stock quedan ocultos según isPrimaryTab.
 */
export default function TabsLayout() {
  const { user } = useAuth();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          height: 60,
          paddingBottom: 8,
          paddingTop: 6,
          borderTopColor: fx.hairline,
          backgroundColor: fx.surface,
          elevation: 0,
          shadowOpacity: 0,
        },
        tabBarActiveTintColor: fx.brand,
        tabBarInactiveTintColor: fx.inkFaint,
      }}
    >
      <Tabs.Screen
        name="dashboard"
        options={{
          title: 'Inicio',
          href: isPrimaryTab(user, 'dashboard') ? undefined : null,
          tabBarIcon: ({ focused }) =>
            tabIcon(tabIcons.dashboard.active, tabIcons.dashboard.inactive, focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Inicio" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="pedidos"
        options={{
          title: 'Pedidos',
          href: isPrimaryTab(user, 'pedidos') ? undefined : null,
          tabBarIcon: ({ focused }) =>
            tabIcon(tabIcons.pedidos.active, tabIcons.pedidos.inactive, focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Pedidos" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="mesas"
        options={{
          title: 'Mesas',
          href: isPrimaryTab(user, 'mesas') ? undefined : null,
          tabBarIcon: ({ focused }) =>
            tabIcon(tabIcons.mesas.active, tabIcons.mesas.inactive, focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Mesas" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="caja"
        options={{
          title: 'Caja',
          href: isPrimaryTab(user, 'caja') ? undefined : null,
          tabBarIcon: ({ focused }) =>
            tabIcon(tabIcons.caja.active, tabIcons.caja.inactive, focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Caja" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="pedido"
        options={{
          title: 'Nuevo',
          href: isPrimaryTab(user, 'pedido') ? undefined : null,
          tabBarIcon: ({ focused }) =>
            tabIcon(tabIcons.pedido.active, tabIcons.pedido.inactive, focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Nuevo" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="cocina"
        options={{
          title: 'Cocina',
          href: isPrimaryTab(user, 'cocina') ? undefined : null,
          tabBarIcon: ({ focused }) =>
            tabIcon(tabIcons.cocina.active, tabIcons.cocina.inactive, focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Cocina" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="stock"
        options={{
          title: 'Stock',
          href: isPrimaryTab(user, 'stock') ? undefined : null,
          tabBarIcon: ({ focused }) =>
            tabIcon(tabIcons.stock.active, tabIcons.stock.inactive, focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Stock" focused={focused} />,
        }}
      />
    </Tabs>
  );
}
