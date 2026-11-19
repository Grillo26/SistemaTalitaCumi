# Asignación opcional de profesionales a turnos
**Módulo:** Profesionales
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo regula cómo se asigna —de forma **manual y opcional**— un profesional a un turno de la agenda, sea individual o de sala grupal, y cómo se refleja esa asignación en la agenda, en los diagnósticos integrales y en los reportes. El contexto es explícito: el sistema **no asigna profesionales automáticamente** y el profesional de un turno es opcional; la agenda debe poder mostrar «sin asignar» cuando no se elige a nadie.

Hoy la asignación se resuelve en la pizarra de turnos y en la memoria del personal, sin registro de quién atiende cada turno. Este submódulo reemplaza esa práctica por una asignación trazable, validada contra la matriz profesional–especialidad–sucursal y sujeta a reglas de negocio que evitan solapamientos y asignaciones inválidas. Los diagnósticos integrales que requieren varios profesionales se apoyan en este submódulo para registrar a cada participante.

El valor que aporta es la trazabilidad de la atención y la coherencia operativa: saber quién atiende cada turno, evitar dobles asignaciones en el mismo horario y permitir que la agenda funcione incluso cuando no hay profesional asignado. Se integra con el Directorio de profesionales, Especialidades y sucursales de trabajo, Agenda y turnos, Diagnóstico con varios profesionales, Registro de asistencia y Reportes por profesional.

---

## 2. Alcance

### Incluido en este módulo

- Asignación manual de un profesional a un turno individual o de sala grupal, en estado «programado».
- Posibilidad de dejar el turno **sin asignar** («sin asignar» visible en la agenda).
- Validación de la asignación contra la matriz profesional–especialidad–sucursal (submódulo 02).
- Validación de solapamiento: un profesional no puede estar asignado a dos turnos en el mismo horario.
- Reasignación y desasignación de profesional en un turno, con bitácora.
- Registro de **varios profesionales por diagnóstico integral**, cada uno con su rol dentro del diagnóstico.
- Visualización de la asignación en la agenda semanal y en el detalle del turno.
- Registro en bitácora de altas, cambios y bajas de asignación.
- Exposición de la asignación a los módulos de asistencia y reportes.

### Excluido de este módulo

- **Asignación automática de profesionales:** fuera de alcance por el contexto.
- **Optimización de agenda por disponibilidad:** fuera de alcance en Etapa 1.
- **Cálculo de carga laboral y nómina:** fuera del alcance del sistema.
- **Registro de asistencia del profesional (solo se registra el estado del turno):** corresponde al módulo 07.
- **Sustitución automática por ausencia del profesional:** fuera de alcance; la reasignación es manual.
- **Asignación de profesionales a entrevistas y diagnósticos como flujo completo:** el flujo pertenece al módulo 06; aquí solo se cubre la asignación múltiple del diagnóstico integral.
- **Integraciones externas:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Puede asignar, reasignar y desasignar profesionales en turnos y diagnósticos. |
| Administración | Puede asignar y reasignar profesionales en turnos de sus sucursales (según confirmación del cliente). |
| Recepción | Asigna profesionales a turnos al agendar o reprogramar; puede dejar el turno «sin asignar». |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **La asignación es específica de la sucursal del turno:** el profesional debe trabajar en esa sucursal según la matriz.
- **La asignación múltiple en diagnósticos integrales** se realiza en la sucursal del diagnóstico.
- **Los datos de menores y de salud** quedan protegidos por rol y sucursal.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que Recepción puede asignar profesionales a turnos (no solo Superadmin y Administración) y que la asignación es siempre manual. También debe confirmarse si la reasignación requiere motivo obligatorio cuando el turno ya tenía un profesional asignado.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

La agenda debe poder reflejar la realidad del centro: a veces hay un profesional definido para un turno y a veces no. Este submódulo permite ambos casos sin bloquear la operación, valida que la asignación sea coherente con la especialidad y la sucursal, y evita solapamientos que hoy se detectan a mano en la pizarra. La reasignación queda registrada, lo que facilita resolver reclamos y reorganizar la agenda ante imprevistos.

### 3.2 Justificación en la Atención al Niño y su Familia

Saber quién atiende cada turno refuerza la continuidad de la atención y permite informar a la familia con precisión. En diagnósticos integrales, la asignación de varios profesionales garantiza que el niño sea evaluado por quienes corresponden a su necesidad, con trazabilidad de cada participante.

### 3.3 Justificación Normativa

- **Ley 3131, de Ejercicio Profesional Médico:** identificación del profesional que interviene y conservación de registros [verificar vigencia].
- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de información de menores [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La asignación trazable contribuye a que cada atención quede asociada al profesional correcto y a que el secreto profesional se aplique con claridad.

### 3.4 Justificación Tecnológica

Frente a la pizarra, la asignación en sistema aporta: validación automática contra la matriz profesional–especialidad–sucursal; detección de solapamientos en backend; bitácora de cambios; y visibilidad en la agenda. Se reduce el riesgo de asignaciones inválidas y se habilita el reporte por profesional sin trabajo manual adicional.

---

## 4. Funciones Principales

### FP-01: Asignar profesional a un turno
- **Descripción:** Permite seleccionar un profesional para un turno individual o de sala grupal, validando matriz y solapamiento, y permitiendo dejar el turno «sin asignar».
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Turno con profesional asignado o «sin asignar», visible en la agenda y con bitácora.

### FP-02: Reasignar y desasignar profesional
- **Descripción:** Permite cambiar el profesional de un turno o quitarlo, registrando motivo y valores anteriores y nuevos en bitácora.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Turno actualizado con historial de asignaciones.

### FP-03: Asignar varios profesionales a un diagnóstico integral
- **Descripción:** Permite registrar múltiples profesionales en un diagnóstico integral, cada uno con su rol y especialidad, validando matriz y solapamiento.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Diagnóstico integral con todos sus participantes registrados y trazables.

### FP-04: Validar asignación contra matriz y solapamiento
- **Descripción:** Expone el servicio de validación que usan agenda y diagnósticos para rechazar asignaciones inválidas por especialidad, sucursal o solapamiento horario.
- **Actor principal:** Sistema.
- **Resultado esperado:** Asignaciones consistentes en todo el sistema y reportes confiables por profesional.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Asignar profesional a turno | El sistema debe permitir asignar un profesional activo a un turno programado, guardando la relación con bitácora. | Alta | Recepción, Administración, Superadmin |
| RF-002 | Permitir turno sin asignar | El sistema debe permitir guardar un turno sin profesional asignado y mostrar «sin asignar» en la agenda. | Alta | Recepción, Administración, Superadmin |
| RF-003 | Validar matriz profesional–especialidad–sucursal | El sistema debe rechazar la asignación si el profesional no cubre la especialidad requerida o no trabaja en la sucursal del turno. | Alta | Sistema |
| RF-004 | Validar solapamiento | El sistema debe rechazar la asignación si el profesional ya está asignado a otro turno en el mismo horario y sucursal o en otra sucursal. | Alta | Sistema |
| RF-005 | Reasignar profesional | El sistema debe permitir cambiar el profesional de un turno, registrando motivo y valores anteriores y nuevos. | Alta | Recepción, Administración, Superadmin |
| RF-006 | Desasignar profesional | El sistema debe permitir quitar el profesional de un turno, dejándolo «sin asignar» y registrando el cambio en bitácora. | Alta | Recepción, Administración, Superadmin |
| RF-007 | Asignar varios profesionales a diagnóstico | El sistema debe permitir registrar múltiples profesionales en un diagnóstico integral, cada uno con rol y especialidad. | Alta | Recepción, Administración, Superadmin |
| RF-008 | Validar rol en diagnóstico | El sistema debe validar que cada profesional del diagnóstico cubra una especialidad requerida por el tipo de diagnóstico. | Alta | Sistema |
| RF-009 | Visualizar asignación en agenda | La agenda debe mostrar el profesional asignado en cada turno y marcar «sin asignar» cuando corresponda. | Alta | Recepción, Administración, Superadmin |
| RF-010 | Registrar bitácora | El sistema debe registrar en bitácora asignaciones, reasignaciones y desasignaciones con usuario, fecha, hora y motivo. | Alta | Sistema |
| RF-011 | Bloquear asignación a profesional inactivo | El sistema debe rechazar la asignación de un profesional inactivo a nuevos turnos o diagnósticos. | Alta | Sistema |
| RF-012 | Exponer asignación a otros módulos | El sistema debe exponer la asignación vía API para la agenda, el registro de asistencia y los reportes. | Alta | Sistema |
| RF-013 | Restringir operaciones según estado del turno | El sistema debe impedir la asignación de profesional a turnos en estado asistió, faltó o cancelado; solo permite reasignar en estado programado. | Alta | Sistema |
| RF-014 | Restringir acceso por rol y sucursal | El sistema debe impedir que un usuario asigne profesionales en sucursales no asignadas a su alcance. | Alta | Sistema |
| RF-015 | Exportar asignaciones | El sistema debe permitir exportar el listado de asignaciones por profesional, sucursal y período a PDF y Excel. | Baja | Superadmin, Administración |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que la reasignación requiere motivo obligatorio cuando el turno ya tenía un profesional asignado. También debe confirmarse si la asignación múltiple en diagnósticos requiere que todos los profesionales trabajen en la sucursal del diagnóstico o si se admite alguno de otra sede en casos excepcionales.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La asignación de un profesional a un turno debe guardarse en menos de 1 segundo, incluida la validación de matriz y solapamiento.
- **RNF-002:** La agenda semanal con asignaciones debe cargar en menos de 2 segundos con 10 usuarios concurrentes.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen sesión autenticada.
- **RNF-004:** La validación de matriz y solapamiento debe ejecutarse en backend.
- **RNF-005:** Los datos de menores asociados a turnos y diagnósticos deben tratarse conforme a la política de privacidad y a la normativa aplicable.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir las asignaciones y su histórico.

### 6.4 Usabilidad

- **RNF-008:** La agenda debe permitir asignar o reasignar un profesional en pocos clics y mostrar claramente «sin asignar».
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en profesionales, turnos y diagnósticos sin cambios de arquitectura.

### 6.6 Interoperabilidad

- **RNF-011:** La asignación debe exponerse vía API REST a agenda, asistencia y reportes, y exportarse a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** Las reglas de validación deben centralizarse en un servicio reutilizable por agenda y diagnósticos.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda asignación, reasignación y desasignación debe quedar en bitácora con usuario, fecha, hora, motivo y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Asignación manual — El sistema no asigna profesionales automáticamente; toda asignación es manual.
- **RN-002:** Asignación opcional — El profesional de un turno es opcional; si no se asigna, la agenda muestra «sin asignar».
- **RN-003:** Validación de matriz — Solo se puede asignar un profesional activo que cubra la especialidad requerida y trabaje en la sucursal del turno.
- **RN-004:** Sin solapamiento — Un profesional no puede estar asignado a dos turnos en el mismo horario, aun en sucursales distintas.
- **RN-005:** Estado del turno — Solo se puede asignar o reasignar en turnos en estado programado; no en asistió, faltó o cancelado.
- **RN-006:** Reasignación con motivo — La reasignación de un turno ya asignado requiere motivo registrado en bitácora.
- **RN-007:** Diagnóstico integral — Un diagnóstico integral puede tener varios profesionales, cada uno con su rol y especialidad.
- **RN-008:** Validación de especialidad en diagnóstico — Cada profesional del diagnóstico debe cubrir al menos una especialidad requerida por el tipo de diagnóstico.
- **RN-009:** Trazabilidad — Toda asignación, reasignación y desasignación queda en bitácora con valores anteriores y nuevos.
- **RN-010:** Profesional inactivo — Un profesional inactivo no puede asignarse a nuevos turnos ni diagnósticos; su histórico se conserva.
- **RN-011:** Alcance por rol y sucursal — La asignación se permite solo en sucursales del alcance del usuario; Superadmin puede operar en todas.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-004 (sin solapamiento aun en sucursales distintas) debe confirmarse: si el cliente admite que un profesional pueda tener turnos en dos sucursales en el mismo horario (por ejemplo, porque no se traslada), la regla debe ajustarse. También debe confirmarse la RN-008.

---

## 8. Casos de Uso Principales

### CU-01: Asignar profesional a un turno

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Asignar profesional a un turno |
| **Actor Principal** | Recepción / Administración / Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Turno en estado programado; profesional activo que cubre especialidad y sucursal |
| **Postcondiciones** | Turno con profesional asignado, visible en la agenda y con bitácora |
| **Trigger** | El usuario abre el turno y selecciona “Asignar profesional” |

**Flujo Principal:**
1. El usuario abre el turno en la agenda.
2. El sistema muestra los profesionales elegibles según matriz y sucursal.
3. El usuario selecciona un profesional.
4. El sistema valida matriz y solapamiento.
5. El sistema guarda la asignación y registra en bitácora.
6. La agenda refleja el profesional asignado.

**Flujos Alternativos:**
- **FA-01:** El usuario puede guardar el turno «sin asignar» y completarlo más tarde.

**Flujos de Excepción:**
- **FE-01:** El profesional no cubre la especialidad → el sistema rechaza e informa.
- **FE-02:** El profesional no trabaja en la sucursal → el sistema rechaza e informa.
- **FE-03:** El profesional tiene otro turno en el mismo horario → el sistema rechaza e informa el solapamiento.

### CU-02: Reasignar o desasignar profesional

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Reasignar o desasignar profesional |
| **Actor Principal** | Recepción / Administración / Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Turno programado con profesional asignado |
| **Postcondiciones** | Turno con nuevo profesional o «sin asignar», con bitácora y motivo |
| **Trigger** | El usuario selecciona “Reasignar” o “Desasignar” en el turno |

**Flujo Principal:**
1. El usuario abre el turno asignado.
2. El usuario selecciona “Reasignar” o “Desasignar”.
3. El sistema solicita motivo.
4. El usuario ingresa motivo y confirma.
5. El sistema valida el nuevo profesional (si aplica) contra matriz y solapamiento.
6. El sistema guarda el cambio y registra en bitácora valores anteriores y nuevos.
7. La agenda refleja el nuevo estado del turno.

**Flujos Alternativos:**
- **FA-01:** El usuario cancela; la asignación anterior se mantiene.

**Flujos de Excepción:**
- **FE-01:** Motivo vacío → el sistema no permite continuar.
- **FE-02:** El nuevo profesional no cumple la matriz o tiene solapamiento → el sistema rechaza e informa.

### CU-03: Asignar varios profesionales a un diagnóstico integral

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Asignar varios profesionales a un diagnóstico integral |
| **Actor Principal** | Recepción / Administración / Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Diagnóstico integral creado en el módulo 06; profesionales activos disponibles |
| **Postcondiciones** | Diagnóstico con todos sus profesionales registrados y validados |
| **Trigger** | El usuario abre el diagnóstico y agrega profesionales |

**Flujo Principal:**
1. El usuario abre el diagnóstico integral.
2. El sistema muestra los profesionales elegibles según especialidades requeridas y sucursal.
3. El usuario agrega uno o varios profesionales con su rol.
4. El sistema valida que cada uno cubra una especialidad requerida y no tenga solapamiento.
5. El sistema guarda los participantes y registra en bitácora.
6. El diagnóstico queda con todos sus participantes.

**Flujos Alternativos:**
- **FA-01:** El usuario puede guardar el diagnóstico con participantes parciales y completar después.

**Flujos de Excepción:**
- **FE-01:** Algún profesional no cubre especialidad requerida → el sistema rechaza esa asignación e informa.
- **FE-02:** Solapamiento detectado → el sistema rechaza la asignación del profesional conflictivo.
- **FE-03:** Se intenta asignar un profesional inactivo → el sistema lo impide.

---

## 9. Criterios de Aceptación

### CA-001: Asignación exitosa
- **Given** un turno programado y un profesional activo que cubre la especialidad en la sucursal,
- **When** el usuario lo asigna,
- **Then** el sistema guarda la asignación, la muestra en la agenda y la registra en bitácora.

### CA-002: Turno sin asignar
- **Given** un turno programado,
- **When** el usuario no selecciona profesional y guarda,
- **Then** el sistema guarda el turno en estado «sin asignar» y lo muestra así en la agenda.

### CA-003: Rechazo por especialidad
- **Given** un turno de “Fisioterapia”,
- **When** el usuario intenta asignar un profesional de “Psicología”,
- **Then** el sistema rechaza la asignación e informa el motivo.

### CA-004: Rechazo por sucursal
- **Given** un turno en “Yacuiba Centro”,
- **When** el usuario intenta asignar un profesional que no trabaja en esa sucursal,
- **Then** el sistema rechaza la asignación e informa el motivo.

### CA-005: Rechazo por solapamiento
- **Given** un profesional asignado a un turno de 10:00 a 10:50,
- **When** el usuario intenta asignarlo a otro turno de 10:00 a 10:50,
- **Then** el sistema rechaza la asignación e informa el solapamiento.

### CA-006: Reasignación con motivo
- **Given** un turno con profesional asignado,
- **When** el usuario reasigna indicando motivo,
- **Then** el sistema guarda el cambio y registra en bitácora valores anteriores y nuevos con motivo.

### CA-007: Desasignación con bitácora
- **Given** un turno con profesional asignado,
- **When** el usuario desasigna con motivo,
- **Then** el sistema deja el turno «sin asignar» y registra la acción.

### CA-008: Diagnóstico con varios profesionales
- **Given** un diagnóstico integral que requiere dos especialidades,
- **When** el usuario asigna dos profesionales que las cubren,
- **Then** el sistema registra a ambos con su rol y valida la matriz.

### CA-009: Restricción por estado del turno
- **Given** un turno en estado “asistió”,
- **When** el usuario intenta reasignarlo,
- **Then** el sistema rechaza la operación e informa el motivo.

### CA-010: Restricción por sucursal del usuario
- **Given** un usuario asignado solo a “Yacuiba Centro”,
- **When** intenta asignar profesionales en otra sucursal,
- **Then** el sistema rechaza la operación por alcance.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Asignaciones inválidas por datos incompletos en la matriz profesional–especialidad–sucursal | Media | Alto | Alto | Validación estricta en backend y pruebas de asignación por escenario. |
| RT-002 | Solapamientos no detectados por diferencias de zona horaria o turnos cruzados | Media | Medio | Medio | Validación con zona horaria del sistema y pruebas de casos límite. |
| RT-003 | Presión para automatizar asignaciones fuera de alcance | Media | Medio | Medio | Documentar el fuera de alcance y gestionar como cambio de etapa. |
| RT-004 | Reasignaciones sin motivo que dificulten la auditoría | Media | Bajo | Bajo | Motivo obligatorio cuando hay cambio (RN-006). |
| RT-005 | Ambigüedad sobre quién puede asignar (Recepción vs. Administración) | Alta | Medio | Alto | Confirmar con el cliente y ajustar la matriz de permisos. |
| RT-006 | Diagnósticos integrales con participantes de varias sucursales no previstos | Media | Medio | Medio | Validar con el cliente y definir reglas específicas si se requiere. |
| RT-007 | Rendimiento de la agenda al consultar asignaciones en tiempo real | Media | Medio | Medio | Índices, cache y pruebas de carga. |
| RT-008 | Dependencia de una sola persona para administrar asignaciones | Media | Medio | Medio | Documentación y transferencia de conocimiento. |

---

**Fin del documento**