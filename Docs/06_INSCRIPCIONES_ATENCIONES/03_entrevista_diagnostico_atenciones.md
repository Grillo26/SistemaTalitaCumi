# Entrevista y Diagnóstico como Atenciones
**Módulo:** Inscripciones y Atenciones
**Etapa:** 1
**Versión:** 1.0
**Fecha:** Noviembre de 2025
**Estado:** Borrador

---

## 1. Introducción

Este submódulo registra dos atenciones especiales dentro de la Intervención Terapéutica: la entrevista con los padres y el diagnóstico o evaluación. La entrevista es el primer contacto con la familia y tiene un costo menor; el diagnóstico es un proceso de varios días con un costo mayor, requiere elegir un tipo de diagnóstico y puede involucrar a uno o más profesionales.

La problemática actual es que estas atenciones se anotan en el cuaderno de intervención individual y en notas sueltas, sin un registro formal del tipo de diagnóstico, sin vínculo con los profesionales que participaron y sin control del cobro correspondiente. A veces la entrevista se salta y se va directo al diagnóstico, y esa decisión no queda documentada.

El valor de este submódulo es formalizar la entrevista y el diagnóstico como atenciones con costo propio, vincularlas al paciente y al tutor, permitir la asignación de uno o varios profesionales, y conectar el cobro con el área de caja. Se integra con Pacientes y Tutores, Catálogo de Servicios y Tarifas (tipos de diagnóstico), Profesionales, Agenda y Turnos, Pagos y Caja, y Evaluaciones y Resultados (etapa 2).

---

## 2. Alcance

**Incluido en este módulo:**
- Registro de la entrevista con los padres como atención con costo menor.
- Registro del diagnóstico o evaluación como atención con costo mayor y duración de varios días.
- Selección del tipo de diagnóstico desde el catálogo.
- Asignación de uno o más profesionales al diagnóstico.
- Registro de fechas de inicio y fin del diagnóstico.
- Selección de tarifa y categoría de tarifa para cada atención.
- Cálculo del monto esperado de cada atención.
- Consulta y listado de entrevistas y diagnósticos por sucursal, paciente, profesional, tipo, período y estado.
- Modificación con bitácora.
- Baja con motivo.
- Comprobante imprimible (no fiscal) de cada atención.
- Vinculación con el estado de pago (integración con Pagos y Caja).

**Excluido de este módulo:**
- Resultados del diagnóstico e informes clínicos (corresponden al módulo 11, etapa 2).
- Asignación automática de profesionales.
- Control de cupo.
- Facturación.
- Mensajes automáticos de WhatsApp.
- Recuperación de sesiones o reprogramación (aplica a terapias, no a estas atenciones).

**Actores / Roles involucrados:**
- **Recepción:** registra entrevistas y diagnósticos de su sucursal.
- **Administración:** registra, modifica y da de baja; consulta reportes.
- **Superadmin:** acceso total.
- Los profesionales y los padres NO usan el sistema.

**Sucursales aplicables:**
- La atención pertenece a una sucursal.
- El paciente es global.
- Los profesionales pueden trabajar en una o más sucursales.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa
Elimina el registro manual de entrevistas y diagnósticos en cuadernos y notas, y permite saber con certeza qué pacientes pasaron por entrevista y qué diagnósticos se realizaron en cada sucursal. Facilita listar diagnósticos por tipo, profesional y período.

### 3.2 Justificación en la Atención al Niño y su Familia
Da claridad a la familia sobre el proceso de ingreso: si hay entrevista, cuánto cuesta, qué tipo de diagnóstico se realizará, qué profesionales participan y cuánto demora. La formalización reduce la incertidumbre y mejora la percepción del servicio.

### 3.3 Justificación Normativa
- Constitución Política del Estado (privacidad e intimidad; protección de la niñez) [verificar vigencia].
- Ley 3131 de Ejercicio Profesional Médico (secreto profesional y registros clínicos) [verificar vigencia] — aplica a la información clínica derivada del diagnóstico, no al registro administrativo de la atención.
- Ley 548 Código Niña, Niño y Adolescente [verificar vigencia].
- Ley 223 General para Personas con Discapacidad [verificar vigencia] — por el contexto de atención a la niñez con necesidades de desarrollo.

### 3.4 Justificación Tecnológica
Permite registrar atenciones con costos diferenciados, vincularlas a uno o varios profesionales y conectar el cobro con caja. La base de datos relacional asegura integridad entre atención, paciente, profesional y pago.

---

## 4. Funciones Principales

### FP-01: Registrar entrevista con los padres
- **Descripción:** Permite registrar la entrevista inicial con los padres como atención con costo menor, vinculada a paciente, tutor, sucursal, servicio, tarifa y categoría de tarifa.
- **Actor principal:** Recepción.
- **Resultado esperado:** Entrevista registrada en estado "Activa" con monto esperado calculado y comprobante disponible.

### FP-02: Registrar diagnóstico o evaluación
- **Descripción:** Permite registrar un diagnóstico con tipo seleccionable, fechas de inicio y fin (varios días), uno o más profesionales y tarifa con categoría.
- **Actor principal:** Recepción (también Administración).
- **Resultado esperado:** Diagnóstico registrado con su tipo, profesionales asignados, período y monto esperado.

### FP-03: Consultar y listar entrevistas y diagnósticos
- **Descripción:** Permite filtrar y listar entrevistas y diagnósticos por sucursal, paciente, profesional, tipo, período y estado, con vista de detalle.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Listado filtrado y ficha de detalle de cada atención.

### FP-04: Modificar y dar de baja con motivo
- **Descripción:** Permite editar datos no estructurales (profesionales, fechas, observaciones) con bitácora y dar de baja con motivo cuando corresponda.
- **Actor principal:** Administración.
- **Resultado esperado:** Atención actualizada o marcada como "Retirada" con motivo y fecha.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Registrar entrevista | El sistema debe permitir registrar una entrevista con los padres seleccionando paciente, tutor, sucursal, servicio, tarifa, categoría de tarifa y fecha. | Alta | Recepción |
| RF-002 | Registrar diagnóstico | El sistema debe permitir registrar un diagnóstico seleccionando paciente, sucursal, tipo de diagnóstico, fechas de inicio y fin, tarifa y categoría. | Alta | Recepción / Administración |
| RF-003 | Selección de tipo de diagnóstico | El sistema debe ofrecer el catálogo de tipos de diagnóstico vigentes; cada tipo define los profesionales requeridos (uno o más). | Alta | Recepción |
| RF-004 | Asignación de profesionales | El sistema debe permitir asignar uno o más profesionales al diagnóstico, respetando el tipo seleccionado. | Alta | Recepción / Administración |
| RF-005 | Saltar entrevista | El sistema debe permitir registrar un diagnóstico sin entrevista previa, dejando constancia de que se omitió. | Alta | Recepción |
| RF-006 | Cálculo del monto esperado | A partir de la tarifa y la categoría, el sistema debe calcular el monto esperado de la entrevista y del diagnóstico, y mostrarlo antes de guardar. | Alta | Sistema |
| RF-007 | Fechas de diagnóstico | El sistema debe permitir definir fecha de inicio y fecha de fin del diagnóstico (varios días); si la atención es de un día, ambas coinciden. | Alta | Recepción |
| RF-008 | Listado filtrable | El sistema debe ofrecer listado de entrevistas y diagnósticos con filtros por sucursal, paciente, profesional, tipo, período y estado. | Alta | Recepción / Administración |
| RF-009 | Vista de detalle | El sistema debe mostrar la ficha de la atención con datos del paciente, tipo, profesionales, fechas, tarifa, estado de pago y observaciones. | Alta | Recepción / Administración |
| RF-010 | Modificación con auditoría | El sistema debe permitir modificar profesionales, fechas y observaciones con registro en bitácora. | Alta | Administración |
| RF-011 | Baja con motivo | El sistema debe permitir dar de baja una entrevista o diagnóstico registrando motivo (retiro del paciente, error de registro, otro) y fecha. | Alta | Administración |
| RF-012 | Comprobante imprimible | El sistema debe generar comprobante imprimible (PDF) de la entrevista o diagnóstico con datos del paciente, tipo, profesionales, fechas y monto, con leyenda "No es factura". | Alta | Recepción |
| RF-013 | Vinculación con pago | El sistema debe exponer el estado de pago y permitir registrar el pago desde la ficha, delegando en Pagos y Caja. | Alta | Recepción |
| RF-014 | Control de acceso por sucursal | El sistema debe restringir la vista y edición a las sucursales asignadas al usuario. | Alta | Sistema |
| RF-015 | Notas administrativas | El sistema debe permitir registrar notas administrativas de la atención (máx. 500 caracteres), sin datos clínicos. | Media | Recepción / Administración |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento
- **RNF-001:** El listado de entrevistas y diagnósticos debe cargar en menos de 2 segundos con hasta 3.000 registros por sucursal y 10 usuarios concurrentes.
- **RNF-002:** El alta de una atención debe completarse en menos de 3 segundos.

### 6.2 Seguridad y Privacidad
- **RNF-003:** Autenticación obligatoria; contraseñas con hash seguro.
- **RNF-004:** HTTPS; datos de menores y tutores visibles solo para usuarios autorizados por rol y sucursal.
- **RNF-005:** Los datos clínicos derivados del diagnóstico no se almacenan en este submódulo; solo los administrativos (tipo, profesionales, fechas, monto).

### 6.3 Disponibilidad
- **RNF-006:** Disponibilidad del 99% en horario de atención.
- **RNF-007:** Respaldo automático diario; RTO 4 horas y RPO 24 horas.

### 6.4 Usabilidad
- **RNF-008:** El alta de entrevista o diagnóstico debe completarse en un máximo de 6 pasos o clics, con vocabulario del negocio.

### 6.5 Escalabilidad
- **RNF-009:** El modelo debe soportar más tipos de diagnóstico, profesionales y sucursales sin cambios de código.

### 6.6 Interoperabilidad
- **RNF-010:** Exportación a PDF y Excel; sin integraciones externas en esta etapa.

### 6.7 Mantenibilidad
- **RNF-011:** Catálogo de tipos de diagnóstico administrable; migraciones versionadas; documentación mínima.

### 6.8 Trazabilidad y Auditoría
- **RNF-012:** Altas, modificaciones y bajas quedan en bitácora; las atenciones no se eliminan, se dan de baja.

---

## 7. Reglas de Negocio

- **RN-001:** Entrevista con costo menor — La entrevista con los padres tiene una tarifa menor que el diagnóstico; ambas se seleccionan del catálogo de tarifas.
- **RN-002:** Diagnóstico con costo mayor y varios días — El diagnóstico tiene costo mayor y fechas de inicio y fin que pueden abarcar varios días.
- **RN-003:** Tipo de diagnóstico obligatorio — Todo diagnóstico debe tener un tipo seleccionado del catálogo; cada tipo define los profesionales requeridos (uno o más).
- **RN-004:** Entrevista opcional — La entrevista puede omitirse y registrar directamente el diagnóstico, dejando constancia de la omisión.
- **RN-005:** Varios profesionales — Un diagnóstico puede tener uno o varios profesionales asignados según el tipo; el sistema no asigna automáticamente.
- **RN-006:** Profesional opcional — Un diagnóstico puede registrarse "Sin asignar" y completarse después.
- **RN-007:** Datos obligatorios — Paciente, sucursal, tipo de diagnóstico (si aplica), fechas, tarifa y categoría son obligatorios.
- **RN-008:** Pago adelantado — La entrevista y el diagnóstico requieren pago adelantado (total o a cuenta); el sistema alerta si no hay pago.
- **RN-009:** No se borra, se da de baja — Las atenciones no se eliminan; se cambian a "Retirada" con motivo y fecha.
- **RN-010:** Comprobante no fiscal — El comprobante de la atención no es factura; lleva la leyenda explícita.
- **RN-011:** Sin resultados clínicos en este módulo — Los resultados del diagnóstico y los informes se gestionan en el módulo de Evaluaciones y Resultados (etapa 2).

---

## 8. Casos de Uso Principales

### CU-01: Registrar entrevista con los padres

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar entrevista con los padres |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Paciente activo; tutor registrado; tarifa de entrevista vigente. |
| **Postcondiciones** | Entrevista registrada en estado "Activa" con monto esperado, comprobante y expectativa de pago. |
| **Trigger** | La familia asiste a la entrevista inicial. |

**Flujo Principal:**
1. Recepción abre el formulario "Nueva entrevista".
2. Busca y selecciona al paciente y al tutor.
3. Selecciona la sucursal y la fecha de la entrevista.
4. Selecciona la tarifa de entrevista y la categoría.
5. El sistema calcula y muestra el monto esperado.
6. Recepción confirma y guarda.
7. El sistema crea la entrevista, registra en bitácora y ofrece imprimir comprobante y registrar el pago.

**Flujos Alternativos:**
- **FA-01:** La familia decide continuar directo al diagnóstico — se registra el diagnóstico (CU-02) y la entrevista queda vinculada.

**Flujos de Excepción:**
- **FE-01:** Paciente inactivo — bloqueo y sugerencia de reactivar.

---

### CU-02: Registrar diagnóstico con varios profesionales

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Registrar diagnóstico con varios profesionales |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Paciente activo; tipo de diagnóstico vigente; profesionales cargados; tarifa vigente. |
| **Postcondiciones** | Diagnóstico registrado con tipo, profesionales, fechas de inicio y fin, monto esperado, comprobante y expectativa de pago. |
| **Trigger** | La familia inicia el proceso de diagnóstico o evaluación. |

**Flujo Principal:**
1. Recepción abre el formulario "Nuevo diagnóstico".
2. Busca y selecciona al paciente.
3. Selecciona el tipo de diagnóstico.
4. El sistema muestra los profesionales requeridos según el tipo.
5. Recepción asigna uno o más profesionales (o deja "Sin asignar").
6. Ingresa fecha de inicio y fecha de fin (varios días).
7. Selecciona tarifa y categoría; el sistema calcula el monto esperado.
8. Recepción confirma y guarda.
9. El sistema crea el diagnóstico, registra en bitácora y ofrece imprimir comprobante y registrar el pago.

**Flujos Alternativos:**
- **FA-01:** Se omite la entrevista — el sistema deja constancia de que no hubo entrevista previa y permite continuar.
- **FA-02:** El tipo de diagnóstico requiere más profesionales — el sistema exige la cantidad mínima según el tipo.

**Flujos de Excepción:**
- **FE-01:** Tipo de diagnóstico inactivo — bloqueo y sugerencia de otro tipo.
- **FE-02:** Profesional no asignado a la sucursal — advertencia y bloqueo hasta corregir.

---

### CU-03: Omitir entrevista y registrar diagnóstico directo

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Omitir entrevista y registrar diagnóstico directo |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Paciente activo; tipo de diagnóstico vigente. |
| **Postcondiciones** | Diagnóstico registrado con marca de "Sin entrevista previa" en la ficha. |
| **Trigger** | La familia o el profesional deciden ir directo al diagnóstico. |

**Flujo Principal:**
1. Recepción abre el formulario de diagnóstico.
2. Selecciona al paciente.
3. Marca "Omitir entrevista" (casilla con motivo opcional).
4. Completa el resto del diagnóstico.
5. El sistema guarda con la marca de omisión y registra en bitácora.

**Flujos Alternativos:**
- **FA-01:** Se decide luego hacer la entrevista — se registra la entrevista y se vincula al diagnóstico ya creado.

**Flujos de Excepción:**
- **FE-01:** Ya existe una entrevista para el paciente — el sistema informa y permite decidir si se registra igual el diagnóstico.

---

## 9. Criterios de Aceptación

### CA-001: Alta de entrevista
- **Given** un paciente activo y una tarifa de entrevista vigente,
- **When** Recepción registra la entrevista,
- **Then** el sistema la crea en estado "Activa" y muestra el monto esperado.

### CA-002: Alta de diagnóstico con tipo
- **Given** un tipo de diagnóstico vigente que requiere 2 profesionales,
- **When** Recepción registra el diagnóstico y asigna 2 profesionales,
- **Then** el sistema guarda y muestra los profesionales en la ficha.

### CA-003: Bloqueo por profesionales insuficientes
- **Given** un tipo de diagnóstico que requiere 2 profesionales,
- **When** Recepción intenta guardar con 1 solo profesional,
- **Then** el sistema bloquea y muestra "Faltan profesionales requeridos por el tipo".

### CA-004: Diagnóstico sin entrevista
- **Given** un paciente sin entrevista previa,
- **When** Recepción registra el diagnóstico y marca "Omitir entrevista",
- **Then** el sistema guarda y muestra la marca de omisión en la ficha.

### CA-005: Fechas de varios días
- **Given** un diagnóstico con fecha de inicio 10/03 y fin 14/03,
- **When** Recepción guarda,
- **Then** el sistema registra el rango y lo muestra en la ficha y en el comprobante.

### CA-006: Modificación con bitácora
- **Given** un diagnóstico activo,
- **When** Administración cambia un profesional,
- **Then** el sistema registra en bitácora usuario, fecha, valor anterior y nuevo.

### CA-007: Baja con motivo
- **Given** una entrevista activa,
- **When** Administración da de baja con motivo "Error de registro",
- **Then** el estado pasa a "Retirada" y no se permite modificar el monto.

### CA-008: Filtro por tipo y profesional
- **Given** varios diagnósticos cargados,
- **When** Administración filtra por tipo "Evaluación psicológica" y profesional "X",
- **Then** el sistema muestra solo los diagnósticos que cumplen ambos filtros.

### CA-009: Filtro por sucursal
- **Given** un usuario asignado solo a la Sucursal Sur,
- **When** consulta entrevistas y diagnósticos,
- **Then** solo ve registros de la Sucursal Sur.

### CA-010: Comprobante no fiscal
- **Given** una atención registrada,
- **When** Recepción imprime el comprobante,
- **Then** el PDF incluye "Este documento no es factura", datos del paciente, tipo, profesionales, fechas y monto.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Confusión entre entrevista y diagnóstico al registrar | Media | Alto | Alto | Formularios separados, títulos claros y validaciones que impiden mezclar datos. |
| RT-002 | Tipos de diagnóstico mal parametrizados | Media | Alto | Alto | Catálogo configurable, revisión con Administración antes de producción y validaciones de profesionales requeridos. |
| RT-003 | Profesionales no asignados a la sucursal | Media | Medio | Medio | Validación al seleccionar profesional; advertencia clara y bloqueo hasta corregir. |
| RT-004 | Baja adopción por Recepción | Media | Alto | Alto | Capacitación y manual breve con vocabulario del negocio. |
| RT-005 | Cambios de alcance durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa y aprobación formal del cliente. |
| RT-006 | Dependencia de una sola persona para soporte | Media | Alto | Alto | Documentación técnica y transferencia de conocimiento. |
| RT-007 | Conectividad inestable | Media | Medio | Medio | Guardado progresivo y plan de contingencia en papel. |

---

**Fin del documento**