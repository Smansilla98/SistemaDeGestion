import type { ComponentProps } from 'react';
import { Ionicons } from '@expo/vector-icons';

type Ion = ComponentProps<typeof Ionicons>['name'];

/**
 * Mapa Bootstrap Icons (web) → Ionicons (mobile).
 * Claves: sin prefijo `bi-` o con él.
 */
const BI_TO_ION: Record<string, Ion> = {
  'grid-3x3-gap': 'grid',
  'grid-3x3': 'apps',
  grid: 'grid',
  table: 'grid-outline',
  receipt: 'receipt-outline',
  'egg-fried': 'restaurant-outline',
  'plus-square': 'add-outline',
  'cash-coin': 'cash-outline',
  percent: 'pricetag-outline',
  building: 'business-outline',
  'box-seam': 'cube-outline',
  'card-list': 'list-outline',
  'house-door': 'home-outline',
  'graph-up': 'stats-chart-outline',
  people: 'people-outline',
  person: 'person-outline',
  'person-plus': 'person-add-outline',
  bell: 'notifications-outline',
  'box-arrow-right': 'log-out-outline',
  'wifi-off': 'cloud-offline-outline',
  gear: 'settings-outline',
  calendar: 'calendar-outline',
  wallet: 'wallet-outline',
  key: 'key-outline',
  map: 'map-outline',
  flame: 'flame-outline',
  'add-circle': 'add-circle-outline',
  create: 'create-outline',
  'lock-closed': 'lock-closed-outline',
  folder: 'folder-outline',
  repeat: 'repeat-outline',
  'bar-chart': 'bar-chart-outline',
  'checkmark-circle': 'checkmark-circle-outline',
  'exclamation-circle': 'alert-circle-outline',
  search: 'search-outline',
  funnel: 'filter-outline',
  pencil: 'pencil-outline',
  trash: 'trash-outline',
};

export function biToIon(bi: string): Ion {
  const key = bi.replace(/^bi-/, '').replace(/^bi\s+bi-/, '');
  return BI_TO_ION[key] ?? 'ellipse-outline';
}

export function AppIcon({
  bi,
  name,
  size = 18,
  color,
}: {
  /** Nombre Bootstrap Icon, ej. `receipt` o `bi-receipt` */
  bi?: string;
  name?: Ion;
  size?: number;
  color?: string;
}) {
  const ion = name ?? (bi ? biToIon(bi) : 'ellipse-outline');
  return <Ionicons name={ion} size={size} color={color} />;
}

/** Iconos de tabs alineados al bottom-nav / sidebar web. */
export const tabIcons = {
  dashboard: { active: 'home' as Ion, inactive: 'home-outline' as Ion, bi: 'house-door' },
  mesas: { active: 'grid' as Ion, inactive: 'grid-outline' as Ion, bi: 'table' },
  pedido: { active: 'add-circle' as Ion, inactive: 'add-circle-outline' as Ion, bi: 'plus-square' },
  pedidos: { active: 'receipt' as Ion, inactive: 'receipt-outline' as Ion, bi: 'receipt' },
  cocina: { active: 'restaurant' as Ion, inactive: 'restaurant-outline' as Ion, bi: 'egg-fried' },
  caja: { active: 'cash' as Ion, inactive: 'cash-outline' as Ion, bi: 'cash-coin' },
  stock: { active: 'cube' as Ion, inactive: 'cube-outline' as Ion, bi: 'box-seam' },
};
