# Registro de tutores y contactos
**Módulo:** Pacientes y tutores
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra los tutores —padres, madres, apoderados o responsables legales— de los pacientes de TalitaCumi, con sus datos de identificación, teléfonos de contacto, correo (opcional), parentesco con el paciente y estado. Los tutores son una entidad **global** y pueden estar asociados a uno o varios pacientes; un paciente puede tener varios tutores, con uno marcado como **tutor principal**.

Hoy los contactos de las familias viven en cuadernos y en la memoria del personal, lo que dificulta localizar a un tutor cuando hay una emergencia, cuando cambia un turno o cuando se debe avisar del próximo pago. Este submódulo reemplaza ese registro disperso por una ficha única de tutor con contactos validados, teléfono principal visible en la alerta de próximo pago y bitácora de cambios. Se integra con Registro de pacientes, Ficha de ingreso y entrevista, Alertas de próximo pago, Pagos y caja, e Inscripciones.

El valor que aporta es doble: por un lado, garantiza que el centro pueda contactar a la familia cuando lo necesite; por otro, permite construir la alerta de próximo pago con el teléfono del tutor y el botón de WhatsApp (sin envío automático), cumpliendo el alcance definido.

---

## 2. Alcance

### Incluido en este módulo

- Alta, edición, consulta y cambio de estado (activo/inactivo) de tutores.
- Datos mínimos del tutor: nombres y apellidos, documento de identidad, parentesco con el paciente, teléfono principal, teléfono alternativo (opcional), correo (opcional) y dirección.
- Asociación de uno o más tutores a uno o más pacientes, con marca de **tutor principal** por paciente.
- Validación de unicidad por documento o por combinación nombres + apellidos + teléfono principal.
- Búsqueda de tutores por nombre, documento o teléfono.
- Registro de contactos de emergencia adicionales (persona distinta del tutor, con nombre y teléfono).
- Bitácora de altas, ediciones, cambios de estado y consultas.
- Bloqueo de eliminación cuando el tutor tiene pacientes asociados; solo se permite desactivarlo.
- Exportación del listado de tutores a PDF y Excel (respetando el alcance del usuario).

### Excluido de este módulo

- **Datos clínicos del paciente:** corresponde al submódulo 01 y a la ficha de ingreso (03).
- **Inscripciones, pagos y caja:** módulos 06 y 08.
- **Alerta de próximo pago:** módulo 09; aquí solo se provee el teléfono del tutor como dato.
- **Envío automático de WhatsApp:** fuera de alcance por el contexto; el sistema solo genera el enlace.
- **Cuentas de acceso al sistema para tutores:** fuera de alcance por el contexto.
- **Cobranza y gestión de deudas:** Etapa 2 (plan de cuotas); el tutor como entidad se registra aquí.
- **Integraciones externas:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita, activa y desactiva tutores en todas las sucursales. |
| Administración | Consulta y edita datos de contacto de tutores de sus sucursales; **no crea** tutores (ver [SUPUESTO A VALIDAR CON EL CLIENTE]). |
| Recepción | Crea y edita tutores al momento del ingreso; consulta para la alerta de próximo pago. |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **El tutor es global:** no se duplica por sucursal.
- **La asociación tutor–paciente es global**, pero la sucursal aparece en la inscripción del paciente.
- **Los teléfonos del tutor** se muestran a Recepción y Administración dentro de sus sucursales, para la alerta de próximo pago y la comunicación con la familia.
- **Datos personales de tutores** se tratan conforme a la normativa aplicable.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que un tutor puede estar asociado a varios pacientes (hermanos) y que un paciente puede tener varios tutores con uno principal. Confirmar antes de implementar. También debe confirmarse si el tutor requiere documento de identidad obligatorio o si basta con nombre y teléfono.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita localizar rápidamente al tutor cuando hay una emergencia, cuando cambia un turno o cuando debe avisar del próximo pago. Una ficha única de tutor con teléfonos validados evita llamar al número equivocado, facilita la comunicación y permite que la alerta de próximo pago use el teléfono del tutor principal con el enlace a WhatsApp. La asociación tutor–paciente refleja situaciones reales (hermanos, padres separados, apoderados).

### 3.2 Justificación en la Atención al Niño y su Familia

La familia recibe información oportuna y consistente. Saber a quién contactar y con qué parentesco facilita la comunicación, respeta la estructura familiar y evita confusiones cuando hay varios responsables del niño. El centro puede reaccionar rápido ante una emergencia y la familia siente que sus datos están bien resguardados.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** protección integral, interés superior del niño y resguardo de la información de menores y su entorno familiar [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales de tutores y pacientes [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** uso de canales de comunicación electrónica (WhatsApp) conforme a las reglas del país [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

El registro de tutores y contactos permite demostrar el consentimiento de la familia para la atención y el uso de sus datos de contacto para comunicaciones del centro.

### 3.4 Justificación Tecnológica

Frente al registro manual, la ficha del tutor aporta: identificador único; validación de unicidad; relación explícita tutor–paciente; bitácora de consultas y cambios; y disponibilidad del teléfono para la alerta de próximo pago. Se reduce el riesgo de contactos erróneos y se prepara la información para la comunicación con la familia.

---

## 4. Funciones Principales

### FP-01: Registrar tutor
- **Descripción:** Permite dar de alta un tutor con sus datos de identificación, parentesco, teléfonos y dirección. El sistema valida unicidad y campos obligatorios.
- **Actor principal:** Recepción (y Superadmin).
- **Resultado esperado:** Tutor creado con identificador único, disponible para asociar a uno o varios pacientes.

### FP-02: Asociar tutores a pacientes
- **Descripción:** Permite vincular uno o varios tutores a un paciente, marcando un tutor principal. La asociación se gestiona junto con el paciente.
- **Actor principal:** Recepción, Superadmin.
- **Resultado esperado:** Paciente con tutores asociados y tutor principal definido, condición para activar al paciente.

### FP-03: Consultar y buscar tutores
- **Descripción:** Permite listar y filtrar tutores por nombre, documento, teléfono y estado, y abrir la ficha con sus pacientes asociados.
- **Actor principal:** Recepción, Administración, Superadmin.
- **Resultado esperado:** Vista consolidada con opción de exportar a PDF y Excel.

### FP-04: Actualizar datos y estado del tutor
- **Descripción:** Permite modificar datos de contacto, parentesco y estado, registrando cada cambio en bitácora. Bloquea la eliminación con pacientes asociados.
- **Actor principal:** Recepción (datos); Administración (datos de sus sucursales); Superadmin (todo).
- **Resultado esperado:** Tutor actualizado con historial y estado vigente aplicado en todo el sistema.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear tutor | El sistema debe permitir registrar un tutor con nombres, apellidos, parentesco, teléfono principal y estado activo. | Alta | Recepción, Superadmin |
| RF-002 | Validar campos obligatorios | El sistema debe exigir nombres, apellidos, parentesco y teléfono principal. | Alta | Sistema |
| RF-003 | Validar unicidad | El sistema debe rechazar tutores duplicados por documento (si existe) o por combinación nombres + apellidos + teléfono principal. | Alta | Sistema |
| RF-004 | Asociar tutores a paciente | El sistema debe permitir asociar uno o más tutores a un paciente, con marca de tutor principal. | Alta | Recepción, Superadmin |
| RF-005 | Garantizar un tutor principal | El sistema debe garantizar que todo paciente activo tenga exactamente un tutor principal. | Alta | Sistema |
| RF-006 | Desasociar tutor | El sistema debe permitir desasociar un tutor de un paciente, impidiendo dejar al paciente sin tutor principal. | Alta | Recepción, Superadmin |
| RF-007 | Listar y buscar tutores | El sistema debe mostrar un listado paginado con filtros por nombre, documento, teléfono y estado, respetando el alcance del usuario. | Alta | Recepción, Administración, Superadmin |
| RF-008 | Consultar ficha de tutor | El sistema debe mostrar la ficha con datos, pacientes asociados y estado, con fecha de última modificación. | Alta | Recepción, Administración, Superadmin |
| RF-009 | Editar tutor | El sistema debe permitir modificar datos del tutor, registrando valores anteriores y nuevos en bitácora. | Alta | Recepción, Administración, Superadmin |
| RF-010 | Activar y desactivar tutor | El sistema debe permitir cambiar el estado entre activo e inactivo, exigiendo motivo al desactivar. | Alta | Superadmin |
| RF-011 | Bloquear eliminación con pacientes asociados | El sistema debe impedir la eliminación física de un tutor con pacientes asociados; solo permitirá desactivarlo. | Alta | Sistema |
| RF-012 | Registrar contactos de emergencia | El sistema debe permitir registrar contactos de emergencia adicionales al tutor, con nombre, parentesco y teléfono. | Media | Recepción, Administración |
| RF-013 | Proveer teléfono a la alerta de próximo pago | El sistema debe exponer el teléfono del tutor principal para que el módulo 09 construya la alerta y el enlace de WhatsApp (sin envío automático). | Alta | Sistema |
| RF-014 | Registrar bitácora | El sistema debe registrar altas, ediciones, cambios de estado, asociaciones y desasociaciones en bitácora con valores anteriores y nuevos. | Alta | Sistema |
| RF-015 | Restringir acceso por rol y sucursal | El sistema debe permitir que Recepción y Administración accedan solo a tutores de pacientes con inscripciones en sus sucursales; Superadmin accede a todos. | Alta | Sistema |
| RF-016 | Exportar listado de tutores | El sistema debe permitir exportar el listado filtrado a PDF y Excel, respetando el alcance del usuario. | Baja | Administración, Superadmin |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que el tutor principal debe ser único por paciente. Debe confirmarse si el sistema debe registrar número de documento de identidad y si la asociación tutor–paciente puede modificarse libremente o requiere motivo cuando se cambia al tutor principal.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La búsqueda de tutores por nombre, documento o teléfono debe responder en menos de 1 segundo con hasta 5 000 tutores registrados.
- **RNF-002:** La consulta del teléfono del tutor principal para la alerta de próximo pago debe resolverse en menos de 100 ms.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen sesión autenticada con rol autorizado.
- **RNF-004:** Los datos personales de tutores (documento, teléfonos, dirección) deben tratarse como sensibles y mostrarse solo a roles autorizados.
- **RNF-005:** El teléfono se muestra completo solo a roles que lo necesiten para comunicación; en el resto de las vistas se enmascara parcialmente. [SUPUESTO A VALIDAR CON EL CLIENTE]

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir tutores y sus históricos.

### 6.4 Usabilidad

- **RNF-008:** La ficha del tutor debe mostrar claramente el teléfono principal, el parentesco y los pacientes asociados.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en tutores y asociaciones sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El módulo debe exponer los datos del tutor vía API REST a pacientes, inscripciones, alertas de próximo pago y reportes, y exportar a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** El esquema se gestionará con migraciones versionadas y catálogos configurables (parentescos).

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, edición, cambio de estado, asociación y desasociación debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Tutor global — El tutor no se duplica por sucursal; la asociación tutor–paciente es global.
- **RN-002:** Unicidad de tutor — Un tutor se identifica por documento (si existe) o por la combinación nombres + apellidos + teléfono principal.
- **RN-003:** Tutor principal único — Cada paciente activo debe tener exactamente un tutor principal.
- **RN-004:** Tutor obligatorio — Todo paciente activo debe tener al menos un tutor asociado.
- **RN-005:** Asociación múltiple — Un tutor puede estar asociado a varios pacientes (hermanos) y un paciente puede tener varios tutores.
- **RN-006:** No eliminación con pacientes — Un tutor con pacientes asociados no puede eliminarse; solo desactivarse con motivo.
- **RN-007:** Tutor inactivo — Un tutor inactivo no puede usarse como contacto en nuevas comunicaciones ni en la alerta de próximo pago. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **RN-008:** Teléfono principal obligatorio — Todo tutor activo debe tener un teléfono principal válido.
- **RN-009:** Comunicación por WhatsApp — El sistema solo genera el enlace de WhatsApp con el teléfono del tutor principal; no envía mensajes automáticos.
- **RN-010:** Alcance por rol y sucursal — Recepción y Administración acceden a tutores de pacientes de sus sucursales; Superadmin accede a todos.
- **RN-011:** Bitácora de consultas — Cada acceso a la ficha del tutor queda registrado como evento auditado.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-007 (tutor inactivo no disponible para comunicación) y la RN-009 (sin envío automático de WhatsApp) deben confirmarse; la segunda ya está en el contexto pero conviene dejarla explícita.

---

## 8. Casos de Uso Principales

### CU-01: Registrar tutor y asociarlo a un paciente

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar tutor y asociarlo a un paciente |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; paciente en registro o ya existente |
| **Postcondiciones** | Tutor creado y asociado al paciente; tutor principal definido |
| **Trigger** | Recepción selecciona “Nuevo tutor” durante el alta del paciente |

**Flujo Principal:**
1. Recepción selecciona “Nuevo tutor” o ingresa los datos del tutor desde la ficha del paciente.
2. El sistema muestra el formulario.
3. Recepción ingresa nombres, apellidos, parentesco, teléfonos y dirección.
4. El sistema valida campos obligatorios y unicidad.
5. Recepción marca al tutor como principal (si corresponde).
6. El sistema guarda el tutor y lo asocia al paciente, registrando en bitácora.

**Flujos Alternativos:**
- **FA-01:** El tutor ya existe y se busca por nombre o documento para asociarlo sin crearlo.

**Flujos de Excepción:**
- **FE-01:** Teléfono principal inválido → el sistema rechaza el guardado e informa el formato esperado.
- **FE-02:** Duplicado de tutor detectado → el sistema muestra el existente y permite asociarlo.
- **FE-03:** Se intenta asociar sin tutor principal → el sistema exige designar uno.

### CU-02: Editar tutor y actualizar contactos

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Editar tutor y actualizar contactos |
| **Actor Principal** | Recepción / Administración / Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; tutor dentro del alcance del usuario |
| **Postcondiciones** | Datos del tutor actualizados con bitácora |
| **Trigger** | El usuario abre la ficha del tutor y elige “Editar” |

**Flujo Principal:**
1. El usuario busca el tutor y abre su ficha.
2. El sistema muestra datos y pacientes asociados.
3. El usuario selecciona “Editar” y modifica los campos permitidos según su rol.
4. El sistema valida y guarda, registrando valores anteriores y nuevos en bitácora.
5. El sistema confirma la actualización.

**Flujos Alternativos:**
- **FA-01:** El usuario cancela; los datos anteriores se mantienen.

**Flujos de Excepción:**
- **FE-01:** Teléfono inválido → el sistema rechaza el guardado.
- **FE-02:** Otro usuario modificó la ficha mientras se editaba → el sistema advierte el conflicto.

### CU-03: Consultar tutor desde la alerta de próximo pago

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Consultar tutor desde la alerta de próximo pago |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema (scoping y bitácora) |
| **Precondiciones** | Inscripción próxima a vencer; tutor principal asociado al paciente |
| **Postcondiciones** | Teléfono del tutor visible y enlace de WhatsApp generado (sin envío automático) |
| **Trigger** | Recepción abre la alerta de próximo pago del módulo 09 |

**Flujo Principal:**
1. Recepción abre la alerta de próximo pago.
2. El sistema consulta el tutor principal del paciente y su teléfono.
3. El sistema muestra el nombre del tutor y el teléfono, y genera el enlace de WhatsApp.
4. Recepción puede copiar el teléfono o abrir WhatsApp con el enlace.
5. El sistema registra la consulta en bitácora.

**Flujos Alternativos:**
- **FA-01:** Si el tutor principal no tiene teléfono, el sistema sugiere actualizar la ficha antes de continuar.

**Flujos de Excepción:**
- **FE-01:** Tutor inactivo → el sistema advierte y sugiere actualizarlo o elegir otro tutor. [SUPUESTO A VALIDAR CON EL CLIENTE]

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de tutor
- **Given** un usuario de Recepción autenticado y datos únicos,
- **When** registra un tutor con todos los campos obligatorios y lo asocia a un paciente,
- **Then** el sistema crea el tutor, lo asocia y lo registra en bitácora.

### CA-002: Rechazo por duplicado
- **Given** un tutor existente con el mismo documento o combinación de nombre + teléfono,
- **When** el usuario intenta crear otro,
- **Then** el sistema muestra el existente y permite asociarlo.

### CA-003: Tutor principal único
- **Given** un paciente con un tutor principal,
- **When** el usuario marca otro tutor como principal,
- **Then** el sistema desmarca al anterior y deja uno solo.

### CA-004: Rechazo de paciente sin tutor
- **Given** un paciente sin tutores asociados,
- **When** el usuario intenta activarlo,
- **Then** el sistema bloquea la activación e indica el requisito.

### CA-005: Edición con bitácora
- **Given** un tutor existente,
- **When** el usuario actualiza su teléfono,
- **Then** el sistema guarda el nuevo dato y registra en bitácora el valor anterior y el nuevo.

### CA-006: Bloqueo de eliminación con pacientes
- **Given** un tutor con pacientes asociados,
- **When** el usuario intenta eliminarlo,
- **Then** el sistema rechaza la eliminación y ofrece desactivarlo.

### CA-007: Desasociación sin dejar al paciente sin tutor principal
- **Given** un paciente con un tutor principal,
- **When** el usuario intenta desasociar a ese tutor,
- **Then** el sistema lo impide hasta que se designe otro tutor principal.

### CA-008: Teléfono en alerta de próximo pago
- **Given** una inscripción próxima a vencer,
- **When** Recepción abre la alerta,
- **Then** el sistema muestra el teléfono del tutor principal y genera el enlace de WhatsApp.

### CA-009: Scoping por sucursal
- **Given** un usuario asignado solo a “Yacuiba Centro”,
- **When** busca tutores,
- **Then** el sistema muestra solo tutores de pacientes con inscripciones en esa sucursal.

### CA-010: Restricción por rol
- **Given** un usuario de Administración,
- **When** intenta crear un tutor,
- **Then** el sistema no muestra la opción y rechaza por API (según confirmación del cliente).

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Duplicación de tutores en la migración inicial | Alta | Medio | Alto | Plantilla de migración, validación de unicidad y consolidación con el cliente. |
| RT-002 | Teléfonos inválidos o desactualizados que impidan la comunicación | Media | Alto | Alto | Validación de formato, campo teléfono alternativo y actualización periódica. |
| RT-003 | Exposición de datos personales de tutores a roles no autorizados | Media | Medio | Medio | Control de acceso por rol y sucursal, enmascaramiento de teléfonos. |
| RT-004 | Múltiples tutores con conflictos de comunicación | Media | Medio | Medio | Marca clara de tutor principal y bitácora de cambios. |
| RT-005 | Ambigüedad sobre qué rol crea tutores | Alta | Bajo | Medio | Confirmar con el cliente y ajustar la matriz de permisos. |
| RT-006 | Teléfono de tutor inactivo usado por error en la alerta | Baja | Medio | Bajo | Regla RN-007 y aviso en la alerta. |
| RT-007 | Crecimiento de la ficha sin política de depuración | Media | Medio | Medio | Reglas de negocio claras y revisión periódica. |
| RT-008 | Dependencia de una sola persona para administrar tutores | Media | Medio | Medio | Documentación y transferencia de conocimiento. |

---

**Fin del documento**