import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../src/auth/AuthContext';
import { canSeeTab } from '../../src/auth/permissions';
import { colors } from '../../src/theme';
import { AppText } from '../../src/ui/primitives';

function TabLabel({ label, focused }: { label: string; focused: boolean }) {
  return (
    <AppText
      weight={focused ? 'bold' : 'medium'}
      style={{
        fontSize: 10,
        color: focused ? colors.teal500 : colors.gray500,
        marginTop: 2,
      }}
    >
      {label}
    </AppText>
  );
}

function tabIcon(name: keyof typeof Ionicons.glyphMap, focused: boolean) {
  return (
    <Ionicons
      name={name}
      size={22}
      color={focused ? colors.teal500 : colors.gray400}
    />
  );
}

export default function TabsLayout() {
  const { user } = useAuth();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          height: 70,
          paddingBottom: 10,
          paddingTop: 8,
          borderTopColor: colors.gray100,
          backgroundColor: colors.white,
        },
        tabBarActiveTintColor: colors.teal500,
        tabBarInactiveTintColor: colors.gray400,
      }}
    >
      <Tabs.Screen
        name="dashboard"
        options={{
          title: 'Inicio',
          href: canSeeTab(user, 'dashboard') ? undefined : null,
          tabBarIcon: ({ focused }) => tabIcon(focused ? 'home' : 'home-outline', focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Inicio" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="mesas"
        options={{
          title: 'Mesas',
          href: canSeeTab(user, 'mesas') ? undefined : null,
          tabBarIcon: ({ focused }) => tabIcon(focused ? 'grid' : 'grid-outline', focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Mesas" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="pedido"
        options={{
          title: 'Nuevo',
          href: canSeeTab(user, 'pedido') ? undefined : null,
          tabBarIcon: ({ focused }) => tabIcon(focused ? 'add-circle' : 'add-circle-outline', focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Nuevo" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="pedidos"
        options={{
          title: 'Pedidos',
          href: canSeeTab(user, 'pedidos') ? undefined : null,
          tabBarIcon: ({ focused }) => tabIcon(focused ? 'receipt' : 'receipt-outline', focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Pedidos" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="cocina"
        options={{
          title: 'Cocina',
          href: canSeeTab(user, 'cocina') ? undefined : null,
          tabBarIcon: ({ focused }) => tabIcon(focused ? 'flame' : 'flame-outline', focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Cocina" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="caja"
        options={{
          title: 'Caja',
          href: canSeeTab(user, 'caja') ? undefined : null,
          tabBarIcon: ({ focused }) => tabIcon(focused ? 'cash' : 'cash-outline', focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Caja" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="stock"
        options={{
          title: 'Stock',
          href: canSeeTab(user, 'stock') ? undefined : null,
          tabBarIcon: ({ focused }) => tabIcon(focused ? 'cube' : 'cube-outline', focused),
          tabBarLabel: ({ focused }) => <TabLabel label="Stock" focused={focused} />,
        }}
      />
    </Tabs>
  );
}
