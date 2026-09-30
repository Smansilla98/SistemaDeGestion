import type { Href } from 'expo-router';
import type { ApiUser } from '../api/types';

export function hasPermission(user: ApiUser | null | undefined, permission: string): boolean {
  if (!user) return false;
  const perms = user.permissions ?? [];
  if (perms.includes('*')) return true;
  return perms.includes(permission);
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
    case 'pedido':
      return hasPermission(user, 'tables.read');
    case 'pedidos':
      return hasPermission(user, 'orders.read');
    case 'cocina':
      return hasPermission(user, 'kitchen.read');
    case 'caja':
      return hasPermission(user, 'cash.read');
    case 'stock':
      return hasPermission(user, 'stock.read');
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

/** Ruta inicial post-login (paridad operativa con web). */
export function homeHrefForRole(role?: string): Href {
  switch (role) {
    case 'COCINA':
      return '/(tabs)/cocina';
    case 'CAJERO':
      return '/(tabs)/caja';
    case 'MOZO':
      return '/(tabs)/mesas';
    case 'ADMIN':
    case 'SUPERADMIN':
    case 'GERENTE':
      return '/(tabs)/dashboard' as Href;
    default:
      return '/(tabs)/mesas' as Href;
  }
}
