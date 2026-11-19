# Gestión de usuarios
**Módulo:** Usuarios, roles y seguridad
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo administra las cuentas de las personas que operan el sistema en TalitaCumi: Superadmin, Administración y Recepción. Los profesionales y los padres **no usan el sistema** (según el contexto), por lo que no se contemplan cuentas para ellos. La gestión de usuarios cubre el alta, la edición, la asignación de rol y de sucursales, el cambio de estado (activo/inactivo) y el reseteo de credenciales, siempre con registro en bitácora.

Hoy el control de quién hace qué en el centro se sostiene en la confianza y en acuerdos verbales; no existe un registro formal de usuarios, roles ni de las sedes a las que cada persona accede. Este submódulo reemplaza esa informalidad por cuentas nominales con rol asignado y alcance por sucursal, condición necesaria para proteger datos de menores y datos de salud y para que la agenda, la caja y los reportes respeten el ámbito de cada usuario.

El valor que aporta es triple: trazabilidad (cada acción queda asociada a un usuario), seguridad (acceso por rol y por sucursal, validado en backend) y orden operativo (altas y bajas controladas, sin cuentas compartidas). Se integra con Organización y sucursales (dimensión de sucursal), Roles y permisos, Autenticación y sesiones, Auditoría y bitácora, y con todos los módulos que registran operaciones a nombre de un usuario.

---

## 2. Alcance

### Incluido en este módulo

- Alta, edición, consulta y cambio de estado (activo/inactivo) de usuarios.
- Datos mínimos del usuario: nombre completo, documento de identidad, correo o nombre de usuario, rol, sucursales asignadas y estado.
- Asignación de **un único rol** por usuario (Superadmin, Administración o Recepción) en esta etapa; la asignación de múltiples roles se marca como supuesto.
- Asignación de **una o varias sucursales** por usuario, según el alcance definido en el módulo 01.
- Reseteo de contraseña por parte del Superadmin y cambio de contraseña por el propio usuario (coordinado con Autenticación y sesiones).
- Validación de unicidad de documento de identidad y de correo/usuario.
- Búsqueda, listado y filtrado de usuarios por rol, sucursal y estado.
- Bitácora de altas, ediciones, cambios de estado y reseteos.
- Bloqueo de eliminación física cuando el usuario tiene operaciones registradas; solo se permite desactivarlo.
- Exportación del listado de usuarios a PDF y Excel.

### Excluido de este módulo

- **Definición de roles y permisos:** corresponde al submódulo `02_roles_permisos_sucursal.md`.
- **Autenticación, contraseñas, sesiones y bloqueos por intentos fallidos:** corresponde al submódulo `03_autenticacion_sesiones.md`.
- **Bitácora y auditoría de acciones:** corresponde al submódulo `04_auditoria_bitacora.md`; aquí solo se invoca.
- **Cuentas para profesionales o padres:** fuera de alcance por el contexto.
- **Directorio de profesionales y especialidades:** corresponde al módulo 03.
- **Firma electrónica, doble factor y SSO:** fuera de alcance en esta etapa.
- **Integraciones externas y app móvil:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Crea, edita, activa, desactiva usuarios, asigna rol y sucursales, y resetea contraseñas. |
| Administración | Consulta usuarios de sus sucursales; **no crea ni edita** usuarios (ver [SUPUESTO A VALIDAR CON EL CLIENTE]). |
| Recepción | Sin acceso a la gestión de usuarios. |
| Profesionales y padres | **No usan el sistema**. No son actores. |

### Sucursales aplicables

- **La cuenta de usuario es global**, pero su alcance se define por las sucursales asignadas.
- **La asignación usuario–sucursal** es la base del scoping de todos los módulos operativos.
- **Un usuario puede tener una o varias sucursales**; si no tiene ninguna asignada, no puede operar (RN-006).
- **Los datos de menores y de salud** asociados a las operaciones del usuario se protegen por rol y sucursal.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume un único rol por usuario en Etapa 1 y que Administración no puede crear ni editar usuarios. Confirmar ambos puntos antes de implementar.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Con cuentas nominales y roles claros, Recepción opera solo sobre sus sucursales, Administración supervisa su ámbito y el Superadmin mantiene el control central. Se eliminan las cuentas compartidas y se reduce el riesgo de que una persona realice acciones fuera de su competencia (por ejemplo, anular un pago sin autorización). El alta y la baja controladas permiten reflejar el ingreso o retiro de personal sin afectar los históricos.

### 3.2 Justificación en la Atención al Niño y su Familia

Aunque los padres no acceden al sistema, la correcta gestión de usuarios protege sus datos: solo personal autorizado y de la sucursal correspondiente puede ver la ficha del niño, sus turnos y sus pagos. Esto refuerza la confianza de la familia y contribuye a un entorno de resguardo de la información de menores.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de la información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** secreto profesional y conservación de registros clínicos; aplicable a los datos de salud almacenados [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** marco general para el tratamiento electrónico de información [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La gestión nominal de usuarios y la bitácora asociada son condición para demostrar el cumplimiento de estas obligaciones ante una auditoría.

### 3.4 Justificación Tecnológica

Frente al registro informal, el módulo aporta: identificadores únicos por usuario; validaciones de unicidad; scoping por sucursal en backend; bitácora de cambios; bloqueo de eliminación con operaciones asociadas; y base para que autenticación y permisos funcionen correctamente. Reduce la duplicación de cuentas, evita accesos indebidos y facilita la incorporación de nuevo personal.

---

## 4. Funciones Principales

### FP-01: Registrar usuario
- **Descripción:** Permite dar de alta un usuario con sus datos mínimos, rol y sucursales asignadas. El sistema valida unicidad de documento y correo y guarda en estado activo.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Usuario creado y disponible para autenticarse con el rol y alcance definidos.

### FP-02: Consultar y buscar usuarios
- **Descripción:** Permite listar y filtrar usuarios por rol, sucursal y estado, y abrir la ficha con sus datos y asignaciones. El listado se ajusta al alcance del usuario.
- **Actor principal:** Superadmin (todos); Administración (solo consulta de sus sucursales).
- **Resultado esperado:** Vista consolidada de usuarios con opción de exportar a PDF/Excel.

### FP-03: Actualizar datos, rol y sucursales del usuario
- **Descripción:** Permite modificar los datos del usuario, reasignar su rol y ajustar las sucursales a las que accede, registrando cada cambio en bitácora.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Usuario actualizado con historial de cambios y scoping vigente aplicado en todo el sistema.

### FP-04: Activar, desactivar y resetear credenciales
- **Descripción:** Permite cambiar el estado del usuario y resetear su contraseña. Al desactivarlo, se bloquea el acceso sin borrar su historial; el reseteo se coordina con Autenticación y sesiones.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Acceso controlado y bitácora completa de altas, bajas y reseteos.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Crear usuario | El sistema debe permitir registrar un usuario con nombre completo, documento de identidad, correo o nombre de usuario, rol y sucursales asignadas. | Alta | Superadmin |
| RF-002 | Validar unicidad | El sistema debe rechazar documentos de identidad y correos/usuarios duplicados, mostrando el campo en conflicto. | Alta | Sistema |
| RF-003 | Validar campos obligatorios | El sistema debe exigir nombre completo, documento, correo/usuario, rol y al menos una sucursal para activar al usuario. | Alta | Sistema |
| RF-004 | Asignar rol | El sistema debe permitir asignar exactamente un rol (Superadmin, Administración o Recepción) por usuario en esta etapa. | Alta | Superadmin |
| RF-005 | Asignar sucursales | El sistema debe permitir asignar una o varias sucursales activas al usuario, como base del scoping por sucursal. | Alta | Superadmin |
| RF-006 | Listar y buscar usuarios | El sistema debe mostrar un listado paginado con filtros por rol, sucursal y estado, respetando el alcance del usuario autenticado. | Alta | Superadmin, Administración |
| RF-007 | Consultar ficha de usuario | El sistema debe mostrar la ficha con datos, rol, sucursales y estado, y la fecha de última modificación. | Alta | Superadmin, Administración |
| RF-008 | Editar usuario | El sistema debe permitir modificar datos, rol y sucursales, registrando valores anteriores y nuevos en bitácora. | Alta | Superadmin |
| RF-009 | Activar y desactivar usuario | El sistema debe permitir cambiar el estado entre activo e inactivo exigiendo motivo al desactivar. | Alta | Superadmin |
| RF-010 | Resetear contraseña | El sistema debe permitir al Superadmin resetear la contraseña de un usuario, forzando el cambio en el siguiente inicio de sesión (coordinado con Autenticación). | Alta | Superadmin |
| RF-011 | Bloquear eliminación con historial | El sistema debe impedir la eliminación física de un usuario con operaciones registradas; solo permitirá desactivarlo. | Alta | Sistema |
| RF-012 | Registrar bitácora | El sistema debe registrar en bitácora altas, ediciones, cambios de rol, cambios de sucursales, cambios de estado y reseteos. | Alta | Sistema |
| RF-013 | Restringir acceso por rol | El sistema debe impedir que Administración y Recepción creen o editen usuarios. | Alta | Sistema |
| RF-014 | Validar usuario activo | El sistema debe impedir la asignación de un usuario inactivo a nuevas operaciones; su historial sigue consultable. | Alta | Sistema |
| RF-015 | Exportar listado | El sistema debe permitir exportar el listado filtrado de usuarios a PDF y Excel. | Baja | Superadmin, Administración |
| RF-016 | Aplicar scoping por sucursal | El sistema debe filtrar automáticamente las operaciones del usuario según las sucursales asignadas, validándolo en backend. | Alta | Sistema |

> [SUPUESTO A VALIDAR CON EL CLIENTE] El campo de documento de identidad se asume obligatorio; si el cliente prefiere otro identificador (por ejemplo, solo correo), debe confirmarse antes de implementar. También se asume que el correo/username es el identificador de login.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El listado de usuarios debe cargar en menos de 1 segundo con hasta 200 usuarios y 20 usuarios concurrentes.
- **RNF-002:** La validación de unicidad debe resolverse en menos de 500 ms al guardar.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS; las contraseñas nunca se muestran en la interfaz ni en bitácora.
- **RNF-004:** El scoping por rol y por sucursal debe validarse en backend, no solo en la interfaz.
- **RNF-005:** Los datos personales de usuarios y los datos de menores asociados a sus operaciones deben tratarse conforme a la política de privacidad y a la normativa aplicable.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro, con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir la tabla de usuarios y sus históricos de cambios.

### 6.4 Usabilidad

- **RNF-008:** La interfaz debe usar el vocabulario del negocio (usuario, rol, sucursal, estado) y permitir crear un usuario en pocos pasos.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento en usuarios y sucursales sin cambios de código.

### 6.6 Interoperabilidad

- **RNF-011:** El módulo debe exponer los datos de usuario vía API REST a los módulos de autenticación, permisos, agenda y caja, y exportar a PDF y Excel.

### 6.7 Mantenibilidad

- **RNF-012:** El esquema se gestionará con migraciones versionadas; la lista de roles se tomará del módulo de roles y permisos, sin valores fijos en el código.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Toda alta, edición, cambio de rol, cambio de sucursales, cambio de estado y reseteo debe quedar en bitácora con usuario, fecha, hora y valores anteriores/nuevos; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Unicidad de usuario — Documento de identidad y correo/username deben ser únicos en todo el sistema.
- **RN-002:** Un rol por usuario — En esta etapa cada usuario tiene exactamente un rol; la asignación múltiple queda fuera de alcance.
- **RN-003:** Sucursal obligatoria — Todo usuario activo debe tener al menos una sucursal asignada; sin sucursal no puede operar.
- **RN-004:** Solo roles del sistema — Solo se permiten los roles Superadmin, Administración y Recepción; profesionales y padres no son usuarios.
- **RN-005:** No eliminación con historial — Un usuario con operaciones registradas no puede eliminarse; solo desactivarse con motivo.
- **RN-006:** Usuario inactivo — Un usuario inactivo no puede iniciar sesión ni ser asignado a nuevas operaciones; su historial se conserva.
- **RN-007:** Reseteo seguro — El reseteo de contraseña debe forzar el cambio en el siguiente inicio de sesión y registrarse en bitácora.
- **RN-008:** Cambio de rol controlado — Cambiar el rol de un usuario no altera las operaciones ya registradas; se aplica a partir del cambio.
- **RN-009:** Alcance por rol — Superadmin administra todos los usuarios; Administración solo consulta los de sus sucursales; Recepción no accede.
- **RN-010:** Motivo obligatorio — La desactivación de un usuario requiere motivo registrado en bitácora.
- **RN-011:** Datos mínimos — Nombre completo, documento, correo/username, rol y sucursales son obligatorios para activar un usuario.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-002 (un rol por usuario) y la RN-003 (al menos una sucursal) deben confirmarse; la alternativa sería permitir usuarios con múltiples roles o con acceso global sin sucursal específica (por ejemplo, Superadmin).

---

## 8. Casos de Uso Principales

### CU-01: Registrar usuario

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar usuario |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; roles y sucursales activas disponibles |
| **Postcondiciones** | Usuario creado en estado activo, con rol y sucursales asignadas y registro en bitácora |
| **Trigger** | El Superadmin selecciona “Nuevo usuario” en Usuarios, roles y seguridad |

**Flujo Principal:**
1. El Superadmin selecciona “Nuevo usuario”.
2. El sistema muestra el formulario de alta.
3. El Superadmin ingresa nombre completo, documento, correo/username, rol y sucursales.
4. El sistema valida campos obligatorios, formatos y unicidad.
5. El sistema guarda el usuario en estado activo.
6. El sistema registra el alta en bitácora.
7. El sistema confirma y redirige al listado.

**Flujos Alternativos:**
- **FA-01:** El Superadmin puede crear el usuario en estado inactivo para configurarlo antes de habilitarlo. [SUPUESTO A VALIDAR CON EL CLIENTE]

**Flujos de Excepción:**
- **FE-01:** Documento o correo duplicado → el sistema informa y no guarda.
- **FE-02:** Falta una sucursal → el sistema impide activar al usuario y solicita asignarla.
- **FE-03:** Falla de conexión → el sistema informa y permite reintentar sin perder datos.

### CU-02: Editar rol y sucursales de un usuario

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Editar rol y sucursales de un usuario |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada; usuario existente |
| **Postcondiciones** | Rol y/o sucursales actualizados, con bitácora y scoping vigente en el sistema |
| **Trigger** | El Superadmin abre la ficha del usuario y elige “Editar” |

**Flujo Principal:**
1. El Superadmin busca el usuario y abre la ficha.
2. El sistema muestra datos, rol y sucursales actuales.
3. El Superadmin selecciona “Editar”.
4. El Superadmin modifica rol y/o sucursales.
5. El sistema valida que el usuario quede con al menos una sucursal activa.
6. El sistema guarda y registra en bitácora valores anteriores y nuevos.
7. El sistema confirma la actualización.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; el sistema mantiene los valores anteriores.

**Flujos de Excepción:**
- **FE-01:** Se intenta dejar al usuario sin sucursal → el sistema rechaza e informa.
- **FE-02:** Otro usuario modificó el registro mientras se editaba → el sistema advierte el conflicto.

### CU-03: Desactivar usuario

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Desactivar usuario |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; usuario existente y activo |
| **Postcondiciones** | Usuario inactivo, bloqueado para iniciar sesión, con bitácora y motivo |
| **Trigger** | El Superadmin selecciona “Desactivar” en la ficha del usuario |

**Flujo Principal:**
1. El Superadmin abre la ficha y selecciona “Desactivar”.
2. El sistema solicita el motivo.
3. El Superadmin ingresa el motivo y confirma.
4. El sistema cambia el estado a inactivo.
5. El sistema registra en bitácora la acción con motivo.
6. El sistema confirma la desactivación.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; el usuario sigue activo.

**Flujos de Excepción:**
- **FE-01:** Motivo vacío → el sistema no permite continuar.
- **FE-02:** El usuario tiene sesiones abiertas → el sistema las invalida al confirmar y lo registra.

---

## 9. Criterios de Aceptación

### CA-001: Alta exitosa de usuario
- **Given** un Superadmin autenticado y datos únicos,
- **When** registra un usuario con rol Recepción y una sucursal,
- **Then** el sistema crea el usuario activo y lo muestra en el listado con bitácora.

### CA-002: Rechazo por documento duplicado
- **Given** un usuario con documento “1234567” existente,
- **When** el Superadmin intenta crear otro con el mismo documento,
- **Then** el sistema rechaza el alta e informa el conflicto.

### CA-003: Sucursal obligatoria
- **Given** el formulario de alta,
- **When** el Superadmin intenta activar un usuario sin sucursal,
- **Then** el sistema bloquea la activación e indica que debe asignar al menos una sucursal.

### CA-004: Edición con bitácora
- **Given** un usuario con rol Recepción,
- **When** el Superadmin cambia su rol a Administración,
- **Then** el sistema actualiza el rol y registra en bitácora el valor anterior y el nuevo.

### CA-005: Desactivación con motivo
- **Given** un usuario activo,
- **When** el Superadmin lo desactiva con motivo,
- **Then** el sistema bloquea su acceso y registra la acción.

### CA-006: Bloqueo de eliminación con historial
- **Given** un usuario con pagos registrados,
- **When** el Superadmin intenta eliminarlo,
- **Then** el sistema rechaza la eliminación y ofrece desactivarlo.

### CA-007: Restricción por rol
- **Given** un usuario de Administración autenticado,
- **When** intenta crear o editar un usuario,
- **Then** el sistema no muestra la opción y responde con error de permisos por API.

### CA-008: Scoping por sucursal
- **Given** un usuario de Recepción asignado solo a “Yacuiba Centro”,
- **When** opera en el sistema,
- **Then** el backend solo le permite ver y modificar datos de esa sucursal.

### CA-009: Reseteo de contraseña
- **Given** un usuario activo,
- **When** el Superadmin resetea su contraseña,
- **Then** el sistema marca la contraseña como temporal y fuerza el cambio en el siguiente inicio de sesión.

### CA-010: Exportación del listado
- **Given** un Superadmin autenticado,
- **When** exporta el listado filtrado,
- **Then** el sistema genera PDF y Excel con los datos vigentes.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Cuentas compartidas o contraseñas débiles que anulen la trazabilidad | Media | Alto | Alto | Política de contraseñas en Autenticación, prohibición explícita de cuentas compartidas y capacitación. |
| RT-002 | Asignación incorrecta de sucursal que exponga datos de otra sede | Media | Alto | Alto | Validación en backend, pruebas de scoping y CA-008. |
| RT-003 | Cambio de rol sin comunicación que genere accesos indebidos | Media | Medio | Medio | Bitácora con motivo y notificación al usuario afectado. |
| RT-004 | Dependencia del Superadmin para altas/bajas operativas | Media | Medio | Medio | Procedimiento documentado y backups de configuración. |
| RT-005 | Duplicación de usuarios en la migración inicial | Alta | Medio | Alto | Plantilla de migración, normalización y validación con el cliente. |
| RT-006 | Reseteo de contraseña sin control que permita suplantación | Baja | Alto | Medio | Registro en bitácora, forzar cambio en el siguiente login y notificación al usuario. |
| RT-007 | Exposición de datos personales en exportaciones | Baja | Medio | Bajo | Restringir exportación a Superadmin y Administración, y registrar la exportación en bitácora. |
| RT-008 | Escalamiento de privilegios por error de permisos en la API | Baja | Alto | Medio | Revisión de permisos, pruebas de seguridad y CA-007. |

---

**Fin del documento**