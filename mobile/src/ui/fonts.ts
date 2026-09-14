import { useFonts, Outfit_400Regular, Outfit_500Medium, Outfit_600SemiBold, Outfit_700Bold } from '@expo-google-fonts/outfit';

export const outfitAssets = {
  Outfit_400Regular,
  Outfit_500Medium,
  Outfit_600SemiBold,
  Outfit_700Bold,
};

export function useOutfitFonts(): boolean {
  const [loaded] = useFonts(outfitAssets);
  return loaded;
}
