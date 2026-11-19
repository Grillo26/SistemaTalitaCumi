# Tarifas, categorías y vigencias
**Módulo:** Catálogo de servicios y tarifas
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra las **tarifas** de los servicios del centro, sus **categorías** (usadas por Recepción al inscribir) y sus **vigencias**, con la posibilidad de definir tarifas específicas por sucursal cuando corresponda. Es el catálogo que determina cuánto cuesta cada servicio y que alimenta la inscripción y el registro de pagos. Los precios los define el cliente; la categoría de tarifa la elige Recepción al inscribir.

Hoy los precios viven en listas de papel, en mensajes de WhatsApp y en la memoria del personal, sin control de vigencia ni de sucursal. Esto genera cobros inconsistentes, discusiones con la familia y dificultad para saber qué precio estaba vigente en una fecha. Este submódulo reemplaza esa informalidad por un catálogo con vigencia, categorías y control de cambios, que el cliente administra sin intervención técnica.

El valor que aporta es doble: por un lado, garantiza precios consistentes y auditables; por otro, permite reflejar la realidad del negocio cuando hay tarifas diferenciadas por sucursal o por categoría (por ejemplo, hermanos, convenios). Se integra con Áreas, servicios y colores, Tipos de diagnóstico y evaluación, Inscripciones, Pagos y caja, y Reportes de ingresos.

---

## 2. Alcance

### Incluido en este módulo

- Administración del **catálogo de categorías de tarifa** (por ejemplo, general, hermanos, convenio), con nombre único, descripción y estado.
- Administración de **tarifas** por servicio, con monto en bolivianos, vigencia (fecha de inicio y fin) y, si corresponde, **tarifa por sucursal**.
- Validación de que no existan dos tarifas vigentes para el mismo servicio, sucursal y categoría en el mismo período.
- Selección de la categoría de tarifa por parte de Recepción al inscribir.
- Búsqueda y filtrado de tarifas por servicio, sucursal, categoría y vigencia.
- Registro en bitácora de altas, ediciones y cambios de vigencia.
- Bloqueo de eliminación de tarifas con inscripciones o pagos asociados; solo se permite cerrar la vigencia.
- Exportación del catálogo de tarifas a PDF y Excel.
- Provisión de la tarifa vigente a los módulos de inscripciones, pagos y reportes.

### Excluido de este módulo

- **Servicios y áreas:** corresponde al submódulo 01.
- **Tipos de diagnóstico y evaluación:** submódulo 03.
- **Registro de pagos, comprobantes y caja:** módulo 08.
- **Plan de cuotas y gestión de deudas:** Etapa 2 (módulo 08, submódulo 05).
- **Descuentos automáticos por categoría:** fuera de alcance en Etapa 1; la categoría selecciona una tarifa, no aplica descuento automático. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **Facturación electrónica:** fuera de alcance del sistema.
- **Integraciones externas:** fuera de alcance.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita y cierra vigencias de tarifas y categorías; define tarifas por sucursal. |
| Administración | Consulta el catálogo y puede editar tarifas de sus sucursales (según confirmación del cliente). |
| Recepción | Consulta las tarifas vigentes y selecciona la categoría al inscribir. |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **El catálogo de categorías es global.**
- **Las tarifas pueden ser globales o específicas por sucursal**, según lo defina el cliente.
- **La tarifa aplicable a una inscripción** se determina por la combinación servicio + sucursal + categoría + fecha de vigencia.
- **Los pagos y comprobantes** referencian la tarifa aplicada al momento de la inscripción.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que una tarifa puede definirse a nivel global (aplica a todas las sucursales) o a nivel sucursal (solo a esa sede). Debe confirmarse si la tarifa por sucursal reemplaza o complementa a la global. También debe confirmarse si la categoría de tarifa puede cambiar durante la vigencia de una inscripción (por ejemplo, al cumplir el hermano) o si se fija al inscribir.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción necesita saber, al inscribir, cuál es el precio vigente del servicio para la sucursal y la categoría del paciente. Con un catálogo con vigencia, el sistema sugiere la tarifa correcta, evita cobros inconsistentes y deja registro de la tarifa aplicada. Administración puede auditar los cambios de precio y su impacto en los ingresos. El cliente administra el catálogo sin depender del equipo técnico.

### 3.2 Justificación en la Atención al Niño y su Familia

La familia recibe información de precio consistente y transparente, y el comprobante refleja la tarifa aplicada al momento de la inscripción. La posibilidad de tarifas diferenciadas por categoría (hermanos, convenios) permite reflejar acuerdos con la familia sin improvisación.

### 3.3 Justificación Normativa

- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales, incluidos los datos económicos de la familia [verificar vigencia].
- **Ley 548, Código Niña, Niño y Adolescente:** transparencia en la prestación de servicios a la niñez [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** marco para el tratamiento electrónico de información económica [verificar vigencia].
- **Constitución Política del Estado:** derechos del consumidor y a la información veraz [verificar vigencia y articulado aplicable].

El comprobante (aunque no es factura) y la trazabilidad de la tarifa aplicada son mecanismos de transparencia frente a la familia.

### 3.4 Justificación Tecnológica

Frente al registro manual, el catálogo aporta: vigencia controlada; validación de unicidad por servicio + sucursal + categoría + período; selección automática de la tarifa al inscribir; y bitácora de cambios. Se reducen errores de cobro y se facilita la auditoría de ingresos.

---

## 4. Funciones Principales

### FP-01: Administrar categorías de tarifa
- **Descripción:** Permite crear, editar y desactivar categorías (general, hermanos, convenio) que Recepción usa al inscribir.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Catálogo de categorías vigente y disponible en la inscripción.

### FP-02: Administrar tarifas con vigencia y sucursal
- **Descripción:** Permite registrar tarifas por servicio, monto, vigencia y sucursal (si aplica), validando que no haya solapamientos.
- **Actor principal:** Superadmin (y Administración según confirmación).
- **Resultado esperado:** Tarifas vigentes y consistentes, con historial de vigencias anteriores.

### FP-03: Consultar tarifa vigente
- **Descripción:** Dado un servicio, sucursal, categoría y fecha, el sistema devuelve la tarifa aplicable; es la base para inscribir y cobrar.
- **Actor principal:** Recepción, Sistema.
- **Resultado esperado:** Tarifa correcta y auditable en cada inscripción y pago.

### FP-04: Auditar cambios de tarifas
- **Descripción:** Registra y permite consultar cada alta, edición y cierre de vigencia, con valores anteriores y nuevos.
- **Actor principal:** Superadmin (consulta); Sistema (registro).
- **Resultado esperado:** Trazabilidad completa del catálogo de precios a lo largo del tiempo.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear categoría de tarifa | El sistema debe permitir registrar categorías con nombre único, descripción y estado. | Alta | Superadmin |
| RF-002 | Editar y desactivar categoría | El sistema debe permitir editar y desactivar categorías; no podrá desactivarse si tiene tarifas vigentes asociadas. | Alta | Superadmin |
| RF-003 | Crear tarifa | El sistema debe permitir registrar una tarifa con servicio, monto, moneda (bolivianos), vigencia desde/hasta y sucursal (opcional). | Alta | Superadmin |
| RF-004 | Asociar categoría a tarifa | El sistema debe permitir asociar una tarifa a una categoría específica. | Alta | Superadmin |
| RF-005 | Validar solapamiento | El sistema debe rechazar tarifas solapadas para el mismo servicio, sucursal, categoría y período. | Alta | Sistema |
| RF-006 | Validar monto | El sistema debe exigir un monto mayor o igual a cero, en bolivianos, con dos decimales. | Alta | Sistema |
| RF-007 | Validar vigencia | El sistema debe exigir fecha de inicio y permitir fecha de fin; la fecha de fin debe ser posterior a la de inicio. | Alta | Sistema |
| RF-008 | Consultar tarifa vigente | El sistema debe devolver la tarifa aplicable dado un servicio, sucursal, categoría y fecha. | Alta | Recepción, Sistema |
| RF-009 | Listar y buscar tarifas | El sistema debe mostrar un listado paginado con filtros por servicio, sucursal, categoría y vigencia. | Alta | Superadmin, Administración, Recepción |
| RF-010 | Consultar ficha de tarifa | El sistema debe mostrar la ficha con servicio, sucursal, categoría, monto, vigencia y estado. | Alta | Superadmin, Administración, Recepción |
| RF-011 | Editar tarifa | El sistema debe permitir editar una tarifa futura o vigente, registrando valores anteriores y nuevos en bitácora. | Alta | Superadmin |
| RF-012 | Cerrar vigencia | El sistema debe permitir cerrar la vigencia de una tarifa (fijando fecha de fin) sin eliminarla. | Alta | Superadmin |
| RF-013 | Bloquear eliminación con historial | El sistema debe impedir la eliminación física de una tarifa con inscripciones o pagos; solo permitirá cerrar la vigencia. | Alta | Sistema |
| RF-014 | Seleccionar categoría al inscribir | El sistema debe permitir a Recepción seleccionar la categoría al inscribir, ofreciendo solo las vigentes. | Alta | Recepción |
| RF-015 | Registrar bitácora | El sistema debe registrar altas, ediciones y cierres de vigencia con valores anteriores y nuevos. | Alta | Sistema |
| RF-016 | Proveer tarifa a otros módulos | El sistema debe exponer la tarifa vigente vía API a inscripciones, pagos y reportes. | Alta | Sistema |
| RF-017 | Restringir acceso por rol | El sistema debe impedir que Recepción edite tarifas; Administración solo edita las de sus sucursales (según confirmación). | Alta | Sistema |
| RF-018 | Exportar catálogo de tarifas | El sistema debe permitir exportar el catálogo filtrado a PDF y Excel. | Baja | Superadmin, Administración |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Debe confirmarse si la tarifa por sucursal reemplaza o complementa a la global, y si Recepción puede ver tarifas de todas las sucursales o solo de las que tiene asignadas. También debe confirmarse si la categoría se fija al inscribir o puede cambiarse durante la inscripción.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La consulta de tarifa vigente debe resolverse en menos de 200 ms con cacheo, para no afectar la inscripción ni el cobro.
- **RNF-002:** El listado de tarifas debe cargar en menos de 1 segundo con hasta 500 tarifas registradas.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigen rol autorizado.
- **RNF-004:** Los montos y tarifas son datos económicos; se muestran solo a roles que los necesitan.
- **RNF-005:** La tarifa aplicada a una inscripción queda registrada de forma inmutable para auditoría.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir tarifas y categorías.

### 6.4 Usabilidad

- **RNF-008:** La consulta de tarifa debe mostrar claramente el monto, la vigencia y la sucursal, y permitir comparar categorías.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en servicios, categorías y sucursales sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El catálogo debe exponerse vía API REST a inscripciones, pagos y reportes, y exportarse a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** Las categorías y la estructura de tarifas deben ser configurables y versionadas.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, edición y cierre de vigencia debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; la tarifa aplicada a cada inscripción es inmutable.

---

## 7. Reglas de Negocio

- **RN-001:** Tarifa en bolivianos — Toda tarifa se expresa en bolivianos con dos decimales; no se admite multimoneda.
- **RN-002:** Tarifa por servicio — Toda tarifa está asociada a un servicio del catálogo del submódulo 01.
- **RN-003:** Vigencia obligatoria — Toda tarifa tiene fecha de inicio de vigencia; la fecha de fin es opcional (vigencia abierta) y puede cerrarse manualmente.
- **RN-004:** Sin solapamiento — No puede existir más de una tarifa vigente para el mismo servicio, sucursal, categoría y período.
- **RN-005:** Tarifa aplicable — La tarifa aplicable a una inscripción se determina por la combinación servicio + sucursal + categoría + fecha de la inscripción.
- **RN-006:** Categoría seleccionada por Recepción — La categoría de tarifa la elige Recepción al inscribir; el sistema no la infiere automáticamente.
- **RN-007:** Tarifa inmutable en inscripción — La tarifa aplicada a una inscripción no cambia aunque el catálogo se modifique después; los cambios afectan a nuevas inscripciones.
- **RN-008:** Cierre de vigencia — Cerrar la vigencia de una tarifa no afecta las inscripciones ya realizadas; solo impide nuevas inscripciones con esa tarifa.
- **RN-009:** No eliminación con historial — Una tarifa con inscripciones o pagos no puede eliminarse; solo cerrarse.
- **RN-010:** Categoría inactiva — Una categoría inactiva no puede seleccionarse en nuevas inscripciones.
- **RN-011:** Alcance por rol y sucursal — Superadmin administra todo; Administración solo sus sucursales (si se confirma); Recepción solo consulta.
- **RN-012:** Motivo en cambios — Los cambios de vigencia y la desactivación de categorías requieren motivo registrado en bitácora.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-005 (combinación servicio + sucursal + categoría + fecha) y la RN-007 (tarifa inmutable en inscripción) deben confirmarse; la segunda es una decisión de diseño importante para la trazabilidad de cobros. También debe confirmarse si Recepción puede ver tarifas de todas las sucursales o solo de las suyas.

---

## 8. Casos de Uso Principales

### CU-01: Registrar tarifa con vigencia

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar tarifa con vigencia |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Servicio activo; categoría activa; sucursal activa (si aplica) |
| **Postcondiciones** | Tarifa creada y disponible para inscripciones en su período de vigencia |
| **Trigger** | El Superadmin selecciona “Nueva tarifa” |

**Flujo Principal:**
1. El Superadmin selecciona “Nueva tarifa”.
2. El sistema muestra el formulario con servicio, sucursal, categoría, monto y vigencia.
3. El Superadmin completa los datos.
4. El sistema valida monto, vigencia y solapamiento.
5. El sistema guarda la tarifa.
6. El sistema registra el alta en bitácora.
7. El sistema confirma y muestra la tarifa en el listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede programar una tarifa futura (vigencia desde fecha posterior) sin afectar la tarifa actual.

**Flujos de Excepción:**
- **FE-01:** Solapamiento con otra tarifa vigente → el sistema informa y no guarda.
- **FE-02:** Monto negativo o formato inválido → el sistema rechaza el guardado.
- **FE-03:** Fecha de fin anterior a la de inicio → el sistema rechaza el guardado.

### CU-02: Consultar tarifa aplicable al inscribir

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Consultar tarifa aplicable al inscribir |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Servicio activo; al menos una tarifa vigente |
| **Postcondiciones** | Tarifa aplicable mostrada y lista para asociar a la inscripción |
| **Trigger** | Recepción inicia una inscripción |

**Flujo Principal:**
1. Recepción selecciona servicio, sucursal y categoría.
2. El sistema consulta la tarifa vigente para esa combinación y la fecha actual.
3. El sistema muestra el monto y la vigencia.
4. Recepción confirma y la inscripción se asocia a esa tarifa.

**Flujos Alternativos:**
- **FA-01:** Existen varias tarifas vigentes (por ejemplo, global y por sucursal); el sistema aplica la más específica según la regla vigente. [SUPUESTO A VALIDAR CON EL CLIENTE]

**Flujos de Excepción:**
- **FE-01:** No hay tarifa vigente → el sistema bloquea la inscripción e informa que debe configurarse una tarifa.
- **FE-02:** La tarifa está vencida o cerrada → el sistema no la ofrece.

### CU-03: Cerrar vigencia de una tarifa

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Cerrar vigencia de una tarifa |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Tarifa existente con vigencia abierta |
| **Postcondiciones** | Tarifa cerrada en la fecha indicada; no se aplica a nuevas inscripciones |
| **Trigger** | El Superadmin selecciona “Cerrar vigencia” en la ficha de la tarifa |

**Flujo Principal:**
1. El Superadmin abre la ficha de la tarifa.
2. El Superadmin selecciona “Cerrar vigencia”.
3. El sistema solicita fecha de fin y motivo.
4. El Superadmin ingresa los datos y confirma.
5. El sistema cierra la vigencia y registra en bitácora.
6. El sistema confirma el cierre.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; la vigencia se mantiene abierta.

**Flujos de Excepción:**
- **FE-01:** Fecha de fin anterior a la de inicio → el sistema rechaza.
- **FE-02:** Motivo vacío → el sistema no permite continuar.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de tarifa
- **Given** un servicio activo y una categoría activa,
- **When** el Superadmin registra una tarifa con monto y vigencia válidos,
- **Then** el sistema crea la tarifa y la muestra en el listado con bitácora.

### CA-002: Rechazo por solapamiento
- **Given** una tarifa vigente para un servicio en una sucursal y categoría,
- **When** el Superadmin intenta crear otra con el mismo servicio, sucursal y categoría en el mismo período,
- **Then** el sistema rechaza el alta e informa el solapamiento.

### CA-003: Tarifa aplicable al inscribir
- **Given** una tarifa vigente para “Atención Psicológica” en “Yacuiba Centro” categoría “General”,
- **When** Recepción inscribe con esos datos,
- **Then** el sistema muestra el monto de esa tarifa y lo asocia a la inscripción.

### CA-004: Sin tarifa vigente
- **Given** un servicio sin tarifa vigente,
- **When** Recepción intenta inscribir,
- **Then** el sistema bloquea la inscripción e indica configurar la tarifa.

### CA-005: Cierre de vigencia
- **Given** una tarifa con vigencia abierta,
- **When** el Superadmin la cierra con motivo,
- **Then** el sistema registra la fecha de fin y no la aplica a nuevas inscripciones.

### CA-006: Inmutabilidad de la tarifa en inscripción
- **Given** una inscripción con tarifa aplicada,
- **When** el Superadmin modifica el catálogo,
- **Then** la inscripción conserva la tarifa original y solo las nuevas inscripciones usan la nueva.

### CA-007: Validación de monto
- **Given** un monto negativo o con formato inválido,
- **When** el Superadmin intenta guardar la tarifa,
- **Then** el sistema rechaza el guardado e informa el formato esperado.

### CA-008: Categoría inactiva
- **Given** una categoría inactiva,
- **When** Recepción intenta seleccionarla al inscribir,
- **Then** el sistema no la ofrece.

### CA-009: Restricción por rol
- **Given** un usuario de Recepción,
- **When** intenta editar una tarifa,
- **Then** el sistema no muestra la opción y rechaza por API.

### CA-010: Exportación del catálogo
- **Given** un Superadmin autenticado,
- **When** exporta el catálogo de tarifas,
- **Then** el sistema genera PDF y Excel con tarifas y vigencias.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Carga inicial de tarifas incompleta o desactualizada | Alta | Alto | Alto | Plantilla de migración, validación con el cliente y pruebas de inscripción. |
| RT-002 | Inscripciones sin tarifa vigente por olvido de configuración | Media | Alto | Alto | Validación al inscribir y alerta al Superadmin cuando un servicio no tiene tarifa. |
| RT-003 | Solapamiento de tarifas por error de vigencia | Media | Alto | Alto | Validación estricta y pruebas con CA-002. |
| RT-004 | Cambio de tarifa que afecte inscripciones pasadas | Media | Medio | Medio | Regla RN-007 (inmutabilidad) y registro de la tarifa aplicada en la inscripción. |
| RT-005 | Categorías mal definidas que confundan a Recepción | Media | Medio | Medio | Taller de definición de categorías con el cliente y ejemplos claros. |
| RT-006 | Ambigüedad entre tarifa global y por sucursal | Alta | Medio | Alto | Definir la precedencia con el cliente y documentar en RN. |
| RT-007 | Exposición de tarifas a roles no autorizados | Baja | Bajo | Bajo | Control de acceso por rol. |
| RT-008 | Dependencia de una sola persona para administrar tarifas | Media | Medio | Medio | Documentación y transferencia de conocimiento. |

---

**Fin del documento**