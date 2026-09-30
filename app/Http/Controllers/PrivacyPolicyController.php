<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Política de privacidad pública (Play Store / App Store).
 */
class PrivacyPolicyController extends Controller
{
    public function show(): View
    {
        return view('legal.privacy', $this->payload());
    }

    public function pdf(): Response
    {
        $pdf = Pdf::loadView('legal.privacy-pdf', $this->payload())
            ->setPaper('a4');

        return $pdf->download('Conurbania-Politica-de-Privacidad.pdf');
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'appName' => 'Conurbania',
            'packageName' => 'com.conurbania.app',
            'developerName' => 'Santi Mansilla',
            'contactEmail' => 'samansilla.998@gmail.com',
            'effectiveDate' => '16 de septiembre de 2026',
            'lastUpdated' => '16 de septiembre de 2026',
            'webUrl' => rtrim((string) config('app.url'), '/').'/privacidad',
            'pdfUrl' => rtrim((string) config('app.url'), '/').'/privacidad.pdf',
        ];
    }
}
