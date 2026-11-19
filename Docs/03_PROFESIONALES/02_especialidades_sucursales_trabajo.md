# Especialidades y sucursales de trabajo
**Módulo:** Profesionales
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo define, por un lado, el catálogo de especialidades que ofrece el centro (atención psicológica, psicopedagógica, ocupacional, fisioterapia, terapia de lenguaje y las que el cliente agregue) y, por otro, la relación entre cada profesional, sus especialidades y las sucursales donde presta servicios. Ambos elementos son necesarios para que la agenda, los diagnósticos integrales y los reportes puedan referenciar correctamente al profesional y su ámbito de trabajo.

Hoy esta información no está formalizada: cada profesional “hace de todo un poco” según la demanda del día y las sucursales donde trabaja se acuerdan verbalmente. Este submódulo reemplaza esa informalidad por un catálogo único de especialidades y una matriz profesional–especialidad–sucursal, administrada por el cliente, con vigencia y estado, y con trazabilidad de cambios.

El valor que aporta es la organización de la oferta profesional: permite saber qué especialidades ofrece el centro, qué profesional las cubre y en qué sucursal, y evita asignaciones imposibles (por ejemplo, un profesional de terapia de lenguaje en una sucursal donde no trabaja). Se integra con el Directorio de profesionales, el Catálogo de servicios y tarifas, la Agenda y turnos, los Diagnósticos integrales con varios profesionales y los Reportes por especialidad y sucursal.

---

## 2. Alcance

### Incluido en este módulo

- Administración del **catálogo de especialidades** (nombre, descripción, estado), sin valores fijos en el código.
- Asignación de la **especialidad principal** y de **especialidades secundarias** (si se confirman) a cada profesional.
- Registro de las **sucursales de trabajo** de cada profesional, con posibilidad de activar o desactivar la relación.
- Validación de que un profesional solo pueda asignarse a turnos y diagnósticos de especialidades y sucursales en las que trabaja.
- Búsqueda y filtrado de profesionales por especialidad y sucursal.
- Bitácora de altas, ediciones y cambios de estado en especialidades y en la matriz profesional–especialidad–sucursal.
- Exportación de la matriz a PDF y Excel.
- Exposición de la matriz a los módulos consumidores (agenda, diagnósticos, reportes).

### Excluido de este módulo

- **Datos de identificación del profesional:** corresponde al submódulo `01_directorio_profesionales.md`.
- **Asignación de profesionales a turnos (opcional):** corresponde al submódulo `03_asignacion_opcional_turnos.md`.
- **Horarios de atención del profesional por día y sucursal:** fuera de alcance en Etapa 1; la agenda opera con turnos fijos por inscripción. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **Nómina, liquidaciones y control laboral:** fuera del alcance del sistema.
- **Tarifas por especialidad:** corresponde al módulo 05 (Catálogo de servicios y tarifas).
- **Integraciones externas:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Administra el catálogo de especialidades y la matriz profesional–especialidad–sucursal. |
| Administración | Consulta la matriz de sus sucursales; **no edita** especialidades ni asignaciones. |
| Recepción | Consulta la matriz para la agenda y la asignación opcional de turnos. |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **El catálogo de especialidades es global.**
- **La matriz profesional–especialidad–sucursal es específica por sucursal:** una misma especialidad puede estar cubierta por el profesional en una sucursal y no en otra.
- **Los datos de menores y de salud** quedan protegidos por la combinación rol + sucursal.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que un profesional puede tener una especialidad principal y varias secundarias, y que la relación con sucursales se gestiona independientemente de la especialidad. Si el cliente prefiere que cada especialidad se asocie a sucursales específicas (especialidad–sucursal), debe confirmarse antes de implementar.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita saber, al agendar o asignar un turno, qué profesional cubre la especialidad requerida en la sucursal de la inscripción. Con esta matriz, la agenda puede validar la asignación y evitar errores como asignar a un profesional que no trabaja en esa sede o que no tiene la especialidad. Administración obtiene reportes de cobertura por especialidad y sucursal.

### 3.2 Justificación en la Atención al Niño y su Familia

La familia recibe atención del profesional adecuado para la necesidad del niño y en la sucursal correcta. En diagnósticos integrales, el sistema puede verificar que los profesionales involucrados cubren las especialidades requeridas en la sucursal correspondiente, lo que mejora la calidad del proceso.

### 3.3 Justificación Normativa

- **Ley 3131, de Ejercicio Profesional Médico:** identificación del profesional y de su especialidad en cada atención [verificar vigencia].
- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de información de menores [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La matriz contribuye a que el registro clínico identifique correctamente al profesional y su especialidad, condición para el secreto profesional y para la trazabilidad de la atención.

### 3.4 Justificación Tecnológica

Frente al registro informal, la matriz aporta: catálogo único de especialidades; validación en backend de la combinación profesional–especialidad–sucursal; bitácora de cambios; y base para reportes de cobertura. Se evita la duplicación de especialidades con nombres distintos y se habilita la validación de asignaciones en agenda y diagnósticos.

---

## 4. Funciones Principales

### FP-01: Administrar el catálogo de especialidades
- **Descripción:** Permite crear, editar y desactivar especialidades con nombre único, descripción y estado. El catálogo es consumido por el directorio, la agenda y los reportes.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Catálogo vigente y consistente de especialidades disponibles en el centro.

### FP-02: Asignar especialidades a profesionales
- **Descripción:** Permite definir la especialidad principal y, si se confirma, las secundarias de cada profesional, con registro en bitácora.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Profesionales con especialidades claras, disponibles para asignación en agenda y diagnósticos.

### FP-03: Registrar sucursales de trabajo
- **Descripción:** Permite activar o desactivar la relación entre un profesional y cada sucursal, definiendo dónde presta servicios.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Matriz profesional–sucursal vigente y consistente con la agenda.

### FP-04: Validar combinación profesional–especialidad–sucursal
- **Descripción:** Expone la matriz a los módulos consumidores para que rechacen asignaciones incompatibles (profesional sin la especialidad o sin la sucursal).
- **Actor principal:** Sistema (invocado por agenda, diagnósticos y reportes).
- **Resultado esperado:** Asignaciones válidas y reportes confiables por especialidad y sucursal.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear especialidad | El sistema debe permitir registrar especialidades con nombre único, descripción y estado. | Alta | Superadmin |
| RF-002 | Editar y desactivar especialidad | El sistema debe permitir renombrar y desactivar especialidades; no podrá desactivarse si tiene profesionales activos asociados en alguna sucursal. | Alta | Superadmin |
| RF-003 | Listar especialidades | El sistema debe mostrar el catálogo de especialidades con filtros por estado y búsqueda por nombre. | Alta | Superadmin, Administración, Recepción |
| RF-004 | Asignar especialidad principal | El sistema debe permitir asignar exactamente una especialidad principal por profesional. | Alta | Superadmin |
| RF-005 | Asignar especialidades secundarias | El sistema debe permitir, si el cliente lo confirma, asignar una o varias especialidades secundarias por profesional. | Media | Superadmin |
| RF-006 | Registrar sucursales de trabajo | El sistema debe permitir asociar un profesional a una o varias sucursales activas y activar o desactivar cada relación. | Alta | Superadmin |
| RF-007 | Validar unicidad de relación | El sistema debe impedir duplicar la relación profesional–especialidad y profesional–sucursal. | Alta | Sistema |
| RF-008 | Consultar matriz profesional–especialidad–sucursal | El sistema debe mostrar la matriz vigente filtrable por profesional, especialidad y sucursal. | Alta | Superadmin, Administración |
| RF-009 | Validar asignación en agenda y diagnósticos | El sistema debe rechazar la asignación de un profesional a un turno o diagnóstico si no cubre la especialidad requerida o no trabaja en la sucursal. | Alta | Sistema |
| RF-010 | Registrar bitácora | El sistema debe registrar en bitácora cambios en especialidades y en la matriz profesional–especialidad–sucursal, con valores anteriores y nuevos. | Alta | Sistema |
| RF-011 | Impedir eliminación con historial | El sistema debe impedir la eliminación física de una especialidad o relación con turnos o diagnósticos asociados; solo permitirá desactivarla. | Alta | Sistema |
| RF-012 | Exportar matriz | El sistema debe permitir exportar la matriz filtrada a PDF y Excel. | Baja | Superadmin, Administración |
| RF-013 | Restringir operaciones sobre especialidad inactiva | El sistema debe impedir asignar una especialidad inactiva a nuevos profesionales o turnos. | Alta | Sistema |
| RF-014 | Restringir acceso por rol | El sistema debe impedir que Administración y Recepción editen especialidades o asignaciones. | Alta | Sistema |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Debe confirmarse si el cliente requiere especialidades secundarias por profesional y si la relación profesional–sucursal debe depender de la especialidad (es decir, si un profesional puede trabajar en una sucursal solo para ciertas especialidades). Se asume que la relación es independiente para simplificar la Etapa 1.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La consulta de la matriz debe cargar en menos de 1 segundo con hasta 50 profesionales, 10 especialidades y 5 sucursales.
- **RNF-002:** La validación de asignación en agenda y diagnósticos debe resolverse en menos de 100 ms por operación, con cacheo de la matriz vigente.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen rol Superadmin.
- **RNF-004:** La validación de la matriz debe ejecutarse en backend.
- **RNF-005:** Los datos de profesionales y de menores asociados deben tratarse conforme a la política de privacidad y a la normativa aplicable.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir el catálogo de especialidades y la matriz.

### 6.4 Usabilidad

- **RNF-008:** La matriz debe presentarse en una grilla clara, con filtros por profesional, especialidad y sucursal.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar la incorporación de nuevas especialidades, sucursales y profesionales sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** La matriz debe exponerse vía API REST para agenda, diagnósticos y reportes, y exportarse a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** El catálogo de especialidades debe ser configurable y versionado; la matriz se gestionará con migraciones controladas.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Todo cambio en especialidades o en la matriz debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Catálogo configurable — Las especialidades las define el cliente; no hay valores fijos en el código.
- **RN-002:** Unicidad de especialidad — El nombre de la especialidad debe ser único en el sistema.
- **RN-003:** Especialidad principal única — Cada profesional tiene exactamente una especialidad principal.
- **RN-004:** Especialidades secundarias opcionales — Si el cliente las confirma, un profesional puede tener varias especialidades secundarias además de la principal.
- **RN-005:** Sucursal de trabajo obligatoria — Todo profesional activo debe tener al menos una sucursal de trabajo activa para poder asignarse a turnos.
- **RN-006:** Validación de asignación — Un profesional solo puede asignarse a turnos y diagnósticos de especialidades y sucursales en las que trabaja.
- **RN-007:** Especialidad inactiva — Una especialidad inactiva no puede asignarse a nuevos profesionales ni a nuevos turnos; su histórico se conserva.
- **RN-008:** No eliminación con historial — Una especialidad con profesionales o turnos asociados no puede eliminarse; solo desactivarse.
- **RN-009:** Relación independiente especialidad–sucursal — En Etapa 1 la relación profesional–sucursal es independiente de la especialidad; si el cliente requiere dependencia, se evalúa como cambio de alcance.
- **RN-010:** Motivo obligatorio — El cambio de estado de una especialidad o de una relación profesional–sucursal requiere motivo registrado en bitácora.
- **RN-011:** Alcance por rol — Superadmin administra todo; Administración y Recepción solo consultan.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-009 (relación independiente especialidad–sucursal) y la RN-004 (especialidades secundarias) deben confirmarse. Si el cliente requiere que un profesional cubra una especialidad solo en ciertas sucursales, la matriz debe extenderse a profesional–especialidad–sucursal.

---

## 8. Casos de Uso Principales

### CU-01: Registrar especialidad

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar especialidad |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin |
| **Postcondiciones** | Especialidad creada en estado activo y disponible para asignación |
| **Trigger** | El Superadmin selecciona “Nueva especialidad” en Profesionales |

**Flujo Principal:**
1. El Superadmin selecciona “Nueva especialidad”.
2. El sistema muestra el formulario.
3. El Superadmin ingresa nombre, descripción y estado.
4. El sistema valida unicidad de nombre y campos obligatorios.
5. El sistema guarda la especialidad en estado activo.
6. El sistema registra el alta en bitácora.
7. El sistema confirma y muestra la especialidad en el listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede crear la especialidad como inactiva para configurarla antes de habilitarla.

**Flujos de Excepción:**
- **FE-01:** Nombre duplicado → el sistema informa y no guarda.
- **FE-02:** Falta el nombre → el sistema bloquea el guardado.

### CU-02: Asignar sucursales de trabajo a un profesional

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Asignar sucursales de trabajo a un profesional |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Profesional existente; sucursales activas disponibles |
| **Postcondiciones** | Profesional asociado a una o varias sucursales, con bitácora |
| **Trigger** | El Superadmin abre la ficha del profesional y elige “Sucursales de trabajo” |

**Flujo Principal:**
1. El Superadmin abre la ficha del profesional.
2. El sistema muestra las sucursales activas con la relación actual.
3. El Superadmin activa o desactiva las sucursales correspondientes.
4. El sistema valida que el profesional quede con al menos una sucursal activa.
5. El sistema guarda y registra en bitácora los cambios.
6. El sistema confirma la actualización.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; la relación anterior se mantiene.

**Flujos de Excepción:**
- **FE-01:** Se intenta dejar al profesional sin sucursal → el sistema rechaza e informa.
- **FE-02:** Otro usuario modificó la relación mientras se editaba → el sistema advierte el conflicto.

### CU-03: Validar asignación de profesional en un turno

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Validar asignación de profesional en un turno |
| **Actor Principal** | Sistema (invocado por agenda) |
| **Actores Secundarios** | — |
| **Precondiciones** | Turno en edición; profesional y sucursal definidos |
| **Postcondiciones** | Asignación permitida o rechazada según la matriz |
| **Trigger** | Recepción intenta asignar un profesional a un turno |

**Flujo Principal:**
1. Recepción selecciona un profesional para un turno.
2. La agenda invoca el servicio de validación con profesional, especialidad del servicio y sucursal del turno.
3. El servicio verifica que el profesional esté activo, cubra la especialidad y trabaje en la sucursal.
4. El servicio devuelve permitido o denegado con motivo.
5. La agenda confirma o rechaza la asignación.

**Flujos Alternativos:**
- **FA-01:** El turno puede quedar “sin asignar” si Recepción no selecciona profesional.

**Flujos de Excepción:**
- **FE-01:** El profesional no cubre la especialidad → la agenda rechaza la asignación y sugiere alternativas.
- **FE-02:** El profesional no trabaja en la sucursal → la agenda rechaza la asignación e informa.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de especialidad
- **Given** un Superadmin autenticado y un nombre no usado,
- **When** registra la especialidad,
- **Then** el sistema la crea activa y la muestra en el catálogo con bitácora.

### CA-002: Rechazo por nombre duplicado
- **Given** una especialidad “Fisioterapia” existente,
- **When** el Superadmin intenta crear otra con el mismo nombre,
- **Then** el sistema rechaza el alta e informa el conflicto.

### CA-003: Asignación de sucursales
- **Given** un profesional activo y dos sucursales activas,
- **When** el Superadmin le asigna una sucursal,
- **Then** el sistema registra la relación y la refleja en la matriz con bitácora.

### CA-004: Sucursal obligatoria
- **Given** un profesional activo con una sucursal asignada,
- **When** el Superadmin intenta quitarle la última sucursal,
- **Then** el sistema rechaza la operación e informa el requisito.

### CA-005: Validación de especialidad en turno
- **Given** un profesional con especialidad “Psicología”,
- **When** Recepción intenta asignarlo a un turno de “Fisioterapia”,
- **Then** el sistema rechaza la asignación e informa el motivo.

### CA-006: Validación de sucursal en turno
- **Given** un profesional que no trabaja en “Yacuiba Norte”,
- **When** Recepción intenta asignarlo a un turno de esa sucursal,
- **Then** el sistema rechaza la asignación e informa el motivo.

### CA-007: Desactivación de especialidad con historial
- **Given** una especialidad con profesionales activos,
- **When** el Superadmin intenta desactivarla,
- **Then** el sistema lo impide y sugiere reasignar primero.

### CA-008: Restricción por rol
- **Given** un usuario de Administración,
- **When** intenta editar especialidades o asignaciones,
- **Then** el sistema no muestra la opción y rechaza por API.

### CA-009: Consulta de matriz
- **Given** un Superadmin autenticado,
- **When** consulta la matriz filtrada por sucursal,
- **Then** el sistema muestra la relación vigente profesional–especialidad–sucursal.

### CA-010: Exportación
- **Given** un Superadmin autenticado,
- **When** exporta la matriz,
- **Then** el sistema genera PDF y Excel con la relación vigente y la fecha de exportación.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Catálogo de especialidades desalineado con los servicios del módulo 05 | Media | Alto | Alto | Coordinar el catálogo con el cliente y validar consistencia entre módulos. |
| RT-002 | Matriz incompleta que bloquee asignaciones legítimas | Media | Medio | Medio | Revisión con el cliente, validación al guardar y pruebas de asignación. |
| RT-003 | Dependencia de especialidad por sucursal no prevista | Media | Alto | Alto | Documentar el supuesto, evaluar como cambio de alcance si el cliente lo requiere. |
| RT-004 | Desactivación de especialidad con profesionales activos | Media | Medio | Medio | Validar antes de desactivar y guiar al usuario a reasignar. |
| RT-005 | Cache de matriz desactualizado que permita asignaciones inválidas | Media | Medio | Medio | Invalidación de cache al guardar cambios y TTL corto. |
| RT-006 | Ambigüedad sobre especialidades secundarias | Alta | Medio | Alto | Confirmar con el cliente y ajustar el modelo antes de implementar. |
| RT-007 | Exposición de la matriz a roles no autorizados | Baja | Bajo | Bajo | Control de acceso por rol y pruebas de scoping. |
| RT-008 | Crecimiento de la matriz sin mantenimiento que genere datos obsoletos | Media | Medio | Medio | Revisión periódica y reportes de cobertura por especialidad y sucursal. |

---

**Fin del documento**