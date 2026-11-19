# Ficha de ingreso y entrevista
**Módulo:** Pacientes y tutores
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo registra la **ficha de ingreso** del paciente al centro: los datos de contexto familiar, el motivo de consulta expresado por la familia, la persona que deriva o recomienda, la composición del hogar, los contactos de emergencia, las autorizaciones (consentimientos) y las observaciones iniciales del ingreso. La ficha de ingreso es administrativa y de contexto; **no reemplaza** la entrevista terapéutica con los padres (que es un servicio con costo y se gestiona en el módulo 06) ni el registro clínico de la evaluación (que pertenece al módulo 11, en Etapa 2 o 3 según confirmación).

Hoy esta información se recoge en papel en el primer contacto con la familia y se archiva en una carpeta física, lo que dificulta consultarla después y la expone a pérdida. Este submódulo reemplaza ese registro por una ficha digital, asociada al paciente y a sus tutores, con campos mínimos, autorizaciones registradas y bitácora de cambios. Se integra con Registro de pacientes, Registro de tutores y contactos, Inscripciones, Entrevista y diagnóstico, y con el módulo de evaluaciones y resultados cuando esté disponible.

El valor que aporta es doble: por un lado, garantiza que el centro cuente con la información de contexto necesaria para tomar decisiones de atención; por otro, deja constancia de las autorizaciones de la familia y de la persona que deriva, lo que refuerza la trazabilidad y el cumplimiento normativo.

---

## 2. Alcance

### Incluido en este módulo

- Registro de la **ficha de ingreso** del paciente con: motivo de consulta, persona que deriva o recomienda, composición familiar, antecedentes relevantes referidos por la familia, observaciones iniciales y fecha de ingreso.
- Registro de **consentimientos y autorizaciones** de los tutores: consentimiento para la atención, autorización para el uso de datos de contacto, autorización para el uso de imágenes (si el cliente lo requiere), y cualquier otra que el cliente defina.
- Registro de **contactos de emergencia** adicionales (nombre, parentesco, teléfono).
- Edición y actualización de la ficha con bitácora de cambios.
- Consulta de la ficha por roles autorizados y dentro del alcance por sucursal.
- Registro en bitácora de altas, ediciones y consultas.
- Exportación de la ficha a PDF para adjuntar al legajo físico (respetando el alcance del usuario).

### Excluido de este módulo

- **Datos clínicos de la evaluación y el diagnóstico:** corresponde al módulo 11 (Etapa 2 o 3).
- **Entrevista con los padres como servicio con costo:** corresponde al módulo 06 (`03_entrevista_diagnostico_atenciones.md`).
- **Datos de identificación del paciente y tutores:** submódulos 01 y 02.
- **Inscripciones y turnos:** módulos 06 y 07.
- **Pagos y caja:** módulo 08.
- **Consentimientos informados específicos para procedimientos clínicos:** se marcan como supuesto; el alcance exacto se confirmará con el cliente.
- **Integraciones externas, firma electrónica y app móvil:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Consulta y edita la ficha en todas las sucursales. |
| Administración | Consulta y edita la ficha de pacientes de sus sucursales. |
| Recepción | Crea la ficha al ingreso, registra autorizaciones y actualiza datos de contexto; consulta la ficha. |
| Profesionales y padres | **No usan el sistema**. El profesional no registra aquí la entrevista clínica. |

### Sucursales aplicables

- **La ficha de ingreso pertenece al paciente** (global) pero se registra en el contexto de la sucursal donde se produce el ingreso.
- **La consulta se acota por sucursal:** un usuario solo ve fichas de pacientes con inscripciones en sus sucursales.
- **Los datos de contexto y salud referidos** se tratan como sensibles y se protegen por rol y bitácora.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que la ficha de ingreso es administrativa y de contexto, y que la entrevista clínica con los padres se registra en el módulo 06 y en el módulo 11 (cuando esté disponible). Debe confirmarse qué consentimientos y autorizaciones requiere el cliente y si se necesita registrar el uso de imágenes. También debe confirmarse si la ficha es por paciente (global) o por ingreso a cada sucursal.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita registrar el motivo de consulta y el contexto familiar al primer contacto, para que la inscripción y el agendamiento se hagan con información suficiente y para que el profesional asignado cuente con datos de contexto antes de la entrevista. Una ficha digital elimina la dependencia del papel, permite consultarla desde cualquier sucursal y deja constancia de las autorizaciones firmadas por la familia.

### 3.2 Justificación en la Atención al Niño y su Familia

La ficha de ingreso permite que el centro reciba al niño con información previa, lo que mejora la calidad del primer contacto y de la entrevista. La familia percibe un proceso ordenado y siente que sus datos y autorizaciones se resguardan. En caso de que el niño cambie de sucursal, la ficha viaja con el paciente y evita repetir información.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** interés superior del niño, derecho a ser escuchado y protección integral; la ficha recoge la voz de la familia y del niño en el motivo de consulta [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** conservación de registros y secreto profesional aplicable a los datos referidos en la ficha [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales, incluidos datos sensibles [verificar vigencia].
- **Ley 223 General para Personas con Discapacidad:** cuando corresponda, registro de condiciones y necesidades de apoyo [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

El registro de consentimientos y autorizaciones es un mecanismo concreto para demostrar el cumplimiento del deber de informar y de obtener el consentimiento de la familia.

### 3.4 Justificación Tecnológica

Frente al papel, la ficha digital aporta: disponibilidad desde cualquier sucursal; edición controlada con bitácora; asociación explícita al paciente y sus tutores; registro de consentimientos con fecha y usuario; y exportación a PDF para el legajo físico. Se reduce la pérdida de información y se prepara el terreno para los módulos clínicos.

---

## 4. Funciones Principales

### FP-01: Registrar ficha de ingreso
- **Descripción:** Permite registrar los datos de contexto del ingreso (motivo de consulta, persona que deriva, composición familiar, observaciones) asociados al paciente y a sus tutores.
- **Actor principal:** Recepción.
- **Resultado esperado:** Ficha de ingreso creada, consultable y con bitácora, que acompaña al paciente en su trayectoria.

### FP-02: Registrar consentimientos y autorizaciones
- **Descripción:** Permite registrar los consentimientos y autorizaciones de los tutores con fecha, tipo y usuario que los registra.
- **Actor principal:** Recepción (y Administración).
- **Resultado esperado:** Autorizaciones registradas y consultables, con trazabilidad para auditoría.

### FP-03: Actualizar la ficha
- **Descripción:** Permite modificar los datos de contexto y agregar observaciones con el paso del tiempo, registrando cada cambio en bitácora.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Ficha actualizada y con historial completo de cambios.

### FP-04: Consultar y exportar la ficha
- **Descripción:** Permite consultar la ficha por roles autorizados dentro del alcance por sucursal y exportarla a PDF para el legajo físico.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Ficha disponible y exportable, con registro de consultas y exportaciones.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear ficha de ingreso | El sistema debe permitir registrar la ficha de ingreso asociada a un paciente existente, con motivo de consulta, persona que deriva, composición familiar y observaciones. | Alta | Recepción |
| RF-002 | Validar campos obligatorios | El sistema debe exigir motivo de consulta y fecha de ingreso; los demás campos serán opcionales según lo confirme el cliente. | Alta | Sistema |
| RF-003 | Registrar consentimientos | El sistema debe permitir registrar consentimientos y autorizaciones de los tutores, con tipo, fecha y usuario que los registra. | Alta | Recepción, Administración |
| RF-004 | Consultar consentimientos vigentes | El sistema debe mostrar los consentimientos y autorizaciones vigentes del paciente y sus tutores. | Alta | Recepción, Administración, Superadmin |
| RF-005 | Editar ficha | El sistema debe permitir modificar la ficha, registrando valores anteriores y nuevos en bitácora. | Alta | Recepción, Administración, Superadmin |
| RF-006 | Agregar observaciones | El sistema debe permitir agregar observaciones fechadas a la ficha sin borrar las anteriores. | Media | Recepción, Administración |
| RF-007 | Registrar contactos de emergencia | El sistema debe permitir registrar contactos de emergencia adicionales al tutor, con nombre, parentesco y teléfono. | Media | Recepción, Administración |
| RF-008 | Registrar persona que deriva | El sistema debe permitir registrar la persona o institución que deriva o recomienda al paciente, con nombre y referencia de contacto. | Media | Recepción |
| RF-009 | Consultar ficha | El sistema debe mostrar la ficha completa por roles autorizados y dentro del alcance por sucursal. | Alta | Recepción, Administración, Superadmin |
| RF-010 | Exportar ficha a PDF | El sistema debe permitir exportar la ficha a PDF para adjuntar al legajo físico, con fecha y usuario de exportación. | Baja | Recepción, Administración, Superadmin |
| RF-011 | Registrar bitácora | El sistema debe registrar altas, ediciones, cambios de consentimientos y consultas en bitácora con valores anteriores y nuevos. | Alta | Sistema |
| RF-012 | Vincular con la entrevista del módulo 06 | El sistema debe permitir referenciar desde la ficha el servicio de entrevista con los padres cuando se realice, sin duplicar el registro clínico. | Media | Recepción, Administración |
| RF-013 | Restringir acceso por rol y sucursal | El sistema debe impedir el acceso a roles no autorizados y acotar la consulta a las sucursales del usuario. | Alta | Sistema |
| RF-014 | Bloquear eliminación con historial | El sistema debe impedir la eliminación física de la ficha cuando el paciente tiene inscripciones, turnos o pagos; solo permitirá marcar como inactiva/archivada con motivo. | Alta | Sistema |
| RF-015 | Marcar datos sensibles | El sistema debe permitir marcar campos de la ficha como sensibles, restringiendo su visualización a roles autorizados. | Media | Recepción, Administración |

> [SUPUESTO A VALIDAR CON EL CLIENTE] La lista exacta de campos de la ficha, los consentimientos requeridos y el uso de imágenes deben confirmarse con el cliente. Se asume que la ficha puede actualizarse con el tiempo y que no se borra, solo se complementa. También debe confirmarse si la ficha es por paciente (global) o por ingreso a cada sucursal.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La consulta de la ficha debe responder en menos de 1,5 segundos incluyendo tutores, consentimientos y observaciones.
- **RNF-002:** La exportación de la ficha a PDF debe completarse en menos de 3 segundos.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen rol autorizado.
- **RNF-004:** La ficha contiene datos de salud referidos y datos de contexto familiar; se tratan como sensibles y se protegen por rol y sucursal.
- **RNF-005:** Las exportaciones a PDF quedan registradas en bitácora con usuario, fecha y hora.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir fichas, consentimientos y observaciones.

### 6.4 Usabilidad

- **RNF-007 (bis):** La ficha debe seguir un orden lógico de captura y permitir guardar avances antes de completarla.
- **RNF-008:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-009:** El modelo debe soportar el crecimiento en fichas y observaciones sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-010:** La ficha debe exponerse vía API REST a pacientes, inscripciones, entrevistas y reportes, y exportarse a PDF.

### 6.7 Mantenibilidad

- **RNF-011:** Los campos y consentimientos de la ficha deben ser configurables por el cliente, sin valores fijos en el código.

### 6.8 Trazabilidad y Auditoría

- **RNF-012:** Toda alta, edición, consentimiento y consulta debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Ficha asociada al paciente — La ficha de ingreso siempre está vinculada a un paciente existente y a sus tutores; no existe ficha sin paciente.
- **RN-002:** Motivo de consulta obligatorio — Toda ficha debe registrar al menos el motivo de consulta y la fecha de ingreso.
- **RN-003:** Observaciones no destructivas — Las observaciones se agregan con fecha y no se borran; los errores se corrigen con nuevas observaciones que referencian la anterior.
- **RN-004:** Consentimientos vigentes — Los consentimientos y autorizaciones se registran con fecha y tipo, y se consultan como vigentes hasta que el cliente defina su vencimiento o revocación.
- **RN-005:** No eliminación con historial — La ficha no se elimina; solo puede archivarse o marcarse como inactiva con motivo.
- **RN-006:** Vinculación con la entrevista — La entrevista terapéutica es un servicio con costo del módulo 06; la ficha de ingreso no la reemplaza ni la duplica.
- **RN-007:** Datos sensibles marcados — Los campos marcados como sensibles solo se muestran a roles autorizados.
- **RN-008:** Alcance por rol y sucursal — Recepción y Administración acceden a fichas de pacientes de sus sucursales; Superadmin accede a todas.
- **RN-009:** Exportación auditada — Toda exportación de la ficha queda registrada en bitácora.
- **RN-010:** Autorizaciones de imagen — Si el cliente lo requiere, la autorización de uso de imagen se registra como consentimiento específico y su ausencia bloquea el uso de imágenes del niño. [SUPUESTO A VALIDAR CON EL CLIENTE]

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-010 (uso de imágenes) debe confirmarse; el contexto no lo menciona y puede no aplicar. También debe confirmarse si la ficha es por paciente o por ingreso a cada sucursal.

---

## 8. Casos de Uso Principales

### CU-01: Registrar ficha de ingreso al alta del paciente

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar ficha de ingreso al alta del paciente |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Paciente creado; tutores asociados |
| **Postcondiciones** | Ficha de ingreso creada, consultable y con bitácora |
| **Trigger** | Recepción completa el alta del paciente y abre la ficha de ingreso |

**Flujo Principal:**
1. Recepción abre la ficha del paciente recién creado y selecciona “Ficha de ingreso”.
2. El sistema muestra el formulario con los campos de contexto.
3. Recepción ingresa motivo de consulta, persona que deriva, composición familiar, observaciones y fecha de ingreso.
4. Recepción registra los consentimientos y autorizaciones de los tutores.
5. El sistema valida campos obligatorios.
6. El sistema guarda la ficha y registra en bitácora.
7. Recepción es redirigida a la ficha resumen o a la inscripción.

**Flujos Alternativos:**
- **FA-01:** La ficha puede guardarse como borrador y completarse después, siempre que el paciente no se inactive.

**Flujos de Excepción:**
- **FE-01:** Falta el motivo de consulta → el sistema bloquea el guardado e indica el campo.
- **FE-02:** El paciente no tiene tutores → el sistema impide registrar consentimientos y sugiere completarlos.
- **FE-03:** Falla de conexión → el sistema informa y permite reintentar sin perder datos.

### CU-02: Registrar consentimientos y autorizaciones

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Registrar consentimientos y autorizaciones |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Ficha de ingreso creada y tutores asociados |
| **Postcondiciones** | Consentimientos registrados con fecha y usuario, consultables como vigentes |
| **Trigger** | La familia firma las autorizaciones y Recepción las registra |

**Flujo Principal:**
1. Recepción abre la ficha del paciente y la sección de consentimientos.
2. El sistema muestra los tipos de consentimiento configurados.
3. Recepción selecciona el consentimiento, indica el tutor que autoriza y la fecha.
4. El sistema guarda el consentimiento y registra en bitácora.
5. El sistema muestra el consentimiento como vigente.

**Flujos Alternativos:**
- **FA-01:** Recepción registra la negativa del tutor a un consentimiento; el sistema la deja registrada y visible.

**Flujos de Excepción:**
- **FE-01:** Tutor no asociado al paciente → el sistema impide registrar el consentimiento.
- **FE-02:** Fecha inválida → el sistema rechaza el guardado.

### CU-03: Consultar y exportar la ficha

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Consultar y exportar la ficha |
| **Actor Principal** | Recepción / Administración / Superadmin |
| **Actores Secundarios** | Sistema (scoping y bitácora) |
| **Precondiciones** | Sesión activa; paciente dentro del alcance del usuario |
| **Postcondiciones** | Ficha visible y/o exportada a PDF, con registro en bitácora |
| **Trigger** | El usuario abre la ficha del paciente y selecciona la sección de ingreso |

**Flujo Principal:**
1. El usuario abre la ficha del paciente.
2. El sistema muestra la ficha de ingreso con datos y consentimientos.
3. El usuario puede exportar a PDF.
4. El sistema genera el archivo y registra la exportación en bitácora.

**Flujos Alternativos:**
- **FA-01:** El usuario solo consulta sin exportar.

**Flujos de Excepción:**
- **FE-01:** Falla la generación del PDF → el sistema informa y permite reintentar.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de ficha
- **Given** un paciente con tutores asociados,
- **When** Recepción registra motivo de consulta y fecha de ingreso,
- **Then** el sistema crea la ficha y la registra en bitácora.

### CA-002: Motivo de consulta obligatorio
- **Given** el formulario de ficha,
- **When** Recepción intenta guardar sin motivo de consulta,
- **Then** el sistema bloquea el guardado e indica el campo obligatorio.

### CA-003: Registro de consentimientos
- **Given** un paciente con tutores,
- **When** Recepción registra un consentimiento con tipo, tutor y fecha,
- **Then** el sistema lo guarda y lo muestra como vigente.

### CA-004: Observaciones no destructivas
- **Given** una ficha con observaciones previas,
- **When** Recepción agrega una nueva observación,
- **Then** el sistema agrega la observación con fecha y conserva las anteriores.

### CA-005: Ficha sin tutores
- **Given** un paciente sin tutores,
- **When** Recepción intenta registrar consentimientos,
- **Then** el sistema lo impide y sugiere completar tutores.

### CA-006: Consulta restringida por rol
- **Given** un usuario de Recepción asignado solo a “Yacuiba Centro”,
- **When** busca fichas de pacientes de otra sucursal,
- **Then** el sistema no las muestra.

### CA-007: Exportación auditada
- **Given** un usuario autorizado,
- **When** exporta la ficha a PDF,
- **Then** el sistema genera el archivo y registra la exportación en bitácora.

### CA-008: Bloqueo de eliminación
- **Given** una ficha asociada a un paciente con inscripciones,
- **When** un usuario intenta eliminarla,
- **Then** el sistema rechaza la eliminación y ofrece archivarla con motivo.

### CA-009: Vinculación con entrevista del módulo 06
- **Given** una ficha de ingreso registrada,
- **When** se registra el servicio de entrevista con los padres,
- **Then** el sistema permite referenciar la entrevista desde la ficha sin duplicar el registro clínico.

### CA-010: Campos sensibles
- **Given** un campo de la ficha marcado como sensible,
- **When** un rol no autorizado consulta la ficha,
- **Then** el sistema oculta el valor del campo y lo indica.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Ambigüedad entre ficha de ingreso, entrevista con padres y evaluación clínica | Alta | Alto | Alto | Definir con el cliente el alcance de cada registro y documentar la separación. |
| RT-002 | Datos sensibles en la ficha expuestos por errores de scoping | Media | Alto | Alto | Validación en backend, marca de campos sensibles y pruebas de permisos. |
| RT-003 | Consentimientos registrados sin firma física o digital | Media | Medio | Medio | Dejar constancia del tipo y fecha; evaluar firma electrónica en Etapa 2 o 3. |
| RT-004 | Ficha desactualizada por falta de mantenimiento | Media | Medio | Medio | Recordatorio de revisión periódica y bitácora de cambios. |
| RT-005 | Migración desde papel incompleta que deje fichas sin datos mínimos | Alta | Medio | Alto | Plantilla de migración, validación con el cliente y campos mínimos obligatorios. |
| RT-006 | Confusión con el módulo 06 por la palabra “entrevista” | Media | Medio | Medio | Nombrar claramente “Ficha de ingreso” en la interfaz y documentar la diferencia. |
| RT-007 | Uso de imágenes sin autorización por falta de control | Baja | Alto | Medio | Regla RN-010 y bloqueo de uso de imágenes sin consentimiento (si aplica). |
| RT-008 | Dependencia de una sola persona para administrar la ficha | Media | Medio | Medio | Documentación y transferencia de conocimiento. |

---

**Fin del documento**