/** Tokens de Al Toque — paridad con resources/css/conurbania.css */
export const colors = {
  teal950: '#1a0e0a',
  teal900: '#24160f',
  teal800: '#4a2818',
  teal700: '#6b3a1e',
  teal600: '#8f4e22',
  teal500: '#d06a1f',
  teal400: '#e4843a',
  teal300: '#f0b27a',
  teal200: '#f6d3b0',
  teal100: '#fbe8d4',
  teal50: '#fdf6ee',
  gray900: '#1c1412',
  gray800: '#2c221e',
  gray700: '#43362f',
  gray600: '#6b574c',
  gray500: '#8a7568',
  gray400: '#b09a8c',
  gray300: '#d4c4b8',
  gray200: '#e7ddd4',
  gray100: '#f1ebe6',
  gray50: '#f8f4f0',
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
  btnPrimary: '#d06a1f',
  btnPrimaryHover: '#b55816',
  /** Stops mosaic (legacy array) */
  mosaic: ['#6b3a1e', '#d06a1f', '#1c1412'] as const,
};

/**
 * Degradés exactos de conurbania.css
 * --mosaic-bg: linear-gradient(135deg,#6b3a1e 0%,#d06a1f 48%,#1c1412 100%)
 * .page-header: linear-gradient(135deg,t900 0%,t700 100%)
 */
export const gradients = {
  mosaic: ['#6b3a1e', '#d06a1f', '#1c1412'] as const,
  pageHeader: ['#24160f', '#6b3a1e'] as const,
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
  canvas: '#F8F4F0',
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
    color: '#1a0e0a',
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
