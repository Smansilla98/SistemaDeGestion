<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TutorialController extends Controller
{
    /**
     * Directorio donde se almacenan los PDFs (relativo a public/)
     */
    private const TUTORIALS_DIR = 'tutoriales';

    /**
     * Listar y mostrar la sección de tutoriales (PDFs)
     */
    public function index()
    {
        $dir = public_path(self::TUTORIALS_DIR);

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $files = File::files($dir);
        $pdfs = [];

        foreach ($files as $file) {
            if (strtolower($file->getExtension()) === 'pdf') {
                $pdfs[] = [
                    'name' => $file->getFilename(),
                    'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'url' => asset(self::TUTORIALS_DIR . '/' . $file->getFilename()),
                    'size' => $file->getSize(),
                ];
            }
        }

        usort($pdfs, fn ($a, $b) => strcasecmp($a['title'], $b['title']));

        return view('tutorials.index', compact('pdfs'));
    }

    /**
     * Subir un nuevo tutorial (PDF). Solo ADMIN.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480', // 20 MB
        ], [
            'file.required' => 'Debes seleccionar un archivo PDF.',
            'file.mimes' => 'El archivo debe ser un PDF.',
            'file.max' => 'El archivo no debe superar 20 MB.',
        ]);

        $dir = public_path(self::TUTORIALS_DIR);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $file = $request->file('file');
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
        $path = $dir . '/' . $name;

        // Si ya existe, añadir sufijo numérico
        $base = pathinfo($name, PATHINFO_FILENAME);
        $n = 0;
        while (File::exists($path)) {
            $n++;
            $name = $base . '-' . $n . '.pdf';
            $path = $dir . '/' . $name;
        }

        $file->move($dir, $name);

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

        $path = public_path(self::TUTORIALS_DIR . '/' . $filename);
        if (!File::exists($path)) {
            return redirect()->route('tutorials.index')->with('error', 'El archivo no existe.');
        }

        File::delete($path);

        return redirect()->route('tutorials.index')->with('success', 'Tutorial eliminado correctamente.');
    }
}
