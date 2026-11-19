# Registro de pacientes
**Módulo:** Pacientes y tutores
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra el registro de los pacientes —niños y niñas que reciben servicios en TalitaCumi— con sus datos de identificación, datos de salud relevantes para la atención, estado del paciente (activo, en pausa o retirado) y vínculo con sus tutores. El paciente es una entidad **global**: no se duplica por sucursal, aunque sus inscripciones, turnos y pagos sí pertenezcan a una sede.

Hoy los datos de los pacientes viven en cuadernos, fichas de papel y mensajes de WhatsApp, con riesgo de duplicación, pérdida de datos y dificultad para localizar la ficha completa de un niño. Este submódulo reemplaza ese registro disperso por una ficha única, con datos mínimos obligatorios, validaciones de unicidad, control de estado y bitácora de cambios. Se integra con Registro de tutores y contactos, Ficha de ingreso y entrevista, Búsqueda y ficha resumen, Inscripciones, Agenda y turnos, Pagos y caja, y con los módulos de evaluaciones y resultados.

El valor que aporta es triple: identidad única del paciente (no se duplica), base para la continuidad de la atención (inscripciones, turnos y pagos referencian al mismo paciente) y protección de datos de menores (acceso por rol y bitácora de cada consulta y cambio).

---

## 2. Alcance

### Incluido en este módulo

- Alta, edición, consulta y cambio de estado de pacientes.
- Datos mínimos del paciente: nombres y apellidos, fecha de nacimiento, edad calculada, sexo, documento de identidad del menor (si aplica), tipo de diagnóstico (catálogo del módulo 05) y estado (activo, en pausa, retirado).
- Asociación del paciente a uno o más tutores (gestionada en el submódulo 02).
- Datos complementarios: dirección, alergias o condiciones relevantes, observaciones generales.
- Validación de unicidad por documento y por combinación nombres + fecha de nacimiento + tutor principal.
- Búsqueda, listado y filtrado por estado, tipo de diagnóstico y sucursal de inscripción vigente.
- Registro en bitácora de altas, ediciones y cambios de estado.
- Bloqueo de eliminación física cuando el paciente tiene inscripciones, turnos o pagos asociados; solo se permite cambiar de estado.
- Control de acceso por rol y bitácora de consultas a datos sensibles.
- Exportación de listados a PDF y Excel (respetando el alcance del usuario).

### Excluido de este módulo

- **Tutores y contactos:** corresponde al submódulo `02_registro_tutores_contactos.md`.
- **Ficha de ingreso y entrevista inicial (datos de contexto familiar y motivo de consulta):** corresponde al submódulo `03_ficha_ingreso_entrevista.md`.
- **Búsqueda global y ficha resumen con estado, servicios, pagos y asistencia:** corresponde al submódulo `04_busqueda_ficha_resumen.md`.
- **Inscripciones, paquetes de sesiones y entrevista/diagnóstico como servicio:** corresponde al módulo 06.
- **Resultados clínicos, informes y derivaciones:** corresponde al módulo 11 (Etapa 2 o 3 según confirmación).
- **Cuentas de acceso al sistema para pacientes o tutores:** fuera de alcance por el contexto.
- **Integraciones externas y app móvil:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita y cambia el estado de pacientes; puede eliminar lógicamente (desactivar) cuando no hay historial. |
| Administración | Consulta y edita datos del paciente de sus sucursales; **no crea** pacientes (ver [SUPUESTO A VALIDAR CON EL CLIENTE]). |
| Recepción | Crea y edita pacientes al momento del ingreso; cambia el estado cuando corresponde; consulta fichas. |
| Profesionales y padres | **No usan el sistema**. No acceden a la ficha del paciente. |

### Sucursales aplicables

- **El paciente es global:** no se duplica por sucursal.
- **La sucursal aplica a inscripciones, turnos, pagos y caja**, no al paciente en sí.
- **El estado del paciente es global:** activo, en pausa o retirado lo cambia una persona autorizada y afecta a todas las sucursales donde tenga inscripciones.
- **Los datos de salud y del menor** se protegen por rol y sucursal, aunque el paciente sea global.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que Recepción puede crear pacientes y que Administración solo edita. Debe confirmarse si Administración puede crear pacientes en casos excepcionales. También se asume que el documento de identidad del menor es opcional (algunos niños no lo tienen al momento del ingreso) y que basta la identificación por nombres + fecha de nacimiento + tutor principal.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita registrar a un niño en pocos minutos, con los datos necesarios para inscribirlo y agendarlo, y poder localizarlo después por nombre, documento o tutor. Con una ficha única, se evita duplicar pacientes cuando vuelven a otra sucursal o cuando el tutor cambia de teléfono, y se facilita la consulta desde cualquier sede. El control de estado permite reflejar pausas (por ejemplo, viaje familiar) y retiros sin perder el historial.

### 3.2 Justificación en la Atención al Niño y su Familia

Una ficha única garantiza continuidad: el niño es el mismo a lo largo del tiempo, aunque cambie de servicio o de sucursal, y su historia no se fragmenta. La familia no tiene que repetir datos básicos en cada visita y el centro puede atender mejor al conocer condiciones relevantes (alergias, diagnósticos previos) y al tutor principal.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** protección integral, interés superior del niño y resguardo de la información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** secreto profesional y conservación de registros clínicos asociados al paciente [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales, incluidos datos sensibles de menores [verificar vigencia].
- **Ley 223 General para Personas con Discapacidad:** trato adecuado y resguardo de la información cuando el paciente tiene alguna discapacidad [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad y protección de la niñez [verificar vigencia y articulado aplicable].

La ficha única con bitácora de accesos y cambios es un mecanismo concreto para cumplir con el deber de resguardo y con el interés superior del niño.

### 3.4 Justificación Tecnológica

Frente al registro manual, la ficha del paciente aporta: identificador único; validación de unicidad; relación explícita con tutores; bitácora de consultas y cambios; bloqueo de eliminación con historial; y base para reportes de asistencia, pagos y servicios por paciente. Se reduce la duplicación y se prepara la información para los módulos de inscripciones, agenda y reportes.

---

## 4. Funciones Principales

### FP-01: Registrar paciente
- **Descripción:** Permite dar de alta un paciente con sus datos mínimos, tipo de diagnóstico y estado inicial activo. El sistema valida unicidad y campos obligatorios.
- **Actor principal:** Recepción (y Superadmin).
- **Resultado esperado:** Paciente creado con identificador único, disponible para asociar tutores e inscribirlo en servicios.

### FP-02: Consultar y buscar pacientes
- **Descripción:** Permite listar y filtrar pacientes por estado, tipo de diagnóstico y sucursal de inscripción vigente, y abrir la ficha con datos, tutores y observaciones.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Vista consolidada y paginada con opción de exportar a PDF y Excel.

### FP-03: Actualizar datos y estado del paciente
- **Descripción:** Permite modificar datos de identificación, diagnóstico y observaciones, y cambiar el estado entre activo, en pausa y retirado, registrando cada cambio en bitácora.
- **Actor principal:** Recepción (datos y estado); Administración (datos de sus sucursales); Superadmin (todo).
- **Resultado esperado:** Paciente actualizado con historial completo de cambios y estado vigente aplicado en todo el sistema.

### FP-04: Proteger el acceso a datos del menor
- **Descripción:** Expone la ficha del paciente solo a los roles autorizados y dentro de las sucursales asignadas, registrando cada consulta en bitácora.
- **Actor principal:** Sistema.
- **Resultado esperado:** Acceso controlado a datos de menores y trazabilidad de cada consulta.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear paciente | El sistema debe permitir registrar un paciente con nombres, apellidos, fecha de nacimiento, sexo, tipo de diagnóstico y estado inicial activo. | Alta | Recepción, Superadmin |
| RF-002 | Calcular edad | El sistema debe calcular la edad del paciente a partir de la fecha de nacimiento y mostrarla actualizada en la ficha. | Alta | Sistema |
| RF-003 | Validar unicidad | El sistema debe rechazar pacientes duplicados según documento (si existe) o combinación nombres + apellidos + fecha de nacimiento + tutor principal. | Alta | Sistema |
| RF-004 | Validar campos obligatorios | El sistema debe exigir nombres, apellidos, fecha de nacimiento, sexo, tipo de diagnóstico y al menos un tutor asociado para activar al paciente. | Alta | Sistema |
| RF-005 | Listar y buscar pacientes | El sistema debe mostrar un listado paginado con filtros por estado, tipo de diagnóstico y sucursal de inscripción vigente, respetando el alcance del usuario. | Alta | Recepción, Administración, Superadmin |
| RF-006 | Consultar ficha de paciente | El sistema debe mostrar la ficha con datos, tutores, observaciones y estado, con fecha de última modificación. | Alta | Recepción, Administración, Superadmin |
| RF-007 | Editar datos del paciente | El sistema debe permitir modificar los datos del paciente, registrando valores anteriores y nuevos en bitácora. | Alta | Recepción, Administración, Superadmin |
| RF-008 | Cambiar estado del paciente | El sistema debe permitir cambiar el estado entre activo, en pausa y retirado, exigiendo motivo cuando se pasa a retirado o de retirado a activo. | Alta | Recepción, Superadmin |
| RF-009 | Bloquear eliminación con historial | El sistema debe impedir la eliminación física de un paciente con inscripciones, turnos o pagos asociados; solo permitirá cambiar su estado. | Alta | Sistema |
| RF-010 | Asociar tutores al paciente | El sistema debe permitir asociar uno o más tutores al paciente, marcando uno como tutor principal (gestionado con el submódulo 02). | Alta | Recepción, Superadmin |
| RF-011 | Registrar observaciones y condiciones relevantes | El sistema debe permitir registrar alergias, condiciones médicas relevantes y observaciones generales del paciente, con marca de sensibilidad. | Media | Recepción, Administración |
| RF-012 | Registrar bitácora de cambios | El sistema debe registrar altas, ediciones y cambios de estado con usuario, fecha, hora y valores anteriores/nuevos. | Alta | Sistema |
| RF-013 | Registrar consultas a la ficha | El sistema debe registrar cada consulta a la ficha del paciente como evento auditado, con usuario, fecha y hora. | Alta | Sistema |
| RF-014 | Restringir acceso por rol | El sistema debe impedir que roles no autorizados accedan a la ficha del paciente, y acotar la consulta a las sucursales del usuario. | Alta | Sistema |
| RF-015 | Exportar listado de pacientes | El sistema debe permitir exportar el listado filtrado a PDF y Excel, respetando el alcance del usuario. | Baja | Recepción, Administración, Superadmin |
| RF-016 | Alerta de duplicado potencial | Al crear un paciente, el sistema debe advertir si detecta coincidencias parciales (por ejemplo, mismo apellido + fecha de nacimiento + tutor) para que el usuario confirme. | Media | Sistema |

> [SUPUESTO A VALIDAR CON EL CLIENTE] El tipo de diagnóstico al alta puede definirse después de la evaluación; en ese caso, el campo debe ser opcional al alta y obligatorio al iniciar terapia. Confirmar con el cliente. También debe confirmarse si el paciente requiere documento de identidad obligatorio o solo cuando esté disponible.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La búsqueda de pacientes por nombre o documento debe responder en menos de 1 segundo con hasta 5 000 pacientes registrados.
- **RNF-002:** La apertura de la ficha del paciente debe cargar en menos de 1,5 segundos incluyendo tutores y datos básicos.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen sesión autenticada con rol autorizado.
- **RNF-004:** Los datos clínicos y de salud del paciente deben tratarse como sensibles: acceso por rol y sucursal, cifrado en reposo si el cliente lo requiere, y bitácora de consultas.
- **RNF-005:** Las contraseñas y datos de tutores no se muestran completos a roles no autorizados; los teléfonos se muestran según política acordada.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir pacientes y sus históricos; la restauración debe preservar la integridad referencial con tutores e inscripciones.

### 6.4 Usabilidad

- **RNF-008:** La ficha debe usar el vocabulario del negocio (paciente, tutor, diagnóstico, estado) y permitir registrar un paciente en pocos pasos.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en pacientes y en número de sucursales sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El módulo debe exponer la ficha del paciente vía API REST a inscripciones, agenda, pagos, reportes y evaluaciones, y exportar a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** El esquema se gestionará con migraciones versionadas; el tipo de diagnóstico provendrá del catálogo configurable del módulo 05.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, edición, cambio de estado y consulta a la ficha debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Paciente global — El paciente no se duplica por sucursal; las inscripciones, turnos y pagos sí pertenecen a una sucursal.
- **RN-002:** Unicidad de paciente — Un paciente se identifica por documento (si existe) o por la combinación nombres + apellidos + fecha de nacimiento + tutor principal.
- **RN-003:** Tutor obligatorio — Todo paciente activo debe tener al menos un tutor asociado; sin tutor no puede activarse ni inscribirse.
- **RN-004:** Estados del paciente — Solo se admiten tres estados: activo, en pausa y retirado; el cambio lo realiza una persona autorizada.
- **RN-005:** Motivo de cambio de estado — El paso a retirado y el retorno desde retirado a activo requieren motivo registrado en bitácora.
- **RN-006:** No eliminación con historial — Un paciente con inscripciones, turnos o pagos no puede eliminarse; solo desactivarse mediante el estado retirado.
- **RN-007:** Efecto del estado en la operación — Un paciente retirado no puede recibir nuevas inscripciones ni turnos; un paciente en pausa conserva sus inscripciones pero no se le generan nuevos turnos. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **RN-008:** Datos sensibles — Alergias y condiciones médicas se tratan como datos sensibles y se muestran solo a roles autorizados.
- **RN-009:** Bitácora de consultas — Cada acceso a la ficha del paciente queda registrado como evento auditado.
- **RN-010:** Alcance por rol y sucursal — Recepción y Administración acceden a los pacientes de sus sucursales; Superadmin accede a todos.
- **RN-011:** Tipo de diagnóstico — El tipo de diagnóstico se elige del catálogo del módulo 05; puede actualizarse cuando se complete la evaluación.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-007 (efecto de pausa/retiro en la generación de turnos) debe confirmarse; podría requerirse que el paciente en pausa conserve turnos futuros con marca especial. También debe confirmarse la RN-005 (motivo en pausa).

---

## 8. Casos de Uso Principales

### CU-01: Registrar paciente

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar paciente |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; catálogo de tipos de diagnóstico disponible; al menos un tutor registrado o en curso |
| **Postcondiciones** | Paciente creado en estado activo, asociado a un tutor principal y con bitácora |
| **Trigger** | Recepción selecciona “Nuevo paciente” al recibir a la familia |

**Flujo Principal:**
1. Recepción selecciona “Nuevo paciente”.
2. El sistema muestra el formulario de alta.
3. Recepción ingresa nombres, apellidos, fecha de nacimiento, sexo, tipo de diagnóstico y observaciones.
4. El sistema calcula la edad y muestra posibles coincidencias (alerta de duplicado potencial).
5. Recepción asocia un tutor principal (existente o creado en el mismo flujo).
6. El sistema valida campos obligatorios y unicidad.
7. El sistema guarda el paciente en estado activo y registra en bitácora.
8. Recepción es redirigida a la ficha del paciente.

**Flujos Alternativos:**
- **FA-01:** El paciente se registra sin tipo de diagnóstico (pendiente de evaluación); el sistema lo marca como “sin diagnóstico definido”. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **FA-02:** El tutor principal ya existe en el sistema y se asocia sin crearlo.

**Flujos de Excepción:**
- **FE-01:** Detectado posible duplicado → el sistema muestra la ficha existente y solicita confirmar si es el mismo paciente.
- **FE-02:** Falta un campo obligatorio → el sistema bloquea el guardado e indica el campo.
- **FE-03:** Falla de conexión → el sistema informa y permite reintentar sin perder datos.

### CU-02: Editar paciente y cambiar estado

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Editar paciente y cambiar estado |
| **Actor Principal** | Recepción / Administración / Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; paciente dentro del alcance del usuario |
| **Postcondiciones** | Datos o estado actualizados con bitácora y motivo cuando corresponda |
| **Trigger** | El usuario abre la ficha del paciente y elige “Editar” o “Cambiar estado” |

**Flujo Principal:**
1. El usuario busca y abre la ficha del paciente.
2. El sistema muestra datos, tutores y estado actual.
3. El usuario selecciona “Editar” y modifica campos permitidos según su rol.
4. El sistema valida y guarda, registrando en bitácora valores anteriores y nuevos.
5. Si el usuario cambia el estado, el sistema solicita motivo.
6. El usuario ingresa el motivo y confirma.
7. El sistema aplica el nuevo estado y registra la acción en bitácora.

**Flujos Alternativos:**
- **FA-01:** El usuario cancela; el sistema mantiene los datos y estado anteriores.

**Flujos de Excepción:**
- **FE-01:** Motivo vacío cuando se pasa a retirado → el sistema no permite continuar.
- **FE-02:** Cambio a estado activo desde retirado sin motivo → el sistema lo exige.
- **FE-03:** Otro usuario modificó la ficha mientras se editaba → el sistema advierte el conflicto.

### CU-03: Consultar ficha del paciente

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Consultar ficha del paciente |
| **Actor Principal** | Recepción / Administración / Superadmin |
| **Actores Secundarios** | Sistema (scoping y bitácora) |
| **Precondiciones** | Sesión activa; paciente dentro del alcance del usuario |
| **Postcondiciones** | Ficha visible con datos, tutores y estado; consulta registrada en bitácora |
| **Trigger** | El usuario busca un paciente y abre su ficha |

**Flujo Principal:**
1. El usuario busca al paciente por nombre, documento o tutor.
2. El sistema aplica el scoping por sucursal y muestra resultados.
3. El usuario abre la ficha del paciente.
4. El sistema muestra datos, tutores y estado, y registra la consulta en bitácora.

**Flujos Alternativos:**
- **FA-01:** El usuario puede ver la ficha resumen (submódulo 04) para una vista consolidada.

**Flujos de Excepción:**
- **FE-01:** El paciente no existe o está fuera del alcance del usuario → el sistema informa sin revelar datos.
- **FE-02:** Falla al registrar la consulta en bitácora → el sistema registra alerta técnica y muestra la ficha con advertencia. [SUPUESTO A VALIDAR CON EL CLIENTE]

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de paciente
- **Given** un usuario de Recepción autenticado y datos únicos,
- **When** registra un paciente con todos los campos obligatorios y tutor principal,
- **Then** el sistema crea el paciente activo y lo muestra en el listado con bitácora.

### CA-002: Rechazo por duplicado
- **Given** un paciente con la misma combinación de nombres + apellidos + fecha de nacimiento + tutor principal,
- **When** el usuario intenta crear otro,
- **Then** el sistema muestra la ficha existente y solicita confirmar si es un duplicado.

### CA-003: Alerta de duplicado potencial
- **Given** un paciente con mismo apellido, fecha de nacimiento y tutor,
- **When** el usuario intenta crear un nuevo paciente,
- **Then** el sistema advierte la posible coincidencia y permite continuar solo si se confirma.

### CA-004: Tutor obligatorio
- **Given** el formulario de alta sin tutor asociado,
- **When** el usuario intenta activar al paciente,
- **Then** el sistema bloquea la activación e indica el requisito.

### CA-005: Cálculo de edad
- **Given** un paciente con fecha de nacimiento registrada,
- **When** se abre la ficha,
- **Then** el sistema muestra la edad calculada y actualizada.

### CA-006: Cambio de estado con motivo
- **Given** un paciente activo,
- **When** el usuario lo pasa a retirado con motivo,
- **Then** el sistema cambia el estado y registra el motivo en bitácora.

### CA-007: Efecto del estado en la operación
- **Given** un paciente en estado retirado,
- **When** Recepción intenta inscribirlo en un nuevo servicio,
- **Then** el sistema rechaza la inscripción e informa el motivo.

### CA-008: Bloqueo de eliminación con historial
- **Given** un paciente con inscripciones y pagos registrados,
- **When** el usuario intenta eliminarlo,
- **Then** el sistema rechaza la eliminación y sugiere desactivarlo (estado retirado).

### CA-009: Bitácora de consultas
- **Given** un usuario que consulta la ficha de un paciente,
- **When** abre la ficha,
- **Then** el sistema registra la consulta con usuario, fecha y hora.

### CA-010: Scoping por sucursal
- **Given** un usuario asignado solo a “Yacuiba Centro”,
- **When** busca pacientes por sucursal de inscripción,
- **Then** el sistema muestra solo los pacientes con inscripciones vigentes en esa sucursal.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Duplicación masiva de pacientes en la migración desde cuadernos | Alta | Alto | Alto | Plantilla de migración, normalización de nombres y fecha de nacimiento, alerta de duplicado potencial, validación con el cliente. |
| RT-002 | Exposición de datos de menores por errores de scoping | Media | Alto | Alto | Validación en backend, pruebas de permisos y bitácora de consultas. |
| RT-003 | Falta de documento de identidad que dificulte la identificación | Alta | Medio | Alto | Regla de unicidad alternativa y alerta de duplicado potencial. |
| RT-004 | Tipo de diagnóstico desconocido al alta que bloquee la inscripción | Media | Medio | Medio | Permitir alta con diagnóstico pendiente y exigirlo antes de iniciar terapia. |
| RT-005 | Cambios de estado mal gestionados que dejen pacientes “invisibles” | Media | Medio | Medio | Validar motivo, bitácora y reporte de pacientes en pausa/retirados. |
| RT-006 | Datos sensibles en exportaciones no controladas | Media | Medio | Medio | Restringir exportación a roles autorizados y registrar la exportación en bitácora. |
| RT-007 | Dependencia de una sola persona para administrar la ficha | Media | Medio | Medio | Documentación y transferencia de conocimiento. |
| RT-008 | Ambigüedad sobre qué rol crea pacientes | Alta | Bajo | Medio | Confirmar con el cliente y ajustar la matriz de permisos. |

---

**Fin del documento**