# Áreas, servicios y colores
**Módulo:** Catálogo de servicios y tarifas
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra dos catálogos que ordenan la oferta del centro: las **áreas** (Intervención Terapéutica y Salas Grupales, y las que el cliente agregue) y los **servicios** que se ofrecen dentro de cada área (entrevista con los padres, diagnóstico o evaluación, atención psicológica, psicopedagógica, ocupacional, fisioterapia, terapia de lenguaje, y las salas grupales como servicios). Además, define los **colores configurables por área**, que se usan únicamente para diferenciar visualmente en la agenda.

Hoy los servicios y las áreas no están formalizados en un catálogo único: se nombran de distintas formas según la persona y el cuaderno, y los colores se eligen de memoria. Este submódulo reemplaza esa dispersión por un catálogo administrado por el cliente, con identificadores estables, unicidad controlada, estado y colores por área. Se integra con Tarifas, categorías y vigencias, Tipos de diagnóstico y evaluación, Inscripciones, Agenda y turnos, Reportes e Inventario (cuando aplica).

El valor que aporta es la consistencia: todos los módulos consumen los mismos servicios y áreas, la agenda se pinta con los colores configurados y los reportes se agrupan por área y servicio. Sin este catálogo, los demás módulos no pueden referenciar servicios ni tarifas correctamente.

---

## 2. Alcance

### Incluido en este módulo

- Administración del **catálogo de áreas** (Intervención Terapéutica, Salas Grupales y las que el cliente agregue), con nombre único, descripción, color (configurable) y estado.
- Administración del **catálogo de servicios**, con nombre único, área a la que pertenece, tipo de servicio (entrevista, diagnóstico/evaluación, terapia individual, sala grupal), duración por defecto si aplica, y estado.
- Asignación del **color por área**, usado únicamente para diferenciar en la agenda; no tiene otro efecto funcional.
- Validación de unicidad por área y por servicio dentro de su área.
- Búsqueda, listado y filtrado por área, tipo y estado.
- Registro en bitácora de altas, ediciones y cambios de estado.
- Bloqueo de eliminación cuando el área o servicio tiene tarifas, inscripciones o turnos asociados; solo se permite desactivarlo.
- Exportación del catálogo a PDF y Excel.
- Provisión de los servicios y colores a los módulos consumidores.

### Excluido de este módulo

- **Tarifas, categorías de tarifa y vigencias:** corresponde al submódulo `02_tarifas_categorias_vigencias.md`.
- **Tipos de diagnóstico y evaluación:** corresponde al submódulo `03_tipos_diagnostico_evaluacion.md`.
- **Asignación de salas a servicios:** las salas se gestionan en el módulo 01 y se asocian a la inscripción en el módulo 06.
- **Definición de qué profesional cubre cada servicio:** se gestiona en el módulo 03 (matriz profesional–especialidad–sucursal).
- **Diseño visual de la agenda más allá del color:** módulo 07.
- **Facturación y precios:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita, activa y desactiva áreas y servicios; configura colores. |
| Administración | Consulta el catálogo; **no edita** áreas ni servicios. |
| Recepción | Consulta el catálogo para inscribir y agendar. |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **El catálogo de áreas y servicios es global:** se define una vez y aplica a todas las sucursales.
- **Las tarifas por servicio y por sucursal** se definen en el submódulo 02.
- **Los colores por área son globales** y se usan para diferenciar visualmente la agenda de todas las sucursales.
- **Los datos de menores** asociados a turnos de cada servicio se protegen por rol y sucursal.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que el catálogo de áreas se limita a las dos definidas en el contexto (Intervención Terapéutica y Salas Grupales) y que el cliente puede agregar más. Confirmar si se requiere una tercera área para actividades u otros. También debe confirmarse si la duración por defecto del servicio es un dato del servicio o se define por turno.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita un catálogo único para inscribir y agendar sin ambigüedades: “atención psicológica” debe ser siempre el mismo servicio, sin importar quién lo registre. Con áreas y servicios formalizados, la agenda se organiza por área con colores consistentes, los reportes se agrupan correctamente y las tarifas pueden asociarse a servicios específicos. La administración del catálogo queda en manos del cliente, sin depender del equipo técnico.

### 3.2 Justificación en la Atención al Niño y su Familia

La familia recibe información consistente sobre el servicio que recibe el niño, y la agenda muestra claramente el área a la que pertenece cada turno. Esto reduce confusiones, mejora la comunicación con la familia y refuerza la percepción de orden del centro.

### 3.3 Justificación Normativa

El submódulo no introduce una norma específica sobre áreas y servicios, pero contribuye al cumplimiento de:

- **Ley 548, Código Niña, Niño y Adolescente:** resguardo de la información de menores y organización de la atención [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** identificación clara del servicio prestado [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La formalización de servicios y áreas contribuye a que el registro clínico identifique correctamente el servicio prestado.

### 3.4 Justificación Tecnológica

Frente al registro manual, el catálogo aporta: identificadores únicos; validación de unicidad; relación explícita servicio–área; colores configurables sin cambios de código; y base para tarifas, inscripciones, agenda y reportes. Se reduce la duplicación y se evita que módulos distintos usen nombres distintos para el mismo servicio.

---

## 4. Funciones Principales

### FP-01: Administrar áreas y colores
- **Descripción:** Permite crear, editar y desactivar áreas (Intervención Terapéutica, Salas Grupales y las que se agreguen) y configurar el color de cada una para la agenda.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Catálogo de áreas vigente, con colores consistentes usados por la agenda.

### FP-02: Administrar servicios
- **Descripción:** Permite crear, editar y desactivar servicios dentro de cada área, definiendo su tipo (entrevista, diagnóstico/evaluación, terapia individual, sala grupal) y su estado.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Catálogo de servicios vigente y consumible por tarifas, inscripciones, agenda y reportes.

### FP-03: Consultar el catálogo
- **Descripción:** Permite listar y filtrar áreas y servicios por estado y tipo, y consultar la ficha de cada uno con su configuración actual.
- **Actor principal:** Superadmin, Administración, Recepción.
- **Resultado esperado:** Catálogo visible, filtrable y exportable a PDF y Excel.

### FP-04: Proveer servicios y colores a otros módulos
- **Descripción:** Expone el catálogo vía API para que tarifas, inscripciones, agenda, reportes y otros módulos referencien servicios y áreas correctamente.
- **Actor principal:** Sistema (invocado por módulos consumidores).
- **Resultado esperado:** Consistencia de servicios y colores en todo el sistema.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear área | El sistema debe permitir registrar un área con nombre único, descripción, color y estado. | Alta | Superadmin |
| RF-002 | Editar y desactivar área | El sistema debe permitir renombrar, cambiar color y desactivar áreas; no podrá desactivarse si tiene servicios activos asociados. | Alta | Superadmin |
| RF-003 | Validar unicidad de área | El sistema debe rechazar áreas con el mismo nombre, sin distinguir mayúsculas ni espacios. | Alta | Sistema |
| RF-004 | Configurar color por área | El sistema debe permitir asignar un color a cada área mediante selector o valor hexadecimal, con validación de formato. | Alta | Superadmin |
| RF-005 | Crear servicio | El sistema debe permitir registrar un servicio con nombre único dentro de su área, tipo de servicio, duración por defecto (si aplica) y estado. | Alta | Superadmin |
| RF-006 | Validar unicidad de servicio | El sistema debe rechazar servicios con el mismo nombre dentro de la misma área. | Alta | Sistema |
| RF-007 | Asociar servicio a área | El sistema debe exigir que cada servicio pertenezca a exactamente un área. | Alta | Sistema |
| RF-008 | Listar y buscar servicios | El sistema debe mostrar un listado paginado de servicios con filtros por área, tipo y estado, y búsqueda por nombre. | Alta | Superadmin, Administración, Recepción |
| RF-009 | Listar y buscar áreas | El sistema debe mostrar el listado de áreas con su color y estado, y búsqueda por nombre. | Alta | Superadmin, Administración, Recepción |
| RF-010 | Consultar ficha de servicio | El sistema debe mostrar la ficha del servicio con área, tipo, duración, estado y fecha de última modificación. | Alta | Superadmin, Administración, Recepción |
| RF-011 | Editar servicio | El sistema debe permitir modificar el servicio, registrando valores anteriores y nuevos en bitácora. | Alta | Superadmin |
| RF-012 | Activar y desactivar servicio | El sistema debe permitir cambiar el estado del servicio exigiendo motivo al desactivar. | Alta | Superadmin |
| RF-013 | Bloquear eliminación con datos asociados | El sistema debe impedir la eliminación física de un área o servicio con tarifas, inscripciones o turnos; solo permitirá desactivarlo. | Alta | Sistema |
| RF-014 | Registrar bitácora | El sistema debe registrar en bitácora altas, ediciones, cambios de color y cambios de estado. | Alta | Sistema |
| RF-015 | Proveer catálogo vía API | El sistema debe exponer áreas, servicios y colores vía API REST a los módulos consumidores. | Alta | Sistema |
| RF-016 | Restringir acceso por rol | El sistema debe impedir que Administración y Recepción editen áreas o servicios. | Alta | Sistema |
| RF-017 | Exportar catálogo | El sistema debe permitir exportar áreas y servicios a PDF y Excel. | Baja | Superadmin, Administración |

> [SUPUESTO A VALIDAR CON EL CLIENTE] La lista de tipos de servicio (entrevista, diagnóstico/evaluación, terapia individual, sala grupal) debe confirmarse; si el cliente requiere más tipos (por ejemplo, taller o actividad), se debe agregar. También debe confirmarse si la duración por defecto es un dato del servicio o se define por turno.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El listado de servicios debe cargar en menos de 1 segundo con hasta 100 servicios y 20 usuarios concurrentes.
- **RNF-002:** La consulta de colores por área para la agenda debe resolverse en menos de 100 ms con cacheo.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen rol Superadmin.
- **RNF-004:** El catálogo no contiene datos personales; sin embargo, los turnos asociados sí contienen datos de menores y se protegen en sus módulos.
- **RNF-005:** El color configurado se valida para evitar valores inválidos o inyecciones.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir el catálogo de áreas y servicios.

### 6.4 Usabilidad

- **RNF-008:** La selección de color debe ser visual (paleta o selector) y mostrar una vista previa del uso en la agenda.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en áreas, servicios y tipos sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El catálogo debe exponerse vía API REST a tarifas, inscripciones, agenda, reportes e inventario (cuando aplique), y exportarse a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** El catálogo de tipos de servicio debe ser configurable y versionado, sin valores fijos en el código.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, edición, cambio de color y cambio de estado debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Catálogo global — Áreas y servicios son globales y aplican a todas las sucursales; las tarifas por sucursal se definen en el submódulo 02.
- **RN-002:** Unicidad de área — El nombre del área debe ser único en el sistema.
- **RN-003:** Unicidad de servicio por área — El nombre del servicio debe ser único dentro de su área; el mismo nombre puede repetirse en áreas distintas.
- **RN-004:** Servicio pertenece a un área — Todo servicio pertenece a exactamente un área.
- **RN-005:** Color con fin visual — El color del área se usa solo para diferenciar en la agenda; no tiene otro efecto funcional.
- **RN-006:** Servicio inactivo — Un servicio inactivo no puede usarse en nuevas inscripciones ni en nuevos turnos; sus registros históricos se conservan.
- **RN-007:** Área inactiva — Un área inactiva no puede contener servicios activos; para desactivarla, deben desactivarse antes sus servicios.
- **RN-008:** No eliminación con historial — Un área o servicio con tarifas, inscripciones o turnos no puede eliminarse; solo desactivarse con motivo.
- **RN-009:** Tipos de servicio — Los tipos de servicio los define el cliente a partir de un catálogo configurable (entrevista, diagnóstico/evaluación, terapia individual, sala grupal).
- **RN-010:** Motivo obligatorio — El cambio de estado de un área o servicio requiere motivo registrado en bitácora.
- **RN-011:** Alcance por rol — Superadmin administra todo el catálogo; Administración y Recepción solo consultan.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-005 (color solo con fin visual) y la RN-007 (área inactiva sin servicios activos) deben confirmarse; son reglas operativas que conviene dejar explícitas.

---

## 8. Casos de Uso Principales

### CU-01: Registrar área con color

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar área con color |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin |
| **Postcondiciones** | Área creada en estado activo con color configurado y bitácora |
| **Trigger** | El Superadmin selecciona “Nueva área” en Catálogo de servicios y tarifas |

**Flujo Principal:**
1. El Superadmin selecciona “Nueva área”.
2. El sistema muestra el formulario con nombre, descripción, color y estado.
3. El Superadmin ingresa los datos y elige un color.
4. El sistema valida unicidad y formato del color.
5. El sistema guarda el área en estado activo.
6. El sistema registra el alta en bitácora.
7. El sistema confirma y muestra el área en el listado con su color.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede crear el área como inactiva para configurarla antes de habilitarla.

**Flujos de Excepción:**
- **FE-01:** Nombre duplicado → el sistema informa y no guarda.
- **FE-02:** Color con formato inválido → el sistema rechaza el guardado.

### CU-02: Registrar servicio

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Registrar servicio |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Área destino activa; catálogo de tipos de servicio disponible |
| **Postcondiciones** | Servicio creado en estado activo y disponible para tarifas, inscripciones y agenda |
| **Trigger** | El Superadmin selecciona “Nuevo servicio” |

**Flujo Principal:**
1. El Superadmin selecciona “Nuevo servicio”.
2. El sistema muestra el formulario con nombre, área, tipo de servicio, duración por defecto (si aplica) y estado.
3. El Superadmin completa los datos.
4. El sistema valida unicidad por área y campos obligatorios.
5. El sistema guarda el servicio en estado activo.
6. El sistema registra el alta en bitácora.
7. El sistema confirma y muestra el servicio en el listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede crear el servicio como inactivo para configurarlo antes de habilitarlo.

**Flujos de Excepción:**
- **FE-01:** Nombre duplicado en el área → el sistema informa y no guarda.
- **FE-02:** Falta el tipo de servicio → el sistema bloquea el guardado.

### CU-03: Desactivar área o servicio

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Desactivar área o servicio |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Área o servicio existente y activo, sin dependencias activas |
| **Postcondiciones** | Área o servicio inactivo con motivo y bitácora |
| **Trigger** | El Superadmin selecciona “Desactivar” en la ficha del área o servicio |

**Flujo Principal:**
1. El Superadmin abre la ficha del área o servicio.
2. El Superadmin selecciona “Desactivar”.
3. El sistema solicita motivo.
4. El Superadmin ingresa motivo y confirma.
5. El sistema verifica dependencias (servicios activos en el área, tarifas o turnos en el servicio).
6. El sistema cambia el estado a inactivo y registra en bitácora.
7. El sistema confirma la desactivación.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; el área o servicio sigue activo.

**Flujos de Excepción:**
- **FE-01:** El área tiene servicios activos → el sistema lo impide y sugiere desactivarlos primero.
- **FE-02:** Motivo vacío → el sistema no permite continuar.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de área
- **Given** un Superadmin autenticado y un nombre no usado,
- **When** registra un área con color válido,
- **Then** el sistema crea el área activa y la muestra con su color en el listado.

### CA-002: Rechazo por nombre duplicado de área
- **Given** un área “Intervención Terapéutica” existente,
- **When** el Superadmin intenta crear otra con el mismo nombre,
- **Then** el sistema rechaza el alta e informa el conflicto.

### CA-003: Alta exitosa de servicio
- **Given** un área activa y un nombre de servicio no usado en esa área,
- **When** el Superadmin registra el servicio,
- **Then** el sistema crea el servicio activo y lo muestra en el listado.

### CA-004: Rechazo por nombre duplicado de servicio
- **Given** un servicio “Atención Psicológica” en Intervención Terapéutica,
- **When** el Superadmin intenta crear otro con el mismo nombre en la misma área,
- **Then** el sistema rechaza el alta e informa el conflicto.

### CA-005: Servicio en otra área con mismo nombre
- **Given** un servicio “Atención Psicológica” en Intervención Terapéutica,
- **When** el Superadmin crea “Atención Psicológica” en otra área,
- **Then** el sistema permite el alta porque la unicidad es por área.

### CA-006: Color usado en la agenda
- **Given** un área con color configurado,
- **When** la agenda muestra turnos de esa área,
- **Then** los turnos se pintan con el color del área.

### CA-007: Desactivación de área con servicios activos
- **Given** un área con servicios activos,
- **When** el Superadmin intenta desactivarla,
- **Then** el sistema lo impide e indica desactivar primero los servicios.

### CA-008: Desactivación de servicio con tarifas o turnos
- **Given** un servicio con turnos registrados,
- **When** el Superadmin intenta eliminarlo,
- **Then** el sistema rechaza la eliminación y ofrece desactivarlo.

### CA-009: Restricción por rol
- **Given** un usuario de Administración,
- **When** intenta crear o editar un área o servicio,
- **Then** el sistema no muestra la opción y rechaza por API.

### CA-010: Exportación del catálogo
- **Given** un Superadmin autenticado,
- **When** exporta el catálogo,
- **Then** el sistema genera PDF y Excel con áreas y servicios vigentes.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Duplicación de servicios en la migración inicial | Alta | Medio | Alto | Plantilla de migración, normalización y validación con el cliente. |
| RT-002 | Colores que no se reflejan correctamente en la agenda | Media | Medio | Medio | Validación de formato, pruebas con la agenda y vista previa en la configuración. |
| RT-003 | Desactivación de área con servicios activos que rompa la operación | Media | Medio | Medio | Validación al desactivar y guía al usuario para desactivar servicios primero. |
| RT-004 | Catálogo que no refleja la realidad operativa tras cambios | Media | Medio | Medio | Revisión periódica con el cliente y bitácora de cambios. |
| RT-005 | Ambigüedad en la lista de tipos de servicio | Alta | Medio | Alto | Definir con el cliente antes de implementar. |
| RT-006 | Cambio de nombre de servicio que afecte históricos | Media | Bajo | Bajo | Los históricos conservan el nombre al momento del registro; se documenta. |
| RT-007 | Dependencia de una sola persona para administrar el catálogo | Media | Medio | Medio | Documentación y transferencia de conocimiento. |
| RT-008 | Colores mal elegidos que afecten la legibilidad de la agenda | Media | Bajo | Bajo | Vista previa y recomendaciones de contraste en la interfaz. |

---

**Fin del documento**