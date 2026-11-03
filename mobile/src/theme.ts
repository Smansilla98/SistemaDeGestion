/** Tokens de Al Toque — paridad con resources/css/conurbania.css */
export const colors = {
  teal950: '#10262c',
  teal900: '#16343b',
  teal800: '#1e4650',
  teal700: '#2a5c68',
  teal600: '#3a7380',
  teal500: '#4e8d99',
  teal400: '#6aadb6',
  teal300: '#9dccd2',
  teal200: '#c7e4e7',
  teal100: '#e5f3f4',
  teal50: '#f4fafa',
  gray900: '#1a2326',
  gray800: '#243033',
  gray700: '#3a4a4e',
  gray600: '#5c6e73',
  gray500: '#7d9094',
  gray400: '#a3b4b7',
  gray300: '#c5d1d3',
  gray200: '#dde5e6',
  gray100: '#eef3f3',
  gray50: '#f6f8f8',
  white: '#FFFFFF',
  danger: '#ef4444',
  dangerBg: '#fee2e2',
  dangerFg: '#991b1b',
  amber: '#f59e0b',
  amberBg: '#fef3c7',
  amberFg: '#92400e',
  green: '#22c55e',
  greenBg: '#dcfce7',
  greenFg: '#166534',
  blue: '#3b82f6',
  blueBg: '#dbeafe',
  /** Override web .btn-primary en conurbania.css */
  btnPrimary: '#4e8d99',
  btnPrimaryHover: '#3d7580',
  /** Stops mosaic (legacy array) */
  mosaic: ['#2a5c68', '#6aadb6', '#16343b'] as const,
};

/**
 * Degradés exactos de conurbania.css
 * --mosaic-bg: linear-gradient(135deg,#2a5c68 0%,#6aadb6 48%,#16343b 100%)
 * .page-header: linear-gradient(135deg,t900 0%,t700 100%)
 */
export const gradients = {
  mosaic: ['#2a5c68', '#6aadb6', '#16343b'] as const,
  pageHeader: ['#16343b', '#2a5c68'] as const,
  /** Locations 0 / 0.5 / 1 para mosaic */
  mosaicLocations: [0, 0.5, 1] as const,
};

export const radius = {
  sm: 6,
  md: 8,
  lg: 12,
  xl: 16,
};

export const space = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
};

/** Outfit — misma familia que la web */
export const font = {
  regular: 'Outfit_400Regular',
  medium: 'Outfit_500Medium',
  semibold: 'Outfit_600SemiBold',
  bold: 'Outfit_700Bold',
  /** DM Mono — montos / códigos (.td-mono web) */
  mono: 'DMMono_400Regular',
  monoMedium: 'DMMono_500Medium',
} as const;

export function statusTone(status: string): { fg: string; bg: string } {
  const s = status.toUpperCase();
  if (['LIBRE', 'LISTO', 'CERRADO', 'ENTREGADO', 'ABIERTA', 'OK', 'CAJA ABIERTA'].includes(s)) {
    return { fg: colors.greenFg, bg: colors.greenBg };
  }
  if (['OCUPADA', 'ENVIADO', 'ABIERTO', 'EN_PREPARACION', 'BAJO', 'STOCK BAJO', 'RESERVADA'].includes(s) || s.includes('STOCK BAJO')) {
    return { fg: colors.amberFg, bg: colors.amberBg };
  }
  if (['ANULADO', 'CANCELADO', 'SIN CAJA'].includes(s)) {
    return { fg: colors.dangerFg, bg: colors.dangerBg };
  }
  if (['ENTRADA'].includes(s)) return { fg: colors.greenFg, bg: colors.greenBg };
  if (['SALIDA'].includes(s)) return { fg: colors.dangerFg, bg: colors.dangerBg };
  if (['AJUSTE'].includes(s)) return { fg: '#1e40af', bg: colors.blueBg };
  return { fg: colors.gray700, bg: colors.gray100 };
}

/**
 * Prototipo UX fintech (MercadoPago/Prex-inspired).
 * Convive con tokens web-parity de arriba — no los reemplaza.
 * Marca = teal500; neutros + un acento de estado (punto, no relleno).
 */
export const fx = {
  brand: colors.teal500,
  brandSoft: colors.teal50,
  brandInk: colors.teal800,
  canvas: '#F6F8F8',
  surface: colors.white,
  ink: colors.gray900,
  inkMuted: colors.gray500,
  inkFaint: colors.gray400,
  hairline: 'rgba(28, 20, 18, 0.06)',
  success: colors.green,
  warning: colors.amber,
  danger: colors.danger,
  space: {
    xs: 4,
    sm: 8,
    md: 16,
    lg: 24,
    xl: 32,
  },
  radius: {
    sm: 10,
    md: 16,
    lg: 20,
    pill: 999,
  },
  type: {
    hero: 36,
    title: 22,
    body: 15,
    caption: 12,
    micro: 11,
  },
  shadow: {
    color: '#10262c',
    opacity: 0.06,
    radius: 12,
    offset: { width: 0, height: 4 } as const,
    elevation: 2,
  },
  motion: {
    /** ms — micro-interacciones cortas */
    fast: 160,
    in: 220,
  },
} as const;

/** Estado como punto + label (sin badges de color saturados). */
export function statusDot(status: string): { color: string; label: string } {
  const s = status.toUpperCase();
  const label = s.replace(/_/g, ' ');
  if (['LIBRE', 'LISTO', 'ENTREGADO', 'CERRADO', 'OK', 'ABIERTA'].includes(s)) {
    return { color: fx.success, label };
  }
  if (['OCUPADA', 'ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'RESERVADA', 'BAJO'].includes(s)) {
    return { color: fx.warning, label };
  }
  if (['ANULADO', 'CANCELADO'].includes(s)) {
    return { color: fx.danger, label };
  }
  return { color: fx.inkFaint, label };
}
