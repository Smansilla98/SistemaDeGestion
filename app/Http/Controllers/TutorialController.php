<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

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

        // Ordenar por nombre
        usort($pdfs, fn ($a, $b) => strcasecmp($a['title'], $b['title']));

        return view('tutorials.index', compact('pdfs'));
    }
}
