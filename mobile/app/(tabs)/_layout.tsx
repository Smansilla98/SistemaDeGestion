import { Tabs } from 'expo-router';
import { Text } from 'react-native';
import { useAuth } from '../../src/auth/AuthContext';
import { colors } from '../../src/theme';

function TabLabel({ label, focused }: { label: string; focused: boolean }) {
  return (
    <Text style={{ fontSize: 11, fontWeight: focused ? '700' : '500', color: focused ? colors.teal700 : colors.gray600 }}>
      {label}
    </Text>
  );
}

export default function TabsLayout() {
  const { user } = useAuth();
  const role = user?.role ?? '';

  const showKitchen = ['COCINA', 'ADMIN', 'SUPERADMIN', 'GERENTE', 'ENCARGADO'].includes(role);
  const showCash = ['CAJERO', 'ADMIN', 'SUPERADMIN', 'GERENTE', 'ENCARGADO', 'MOZO'].includes(role);
  const showFloor = ['MOZO', 'CAJERO', 'ADMIN', 'SUPERADMIN', 'GERENTE', 'ENCARGADO', 'SUPERVISOR'].includes(role);

  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: colors.teal700 },
        headerTintColor: '#fff',
        tabBarStyle: { height: 64, paddingBottom: 8, paddingTop: 6 },
        tabBarActiveTintColor: colors.teal700,
      }}
    >
      <Tabs.Screen
        name="mesas"
        options={{
          title: 'Mesas',
          href: showFloor ? undefined : null,
          tabBarLabel: ({ focused }) => <TabLabel label="Mesas" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="pedido"
        options={{
          title: 'Pedido',
          href: showFloor ? undefined : null,
          tabBarLabel: ({ focused }) => <TabLabel label="Pedido" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="pedidos"
        options={{
          title: 'Pedidos',
          href: showFloor || showKitchen ? undefined : null,
          tabBarLabel: ({ focused }) => <TabLabel label="Pedidos" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="cocina"
        options={{
          title: 'Cocina',
          href: showKitchen ? undefined : null,
          tabBarLabel: ({ focused }) => <TabLabel label="Cocina" focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="caja"
        options={{
          title: 'Caja',
          href: showCash ? undefined : null,
          tabBarLabel: ({ focused }) => <TabLabel label="Caja" focused={focused} />,
        }}
      />
    </Tabs>
  );
}
