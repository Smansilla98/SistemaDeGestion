/** Tokens Conurbania — paridad con resources/css/conurbania.css */
export const colors = {
  teal950: '#031a16',
  teal900: '#082822',
  teal800: '#0f3d32',
  teal700: '#155240',
  teal600: '#1d7a5c',
  teal500: '#1d9e75',
  teal400: '#2ec28f',
  teal300: '#5dcaa5',
  teal200: '#9fe1cb',
  teal100: '#d4f2e8',
  teal50: '#edf9f4',
  gray900: '#131a18',
  gray800: '#1e2a27',
  gray700: '#2d3d39',
  gray600: '#4a5e59',
  gray500: '#6b7f7a',
  gray400: '#8fa39e',
  gray300: '#b3c4bf',
  gray200: '#d1ddd9',
  gray100: '#e8eeec',
  gray50: '#f4f7f6',
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
  btnPrimary: '#1f9fb5',
  btnPrimaryHover: '#1a8ca0',
  /** Stops mosaic (legacy array) */
  mosaic: ['#24696b', '#5f7477', '#262c3b'] as const,
};

/**
 * Degradés exactos de conurbania.css
 * --mosaic-bg: linear-gradient(135deg,#24696b 0%,#5f7477 50%,#262c3b 100%)
 * .page-header: linear-gradient(135deg,t900 0%,t700 100%)
 */
export const gradients = {
  mosaic: ['#24696b', '#5f7477', '#262c3b'] as const,
  pageHeader: ['#082822', '#155240'] as const,
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
