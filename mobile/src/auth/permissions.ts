import type { Href } from 'expo-router';
import type { ApiUser } from '../api/types';

export function hasPermission(user: ApiUser | null | undefined, permission: string): boolean {
  if (!user) return false;
  const perms = user.permissions ?? [];
  if (perms.includes('*')) return true;
  return perms.includes(permission);
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

/** Ruta inicial post-login (paridad operativa con web). */
export function homeHrefForRole(role?: string): Href {
  switch (role) {
    case 'COCINA':
      return '/(tabs)/cocina';
    case 'CAJERO':
      return '/(tabs)/caja';
    case 'ADMIN':
    case 'SUPERADMIN':
    case 'GERENTE':
      return '/(tabs)/dashboard' as Href;
    default:
      return '/(tabs)/dashboard' as Href;
  }
}
