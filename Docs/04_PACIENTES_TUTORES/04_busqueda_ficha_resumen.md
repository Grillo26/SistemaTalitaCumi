# Búsqueda y ficha resumen de pacientes
**Módulo:** Pacientes y tutores
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo provee el **buscador de pacientes** y la **ficha resumen** que concentra, en una sola pantalla, el estado del paciente, sus servicios vigentes (inscripciones y paquetes activos), el estado de pago y un resumen de asistencia. Es la pantalla que Recepción y Administración usan a diario para atender consultas de la familia, verificar si un paciente está al día y decidir acciones como inscribirlo, agendarlo o avisar del próximo pago.

Hoy esta información se arma a mano consultando cuadernos, la pizarra de turnos y el libro de recibos, lo que consume tiempo y produce errores. Este submódulo reemplaza esa búsqueda manual por una vista consolidada, filtrable y respetuosa del alcance por sucursal, que integra datos de los módulos de pacientes, inscripciones, agenda, pagos y caja. El buscador está pensado para funcionar rápido, incluso con miles de pacientes, y para no exponer datos sensibles a roles no autorizados.

El valor que aporta es la eficiencia operativa y la calidad de la atención: Recepción responde con datos confiables en segundos, Administración detecta casos que requieren seguimiento y la familia recibe información consistente. Se integra con Registro de pacientes, Registro de tutores y contactos, Inscripciones, Agenda y turnos, Pagos y caja, y Alertas de próximo pago.

---

## 2. Alcance

### Incluido en este módulo

- **Buscador de pacientes** por nombre, apellido, documento, teléfono del tutor o tutor principal, con resultados paginados y filtros por estado y sucursal de inscripción vigente.
- **Ficha resumen** con: datos básicos del paciente, tutores y contactos, estado del paciente, servicios vigentes (inscripciones y paquetes activos), estado de pago (al día / pendiente / con saldo) y resumen de asistencia (turnos asistidos, faltó, cancelados y porcentaje del período).
- Acceso directo desde la ficha resumen a las acciones frecuentes: ver ficha completa, ver inscripciones, ver turnos, ver pagos, generar alerta de próximo pago (enlace a WhatsApp sin envío automático).
- Filtros y ordenamientos en el buscador (estado, sucursal, tipo de servicio, al día / pendiente).
- Respeto del alcance por rol y sucursal: cada usuario ve solo los pacientes de sus sucursales.
- Registro en bitácora de cada consulta a la ficha resumen (usuario, fecha, hora, paciente consultado).
- Exportación del listado filtrado a PDF y Excel (según permisos).
- Actualización en tiempo real de los indicadores (al menos al abrir la ficha) para reflejar cambios recientes en pagos y asistencia.

### Excluido de este módulo

- **Edición de datos del paciente o tutores:** submódulos 01 y 02.
- **Registro de pagos y movimientos de caja:** módulo 08; aquí solo se muestran los indicadores.
- **Registro de asistencia:** módulo 07; aquí solo se muestra el resumen.
- **Reportes analíticos avanzados y por profesional:** módulo 09 (Etapa 2).
- **Plan de cuotas y gestión de deudas:** Etapa 2 (módulo 08, submódulo 05).
- **Envío automático de WhatsApp:** fuera de alcance; solo se genera el enlace.
- **Portal de padres y app móvil:** fuera de alcance por el contexto.
- **Integraciones externas:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Consulta la ficha resumen de todos los pacientes; exporta el listado. |
| Administración | Consulta y exporta la ficha resumen de los pacientes de sus sucursales. |
| Recepción | Consulta la ficha resumen de los pacientes de sus sucursales; usa las acciones frecuentes (inscribir, agendar, alerta de pago). |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **La ficha resumen es global en los datos del paciente**, pero los servicios, turnos y pagos mostrados corresponden a las sucursales del alcance del usuario.
- **Si el paciente tiene inscripciones en varias sucursales**, la ficha resumen debe indicarlo claramente y permitir alternar el contexto de sucursal. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **Los datos sensibles** se muestran en la ficha resumen según el rol del usuario.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que la ficha resumen muestra todos los servicios del paciente, aunque estén en sucursales distintas, indicando la sucursal de cada uno. Debe confirmarse si Recepción puede ver servicios de otra sucursal (por ejemplo, cuando un hermano se atiende en otra sede) o si solo ve los de su sucursal. También debe confirmarse qué indicadores de asistencia se muestran (del mes en curso, del paquete, del período de inscripción).

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción atiende consultas del tipo “¿está al día?”, “¿cuántas sesiones le quedan?”, “¿vino la semana pasada?”. Sin una ficha resumen, debe revisar cuadernos, pizarra y recibos por separado, lo que demora la atención y produce errores. Con el buscador y la ficha resumen, responde en segundos y puede accionar directamente (inscribir, agendar, avisar del próximo pago). Administración obtiene una vista consolidada para supervisar y detectar casos que requieren seguimiento.

### 3.2 Justificación en la Atención al Niño y su Familia

La familia recibe información consistente y oportuna sobre la situación de su hijo, lo que refuerza la confianza. Recepción puede detectar rápidamente ausencias reiteradas o pagos pendientes y actuar con empatía, sin exponer datos innecesarios. La ficha resumen es una herramienta de atención, no solo de control.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** interés superior del niño y resguardo de la información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** secreto profesional aplicable a los datos de salud mostrados en la ficha [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales, con acceso mínimo necesario [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** uso de WhatsApp conforme a las reglas del país; el sistema solo genera el enlace [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

El acceso mínimo necesario y la bitácora de consultas son mecanismos para cumplir con estas obligaciones.

### 3.4 Justificación Tecnológica

Frente a la consulta manual, la ficha resumen aporta: búsqueda rápida con índices adecuados; vista consolidada con datos de varios módulos; filtros y ordenamientos; indicadores calculados en tiempo real; y bitácora de consultas. Se reduce el tiempo de atención y se evita exponer datos innecesarios.

---

## 4. Funciones Principales

### FP-01: Buscar pacientes
- **Descripción:** Permite encontrar pacientes por nombre, apellido, documento, teléfono del tutor o tutor principal, con resultados paginados y filtros por estado y sucursal de inscripción vigente.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Lista de pacientes coincidentes, ordenada y paginada, con acceso a la ficha resumen.

### FP-02: Consultar ficha resumen
- **Descripción:** Muestra en una pantalla el estado del paciente, los servicios vigentes, el estado de pago y el resumen de asistencia, con acceso a las acciones frecuentes.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Vista consolidada que permite responder consultas y accionar en segundos.

### FP-03: Acceder a acciones frecuentes
- **Descripción:** Desde la ficha resumen, permite abrir la ficha completa, ver inscripciones, ver turnos, ver pagos y generar la alerta de próximo pago con enlace de WhatsApp.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Continuidad operativa sin cambiar de módulo manualmente.

### FP-04: Exportar listado filtrado
- **Descripción:** Permite exportar el listado de pacientes filtrado a PDF y Excel, respetando el alcance del usuario y registrando la exportación en bitácora.
- **Actor principal:** Administración, Superadmin.
- **Resultado esperado:** Archivo generado con los datos vigentes y registro de la exportación.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Buscar por nombre y apellido | El sistema debe permitir buscar pacientes por nombre y/o apellido, con coincidencia parcial y sin distinguir mayúsculas ni tildes. | Alta | Recepción, Administración, Superadmin |
| RF-002 | Buscar por documento | El sistema debe permitir buscar por documento de identidad del paciente. | Alta | Recepción, Administración, Superadmin |
| RF-003 | Buscar por tutor | El sistema debe permitir buscar por nombre o teléfono del tutor principal. | Alta | Recepción, Administración, Superadmin |
| RF-004 | Filtrar por estado y sucursal | El sistema debe permitir filtrar por estado del paciente (activo, en pausa, retirado) y por sucursal de inscripción vigente, respetando el alcance del usuario. | Alta | Recepción, Administración, Superadmin |
| RF-005 | Mostrar ficha resumen | El sistema debe mostrar en una sola pantalla: datos básicos del paciente, tutores y contactos, estado, servicios vigentes, estado de pago y resumen de asistencia. | Alta | Recepción, Administración, Superadmin |
| RF-006 | Mostrar servicios vigentes | El sistema debe mostrar las inscripciones a salas grupales y los paquetes de sesiones individuales vigentes, con sucursal, servicio y período. | Alta | Recepción, Administración, Superadmin |
| RF-007 | Mostrar estado de pago | El sistema debe mostrar si el paciente está al día, con saldo pendiente o con pagos vencidos, según los pagos registrados y el período vigente. | Alta | Recepción, Administración, Superadmin |
| RF-008 | Mostrar resumen de asistencia | El sistema debe mostrar la cantidad de turnos asistidos, faltó y cancelados del período vigente, y el porcentaje de asistencia. | Alta | Recepción, Administración, Superadmin |
| RF-009 | Acciones frecuentes | El sistema debe permitir abrir la ficha completa, ver inscripciones, ver turnos, ver pagos y generar la alerta de próximo pago con enlace de WhatsApp, sin envío automático. | Alta | Recepción, Administración, Superadmin |
| RF-010 | Scoping por sucursal | El sistema debe mostrar solo los pacientes con inscripciones en las sucursales del usuario; Superadmin ve todos. | Alta | Sistema |
| RF-011 | Indicar sucursales múltiples | Si el paciente tiene inscripciones en varias sucursales, el sistema debe indicarlo y permitir alternar el contexto de sucursal. | Media | Recepción, Administración, Superadmin |
| RF-012 | Registrar bitácora de consultas | El sistema debe registrar cada consulta a la ficha resumen con usuario, fecha, hora y paciente consultado. | Alta | Sistema |
| RF-013 | Exportar listado | El sistema debe permitir exportar el listado filtrado a PDF y Excel, con fecha y hora de exportación, y registrar la exportación en bitácora. | Baja | Administración, Superadmin |
| RF-014 | Restringir datos sensibles por rol | El sistema debe ocultar datos sensibles (por ejemplo, condiciones médicas) a roles no autorizados. | Alta | Sistema |
| RF-015 | Actualizar indicadores | El sistema debe actualizar los indicadores de pago y asistencia al abrir la ficha y permitir su actualización manual mediante un botón. | Media | Sistema |
| RF-016 | Mostrar próximo vencimiento | El sistema debe mostrar la fecha del próximo vencimiento de pago o de fin de período de la inscripción vigente. | Alta | Recepción, Administración, Superadmin |
| RF-017 | Ordenar resultados | El sistema debe permitir ordenar los resultados por apellido, fecha de nacimiento o estado. | Media | Recepción, Administración, Superadmin |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Los indicadores exactos de la ficha resumen (por ejemplo, si el resumen de asistencia es del mes en curso, del paquete o del período de la inscripción) y los criterios de “al día” / “con saldo” deben confirmarse con el cliente. También debe confirmarse si Recepción puede ver servicios de otras sucursales o solo los de su sucursal activa.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La búsqueda debe responder en menos de 1 segundo con hasta 5 000 pacientes, con paginación.
- **RNF-002:** La ficha resumen debe cargar en menos de 2 segundos con 10 usuarios concurrentes, incluyendo indicadores de pago y asistencia.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen sesión autenticada.
- **RNF-004:** El scoping por sucursal y por rol debe validarse en backend, no solo en la interfaz.
- **RNF-005:** Los datos sensibles se muestran solo a roles autorizados; los accesos quedan en bitácora.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los indicadores deben ser consistentes con los módulos de origen; si un módulo está momentáneamente caído, la ficha resumen debe indicar el dato como “no disponible” en lugar de mostrar valores incorrectos.

### 6.4 Usabilidad

- **RNF-008:** La ficha resumen debe ser legible en una sola pantalla, con jerarquía visual clara y accesos directos a las acciones frecuentes.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en pacientes y en volumen de pagos y turnos sin degradación significativa.

### 6.6 Interoperabilidad

- **RNF-011:** La ficha resumen debe consumir datos de pacientes, inscripciones, agenda y pagos vía API, y exportar el listado a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** Las consultas de indicadores deben centralizarse en servicios reutilizables para evitar duplicación y facilitar el mantenimiento.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Cada consulta a la ficha resumen y cada exportación deben quedar en bitácora con usuario, fecha, hora y paciente consultado.

---

## 7. Reglas de Negocio

- **RN-001:** Búsqueda global con scoping — El buscador opera sobre todos los pacientes, pero los resultados se filtran por las sucursales del usuario.
- **RN-002:** Acceso mínimo — La ficha resumen muestra solo los datos necesarios para la atención; los datos sensibles se ocultan a roles no autorizados.
- **RN-003:** Estado de pago — Se considera “al día” al paciente sin pagos vencidos para su inscripción vigente; “con saldo” cuando hay pagos a cuenta pendientes; “vencido” cuando existe un período sin pago dentro de la inscripción activa. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **RN-004:** Resumen de asistencia — Se calcula sobre el período de la inscripción o paquete vigente; los turnos cancelados no cuentan como asistidos ni como faltas para el porcentaje. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **RN-005:** Servicios vigentes — Se muestran inscripciones y paquetes con estado vigente y dentro del período activo; los vencidos quedan consultables en la ficha completa.
- **RN-006:** Sucursales múltiples — Si el paciente tiene servicios en varias sucursales, la ficha resumen lo indica y permite alternar el contexto.
- **RN-007:** Alerta de próximo pago — La alerta muestra el teléfono del tutor principal y un enlace de WhatsApp; no hay envío automático.
- **RN-008:** Consultas auditadas — Cada consulta a la ficha resumen queda registrada en bitácora.
- **RN-009:** Exportación auditada — Toda exportación del listado queda registrada en bitácora.
- **RN-010:** Sin edición desde la ficha resumen — La ficha resumen es de consulta; las modificaciones se hacen en los módulos correspondientes.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Las RN-003 y RN-004 (definición de “al día” y cálculo de asistencia) deben confirmarse; son reglas clave para la operación y su definición exacta afecta los indicadores que verá Recepción.

---

## 8. Casos de Uso Principales

### CU-01: Buscar paciente y abrir ficha resumen

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Buscar paciente y abrir ficha resumen |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema (scoping y bitácora) |
| **Precondiciones** | Sesión activa; paciente dentro del alcance del usuario |
| **Postcondiciones** | Ficha resumen visible con indicadores vigentes y consulta registrada |
| **Trigger** | Recepción atiende una consulta de la familia o verifica la situación de un paciente |

**Flujo Principal:**
1. Recepción ingresa nombre, apellido, documento o teléfono del tutor en el buscador.
2. El sistema aplica el scoping por sucursal y muestra resultados paginados.
3. Recepción selecciona el paciente.
4. El sistema carga la ficha resumen con estado, servicios vigentes, estado de pago y resumen de asistencia.
5. El sistema registra la consulta en bitácora.
6. Recepción responde a la familia y, si corresponde, acciona (inscribir, agendar, alerta de pago).

**Flujos Alternativos:**
- **FA-01:** La búsqueda no devuelve resultados; el sistema sugiere revisar la ortografía o crear el paciente.
- **FA-02:** El paciente tiene servicios en varias sucursales; el sistema permite alternar el contexto.

**Flujos de Excepción:**
- **FE-01:** Un módulo de origen no responde → la ficha resumen muestra el indicador como “no disponible” y no bloquea la consulta.
- **FE-02:** Falla al registrar la consulta en bitácora → el sistema alerta técnicamente y muestra la ficha con advertencia.

### CU-02: Consultar estado de pago y asistencia

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Consultar estado de pago y asistencia |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema (indicadores y bitácora) |
| **Precondiciones** | Paciente con inscripción vigente |
| **Postcondiciones** | Indicadores mostrados y consulta registrada |
| **Trigger** | El usuario abre la ficha resumen de un paciente con inscripción vigente |

**Flujo Principal:**
1. El usuario abre la ficha resumen.
2. El sistema calcula el estado de pago a partir de los pagos registrados y el período vigente.
3. El sistema calcula el resumen de asistencia a partir de los turnos del período.
4. El sistema muestra ambos indicadores con la fecha de corte.
5. El sistema registra la consulta en bitácora.

**Flujos Alternativos:**
- **FA-01:** El usuario actualiza los indicadores manualmente con el botón “Actualizar”.

**Flujos de Excepción:**
- **FE-01:** No hay período vigente → el sistema indica “sin servicios vigentes” y no calcula indicadores.
- **FE-02:** Falla el cálculo → el sistema muestra el indicador como “no disponible”.

### CU-03: Generar alerta de próximo pago desde la ficha resumen

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Generar alerta de próximo pago desde la ficha resumen |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema (enlace WhatsApp), módulo de auditoría |
| **Precondiciones** | Paciente con inscripción próxima a vencer; tutor principal con teléfono válido |
| **Postcondiciones** | Enlace de WhatsApp generado y consulta registrada |
| **Trigger** | Recepción selecciona “Alerta de próximo pago” en la ficha resumen |

**Flujo Principal:**
1. Recepción selecciona “Alerta de próximo pago”.
2. El sistema muestra el nombre del tutor principal y su teléfono.
3. El sistema genera el enlace de WhatsApp con el mensaje predefinido (sin envío automático).
4. Recepción abre el enlace en su dispositivo o copia el teléfono.
5. El sistema registra la acción en bitácora.

**Flujos Alternativos:**
- **FA-01:** El tutor principal no tiene teléfono → el sistema sugiere actualizar la ficha o elegir otro tutor.

**Flujos de Excepción:**
- **FE-01:** El enlace no puede generarse por datos inválidos → el sistema informa y sugiere actualizar el teléfono del tutor.

---

## 9. Criterios de Aceptación

### CA-001: Búsqueda por nombre
- **Given** un usuario autenticado,
- **When** busca por nombre parcial,
- **Then** el sistema devuelve pacientes coincidentes paginados y filtrados por sucursal.

### CA-002: Búsqueda por tutor
- **Given** un tutor con teléfono registrado,
- **When** el usuario busca por ese teléfono,
- **Then** el sistema devuelve el paciente asociado y permite abrir su ficha resumen.

### CA-003: Ficha resumen consolidada
- **Given** un paciente activo con inscripción vigente y pagos registrados,
- **When** el usuario abre su ficha resumen,
- **Then** el sistema muestra estado, servicios, estado de pago y resumen de asistencia en una sola pantalla.

### CA-004: Scoping por sucursal
- **Given** un usuario asignado solo a “Yacuiba Centro”,
- **When** busca pacientes,
- **Then** el sistema solo muestra pacientes con inscripciones en esa sucursal.

### CA-005: Indicador de pago “al día”
- **Given** un paciente con pagos completos para el período vigente,
- **When** el usuario abre la ficha resumen,
- **Then** el sistema muestra el estado “al día” con la fecha de corte.

### CA-006: Indicador de pago “vencido”
- **Given** un paciente con período vigente sin pago,
- **When** el usuario abre la ficha resumen,
- **Then** el sistema muestra el estado “vencido” y el próximo vencimiento.

### CA-007: Resumen de asistencia
- **Given** un paciente con turnos asistidos, faltas y cancelaciones en el período,
- **When** el usuario abre la ficha resumen,
- **Then** el sistema muestra los conteos y el porcentaje de asistencia calculado según la regla vigente.

### CA-008: Alerta de próximo pago
- **Given** un paciente con inscripción próxima a vencer y tutor principal con teléfono,
- **When** Recepción selecciona “Alerta de próximo pago”,
- **Then** el sistema muestra el teléfono y genera el enlace de WhatsApp sin enviar automáticamente.

### CA-009: Consulta auditada
- **Given** un usuario que abre la ficha resumen,
- **When** se muestra la ficha,
- **Then** el sistema registra la consulta en bitácora con usuario, fecha, hora y paciente.

### CA-010: Sucursales múltiples
- **Given** un paciente con servicios en dos sucursales,
- **When** el usuario autorizado abre la ficha resumen,
- **Then** el sistema indica las sucursales y permite alternar el contexto.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Indicadores desactualizados por desfase entre módulos | Media | Alto | Alto | Actualización al abrir la ficha y botón manual de actualización; monitoreo de latencia entre módulos. |
| RT-002 | Exposición de datos sensibles por errores de scoping | Media | Alto | Alto | Validación en backend, pruebas de permisos y bitácora de consultas. |
| RT-003 | Búsqueda lenta con muchos pacientes | Media | Medio | Medio | Índices adecuados, paginación y pruebas de carga. |
| RT-004 | Ambigüedad en la definición de “al día” y del cálculo de asistencia | Alta | Alto | Alto | Definir con el cliente antes de implementar y documentar en las RN. |
| RT-005 | Caída de un módulo de origen que rompa la ficha resumen | Media | Medio | Medio | Indicadores “no disponibles” sin bloquear; monitoreo de servicios. |
| RT-006 | Exportaciones con datos sensibles no controladas | Media | Medio | Medio | Restringir exportación a roles autorizados y registrar en bitácora. |
| RT-007 | Ambigüedad sobre qué sucursales ve Recepción en la ficha | Alta | Medio | Alto | Confirmar con el cliente y ajustar el scoping. |
| RT-008 | Dependencia de una sola persona para administrar los indicadores | Media | Medio | Medio | Documentación y transferencia de conocimiento. |

---

**Fin del documento**