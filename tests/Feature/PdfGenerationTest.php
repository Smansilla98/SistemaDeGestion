<?php

/*
 * Humo de dompdf: los comprobantes, comandas y reportes salen por el mismo motor.
 * Cubre la actualización a barryvdh/laravel-dompdf 3 (dompdf 3.1).
 */
it('genera el PDF de la política de privacidad', function () {
    $res = $this->get('/privacidad.pdf');

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
    expect(substr((string) $res->getContent(), 0, 5))->toBe('%PDF-');
});
