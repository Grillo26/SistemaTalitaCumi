# Roles, permisos y alcance por sucursal
**Módulo:** Usuarios, roles y seguridad
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo define los roles del sistema (Superadmin, Administración y Recepción), las acciones que cada rol puede ejecutar y el **alcance por sucursal** que acota las operaciones de cada usuario. Los profesionales y los padres no son usuarios del sistema, por lo que no se contemplan roles para ellos. Los permisos se organizan como una matriz rol × acción y se validan en el backend, garantizando que la interfaz no sea la única barrera de seguridad.

Hoy el control de permisos es implícito: cada persona sabe “hasta dónde puede llegar” y las decisiones se toman por confianza. Este submódulo formaliza esas reglas, las deja documentadas y auditables, y las aplica de forma consistente en todos los módulos. Es la pieza que conecta la gestión de usuarios con la operación real del sistema y con la protección de datos de menores y datos de salud.

El valor que aporta es la previsibilidad y la seguridad: cada usuario ve y hace exactamente lo que su rol y sus sucursales permiten, sin excepciones informales. Se integra con Gestión de usuarios, Autenticación y sesiones, Auditoría y bitácora, y con todos los módulos funcionales que exigen permisos (inscripciones, agenda, pagos, caja, reportes, inventario).

---

## 2. Alcance

### Incluido en este módulo

- Definición y administración del **catálogo de roles** (Superadmin, Administración, Recepción) como dato configurable, sin valores fijos en el código.
- Definición de la **matriz de permisos** por rol y por módulo/acción, con granularidad de ver, crear, editar, desactivar y anular.
- Aplicación del **alcance por sucursal**: cada permiso se evalúa junto con las sucursales asignadas al usuario.
- Validación de permisos en backend en cada endpoint y en la interfaz para ocultar acciones no permitidas.
- Registro en bitácora de cambios en roles y permisos.
- Consulta de la matriz vigente por parte del Superadmin.
- Exposición de la matriz y del alcance a los módulos consumidores para que apliquen las mismas reglas.

### Excluido de este módulo

- **Gestión de usuarios (altas, bajas, asignaciones):** corresponde al submódulo `01_gestion_usuarios.md`.
- **Autenticación y sesiones:** corresponde al submódulo `03_autenticacion_sesiones.md`.
- **Bitácora detallada:** corresponde al submódulo `04_auditoria_bitacora.md`.
- **Definición de permisos de profesionales o padres:** fuera de alcance por el contexto.
- **Roles personalizados creados por el cliente:** fuera de alcance en Etapa 1; el catálogo de roles es fijo (los tres del contexto) y solo se administran sus permisos.
- **Doble factor, SSO, OAuth:** fuera de alcance en esta etapa.
- **Integraciones externas:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Administra roles y matriz de permisos; valida su vigencia. |
| Administración | Consulta la matriz vigente; **no la edita**. |
| Recepción | Sin acceso a la administración de roles; opera bajo los permisos asignados a su rol. |
| Profesionales y padres | **No usan el sistema**. No son actores. |

### Sucursales aplicables

- **El catálogo de roles y la matriz de permisos son globales** (una única definición para toda la empresa).
- **El alcance por sucursal se aplica por usuario**, no por rol: dos usuarios con el mismo rol pueden operar en sucursales distintas.
- **Un permiso sin sucursal válida no se concede**: rol + sucursal se evalúan juntos.
- **Los datos de menores y de salud** quedan protegidos por la combinación rol + sucursal.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que el catálogo de roles es fijo en Etapa 1 (los tres del contexto) y que solo se administran sus permisos. Si el cliente requiere roles personalizados, debe evaluarse como cambio de alcance.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Con una matriz de permisos explícita, Recepción no puede anular pagos, Administración no puede crear usuarios y el Superadmin conserva el control central. Esto elimina discusiones informales, reduce el riesgo de errores costosos (por ejemplo, anulaciones no autorizadas) y hace previsible el comportamiento del sistema. La matriz documentada sirve además como manual de operación para el personal.

### 3.2 Justificación en la Atención al Niño y su Familia

La separación de permisos por rol y por sucursal protege la información del niño y de su familia: solo el personal autorizado y de la sede correspondiente accede a la ficha, los turnos y los pagos. Esto refuerza la confidencialidad y contribuye a un entorno de resguardo.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de la información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** secreto profesional y conservación de registros clínicos [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** marco para el tratamiento electrónico de información [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La matriz de permisos y su validación en backend son mecanismos concretos para cumplir con el principio de acceso mínimo necesario.

### 3.4 Justificación Tecnológica

Frente a controles informales, la matriz aporta: una definición única consumida por todos los módulos; validación en backend que no depende de la interfaz; trazabilidad de cambios; y extensibilidad para incorporar nuevas acciones sin recompilar. Se reduce el riesgo de endpoints expuestos por error y se facilita la revisión de seguridad.

---

## 4. Funciones Principales

### FP-01: Consultar la matriz de roles y permisos
- **Descripción:** Permite al Superadmin ver la matriz vigente (roles × módulos × acciones) y entender qué puede hacer cada rol en cada módulo.
- **Actor principal:** Superadmin (consulta); Administración (consulta).
- **Resultado esperado:** Matriz vigente visible y documentada, con opción de exportar.

### FP-02: Editar permisos por rol
- **Descripción:** Permite al Superadmin habilitar o deshabilitar acciones para un rol específico, registrando cada cambio en bitácora con valores anteriores y nuevos.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Matriz actualizada y aplicada de inmediato en el backend y en la interfaz.

### FP-03: Evaluar alcance por sucursal
- **Descripción:** En cada operación, el sistema evalúa la combinación rol + permiso + sucursal del usuario y decide si la acción se permite, se oculta o se rechaza.
- **Actor principal:** Sistema (invocado por todos los módulos).
- **Resultado esperado:** Scoping por sucursal consistente en todo el sistema y bloqueo de accesos indebidos.

### FP-04: Auditar cambios de permisos
- **Descripción:** Registra y permite consultar cada modificación de la matriz de permisos, identificando quién la hizo, cuándo y con qué valores.
- **Actor principal:** Superadmin (consulta); Sistema (registro).
- **Resultado esperado:** Trazabilidad completa de la configuración de seguridad.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Definir catálogo de roles | El sistema debe tener un catálogo de roles con Superadmin, Administración y Recepción, almacenado como dato configurable. | Alta | Sistema |
| RF-002 | Definir matriz de permisos | El sistema debe modelar permisos por rol, módulo y acción (ver, crear, editar, desactivar, anular) en una matriz configurable. | Alta | Superadmin |
| RF-003 | Consultar matriz vigente | El sistema debe permitir consultar y exportar la matriz vigente por rol y por módulo. | Alta | Superadmin, Administración |
| RF-004 | Editar permisos por rol | El sistema debe permitir habilitar o deshabilitar acciones por rol, registrando cambios en bitácora. | Alta | Superadmin |
| RF-005 | Validar permisos en backend | El sistema debe validar cada endpoint contra la matriz vigente y el alcance por sucursal antes de ejecutar la operación. | Alta | Sistema |
| RF-006 | Ocultar acciones no permitidas | La interfaz debe ocultar o deshabilitar las acciones que el rol del usuario no permite, sin sustituir la validación de backend. | Alta | Sistema |
| RF-007 | Aplicar alcance por sucursal | El sistema debe filtrar los datos por las sucursales asignadas al usuario en cada consulta y operación. | Alta | Sistema |
| RF-008 | Impedir auto-reducción del Superadmin | El sistema debe impedir que el último Superadmin activo pierda permisos críticos o sea desactivado. | Alta | Sistema |
| RF-009 | Rechazar operaciones sin sucursal válida | El sistema debe rechazar operaciones sobre sucursales no asignadas al usuario, devolviendo un error controlado. | Alta | Sistema |
| RF-010 | Registrar bitácora de cambios | El sistema debe registrar en bitácora cada cambio de la matriz, con usuario, fecha, hora y valores anteriores/nuevos. | Alta | Sistema |
| RF-011 | Exponer matriz a otros módulos | El sistema debe exponer los permisos vigentes vía API para que los módulos apliquen las mismas reglas. | Alta | Sistema |
| RF-012 | Recalcular permisos al cambiar rol | Al cambiar el rol o las sucursales de un usuario, el sistema debe recalcular sus permisos efectivos en la siguiente operación. | Alta | Sistema |
| RF-013 | Impedir modificaciones concurrentes conflictivas | El sistema debe detectar y advertir si dos Superadmins editan la misma celda de la matriz de forma concurrente. | Media | Sistema |
| RF-014 | Versionar la matriz | El sistema debe conservar el histórico de versiones de la matriz y permitir consultar qué permisos estaban vigentes en una fecha dada. | Media | Superadmin |
| RF-015 | Restringir acceso a la administración de permisos | Solo el Superadmin puede editar la matriz; Administración solo consulta; Recepción no accede. | Alta | Sistema |

> [SUPUESTO A VALIDAR CON EL CLIENTE] La granularidad de la matriz (módulos × acciones) y la lista exacta de acciones por módulo deben validarse con el cliente. Se asume un conjunto estándar de acciones (ver, crear, editar, desactivar, anular) más acciones específicas por módulo (por ejemplo, “cerrar caja” o “anular pago”) que se definirán al documentar cada módulo.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** La evaluación de permisos en backend debe resolverse en menos de 50 ms por solicitud, usando cacheo de la matriz vigente.
- **RNF-002:** La consulta de la matriz completa debe cargar en menos de 2 segundos con hasta 15 módulos × 6 acciones × 3 roles.

### 6.2 Seguridad y Privacidad

- **RNF-003:** La matriz debe validarse en backend; la interfaz solo complementa la experiencia.
- **RNF-004:** Los permisos deben evaluarse junto con las sucursales asignadas, no de forma aislada.
- **RNF-005:** Los cambios de matriz deben registrarse en bitácora con usuario, fecha, hora y valores anteriores/nuevos.

### 6.3 Disponibilidad

- **RNF-006:** El servicio de evaluación de permisos debe estar disponible en el horario operativo del centro, con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir la matriz y su histórico.

### 6.4 Usabilidad

- **RNF-008:** La matriz debe presentarse en una grilla clara, con agrupación por módulo y leyenda de acciones.
- **RNF-009:** La interfaz debe advertir cuando un cambio de permisos afecte a usuarios activos con sesiones abiertas.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe permitir agregar nuevas acciones o módulos sin rediseño de la matriz.

### 6.6 Interoperabilidad

- **RNF-011:** La matriz debe exponerse vía API REST para todos los módulos consumidores y exportarse a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** La matriz debe definirse en catálogos versionados; los cambios deben poder revertirse y documentarse.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Cada cambio de matriz debe quedar en bitácora y debe poder reconstruirse la matriz vigente en cualquier fecha.

---

## 7. Reglas de Negocio

- **RN-001:** Catálogo fijo de roles — En Etapa 1 solo existen Superadmin, Administración y Recepción; no se crean roles personalizados.
- **RN-002:** Un rol por usuario — Cada usuario tiene exactamente un rol; los permisos efectivos son la intersección de rol y sucursales asignadas.
- **RN-003:** Acceso mínimo necesario — Cada rol solo accede a las acciones necesarias para su función; el resto se oculta y se rechaza en backend.
- **RN-004:** Validación en backend — Ninguna acción se ejecuta solo por habilitación en la interfaz; el backend siempre valida.
- **RN-005:** Scoping por sucursal — Toda operación se acota a las sucursales asignadas al usuario; sin sucursal válida, la operación se rechaza.
- **RN-006:** Protección del último Superadmin — No se puede dejar al sistema sin al menos un Superadmin activo con permisos completos.
- **RN-007:** Cambios auditados — Todo cambio de matriz queda en bitácora con valores anteriores y nuevos.
- **RN-008:** Aplicación inmediata — Los cambios de permisos se aplican en la siguiente operación del usuario afectado; las sesiones abiertas pueden mantenerse hasta el siguiente control de permisos.
- **RN-009:** Roles no eliminables — Los roles del sistema no pueden eliminarse; solo se modifican sus permisos.
- **RN-010:** Consulta sin edición — Administración puede consultar la matriz, pero no editarla; Recepción no accede a esta sección.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-008 (aplicación inmediata en la siguiente operación y continuidad de sesiones abiertas) debe confirmarse; la alternativa sería invalidar sesiones al cambiar permisos, lo que puede afectar operaciones en curso.

---

## 8. Casos de Uso Principales

### CU-01: Editar permisos de un rol

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Editar permisos de un rol |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin |
| **Postcondiciones** | Matriz actualizada, vigente en backend e interfaz, con bitácora |
| **Trigger** | El Superadmin selecciona un rol en la matriz y habilita/deshabilita una acción |

**Flujo Principal:**
1. El Superadmin abre la matriz de roles y permisos.
2. El sistema muestra la grilla vigente por módulo y acción.
3. El Superadmin selecciona un rol y una acción.
4. El sistema solicita confirmación y, si es sensible, motivo.
5. El Superadmin confirma.
6. El sistema guarda el cambio y registra en bitácora valores anteriores y nuevos.
7. El sistema aplica el cambio en la siguiente evaluación de permisos.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; el sistema mantiene la matriz vigente.

**Flujos de Excepción:**
- **FE-01:** El cambio dejaría al último Superadmin sin permisos críticos → el sistema lo rechaza.
- **FE-02:** Otro Superadmin modificó la misma celda → el sistema advierte el conflicto.

### CU-02: Consultar matriz vigente y exportar

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Consultar matriz vigente y exportar |
| **Actor Principal** | Superadmin / Administración |
| **Actores Secundarios** | — |
| **Precondiciones** | Sesión iniciada con rol autorizado |
| **Postcondiciones** | Matriz visible y/o exportada con fecha de vigencia |
| **Trigger** | El usuario abre la sección de roles y permisos |

**Flujo Principal:**
1. El usuario abre la sección de roles y permisos.
2. El sistema muestra la matriz vigente agrupada por módulo.
3. El usuario aplica filtros por rol o módulo.
4. El usuario selecciona “Exportar” y elige PDF o Excel.
5. El sistema genera el archivo con fecha y hora de exportación.

**Flujos Alternativos:**
- **FA-01:** El usuario solo consulta sin exportar.

**Flujos de Excepción:**
- **FE-01:** Falla en la generación del archivo → el sistema informa y permite reintentar.

### CU-03: Evaluar permiso en una operación

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Evaluar permiso en una operación |
| **Actor Principal** | Sistema (módulos consumidores) |
| **Actores Secundarios** | — |
| **Precondiciones** | Usuario autenticado con rol y sucursales asignadas |
| **Postcondiciones** | Operación permitida, ocultada o rechazada según la matriz vigente |
| **Trigger** | El usuario invoca una acción (ver, crear, editar, desactivar, anular) |

**Flujo Principal:**
1. El módulo consumidor invoca el servicio de permisos con usuario, acción y sucursal.
2. El servicio consulta la matriz vigente y las sucursales asignadas.
3. El servicio devuelve permitido o denegado.
4. El módulo ejecuta o rechaza la operación en consecuencia.

**Flujos Alternativos:**
- **FA-01:** El permiso es global (por ejemplo, consultar la matriz) y no depende de sucursal.

**Flujos de Excepción:**
- **FE-01:** El servicio de permisos no responde → el módulo rechaza la operación por defecto (fail-closed) y registra alerta técnica.

---

## 9. Criterios de Aceptación

### CA-001: Cambio de permiso con bitácora
- **Given** la acción “anular pago” habilitada para Recepción,
- **When** el Superadmin la deshabilita,
- **Then** el sistema actualiza la matriz y registra en bitácora el valor anterior y el nuevo.

### CA-002: Validación en backend
- **Given** un usuario de Recepción sin permiso para anular pagos,
- **When** invoca la acción por API directamente,
- **Then** el sistema rechaza con error de permisos aunque la interfaz no la muestre.

### CA-003: Scoping por sucursal
- **Given** un usuario asignado solo a “Yacuiba Centro”,
- **When** intenta consultar datos de otra sucursal,
- **Then** el sistema deniega el acceso y registra el intento.

### CA-004: Protección del último Superadmin
- **Given** un único Superadmin activo,
- **When** se intenta deshabilitar una acción crítica de su rol,
- **Then** el sistema rechaza el cambio y explica la restricción.

### CA-005: Matriz visible para Administración
- **Given** un usuario de Administración autenticado,
- **When** abre la sección de roles y permisos,
- **Then** el sistema le muestra la matriz en modo consulta y no le permite editar.

### CA-006: Recepción sin acceso
- **Given** un usuario de Recepción,
- **When** intenta acceder a roles y permisos,
- **Then** el sistema no muestra la opción y rechaza la llamada por API.

### CA-007: Aplicación inmediata en la siguiente operación
- **Given** un cambio de permiso sobre el rol Recepción,
- **When** un usuario de ese rol realiza una nueva operación,
- **Then** el sistema aplica la nueva matriz.

### CA-008: Exportación de la matriz
- **Given** un Superadmin autenticado,
- **When** exporta la matriz,
- **Then** el sistema genera PDF y Excel con fecha y hora de vigencia.

### CA-009: Cambio concurrente advertido
- **Given** dos Superadmins editando la misma celda,
- **When** ambos guardan,
- **Then** el sistema advierte el conflicto al segundo y solicita recargar.

### CA-010: Recálculo al cambiar rol
- **Given** un usuario que cambia de Recepción a Administración,
- **When** inicia una nueva operación,
- **Then** el sistema le aplica los permisos del nuevo rol.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Endpoints que no validan permisos y quedan expuestos | Media | Alto | Alto | Revisión de seguridad, pruebas automatizadas de permisos y CA-002. |
| RT-002 | Cambios de matriz que dejan al sistema sin Superadmin operativo | Baja | Alto | Medio | Regla RN-006, validación al guardar y pruebas específicas. |
| RT-003 | Inconsistencia entre permisos en interfaz y backend | Media | Medio | Medio | Backend como fuente única; interfaz solo refleja la matriz. |
| RT-004 | Ambigüedad en la granularidad de acciones por módulo | Alta | Medio | Alto | Taller de definición de la matriz antes de desarrollar y checklist por módulo. |
| RT-005 | Cache de permisos desactualizado tras un cambio | Media | Medio | Medio | Invalidación de cache al guardar y TTL corto con verificación en cada operación. |
| RT-006 | Roles personalizados solicitados fuera de alcance | Media | Medio | Medio | Documentar el fuera de alcance y gestionar el pedido como cambio de etapa. |
| RT-007 | Exposición de la matriz a roles no autorizados | Baja | Medio | Bajo | Validación estricta de acceso y pruebas con CA-005 y CA-006. |
| RT-008 | Dependencia de una sola persona para administrar la matriz | Media | Medio | Medio | Documentar la matriz vigente y su historial; transferencia de conocimiento. |

---

**Fin del documento**