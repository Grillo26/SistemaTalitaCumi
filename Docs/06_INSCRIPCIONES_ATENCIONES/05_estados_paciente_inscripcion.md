# Estados del Paciente y de la Inscripción
**Módulo:** Inscripciones y Atenciones
**Etapa:** 1
**Versión:** 1.0
**Fecha:** Noviembre de 2025
**Estado:** Borrador

---

## 1. Introducción

Este submódulo define y gestiona los estados del paciente y de sus inscripciones (a salas grupales y a paquetes de sesiones individuales), junto con las transiciones permitidas entre ellos, los permisos por rol y el registro de cada cambio. Su propósito es que el sistema refleje con fidelidad la situación real de cada niño y de cada servicio contratado, sin depender de anotaciones en cuadernos ni de la memoria del personal.

Hoy el centro maneja tres estados de paciente (activo, en pausa, retirado) de manera informal, y las inscripciones no tienen un ciclo de vida claro: se anotan, se olvidan, se superponen y no se sabe cuáles están vigentes, cuáles vencidas y cuáles retiradas. Este submódulo formaliza ambos ciclos de vida y los conecta con las reglas de negocio del sistema: un paciente en pausa o retirado no puede inscribirse; una inscripción retirada no permite nuevas asistencias; una inscripción vencida debe renovarse antes de registrar turnos.

El valor principal es la coherencia operativa: recepción y administración ven siempre el estado real, los cambios quedan en bitácora con motivo y usuario, y los demás módulos (Agenda y Turnos, Pagos y Caja, Reportes y Alertas) reaccionan de forma consistente. Se integra con Pacientes y Tutores, Inscripción a Salas Grupales, Paquetes de Sesiones Individuales, Vigencia y Renovación, Agenda y Turnos, Pagos y Caja, y Reportes y Alertas.

---

## 2. Alcance

**Incluido en este módulo:**
- Definición y gestión de los estados del paciente: activo, en pausa, retirado.
- Definición y gestión de los estados de la inscripción: activa, en pausa, vencida, renovada, retirada.
- Transiciones permitidas entre estados y validaciones asociadas.
- Registro de cada cambio de estado con usuario, fecha, estado anterior, estado nuevo y motivo.
- Permisos por rol para ejecutar cada transición.
- Efectos de cada estado en los demás módulos (bloqueo de inscripción, bloqueo de asistencias, alertas).
- Consulta y listado de pacientes e inscripciones por estado.
- Reportes de pacientes e inscripciones por estado.

**Excluido de este módulo:**
- Recuperación de sesiones o descuentos por estado (las sesiones vencen y se pierden; el estado no cambia esa regla).
- Alertas automáticas de abandono (fuera de alcance del proyecto).
- Notificaciones automáticas por WhatsApp o correo.
- Eliminación física de pacientes o inscripciones (no se borran; se cambian de estado).
- Plan de cuotas (etapa 2).
- Facturación.

**Actores / Roles involucrados:**
- **Recepción:** consulta estados y puede solicitar cambios; ejecuta pausas y reactivaciones de inscripciones de su sucursal [SUPUESTO A VALIDAR CON EL CLIENTE].
- **Administración:** ejecuta todos los cambios de estado de paciente e inscripción de su sucursal, con motivo obligatorio en retiros.
- **Superadmin:** acceso total, configuración y ejecución de cualquier transición.
- Los profesionales y los padres NO usan el sistema.

**Sucursales aplicables:**
- El estado del paciente es global (no por sucursal); el paciente es único para todo el centro.
- El estado de la inscripción pertenece a la sucursal de la inscripción.
- Cada usuario solo puede cambiar estados de las sucursales asignadas.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa
Reemplaza el manejo informal de estados por un flujo claro con reglas y permisos. Recepción sabe de inmediato si un paciente puede inscribirse, si una inscripción está activa o vencida, y qué acción corresponde. Administración obtiene reportes por estado sin revisar cuadernos, y cada cambio queda en bitácora con motivo.

### 3.2 Justificación en la Atención al Niño y su Familia
Evita situaciones confusas para la familia: no se registran asistencias sobre inscripciones retiradas o vencidas, no se inscribe a un paciente retirado sin reactivarlo, y la pausa permite suspender temporalmente sin perder el historial. La familia recibe información consistente sobre el estado del servicio contratado.

### 3.3 Justificación Normativa
- Constitución Política del Estado (protección de la niñez y privacidad) [verificar vigencia].
- Ley 548 Código Niña, Niño y Adolescente [verificar vigencia].
- Ley 1080 de Ciudadanía Digital (resguardo de información digital) [verificar vigencia].
- No existe una norma específica sobre estados de inscripción en servicios de desarrollo infantil; la regla es operativa y contractual, y se documenta como regla de negocio interna.

### 3.4 Justificación Tecnológica
Los estados y transiciones se modelan como una máquina de estados validada en backend, lo que evita combinaciones inválidas (por ejemplo, retirar una inscripción ya vencida sin motivo, o reactivar un paciente sin autorización). La bitácora y los reportes se alimentan automáticamente.

---

## 4. Funciones Principales

### FP-01: Gestionar el estado del paciente
- **Descripción:** Permite cambiar el estado del paciente entre activo, en pausa y retirado, con validaciones según el estado actual y registro en bitácora.
- **Actor principal:** Administración (todos los cambios); Recepción (consulta) [SUPUESTO A VALIDAR CON EL CLIENTE].
- **Resultado esperado:** Paciente con estado actualizado, motivo registrado cuando corresponde y efecto inmediato en los demás módulos (por ejemplo, bloqueo de nuevas inscripciones si está retirado).

### FP-02: Gestionar el estado de la inscripción
- **Descripción:** Permite cambiar el estado de una inscripción (sala grupal o paquete) entre activa, en pausa, vencida, renovada y retirada, respetando las transiciones permitidas.
- **Actor principal:** Administración (todas las transiciones); Recepción (pausa y reactivación de su sucursal) [SUPUESTO A VALIDAR CON EL CLIENTE].
- **Resultado esperado:** Inscripción con estado actualizado, motivo registrado en retiros y pausas, y efecto inmediato en Agenda y Turnos y en Reportes.

### FP-03: Consultar y listar por estado
- **Descripción:** Permite filtrar y listar pacientes e inscripciones por estado, sucursal, servicio y período, con vista de detalle.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Listados filtrados y fichas de detalle con el estado actual y el historial de cambios.

### FP-04: Reportar estados y transiciones
- **Descripción:** Genera reportes de pacientes e inscripciones por estado (activos, en pausa, retirados; activas, vencidas, renovadas, retiradas) con exportación a PDF y Excel.
- **Actor principal:** Administración.
- **Resultado esperado:** Reportes descargables con filtros por sucursal, período y servicio, y trazabilidad de las transiciones.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Definir estados de paciente | El sistema debe manejar exactamente tres estados de paciente: activo, en pausa y retirado, sin permitir estados adicionales. | Alta | Sistema |
| RF-002 | Definir estados de inscripción | El sistema debe manejar los estados de inscripción: activa, en pausa, vencida, renovada y retirada. | Alta | Sistema |
| RF-003 | Cambio de estado de paciente | El sistema debe permitir cambiar el estado del paciente respetando las transiciones permitidas (activo ↔ en pausa; activo → retirado; en pausa → retirado; retirado → activo). | Alta | Administración |
| RF-004 | Cambio de estado de inscripción | El sistema debe permitir cambiar el estado de la inscripción respetando las transiciones permitidas (activa ↔ en pausa; activa → vencida; activa → renovada; activa → retirada; en pausa → retirada; en pausa → activa; vencida → renovada). | Alta | Administración |
| RF-005 | Motivo obligatorio en retiro | El sistema debe exigir un motivo al pasar un paciente o una inscripción a estado "Retirado". | Alta | Administración |
| RF-006 | Motivo obligatorio en pausa | El sistema debe exigir un motivo al pasar un paciente o una inscripción a estado "En pausa". | Media | Administración / Recepción |
| RF-007 | Bloqueo de inscripción por estado del paciente | El sistema debe bloquear la creación de nuevas inscripciones para pacientes en estado "En pausa" o "Retirado". | Alta | Sistema |
| RF-008 | Bloqueo de asistencias por estado de la inscripción | El sistema debe bloquear el registro de asistencias sobre inscripciones en estado "En pausa", "Vencida", "Renovada" o "Retirada". | Alta | Sistema |
| RF-009 | Bitácora de transiciones | El sistema debe registrar en bitácora cada transición con usuario, fecha, estado anterior, estado nuevo y motivo. | Alta | Sistema |
| RF-010 | Historial en ficha | El sistema debe mostrar en la ficha del paciente y de la inscripción el historial de cambios de estado con fecha, usuario y motivo. | Alta | Recepción / Administración |
| RF-011 | Listado por estado | El sistema debe ofrecer listado de pacientes e inscripciones con filtros por estado, sucursal, servicio y período. | Alta | Recepción / Administración |
| RF-012 | Reporte por estado | El sistema debe generar reportes de pacientes e inscripciones por estado con exportación a PDF y Excel. | Media | Administración |
| RF-013 | Permisos por rol | El sistema debe validar que solo los roles autorizados ejecuten cada transición (por ejemplo, solo Administración puede retirar). | Alta | Sistema |
| RF-014 | Control de acceso por sucursal | El sistema debe restringir los cambios de estado de inscripción a las sucursales asignadas al usuario. | Alta | Sistema |
| RF-015 | Efecto en alertas | El sistema debe excluir de las alertas de próximo pago y de vencimiento a las inscripciones retiradas y a los pacientes retirados. | Alta | Sistema |
| RF-016 | Reactivación con verificación de pago | Al reactivar una inscripción, el sistema debe verificar el estado de pago y alertar si hay saldo pendiente. | Media | Sistema |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento
- **RNF-001:** El cambio de estado debe completarse en menos de 1 segundo.
- **RNF-002:** El listado por estado debe cargar en menos de 2 segundos con hasta 5.000 registros por sucursal y 10 usuarios concurrentes.

### 6.2 Seguridad y Privacidad
- **RNF-003:** Autenticación obligatoria; contraseñas con hash seguro (bcrypt o Argon2).
- **RNF-004:** HTTPS; los cambios de estado respetan rol y sucursal; los motivos no exponen datos clínicos.

### 6.3 Disponibilidad
- **RNF-005:** Disponibilidad del 99% en horario de atención (lunes a sábado, 07:00 a 20:00).
- **RNF-006:** Respaldo automático diario; RTO 4 horas y RPO 24 horas.

### 6.4 Usabilidad
- **RNF-007:** El cambio de estado debe poder realizarse desde la ficha del paciente o de la inscripción en un máximo de 3 clics, con motivo sugerido por lista y texto libre.

### 6.5 Escalabilidad
- **RNF-008:** El modelo de estados debe soportar más sucursales y entidades sin cambios de código, manteniendo los tiempos de respuesta del RNF-002.

### 6.6 Interoperabilidad
- **RNF-009:** Exportación a PDF y Excel; sin integraciones externas en esta etapa.

### 6.7 Mantenibilidad
- **RNF-010:** La máquina de estados y sus transiciones permitidas deben estar centralizadas en el backend, con pruebas automatizadas; migraciones versionadas.

### 6.8 Trazabilidad y Auditoría
- **RNF-011:** Toda transición queda en bitácora con usuario, fecha, estado anterior, estado nuevo y motivo; el historial no se elimina.

---

## 7. Reglas de Negocio

- **RN-001:** Tres estados de paciente — El paciente solo puede estar en uno de tres estados: activo, en pausa o retirado. No existen estados intermedios.
- **RN-002:** Transiciones de paciente permitidas — Activo ↔ En pausa; Activo → Retirado; En pausa → Retirado; Retirado → Activo (reactivación).
- **RN-003:** Transiciones de inscripción permitidas — Activa ↔ En pausa; Activa → Vencida; Activa → Renovada; Activa → Retirada; En pausa → Retirada; En pausa → Activa; Vencida → Renovada.
- **RN-004:** Motivo obligatorio en retiro y pausa — Todo retiro y toda pausa deben registrarse con motivo (lista cerrada + texto libre).
- **RN-005:** Solo Administración retira — Solo el rol Administración (y Superadmin) puede ejecutar retiros de pacientes o inscripciones.
- **RN-006:** Paciente en pausa o retirado no se inscribe — No se pueden crear nuevas inscripciones para un paciente en pausa o retirado; primero debe reactivarse.
- **RN-007:** Inscripción no activa no permite asistencias — No se pueden registrar asistencias sobre inscripciones en pausa, vencidas, renovadas o retiradas.
- **RN-008:** Cambio de estado no recupera sesiones — El cambio de estado no altera el saldo de sesiones ni recupera sesiones perdidas; solo habilita o bloquea la operación.
- **RN-009:** Retiro excluye de alertas — Las inscripciones retiradas y los pacientes retirados no aparecen en alertas de próximo pago ni de vencimiento.
- **RN-010:** Reactivación con verificación de pago — Al reactivar una inscripción, el sistema verifica el estado de pago y alerta si hay saldo pendiente, sin bloquear la operación.
- **RN-011:** Historial preservado — Los cambios de estado no se eliminan; el historial completo queda disponible en la ficha y en reportes.
- **RN-012:** Acceso por sucursal — Los cambios de estado de inscripción solo pueden ejecutarse sobre sucursales asignadas al usuario.

---

## 8. Casos de Uso Principales

### CU-01: Pausar y reactivar la inscripción de un paciente

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Pausar y reactivar la inscripción de un paciente |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Inscripción en estado "Activa"; paciente activo. |
| **Postcondiciones** | Inscripción en estado "En pausa" con motivo, o de vuelta a "Activa" con verificación de pago. |
| **Trigger** | La familia solicita suspender temporalmente el servicio (viaje, enfermedad, motivos personales). |

**Flujo Principal (pausa):**
1. Recepción busca la inscripción activa.
2. Selecciona "Pausar".
3. El sistema solicita motivo (lista cerrada + texto libre).
4. Recepción confirma.
5. El sistema cambia el estado a "En pausa", registra en bitácora y bloquea nuevas asistencias.

**Flujo Principal (reactivación):**
1. Recepción busca la inscripción en pausa.
2. Selecciona "Reactivar".
3. El sistema verifica el estado de pago y muestra alerta si hay saldo pendiente.
4. Recepción confirma.
5. El sistema cambia el estado a "Activa" y registra en bitácora.

**Flujos Alternativos:**
- **FA-01:** La pausa se prolonga y la familia decide retirarse — se ejecuta el retiro (CU-03).

**Flujos de Excepción:**
- **FE-01:** Hay turnos futuros programados — el sistema advierte y sugiere cancelarlos antes de pausar.
- **FE-02:** La inscripción está vencida — el sistema bloquea la pausa y sugiere renovar primero.

---

### CU-02: Retirar al paciente del centro

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Retirar al paciente del centro |
| **Actor Principal** | Administración |
| **Actores Secundarios** | Recepción (notifica), Sistema |
| **Precondiciones** | Paciente activo o en pausa; familia comunicó el retiro definitivo. |
| **Postcondiciones** | Paciente en estado "Retirado" con motivo y fecha; todas sus inscripciones activas o en pausa quedan retiradas; alertas excluidas. |
| **Trigger** | La familia comunica el retiro definitivo del niño del centro. |

**Flujo Principal:**
1. Administración busca al paciente.
2. Selecciona "Retirar paciente".
3. El sistema muestra las inscripciones activas y en pausa del paciente.
4. Administración confirma el retiro y registra motivo (lista cerrada + texto libre).
5. El sistema cambia el estado del paciente a "Retirado" y de sus inscripciones a "Retiradas", registra en bitácora y excluye al paciente de alertas.

**Flujos Alternativos:**
- **FA-01:** El retiro es solo de una inscripción, no del paciente — Administración retira solo la inscripción afectada y el paciente sigue activo.

**Flujos de Excepción:**
- **FE-01:** Hay turnos futuros programados — el sistema advierte y sugiere cancelarlos antes del retiro.
- **FE-02:** Hay pagos pendientes — el sistema muestra alerta pero no bloquea el retiro (el saldo queda registrado).

---

### CU-03: Retirar una inscripción específica

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Retirar una inscripción específica |
| **Actor Principal** | Administración |
| **Actores Secundarios** | Recepción (notifica), Sistema |
| **Precondiciones** | Inscripción activa o en pausa; familia comunicó el retiro de ese servicio. |
| **Postcondiciones** | Inscripción en estado "Retirada" con motivo y fecha; el paciente puede seguir activo con otras inscripciones. |
| **Trigger** | La familia decide dejar un servicio específico (por ejemplo, sala grupal) pero continuar con otro (por ejemplo, terapia individual). |

**Flujo Principal:**
1. Administración busca la inscripción.
2. Selecciona "Retirar inscripción".
3. El sistema solicita motivo (lista cerrada + texto libre) y confirma fecha.
4. Administración confirma.
5. El sistema cambia el estado a "Retirada", registra en bitácora y bloquea nuevas asistencias sobre esa inscripción.

**Flujos Alternativos:**
- **FA-01:** El retiro es temporal — se usa la pausa (CU-01) en lugar del retiro.

**Flujos de Excepción:**
- **FE-01:** Hay turnos futuros programados — el sistema advierte y sugiere cancelarlos.

---

## 9. Criterios de Aceptación

### CA-001: Pausa con motivo
- **Given** una inscripción activa,
- **When** Recepción la pausa con motivo "Viaje familiar",
- **Then** el estado pasa a "En pausa", se registra en bitácora con fecha y usuario, y se bloquean nuevas asistencias.

### CA-002: Reactivación con verificación de pago
- **Given** una inscripción en pausa con saldo pendiente,
- **When** Recepción la reactiva,
- **Then** el sistema muestra alerta de saldo pendiente pero permite la reactivación y registra en bitácora.

### CA-003: Bloqueo de inscripción a paciente retirado
- **Given** un paciente en estado "Retirado",
- **When** Recepción intenta crear una nueva inscripción,
- **Then** el sistema bloquea y muestra "Debe reactivar al paciente antes de inscribir".

### CA-004: Bloqueo de asistencias en inscripción no activa
- **Given** una inscripción en pausa, vencida, renovada o retirada,
- **When** Recepción intenta registrar una asistencia,
- **Then** el sistema bloquea y muestra "La inscripción no está activa".

### CA-005: Retiro de paciente con motivo obligatorio
- **Given** un paciente activo,
- **When** Administración intenta retirarlo sin ingresar motivo,
- **Then** el sistema bloquea y exige el motivo.

### CA-006: Retiro de paciente arrastra inscripciones
- **Given** un paciente con dos inscripciones activas,
- **When** Administración retira al paciente,
- **Then** el paciente y ambas inscripciones pasan a "Retirados" con el mismo motivo y fecha.

### CA-007: Retiro de inscripción individual
- **Given** un paciente con sala grupal y terapia individual activas,
- **When** Administración retira solo la inscripción de sala grupal,
- **Then** esa inscripción pasa a "Retirada" y el paciente sigue activo con la terapia individual.

### CA-008: Permisos por rol
- **Given** un usuario con rol Recepción,
- **When** intenta retirar un paciente,
- **Then** el sistema bloquea y muestra "Acción no permitida para su rol".

### CA-009: Exclusión de alertas
- **Given** un paciente retirado con inscripciones retiradas,
- **When** se genera el panel de alertas de próximo pago,
- **Then** el paciente y sus inscripciones no aparecen en el panel.

### CA-010: Historial de cambios
- **Given** una inscripción con varios cambios de estado,
- **When** Recepción consulta la ficha,
- **Then** el sistema muestra el historial completo con fecha, usuario, estado anterior, estado nuevo y motivo.

### CA-011: Filtro por estado
- **Given** varios pacientes cargados en distintos estados,
- **When** Administración filtra por estado "En pausa",
- **Then** el sistema muestra solo los pacientes en pausa.

### CA-012: Filtro por sucursal
- **Given** un usuario asignado solo a la Sucursal Centro,
- **When** consulta inscripciones por estado,
- **Then** solo ve inscripciones de la Sucursal Centro.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Estados inconsistentes por cambios manuales en base de datos | Media | Alto | Alto | Restringir acceso directo a BD; todas las transiciones pasan por la API con validación de máquina de estados. |
| RT-002 | Confusión entre pausa y retiro por parte del personal | Media | Alto | Alto | Etiquetas claras en la interfaz, motivos sugeridos y manual breve con ejemplos. |
| RT-003 | Reactivación de paciente sin verificar pagos | Media | Medio | Medio | Alerta de saldo pendiente obligatoria al reactivar (RN-010) y registro en bitácora. |
| RT-004 | Baja adopción por Recepción | Media | Alto | Alto | Capacitación y soporte en sitio la primera semana. |
| RT-005 | Cambios de alcance durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa y aprobación formal del cliente. |
| RT-006 | Dependencia de una sola persona para soporte | Media | Alto | Alto | Documentación técnica y transferencia de conocimiento. |
| RT-007 | Datos históricos de estados migrados desde cuadernos | Alta | Medio | Alto | Proceso de migración con validación y depuración previa con Administración; marcar como [SUPUESTO A VALIDAR CON EL CLIENTE] los estados iniciales. |

---

**Fin del documento**