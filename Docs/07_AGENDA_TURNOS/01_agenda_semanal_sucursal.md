# Agenda Semanal por Sucursal
**Módulo:** Agenda y Turnos
**Etapa:** 1
**Versión:** 1.0
**Fecha:** Noviembre de 2025
**Estado:** Borrador

---

## 1. Introducción

Este submódulo provee la vista principal de la agenda semanal de cada sucursal, desde la cual Recepción y Administración consultan, organizan y dan seguimiento a los turnos de las salas grupales y de las terapias individuales. Es el reemplazo directo de la pizarra de turnos y del cuaderno de registro diario que hoy se usan en el centro.

La agenda semanal muestra los turnos distribuidos por día de la semana y por hora, diferenciando visualmente el área (Salas Grupales o Intervención Terapéutica) mediante los colores configurables por área, y permitiendo identificar de un vistazo qué turnos están programados, cuáles tienen profesional asignado y cuáles están "sin asignar". Cada sucursal tiene su propia agenda; los pacientes son globales pero los turnos pertenecen a la sucursal.

El valor principal es la visibilidad operativa: Recepción responde consultas de las familias sin buscar en cuadernos, detecta solapamientos y turnos sin profesional, y Administración obtiene una vista consolidada de la carga semanal por sucursal. Se integra con Programación de Turnos y Patrones de Días (7.2), Registro de Asistencia por Turno (7.4), Diagnóstico con Varios Profesionales (7.5), Reprogramación y Cancelación (7.3), y con los módulos de Inscripciones, Profesionales y Reportes.

---

## 2. Alcance

**Incluido en este módulo:**
- Vista semanal de la agenda por sucursal, con navegación entre semanas.
- Visualización de turnos por día y hora, con colores configurables por área.
- Filtros por sucursal, área, sala, profesional, servicio y estado del turno.
- Indicación visual de turnos "sin asignar".
- Vista por profesional y vista por sala.
- Vista de detalle del turno con acceso a acciones (asistencia, reprogramación, cancelación).
- Exportación de la agenda semanal a PDF.
- Control de acceso por rol y por sucursal.

**Excluido de este módulo:**
- Creación y programación de turnos (corresponde a 7.2).
- Registro de asistencia (corresponde a 7.4).
- Reprogramación y cancelación (corresponde a 7.3).
- Asignación automática de profesionales (los profesionales rotan y se asignan manualmente).
- Control de cupo por sala (fuera de alcance del proyecto).
- Alertas de abandono.
- Vista de agenda para profesionales o padres (ellos no usan el sistema).
- Sincronización con calendarios externos (Google Calendar, Outlook).

**Actores / Roles involucrados:**
- **Recepción:** consulta la agenda de su sucursal y accede a acciones básicas.
- **Administración:** consulta la agenda de sus sucursales, filtra y exporta.
- **Superadmin:** acceso total a todas las sucursales.
- Los profesionales y los padres NO usan el sistema.

**Sucursales aplicables:**
- La agenda es por sucursal; los turnos pertenecen a la sucursal de la sala o del paquete.
- El usuario solo ve las sucursales que tenga asignadas.
- Los pacientes son globales, pero se muestran en la agenda de la sucursal donde tienen el turno.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa
Sustituye la pizarra de turnos por una vista digital siempre disponible y actualizada. Recepción encuentra rápido el turno de un paciente, verifica el profesional asignado, detecta solapamientos y responde consultas en segundos. Administración obtiene una vista consolidada para planificar la carga semanal y detectar turnos sin profesional.

### 3.2 Justificación en la Atención al Niño y su Familia
La familia recibe información precisa sobre el día, hora, sala y profesional de la próxima sesión. La agenda evita confusiones de horario y reduce el riesgo de que un niño llegue cuando no corresponde o se pierda una sesión por desinformación.

### 3.3 Justificación Normativa
- Constitución Política del Estado (privacidad e intimidad; protección de la niñez) [verificar vigencia].
- Ley 548 Código Niña, Niño y Adolescente [verificar vigencia].
- Ley 1080 de Ciudadanía Digital (resguardo de información) [verificar vigencia].
- No existe una norma específica sobre agendas de servicios de desarrollo infantil; la organización es operativa y contractual.

### 3.4 Justificación Tecnológica
La vista semanal se construye con consultas optimizadas sobre PostgreSQL y renderizado en Angular, con caché por sucursal y semana. Los colores configurables por área se cargan desde catálogo, sin código duro. La agenda respeta permisos por rol y sucursal, y la exportación a PDF se genera en backend.

---

## 4. Funciones Principales

### FP-01: Visualizar la agenda semanal por sucursal
- **Descripción:** Muestra los turnos de la semana seleccionada organizados por día y hora, con colores por área, indicación de profesional asignado o "sin asignar", y navegación entre semanas.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Vista clara y filtrable de la agenda semanal de la sucursal.

### FP-02: Filtrar la agenda
- **Descripción:** Permite aplicar filtros por sucursal, área, sala, profesional, servicio y estado del turno para reducir la vista a lo relevante.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Agenda filtrada que responde a la consulta del usuario.

### FP-03: Consultar el detalle de un turno
- **Descripción:** Al hacer clic en un turno, muestra el detalle con paciente, servicio, sala, profesional, fecha, hora, estado y accesos a acciones (asistencia, reprogramación, cancelación).
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Ficha de detalle del turno con información completa y acciones disponibles según permisos.

### FP-04: Exportar la agenda semanal
- **Descripción:** Permite exportar la agenda semanal de una sucursal a PDF, con los filtros aplicados.
- **Actor principal:** Administración (también Recepción [SUPUESTO A VALIDAR CON EL CLIENTE]).
- **Resultado esperado:** PDF descargable con la agenda de la semana y los filtros aplicados.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Vista semanal | El sistema debe mostrar la agenda semanal de la sucursal seleccionada, organizada por día de la semana y hora. | Alta | Recepción / Administración |
| RF-002 | Navegación entre semanas | El sistema debe permitir avanzar y retroceder entre semanas y volver a la semana actual. | Alta | Recepción / Administración |
| RF-003 | Colores por área | El sistema debe aplicar los colores configurados por área para diferenciar Salas Grupales e Intervención Terapéutica. | Alta | Sistema |
| RF-004 | Indicación "sin asignar" | El sistema debe mostrar visualmente los turnos sin profesional asignado. | Alta | Sistema |
| RF-005 | Filtros de agenda | El sistema debe permitir filtrar por sucursal, área, sala, profesional, servicio y estado del turno. | Alta | Recepción / Administración |
| RF-006 | Vista por profesional | El sistema debe ofrecer una vista de la agenda agrupada por profesional. | Media | Recepción / Administración |
| RF-007 | Vista por sala | El sistema debe ofrecer una vista de la agenda agrupada por sala. | Media | Recepción / Administración |
| RF-008 | Detalle del turno | El sistema debe mostrar el detalle de un turno al hacer clic: paciente, servicio, sala, profesional, fecha, hora, estado. | Alta | Recepción / Administración |
| RF-009 | Acciones desde el detalle | El sistema debe ofrecer desde el detalle las acciones de registrar asistencia, reprogramar y cancelar, según permisos. | Alta | Recepción / Administración |
| RF-010 | Exportación a PDF | El sistema debe permitir exportar la agenda semanal filtrada a PDF. | Media | Administración |
| RF-011 | Control de acceso por sucursal | El sistema debe restringir la agenda a las sucursales asignadas al usuario. | Alta | Sistema |
| RF-012 | Actualización de la vista | El sistema debe actualizar la vista cuando se registran cambios (asistencia, reprogramación, cancelación) sin recargar toda la página. | Media | Sistema |
| RF-013 | Turnos del día actual destacados | El sistema debe destacar visualmente el día actual en la vista semanal. | Baja | Sistema |
| RF-014 | Resumen de la semana | El sistema debe mostrar un resumen al pie de la agenda con la cantidad de turnos por estado en la semana. | Media | Recepción / Administración |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento
- **RNF-001:** La agenda semanal debe cargar en menos de 2 segundos con 10 usuarios concurrentes y hasta 500 turnos por semana por sucursal.
- **RNF-002:** Los filtros deben aplicarse en menos de 1 segundo sin recargar la página.

### 6.2 Seguridad y Privacidad
- **RNF-003:** Autenticación obligatoria; contraseñas con hash seguro.
- **RNF-004:** HTTPS; la agenda respeta el rol y las sucursales asignadas al usuario.
- **RNF-005:** La agenda muestra solo datos operativos del paciente (nombre, servicio, sala); no expone datos clínicos.

### 6.3 Disponibilidad
- **RNF-006:** Disponibilidad del 99% en horario de atención (lunes a sábado, 07:00 a 20:00).
- **RNF-007:** Respaldo automático diario; RTO 4 horas y RPO 24 horas.

### 6.4 Usabilidad
- **RNF-008:** La agenda debe ser legible en computadora y tablet, con celdas de turno que muestren la información clave y un tooltip con el detalle al pasar el cursor.

### 6.5 Escalabilidad
- **RNF-009:** El modelo debe soportar más sucursales, salas y profesionales sin degradar los tiempos del RNF-001.

### 6.6 Interoperabilidad
- **RNF-010:** Exportación a PDF; sin sincronización con calendarios externos en esta etapa.

### 6.7 Mantenibilidad
- **RNF-011:** Los colores por área son configurables desde el catálogo; migraciones versionadas.

### 6.8 Trazabilidad y Auditoría
- **RNF-012:** Las acciones ejecutadas desde la agenda (asistencia, reprogramación, cancelación) quedan registradas en bitácora por los submódulos correspondientes.

---

## 7. Reglas de Negocio

- **RN-001:** Agenda por sucursal — Cada sucursal tiene su propia agenda; los turnos no se mezclan entre sucursales.
- **RN-002:** Colores configurables — Los colores por área se cargan del catálogo; no hay colores fijos en el código.
- **RN-003:** Turnos sin asignar visibles — Los turnos sin profesional asignado deben ser visualmente distinguibles.
- **RN-004:** Acceso por sucursal — El usuario solo ve la agenda de las sucursales asignadas.
- **RN-005:** Semana de lunes a sábado — La agenda muestra de lunes a sábado; el domingo no se muestra por defecto (configurable) [SUPUESTO A VALIDAR CON EL CLIENTE].
- **RN-006:** Estados visibles — La agenda debe diferenciar los estados del turno (programado, asistió, faltó, cancelado).
- **RN-007:** Sin edición directa en la agenda — La agenda es de consulta; las acciones se ejecutan desde el detalle del turno.
- **RN-008:** Vista de impresión — La exportación a PDF debe incluir la misma información que la vista en pantalla y respetar los filtros aplicados.

---

## 8. Casos de Uso Principales

### CU-01: Consultar la agenda semanal de la sucursal

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Consultar la agenda semanal de la sucursal |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Usuario autenticado con al menos una sucursal asignada. |
| **Postcondiciones** | Vista de la agenda semanal cargada con los turnos de la sucursal seleccionada. |
| **Trigger** | Recepción abre el módulo de agenda. |

**Flujo Principal:**
1. Recepción abre el módulo de agenda.
2. El sistema carga por defecto la sucursal principal y la semana actual.
3. Muestra los turnos por día y hora, con colores por área y estados.
4. Recepción navega entre semanas o aplica filtros según necesidad.

**Flujos Alternativos:**
- **FA-01:** El usuario tiene varias sucursales — puede cambiar de sucursal desde un selector.
- **FA-02:** La semana no tiene turnos — la agenda muestra el mensaje "Sin turnos para esta semana".

**Flujos de Excepción:**
- **FE-01:** Sin sucursales asignadas — el sistema muestra error y sugiere contactar al Superadmin.

---

### CU-02: Filtrar la agenda por profesional

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Filtrar la agenda por profesional |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Agenda semanal cargada; profesional activo en la sucursal. |
| **Postcondiciones** | Agenda filtrada mostrando solo los turnos del profesional seleccionado. |
| **Trigger** | El usuario necesita ver la carga de un profesional específico. |

**Flujo Principal:**
1. El usuario selecciona el filtro "Profesional".
2. Elige un profesional de la lista.
3. El sistema aplica el filtro y actualiza la vista.
4. El usuario revisa los turnos del profesional en la semana.

**Flujos Alternativos:**
- **FA-01:** El usuario quiere ver turnos "sin asignar" — selecciona la opción "Sin asignar" en el filtro de profesional.

**Flujos de Excepción:**
- **FE-01:** El profesional no tiene turnos — el sistema muestra "Sin turnos para este profesional".

---

### CU-03: Consultar el detalle de un turno desde la agenda

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Consultar el detalle de un turno desde la agenda |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Agenda semanal cargada. |
| **Postcondiciones** | Detalle del turno visible con acciones disponibles según permisos. |
| **Trigger** | Recepción hace clic en un turno. |

**Flujo Principal:**
1. Recepción hace clic en un turno de la agenda.
2. El sistema abre el panel de detalle con paciente, servicio, sala, profesional, fecha, hora y estado.
3. El sistema muestra las acciones disponibles según rol: registrar asistencia, reprogramar, cancelar.
4. Recepción selecciona una acción o cierra el detalle.

**Flujos Alternativos:**
- **FA-01:** El turno ya tiene asistencia registrada — el sistema muestra la acción de "Editar asistencia" si el rol lo permite.

**Flujos de Excepción:**
- **FE-01:** El turno pertenece a una sucursal no asignada al usuario — el sistema bloquea el acceso al detalle.

---

## 9. Criterios de Aceptación

### CA-001: Carga de la agenda semanal
- **Given** un usuario con sucursal asignada y turnos en la semana,
- **When** abre la agenda,
- **Then** el sistema muestra los turnos de la semana actual en menos de 2 segundos.

### CA-002: Navegación entre semanas
- **Given** la agenda en la semana actual,
- **When** el usuario presiona "Semana siguiente",
- **Then** la agenda muestra los turnos de la semana siguiente.

### CA-003: Colores por área
- **Given** un turno de Intervención Terapéutica y un turno de Sala Grupal,
- **When** se muestran en la agenda,
- **Then** cada uno se pinta con el color configurado para su área.

### CA-004: Indicación "sin asignar"
- **Given** un turno sin profesional,
- **When** se muestra en la agenda,
- **Then** el turno tiene una marca visual que indica "Sin asignar".

### CA-005: Filtro por profesional
- **Given** varios turnos de distintos profesionales,
- **When** el usuario filtra por un profesional,
- **Then** la agenda muestra solo los turnos de ese profesional.

### CA-006: Detalle del turno
- **Given** un turno visible en la agenda,
- **When** el usuario hace clic en él,
- **Then** el sistema abre el panel de detalle con los datos completos.

### CA-007: Acceso por sucursal
- **Given** un usuario asignado solo a la Sucursal Centro,
- **When** intenta ver la agenda de la Sucursal Norte,
- **Then** el sistema no muestra la sucursal en el selector.

### CA-008: Exportación a PDF
- **Given** una agenda con filtros aplicados,
- **When** Administración exporta a PDF,
- **Then** el sistema descarga el PDF con los turnos filtrados y la información correspondiente.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Lentitud en la carga con muchas sucursales y turnos | Media | Alto | Alto | Índices en base de datos, caché por sucursal y semana, paginación si es necesario. |
| RT-002 | Confusión de colores si el catálogo no se parametriza bien | Media | Medio | Medio | Validar configuración de colores con Administración antes de producción. |
| RT-003 | Dependencia de conectividad para consultar la agenda | Media | Alto | Alto | Vista imprimible de respaldo y plan de contingencia en papel. |
| RT-004 | Baja adopción de la agenda digital | Media | Alto | Alto | Capacitación y soporte en sitio la primera semana. |
| RT-005 | Cambios de alcance durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa y aprobación formal. |
| RT-006 | Dependencia de una sola persona para soporte | Media | Alto | Alto | Documentación técnica y transferencia de conocimiento. |

---

**Fin del documento**