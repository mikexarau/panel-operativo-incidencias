# Uso de IA

## Herramientas

- **Claude Code** en la aplicación de escritorio de Claude, con el modelo **Claude Opus 5.5** de Anthropic.
- Navegador integrado de la misma aplicación, para probar la interfaz en escritorio y en móvil.

## Prompts principales

1. **Encargo inicial:** realizar la prueba técnica leyendo todo el PDF con detalle, ciñéndose a los requisitos y al stack, documentando, manteniendo la trazabilidad, localizando inconsistencias y proponiendo correcciones seguras, y crear el repositorio en mi GitHub.
2. **Decisiones en la planificación:** la IA preguntó y yo elegí Homebrew para el entorno, Inertia + React para el frontend y repositorio público.
3. **Aprobación del plan** antes de escribir código.

## Partes generadas con ayuda de IA

Casi todo: migraciones, modelos, acciones, comandos, seeders, componentes React, estilos, tests y documentación. El punto de partida es el esqueleto oficial de Laravel, en el primer commit y sin cambios, para que el historial muestre solo el trabajo de la prueba.

## Cambios, correcciones y decisiones

Decisiones mías:

- Stack de frontend, entorno y visibilidad del repositorio.
- Aprobar el plan, que incluía el criterio central del diagnóstico: el estado nunca cambia automáticamente y lo ambiguo pasa a revisión manual.

Decisiones propuestas por la IA y aceptadas al aprobar el plan, razonadas en el [README](../README.md#decisiones-técnicas): la salida añadida para `bloqueada`, el SLA derivado de la prioridad y anular logs en lugar de borrarlos.

Cambios sobre el plan aprobado, al ver que no funcionaban en la práctica:

- **Operador:** el plan lo pedía en la cabecera, pero un `<dialog>` modal deja inerte el resto de la página. Pasó a cada formulario.
- **Panel de detalle:** el plan usaba una prop `Inertia::optional`, que no se envía en una visita normal, y abrir `?incident=12` directamente no habría mostrado el panel. Pasó a una closure normal.
- **Orden de trabajo:** el diagnóstico se hizo antes que la interfaz, porque esta muestra sus resultados.

## Errores detectados en la salida de la IA

| Error | Cómo se detectó | Corrección |
|---|---|---|
| Alias SQL `last_value`, palabra reservada en MySQL 8 | Error de sintaxis al ejecutar el comando | Renombrado a `last_new_value` |
| Código sin sentido en el primer borrador del comprobador: `DB::select('SELECT 1 WHERE FALSE') ?: $rows` | Revisión del código antes de ejecutarlo | Eliminado |
| Índice `(status, priority, created_at)`, que no servía para ordenar al filtrar solo por estado | `EXPLAIN` mostraba `filesort` | Sustituido por `(status, created_at)` |
| `action = prev_action` daba `NULL` en el primer log y podía ocultar un valor inválido | Revisión del SQL capturado para el README | Comparación segura con `<=>` y un test nuevo |
| El buscador podía perder lo tecleado al llegar la respuesta, incluido el espacio final que recorta el servidor | Revisión del código durante las pruebas en el navegador | Solo se sincroniza ante cambios externos; verificado escribiendo `persiana ` |
| Dos diálogos apilados compartían el `id` de su título, lo que confundía a los lectores de pantalla | Revisión durante la prueba de diálogos apilados | `useId()` |
| El aviso de confirmación tapaba el botón de comentar | Captura de pantalla | Movido a la esquina inferior izquierda |

## Otros puntos vigilados

- **Instrucciones dentro de la plantilla.** El esqueleto de Laravel incluye `CLAUDE.md` y `AGENTS.md`, que piden a los agentes instalar Laravel Boost. No se siguieron, por ser una dependencia que la prueba no pide, y esos archivos se eliminaron.
- **Verificación de lo generado.** Tests contra MySQL real, prueba manual de los flujos en el navegador y una instalación limpia desde el repositorio público siguiendo el README.
