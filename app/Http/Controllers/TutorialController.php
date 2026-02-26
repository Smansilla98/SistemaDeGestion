<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TutorialController extends Controller
{
    /**
     * Subcarpeta en el disco público para los PDFs
     */
    private const TUTORIALS_DIR = 'tutoriales';

    /**
     * Archivo JSON con títulos personalizados (nombre_archivo => título)
     */
    private const META_FILE = 'tutoriales/_meta.json';

    /**
     * Tamaño máximo en KB (10 MB). Ajustar si el servidor tiene límites menores.
     */
    private const MAX_FILE_KB = 10240;

    private function getTitlesMeta(): array
    {
        $disk = Storage::disk('public');
        if (!$disk->exists(self::META_FILE)) {
            return [];
        }
        $json = $disk->get(self::META_FILE);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function saveTitlesMeta(array $meta): void
    {
        Storage::disk('public')->put(self::META_FILE, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * Listar y mostrar la sección de tutoriales (PDFs)
     */
    public function index()
    {
        $disk = Storage::disk('public');
        $dir = self::TUTORIALS_DIR;

        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $titles = $this->getTitlesMeta();
        $pdfs = [];
        $files = $disk->files($dir);

        foreach ($files as $path) {
            $name = basename($path);
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
                continue;
            }
            $defaultTitle = pathinfo($name, PATHINFO_FILENAME);
            $pdfs[] = [
                'name' => $name,
                'title' => $titles[$name] ?? $defaultTitle,
                'url' => asset('storage/' . $path),
                'size' => $disk->size($path),
            ];
        }

        usort($pdfs, fn ($a, $b) => strcasecmp($a['title'], $b['title']));

        return view('tutorials.index', compact('pdfs'));
    }

    /**
     * Subir un nuevo tutorial (PDF). Solo ADMIN.
     */
    public function store(Request $request)
    {
        // Comprobar que el archivo llegó (evita "The file failed to upload" genérico)
        if (!$request->hasFile('file')) {
            $error = $request->get('file') ? 'El archivo no se pudo subir. Puede superar el límite del servidor (p. ej. upload_max_filesize en PHP).' : 'Debes seleccionar un archivo PDF.';
            throw ValidationException::withMessages(['file' => $error]);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:' . self::MAX_FILE_KB,
            'title' => 'nullable|string|max:255',
        ], [
            'file.required' => 'Debes seleccionar un archivo PDF.',
            'file.file' => 'El archivo no se subió correctamente. Comprueba el tamaño (máx. ' . (self::MAX_FILE_KB / 1024) . ' MB).',
            'file.mimes' => 'El archivo debe ser un PDF.',
            'file.max' => 'El archivo no debe superar ' . (self::MAX_FILE_KB / 1024) . ' MB.',
            'title.max' => 'El nombre no debe superar 255 caracteres.',
        ]);

        $file = $request->file('file');
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = $baseName . '.pdf';
        $dir = self::TUTORIALS_DIR;

        $disk = Storage::disk('public');
        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $n = 0;
        while ($disk->exists($dir . '/' . $name)) {
            $n++;
            $name = $baseName . '-' . $n . '.pdf';
        }

        $file->storeAs($dir, $name, 'public');

        $customTitle = $request->input('title');
        if ($customTitle !== null && trim($customTitle) !== '') {
            $meta = $this->getTitlesMeta();
            $meta[$name] = trim($customTitle);
            $this->saveTitlesMeta($meta);
        }

        return redirect()->route('tutorials.index')->with('success', 'Tutorial agregado correctamente.');
    }

    /**
     * Eliminar un tutorial por nombre de archivo. Solo ADMIN.
     */
    public function destroy(string $filename)
    {
        $filename = basename($filename);
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            return redirect()->route('tutorials.index')->with('error', 'Archivo no válido.');
        }

        $path = self::TUTORIALS_DIR . '/' . $filename;
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return redirect()->route('tutorials.index')->with('error', 'El archivo no existe.');
        }

        $disk->delete($path);

        $meta = $this->getTitlesMeta();
        if (isset($meta[$filename])) {
            unset($meta[$filename]);
            $this->saveTitlesMeta($meta);
        }

        return redirect()->route('tutorials.index')->with('success', 'Tutorial eliminado correctamente.');
    }
}
