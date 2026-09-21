=== Accesibilidad LSC - Bloques por Menú ===
Requires at least: 5.0
Tested up to: 6.x
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later

Muestra videos (MP4) o GIF en Lengua de Señas Colombiana (LSC) asociados a los ítems principales de cualquier menú de WordPress, como estrategia de accesibilidad e inclusión.

== Descripción ==

Este plugin permite crear "bloques" de contenido LSC desde el panel de administración de WordPress. Cada bloque asigna un video o GIF con interpretación en Lengua de Señas Colombiana a los ítems de nivel 0 (principales) de un menú de WordPress elegido, con su propio nombre, tamaño, posición y páginas donde debe mostrarse.

* En **escritorio**: al pasar el cursor (hover) sobre un ítem del menú que tenga contenido asignado, aparece un recuadro flotante junto al enlace reproduciendo el video o GIF, con el tamaño y la posición configurados en el bloque.
* En **móvil** (menú offcanvas): aparece un pequeño ícono junto al enlace; al tocarlo se abre un modal centrado con el contenido. El enlace del menú sigue funcionando normalmente.
* Si un ítem no tiene contenido asignado, no se muestra nada adicional.
* Cada bloque puede limitarse a **todo el sitio** o a **páginas específicas**.
* El botón "Campus Virtual etR" (elemento fijo del tema, no un ítem de menú) se configura aparte, en su propia pantalla.

No requiere licencias externas ni servicios de terceros: todo el contenido se aloja en la Biblioteca de Medios de WordPress.

== Instalación ==

1. Sube la carpeta `plugin-accesibilidad-lsc` a `/wp-content/plugins/`.
2. Activa el plugin desde el panel "Plugins" de WordPress.
3. Ve a "Accesibilidad LSC" en el menú de administración y haz clic en "Añadir bloque nuevo".
4. Ponle un nombre al bloque (ej. "Menú de apoyo visual principal"), elige el menú de WordPress de origen, y ajusta el tamaño, la posición y el alcance (todo el sitio o páginas específicas).
5. Para cada ítem principal del menú elegido, haz clic en "Subir GIF/Video", selecciona el archivo (GIF o MP4) desde la Biblioteca de Medios.
6. Guarda el bloque. Puedes crear tantos bloques como menús quieras cubrir.
7. Para el botón "Campus Virtual etR", usa la fila fija al final del listado.

== Requisitos técnicos ==

* Compatible con PHP 7.4, 8.2 y 8.3.
* No depende de jQuery ni de librerías externas en el frontend (JS nativo). El panel de administración usa `wp.media`, que ya requiere jQuery en el admin de WordPress.
* Los bloques se guardan como un tipo de contenido interno (`lsc_bloque`), sin interfaz nativa de WordPress: solo se administran desde las pantallas propias del plugin.

== Prueba recomendada antes de producción ==

1. Instalar en un entorno de staging.
2. Si venías de la versión 1.x, confirmar que se creó automáticamente un bloque "Menú principal (migrado)" con la configuración anterior (solo ítems de nivel 0).
3. Crear un bloque nuevo apuntando a otro menú, con al menos un ítem con GIF y otro con MP4.
4. Verificar en escritorio que el hover muestra el recuadro con el tamaño y posición configurados, sin desplazar el layout del menú.
5. Verificar en móvil (o modo responsive del navegador) que el ícono aparece junto al enlace y que el modal se abre/cierra correctamente, incluyendo con la tecla Escape.
6. Configurar el alcance de un bloque a una página específica y verificar que no aparece en otras páginas.
7. Eliminar un bloque y confirmar que pasa a la papelera de WordPress.
8. Verificar que el botón Campus Virtual sigue funcionando de forma independiente.

== Changelog ==

= 2.0.0 =
* Rediseño a sistema de bloques con CRUD completo (crear, editar, eliminar).
* Cada bloque elige su propio menú de WordPress de origen (ya no autodetección).
* Solo se admite contenido en ítems de nivel 0 (se elimina el soporte a subítems).
* Tamaño y posición del recuadro configurables por bloque.
* Alcance por bloque: todo el sitio o páginas específicas.
* El botón "Campus Virtual etR" pasa a configurarse en su propia pantalla, fuera del CRUD de bloques.
* Migración automática de la configuración de la versión 1.x.

= 1.0.0 =
* Versión inicial: hover en escritorio, ícono + modal en móvil, panel de administración con subida de medios.
