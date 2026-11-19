**Archivo:** `01_ORGANIZACION_SUCURSALES/01_gestion_sucursales.md`

---

# Gestión de sucursales
**Módulo:** Organización y sucursales
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo permite registrar y mantener las sucursales del Centro Integral de Desarrollo Infantil TalitaCumi, que opera como una única empresa con varias sedes. La sucursal es la unidad operativa a la que pertenecen los turnos, las inscripciones, los pagos, la caja diaria y las salas; en cambio, los pacientes y sus tutores son globales y pueden atenderse en más de una sede. Por eso la correcta definición de sucursales es la base sobre la que se apoyan la agenda, la caja y los reportes de todo el sistema.

Hoy la información de las sedes vive en cuadernos, pizarras de turnos, libros de recibos y mensajes de WhatsApp, sin una fuente única que permita saber qué profesional, qué sala, qué caja o qué inscripción corresponde a cada sede. Este submódulo reemplaza ese registro disperso por un catálogo administrado por el cliente, con datos de contacto, dirección y estado, y con un identificador estable que el resto de los módulos usará para acotar sus operaciones.

El valor que aporta es doble: por un lado, ordena la operación multi-sede y evita cruces de información entre sucursales; por otro, habilita el control de acceso por sucursal exigido por el contexto (cada usuario accede a una o varias sucursales) y sirve de ancla para las tarifas por sucursal, las salas por sucursal y el cierre de caja por sucursal. Se integra principalmente con Usuarios y roles, Salas y ambientes, Catálogo de servicios y tarifas, Agenda y turnos, Pagos y caja, y Reportes.

---

## 2. Alcance

### Incluido en este módulo

- Registro, edición y consulta de sucursales con sus datos identificatorios y de contacto.
- Definición de un código único de sucursal y de un nombre único.
- Gestión del estado de la sucursal (activa / inactiva) con motivo de cambio.
- Validación de unicidad y de campos obligatorios.
- Búsqueda, listado y filtrado de sucursales según el alcance del usuario.
- Bitácora de creación, modificación y cambio de estado.
- Bloqueo de eliminación física cuando la sucursal tiene datos asociados.
- Exportación del listado de sucursales a PDF y Excel.
- Provisión del identificador de sucursal al resto de los módulos (scoping).

### Excluido de este módulo

- **Gestión de usuarios y asignación usuario–sucursal:** corresponde al módulo 02 (Usuarios, roles y seguridad). Aquí solo se define la sucursal como entidad.
- **Gestión de salas y ambientes:** corresponde al submódulo `01_ORGANIZACION_SUCURSALES/02_gestion_salas_ambientes.md`.
- **Parámetros generales del sistema:** corresponde al submódulo `01_ORGANIZACION_SUCURSALES/03_configuracion_general_parametros.md`.
- **Tarifas por sucursal y vigencias:** corresponde al módulo 05.
- **Reportes de ingresos, caja y ocupación por sucursal:** corresponde al módulo 09; aquí solo se provee la dimensión sucursal.
- **Facturación electrónica, integraciones externas y app móvil:** fuera de alcance del sistema según el contexto.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita, activa y desactiva sucursales; administra el catálogo completo. |
| Administración | Consulta y edita datos de contacto/dirección de las sucursales a las que tiene acceso; no crea ni desactiva. |
| Recepción | Consulta la ficha y el listado de sucursales a las que está asignada; no crea ni edita. |
| Profesionales y padres | **No usan el sistema** (según contexto). No son actores de este submódulo. |

### Sucursales aplicables

- **La entidad “sucursal” es global:** el catálogo lo administra el Superadmin y es visible según el alcance de cada usuario.
- **Los datos de la propia sucursal son de la sucursal:** nombre, código, dirección, teléfonos y estado describen a esa sede.
- **Los pacientes y tutores son globales:** no se duplican por sucursal.
- **Turnos, inscripciones, pagos, caja y salas pertenecen a una sucursal:** este submódulo es el que provee esa referencia.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que cada sucursal tendrá, como mínimo, código, nombre, dirección, teléfono principal y estado. La lista definitiva de campos (p. ej., email, responsable de sede, horario de atención, coordenadas) debe confirmarse con el cliente antes de la implementación.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción y Administración trabajan hoy con información repartida entre cuadernos y pizarras por sede, lo que dificulta saber a qué sucursal corresponde cada turno, cada inscripción y cada cierre de caja. Con un catálogo único de sucursales, cada operación queda etiquetada con su sede, se reducen los errores de imputación y se habilita el filtrado por sucursal en la agenda y en la caja diaria. Además, el bloqueo de eliminación con datos asociados evita perder trazabilidad histórica.

### 3.2 Justificación en la Atención al Niño y su Familia

Aunque los padres no usan el sistema, la correcta delimitación de sucursales impacta en su experiencia: el niño puede atenderse en la sede que corresponde a su inscripción, los turnos se agendan en la sede correcta y el comprobante de pago refleja la sede donde se cobró. Esto reduce confusiones, reprogramaciones innecesarias y desplazamientos erróneos de las familias.

### 3.3 Justificación Normativa

El submódulo no introduce una norma específica sobre sucursales, pero hereda las obligaciones generales del sistema en materia de datos de menores y datos de salud:

- **Constitución Política del Estado:** derechos a la privacidad, intimidad y protección de la niñez [verificar vigencia y articulado aplicable].
- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de la información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** secreto profesional y conservación de registros clínicos, aplicable a los datos de salud que el sistema almacena aunque los profesionales no operen el sistema [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento de datos personales y deber de resguardo [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** marco general para el tratamiento electrónico de información [verificar vigencia].

Este submódulo, en particular, contribuye a la norma porque permite identificar con precisión la sede responsable de cada registro y sostener la trazabilidad exigible en caso de auditoría.

### 3.4 Justificación Tecnológica

Frente al registro manual, el catálogo de sucursales aporta: (i) un identificador estable y único que el resto de los módulos usa como clave foránea; (ii) validaciones automáticas de unicidad y de campos obligatorios; (iii) bitácora de cambios con usuario, fecha y motivo; (iv) bloqueo de eliminación cuando existen datos asociados; y (v) base para reportes consistentes por sucursal. Todo esto reduce la duplicación de datos, evita cruces entre sedes y facilita la migración ordenada desde los cuadernos.

---

## 4. Funciones Principales

### FP-01: Registrar sucursal
- **Descripción:** Permite dar de alta una nueva sede con su código, nombre, datos de contacto y dirección. El sistema valida unicidad y campos obligatorios antes de guardar.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Sucursal creada y disponible para ser referenciada por usuarios, salas, tarifas, agenda y caja.

### FP-02: Consultar y buscar sucursales
- **Descripción:** Permite listar y filtrar las sucursales a las que el usuario tiene acceso, y abrir la ficha de una sucursal con sus datos y estado. El listado se ajusta según el rol y las sucursales asignadas.
- **Actor principal:** Superadmin, Administración y Recepción.
- **Resultado esperado:** Vista consolidada del catálogo de sucursales, filtrada por alcance del usuario, con opción de exportar a PDF/Excel.

### FP-03: Actualizar datos y estado de sucursal
- **Descripción:** Permite modificar los datos de una sucursal existente y cambiar su estado (activa/inactiva) registrando el motivo. Los cambios quedan en la bitácora.
- **Actor principal:** Superadmin (todos los campos y estado); Administración (datos de contacto/dirección de sus sucursales).
- **Resultado esperado:** Sucursal actualizada con historial de cambios y estado vigente aplicado al resto del sistema.

### FP-04: Proveer el alcance de sucursal a los demás módulos
- **Descripción:** Expone el identificador y el estado de la sucursal para que agenda, inscripciones, pagos, caja, salas y reportes acoten sus operaciones por sede y respeten el acceso del usuario.
- **Actor principal:** Sistema (invocado por los módulos consumidores).
- **Resultado esperado:** Consistencia del scoping por sucursal en todo el sistema y bloqueo de operaciones sobre sucursales inactivas.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear sucursal | El sistema debe permitir registrar una sucursal con código, nombre, dirección, teléfono y estado inicial (activa por defecto). El código y el nombre deben ser únicos. | Alta | Superadmin |
| RF-002 | Validar unicidad de sucursal | Al guardar, el sistema debe rechazar códigos o nombres duplicados (comparación sin distinguir mayúsculas/espacios) y mostrar el campo en conflicto. | Alta | Sistema |
| RF-003 | Validar campos obligatorios | El sistema debe exigir código, nombre y estado; el resto de campos serán obligatorios según la configuración validada con el cliente. | Alta | Sistema |
| RF-004 | Listar y buscar sucursales | El sistema debe mostrar un listado paginado de sucursales con búsqueda por código, nombre y estado, respetando el alcance del usuario. | Alta | Superadmin, Administración, Recepción |
| RF-005 | Consultar ficha de sucursal | El sistema debe mostrar la ficha de una sucursal con sus datos, estado y fecha de última modificación. | Alta | Superadmin, Administración, Recepción |
| RF-006 | Editar datos de sucursal | El sistema debe permitir modificar los datos de la sucursal; los cambios se registran en bitácora con usuario, fecha y valores anteriores/nuevos. | Alta | Superadmin, Administración |
| RF-007 | Activar/desactivar sucursal | El sistema debe permitir cambiar el estado de la sucursal entre activa e inactiva, exigiendo un motivo cuando se desactiva. | Alta | Superadmin |
| RF-008 | Bloquear eliminación con datos asociados | El sistema debe impedir la eliminación física de una sucursal que tenga usuarios, salas, inscripciones, turnos, pagos o caja asociados; solo permitirá desactivarla. | Alta | Sistema |
| RF-009 | Asignar usuarios a sucursal (integración) | El sistema debe exponer la sucursal para que el módulo 02 asigne uno o varios usuarios a una o varias sucursales. | Media | Superadmin |
| RF-010 | Aplicar scoping por sucursal | El sistema debe filtrar automáticamente los datos y operaciones según las sucursales asignadas al usuario autenticado. | Alta | Sistema |
| RF-011 | Registrar bitácora de cambios | El sistema debe registrar en bitácora las acciones de creación, edición, activación y desactivación de sucursales. | Alta | Sistema |
| RF-012 | Exportar listado de sucursales | El sistema debe permitir exportar el listado filtrado a PDF y Excel. | Baja | Superadmin, Administración |
| RF-013 | Validar formato de contacto | El sistema debe validar el formato de teléfono y, si se incorpora, email, y mostrar mensajes de error claros. | Media | Sistema |
| RF-014 | Restringir acceso por rol | El sistema debe impedir que Recepción cree, edite o desactive sucursales, y que Administración cree o desactive sucursales. | Alta | Sistema |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El listado de sucursales debe cargar en menos de 1 segundo con hasta 50 sucursales registradas y 20 usuarios concurrentes.
- **RNF-002:** La consulta de ficha de sucursal debe responder en menos de 800 ms en condiciones normales de operación.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS; las contraseñas de los usuarios que operan el módulo se almacenan con hash seguro (según el módulo 02).
- **RNF-004:** El acceso a los datos de sucursal debe validarse por rol y por sucursal asignada en el backend, no solo en la interfaz.
- **RNF-005:** Los datos de menores y de salud asociados indirectamente a la sucursal deben tratarse conforme a la normativa aplicable y a la política de privacidad definida con el cliente.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en horario operativo del centro (lunes a sábado, horario a validar), con un objetivo de disponibilidad del 99,5 % mensual.
- **RNF-007:** Los respaldos de la base de datos deben ser diarios y permitir una recuperación ante desastre (RTO/RPO) acordada con el cliente.

### 6.4 Usabilidad

- **RNF-008:** La interfaz debe estar en español, usar el vocabulario del negocio (sucursal, sala, turno, inscripción, comprobante) y requerir pocos clics para las operaciones frecuentes.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet, con personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar la incorporación de nuevas sucursales sin cambios de código, manteniendo el rendimiento dentro de los umbrales definidos.

### 6.6 Interoperabilidad

- **RNF-011:** El módulo debe exportar el listado de sucursales a PDF y Excel; no se contemplan integraciones externas en esta etapa.

### 6.7 Mantenibilidad

- **RNF-012:** Los catálogos y campos de la sucursal deben ser configurables vía parámetros; el esquema se gestionará con migraciones versionadas y control de versiones del código.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda creación, modificación o cambio de estado de una sucursal debe quedar en bitácora con usuario, fecha, hora, acción y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Unicidad de sucursal — El código y el nombre de la sucursal deben ser únicos en todo el sistema; no puede existir una sucursal con el mismo código o nombre que otra.
- **RN-002:** Estado inicial — Toda sucursal nueva se crea en estado “activa” salvo indicación expresa del Superadmin.
- **RN-003:** Sucursal inactiva — Una sucursal inactiva no puede recibir nuevas inscripciones, turnos, pagos ni aperturas de caja; sus datos históricos se conservan para consulta y reporte.
- **RN-004:** No eliminación con datos — Una sucursal con usuarios, salas, inscripciones, turnos, pagos o movimientos de caja asociados no puede eliminarse; solo desactivarse.
- **RN-005:** Motivo obligatorio — El cambio de estado (activa → inactiva o inactiva → activa) requiere un motivo registrado en la bitácora.
- **RN-006:** Alcance por rol — El Superadmin administra todo el catálogo; Administración solo consulta y edita datos de contacto/dirección de sus sucursales; Recepción solo consulta sus sucursales asignadas.
- **RN-007:** Pertenencia de entidades operativas — Turnos, inscripciones, pagos, caja y salas deben referenciar exactamente una sucursal; pacientes y tutores no se duplican por sucursal.
- **RN-008:** Datos globales vs. por sucursal — Los pacientes y tutores son globales; la sucursal aplica a las entidades operativas y a la asignación de usuarios.
- **RN-009:** Moneda y zona horaria — Toda sucursal opera con moneda única (bolivianos) y la zona horaria del país; no se admite configuración por sucursal en esta etapa.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-003 (bloqueo de operaciones en sucursal inactiva) y la RN-005 (motivo obligatorio) deben confirmarse con el cliente, ya que el contexto no las detalla explícitamente.

---

## 8. Casos de Uso Principales

### CU-01: Registrar sucursal

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar sucursal |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; catálogo de sucursales accesible |
| **Postcondiciones** | Sucursal creada en estado activo, disponible para asignación y operación, con registro en bitácora |
| **Trigger** | El Superadmin selecciona “Nueva sucursal” en el módulo Organización y sucursales |

**Flujo Principal:**
1. El Superadmin selecciona “Nueva sucursal”.
2. El sistema muestra el formulario de creación.
3. El Superadmin ingresa código, nombre, dirección, teléfono y datos opcionales.
4. El sistema valida campos obligatorios y formatos.
5. El sistema verifica unicidad de código y nombre.
6. El sistema guarda la sucursal en estado activo.
7. El sistema registra el evento en bitácora.
8. El sistema confirma la creación y redirige al listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede guardar como borrador si el cliente lo requiere; el sistema deja la sucursal inactiva hasta confirmación. [SUPUESTO A VALIDAR CON EL CLIENTE]

**Flujos de Excepción:**
- **FE-01:** Código o nombre duplicado → el sistema muestra el conflicto y no guarda.
- **FE-02:** Campos obligatorios vacíos o formato inválido → el sistema resalta los campos y no guarda.
- **FE-03:** Falla de conexión al guardar → el sistema informa y permite reintentar sin perder los datos ingresados.

### CU-02: Consultar y actualizar datos de sucursal

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Consultar y actualizar datos de sucursal |
| **Actor Principal** | Superadmin / Administración |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; sucursal existente dentro del alcance del usuario |
| **Postcondiciones** | Datos actualizados y registrados en bitácora; ficha vigente |
| **Trigger** | El usuario abre la ficha de una sucursal y elige “Editar” |

**Flujo Principal:**
1. El usuario accede al listado y busca la sucursal.
2. El sistema muestra la ficha con los datos y el estado.
3. El usuario selecciona “Editar”.
4. El sistema habilita los campos permitidos según el rol.
5. El usuario modifica los datos y confirma.
6. El sistema valida formatos y unicidad cuando corresponda.
7. El sistema guarda y registra en bitácora valores anteriores y nuevos.
8. El sistema confirma la actualización.

**Flujos Alternativos:**
- **FA-01:** El usuario cancela la edición; el sistema descarta los cambios y vuelve a la ficha.

**Flujos de Excepción:**
- **FE-01:** El usuario intenta editar un campo no permitido para su rol → el sistema bloquea la acción y muestra un mensaje.
- **FE-02:** La sucursal fue modificada por otro usuario → el sistema advierte el conflicto y solicita recargar.

### CU-03: Desactivar sucursal

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Desactivar sucursal |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; sucursal existente y activa |
| **Postcondiciones** | Sucursal en estado inactivo, bloqueada para nuevas operaciones, con motivo y bitácora |
| **Trigger** | El Superadmin selecciona “Desactivar” en la ficha de la sucursal |

**Flujo Principal:**
1. El Superadmin abre la ficha de la sucursal y selecciona “Desactivar”.
2. El sistema solicita el motivo de la desactivación.
3. El Superadmin ingresa el motivo y confirma.
4. El sistema cambia el estado a inactivo.
5. El sistema registra en bitácora la acción con motivo y valores anteriores/nuevos.
6. El sistema confirma la desactivación.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; el sistema mantiene la sucursal activa.

**Flujos de Excepción:**
- **FE-01:** El motivo está vacío → el sistema no permite continuar.
- **FE-02:** Existen operaciones en curso que dependen de la sucursal → el sistema informa y sugiere coordinar el cierre antes de desactivar. [SUPUESTO A VALIDAR CON EL CLIENTE]

---

## 9. Criterios de Aceptación

### CA-001: Creación exitosa de sucursal
- **Given** un Superadmin autenticado y un código y nombre no utilizados,
- **When** completa el formulario y guarda,
- **Then** el sistema crea la sucursal en estado activo y la muestra en el listado con registro en bitácora.

### CA-002: Rechazo de código o nombre duplicado
- **Given** una sucursal existente con código “YCB-01”,
- **When** el Superadmin intenta crear otra con el mismo código,
- **Then** el sistema rechaza la operación, muestra el campo en conflicto y no guarda.

### CA-003: Validación de campos obligatorios
- **Given** un formulario de sucursal,
- **When** el usuario deja vacío el nombre,
- **Then** el sistema resalta el campo, informa el error y no guarda.

### CA-004: Listado filtrado por alcance
- **Given** un usuario de Recepción asignado solo a la sucursal “Yacuiba Centro”,
- **When** abre el listado de sucursales,
- **Then** el sistema muestra únicamente las sucursales asignadas a su usuario.

### CA-005: Edición con registro en bitácora
- **Given** una sucursal existente,
- **When** el Superadmin modifica el teléfono y guarda,
- **Then** el sistema actualiza el dato y registra en bitácora usuario, fecha y valores anteriores/nuevos.

### CA-006: Desactivación con motivo
- **Given** una sucursal activa y un Superadmin autenticado,
- **When** desactiva la sucursal indicando un motivo,
- **Then** el sistema cambia el estado a inactivo y registra el motivo en bitácora.

### CA-007: Bloqueo de eliminación con datos asociados
- **Given** una sucursal con turnos y pagos registrados,
- **When** el Superadmin intenta eliminarla,
- **Then** el sistema rechaza la eliminación y ofrece desactivarla.

### CA-008: Restricción de acceso por rol
- **Given** un usuario de Recepción autenticado,
- **When** intenta acceder a la acción “Nueva sucursal” o “Desactivar”,
- **Then** el sistema no muestra la opción y, si intenta invocarla por API, responde con error de permisos.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Migración desde cuadernos con datos inconsistentes de sucursales (nombres repetidos, sin código) | Alta | Medio | Alto | Definir plantilla de migración, normalizar nombres y códigos, validar con el cliente antes de cargar. |
| RT-002 | Baja adopción por parte de Recepción si el catálogo se percibe como trámite | Media | Medio | Medio | Capacitación breve, interfaz simple, pocos clics y visibilidad del beneficio en agenda y caja. |
| RT-003 | Conectividad limitada en el centro | Media | Alto | Alto | Diseñar formularios tolerantes a reintentos, guardado sin pérdida de datos y mensajes claros de error. |
| RT-004 | Errores en la asignación de sucursal que afecten pagos y caja | Media | Alto | Alto | Validaciones en backend, scoping por sucursal y pruebas de integración con módulos 07 y 08. |
| RT-005 | Exposición indebida de datos de menores por filtrado incorrecto | Baja | Alto | Medio | Revisión de permisos por rol y sucursal, pruebas de seguridad y bitácora de accesos. |
| RT-006 | Cambios de alcance (campos adicionales, reglas de estado) durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa, registrar supuestos y gestionar cambios con el cliente. |
| RT-007 | Dependencia de una sola persona para desarrollo y soporte | Media | Alto | Alto | Documentación técnica, control de versiones y transferencia de conocimiento planificada. |
| RT-008 | Degradación de rendimiento al crecer el número de sucursales y usuarios concurrentes | Baja | Medio | Bajo | Índices en código y nombre, paginación y pruebas de carga periódicas. |

---

**Fin del documento**