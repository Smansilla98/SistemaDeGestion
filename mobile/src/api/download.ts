import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { API_URL } from '../config';
import { getAccessToken } from '../auth/storage';

/** Descarga un archivo autenticado (xlsx/pdf) y abre el share sheet. */
export async function downloadAndShare(
  path: string,
  filename: string,
): Promise<void> {
  const token = await getAccessToken();
  const url = `${API_URL}${path.startsWith('/') ? path : `/${path}`}`;
  const base = FileSystem.cacheDirectory ?? FileSystem.documentDirectory;
  if (!base) {
    throw new Error('No hay directorio de archivos disponible');
  }
  const target = `${base}${filename}`;

  const result = await FileSystem.downloadAsync(url, target, {
    headers: {
      Accept: '*/*',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  });

  if (result.status < 200 || result.status >= 300) {
    throw new Error(`No se pudo descargar (${result.status})`);
  }

  const canShare = await Sharing.isAvailableAsync();
  if (!canShare) {
    throw new Error('Compartir no disponible en este dispositivo');
  }

  await Sharing.shareAsync(result.uri, {
    mimeType: filename.endsWith('.pdf')
      ? 'application/pdf'
      : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    dialogTitle: 'Exportar reporte',
  });
}
