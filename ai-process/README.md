# Uso de IA

He usado IA como herramienta de implementación y he dirigido el proceso por iteraciones: fijé el objetivo y el nivel de exigencia, tomé las decisiones de producto y stack, aprobé el plan antes de escribir código y pedí una segunda pasada completa de revisión.

## Herramientas

- **Claude Code**, con el modelo Claude Opus 5.5.
- **PHPStan con Larastan, ESLint y axe-core**, para revisar el resultado.

## Prompts principales

1. **Encargo:** construir la prueba leyendo todo el PDF con detalle, ciñéndose a los requisitos y al stack, documentando, manteniendo la trazabilidad, localizando inconsistencias y proponiendo correcciones seguras.
2. **Decisiones de planificación:** ante las preguntas de la IA elegí Inertia + React para el frontend y Homebrew para instalar el entorno, porque desarrollo en macOS.
3. **Segunda pasada:** pedir que se revisara todo de nuevo (seguridad, buenas prácticas de código y ajuste a los requisitos del PDF) y que la interfaz pasara de un listado correcto a un cuadro de mando moderno y funcional.

## Partes generadas con ayuda de IA

La implementación se generó con IA a partir de esa dirección, sobre el esqueleto oficial de Laravel: migraciones, modelos, acciones, comandos, seeders, componentes React, estilos, tests y primeros borradores de la documentación.

## Cambios, correcciones y decisiones manuales

Decisiones mías:

- **Stack:** Inertia + React frente a Blade + Livewire, por el peso de React en el enunciado y porque deja explícito dónde vive el estado.
- **Alcance y criterio:** aprobar antes de implementar que el diagnóstico nunca cambie un estado por su cuenta y que lo ambiguo pase a revisión manual.
- **Nivel de exigencia:** no dar por buena la primera versión y pedir la auditoría de seguridad y de requisitos que originó los hallazgos de la tabla.
- **Producto:** pedir que la pantalla fuera un cuadro de mando, lo que llevó a los plazos, los filtros rápidos, el orden por urgencia, la actividad reciente y el tema oscuro.

Propuestas de la IA que acepté tras revisarlas en el plan, razonadas en el [README](../README.md#decisiones-técnicas): la salida añadida para `bloqueada`, el plazo derivado de la prioridad y anular logs en lugar de borrarlos.

## Errores detectados en la salida de la IA

Los más relevantes:

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
| Efecto de React con dependencias incompletas y `match` no exhaustivos | ESLint y PHPStan | Corregidos |

El resultado se verificó con tests contra MySQL real, prueba de los flujos en el navegador, una auditoría de accesibilidad y una instalación limpia desde el repositorio siguiendo el README.
