# Directorio de profesionales
**Módulo:** Profesionales
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra el directorio de profesionales que prestan servicios en TalitaCumi, tanto en intervención terapéutica individual (psicología, psicopedagogía, terapia ocupacional, fisioterapia, terapia de lenguaje) como en salas grupales. Los profesionales **no usan el sistema** (según el contexto); su registro existe para que la agenda, los diagnósticos y los reportes puedan referenciarlos, y para que la asignación de profesionales a turnos —que es opcional— sea posible.

Hoy el directorio vive en cuadernos y en la memoria del personal: no hay una fuente única de quién trabaja en el centro, qué especialidad tiene ni en qué sucursales presta servicios. Este submódulo reemplaza ese registro disperso por un catálogo administrado por el cliente, con datos de identificación, contacto, especialidad y estado, y con un identificador estable que el resto de los módulos consumirá.

El valor que aporta es la trazabilidad de la atención: permite saber qué profesional atendió o fue asignado a cada turno, quién participa en un diagnóstico integral y qué profesionales están disponibles por especialidad y sucursal. Se integra con Especialidades y sucursales de trabajo, Asignación opcional a turnos, Catálogo de servicios y tarifas, Agenda y turnos, Diagnóstico con varios profesionales, y Reportes por profesional.

---

## 2. Alcance

### Incluido en este módulo

- Registro, edición, consulta y cambio de estado (activo/inactivo) de profesionales.
- Datos mínimos del profesional: nombre completo, documento de identidad, especialidad principal, teléfonos de contacto, correo (opcional) y estado.
- Asociación del profesional a una o más sucursales donde presta servicios (gestionada en el submódulo 02).
- Asociación del profesional a una o más especialidades (principal y secundarias), según lo defina el cliente.
- Búsqueda, listado y filtrado de profesionales por especialidad, sucursal y estado, respetando el alcance del usuario.
- Registro en bitácora de altas, ediciones y cambios de estado.
- Bloqueo de eliminación física cuando el profesional tiene turnos, diagnósticos o atenciones registradas; solo se permite desactivarlo.
- Exportación del listado de profesionales a PDF y Excel.
- Provisión del identificador de profesional a la agenda, a los diagnósticos integrales y a los reportes.

### Excluido de este módulo

- **Asignación de especialidades por sucursal y horarios de trabajo:** corresponde al submódulo `02_especialidades_sucursales_trabajo.md`.
- **Asignación opcional de profesionales a turnos:** corresponde al submódulo `03_asignacion_opcional_turnos.md`.
- **Cuentas de usuario para profesionales:** fuera de alcance por el contexto; los profesionales no usan el sistema.
- **Nómina, liquidaciones, control de asistencia laboral y vacaciones:** fuera del alcance del sistema.
- **Registro clínico de atenciones y resultados:** corresponde a los módulos 06 y 11 (Etapa 2/3 para resultados).
- **Integraciones externas y app móvil:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita, activa y desactiva profesionales en todas las sucursales. |
| Administración | Consulta y edita datos de contacto de los profesionales de sus sucursales; no crea ni desactiva. |
| Recepción | Consulta el directorio de profesionales de sus sucursales asignadas (para la agenda y la asignación opcional de turnos). |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **El profesional es una entidad global**, pero su disponibilidad se define por las sucursales en las que trabaja.
- **Un profesional puede trabajar en una o varias sucursales**, según se defina en el submódulo 02.
- **Los datos de menores y de salud** asociados a la actividad del profesional quedan protegidos por rol y sucursal.
- **Las especialidades son globales** (catálogo del módulo 05 o del propio módulo 03, a definir), mientras que la relación profesional–especialidad–sucursal es específica.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que un profesional tiene una especialidad principal y puede tener especialidades secundarias, y que puede trabajar en más de una sucursal. Confirmar antes de implementar. También debe confirmarse si el directorio se carga inicialmente desde una planilla del centro.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita saber qué profesionales hay, qué especialidad tienen y en qué sucursales trabajan para asignar turnos (cuando corresponda) y organizar la agenda. Administración necesita un registro único para reportes por profesional. Sin este directorio, las asignaciones se hacen de memoria y los reportes por profesional son imposibles de construir de forma confiable.

### 3.2 Justificación en la Atención al Niño y su Familia

Saber qué profesional atiende a cada niño y con qué especialidad refuerza la continuidad de la atención: la familia recibe información consistente sobre quién la atiende y el centro puede garantizar que el profesional asignado corresponde a la necesidad del niño. En diagnósticos integrales, el directorio permite identificar a los profesionales que participan.

### 3.3 Justificación Normativa

El directorio no introduce una norma específica sobre profesionales, pero contribuye al cumplimiento de:

- **Ley 3131, de Ejercicio Profesional Médico:** identificación del profesional que interviene y conservación de registros [verificar vigencia].
- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de la información de menores [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales de profesionales y pacientes [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La identificación del profesional en cada atención es requisito para que el registro clínico sea trazable y para que el secreto profesional se aplique correctamente.

### 3.4 Justificación Tecnológica

Frente al registro manual, el directorio aporta: identificadores únicos por profesional; validación de unicidad de documento; relación explícita con especialidades y sucursales; bitácora de cambios; y bloqueo de eliminación con historial. Se reduce la duplicación de profesionales con nombres escritos de formas distintas y se habilita el filtrado confiable en agenda y reportes.

---

## 4. Funciones Principales

### FP-01: Registrar profesional
- **Descripción:** Permite dar de alta un profesional con sus datos de identificación, especialidad principal, contactos y estado. El sistema valida unicidad de documento y guarda en estado activo.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Profesional creado y disponible para asociarlo a sucursales, especialidades y turnos.

### FP-02: Consultar y buscar profesionales
- **Descripción:** Permite listar y filtrar profesionales por especialidad, sucursal y estado, y abrir la ficha con sus datos y asociaciones. El listado se ajusta al alcance del usuario.
- **Actor principal:** Superadmin, Administración, Recepción.
- **Resultado esperado:** Vista consolidada del directorio con opción de exportar a PDF/Excel.

### FP-03: Actualizar datos y estado del profesional
- **Descripción:** Permite modificar los datos de contacto, la especialidad principal y el estado del profesional, registrando cada cambio en bitácora.
- **Actor principal:** Superadmin (todos los campos y estado); Administración (datos de contacto de sus sucursales).
- **Resultado esperado:** Profesional actualizado con historial de cambios y estado vigente aplicado en agenda y diagnósticos.

### FP-04: Proveer el directorio a la agenda y a los diagnósticos
- **Descripción:** Expone el identificador, las especialidades y el estado de cada profesional para que la agenda, los diagnósticos integrales y los reportes lo referencien correctamente.
- **Actor principal:** Sistema (invocado por los módulos consumidores).
- **Resultado esperado:** Coherencia entre profesional, especialidad, sucursal y turno en todo el sistema.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear profesional | El sistema debe permitir registrar un profesional con nombre completo, documento, especialidad principal, teléfonos, correo (opcional) y estado. | Alta | Superadmin |
| RF-002 | Validar unicidad | El sistema debe rechazar documentos de identidad duplicados, mostrando el campo en conflicto. | Alta | Sistema |
| RF-003 | Validar campos obligatorios | El sistema debe exigir nombre completo, documento y especialidad principal. | Alta | Sistema |
| RF-004 | Listar y buscar profesionales | El sistema debe mostrar un listado paginado con filtros por especialidad, sucursal y estado, respetando el alcance del usuario. | Alta | Superadmin, Administración, Recepción |
| RF-005 | Consultar ficha de profesional | El sistema debe mostrar la ficha con datos, especialidades, sucursales y estado, y la fecha de última modificación. | Alta | Superadmin, Administración, Recepción |
| RF-006 | Editar profesional | El sistema debe permitir modificar datos, especialidades y estado, registrando valores anteriores y nuevos en bitácora. | Alta | Superadmin, Administración |
| RF-007 | Activar y desactivar profesional | El sistema debe permitir cambiar el estado entre activo e inactivo exigiendo motivo al desactivar. | Alta | Superadmin |
| RF-008 | Bloquear eliminación con historial | El sistema debe impedir la eliminación física de un profesional con turnos, diagnósticos o atenciones registradas; solo permitirá desactivarlo. | Alta | Sistema |
| RF-009 | Asociar especialidades | El sistema debe permitir asociar una especialidad principal y, si el cliente lo confirma, especialidades secundarias al profesional. | Media | Superadmin |
| RF-010 | Asociar sucursales de trabajo | El sistema debe exponer el profesional para que el submódulo 02 asocie una o varias sucursales de trabajo. | Alta | Superadmin |
| RF-011 | Registrar bitácora | El sistema debe registrar en bitácora altas, ediciones, cambios de especialidad y cambios de estado. | Alta | Sistema |
| RF-012 | Proveer directorio a otros módulos | El sistema debe exponer el directorio vía API para agenda, diagnósticos y reportes. | Alta | Sistema |
| RF-013 | Restringir operaciones sobre profesional inactivo | El sistema debe impedir asignar un profesional inactivo a nuevos turnos o diagnósticos; su historial sigue consultable. | Alta | Sistema |
| RF-014 | Restringir acceso por rol | El sistema debe impedir que Recepción cree o edite profesionales y que Administración cree o desactive profesionales. | Alta | Sistema |
| RF-015 | Exportar directorio | El sistema debe permitir exportar el listado filtrado de profesionales a PDF y Excel. | Baja | Superadmin, Administración |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que la especialidad principal es obligatoria y las secundarias opcionales. Debe confirmarse si el cliente desea registrar número de matrícula profesional y, en ese caso, si es obligatorio.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El listado de profesionales debe cargar en menos de 1 segundo con hasta 200 profesionales registrados y 20 usuarios concurrentes.
- **RNF-002:** La validación de unicidad de documento debe resolverse en menos de 500 ms.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS; las operaciones de escritura exigen sesión autenticada con rol autorizado.
- **RNF-004:** El scoping por sucursal debe validarse en backend.
- **RNF-005:** Los datos de profesionales y de menores asociados a su actividad deben tratarse conforme a la política de privacidad y a la normativa aplicable.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro, con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir el directorio y sus históricos.

### 6.4 Usabilidad

- **RNF-008:** La interfaz debe usar el vocabulario del negocio (profesional, especialidad, sucursal, estado) y permitir crear un profesional en pocos pasos.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en profesionales y especialidades sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El módulo debe exponer el directorio vía API REST y exportar a PDF y Excel; no se contemplan integraciones externas en esta etapa.

### 6.7 Mantenibilidad

- **RNF-012:** El esquema se gestionará con migraciones versionadas; las especialidades provendrán de un catálogo configurable.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, edición, cambio de especialidad o estado debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Unicidad de profesional — El documento de identidad del profesional debe ser único en el sistema.
- **RN-002:** Especialidad principal obligatoria — Todo profesional debe tener una especialidad principal definida.
- **RN-003:** Profesional inactivo — Un profesional inactivo no puede asignarse a nuevos turnos ni a nuevos diagnósticos; sus registros históricos se conservan.
- **RN-004:** No eliminación con historial — Un profesional con turnos, diagnósticos o atenciones no puede eliminarse; solo desactivarse con motivo.
- **RN-005:** Especialidades del sistema — Las especialidades se toman de un catálogo configurable; no hay valores fijos en el código.
- **RN-006:** Sucursales de trabajo — Un profesional puede trabajar en una o varias sucursales, definidas en el submódulo 02.
- **RN-007:** Asignación no automática — El sistema no asigna profesionales automáticamente a turnos; la asignación es manual y opcional (submódulo 03).
- **RN-008:** Motivo obligatorio — El cambio de estado de un profesional requiere motivo registrado en bitácora.
- **RN-009:** Alcance por rol — Superadmin administra todo el directorio; Administración edita contactos de sus sucursales; Recepción solo consulta.
- **RN-010:** Profesional sin usuario del sistema — Los profesionales no tienen cuenta de acceso; su registro es referencial para agenda y reportes.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-005 (catálogo de especialidades configurable) y la RN-010 (sin acceso al sistema) deben confirmarse; la segunda ya está definida por el contexto, pero conviene dejarla explícita.

---

## 8. Casos de Uso Principales

### CU-01: Registrar profesional

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar profesional |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; catálogo de especialidades disponible |
| **Postcondiciones** | Profesional creado en estado activo, disponible para asociación con sucursales y especialidades |
| **Trigger** | El Superadmin selecciona “Nuevo profesional” en el módulo Profesionales |

**Flujo Principal:**
1. El Superadmin selecciona “Nuevo profesional”.
2. El sistema muestra el formulario de alta.
3. El Superadmin ingresa nombre completo, documento, especialidad principal, contactos y estado.
4. El sistema valida campos obligatorios y unicidad de documento.
5. El sistema guarda el profesional en estado activo.
6. El sistema registra el alta en bitácora.
7. El sistema confirma y redirige al listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede cargar el profesional como inactivo para configurarlo antes de habilitarlo. [SUPUESTO A VALIDAR CON EL CLIENTE]

**Flujos de Excepción:**
- **FE-01:** Documento duplicado → el sistema informa y no guarda.
- **FE-02:** Falta la especialidad principal → el sistema bloquea el alta.
- **FE-03:** Falla de conexión al guardar → el sistema informa y permite reintentar sin perder datos.

### CU-02: Editar profesional

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Editar profesional |
| **Actor Principal** | Superadmin / Administración |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; profesional existente dentro del alcance del usuario |
| **Postcondiciones** | Datos actualizados y registrados en bitácora |
| **Trigger** | El usuario abre la ficha del profesional y elige “Editar” |

**Flujo Principal:**
1. El usuario busca el profesional y abre la ficha.
2. El sistema muestra datos, especialidades y sucursales.
3. El usuario selecciona “Editar”.
4. El sistema habilita los campos permitidos según su rol.
5. El usuario modifica los datos y confirma.
6. El sistema valida y guarda, registrando valores anteriores y nuevos.
7. El sistema confirma la actualización.

**Flujos Alternativos:**
- **FA-01:** El usuario cancela; el sistema descarta los cambios.

**Flujos de Excepción:**
- **FE-01:** El usuario intenta editar un campo no permitido para su rol → el sistema bloquea la acción.
- **FE-02:** Otro usuario modificó el registro mientras se editaba → el sistema advierte el conflicto y solicita recargar.

### CU-03: Desactivar profesional

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Desactivar profesional |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; profesional existente y activo |
| **Postcondiciones** | Profesional inactivo, bloqueado para nuevas asignaciones, con bitácora y motivo |
| **Trigger** | El Superadmin selecciona “Desactivar” en la ficha del profesional |

**Flujo Principal:**
1. El Superadmin abre la ficha y selecciona “Desactivar”.
2. El sistema solicita el motivo.
3. El Superadmin ingresa el motivo y confirma.
4. El sistema cambia el estado a inactivo.
5. El sistema registra en bitácora la acción con motivo.
6. El sistema confirma la desactivación.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; el profesional sigue activo.

**Flujos de Excepción:**
- **FE-01:** Motivo vacío → el sistema no permite continuar.
- **FE-02:** El profesional tiene turnos futuros asignados → el sistema informa y sugiere reasignar o cancelar antes de desactivar. [SUPUESTO A VALIDAR CON EL CLIENTE]

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de profesional
- **Given** un Superadmin autenticado y un documento no registrado,
- **When** registra el profesional con su especialidad principal,
- **Then** el sistema crea el profesional activo y lo muestra en el listado con bitácora.

### CA-002: Rechazo por documento duplicado
- **Given** un profesional con documento “1234567”,
- **When** el Superadmin intenta crear otro con el mismo documento,
- **Then** el sistema rechaza el alta e informa el conflicto.

### CA-003: Especialidad principal obligatoria
- **Given** el formulario de alta,
- **When** el Superadmin intenta guardar sin especialidad principal,
- **Then** el sistema bloquea el guardado y explica el requisito.

### CA-004: Listado filtrado por sucursal
- **Given** un usuario de Recepción asignado solo a “Yacuiba Centro”,
- **When** abre el directorio,
- **Then** el sistema muestra solo profesionales asociados a esa sucursal.

### CA-005: Edición con bitácora
- **Given** un profesional existente,
- **When** el Superadmin modifica su teléfono,
- **Then** el sistema actualiza el dato y registra en bitácora valores anteriores y nuevos.

### CA-006: Desactivación con motivo
- **Given** un profesional activo sin turnos futuros,
- **When** el Superadmin lo desactiva con motivo,
- **Then** el sistema cambia el estado a inactivo y lo registra.

### CA-007: Bloqueo de eliminación con historial
- **Given** un profesional con turnos registrados,
- **When** el Superadmin intenta eliminarlo,
- **Then** el sistema rechaza la eliminación y ofrece desactivarlo.

### CA-008: Profesional inactivo no asignable
- **Given** un profesional inactivo,
- **When** Recepción intenta asignarlo a un nuevo turno,
- **Then** el sistema rechaza la asignación y explica el motivo.

### CA-009: Restricción por rol
- **Given** un usuario de Recepción,
- **When** intenta crear o editar un profesional,
- **Then** el sistema no muestra la opción y rechaza por API.

### CA-010: Exportación del directorio
- **Given** un Superadmin autenticado,
- **When** exporta el listado filtrado,
- **Then** el sistema genera PDF y Excel con los datos vigentes.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Duplicación de profesionales en la migración inicial | Alta | Medio | Alto | Plantilla de migración, normalización de nombres y documentos, validación con el cliente. |
| RT-002 | Asociación incorrecta de especialidad que afecte reportes por profesional | Media | Medio | Medio | Validación al guardar, catálogo de especialidades y pruebas de reportes. |
| RT-003 | Desactivación de profesionales con turnos futuros que deje la agenda inconsistente | Media | Alto | Alto | Validación al desactivar, aviso al usuario y opción de reasignar turnos. |
| RT-004 | Ausencia de matrícula o datos legales que el cliente solicite después | Media | Medio | Medio | Dejar el modelo extensible y validar campos con el cliente antes de implementar. |
| RT-005 | Exposición de datos de profesionales a roles no autorizados | Baja | Medio | Bajo | Control de acceso por rol en backend y pruebas de scoping. |
| RT-006 | Cambios de alcance (especialidades secundarias, horarios) durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa y registrar supuestos. |
| RT-007 | Dependencia de una sola persona para administrar el directorio | Media | Medio | Medio | Documentación y transferencia de conocimiento. |
| RT-008 | Reportes por profesional inconsistentes por datos incompletos | Media | Medio | Medio | Reglas de obligatoriedad (RN-002) y controles de calidad al cargar. |

---

**Fin del documento**