<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Política de Privacidad — {{ $appName }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #131a18; line-height: 1.45; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .brand { color: #1d9e75; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .meta { color: #6b7f7a; font-size: 10px; margin-bottom: 12px; }
        .callout { background: #edf9f4; border-left: 3px solid #1d9e75; padding: 8px 10px; margin: 10px 0 14px; }
        ul { margin: 4px 0 8px 16px; padding: 0; }
        li { margin: 3px 0; }
        p { margin: 0 0 8px; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #d1ddd9; color: #6b7f7a; font-size: 9px; }
    </style>
</head>
<body>
    <div class="brand">{{ $appName }} · {{ $packageName }}</div>
    <h1>Política de Privacidad</h1>
    <div class="meta">Vigente desde el {{ $effectiveDate }} · Última actualización: {{ $lastUpdated }}</div>

    <div class="callout">
        <strong>Compromiso con la privacidad.</strong>
        {{ $appName }} es una aplicación de gestión interna para personal autorizado del restaurante.
        No comercializamos datos personales, no incluimos publicidad de terceros y no utilizamos
        los datos de los usuarios para perfiles publicitarios. El tratamiento se limita a la
        operación segura del local (pedidos, mesas, caja, cocina y administración).
    </div>

    <h2>1. Responsable del tratamiento</h2>
    <p>
        Responsable: <strong>{{ $developerName }}</strong>. Contacto:
        <strong>{{ $contactEmail }}</strong>. URL: {{ $webUrl }} · PDF: {{ $pdfUrl }}
    </p>

    <h2>2. Ámbito de aplicación</h2>
    <p>
        Aplica a la app móvil {{ $appName }} ({{ $packageName }}), la web asociada y la API operativa.
        Destinada a personal autorizado del restaurante, no al público consumidor general.
    </p>

    <h2>3. Datos que tratamos</h2>
    <ul>
        <li>Cuenta: usuario, nombre, rol y credenciales (hash).</li>
        <li>Datos operativos: mesas, pedidos, productos, caja, stock, reportes y configuración.</li>
        <li>Agenda de clientes del local (si el personal la carga): nombre, teléfono, email o notas.</li>
        <li>Token de notificaciones push y plataforma del dispositivo (avisos operativos).</li>
        <li>Registros técnicos mínimos de seguridad y diagnóstico.</li>
    </ul>
    <p>
        No recopilamos ubicación precisa con fines de seguimiento, contactos del teléfono,
        micrófono, cámara ni identificadores publicitarios. No vendemos datos personales.
    </p>

    <h2>4. Finalidades</h2>
    <ul>
        <li>Autenticación y permisos por rol.</li>
        <li>Operación del restaurante (mesas, pedidos, cocina, caja, administración).</li>
        <li>Notificaciones operativas al personal.</li>
        <li>Seguridad, integridad y continuidad del servicio.</li>
        <li>Cumplimiento de obligaciones legales aplicables.</li>
    </ul>

    <h2>5. Base de legitimación</h2>
    <p>
        Relación laboral/comercial con el personal autorizado, ejecución del servicio de gestión
        y, cuando corresponda, consentimiento para notificaciones en el dispositivo.
    </p>

    <h2>6. Conservación</h2>
    <p>
        Mientras la cuenta esté activa y sea necesaria para la operación, o según plazos legales.
        Los tokens de dispositivo pueden eliminarse al cerrar sesión o dar de baja el equipo.
    </p>

    <h2>7. Destinatarios</h2>
    <p>
        Infraestructura de hosting y, para push, proveedores técnicos (Expo / FCM / APNs)
        solo como encargados del envío. Sin cesión a anunciantes ni fines comerciales.
    </p>

    <h2>8. Transferencias internacionales</h2>
    <p>
        Si un proveedor opera fuera del país del responsable, se aplican las salvaguardas
        contractuales y técnicas habituales de esos servicios.
    </p>

    <h2>9. Seguridad</h2>
    <ul>
        <li>Autenticación y control de permisos por rol.</li>
        <li>HTTPS en producción.</li>
        <li>Contraseñas con hash.</li>
        <li>Mínimo privilegio según función del personal.</li>
    </ul>

    <h2>10. Derechos</h2>
    <p>
        Acceso, rectificación, actualización o baja de cuenta y eliminación de tokens:
        contactar a {{ $contactEmail }}.
    </p>

    <h2>11. Menores</h2>
    <p>No dirigida a menores de 13 años. Acceso limitado a personal autorizado.</p>

    <h2>12. Cambios</h2>
    <p>La versión vigente estará en {{ $webUrl }} y {{ $pdfUrl }}.</p>

    <h2>13. Contacto</h2>
    <p>{{ $contactEmail }} · {{ $developerName }} · {{ $appName }}</p>

    <div class="footer">
        Documento para cumplimiento de políticas de Google Play / App Store. Idioma: español. {{ $lastUpdated }}.
    </div>
</body>
</html>
