<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index,follow">
    <title>Política de Privacidad — {{ $appName }}</title>
    <meta name="description" content="Política de privacidad de la aplicación {{ $appName }} ({{ $packageName }}). Uso responsable de datos del personal autorizado del restaurante.">
    <style>
        :root {
            --ink: #131a18;
            --muted: #4a5e59;
            --faint: #6b7f7a;
            --brand: #1d9e75;
            --canvas: #f2f5f4;
            --surface: #ffffff;
            --line: rgba(19, 26, 24, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background: var(--canvas);
            line-height: 1.65;
            font-size: 16px;
        }
        .wrap {
            max-width: 820px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }
        .card {
            background: var(--surface);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 8px 28px rgba(19, 26, 24, 0.06);
        }
        .brand {
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--brand);
            font-weight: 700;
            margin: 0 0 8px;
        }
        h1 {
            font-size: 1.85rem;
            line-height: 1.25;
            margin: 0 0 12px;
        }
        .meta {
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 13px;
            color: var(--faint);
            margin-bottom: 24px;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 28px;
        }
        .btn {
            font-family: system-ui, -apple-system, sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-primary {
            background: var(--brand);
            color: #fff;
        }
        .btn-ghost {
            background: var(--canvas);
            color: var(--ink);
        }
        .callout {
            border-left: 4px solid var(--brand);
            background: #edf9f4;
            padding: 14px 16px;
            margin: 0 0 28px;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 14px;
            color: #0f3d32;
        }
        h2 {
            font-size: 1.15rem;
            margin: 28px 0 10px;
            padding-top: 8px;
            border-top: 1px solid var(--line);
        }
        h2:first-of-type { border-top: 0; padding-top: 0; }
        p, li { color: var(--muted); }
        ul { padding-left: 1.2rem; }
        li { margin: 6px 0; }
        strong { color: var(--ink); }
        .footer {
            margin-top: 36px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 13px;
            color: var(--faint);
        }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .card { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <article class="card">
            <p class="brand">{{ $appName }} · {{ $packageName }}</p>
            <h1>Política de Privacidad</h1>
            <p class="meta">
                Vigente desde el {{ $effectiveDate }} · Última actualización: {{ $lastUpdated }}
            </p>

            <div class="actions">
                <a class="btn btn-primary" href="{{ route('privacy.pdf') }}">Descargar PDF</a>
                <a class="btn btn-ghost" href="javascript:window.print()">Imprimir / Guardar como PDF</a>
            </div>

            <div class="callout">
                <strong>Compromiso con la privacidad.</strong>
                {{ $appName }} es una aplicación de gestión interna para personal autorizado del restaurante.
                No comercializamos datos personales, no incluimos publicidad de terceros y no utilizamos
                los datos de los usuarios para perfiles publicitarios. El tratamiento se limita a la
                operación segura del local (pedidos, mesas, caja, cocina y administración).
            </div>

            <h2>1. Responsable del tratamiento</h2>
            <p>
                El responsable de esta aplicación es <strong>{{ $developerName }}</strong>
                (desarrollador de {{ $appName }}).
                Contacto de privacidad: <strong>{{ $contactEmail }}</strong>.
            </p>
            <p>
                Documento público: <a href="{{ $webUrl }}">{{ $webUrl }}</a>
                · PDF: <a href="{{ $pdfUrl }}">{{ $pdfUrl }}</a>
            </p>

            <h2>2. Ámbito de aplicación</h2>
            <p>
                Esta política aplica a la aplicación móvil <strong>{{ $appName }}</strong>
                (Android package <strong>{{ $packageName }}</strong>), a la versión web asociada
                y a la API de soporte operativa. Está dirigida a empleados y colaboradores
                autorizados del restaurante (mozos, cocina, caja, gerencia y administración),
                no a consumidores finales del público general.
            </p>

            <h2>3. Datos que tratamos</h2>
            <p>Solo tratamos datos necesarios para el funcionamiento del servicio:</p>
            <ul>
                <li><strong>Cuenta de acceso:</strong> nombre de usuario, nombre, rol y credenciales de autenticación (contraseña almacenada de forma segura / hasheada).</li>
                <li><strong>Datos operativos del restaurante:</strong> mesas, pedidos, productos, caja, stock, reportes y configuración interna.</li>
                <li><strong>Clientes del local (agenda opcional):</strong> nombre, teléfono, email o notas que el personal cargue para reservas o atención, cuando corresponda.</li>
                <li><strong>Dispositivo (notificaciones push):</strong> token de notificaciones y plataforma (Android/iOS), únicamente para avisos operativos (por ejemplo, pedidos listos).</li>
                <li><strong>Registros técnicos mínimos:</strong> eventos de seguridad y diagnóstico necesarios para mantener el servicio (por ejemplo, fallos de autenticación).</li>
            </ul>
            <p>
                <strong>No recopilamos</strong> datos de ubicación precisa con fines de seguimiento,
                contactos del teléfono, micrófono, cámara, ni identificadores publicitarios.
                No vendemos datos personales.
            </p>

            <h2>4. Finalidades del tratamiento</h2>
            <ul>
                <li>Autenticar usuarios autorizados y aplicar permisos por rol.</li>
                <li>Operar el flujo del restaurante (mesas, pedidos, cocina, caja y administración).</li>
                <li>Enviar notificaciones operativas al personal cuando corresponda.</li>
                <li>Garantizar seguridad, integridad y continuidad del servicio.</li>
                <li>Cumplir obligaciones legales aplicables al responsable.</li>
            </ul>

            <h2>5. Base de legitimación</h2>
            <p>
                El tratamiento se realiza sobre la base de la relación laboral/comercial con el
                personal autorizado, la ejecución del servicio de gestión contratado por el
                restaurante y, cuando corresponda, el consentimiento para notificaciones en el dispositivo.
            </p>

            <h2>6. Conservación</h2>
            <p>
                Conservamos los datos mientras la cuenta esté activa y sea necesaria para la
                operación del restaurante, o durante los plazos legales de documentación comercial
                y seguridad. Los tokens de dispositivo pueden eliminarse al cerrar sesión o
                desactivar notificaciones / dar de baja el equipo.
            </p>

            <h2>7. Destinatarios y encargados</h2>
            <p>
                Los datos se procesan en la infraestructura del servicio (hosting de aplicación y base de datos).
                Para entrega de notificaciones push pueden intervenir proveedores técnicos
                (por ejemplo, servicios de Expo / Firebase Cloud Messaging / Apple Push Notification),
                exclusivamente como encargados del envío del aviso.
            </p>
            <p>
                No compartimos datos con anunciantes ni los cedemos a terceros con fines comerciales.
            </p>

            <h2>8. Transferencias internacionales</h2>
            <p>
                Si algún proveedor de infraestructura o de notificaciones opera fuera del país
                del responsable, se aplicarán las salvaguardas contractuales y técnicas habituales
                de esos servicios para proteger la información.
            </p>

            <h2>9. Seguridad</h2>
            <ul>
                <li>Acceso autenticado (tokens / sesión) y control de permisos por rol.</li>
                <li>Comunicación cifrada mediante HTTPS en producción.</li>
                <li>Contraseñas almacenadas con hash; no se exponen en texto plano.</li>
                <li>Principio de mínimo privilegio para el personal según su función.</li>
            </ul>

            <h2>10. Derechos de los usuarios</h2>
            <p>
                El personal autorizado puede solicitar acceso, rectificación, actualización o
                baja de su cuenta, y la eliminación de tokens de dispositivo, contactando a
                <strong>{{ $contactEmail }}</strong>. Atenderemos los pedidos en un plazo razonable
                y conforme a la normativa aplicable.
            </p>

            <h2>11. Menores de edad</h2>
            <p>
                La aplicación no está dirigida a menores de 13 años ni está pensada para uso
                por el público infantil. El acceso se limita a personal autorizado del restaurante.
            </p>

            <h2>12. Cambios a esta política</h2>
            <p>
                Podemos actualizar este documento para reflejar mejoras del servicio o requisitos legales.
                La versión vigente siempre estará disponible en
                <a href="{{ $webUrl }}">{{ $webUrl }}</a>
                y en PDF en
                <a href="{{ $pdfUrl }}">{{ $pdfUrl }}</a>.
            </p>

            <h2>13. Contacto</h2>
            <p>
                Consultas sobre privacidad: <strong>{{ $contactEmail }}</strong><br>
                Responsable: <strong>{{ $developerName }}</strong> · Aplicación <strong>{{ $appName }}</strong>
            </p>

            <div class="footer">
                Documento elaborado para cumplimiento de políticas de Google Play / App Store.
                Idioma: español. Versión pública de {{ $lastUpdated }}.
            </div>
        </article>
    </div>
</body>
</html>
