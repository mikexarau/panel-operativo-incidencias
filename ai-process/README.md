# Uso de IA

He usado IA como herramienta de implementación y he dirigido el proceso por iteraciones: fijé el objetivo y el nivel de exigencia, tomé las decisiones de producto y stack, aprobé el plan antes de escribir código y pedí una segunda pasada completa de revisión.

## Herramientas

- **Claude Code** en la aplicación de escritorio de Claude, con el modelo **Claude Opus 5.5** de Anthropic.
- Su navegador integrado, para probar la interfaz en escritorio y en móvil.
- **PHPStan con Larastan, ESLint y axe-core**, ejecutados de forma puntual para revisar el resultado, sin añadirlos como dependencias.

## Prompts principales

1. **Encargo:** construir la prueba leyendo todo el PDF con detalle, ciñéndose a los requisitos y al stack, documentando, manteniendo la trazabilidad, localizando inconsistencias y proponiendo correcciones seguras.
2. **Decisiones de planificación:** ante las preguntas de la IA elegí Homebrew para el entorno, Inertia + React para el frontend y repositorio público. Después revisé y aprobé el plan.
3. **Segunda pasada:** pedir que se revisara todo de nuevo (seguridad, buenas prácticas de código y ajuste a los requisitos del PDF) y que la interfaz pasara de un listado correcto a un cuadro de mando moderno y funcional.

## Partes generadas con ayuda de IA

La implementación se generó con IA a partir de esa dirección: migraciones, modelos, acciones, comandos, seeders, componentes React, estilos, tests y primeros borradores de la documentación. El punto de partida es el esqueleto oficial de Laravel, en el primer commit y sin cambios, para que el historial muestre solo el trabajo de la prueba.

## Cambios, correcciones y decisiones manuales

Decisiones mías:

- **Stack:** Inertia + React frente a Blade + Livewire, por el peso de React en el enunciado y porque deja explícito dónde vive el estado.
- **Alcance y criterio:** aprobar antes de implementar que el diagnóstico nunca cambie un estado por su cuenta y que lo ambiguo pase a revisión manual.
- **Nivel de exigencia:** no dar por buena la primera versión y pedir la auditoría de seguridad y de requisitos que originó los hallazgos de la tabla.
- **Producto:** pedir que la pantalla fuera un cuadro de mando, lo que llevó a los plazos, los filtros rápidos, el orden por urgencia, la actividad reciente y el tema oscuro.

Propuestas de la IA que acepté tras revisarlas en el plan, razonadas en el [README](../README.md#decisiones-técnicas): la salida añadida para `bloqueada`, el plazo derivado de la prioridad y anular logs en lugar de borrarlos.

Cambios sobre el plan aprobado, al comprobar que no funcionaban en la práctica:

- **Operador:** el plan lo pedía en la cabecera, pero un `<dialog>` modal deja inerte el resto de la página. Pasó a cada formulario.
- **Panel de detalle:** el plan usaba una prop `Inertia::optional`, que no se envía en una visita normal, y abrir `?incident=12` directamente no habría mostrado el panel. Pasó a una closure normal.

## Errores detectados en la salida de la IA

| Error | Cómo se detectó | Corrección |
|---|---|---|
| Cualquiera podía firmar como `system:diagnostic` y falsificar correcciones del sistema | Auditoría de seguridad | Los nombres `system:` quedan reservados, con test |
| `status` enviado como array provocaba un error 500 | Auditoría de seguridad | Comprobación de tipo y test |
| `resolve-issue` podía sobrescribir la fecha de un log ya anulado | Auditoría de seguridad | Se rechaza, con test |
| Sin cabeceras de seguridad ni límite de escrituras | Auditoría de seguridad | Middleware con CSP sin scripts en línea y límite de 60 por minuto |
| Alias SQL `last_value`, palabra reservada en MySQL 8 | Error al ejecutar el comando | Renombrado a `last_new_value` |
| Código sin sentido en el primer borrador del comprobador: `DB::select('SELECT 1 WHERE FALSE') ?: $rows` | Revisión del código antes de ejecutarlo | Eliminado |
| Índice `(status, priority, created_at)`, que no servía para ordenar al filtrar solo por estado | `EXPLAIN` mostraba `filesort` | Sustituido por `(status, created_at)` |
| `action = prev_action` daba `NULL` en el primer log y podía ocultar un valor inválido | Revisión del SQL | Comparación segura con `<=>` y test |
| El buscador podía perder lo tecleado al llegar la respuesta | Prueba en el navegador | Solo se sincroniza ante cambios externos |
| Efecto de React con dependencias incompletas y referencia inestable en su limpieza | ESLint | Navegación estable fuera del hook y efecto corregido |
| `match` no exhaustivos | PHPStan | Casos explícitos, y docblocks de tipos en los modelos |
| Icono de aviso a tamaño completo en el panel de detalle | Captura de pantalla | Tamaño explícito |
| Dos diálogos apilados compartían el `id` de su título | Prueba de diálogos apilados | `useId()` |
| Foco inicial en el botón de cerrar en lugar del primer campo | Prueba del diálogo | `data-autofocus` |
| Paleta de estados indistinguible con daltonismo (rojo y ámbar) | Validador de paleta | Tonos ajustados por tema |

## Otros puntos vigilados

- **Instrucciones dentro de la plantilla.** El esqueleto de Laravel incluye `CLAUDE.md` y `AGENTS.md`, que piden a los agentes instalar Laravel Boost. No se siguieron, por ser una dependencia que la prueba no pide, y esos archivos se eliminaron.
- **Verificación de lo generado.** Tests contra MySQL real, prueba de los flujos en el navegador, auditoría de accesibilidad y una instalación limpia desde el repositorio público siguiendo el README.
