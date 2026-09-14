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
  amber: '#f59e0b',
  amberBg: '#fef3c7',
  green: '#22c55e',
  greenBg: '#dcfce7',
  blue: '#3b82f6',
  blueBg: '#dbeafe',
  mosaic: ['#24696b', '#5f7477', '#262c3b'] as const,
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
} as const;

export function statusTone(status: string): { fg: string; bg: string } {
  const s = status.toUpperCase();
  if (['LIBRE', 'LISTO', 'CERRADO', 'ENTREGADO', 'ABIERTA', 'OK', 'CAJA ABIERTA'].includes(s)) {
    return { fg: '#166534', bg: colors.greenBg };
  }
  if (['OCUPADA', 'ENVIADO', 'ABIERTO', 'EN_PREPARACION', 'BAJO', 'STOCK BAJO'].includes(s) || s.includes('STOCK BAJO')) {
    return { fg: '#92400e', bg: colors.amberBg };
  }
  if (['ANULADO', 'CANCELADO', 'SIN CAJA'].includes(s)) {
    return { fg: '#991b1b', bg: colors.dangerBg };
  }
  if (['ENTRADA'].includes(s)) return { fg: '#166534', bg: colors.greenBg };
  if (['SALIDA'].includes(s)) return { fg: '#991b1b', bg: colors.dangerBg };
  if (['AJUSTE'].includes(s)) return { fg: '#1e40af', bg: colors.blueBg };
  return { fg: colors.gray700, bg: colors.gray100 };
}
