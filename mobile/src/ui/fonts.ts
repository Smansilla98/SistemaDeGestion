import {
  useFonts,
  Outfit_400Regular,
  Outfit_500Medium,
  Outfit_600SemiBold,
  Outfit_700Bold,
} from '@expo-google-fonts/outfit';
import {
  DMMono_400Regular,
  DMMono_500Medium,
} from '@expo-google-fonts/dm-mono';

export const fontAssets = {
  Outfit_400Regular,
  Outfit_500Medium,
  Outfit_600SemiBold,
  Outfit_700Bold,
  DMMono_400Regular,
  DMMono_500Medium,
};

/** Carga Outfit + DM Mono (paridad web). */
export function useAppFonts(): boolean {
  const [loaded] = useFonts(fontAssets);
  return loaded;
}

/** @deprecated usar useAppFonts */
export function useOutfitFonts(): boolean {
  return useAppFonts();
}
