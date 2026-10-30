/**
 * Configuración que inyecta la vista Blade (resources/views/disenos/editor.blade.php).
 * Integración de OpenDesign en Patuka: el backend es Laravel (DesignEditorController).
 */
export interface OpenDesignConfig {
  basePath: string; // ruta del editor en el panel, ej. "/disenos"
  apiBase: string; // ej. "/disenos/api"
  panelUrl: string; // volver al panel
  logoUrl?: string; // logo de la organización para el encabezado
  brandName: string; // nombre de la organización (textos y nombres de archivo)
  csrfToken: string;
  puedeGestionarKit: boolean;
}

export const CONFIG: OpenDesignConfig = (window as unknown as { __OPENDESIGN__: OpenDesignConfig }).__OPENDESIGN__;
