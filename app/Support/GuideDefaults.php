<?php

namespace App\Support;

use App\Models\GuideSection;
use App\Models\Restaurant;

/**
 * Contenido base de la guía de comunicación. Cada espacio lo edita después a su gusto;
 * cargarlo de nuevo sólo agrega las secciones que falten.
 */
final class GuideDefaults
{
    /** @return list<array{slug: string, title: string, summary: string, audience: string, icon: string, template_id: ?string, body: string}> */
    public static function sections(string $name): array
    {
        return [
            [
                'slug' => 'para-que', 'title' => 'Para qué sirve esta guía', 'audience' => 'TODOS', 'icon' => 'bi-compass', 'template_id' => null,
                'summary' => 'Cómo hablamos de '.$name.' para que todo lo que se publique se reconozca como nuestro.',
                'body' => <<<MD
Todo lo que sale de {$name} —un flyer, una historia, un mensaje de WhatsApp— cuenta quiénes somos. Esta guía junta lo que aprendimos para que cualquiera del espacio (equipo, profes, artistas y emprendedores) pueda publicar sin dudar.

**Tres ideas para tener siempre presentes**

1. **Primero la información.** Qué es, cuándo, dónde, cuánto sale y cómo anotarse. Si falta un dato, la gente pregunta (o no viene).
2. **Mostrar personas.** Las fotos de clases, ensayos y ferias funcionan mejor que cualquier diseño.
3. **Una sola voz.** Usamos el negro y blanco de la marca, Roboto y las plantillas de *Diseño*. Así todo se ve parte de lo mismo.

Si algo no está acá, preguntale al equipo de comunicación por *Mensajes*.
MD,
            ],
            [
                'slug' => 'que-publicar', 'title' => 'Qué publicar: los pilares', 'audience' => 'TODOS', 'icon' => 'bi-grid-3x3-gap', 'template_id' => null,
                'summary' => 'Cinco tipos de contenido que se van alternando para que la cuenta no sea sólo avisos.',
                'body' => <<<'MD'
Alterná entre estos pilares. Una buena semana tiene al menos tres distintos.

| Pilar | Qué es | Ejemplos |
|---|---|---|
| **Agenda** | Lo que viene | Talleres con inscripción abierta, shows, ferias, fechas especiales |
| **Detrás de escena** | Cómo se hace | Armado de un show, una clase por dentro, la cocina de la barra |
| **Comunidad** | Las personas | Alumnos tocando, un profe presentándose, el público en un show |
| **Logros** | Lo que pasó | Muestra de fin de taller, una feria llena, un récord de inscriptos |
| **Feria y productores** | Los emprendimientos | Producto de la semana, el nodo de pedidos, un emprendedor nuevo |

**Evitá:** publicar sólo flyers, repetir la misma pieza muchas veces seguidas, subir fotos de menores sin autorización.
MD,
            ],
            [
                'slug' => 'calendario', 'title' => 'Cuándo publicar: semana tipo', 'audience' => 'EQUIPO', 'icon' => 'bi-calendar-week', 'template_id' => 'ptk-agenda',
                'summary' => 'Un ritmo simple para sostener la cuenta sin agotarse.',
                'body' => <<<'MD'
| Día | Feed | Historias |
|---|---|---|
| **Lunes** | Agenda de la semana (plantilla *Agenda de la semana*) | Recordatorio de talleres con lugar |
| **Miércoles** | Detrás de escena o comunidad | Encuesta o pregunta |
| **Viernes** | Show o actividad del finde | Cuenta regresiva, ubicación |
| **Domingo** | Feria / productores | Productos y precios |

- **Eventos:** anunciar con 2 semanas, recordar 3 días antes, el mismo día y agradecer al día siguiente con fotos.
- **Talleres:** abrir inscripción 3 semanas antes del inicio; con cupo casi lleno, publicar «últimos lugares».
- **Horarios:** de 12 a 14 h y de 19 a 22 h suelen tener más alcance. Mirá las estadísticas de la cuenta y ajustá.

Cargá cada publicación importante como aviso en la **Agenda** para que todo el equipo la vea.
MD,
            ],
            [
                'slug' => 'como-escribir', 'title' => 'Cómo escribir', 'audience' => 'TODOS', 'icon' => 'bi-pencil-square', 'template_id' => null,
                'summary' => 'Tono, estructura de un posteo y la lista de datos que no puede faltar.',
                'body' => <<<'MD'
**Tono:** cercano, en voseo, claro y sin vueltas. Cálido pero no exagerado: mejor «Arranca el taller de cerámica» que «¡¡¡IMPERDIBLE!!!».

**Estructura de un posteo**

1. **Gancho** (primera línea): qué es y por qué te interesa. *«¿Siempre quisiste tocar percusión? Este martes arrancamos.»*
2. **Datos clave** en renglones cortos.
3. **Llamado a la acción:** qué tiene que hacer la persona. *«Inscribite desde el link de la bio.»*
4. **Etiquetas:** profes, artistas o emprendedores que participan.

**Checklist de datos**

- [ ] Qué es (taller, show, feria) y con quién
- [ ] Día y hora (con día de la semana)
- [ ] Lugar (dirección o «en el espacio»)
- [ ] Precio, o «gratis» / «a la gorra»
- [ ] Cómo anotarse o conseguir entrada
- [ ] Si hay cupo, decirlo

**Hashtags:** pocos y locales (barrio, ciudad, disciplina). Más de 5 no suma.
MD,
            ],
            [
                'slug' => 'formatos', 'title' => 'Formatos y medidas', 'audience' => 'TODOS', 'icon' => 'bi-aspect-ratio', 'template_id' => 'ptk-taller',
                'summary' => 'Qué medida usar en cada lugar y qué plantilla de Diseño conviene.',
                'body' => <<<'MD'
| Dónde | Medida | Plantilla en *Diseño* |
|---|---|---|
| Feed de Instagram | 1080 × 1350 (vertical) | Flyer de taller, Agenda de la semana |
| Historias y reels | 1080 × 1920 | Historia de show |
| Post cuadrado / WhatsApp | 1080 × 1080 | Post de feria, Frase |
| Afiche impreso | A4 (1240 × 1754) | Afiche A4 de temporada |
| Web y eventos | 1200 × 628 | Banner web |

- En historias dejá libres unos 250 px arriba y abajo: ahí van el nombre de la cuenta y la caja de respuesta.
- **Logo blanco** sobre fondos oscuros, **logo negro** sobre fondos claros. No lo deformes ni le cambies el color.
- Texto grande y poco: si no se lee en el celular a la distancia de un brazo, sobra.
- Para animaciones o video, empezá en *Diseño* y seguí en Canva desde la pestaña **Canva / IA**.
MD,
            ],
            [
                'slug' => 'fotos-video', 'title' => 'Fotos y video con el celular', 'audience' => 'TODOS', 'icon' => 'bi-camera', 'template_id' => null,
                'summary' => 'Trucos simples para que las fotos de clases y shows queden bien, y los permisos que hacen falta.',
                'body' => <<<'MD'
- **Luz:** de frente o de costado, nunca detrás de la persona. Cerca de una ventana es ideal.
- **Limpiá la lente** antes de sacar. Parece obvio y cambia todo.
- **Vertical** para historias y reels; **horizontal** sólo para la web.
- **Sacá varias** y elegí. Manos tocando, caras concentradas y risas funcionan mejor que el grupo posando.
- **Video:** 10 a 20 segundos alcanzan. Sostené el celular con las dos manos y no hagas zoom.
- **Sonido:** en shows, grabá cerca de la consola o de los parlantes, no del público.

**Permisos (importante)**

- Pedí autorización antes de publicar la cara de alguien.
- **Menores:** sólo con autorización escrita de la familia. Si no la hay, fotos de espaldas o de manos.
- Si alguien pide que se baje una foto, se baja ese mismo día.
MD,
            ],
            [
                'slug' => 'profes-artistas', 'title' => 'Para profes y artistas', 'audience' => 'DOCENTES', 'icon' => 'bi-music-note-beamed', 'template_id' => 'ptk-show',
                'summary' => 'Cómo sumarse a la difusión de tu taller o show.',
                'body' => <<<MD
- **Mandá al equipo** (por *Mensajes*) dos o tres fotos tuyas o de tu trabajo y una presentación de 3 líneas: quién sos y qué vas a hacer.
- **Compartí** desde tu cuenta las publicaciones de {$name} sobre tu taller o show. Es lo que más inscripciones trae.
- **Contá el proceso:** una historia corta de una clase o un ensayo (con permiso de quienes aparecen) vale más que un flyer.
- **Usá las plantillas** de *Diseño* (*Flyer de taller*, *Historia de show*) para que se vea parte del espacio.
- **Después:** subí fotos de la muestra o del show y etiquetá al espacio.
MD,
            ],
            [
                'slug' => 'emprendedores', 'title' => 'Para emprendedores de la feria', 'audience' => 'EMPRENDEDORES', 'icon' => 'bi-shop', 'template_id' => 'ptk-feria',
                'summary' => 'Cómo mostrar tus productos y aprovechar la difusión del espacio.',
                'body' => <<<MD
- **Producto protagonista:** fondo liso, buena luz y de cerca. Un producto por foto.
- **Precio visible.** La pregunta «¿precio?» hace perder ventas.
- **Contá la historia:** quién lo hace, con qué, por qué. La gente compra a personas.
- **Tu marca:** en *Diseño → Marca* está tu paleta (la que cargó el espacio en tu ficha). Usala en tus piezas para que te reconozcan.
- **Antes de cada feria:** publicá qué vas a llevar y etiquetá a {$name}; nosotros lo compartimos.
- **Nodo de pedidos:** si estás en el ciclo de pedidos, recordá la fecha de cierre a tus clientes.
- **Tu stock** y tus consignaciones los ves en *Feria*: no publiques algo que no tengas.
MD,
            ],
            [
                'slug' => 'whatsapp', 'title' => 'WhatsApp y comunicaciones', 'audience' => 'EQUIPO', 'icon' => 'bi-whatsapp', 'template_id' => null,
                'summary' => 'Cómo usar la lista de contactos sin cansar a nadie.',
                'body' => <<<'MD'
- **Sólo a quien aceptó** recibir novedades (lo marca el formulario de inscripción). *Comunicaciones* ya filtra eso.
- **Segmentá:** alumnos de un taller, público de shows, emprendedores. Un mensaje general por semana como máximo.
- **Mensajes cortos:** saludo, el dato, el link. Una imagen como mucho.
- **Firmá** como el espacio, no como persona.
- **Respondé** en el día. Si no sabés algo, «lo consulto y te aviso» también es una respuesta.
MD,
            ],
            [
                'slug' => 'antes-de-publicar', 'title' => 'Antes de publicar', 'audience' => 'TODOS', 'icon' => 'bi-check2-square', 'template_id' => null,
                'summary' => 'Última revisión de 30 segundos.',
                'body' => <<<'MD'
- [ ] ¿Están todos los datos? (qué, cuándo, dónde, cuánto, cómo anotarse)
- [ ] ¿Fecha y día de la semana coinciden?
- [ ] ¿Se lee bien en el celular?
- [ ] ¿Logo correcto (blanco sobre oscuro, negro sobre claro)?
- [ ] ¿Las personas que aparecen dieron permiso?
- [ ] ¿Etiquetaste a quienes participan?
- [ ] Si es una pieza oficial del espacio, ¿la vio alguien del equipo de comunicación?
MD,
            ],
        ];
    }

    /** Agrega a la organización las secciones base que todavía no tiene. Devuelve cuántas agregó. */
    public static function install(Restaurant $org, ?int $userId = null): int
    {
        $added = 0;
        foreach (self::sections(Branding::name()) as $position => $section) {
            $row = GuideSection::withoutGlobalScopes()->firstOrCreate(
                ['restaurant_id' => $org->id, 'slug' => $section['slug']],
                $section + ['position' => $position, 'updated_by' => $userId],
            );
            $added += $row->wasRecentlyCreated ? 1 : 0;
        }

        return $added;
    }
}
