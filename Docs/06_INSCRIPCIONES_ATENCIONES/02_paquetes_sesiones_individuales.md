# Paquetes de Sesiones Individuales
**Módulo:** Inscripciones y Atenciones
**Etapa:** 1
**Versión:** 1.0
**Fecha:** Noviembre de 2025
**Estado:** Borrador

---

## 1. Introducción

Este submódulo registra los paquetes de sesiones de terapia individual, que son la unidad comercial y operativa de la Intervención Terapéutica. Un paquete define cuántas sesiones incluye (por ejemplo 10), en qué días de la semana y a qué hora fija, con qué profesional (opcional), qué tarifa y categoría de tarifa se aplican y cuál es su período de vigencia.

La problemática actual es crítica en esta área: el cuaderno de intervención individual registra cada sesión a mano, no hay conteo confiable de sesiones consumidas ni saldo disponible, las faltas se anotan sin regla clara y los vencimientos se pierden. Este submódulo resuelve esa dispersión al crear el paquete como objeto formal, con reglas explícitas de duración, vencimiento y consumo.

El valor principal es el control de sesiones: el sistema sabe cuántas se pactaron, cuántas se consumieron, cuántas se perdieron por inasistencia y cuántas quedan disponibles, respetando la regla del negocio de que la sesión perdida no se recupera ni se descuenta y que las sesiones vencen al terminar el período. Se integra con los módulos de Pacientes y Tutores, Catálogo de Servicios y Tarifas, Profesionales, Agenda y Turnos, Pagos y Caja, y Reportes y Alertas.

---

## 2. Alcance

**Incluido en este módulo:**
- Registro del paquete: paciente, tutor, sucursal, servicio, profesional (opcional), cantidad de sesiones, días de la semana, hora, duración (50 min por defecto, configurable), fecha de inicio y fecha de fin.
- Selección de tarifa y categoría de tarifa al momento de registrar el paquete.
- Cálculo del monto esperado del paquete.
- Definición del patrón de días y hora que servirá de base para la programación de turnos (delegada al módulo de Agenda y Turnos).
- Consulta y listado de paquetes por sucursal, paciente, servicio, profesional, período y estado.
- Modificación de datos no estructurales con bitácora.
- Renovación del paquete al siguiente período.
- Baja del paquete con motivo.
- Vista de consumo del paquete: sesiones pactadas, realizadas, faltadas, canceladas y saldo.
- Vinculación con el estado de pago (integración con Pagos y Caja).
- Comprobante imprimible del paquete (no fiscal).

**Excluido de este módulo:**
- Generación y reprogramación individual de turnos (corresponde al módulo de Agenda y Turnos, submódulo 7.2).
- Asignación automática de profesionales (el profesional es opcional y se asigna manualmente).
- Recuperación de sesiones perdidas por inasistencia.
- Descuentos o compensaciones por faltas.
- Control de cupo.
- Facturación.
- Plan de cuotas (etapa 2).
- Mensajes automáticos de WhatsApp.

**Actores / Roles involucrados:**
- **Recepción:** registra y consulta paquetes de su sucursal.
- **Administración:** registra, modifica, renueva y da de baja paquetes; consulta reportes.
- **Superadmin:** acceso total y configuración.
- Los profesionales y los padres NO usan el sistema.

**Sucursales aplicables:**
- El paquete pertenece a una sucursal.
- El paciente es global.
- El profesional puede trabajar en una o más sucursales (ver módulo 3.2).

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa
Sustituye el conteo manual de sesiones del cuaderno de intervención individual por un registro formal con saldo calculado. Reduce reclamos y confusiones sobre cuántas sesiones quedan, y automatiza el descuento al registrar la asistencia (integración con Agenda y Turnos).

### 3.2 Justificación en la Atención al Niño y su Familia
Da certeza a la familia sobre el número de sesiones contratadas, su vigencia y las reglas de inasistencia. Al conocer el saldo disponible, Recepción puede informar con precisión y Administración puede alertar sobre la renovación antes del vencimiento.

### 3.3 Justificación Normativa
- Constitución Política del Estado (privacidad e intimidad; protección de la niñez) [verificar vigencia].
- Ley 548 Código Niña, Niño y Adolescente [verificar vigencia].
- Ley 3131 de Ejercicio Profesional Médico (secreto profesional y registros clínicos) [verificar vigencia] — aplica a la información clínica asociada al paquete, no al paquete en sí.
- Ley 1080 de Ciudadanía Digital (resguardo de información digital) [verificar vigencia].

### 3.4 Justificación Tecnológica
Permite calcular automáticamente el saldo de sesiones, disparar alertas de vencimiento y renovación, y alimentar los reportes de atención por profesional y sucursal. La base PostgreSQL y la API REST garantizan integridad de los datos y consultas rápidas.

---

## 4. Funciones Principales

### FP-01: Registrar paquete de sesiones individuales
- **Descripción:** Permite a Recepción o Administración crear un paquete definiendo paciente, tutor, sucursal, servicio, profesional (opcional), cantidad de sesiones, días de la semana, hora, duración, fecha de inicio y fecha de fin, además de tarifa y categoría de tarifa.
- **Actor principal:** Recepción (también Administración).
- **Resultado esperado:** Paquete creado en estado "Activo", con monto esperado calculado, patrón de días y hora listo para que Agenda y Turnos genere los turnos, y comprobante disponible.

### FP-02: Consultar, buscar y ver consumo del paquete
- **Descripción:** Permite listar y filtrar paquetes por sucursal, paciente, servicio, profesional, período y estado, y ver la ficha con el consumo (pactadas, realizadas, faltadas, canceladas y saldo).
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Listado filtrado y ficha de detalle con el saldo de sesiones actualizado.

### FP-03: Modificar y renovar paquete
- **Descripción:** Permite editar datos no estructurales del paquete (profesional, días, hora, observaciones) con bitácora, y renovar el paquete al siguiente período copiando la configuración vigente.
- **Actor principal:** Administración (modificación); Recepción y Administración (renovación).
- **Resultado esperado:** Paquete actualizado o nueva versión del paquete vinculada a la anterior, con bitácora.

### FP-04: Dar de baja el paquete con motivo
- **Descripción:** Permite dar de baja un paquete registrando motivo (retiro del paciente, cambio de servicio, cambio de profesional, otro) y fecha, bloqueando nuevas asistencias.
- **Actor principal:** Administración.
- **Resultado esperado:** Paquete en estado "Retirado" con motivo y fecha, historial preservado y turnos futuros advertidos.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear paquete | El sistema debe permitir registrar un paquete de sesiones seleccionando paciente, tutor, sucursal, servicio, profesional (opcional), cantidad de sesiones, días de la semana, hora, duración y fechas de inicio y fin. | Alta | Recepción / Administración |
| RF-002 | Cantidad de sesiones configurable | El sistema debe permitir definir la cantidad de sesiones del paquete (por ejemplo 5, 10, 20) según lo acordado con la familia; el valor por defecto es configurable. | Alta | Recepción |
| RF-003 | Duración configurable | La duración de cada sesión debe ser configurable, con valor por defecto de 50 minutos. | Alta | Administración |
| RF-004 | Patrón de días y hora | El sistema debe permitir seleccionar uno o más días de la semana (lunes a sábado) y una hora fija para las sesiones del paquete. | Alta | Recepción |
| RF-005 | Profesional opcional | El sistema debe permitir registrar el paquete sin profesional asignado, mostrando "Sin asignar"; el profesional puede asignarse después. | Alta | Recepción / Administración |
| RF-006 | Validar paciente activo | Solo se puede crear un paquete para un paciente en estado "Activo". | Alta | Sistema |
| RF-007 | Selección de tarifa y categoría | El sistema debe permitir seleccionar la tarifa vigente y la categoría de tarifa, y calcular el monto esperado del paquete. | Alta | Recepción |
| RF-008 | Cálculo del monto esperado | A partir de la tarifa, la categoría y la cantidad de sesiones, el sistema debe calcular el monto esperado del paquete y mostrarlo antes de guardar. | Alta | Sistema |
| RF-009 | Vista de consumo | El sistema debe mostrar en la ficha del paquete: sesiones pactadas, realizadas, faltadas, canceladas y saldo disponible. | Alta | Recepción / Administración |
| RF-010 | Descuento al registrar asistencia | Al registrar un turno como "Asistió" o "Faltó", el sistema debe descontar la sesión del saldo del paquete (integración con Agenda y Turnos). | Alta | Sistema |
| RF-011 | Listado filtrable | El sistema debe ofrecer listado de paquetes con filtros por sucursal, paciente, servicio, profesional, período y estado. | Alta | Recepción / Administración |
| RF-012 | Modificación con auditoría | El sistema debe permitir modificar profesional, días, hora y observaciones con registro en bitácora (usuario, fecha, valor anterior y nuevo). | Alta | Administración |
| RF-013 | Renovación | El sistema debe permitir renovar el paquete al siguiente período copiando la configuración vigente y generando un nuevo paquete vinculado al anterior. | Alta | Recepción / Administración |
| RF-014 | Baja con motivo | El sistema debe permitir dar de baja un paquete registrando motivo (retiro del paciente, cambio de servicio, cambio de profesional, otro) y fecha. | Alta | Administración |
| RF-015 | Comprobante imprimible | El sistema debe generar un comprobante imprimible (PDF) del paquete con datos del paciente, servicio, profesional, sesiones, período y monto, con leyenda "No es factura". | Alta | Recepción |
| RF-016 | Vinculación con pago | El sistema debe exponer el estado de pago del paquete y permitir registrar el pago desde la ficha, delegando en el módulo de Pagos y Caja. | Alta | Recepción |
| RF-017 | Control de acceso por sucursal | El sistema debe restringir la vista y edición de paquetes a las sucursales asignadas al usuario. | Alta | Sistema |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento
- **RNF-001:** El listado de paquetes debe cargar en menos de 2 segundos con hasta 5.000 paquetes por sucursal y 10 usuarios concurrentes.
- **RNF-002:** El cálculo del saldo de sesiones debe resolverse en menos de 500 ms por paquete.

### 6.2 Seguridad y Privacidad
- **RNF-003:** Autenticación obligatoria; contraseñas con hash seguro (bcrypt o Argon2).
- **RNF-004:** Comunicación por HTTPS; los datos de menores y tutores solo son visibles para usuarios autorizados según rol y sucursal.
- **RNF-005:** El saldo de sesiones es información operativa; los datos clínicos asociados al paquete solo se exponen en módulos clínicos autorizados.

### 6.3 Disponibilidad
- **RNF-006:** Disponibilidad del 99% en horario de atención (lunes a sábado, 07:00 a 20:00).
- **RNF-007:** Respaldo automático diario; RTO de 4 horas y RPO de 24 horas.

### 6.4 Usabilidad
- **RNF-008:** El alta de paquete debe completarse en un máximo de 7 pasos o clics desde el menú principal, con etiquetas en español y vocabulario del negocio.

### 6.5 Escalabilidad
- **RNF-009:** El modelo debe soportar más sucursales, servicios y profesionales sin cambios de código, manteniendo los tiempos de respuesta del RNF-001.

### 6.6 Interoperabilidad
- **RNF-010:** Exportación a PDF y Excel; generación de enlaces `wa.me` para contacto con el tutor; sin integraciones externas en esta etapa.

### 6.7 Mantenibilidad
- **RNF-011:** Catálogos administrables (servicios, tarifas, categorías, profesionales, duración de sesión); migraciones versionadas; documentación mínima.

### 6.8 Trazabilidad y Auditoría
- **RNF-012:** Toda alta, modificación, renovación y baja de paquete queda en bitácora con usuario, fecha y datos modificados; los paquetes no se eliminan, se dan de baja.

---

## 7. Reglas de Negocio

- **RN-001:** Paquete de N sesiones — Todo paquete debe definir la cantidad de sesiones incluidas; el valor por defecto es 10, configurable por el cliente.
- **RN-002:** Días y hora fijos — El paquete define uno o más días de la semana y una hora fija; la programación de turnos derivada respeta ese patrón.
- **RN-003:** Duración de sesión — Cada sesión dura 50 minutos por defecto; el valor es configurable a nivel de sistema.
- **RN-004:** Falta = sesión perdida — Si el paciente falta, la sesión se pierde: no se recupera ni se descuenta del saldo como si no hubiera ocurrido; el saldo refleja la sesión como perdida.
- **RN-005:** Vencimiento del paquete — Las sesiones vencen al terminar el período (fecha de fin); no pasan al siguiente período.
- **RN-006:** Profesional opcional y sin asignación automática — El paquete puede registrarse "Sin asignar"; el sistema no asigna profesionales automáticamente.
- **RN-007:** Múltiples paquetes simultáneos — Un paciente puede tener varios paquetes activos a la vez (por ejemplo, psicología y fonoaudiología); no puede tener dos paquetes activos del mismo servicio en el mismo período.
- **RN-008:** Datos obligatorios — Paciente, tutor, sucursal, servicio, cantidad de sesiones, días, hora, duración, fecha de inicio, fecha de fin, tarifa y categoría de tarifa son obligatorios.
- **RN-009:** Pago adelantado — El paquete debe quedar con pago registrado (total o a cuenta) antes o al inicio del período; el sistema alerta si no hay pago.
- **RN-010:** No se borra, se da de baja — Los paquetes no se eliminan; se cambian a "Retirado" con motivo y fecha.
- **RN-011:** Saldo calculado en tiempo real — El saldo de sesiones se calcula a partir de los turnos registrados como "Asistió" y "Faltó"; los turnos "Cancelados" no descuentan saldo.

---

## 8. Casos de Uso Principales

### CU-01: Registrar paquete de sesiones individuales

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar paquete de sesiones individuales |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Administración (consulta), Sistema |
| **Precondiciones** | Paciente activo; tutor registrado; servicio y tarifa vigentes; profesional cargado (opcional). |
| **Postcondiciones** | Paquete creado en estado "Activo" con cantidad de sesiones, patrón de días y hora, monto esperado, comprobante disponible y expectativa de pago registrada. |
| **Trigger** | La familia contrata un paquete de sesiones de terapia individual. |

**Flujo Principal:**
1. Recepción abre el formulario "Nuevo paquete de sesiones".
2. Busca y selecciona al paciente.
3. Selecciona al tutor, la sucursal y el servicio.
4. Selecciona al profesional (opcional; puede quedar "Sin asignar").
5. Ingresa cantidad de sesiones, días de la semana, hora y duración.
6. Ingresa fecha de inicio y fecha de fin del período.
7. Selecciona tarifa y categoría; el sistema calcula y muestra el monto esperado.
8. Recepción confirma y guarda.
9. El sistema crea el paquete, registra en bitácora, ofrece imprimir comprobante, registrar pago y derivar a Agenda y Turnos para generar los turnos.

**Flujos Alternativos:**
- **FA-01:** El paquete se registra sin profesional — el sistema permite y muestra "Sin asignar" en la ficha.
- **FA-02:** La familia pide varios servicios — se repite el flujo para cada servicio, generando paquetes independientes.

**Flujos de Excepción:**
- **FE-01:** Paciente inactivo — el sistema bloquea y sugiere reactivar.
- **FE-02:** Ya existe un paquete activo del mismo servicio en el mismo período — el sistema advierte y no permite duplicar.
- **FE-03:** La tarifa no está vigente a la fecha de inicio — el sistema sugiere la tarifa vigente y pide confirmación.

---

### CU-02: Renovar paquete de sesiones

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Renovar paquete de sesiones |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Existe un paquete activo por vencer o vencido; el paciente sigue activo; el servicio y la tarifa siguen vigentes. |
| **Postcondiciones** | Nuevo paquete creado para el siguiente período, vinculado al anterior, con la misma configuración salvo cambios explícitos. |
| **Trigger** | La familia confirma la continuidad de la terapia para el siguiente período. |

**Flujo Principal:**
1. El usuario busca el paquete vigente del paciente.
2. Selecciona "Renovar".
3. El sistema propone fechas del siguiente período, cantidad de sesiones, días, hora, tarifa vigente y categoría previa.
4. El usuario ajusta lo necesario y confirma.
5. El sistema crea el nuevo paquete, lo vincula al anterior y registra en bitácora.
6. El sistema ofrece imprimir comprobante, registrar pago y derivar a Agenda y Turnos.

**Flujos Alternativos:**
- **FA-01:** El saldo anterior no llegó a cero — el sistema advierte que las sesiones no consumidas no se trasladan (RN-005) y continúa.

**Flujos de Excepción:**
- **FE-01:** Paciente retirado — bloqueo y sugerencia de reactivar.
- **FE-02:** Servicio inactivo — bloqueo y sugerencia de cambiar de servicio.

---

### CU-03: Dar de baja paquete con motivo

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Dar de baja paquete con motivo |
| **Actor Principal** | Administración |
| **Actores Secundarios** | Recepción (notifica) |
| **Precondiciones** | Existe un paquete activo; la familia comunicó el retiro o cambio. |
| **Postcondiciones** | Paquete en estado "Retirado" con motivo, fecha y usuario; turnos futuros advertidos; historial preservado. |
| **Trigger** | Retiro del paciente, cambio de servicio o cambio de profesional. |

**Flujo Principal:**
1. Administración busca el paquete.
2. Selecciona "Dar de baja".
3. El sistema solicita motivo (lista cerrada + texto libre) y confirma fecha.
4. Administración confirma.
5. El sistema cambia el estado a "Retirado", registra en bitácora y bloquea nuevos descuentos de sesiones.

**Flujos Alternativos:**
- **FA-01:** El cambio es solo de profesional — Administración usa la modificación del paquete (RF-012) en lugar de la baja.

**Flujos de Excepción:**
- **FE-01:** Hay turnos futuros programados — el sistema advierte y sugiere cancelarlos.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de paquete
- **Given** un paciente activo, servicio y tarifa vigentes,
- **When** Recepción completa el formulario con 10 sesiones, 2 días por semana, 50 minutos y un período,
- **Then** el sistema crea el paquete en estado "Activo" y muestra el monto esperado.

### CA-002: Profesional opcional
- **Given** un paquete nuevo,
- **When** Recepción no selecciona profesional,
- **Then** el sistema guarda el paquete con "Sin asignar" y permite asignarlo después.

### CA-003: Bloqueo por duplicidad
- **Given** un paquete activo del mismo servicio en el mismo período,
- **When** Recepción intenta crear otro igual,
- **Then** el sistema advierte y no permite duplicar.

### CA-004: Descuento al registrar asistencia
- **Given** un paquete de 10 sesiones con 4 realizadas,
- **When** se registra un turno como "Asistió",
- **Then** el saldo pasa a 5 sesiones disponibles.

### CA-005: Falta descuenta sesión
- **Given** un paquete de 10 sesiones con 4 realizadas y saldo 6,
- **When** se registra un turno como "Faltó",
- **Then** el saldo pasa a 5 y la sesión no se recupera.

### CA-006: Vencimiento sin traslado
- **Given** un paquete con saldo 2 al llegar la fecha de fin,
- **When** finaliza el período,
- **Then** el sistema marca el paquete como "Vencido" y las 2 sesiones no se trasladan al siguiente período.

### CA-007: Renovación vinculada
- **Given** un paquete activo que vence este mes,
- **When** Recepción renueva,
- **Then** se crea un nuevo paquete para el siguiente período, vinculado al anterior, con la misma configuración.

### CA-008: Baja con motivo
- **Given** un paquete activo,
- **When** Administración da de baja con motivo "Retiro del paciente",
- **Then** el estado pasa a "Retirado" y no se permiten nuevas asistencias.

### CA-009: Filtro por sucursal
- **Given** un usuario asignado solo a la Sucursal Norte,
- **When** consulta paquetes,
- **Then** solo ve paquetes de la Sucursal Norte.

### CA-010: Comprobante no fiscal
- **Given** un paquete creado,
- **When** Recepción imprime el comprobante,
- **Then** el PDF incluye "Este documento no es factura", datos del paciente, servicio, sesiones, período y monto.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Conteo de sesiones inconsistente entre paquete y turnos | Media | Alto | Alto | Descuento transaccional al registrar asistencia; verificación diaria de saldos; bitácora de movimientos. |
| RT-002 | Baja adopción por Recepción | Media | Alto | Alto | Capacitación, manual breve y soporte en sitio la primera semana. |
| RT-003 | Pacientes duplicados o paquetes mal vinculados | Alta | Medio | Alto | Búsqueda por CI y nombre; validación de unicidad por servicio y período. |
| RT-004 | Errores en cálculo de monto | Media | Alto | Alto | Cálculo en backend, no en frontend; pruebas unitarias y revisión con Administración. |
| RT-005 | Cambios de alcance durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa y aprobación formal del cliente. |
| RT-006 | Dependencia de una sola persona para soporte | Media | Alto | Alto | Documentación técnica y transferencia de conocimiento. |
| RT-007 | Conectividad inestable al registrar asistencia | Media | Medio | Medio | Cola local de eventos y sincronización posterior; plan de contingencia en papel. |

---

**Fin del documento**