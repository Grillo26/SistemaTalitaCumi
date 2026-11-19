**Archivo:** `01_ORGANIZACION_SUCURSALES/02_gestion_salas_ambientes.md`

---

# Gestión de salas y ambientes
**Módulo:** Organización y sucursales
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra los espacios físicos donde el centro presta sus servicios: las **salas grupales** (una sala es tipo + turno + sucursal) y los **ambientes** destinados a la atención individual y a los diagnósticos o evaluaciones. Ambos son la referencia espacial que consumen la agenda, las inscripciones a salas grupales y los turnos individuales, por lo que su definición correcta es condición previa para programar cualquier atención.

Hoy la asignación de espacios se resuelve en la pizarra de turnos y en cuadernos, sin un catálogo único y sin distinguir con claridad qué sala pertenece a qué sucursal y a qué turno. Esto genera cruces entre sedes, choques de uso y dificultad para saber dónde se atiende cada niño. Este submódulo reemplaza ese registro manual por un catálogo administrado por el cliente, con tipos de sala configurables, turnos de mañana y tarde, estado por espacio y unicidad controlada.

El valor que aporta es la base física de la operación: permite que la agenda muestre los turnos en la sala o ambiente correcto, que la inscripción a sala grupal apunte a una sala vigente y que la sucursal quede determinada sin ambigüedad. Se integra con Organización y sucursales, Catálogo de servicios y tarifas, Inscripciones a salas grupales, Agenda y turnos, y con el módulo de auditoría.

---

## 2. Alcance

### Incluido en este módulo

- Administración del **catálogo de tipos de sala** (preescolar, atención temprana, estimulación temprana, escolar; ampliable por el cliente).
- Administración de los **turnos** operativos (mañana y tarde) como catálogo configurable.
- Registro, edición, consulta y cambio de estado de **salas grupales**, identificadas por tipo + turno + sucursal.
- Registro, edición, consulta y cambio de estado de **ambientes** de atención individual y evaluación, por sucursal.
- Validación de unicidad de sala por combinación tipo + turno + sucursal.
- Control de estado (activa / inactiva) con motivo, y bloqueo de eliminación con datos asociados.
- Búsqueda, listado y filtrado por sucursal, tipo, turno y estado, según el alcance del usuario.
- Registro en bitácora de altas, modificaciones y cambios de estado.
- Exportación del listado de salas y ambientes a PDF y Excel.
- Provisión del identificador de sala/ambiente y su estado a la agenda y a las inscripciones.

### Excluido de este módulo

- **Control de cupos por sala:** el contexto lo declara fuera de alcance. Si se incorpora un campo de capacidad, será solo informativo.
- **Colores por área:** los define el submódulo `05_CATALOGO_SERVICIOS_TARIFAS/01_areas_servicios_colores.md`; aquí solo se referencia el área para que la agenda pinte el turno.
- **Horarios de sesión y duración (50 minutos configurables):** corresponden al módulo 06 y 07.
- **Asignación de profesionales a turnos:** es opcional y pertenece al módulo 03 y 07; aquí no se asigna personal.
- **Tarifas por servicio o por sucursal:** corresponden al módulo 05.
- **Programación de turnos y patrones de días:** corresponde al módulo 07.
- **Mantenimiento edilicio, mobiliario o seguridad física:** fuera del alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita y desactiva tipos de sala, turnos, salas y ambientes en todas las sucursales. |
| Administración | Consulta y edita datos operativos (nombre, observaciones) de salas y ambientes de sus sucursales; no crea tipos de sala ni desactiva espacios. |
| Recepción | Consulta el listado de salas y ambientes de sus sucursales asignadas; no crea ni edita. |
| Profesionales y padres | **No usan el sistema** (según contexto). No son actores de este submódulo. |

### Sucursales aplicables

- **El catálogo de tipos de sala y el catálogo de turnos son globales:** los administra el Superadmin y aplican a todas las sucursales.
- **Las salas grupales y los ambientes pertenecen a una sola sucursal:** no se comparten entre sedes.
- **Pacientes y tutores son globales:** no se duplican por sala ni por sucursal.
- **Los turnos, inscripciones, pagos y caja pertenecen a una sucursal** y referencian la sala o ambiente de esa misma sucursal.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que los “ambientes” son los espacios donde se realizan terapias individuales y diagnósticos, y que toda sala o ambiente debe estar asociado a exactamente una sucursal. Se asume también que la capacidad, si se registra, es un dato informativo sin control de cupo. Confirmar antes de implementar.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita saber, en el momento de inscribir o de agendar, qué salas y ambientes existen en cada sucursal, en qué turno operan y si están activos. Con un catálogo único, la pizarra de turnos deja de ser la única fuente de verdad y se evitan errores como inscribir a un niño en una sala que no corresponde a su sucursal o agendar dos atenciones en el mismo ambiente. La unicidad tipo + turno + sucursal elimina las salas duplicadas que hoy aparecen con nombres distintos en los cuadernos.

### 3.2 Justificación en la Atención al Niño y su Familia

Un niño inscrito en sala grupal debe ser atendido en el espacio que corresponde a su tipo y turno, y un niño en terapia individual debe tener un ambiente asignado. Al ordenar el catálogo de espacios, se reducen las esperas, los cambios de sala de último momento y las confusiones que afectan la continuidad de la atención y la confianza de la familia. Además, la familia recibe información consistente sobre dónde se atiende al niño.

### 3.3 Justificación Normativa

El submódulo no tiene una norma específica que regule el registro de salas y ambientes en un sistema informático; su función es de soporte operativo. Sin embargo, hereda obligaciones generales:

- **Ley 548, Código Niña, Niño y Adolescente:** entornos protectores y resguardo de la información de menores [verificar vigencia].
- **Constitución Política del Estado:** protección de la niñez y derecho a la intimidad [verificar vigencia y articulado aplicable].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales [verificar vigencia].

Se deja constancia de que las condiciones físicas, de seguridad y de habilitación de los ambientes (infraestructura, señalética, accesibilidad) **no son verificadas por el sistema**; su cumplimiento es responsabilidad del centro ante la autoridad competente.

### 3.4 Justificación Tecnológica

Frente al registro manual, el catálogo aporta: identificadores estables que la agenda y las inscripciones usan como referencia; validación automática de unicidad; control de estado con bitácora; filtrado por sucursal en el backend; y consistencia para reportes de ocupación por sala. Se elimina la duplicación de espacios con nombres distintos y se evita que un turno quede asociado a una sala inactiva o de otra sede.

---

## 4. Funciones Principales

### FP-01: Administrar el catálogo de tipos de sala y turnos
- **Descripción:** Permite crear, editar y desactivar los tipos de sala (preescolar, atención temprana, estimulación temprana, escolar y los que el cliente agregue) y los turnos operativos (mañana y tarde). Nada queda fijo en el código.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Catálogo vigente que las salas grupales usan para identificarse y que la agenda usa para filtrar.

### FP-02: Registrar y administrar salas grupales
- **Descripción:** Permite dar de alta una sala grupal indicando tipo, turno, sucursal, nombre y observaciones, y luego editarla o cambiar su estado. El sistema impide duplicar la combinación tipo + turno + sucursal.
- **Actor principal:** Superadmin (alta y estado); Administración (datos operativos de sus sucursales).
- **Resultado esperado:** Salas grupales únicas por sucursal, disponibles para la inscripción mensual y para la agenda.

### FP-03: Registrar y administrar ambientes de atención individual
- **Descripción:** Permite dar de alta los ambientes donde se realizan terapias individuales, entrevistas y diagnósticos, con su sucursal y estado, y editarlos posteriormente.
- **Actor principal:** Superadmin (alta y estado); Administración (datos operativos de sus sucursales).
- **Resultado esperado:** Ambientes identificados y disponibles para que la agenda asigne el espacio de cada turno individual.

### FP-04: Proveer salas y ambientes a la agenda y a las inscripciones
- **Descripción:** Expone el identificador, la sucursal y el estado de cada sala o ambiente para que la agenda y las inscripciones solo operen sobre espacios activos de la sucursal correspondiente.
- **Actor principal:** Sistema (invocado por los módulos consumidores).
- **Resultado esperado:** Coherencia entre espacio, sucursal y turno; bloqueo de operaciones sobre espacios inactivos.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear tipo de sala | El sistema debe permitir registrar tipos de sala con nombre único y estado; los tipos iniciales son preescolar, atención temprana, estimulación temprana y escolar. | Alta | Superadmin |
| RF-002 | Editar y desactivar tipo de sala | El sistema debe permitir renombrar y desactivar un tipo de sala; no podrá desactivarse si tiene salas activas asociadas. | Alta | Superadmin |
| RF-003 | Administrar turnos | El sistema debe permitir registrar y editar los turnos operativos (mañana y tarde) como catálogo configurable, con orden de visualización. | Alta | Superadmin |
| RF-004 | Crear sala grupal | El sistema debe permitir registrar una sala grupal con tipo, turno, sucursal, nombre visible, observaciones y estado (activa por defecto). | Alta | Superadmin |
| RF-005 | Validar unicidad de sala grupal | El sistema debe rechazar la creación o edición de una sala cuya combinación tipo + turno + sucursal ya exista, mostrando la sala en conflicto. | Alta | Sistema |
| RF-006 | Crear ambiente | El sistema debe permitir registrar un ambiente con nombre, sucursal, uso previsto (terapia individual, entrevista, diagnóstico, mixto) y estado. | Alta | Superadmin |
| RF-007 | Validar unicidad de ambiente | El sistema debe rechazar ambientes con el mismo nombre dentro de una misma sucursal. | Alta | Sistema |
| RF-008 | Listar y buscar espacios | El sistema debe mostrar un listado paginado de salas y ambientes con filtros por sucursal, tipo, turno, uso y estado, respetando el alcance del usuario. | Alta | Superadmin, Administración, Recepción |
| RF-009 | Consultar ficha de espacio | El sistema debe mostrar la ficha de la sala o ambiente con sus datos, estado, sucursal y fecha de última modificación. | Alta | Superadmin, Administración, Recepción |
| RF-010 | Editar datos de espacio | El sistema debe permitir modificar los datos operativos del espacio; los cambios se registran en bitácora con valores anteriores y nuevos. | Alta | Superadmin, Administración |
| RF-011 | Activar y desactivar espacio | El sistema debe permitir cambiar el estado de una sala o ambiente exigiendo motivo al desactivar; los históricos se conservan. | Alta | Superadmin |
| RF-012 | Bloquear eliminación con datos asociados | El sistema debe impedir la eliminación física de una sala o ambiente con inscripciones o turnos asociados; solo permitirá desactivarlo. | Alta | Sistema |
| RF-013 | Validar coherencia de sucursal | El sistema debe impedir que una inscripción o un turno referencien una sala o ambiente de una sucursal distinta a la de la operación. | Alta | Sistema |
| RF-014 | Restringir operaciones sobre espacios inactivos | El sistema debe impedir nuevas inscripciones y nuevos turnos sobre salas o ambientes inactivos, conservando la consulta histórica. | Alta | Sistema |
| RF-015 | Registrar bitácora | El sistema debe registrar en bitácora la creación, edición y cambio de estado de tipos de sala, turnos, salas y ambientes. | Alta | Sistema |
| RF-016 | Exportar catálogo de espacios | El sistema debe permitir exportar el listado filtrado de salas y ambientes a PDF y Excel. | Baja | Superadmin, Administración |
| RF-017 | Restringir acceso por rol | El sistema debe impedir que Recepción cree o edite espacios y que Administración cree tipos de sala o desactive espacios. | Alta | Sistema |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El listado de salas y ambientes debe cargar en menos de 1 segundo con hasta 100 espacios registrados y 20 usuarios concurrentes.
- **RNF-002:** La validación de unicidad tipo + turno + sucursal debe resolverse en menos de 500 ms al guardar.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura deben exigir sesión autenticada con rol autorizado.
- **RNF-004:** El filtrado por sucursal debe aplicarse en el backend, no solo en la interfaz, para impedir el acceso a espacios de sedes no asignadas.
- **RNF-005:** Los datos de menores asociados a turnos en una sala o ambiente deben tratarse conforme a la política de privacidad y a la normativa aplicable.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con un objetivo de disponibilidad del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios de la base de datos deben incluir el catálogo de espacios y permitir su restauración.

### 6.4 Usabilidad

- **RNF-008:** La interfaz debe usar el vocabulario del negocio (sala, turno, ambiente, sucursal) y permitir crear una sala en menos de cinco pasos.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar la incorporación de nuevos tipos de sala, turnos, sucursales y espacios sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El módulo debe exportar el catálogo a PDF y Excel y exponer los datos vía API REST para la agenda y las inscripciones; no se contemplan integraciones externas en esta etapa.

### 6.7 Mantenibilidad

- **RNF-012:** Los catálogos de tipos de sala y turnos deben ser configurables por el cliente; el esquema se gestionará con migraciones versionadas.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, modificación o cambio de estado de un espacio debe quedar en bitácora con usuario, fecha, hora, acción y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Identidad de la sala grupal — Una sala grupal se identifica por la combinación tipo + turno + sucursal y esa combinación debe ser única.
- **RN-002:** Turnos válidos — Solo se admiten los turnos definidos en el catálogo (inicialmente mañana y tarde); un turno desactivado no puede usarse en nuevas salas.
- **RN-003:** Sin control de cupo — El sistema no controla cupos por sala ni bloquea inscripciones por cantidad de niños; una capacidad, si se registra, es solo informativa.
- **RN-004:** Pertenencia a una sucursal — Toda sala y todo ambiente pertenecen a exactamente una sucursal y no pueden compartirse entre sedes.
- **RN-005:** Coherencia sala–sucursal–operación — Una inscripción o un turno solo puede referenciar espacios de la misma sucursal de la operación.
- **RN-006:** Espacio inactivo — Una sala o ambiente inactivo no admite nuevas inscripciones ni nuevos turnos; sus registros históricos permanecen consultables y reportables.
- **RN-007:** No eliminación con datos — Una sala o ambiente con inscripciones o turnos asociados no puede eliminarse; solo desactivarse con motivo.
- **RN-008:** Catálogos administrables — Los tipos de sala y los turnos los define el cliente; no hay valores fijos en el código.
- **RN-009:** Colores por área — El color que diferencia una sala en la agenda proviene del área configurada en el módulo 05; no se define en este submódulo.
- **RN-010:** Motivo obligatorio — El cambio de estado de una sala o ambiente requiere motivo registrado en bitácora.
- **RN-011:** Alcance por rol y sucursal — Superadmin administra todo; Administración opera sobre sus sucursales; Recepción solo consulta.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-002 (turnos como catálogo configurable) y la RN-005 (validación estricta de coherencia sala–sucursal) deben confirmarse, ya que el contexto menciona mañana y tarde pero no detalla si podrían agregarse turnos adicionales ni si una sala podría reutilizarse entre sedes.

---

## 8. Casos de Uso Principales

### CU-01: Registrar sala grupal

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar sala grupal |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; sucursal activa existente; catálogo de tipos de sala y turnos vigente |
| **Postcondiciones** | Sala grupal creada en estado activo, única por tipo + turno + sucursal, disponible para inscripción y agenda |
| **Trigger** | El Superadmin selecciona “Nueva sala grupal” en Organización y sucursales |

**Flujo Principal:**
1. El Superadmin selecciona “Nueva sala grupal”.
2. El sistema muestra el formulario con sucursal, tipo de sala, turno, nombre visible y observaciones.
3. El Superadmin completa los datos.
4. El sistema valida campos obligatorios y que la sucursal esté activa.
5. El sistema verifica que la combinación tipo + turno + sucursal no exista.
6. El sistema guarda la sala en estado activo.
7. El sistema registra el evento en bitácora.
8. El sistema confirma la creación y muestra la sala en el listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede guardar la sala como inactiva para configurarla antes de habilitarla. [SUPUESTO A VALIDAR CON EL CLIENTE]

**Flujos de Excepción:**
- **FE-01:** Ya existe una sala con el mismo tipo, turno y sucursal → el sistema muestra la sala en conflicto y no guarda.
- **FE-02:** La sucursal seleccionada está inactiva → el sistema bloquea el alta e informa el motivo.
- **FE-03:** Falla de conexión al guardar → el sistema informa y permite reintentar sin perder los datos.

### CU-02: Editar y desactivar sala o ambiente

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Editar y desactivar sala o ambiente |
| **Actor Principal** | Superadmin / Administración |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; espacio existente dentro del alcance del usuario |
| **Postcondiciones** | Espacio actualizado o inactivo, con bitácora y bloqueo de nuevas operaciones |
| **Trigger** | El usuario abre la ficha del espacio y elige “Editar” o “Desactivar” |

**Flujo Principal:**
1. El usuario busca el espacio y abre su ficha.
2. El sistema muestra datos, estado y sucursal.
3. El usuario selecciona “Editar” y modifica los campos permitidos según su rol.
4. El sistema valida y guarda, registrando valores anteriores y nuevos en bitácora.
5. Si el usuario elige “Desactivar”, el sistema solicita el motivo.
6. El usuario ingresa el motivo y confirma.
7. El sistema cambia el estado a inactivo y registra la acción en bitácora.
8. El sistema confirma la operación.

**Flujos Alternativos:**
- **FA-01:** El usuario cancela la edición o la desactivación; el sistema mantiene el estado anterior.

**Flujos de Excepción:**
- **FE-01:** El motivo de desactivación está vacío → el sistema no permite continuar.
- **FE-02:** El usuario intenta editar un campo no permitido para su rol → el sistema bloquea la acción y muestra un mensaje.
- **FE-03:** Otro usuario modificó el espacio mientras se editaba → el sistema advierte el conflicto y solicita recargar.

### CU-03: Registrar ambiente de atención individual

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Registrar ambiente de atención individual |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; sucursal activa existente |
| **Postcondiciones** | Ambiente creado en estado activo, disponible para que la agenda asigne turnos individuales |
| **Trigger** | El Superadmin selecciona “Nuevo ambiente” en Organización y sucursales |

**Flujo Principal:**
1. El Superadmin selecciona “Nuevo ambiente”.
2. El sistema muestra el formulario con sucursal, nombre, uso previsto y observaciones.
3. El Superadmin completa los datos.
4. El sistema valida campos obligatorios y unicidad del nombre dentro de la sucursal.
5. El sistema guarda el ambiente en estado activo.
6. El sistema registra el evento en bitácora.
7. El sistema confirma la creación.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede registrar el ambiente con uso “mixto” cuando el espacio se destine a más de una actividad. [SUPUESTO A VALIDAR CON EL CLIENTE]

**Flujos de Excepción:**
- **FE-01:** Ya existe un ambiente con el mismo nombre en la sucursal → el sistema informa y no guarda.
- **FE-02:** La sucursal está inactiva → el sistema bloquea el alta e informa el motivo.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de sala grupal
- **Given** un Superadmin autenticado y una sucursal activa sin sala de tipo “Preescolar” en turno “Mañana”,
- **When** registra esa combinación,
- **Then** el sistema crea la sala en estado activo y la muestra en el listado con registro en bitácora.

### CA-002: Rechazo de sala duplicada
- **Given** una sala “Preescolar – Mañana – Yacuiba Centro” existente,
- **When** el Superadmin intenta crear otra con la misma combinación,
- **Then** el sistema rechaza el alta y muestra la sala en conflicto.

### CA-003: Validación de sucursal activa
- **Given** una sucursal inactiva,
- **When** el Superadmin intenta crear una sala en esa sucursal,
- **Then** el sistema bloquea la operación e informa que la sucursal no está activa.

### CA-004: Alta de ambiente con nombre único
- **Given** una sucursal con un ambiente llamado “Consultorio 1”,
- **When** el Superadmin intenta crear otro ambiente con el mismo nombre en la misma sucursal,
- **Then** el sistema rechaza el alta e informa el conflicto.

### CA-005: Listado filtrado por sucursal
- **Given** un usuario de Recepción asignado solo a “Yacuiba Centro”,
- **When** abre el listado de salas y ambientes,
- **Then** el sistema muestra únicamente los espacios de esa sucursal.

### CA-006: Desactivación con motivo y bloqueo de nuevas operaciones
- **Given** una sala activa sin turnos futuros,
- **When** el Superadmin la desactiva indicando un motivo,
- **Then** el sistema cambia el estado a inactivo, registra el motivo y bloquea nuevas inscripciones o turnos sobre esa sala.

### CA-007: Bloqueo de eliminación con datos asociados
- **Given** una sala con inscripciones y turnos registrados,
- **When** el Superadmin intenta eliminarla,
- **Then** el sistema rechaza la eliminación y ofrece desactivarla.

### CA-008: Coherencia de sucursal en la operación
- **Given** una inscripción en la sucursal “Yacuiba Centro”,
- **When** el usuario intenta asociarle una sala de otra sucursal,
- **Then** el sistema rechaza la operación e informa la inconsistencia.

### CA-009: Restricción de acceso por rol
- **Given** un usuario de Recepción autenticado,
- **When** intenta acceder a “Nueva sala grupal” o “Desactivar”,
- **Then** el sistema no muestra la opción y, si se invoca por API, responde con error de permisos.

### CA-010: Sin control de cupo
- **Given** una sala grupal con varios niños inscritos,
- **When** Recepción inscribe a un niño adicional,
- **Then** el sistema permite la inscripción sin bloquear por cantidad, conforme al alcance definido.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Salas duplicadas en la migración desde cuadernos y pizarra (mismo tipo y turno con nombres distintos) | Alta | Medio | Alto | Normalizar antes de cargar, aplicar la unicidad tipo + turno + sucursal y validar con el cliente. |
| RT-002 | Ambigüedad sobre qué es “ambiente” y qué es “sala” para el cliente | Media | Alto | Alto | Taller de validación de vocabulario y prototipo de pantalla antes de desarrollar. |
| RT-003 | Recepción sigue usando la pizarra en paralelo y el catálogo queda desactualizado | Media | Alto | Alto | Capacitación, regla operativa de que la agenda es la fuente única y control de altas por Administración. |
| RT-004 | Conectividad limitada en el centro al momento de consultar espacios | Media | Medio | Medio | Listados cacheados en el cliente y mensajes claros de reintento. |
| RT-005 | Asociación de un turno a una sala de otra sucursal por error de validación | Media | Alto | Alto | Validación en backend (RF-013), pruebas de integración con módulos 06 y 07 y casos de aceptación CA-008. |
| RT-006 | Presión para incorporar control de cupos no previsto en el alcance | Media | Medio | Medio | Documentar el fuera de alcance y gestionar el pedido como cambio de etapa. |
| RT-007 | Exposición de información de menores a través de listados de espacios mal filtrados | Baja | Alto | Medio | Filtrado por sucursal en backend, pruebas de seguridad y bitácora de accesos. |
| RT-008 | Dependencia de una sola persona para desarrollo y soporte del catálogo | Media | Alto | Alto | Documentación técnica, control de versiones y transferencia de conocimiento planificada. |

---

**Fin del documento**