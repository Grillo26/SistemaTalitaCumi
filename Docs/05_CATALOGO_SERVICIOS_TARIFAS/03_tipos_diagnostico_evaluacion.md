# Tipos de diagnóstico y evaluación
**Módulo:** Catálogo de servicios y tarifas
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra el **catálogo de tipos de diagnóstico o evaluación** que ofrece el centro (por ejemplo, evaluación psicopedagógica, evaluación psicológica, evaluación integral) y las **especialidades profesionales requeridas** por cada tipo. A diferencia de las terapias individuales, el diagnóstico o evaluación es un servicio de costo mayor y de varios días, en el que puede participar más de un profesional. El contexto lo marca explícitamente: cada tipo de diagnóstico requiere uno o más profesionales y la agenda debe poder reflejar esa participación múltiple.

Hoy los tipos de diagnóstico no están formalizados: cada profesional usa su propio nombre para la evaluación y no hay claridad sobre cuántos profesionales se requieren ni en qué especialidad. Este submódulo reemplaza esa dispersión por un catálogo configurable, con la lista de especialidades requeridas por tipo, para que la agenda y los diagnósticos integrales puedan validar la asignación de profesionales. Se integra con Áreas, servicios y colores, Tarifas, categorías y vigencias, Directorio de profesionales, Diagnóstico con varios profesionales, Entrevista y diagnóstico, y Reportes.

El valor que aporta es la calidad y la consistencia del proceso diagnóstico: el cliente define qué tipos existen y qué profesionales se requieren, y el sistema valida que la asignación sea coherente. Esto mejora la atención del niño y facilita la planificación de la agenda.

---

## 2. Alcance

### Incluido en este módulo

- Administración del **catálogo de tipos de diagnóstico/evaluación**, con nombre único, descripción, especialidades profesionales requeridas (una o varias) y estado.
- Asociación de cada tipo de diagnóstico a **uno o más profesionales requeridos por especialidad** (por ejemplo, “evaluación psicopedagógica” requiere un psicopedagogo; “evaluación integral” requiere psicólogo y psicopedagogo).
- Validación de que el tipo de diagnóstico se pueda asignar solo a profesionales activos con la especialidad requerida y en la sucursal correspondiente.
- Búsqueda, listado y filtrado de tipos de diagnóstico por estado y especialidad.
- Registro en bitácora de altas, ediciones y cambios de estado.
- Bloqueo de eliminación cuando el tipo tiene diagnósticos registrados; solo se permite desactivarlo.
- Exportación del catálogo a PDF y Excel.
- Provisión del catálogo a los módulos de diagnóstico, agenda y reportes.

### Excluido de este módulo

- **Servicios y áreas:** submódulo 01.
- **Tarifas y categorías:** submódulo 02; el tipo de diagnóstico es un servicio del catálogo, y su tarifa se define allí.
- **Asignación efectiva de profesionales a un diagnóstico concreto:** submódulo 03 del módulo 03 y módulo 07.
- **Registro clínico del diagnóstico y sus resultados:** módulo 11 (Etapa 2 o 3).
- **Diagnóstico como servicio (entrevista, evaluación, terapias):** el servicio se gestiona en los módulos 01 y 06.
- **Facturación:** fuera de alcance del sistema.
- **Integraciones externas:** fuera de alcance.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita y desactiva tipos de diagnóstico y sus especialidades requeridas. |
| Administración | Consulta el catálogo; **no edita**. |
| Recepción | Consulta el catálogo al inscribir un diagnóstico y al agendar. |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **El catálogo de tipos de diagnóstico es global.**
- **La asignación de profesionales** se valida contra la matriz profesional–especialidad–sucursal (módulo 03, submódulo 02).
- **La tarifa del diagnóstico** puede variar por sucursal (submódulo 02).
- **Los datos clínicos** asociados al diagnóstico se protegen por rol y sucursal.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que cada tipo de diagnóstico define las especialidades requeridas y la cantidad de profesionales por especialidad (por ejemplo, “un psicólogo y un psicopedagogo”). Si el cliente requiere que un tipo de diagnóstico pueda tener profesionales alternativos (por ejemplo, “psicólogo o psicopedagogo”), debe confirmarse antes de implementar.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita saber qué tipos de diagnóstico existen y qué profesionales se requieren para inscribirlo y agendarlo. Con un catálogo formal, la agenda puede validar que los profesionales asignados cubren las especialidades requeridas y evitar diagnósticos incompletos. Administración obtiene reportes de tipos de diagnóstico más frecuentes y de carga por profesional. El cliente administra el catálogo sin depender del equipo técnico.

### 3.2 Justificación en la Atención al Niño y su Familia

Un diagnóstico bien conformado mejora la calidad de la atención y reduce la necesidad de repetir evaluaciones. La familia recibe un proceso claro, con profesionales adecuados y plazos definidos, y el niño es evaluado por quienes corresponden a su necesidad.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** interés superior del niño y derecho a una evaluación integral [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** identificación del profesional y su especialidad en cada etapa del diagnóstico [verificar vigencia].
- **Ley 223 General para Personas con Discapacidad:** cuando corresponda, evaluación con enfoque de derechos y apoyos [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales, incluidos datos clínicos [verificar vigencia].
- **Constitución Política del Estado:** derechos a la salud y a la privacidad [verificar vigencia y articulado aplicable].

La formalización del tipo de diagnóstico y de las especialidades requeridas contribuye a un proceso evaluativo trazable y de calidad.

### 3.4 Justificación Tecnológica

Frente al registro manual, el catálogo aporta: definición explícita de especialidades por tipo; validación en backend de la asignación de profesionales; base para la agenda y los reportes; y bitácora de cambios. Se reduce la improvisación en el proceso diagnóstico y se facilita la planificación.

---

## 4. Funciones Principales

### FP-01: Administrar tipos de diagnóstico
- **Descripción:** Permite crear, editar y desactivar tipos de diagnóstico con sus especialidades requeridas, definiendo cuántos profesionales se necesitan por especialidad.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Catálogo vigente y consumible por la inscripción, la agenda y los diagnósticos integrales.

### FP-02: Validar especialidades requeridas
- **Descripción:** Expone las especialidades requeridas por tipo para que la agenda y los diagnósticos validen que los profesionales asignados las cubren.
- **Actor principal:** Sistema (invocado por agenda y diagnósticos).
- **Resultado esperado:** Diagnósticos conformados por profesionales adecuados y sin improvisación.

### FP-03: Consultar el catálogo
- **Descripción:** Permite listar y filtrar tipos de diagnóstico por estado y especialidad, y consultar la ficha con su configuración.
- **Actor principal:** Superadmin, Administración, Recepción.
- **Resultado esperado:** Catálogo visible, filtrable y exportable.

### FP-04: Auditar cambios del catálogo
- **Descripción:** Registra altas, ediciones y cambios de estado, permitiendo reconstruir la configuración vigente en una fecha.
- **Actor principal:** Superadmin (consulta); Sistema (registro).
- **Resultado esperado:** Trazabilidad del catálogo de diagnósticos a lo largo del tiempo.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear tipo de diagnóstico | El sistema debe permitir registrar un tipo de diagnóstico con nombre único, descripción y estado. | Alta | Superadmin |
| RF-002 | Asociar especialidades requeridas | El sistema debe permitir asociar una o más especialidades requeridas al tipo, con la cantidad de profesionales necesarios por especialidad. | Alta | Superadmin |
| RF-003 | Validar unicidad | El sistema debe rechazar tipos de diagnóstico con el mismo nombre. | Alta | Sistema |
| RF-004 | Editar tipo de diagnóstico | El sistema debe permitir modificar el tipo y sus especialidades requeridas, registrando valores anteriores y nuevos en bitácora. | Alta | Superadmin |
| RF-005 | Desactivar tipo de diagnóstico | El sistema debe permitir desactivar un tipo de diagnóstico con motivo, bloqueando su uso en nuevas inscripciones. | Alta | Superadmin |
| RF-006 | Listar y buscar tipos | El sistema debe mostrar un listado paginado con filtros por estado y especialidad. | Alta | Superadmin, Administración, Recepción |
| RF-007 | Consultar ficha | El sistema debe mostrar la ficha del tipo con sus especialidades requeridas y estado. | Alta | Superadmin, Administración, Recepción |
| RF-008 | Validar asignación de profesionales | El sistema debe exponer las especialidades requeridas para que la agenda y los diagnósticos validen que los profesionales asignados las cubren. | Alta | Sistema |
| RF-009 | Bloquear eliminación con diagnósticos | El sistema debe impedir la eliminación física de un tipo de diagnóstico con diagnósticos registrados; solo permitirá desactivarlo. | Alta | Sistema |
| RF-010 | Registrar bitácora | El sistema debe registrar altas, ediciones y cambios de estado con valores anteriores y nuevos. | Alta | Sistema |
| RF-011 | Proveer catálogo vía API | El sistema debe exponer los tipos de diagnóstico y sus especialidades requeridas vía API a los módulos consumidores. | Alta | Sistema |
| RF-012 | Restringir acceso por rol | El sistema debe impedir que Administración y Recepción editen el catálogo. | Alta | Sistema |
| RF-013 | Exportar catálogo | El sistema debe permitir exportar el catálogo filtrado a PDF y Excel. | Baja | Superadmin, Administración |

> [SUPUESTO A VALIDAR CON EL CLIENTE] La granularidad de “cantidad de profesionales por especialidad” y la posibilidad de especialidades alternativas (por ejemplo, “psicólogo o psicopedagogo”) deben confirmarse. También debe confirmarse si el tipo de diagnóstico es un servicio del catálogo del submódulo 01 o una entidad aparte; se asume que es un servicio con atributos adicionales.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La consulta del catálogo debe responder en menos de 1 segundo con hasta 50 tipos de diagnóstico.
- **RNF-002:** La consulta de especialidades requeridas por tipo debe resolverse en menos de 100 ms con cacheo.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen rol Superadmin.
- **RNF-004:** El catálogo no contiene datos personales; los diagnósticos asociados sí contienen datos clínicos y se protegen en sus módulos.
- **RNF-005:** Las especialidades requeridas son datos operativos, no sensibles, pero su modificación queda en bitácora.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir el catálogo y sus históricos.

### 6.4 Usabilidad

- **RNF-008:** La configuración de especialidades requeridas debe ser visual y clara, indicando cuántos profesionales se necesitan por especialidad.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en tipos de diagnóstico y especialidades sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El catálogo debe exponerse vía API REST a agenda, diagnósticos y reportes, y exportarse a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** El catálogo de especialidades debe provenir del módulo 03; el de tipos de diagnóstico debe ser configurable y versionado.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, edición y cambio de estado debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Catálogo global — Los tipos de diagnóstico son globales; las tarifas por sucursal se definen en el submódulo 02.
- **RN-002:** Unicidad de tipo — El nombre del tipo de diagnóstico debe ser único.
- **RN-003:** Especialidades requeridas — Todo tipo de diagnóstico debe tener al menos una especialidad requerida.
- **RN-004:** Cantidad por especialidad — Cada tipo define cuántos profesionales se necesitan por especialidad.
- **RN-005:** Validación de asignación — Un diagnóstico solo puede asignarse a profesionales activos que cubran las especialidades requeridas en la sucursal correspondiente.
- **RN-006:** Tipo inactivo — Un tipo de diagnóstico inactivo no puede usarse en nuevas inscripciones; los diagnósticos ya registrados conservan su tipo.
- **RN-007:** No eliminación con historial — Un tipo de diagnóstico con diagnósticos registrados no puede eliminarse; solo desactivarse.
- **RN-008:** Tipo como servicio — El tipo de diagnóstico es un servicio del catálogo del submódulo 01, con atributos adicionales de especialidades requeridas.
- **RN-009:** Motivo obligatorio — El cambio de estado requiere motivo registrado en bitácora.
- **RN-010:** Alcance por rol — Superadmin administra todo el catálogo; Administración y Recepción solo consultan.
- **RN-011:** Trazabilidad de cambios — Las modificaciones de especialidades requeridas no afectan los diagnósticos ya realizados; solo los nuevos.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-008 (tipo de diagnóstico como servicio) debe confirmarse con el cliente y con el diseño del módulo 01. Si el cliente prefiere una entidad aparte, se ajusta el modelo.

---

## 8. Casos de Uso Principales

### CU-01: Registrar tipo de diagnóstico con especialidades requeridas

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar tipo de diagnóstico con especialidades requeridas |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Catálogo de especialidades disponible; sesión con rol Superadmin |
| **Postcondiciones** | Tipo de diagnóstico creado con sus especialidades requeridas y bitácora |
| **Trigger** | El Superadmin selecciona “Nuevo tipo de diagnóstico” |

**Flujo Principal:**
1. El Superadmin selecciona “Nuevo tipo de diagnóstico”.
2. El sistema muestra el formulario con nombre, descripción y especialidades requeridas.
3. El Superadmin completa los datos y agrega las especialidades con la cantidad de profesionales por especialidad.
4. El sistema valida unicidad y que haya al menos una especialidad requerida.
5. El sistema guarda el tipo y registra en bitácora.
6. El sistema confirma y muestra el tipo en el listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede guardar el tipo como inactivo para configurarlo antes de habilitarlo.

**Flujos de Excepción:**
- **FE-01:** Nombre duplicado → el sistema informa y no guarda.
- **FE-02:** Sin especialidades requeridas → el sistema bloquea el guardado.

### CU-02: Consultar especialidades requeridas al agendar diagnóstico

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Consultar especialidades requeridas al agendar diagnóstico |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Tipo de diagnóstico activo; profesionales disponibles |
| **Postcondiciones** | Recepción conoce las especialidades requeridas y puede asignar profesionales adecuados |
| **Trigger** | Recepción inscribe un diagnóstico y comienza a asignar profesionales |

**Flujo Principal:**
1. Recepción selecciona el tipo de diagnóstico al inscribir.
2. El sistema muestra las especialidades requeridas y la cantidad de profesionales por especialidad.
3. Recepción asigna profesionales según la matriz profesional–especialidad–sucursal.
4. El sistema valida que los profesionales cubran las especialidades requeridas.
5. La agenda registra el diagnóstico con sus participantes.

**Flujos Alternativos:**
- **FA-01:** La asignación puede completarse en varias sesiones, guardando parciales.

**Flujos de Excepción:**
- **FE-01:** Falta un profesional requerido → el sistema advierte y no cierra el diagnóstico hasta completar.
- **FE-02:** El profesional no cubre la especialidad → el sistema rechaza la asignación.

### CU-03: Desactivar tipo de diagnóstico

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Desactivar tipo de diagnóstico |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Tipo existente y activo |
| **Postcondiciones** | Tipo inactivo, bloqueado para nuevas inscripciones, con bitácora |
| **Trigger** | El Superadmin selecciona “Desactivar” en la ficha del tipo |

**Flujo Principal:**
1. El Superadmin abre la ficha del tipo de diagnóstico.
2. El Superadmin selecciona “Desactivar”.
3. El sistema solicita motivo.
4. El Superadmin ingresa motivo y confirma.
5. El sistema cambia el estado a inactivo.
6. El sistema registra en bitácora.
7. El sistema confirma la desactivación.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; el tipo sigue activo.

**Flujos de Excepción:**
- **FE-01:** Motivo vacío → el sistema no permite continuar.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de tipo de diagnóstico
- **Given** un Superadmin autenticado y especialidades disponibles,
- **When** registra un tipo con sus especialidades requeridas,
- **Then** el sistema lo crea activo y lo muestra en el listado con bitácora.

### CA-002: Rechazo por nombre duplicado
- **Given** un tipo “Evaluación Psicopedagógica” existente,
- **When** el Superadmin intenta crear otro con el mismo nombre,
- **Then** el sistema rechaza el alta e informa el conflicto.

### CA-003: Especialidad requerida obligatoria
- **Given** el formulario sin especialidades requeridas,
- **When** el Superadmin intenta guardar,
- **Then** el sistema bloquea el guardado e indica el requisito.

### CA-004: Consulta de especialidades al inscribir
- **Given** un tipo de diagnóstico activo,
- **When** Recepción lo selecciona al inscribir,
- **Then** el sistema muestra las especialidades requeridas y la cantidad por especialidad.

### CA-005: Validación de asignación
- **Given** un diagnóstico que requiere psicólogo y psicopedagogo,
- **When** Recepción intenta cerrar el diagnóstico solo con un psicólogo,
- **Then** el sistema advierte que falta el psicopedagogo.

### CA-006: Desactivación con motivo
- **Given** un tipo de diagnóstico activo,
- **When** el Superadmin lo desactiva con motivo,
- **Then** el sistema cambia el estado y registra la acción.

### CA-007: Bloqueo de eliminación con historial
- **Given** un tipo con diagnósticos registrados,
- **When** el Superadmin intenta eliminarlo,
- **Then** el sistema rechaza la eliminación y ofrece desactivarlo.

### CA-008: Inmutabilidad en diagnósticos históricos
- **Given** un diagnóstico ya realizado con un tipo,
- **When** el Superadmin modifica las especialidades requeridas del tipo,
- **Then** el diagnóstico conserva sus especialidades originales.

### CA-009: Restricción por rol
- **Given** un usuario de Administración,
- **When** intenta editar un tipo de diagnóstico,
- **Then** el sistema no muestra la opción y rechaza por API.

### CA-010: Exportación del catálogo
- **Given** un Superadmin autenticado,
- **When** exporta el catálogo de tipos,
- **Then** el sistema genera PDF y Excel con los tipos y sus especialidades requeridas.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Catálogo desalineado con la práctica real del centro | Media | Alto | Alto | Taller de definición con el cliente y validación de tipos y especialidades. |
| RT-002 | Ambigüedad entre tipo de diagnóstico y servicio | Alta | Medio | Alto | Documentar la relación con el módulo 01 y ajustar el modelo si el cliente lo requiere. |
| RT-003 | Diagnósticos incompletos por falta de profesionales | Media | Medio | Medio | Validación en backend y aviso en la agenda. |
| RT-004 | Cambios de especialidades que afecten diagnósticos en curso | Media | Medio | Medio | Regla de inmutabilidad en diagnósticos ya iniciados. |
| RT-005 | Presión para automatizar asignaciones fuera de alcance | Media | Medio | Medio | Documentar el fuera de alcance y gestionar como cambio de etapa. |
| RT-006 | Confusión con el módulo 11 (resultados del diagnóstico) | Media | Medio | Medio | Nombrar claramente cada entidad y documentar la separación. |
| RT-007 | Falta de profesionales que cubran las especialidades requeridas | Media | Alto | Alto | Reporte de cobertura por especialidad (módulo 03) y validación al inscribir. |
| RT-008 | Dependencia de una sola persona para administrar el catálogo | Media | Medio | Medio | Documentación y transferencia de conocimiento. |

---

**Fin del documento**