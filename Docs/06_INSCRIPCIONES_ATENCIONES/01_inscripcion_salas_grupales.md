# Inscripción a Salas Grupales
**Módulo:** Inscripciones y Atenciones
**Etapa:** 1
**Versión:** 1.0
**Fecha:** Noviembre de 2025
**Estado:** Borrador

---

## 1. Introducción

Este submódulo permite a Recepción y Administración registrar la inscripción mensual de un paciente a una sala grupal del centro, dejando constancia formal del servicio contratado, la sucursal, el turno, la tarifa aplicada y el período de vigencia. Es la puerta de entrada al flujo operativo de las salas grupales, que hasta hoy se gestiona con anotaciones en cuadernos y mensajes de WhatsApp.

El centro opera con dos áreas (Salas Grupales e Intervención Terapéutica) y varias sucursales. Cada sala se define por la combinación de tipo, turno y sucursal (por ejemplo, "Estimulación Temprana — Mañana — Sucursal Centro"). La inscripción a sala grupal es mensual, con fecha de inicio y fin, y convive con otras inscripciones que el mismo niño pueda tener (por ejemplo, terapia individual en paralelo).

La problemática actual incluye pérdida de información, duplicidad de registros, dificultad para saber qué niños están inscritos en cada sala y en qué período, y desconexión con el área de caja al momento de cobrar. Este submódulo resuelve esa dispersión al centralizar la inscripción, validar los datos mínimos y vincularla con el catálogo de servicios, tarifas y sucursales.

El valor principal es la trazabilidad: cada inscripción queda con su período, tarifa, categoría, estado y vínculo con el pago correspondiente, lo que habilita los reportes de ocupación por sala, las alertas de próximo pago y los listados de asistencia. Se integra con los módulos de Pacientes y Tutores, Catálogo de Servicios y Tarifas, Agenda y Turnos, Pagos y Caja, y Reportes y Alertas.

---

## 2. Alcance

**Incluido en este módulo:**
- Registro de inscripción mensual de un paciente a una sala grupal (tipo + turno + sucursal).
- Selección de servicio, tarifa y categoría de tarifa al momento de inscribir.
- Definición de fecha de inicio y fecha de fin del período.
- Consulta, búsqueda y listado de inscripciones por sucursal, sala, paciente, período y estado.
- Modificación de datos de la inscripción (fechas, tarifa, categoría, observaciones) con registro de auditoría.
- Renovación de inscripción para el siguiente período.
- Baja (retiro) de la inscripción con motivo.
- Visualización del estado de pago asociado a la inscripción (integración con Pagos y Caja).
- Visualización de asistencia vinculada a los turnos de la sala (integración con Agenda y Turnos).
- Generación de comprobante de inscripción imprimible (no fiscal).

**Excluido de este módulo:**
- Control de cupo por sala (fuera de alcance del proyecto en esta etapa).
- Asignación automática de profesionales a la sala (los profesionales rotan y se asignan manualmente).
- Facturación (el comprobante no es factura).
- Mensajes automáticos de WhatsApp a los tutores.
- Portal de padres y app móvil nativa.
- Cálculo de plan de cuotas (etapa 2).
- Recuperación de sesiones perdidas por inasistencia.

**Actores / Roles involucrados:**
- **Recepción:** registra y consulta inscripciones de su sucursal.
- **Administración:** registra, modifica, renueva y da de baja inscripciones; consulta reportes.
- **Superadmin:** acceso total, configuración y consulta global.
- Los profesionales y los padres NO usan el sistema.

**Sucursales aplicables:**
- La inscripción pertenece a una sucursal y a una sala de esa sucursal.
- El paciente y el tutor son globales (compartidos entre sucursales).
- El usuario accede solo a las sucursales que tenga asignadas.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa
Reemplaza el registro manual en cuadernos por un formulario guiado que valida datos mínimos (paciente, tutor, sala, período, tarifa). Reduce el tiempo de atención al permitir buscar al paciente por nombre, CI o código y reutilizar sus datos. Evita inscribir dos veces al mismo niño en la misma sala y período, y facilita listar los inscritos por sala y mes.

### 3.2 Justificación en la Atención al Niño y su Familia
Garantiza que el niño quede formalmente inscrito con un período claro y una tarifa acordada, lo que da certeza a la familia sobre el servicio contratado. Al vincular la inscripción con el comprobante de pago y con la agenda, la familia recibe información consistente sobre fechas y montos.

### 3.3 Justificación Normativa
- Constitución Política del Estado (derecho a la privacidad e intimidad; protección de la niñez) [verificar vigencia].
- Ley 548 Código Niña, Niño y Adolescente (interés superior del niño, protección de datos de menores) [verificar vigencia].
- Ley 1080 de Ciudadanía Digital (uso de medios digitales y resguardo de información) [verificar vigencia].
- La inscripción no constituye un acto médico; el secreto profesional y la historia clínica aplican a los módulos clínicos, no a este submódulo.

### 3.4 Justificación Tecnológica
Centraliza la información en PostgreSQL, elimina la duplicidad de cuadernos, permite consultas rápidas por sucursal y período, deja bitácora de cambios y habilita reportes automáticos. La API REST de Laravel y la interfaz Angular aseguran disponibilidad desde computadora y tablet.

---

## 4. Funciones Principales

### FP-01: Registrar inscripción a sala grupal
- **Descripción:** Permite a Recepción o Administración crear una inscripción mensual de un paciente a una sala grupal, seleccionando sucursal, sala, servicio, tarifa, categoría de tarifa, fecha de inicio y fecha de fin del período.
- **Actor principal:** Recepción (también Administración).
- **Resultado esperado:** Inscripción creada en estado "Activa" con su comprobante de inscripción disponible y con expectativa de pago registrada para el área de caja.

### FP-02: Consultar y buscar inscripciones
- **Descripción:** Permite filtrar y listar inscripciones por sucursal, sala, paciente, tutor, período y estado, con vista de detalle de cada inscripción.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Listado filtrado y ficha de detalle de la inscripción con datos de paciente, sala, período, tarifa y estado.

### FP-03: Modificar, renovar o dar de baja la inscripción
- **Descripción:** Permite editar datos no estructurales (fechas, tarifa, categoría, observaciones), renovar la inscripción al siguiente período copiando la configuración vigente, o darla de baja con motivo.
- **Actor principal:** Administración (modificación y baja); Recepción (consulta y renovación).
- **Resultado esperado:** Inscripción actualizada con registro en bitácora, nueva inscripción generada para el período siguiente, o inscripción marcada como "Retirada" con motivo y fecha.

### FP-04: Visualizar estado de pago y asistencia de la inscripción
- **Descripción:** Muestra en la ficha de la inscripción el estado del pago (pagado, a cuenta, pendiente, anulado) y el resumen de asistencia de los turnos asociados.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Vista consolidada por inscripción que permite responder consultas de la familia y alimentar reportes.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear inscripción a sala grupal | El sistema debe permitir registrar una inscripción mensual seleccionando paciente, tutor responsable, sucursal, sala (tipo + turno + sucursal), servicio, tarifa, categoría de tarifa, fecha de inicio y fecha de fin. | Alta | Recepción / Administración |
| RF-002 | Validar paciente activo | Solo se puede inscribir a un paciente con estado "Activo". Si está "En pausa" o "Retirado", el sistema bloquea la inscripción y sugiere reactivarlo primero. | Alta | Sistema |
| RF-003 | Validar unicidad | El sistema debe impedir dos inscripciones activas del mismo paciente en la misma sala y mismo período. | Alta | Sistema |
| RF-004 | Búsqueda de paciente en el alta | En el formulario de inscripción, el sistema debe ofrecer búsqueda por nombre, apellidos, CI o código de paciente y autocompletar los datos. | Alta | Recepción |
| RF-005 | Cálculo del monto esperado | A partir de la tarifa y la categoría de tarifa seleccionadas, el sistema debe calcular el monto esperado de la inscripción y mostrarlo antes de guardar. | Alta | Sistema |
| RF-006 | Registro de observaciones | El sistema debe permitir agregar observaciones de texto libre a la inscripción (máx. 500 caracteres). | Media | Recepción / Administración |
| RF-007 | Listado filtrable | El sistema debe ofrecer listado de inscripciones con filtros por sucursal, sala, paciente, período (mes/año) y estado. | Alta | Recepción / Administración |
| RF-008 | Vista de detalle | El sistema debe mostrar la ficha de la inscripción con datos del paciente, tutor, sala, período, tarifa, estado de pago y asistencia resumida. | Alta | Recepción / Administración |
| RF-009 | Modificación con auditoría | El sistema debe permitir modificar fechas, tarifa, categoría y observaciones, registrando en bitácora usuario, fecha, valor anterior y valor nuevo. | Alta | Administración |
| RF-010 | Renovación de inscripción | El sistema debe permitir renovar la inscripción al siguiente período copiando la configuración vigente, generando una nueva inscripción vinculada a la anterior. | Alta | Recepción / Administración |
| RF-011 | Baja con motivo | El sistema debe permitir dar de baja una inscripción registrando motivo (retiro del paciente, cambio de sala, cambio de sucursal, otro) y fecha. | Alta | Administración |
| RF-012 | Comprobante de inscripción imprimible | El sistema debe generar un comprobante imprimible (PDF) de la inscripción con datos del paciente, tutor, sala, período, tarifa, monto y datos del centro, con leyenda "No es factura". | Alta | Recepción |
| RF-013 | Vinculación con pago | El sistema debe exponer el estado de pago asociado a la inscripción y permitir registrar el pago desde la ficha de la inscripción, delegando en el módulo de Pagos y Caja. | Alta | Recepción |
| RF-014 | Visualización de asistencia | El sistema debe mostrar el resumen de asistencia (asistencias, faltas, cancelados) de los turnos asociados a la inscripción en el período. | Media | Recepción / Administración |
| RF-015 | Exportación | El sistema debe permitir exportar el listado filtrado de inscripciones a Excel y PDF. | Media | Administración |
| RF-016 | Control de acceso por sucursal | El sistema debe restringir la vista y edición de inscripciones a las sucursales asignadas al usuario. | Alta | Sistema |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento
- **RNF-001:** El listado de inscripciones debe cargar en menos de 2 segundos con hasta 5.000 inscripciones por sucursal y 10 usuarios concurrentes.
- **RNF-002:** La creación de una inscripción debe completarse en menos de 3 segundos en condiciones normales de red.

### 6.2 Seguridad y Privacidad
- **RNF-003:** Todo acceso debe ser autenticado; las contraseñas se almacenan con hash seguro (bcrypt o Argon2).
- **RNF-004:** La comunicación debe ser por HTTPS; los datos de menores y tutores solo son visibles para usuarios autorizados según rol y sucursal.
- **RNF-005:** Los datos sensibles del paciente (diagnóstico, contacto) no deben exponerse en listados; solo en la ficha de detalle con permiso.

### 6.3 Disponibilidad
- **RNF-006:** El sistema debe estar disponible en horario de atención del centro (lunes a sábado, 07:00 a 20:00) con un objetivo de disponibilidad del 99%.
- **RNF-007:** Respaldo automático diario de la base de datos; tiempo de recuperación objetivo (RTO) de 4 horas y pérdida máxima de datos (RPO) de 24 horas.

### 6.4 Usabilidad
- **RNF-008:** La inscripción debe completarse en un máximo de 6 pasos o clics desde el menú principal, con etiquetas en español y vocabulario del negocio (sala, turno, inscripción, período).

### 6.5 Escalabilidad
- **RNF-009:** El modelo debe soportar el crecimiento a más sucursales y salas sin cambios de código; la cantidad de inscripciones por período no debe degradar el rendimiento más allá de los límites del RNF-001.

### 6.6 Interoperabilidad
- **RNF-010:** Exportación a PDF y Excel; generación de enlaces `wa.me` para contacto con el tutor; sin integraciones externas en esta etapa.

### 6.7 Mantenibilidad
- **RNF-011:** Catálogos (salas, servicios, tarifas, categorías) administrables desde el sistema; migraciones de base de datos versionadas; código bajo control de versiones con documentación mínima.

### 6.8 Trazabilidad y Auditoría
- **RNF-012:** Toda alta, modificación, renovación y baja de inscripción debe registrarse en bitácora con usuario, fecha, acción y datos modificados; las inscripciones no se eliminan, se dan de baja.

---

## 7. Reglas de Negocio

- **RN-001:** Inscripción mensual — Toda inscripción a sala grupal debe tener fecha de inicio y fecha de fin que definan un período mensual; no se permiten períodos abiertos.
- **RN-002:** Sala = tipo + turno + sucursal — La sala seleccionada debe pertenecer a la sucursal de la inscripción y estar activa; no se puede inscribir a una sala inactiva.
- **RN-003:** Múltiples inscripciones simultáneas — Un paciente puede tener varias inscripciones activas a la vez (por ejemplo, sala grupal y terapia individual), pero no dos en la misma sala y mismo período.
- **RN-004:** Categoría de tarifa elegida por Recepción — La categoría de tarifa se selecciona manualmente al momento de inscribir; el sistema no la infiere.
- **RN-005:** Pago adelantado — La inscripción debe quedar con pago registrado (total o a cuenta) antes o al inicio del período; el sistema muestra alerta si el período inicia sin pago.
- **RN-006:** Sin control de cupo — El sistema no valida cantidad máxima de inscriptos por sala en esta etapa.
- **RN-007:** Sucursal de la inscripción — La inscripción pertenece a la sucursal de la sala; no se puede mover de sucursal sin darla de baja y crear una nueva.
- **RN-008:** Datos obligatorios — Paciente, tutor responsable, sucursal, sala, servicio, tarifa, categoría de tarifa, fecha de inicio y fecha de fin son obligatorios.
- **RN-009:** No se borra, se da de baja — Las inscripciones no se eliminan físicamente; se cambian de estado a "Retirada" con motivo y fecha, preservando el histórico.
- **RN-010:** Comprobante no fiscal — El comprobante de inscripción es imprimible pero no constituye factura; debe llevar la leyenda explícita.
- **RN-011:** Vigencia de la tarifa — La tarifa seleccionada debe estar vigente a la fecha de inicio de la inscripción; si no lo está, el sistema sugiere la tarifa vigente.

---

## 8. Casos de Uso Principales

### CU-01: Registrar inscripción a sala grupal

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar inscripción a sala grupal |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Administración (consulta), Sistema |
| **Precondiciones** | Paciente registrado y activo; tutor registrado; sala activa en la sucursal; servicio y tarifa vigentes cargados. |
| **Postcondiciones** | Inscripción creada en estado "Activa" con período definido, monto esperado calculado, comprobante disponible y expectativa de pago registrada. |
| **Trigger** | La familia solicita inscribir al niño en una sala grupal. |

**Flujo Principal:**
1. Recepción abre el formulario "Nueva inscripción a sala grupal".
2. Busca y selecciona al paciente por nombre, CI o código.
3. Selecciona al tutor responsable de la inscripción.
4. Selecciona la sucursal y la sala (tipo + turno + sucursal) activa.
5. Selecciona el servicio y la tarifa vigente; elige la categoría de tarifa.
6. Ingresa fecha de inicio y fecha de fin del período.
7. El sistema valida unicidad y vigencia de tarifa, y muestra el monto esperado.
8. Recepción confirma y guarda.
9. El sistema crea la inscripción, registra en bitácora y ofrece imprimir el comprobante y registrar el pago.

**Flujos Alternativos:**
- **FA-01:** La familia pide inscribir al niño en dos salas distintas — el flujo se repite para cada sala; el sistema permite múltiples inscripciones activas simultáneas.
- **FA-02:** Recepción decide renovar en lugar de crear una nueva — se activa el flujo de renovación (ver CU-02).

**Flujos de Excepción:**
- **FE-01:** El paciente no está activo — el sistema bloquea y ofrece ir al módulo de pacientes para reactivarlo.
- **FE-02:** Ya existe inscripción activa en la misma sala y período — el sistema muestra alerta y no permite guardar.
- **FE-03:** La tarifa seleccionada no está vigente a la fecha de inicio — el sistema sugiere la tarifa vigente y pide confirmación.

---

### CU-02: Renovar inscripción mensual

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Renovar inscripción mensual |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Existe una inscripción activa cuyo período está por vencer o venció; el paciente sigue activo; la sala sigue activa. |
| **Postcondiciones** | Nueva inscripción creada para el siguiente período, vinculada a la anterior, con la misma configuración (salvo cambios explícitos). |
| **Trigger** | La familia confirma la continuidad del niño en la sala para el siguiente período. |

**Flujo Principal:**
1. El usuario busca la inscripción vigente del paciente.
2. Selecciona la acción "Renovar".
3. El sistema propone fechas del siguiente período, tarifa vigente y categoría de tarifa previa.
4. El usuario ajusta lo necesario y confirma.
5. El sistema crea la nueva inscripción, la vincula con la anterior y registra en bitácora.
6. El sistema ofrece imprimir comprobante y registrar el pago del nuevo período.

**Flujos Alternativos:**
- **FA-01:** La tarifa cambió — el sistema muestra la tarifa vigente y pide confirmación.

**Flujos de Excepción:**
- **FE-01:** La sala ya no está activa — el sistema bloquea la renovación y sugiere cambiar de sala o dar de baja.
- **FE-02:** El paciente cambió de estado a "Retirado" — el sistema bloquea y sugiere reactivar primero.

---

### CU-03: Dar de baja inscripción por retiro del paciente

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Dar de baja inscripción por retiro del paciente |
| **Actor Principal** | Administración |
| **Actores Secundarios** | Recepción (notifica) |
| **Precondiciones** | Existe inscripción activa; la familia comunicó el retiro. |
| **Postcondiciones** | Inscripción en estado "Retirada" con motivo, fecha y usuario responsable; historial preservado. |
| **Trigger** | La familia comunica que el niño deja la sala. |

**Flujo Principal:**
1. Administración busca la inscripción.
2. Selecciona "Dar de baja".
3. El sistema solicita motivo (lista cerrada + texto libre) y confirma fecha de baja.
4. Administración confirma.
5. El sistema cambia el estado a "Retirada", registra en bitácora y bloquea nuevas asistencias sobre esa inscripción.

**Flujos Alternativos:**
- **FA-01:** El retiro es temporal — Administración puede usar la pausa de la inscripción en lugar de la baja (ver submódulo 6.5 Estados del paciente y de la inscripción).

**Flujos de Excepción:**
- **FE-01:** Hay turnos futuros ya programados — el sistema advierte y sugiere cancelarlos antes de la baja.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de inscripción
- **Given** un paciente activo, tutor registrado, sala activa y tarifa vigente,
- **When** Recepción completa el formulario y confirma,
- **Then** el sistema crea la inscripción en estado "Activa", calcula el monto esperado y muestra el comprobante imprimible.

### CA-002: Bloqueo por paciente inactivo
- **Given** un paciente en estado "Retirado",
- **When** Recepción intenta inscribirlo a una sala,
- **Then** el sistema bloquea la acción y muestra el mensaje "Debe reactivar al paciente antes de inscribir".

### CA-003: Bloqueo por duplicidad
- **Given** una inscripción activa del mismo paciente en la misma sala y período,
- **When** Recepción intenta crear una nueva inscripción igual,
- **Then** el sistema muestra error y no permite guardar.

### CA-004: Cálculo del monto esperado
- **Given** una tarifa de Bs. 500 y una categoría de tarifa "General",
- **When** Recepción selecciona la tarifa y la categoría,
- **Then** el sistema muestra "Monto esperado: Bs. 500" antes de guardar.

### CA-005: Modificación con bitácora
- **Given** una inscripción activa,
- **When** Administración modifica la fecha de fin,
- **Then** el sistema guarda el cambio y registra en bitácora usuario, fecha, valor anterior y valor nuevo.

### CA-006: Renovación vinculada
- **Given** una inscripción activa que vence este mes,
- **When** Recepción renueva,
- **Then** el sistema crea una nueva inscripción para el siguiente período, vinculada a la anterior, con la configuración vigente.

### CA-007: Baja con motivo
- **Given** una inscripción activa,
- **When** Administración da de baja con motivo "Retiro del paciente",
- **Then** el sistema cambia el estado a "Retirada", guarda la fecha y no permite registrar nuevas asistencias sobre esa inscripción.

### CA-008: Filtro por sucursal
- **Given** un usuario asignado solo a la Sucursal Centro,
- **When** consulta el listado de inscripciones,
- **Then** solo ve inscripciones de Sucursal Centro.

### CA-009: Comprobante no fiscal
- **Given** una inscripción creada,
- **When** Recepción imprime el comprobante,
- **Then** el PDF incluye la leyenda "Este documento no es factura" y los datos del paciente, sala, período y monto.

### CA-010: Vinculación con pago
- **Given** una inscripción activa sin pago,
- **When** Recepción registra el pago desde la ficha,
- **Then** el sistema delega en el módulo de Pagos y Caja y actualiza el estado de pago de la inscripción.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Pacientes duplicados al migrar desde cuadernos | Alta | Alto | Alto | Proceso de depuración previo con Administración; búsqueda por CI y nombre antes de crear. |
| RT-002 | Baja adopción por Recepción | Media | Alto | Alto | Capacitación, manual breve, interfaz en español con vocabulario del negocio y soporte en sitio la primera semana. |
| RT-003 | Conectividad inestable en el centro | Media | Medio | Medio | Interfaz con guardado progresivo y reintentos; plan de contingencia con registro en papel para caídas prolongadas. |
| RT-004 | Errores en selección de tarifa o categoría | Media | Alto | Alto | Validación de vigencia, vista previa del monto y confirmación explícita antes de guardar. |
| RT-005 | Cambios de alcance durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa, bitácora de cambios y aprobación formal del cliente. |
| RT-006 | Dependencia de una sola persona para soporte | Media | Alto | Alto | Documentación técnica, repositorio compartido y transferencia de conocimiento al equipo del cliente. |
| RT-007 | Fuga de datos de menores | Baja | Alto | Medio | Acceso por rol y sucursal, HTTPS, bitácora de accesos y respaldos cifrados. |

---

**Fin del documento**