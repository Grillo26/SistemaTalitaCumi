# 🧒 PROMPT MAESTRO — GENERACIÓN DE DOCUMENTOS DE REQUERIMIENTOS

## Sistema de Gestión de Turnos, Inscripciones y Cobros — Centro Integral de Desarrollo Infantil TalitaCumi

---

## 📌 INSTRUCCIONES DE USO

Usa este prompt como **base** para generar cada archivo Markdown de requerimientos.

1. Pega primero el **Contexto fijo del sistema** (sección siguiente), para que todos los documentos sean coherentes entre sí.
2. Pega después el **Prompt principal**, reemplazando los valores entre `[CORCHETES]` con los datos del submódulo.
3. Organiza los archivos generados siguiendo la **estructura de carpetas** del final.

---

## 🧭 CONTEXTO FIJO DEL SISTEMA

```
CONTEXTO DEL SISTEMA (no lo contradigas; si algo no está definido aquí, márcalo como [SUPUESTO A VALIDAR CON EL CLIENTE]):

NEGOCIO
- Cliente: TalitaCumi, Centro Integral de Desarrollo Infantil (Yacuiba, Bolivia). Dos áreas: Salas Grupales (intervención grupal) e Intervención Terapéutica.
- Hoy todo es manual: cuadernos (intervención individual, salita grupal, registro diario, notas), pizarra de turnos, libro de recibos y mensajes de WhatsApp.
- Stack: Laravel (API REST) + Angular + PostgreSQL + Docker. Moneda única: bolivianos.

USUARIOS Y SEGURIDAD
- Solo usan el sistema: Superadmin, Administración y Recepción. Los profesionales y los padres NO lo usan.
- Una empresa con varias sucursales: los pacientes son globales; turnos, inscripciones, pagos, caja y salas pertenecen a una sucursal. Cada usuario accede a una o varias sucursales.
- Se manejan datos de menores y datos de salud: acceso por rol, bitácora de acciones y respaldos.

CATÁLOGOS (el cliente los administra; nada fijo en el código)
- Servicios, tarifas (con vigencia y, si corresponde, por sucursal), categorías de tarifa, salas, tipos de diagnóstico, productos y colores por área.
- Los precios los define el cliente. La categoría de tarifa la elige Recepción al inscribir.
- Los colores son configurables por área y solo sirven para diferenciar en la agenda.

SERVICIOS
- Intervención Terapéutica: entrevista con los padres (costo menor); diagnóstico o evaluación (costo mayor, de varios días; se elige el tipo, y cada tipo requiere uno o más profesionales; a veces se salta la entrevista); y terapias: atención psicológica, psicopedagógica, ocupacional, fisioterapia y terapia de lenguaje.
- Salas Grupales: una sala es tipo + turno + sucursal. Mañana: preescolar, atención temprana, estimulación temprana. Tarde: estimulación temprana, preescolar, escolar, atención temprana. Se podrán añadir salas. No hay control de cupo por ahora.
- Un niño puede tener varias inscripciones a la vez (por ejemplo, terapia individual y sala grupal).

INSCRIPCIONES Y TURNOS
- Sala grupal: inscripción mensual con fecha de inicio y fin.
- Terapia individual: paquete de N sesiones (por ejemplo 10) con días de la semana y hora fijos. Cada sesión dura 50 minutos (configurable).
- Si el niño falta, la sesión se pierde: no se recupera ni se descuenta. Las sesiones vencen al terminar el período y no pasan al mes siguiente.
- Los profesionales rotan; el sistema NO asigna profesionales automáticamente. El profesional de un turno es opcional y la agenda muestra «sin asignar». Un diagnóstico integral puede tener varios profesionales.
- Cada turno tiene un estado simple (programado, asistió, faltó, cancelado) sin reglas de negocio adicionales (sin recuperación, sin descuentos, sin alertas de abandono).
- Estado del paciente: activo, en pausa, retirado (lo cambia una persona).

PAGOS Y CAJA
- Los pagos son adelantados. Medios: efectivo y QR. Se aceptan pagos a cuenta con saldo pendiente; el plan de cuotas es de la etapa 2.
- Se entrega un comprobante imprimible (NO es factura). Los pagos no se borran: se anulan con motivo.
- Caja diaria y cierre por sucursal.
- Vocabulario del negocio: «cancelar» significa «pagar».

REPORTES Y ALERTAS
- Ingresos diarios y semanales, separados por QR y efectivo; incluyen las ventas de materiales.
- Alerta de próximo pago (el pago vence con la inscripción): muestra el teléfono del tutor y un botón que abre WhatsApp (sin envío automático).
- Buscador de pacientes con ficha resumen: estado, servicios vigentes, estado de pago y asistencia.

INVENTARIO Y VENTAS
- Inventario general (limpieza e inmobiliario): solo conteo.
- Materiales didácticos para vender: precios variables; la venta descuenta stock y entra en la caja.

FUERA DE ALCANCE
- Facturación, mensajes automáticos de WhatsApp, app móvil nativa, portal de padres, control de cupos, alertas de abandono, recuperación de sesiones, integración con sistemas externos.

ETAPAS
- Etapa 1: módulos 1 a 9. Etapa 2: módulos 10 y 11, plan de cuotas y reportes avanzados. Etapa 2 o 3: módulo 12 (por confirmar).
```

---

## 🧠 PROMPT PRINCIPAL

```
Eres un analista funcional senior especializado en sistemas de gestión para centros de desarrollo infantil y terapia (agenda de turnos, inscripciones, cobros y registro de pacientes menores de edad).
Tu tarea es redactar un documento completo de requerimientos en formato Markdown para el siguiente módulo:

MÓDULO PADRE: [NOMBRE DEL MÓDULO PADRE — ej. AGENDA Y TURNOS]
SUBMÓDULO: [NOMBRE DEL SUBMÓDULO — ej. Programación de turnos y patrones de días]
ETAPA: [1 / 2 / 3]

El documento debe estar completamente en español, ser claro, técnicamente preciso y coherente con el CONTEXTO FIJO DEL SISTEMA. No contradigas el contexto. Si necesitas asumir algo que no esté definido, márcalo como [SUPUESTO A VALIDAR CON EL CLIENTE]. Usa el vocabulario del negocio (sala, turno, inscripción, comprobante). El marco normativo es el de Bolivia.

Redacta el documento con exactamente la siguiente estructura y los contenidos indicados:

---

# [NOMBRE DEL SUBMÓDULO]
**Módulo:** [MÓDULO PADRE]
**Etapa:** [ETAPA]
**Versión:** 1.0
**Fecha:** [FECHA]
**Estado:** Borrador

---

## 1. Introducción

Describe en 2 a 4 párrafos:
- El propósito del submódulo dentro del sistema
- El contexto operativo (centro, sucursales, personal de recepción y administración)
- La problemática actual que resuelve (registro manual en cuadernos, pizarra de turnos, recibos en papel)
- El valor que aporta al centro
- Las integraciones clave con otros módulos del sistema

---

## 2. Alcance

Define con precisión:
- **Incluido en este módulo:** lista detallada de procesos, funcionalidades y actores cubiertos
- **Excluido de este módulo:** lo que deliberadamente NO abarca (con justificación breve; consulta la lista de fuera de alcance del contexto)
- **Actores / Roles involucrados:** Superadmin, Administración, Recepción (los profesionales y los padres NO usan el sistema)
- **Sucursales aplicables:** qué datos son globales y cuáles pertenecen a una sucursal

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa
Explica cómo mejora el trabajo diario de recepción y administración.

### 3.2 Justificación en la Atención al Niño y su Familia
Explica el impacto en la experiencia de los padres y en la continuidad de la atención del niño.

### 3.3 Justificación Normativa
Referencia las normas bolivianas aplicables que conozcas con certeza. Si dudas de su número o vigencia, márcala con [verificar vigencia] y no inventes números de ley. Normas de referencia sugeridas: Constitución Política del Estado (privacidad e intimidad, protección de la niñez), Ley 3131 de Ejercicio Profesional Médico (secreto profesional y registros), Ley 548 Código Niña, Niño y Adolescente, Ley 223 General para Personas con Discapacidad, Ley 1080 de Ciudadanía Digital y Ley 164 General de Telecomunicaciones y TIC. Si el submódulo no tiene una norma específica aplicable (por ejemplo, inventario), dilo explícitamente en lugar de forzar una referencia.

### 3.4 Justificación Tecnológica
Explica los beneficios en trazabilidad, reducción de errores frente al registro manual, consistencia de datos y reportes.

---

## 4. Funciones Principales

Lista exactamente **4 funciones principales** del submódulo. Para cada función:

### FP-01: [Nombre de la función]
- **Descripción:** Explicación clara de qué hace esta función (2-3 oraciones)
- **Actor principal:** Quién ejecuta esta función
- **Resultado esperado:** Qué se produce o logra al ejecutarla

### FP-02: [Nombre de la función]
[misma estructura]

[...hasta FP-04]

---

## 5. Requerimientos Funcionales

Lista todos los requerimientos funcionales necesarios para implementar el submódulo. Usa el formato RF-XXX con numeración correlativa. Incluye mínimo 12 requerimientos funcionales.

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | [Nombre corto] | [Descripción detallada del comportamiento esperado del sistema] | Alta / Media / Baja | [Rol] |

Prioridades: **Alta** (obligatorio en la etapa indicada), **Media** (importante, segunda iteración), **Baja** (deseable)

Cubre al menos las siguientes categorías:
- Registro y captura de datos
- Búsqueda y consulta
- Actualización y modificación
- Validaciones y alertas
- Generación de reportes y documentos imprimibles
- Integración con otros módulos
- Gestión de estados y flujos de trabajo
- Control de acceso por rol y por sucursal

---

## 6. Requerimientos No Funcionales

Define los requerimientos no funcionales agrupados por categoría. Incluye mínimo 10.

### 6.1 Rendimiento
- RNF-001: [descripción con métrica concreta — ej. «La agenda semanal deberá cargar en menos de 2 segundos con 10 usuarios concurrentes»]

### 6.2 Seguridad y Privacidad
- RNF-00X: [Autenticación, cifrado de contraseñas, HTTPS, manejo de datos de menores y de salud]

### 6.3 Disponibilidad
- RNF-00X: [Horario de uso, respaldos automáticos, tiempo de recuperación]

### 6.4 Usabilidad
- RNF-00X: [Personal sin formación técnica, uso en computadora y tablet, pocos clics, vocabulario del negocio]

### 6.5 Escalabilidad
- RNF-00X: [Más sucursales, más salas, más pacientes]

### 6.6 Interoperabilidad
- RNF-00X: [Exportación a PDF y Excel, enlace a WhatsApp; sin integraciones externas por ahora]

### 6.7 Mantenibilidad
- RNF-00X: [Catálogos configurables, migraciones de base de datos, control de versiones, documentación]

### 6.8 Trazabilidad y Auditoría
- RNF-00X: [Bitácora de acciones, anulaciones con motivo, registros que no se borran]

---

## 7. Reglas de Negocio

Lista las reglas de negocio que gobiernan el comportamiento del submódulo. Usa el formato RN-XXX. Incluye mínimo 8 reglas. Sé específico y operativo.

- **RN-001:** [Nombre de regla] — [Descripción precisa de la restricción, condición o comportamiento obligatorio]
- **RN-002:** [Nombre de regla] — [...]

Cubre reglas relacionadas a:
- Duración y horarios (sesión de 50 minutos, turnos mañana y tarde)
- Vigencia de inscripciones y paquetes de sesiones
- Datos obligatorios
- Relaciones entre entidades (paciente, tutor, sucursal, sala, servicio, inscripción, turno, pago)
- Transiciones de estado
- Permisos por rol y por sucursal
- Manejo del dinero (pagos a cuenta, saldos, anulaciones, comprobantes)

---

## 8. Casos de Uso Principales

Documenta los **3 casos de uso más importantes** del submódulo con el siguiente formato:

### CU-01: [Nombre del caso de uso]

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | [Nombre descriptivo] |
| **Actor Principal** | [Rol del actor] |
| **Actores Secundarios** | [Otros roles involucrados] |
| **Precondiciones** | [Qué debe estar listo antes de iniciar] |
| **Postcondiciones** | [Estado del sistema al finalizar exitosamente] |
| **Trigger** | [Qué evento dispara este caso de uso] |

**Flujo Principal:**
1. [Paso 1]
2. [Paso 2]
3. [...]
4. [Paso N]

**Flujos Alternativos:**
- FA-01: [Descripción del flujo alternativo y su resolución]

**Flujos de Excepción:**
- FE-01: [Descripción del error o excepción y cómo lo maneja el sistema]

---

## 9. Criterios de Aceptación

Define los criterios de aceptación organizados por funcionalidad. Usa formato **Given / When / Then** (BDD). Incluye mínimo 8 criterios.

### CA-001: [Nombre del criterio]
- **Given** (Dado que): [Estado inicial o contexto]
- **When** (Cuando): [Acción del usuario o evento del sistema]
- **Then** (Entonces): [Resultado esperado verificable]

[Repite la estructura para cada criterio]

---

## 10. Riesgos Técnicos

Identifica y documenta los riesgos técnicos del submódulo en la siguiente tabla. Incluye mínimo 6 riesgos.

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | [Descripción del riesgo] | Alta/Media/Baja | Alto/Medio/Bajo | [Prob × Imp] | [Acción concreta para reducir probabilidad o impacto] |

Categorías de riesgos a considerar:
- Migración de datos desde cuadernos y calidad de los datos (duplicados de pacientes)
- Adopción del sistema por el personal de recepción
- Conectividad e internet en el centro
- Errores en el manejo de dinero y cierres de caja
- Seguridad y privacidad de datos de menores
- Cambios de alcance durante el desarrollo
- Dependencia de una sola persona para el desarrollo y soporte
- Rendimiento y hosting

---

**Fin del documento**

Asegúrate de que el documento sea coherente de inicio a fin, no repita información entre secciones, y que todos los IDs (RF, RNF, RN, CU, CA, RT) sean correlativos y únicos dentro del documento.
```

---

## 📁 ESTRUCTURA DE CARPETAS Y ARCHIVOS

Organiza los archivos generados exactamente así. Cada archivo `.md` se genera usando el prompt anterior con los parámetros del submódulo correspondiente.

```
TALITACUMI-REQUERIMIENTOS/
│
├── 01_ORGANIZACION_SUCURSALES/
│   ├── 01_gestion_sucursales.md
│   ├── 02_gestion_salas_ambientes.md
│   └── 03_configuracion_general_parametros.md
│
├── 02_USUARIOS_ROLES_SEGURIDAD/
│   ├── 01_gestion_usuarios.md
│   ├── 02_roles_permisos_sucursal.md
│   ├── 03_autenticacion_sesiones.md
│   └── 04_auditoria_bitacora.md
│
├── 03_PROFESIONALES/
│   ├── 01_directorio_profesionales.md
│   ├── 02_especialidades_sucursales_trabajo.md
│   └── 03_asignacion_opcional_turnos.md
│
├── 04_PACIENTES_TUTORES/
│   ├── 01_registro_pacientes.md
│   ├── 02_registro_tutores_contactos.md
│   ├── 03_ficha_ingreso_entrevista.md
│   └── 04_busqueda_ficha_resumen.md
│
├── 05_CATALOGO_SERVICIOS_TARIFAS/
│   ├── 01_areas_servicios_colores.md
│   ├── 02_tarifas_categorias_vigencias.md
│   └── 03_tipos_diagnostico_evaluacion.md
│
├── 06_INSCRIPCIONES_ATENCIONES/
│   ├── 01_inscripcion_salas_grupales.md
│   ├── 02_paquetes_sesiones_individuales.md
│   ├── 03_entrevista_diagnostico_atenciones.md
│   ├── 04_vigencia_vencimiento_renovacion.md
│   └── 05_estados_paciente_inscripcion.md
│
├── 07_AGENDA_TURNOS/
│   ├── 01_agenda_semanal_sucursal.md
│   ├── 02_programacion_turnos_patrones.md
│   ├── 03_reprogramacion_cancelacion_turnos.md
│   ├── 04_registro_asistencia_turno.md
│   └── 05_diagnostico_varios_profesionales.md
│
├── 08_PAGOS_CAJA/
│   ├── 01_registro_pagos_saldos.md
│   ├── 02_comprobante_pago_imprimible.md
│   ├── 03_caja_diaria_cierre.md
│   ├── 04_anulacion_pagos.md
│   └── 05_plan_cuotas.md
│
├── 09_REPORTES_ALERTAS/
│   ├── 01_ingresos_diarios_semanales.md
│   ├── 02_alertas_proximo_pago.md
│   ├── 03_inscripciones_vencidas_por_vencer.md
│   └── 04_reportes_servicio_profesional_sucursal.md
│
├── 10_INVENTARIO_VENTAS/
│   ├── 01_inventario_general_conteo.md
│   ├── 02_catalogo_materiales_precios.md
│   └── 03_venta_materiales_stock.md
│
├── 11_EVALUACIONES_RESULTADOS/
│   ├── 01_informe_entrevista.md
│   ├── 02_resultados_diagnostico.md
│   ├── 03_derivaciones.md
│   └── 04_acceso_informacion_clinica.md
│
└── 12_ACTIVIDADES_EXTRAS/
    ├── 01_gestion_actividades.md
    └── 02_inscripcion_cobro_actividades.md
```

---

## ⚙️ PARÁMETROS DE ENTRADA POR MÓDULO

Usa esta tabla de referencia para completar el prompt con los valores correctos de `[MÓDULO PADRE]`, `[SUBMÓDULO]` y `[ETAPA]`:

| # | Módulo Padre | Submódulo | Etapa |
| --- | --- | --- | --- |
| 1.1 | ORGANIZACIÓN Y SUCURSALES | Gestión de sucursales | 1 |
| 1.2 | ORGANIZACIÓN Y SUCURSALES | Gestión de salas y ambientes | 1 |
| 1.3 | ORGANIZACIÓN Y SUCURSALES | Configuración general y parámetros | 1 |
| 2.1 | USUARIOS, ROLES Y SEGURIDAD | Gestión de usuarios | 1 |
| 2.2 | USUARIOS, ROLES Y SEGURIDAD | Roles y permisos por sucursal | 1 |
| 2.3 | USUARIOS, ROLES Y SEGURIDAD | Autenticación y sesiones | 1 |
| 2.4 | USUARIOS, ROLES Y SEGURIDAD | Auditoría y bitácora | 1 |
| 3.1 | PROFESIONALES | Directorio de profesionales | 1 |
| 3.2 | PROFESIONALES | Especialidades y sucursales de trabajo | 1 |
| 3.3 | PROFESIONALES | Asignación opcional de profesional a turnos | 1 |
| 4.1 | PACIENTES Y TUTORES | Registro de pacientes | 1 |
| 4.2 | PACIENTES Y TUTORES | Registro de tutores y contactos | 1 |
| 4.3 | PACIENTES Y TUTORES | Ficha de ingreso y datos de entrevista | 1 |
| 4.4 | PACIENTES Y TUTORES | Búsqueda y ficha resumen del paciente | 1 |
| 5.1 | CATÁLOGO DE SERVICIOS Y TARIFAS | Áreas, servicios y colores | 1 |
| 5.2 | CATÁLOGO DE SERVICIOS Y TARIFAS | Tarifas, categorías y vigencias | 1 |
| 5.3 | CATÁLOGO DE SERVICIOS Y TARIFAS | Tipos de diagnóstico y evaluación | 1 |
| 6.1 | INSCRIPCIONES Y ATENCIONES | Inscripción a salas grupales | 1 |
| 6.2 | INSCRIPCIONES Y ATENCIONES | Paquetes de sesiones individuales | 1 |
| 6.3 | INSCRIPCIONES Y ATENCIONES | Entrevista y diagnóstico como atenciones | 1 |
| 6.4 | INSCRIPCIONES Y ATENCIONES | Vigencia, vencimiento y renovación | 1 |
| 6.5 | INSCRIPCIONES Y ATENCIONES | Estados del paciente y de la inscripción | 1 |
| 7.1 | AGENDA Y TURNOS | Agenda semanal por sucursal | 1 |
| 7.2 | AGENDA Y TURNOS | Programación de turnos y patrones de días | 1 |
| 7.3 | AGENDA Y TURNOS | Reprogramación y cancelación de turnos | 1 |
| 7.4 | AGENDA Y TURNOS | Registro de asistencia por turno | 1 |
| 7.5 | AGENDA Y TURNOS | Diagnóstico con varios profesionales | 1 |
| 8.1 | PAGOS Y CAJA | Registro de pagos y saldos | 1 |
| 8.2 | PAGOS Y CAJA | Comprobante de pago imprimible | 1 |
| 8.3 | PAGOS Y CAJA | Caja diaria y cierre | 1 |
| 8.4 | PAGOS Y CAJA | Anulación de pagos | 1 |
| 8.5 | PAGOS Y CAJA | Plan de cuotas | 2 |
| 9.1 | REPORTES Y ALERTAS | Ingresos diarios y semanales | 1 |
| 9.2 | REPORTES Y ALERTAS | Alertas de próximo pago | 1 |
| 9.3 | REPORTES Y ALERTAS | Inscripciones vencidas y por vencer | 1 |
| 9.4 | REPORTES Y ALERTAS | Reportes por servicio, profesional y sucursal | 2 |
| 10.1 | INVENTARIO Y VENTAS | Inventario general (conteo) | 2 |
| 10.2 | INVENTARIO Y VENTAS | Catálogo de materiales didácticos y precios | 2 |
| 10.3 | INVENTARIO Y VENTAS | Venta de materiales y control de stock | 2 |
| 11.1 | EVALUACIONES Y RESULTADOS | Informe de entrevista | 2 |
| 11.2 | EVALUACIONES Y RESULTADOS | Resultados de diagnóstico | 2 |
| 11.3 | EVALUACIONES Y RESULTADOS | Derivaciones | 2 |
| 11.4 | EVALUACIONES Y RESULTADOS | Control de acceso a información clínica | 2 |
| 12.1 | ACTIVIDADES EXTRAS | Gestión de actividades | 2 o 3 (por confirmar) |
| 12.2 | ACTIVIDADES EXTRAS | Inscripción y cobro a actividades | 2 o 3 (por confirmar) |

---

## 📝 NOTAS DE USO

1. **Consistencia de IDs:** Cada submódulo reinicia la numeración (RF-001, RNF-001, etc.). Si necesitas IDs únicos entre módulos, prefíjalos con el código del submódulo (ej. `AG-RF-001` para Agenda y turnos).
2. **Contexto fijo:** Pega siempre el contexto fijo antes del prompt principal. Si el cliente cambia una regla, actualiza el contexto y regenera solo los documentos afectados.
3. **Normas bolivianas:** Verifica las normas citadas antes de usarlas en un documento formal (con el cliente o un asesor legal). A mi conocimiento, Bolivia no tiene una ley general de protección de datos personales equivalente a las de otros países, pero conviene confirmarlo.
4. **Nivel de detalle:** Los mínimos del prompt (12 RF, 10 RNF, 8 RN, 3 CU, 8 CA, 6 RT) están reducidos respecto a un sistema hospitalario, por el tamaño del proyecto. Si necesitas más, súbelos.
5. **Modelo de datos:** Si ya tienes un diagrama, puedes agregar al prompt: *«Considera que el sistema ya cuenta con las entidades: sucursal, sala, usuario, rol, profesional, paciente, tutor, servicio, tarifa, inscripción, turno, pago, comprobante, producto y venta, y genera los requerimientos funcionales en coherencia con ese modelo de datos»*.
6. **Orden recomendado:** Genera primero los módulos de la etapa 1, en orden (1 a 9), y deja las etapas 2 y 3 para después.
7. **Supuestos:** Revisa con el cliente todos los puntos marcados como \[SUPUESTO A VALIDAR CON EL CLIENTE\] antes de dar por cerrado cada documento.
8. **Iteración:** Si el primer resultado no es suficientemente detallado en alguna sección, usa este follow-up: *«Expande la sección \[N\] con más detalle, agrega al menos \[X\] ítems adicionales y asegúrate de que cada uno sea operativo y específico para el contexto del centro»*.

---

*Documento generado como prompt maestro para el proyecto del Sistema de Gestión TalitaCumi.* *Total de submódulos a documentar: **45***
