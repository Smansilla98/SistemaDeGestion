import type { Href } from 'expo-router';
import type { ApiUser } from '../api/types';

export function hasPermission(user: ApiUser | null | undefined, permission: string): boolean {
  if (!user) return false;
  const perms = user.permissions ?? [];
  if (perms.includes('*')) return true;
  return perms.includes(permission);
}

/** Módulo vendible. Si el backend no manda el mapa, se considera habilitado. */
export function hasModule(user: ApiUser | null | undefined, key: string): boolean {
  if (!user?.modules) return true;
  if (user.role === 'SUPERADMIN') return true;
  return user.modules[key] !== false;
}

export function isAdminRole(role?: string): boolean {
  return role === 'ADMIN' || role === 'SUPERADMIN';
}

export function canSeeAdminHub(user: ApiUser | null | undefined): boolean {
  if (!user) return false;
  return isAdminRole(user.role) || user.role === 'GERENTE';
}

export function canSeeTab(
  user: ApiUser | null | undefined,
  tab: 'dashboard' | 'mesas' | 'pedido' | 'pedidos' | 'cocina' | 'caja' | 'stock',
): boolean {
  switch (tab) {
    case 'dashboard':
      return hasPermission(user, 'dashboard.read');
    case 'mesas':
      return hasPermission(user, 'tables.read') && hasModule(user, 'tables');
    case 'pedido':
      return hasPermission(user, 'tables.read') && hasModule(user, 'tables') && hasModule(user, 'orders');
    case 'pedidos':
      return hasPermission(user, 'orders.read') && hasModule(user, 'orders');
    case 'cocina':
      return hasPermission(user, 'kitchen.read') && hasModule(user, 'kitchen');
    case 'caja':
      return hasPermission(user, 'cash.read') && hasModule(user, 'cash');
    case 'stock':
      return hasPermission(user, 'stock.read') && hasModule(user, 'stock');
    default:
      return false;
  }
}

type TabKey = 'dashboard' | 'mesas' | 'pedido' | 'pedidos' | 'cocina' | 'caja' | 'stock';

/**
 * Bottom nav por rol — pocos accesos claros.
 * Mozos: Pedidos · Mesas · Caja. El resto desde Accesos / pantallas internas.
 */
export function isPrimaryTab(
  user: ApiUser | null | undefined,
  tab: TabKey,
): boolean {
  if (!canSeeTab(user, tab)) return false;
  const role = user?.role ?? '';

  const primary: TabKey[] =
    role === 'MOZO'
      ? ['pedidos', 'mesas', 'caja']
      : role === 'COCINA'
        ? ['cocina', 'pedidos']
        : role === 'CAJERO'
          ? ['caja', 'mesas', 'pedidos']
          : /* ADMIN / SUPERADMIN / GERENTE / ENCARGADO / default */
            ['dashboard', 'mesas', 'pedidos', 'caja'];

  return primary.includes(tab);
}

const TAB_HREF: Record<TabKey, Href> = {
  dashboard: '/(tabs)/dashboard' as Href,
  mesas: '/(tabs)/mesas' as Href,
  pedido: '/(tabs)/pedido' as Href,
  pedidos: '/(tabs)/pedidos' as Href,
  cocina: '/(tabs)/cocina' as Href,
  caja: '/(tabs)/caja' as Href,
  stock: '/(tabs)/stock' as Href,
};

/** Primera pantalla usable según rol y módulos licenciados. */
export function homeHrefForRole(user: ApiUser | null | undefined): Href {
  const role = user?.role ?? '';
  const order: TabKey[] =
    role === 'COCINA'
      ? ['cocina', 'pedidos', 'dashboard']
      : role === 'CAJERO'
        ? ['caja', 'mesas', 'pedidos', 'dashboard']
        : role === 'MOZO'
          ? ['mesas', 'pedidos', 'caja', 'dashboard']
          : ['dashboard', 'mesas', 'pedidos', 'caja'];

  for (const tab of order) {
    if (canSeeTab(user, tab)) return TAB_HREF[tab];
  }

  return TAB_HREF.dashboard;
}
