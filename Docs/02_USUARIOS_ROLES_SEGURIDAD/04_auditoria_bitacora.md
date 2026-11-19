# Auditoría y bitácora
**Módulo:** Usuarios, roles y seguridad
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo registra de forma inmutable todas las acciones relevantes del sistema: altas, modificaciones, cambios de estado, inicios de sesión, anulaciones, exportaciones y accesos a datos sensibles. La bitácora es la contraparte técnica del deber de resguardo y trazabilidad que exige el manejo de datos de menores y de salud, y el soporte para resolver reclamos, investigar incidentes y demostrar diligencia ante una auditoría.

Hoy el centro no cuenta con un registro sistemático de quién hizo qué y cuándo: los cuadernos registran resultados, no acciones. Este submódulo reemplaza esa ausencia por un libro de acciones consultable, filtrable y exportable, con datos de usuario, fecha, hora, IP, módulo, entidad afectada, acción y valores anteriores y nuevos cuando corresponda. La bitácora no se borra ni se edita; los errores se corrigen con nuevos registros que referencian al anterior.

El valor que aporta es doble: por un lado, transparencia y rendición de cuentas; por otro, protección institucional frente a reclamos o incidentes. Se integra con todos los módulos del sistema, que invocan el registro de eventos, y con la administración de usuarios y roles que define quién puede consultarla.

---

## 2. Alcance

### Incluido en este módulo

- Registro inmutable de eventos de auditoría con: usuario, fecha, hora, IP, módulo, entidad, acción, valores anteriores y nuevos, y resultado (éxito/error) cuando corresponda.
- Eventos mínimos: inicio y cierre de sesión, intentos fallidos, bloqueos, reseteos, altas/bajas/ediciones de usuarios, cambios de rol y permisos, altas/bajas/ediciones de sucursales, salas, pacientes, tutores, inscripciones, turnos, pagos, anulaciones, cierres de caja, ventas de materiales, exportaciones y accesos a datos sensibles.
- Consulta y filtrado de la bitácora por usuario, módulo, acción, entidad, rango de fechas y resultado.
- Exportación de la bitácora filtrada a PDF y Excel.
- Registro de exportaciones (quién exportó qué y cuándo) como evento auditado.
- Retención y conservación de la bitácora según política acordada con el cliente.
- Consulta restringida por rol (Superadmin y Administración según alcance; Recepción sin acceso).

### Excluido de este módulo

- **Gestión de usuarios:** submódulo `01_gestion_usuarios.md`.
- **Roles y permisos:** submódulo `02_roles_permisos_sucursal.md`.
- **Autenticación y sesiones:** submódulo `03_autenticacion_sesiones.md`; aquí solo se registran sus eventos.
- **Reportes operativos y de ingresos:** módulo 09.
- **Monitoreo de infraestructura, logs técnicos y alertas de sistema:** fuera del alcance funcional; se abordan como RNF.
- **Firma electrónica o sellado de tiempo externo:** fuera de alcance en Etapa 1.
- **Integraciones externas:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Consulta y exporta la bitácora completa. |
| Administración | Consulta y exporta la bitácora de sus sucursales (alcance limitado). |
| Recepción | Sin acceso a la bitácora. |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **La bitácora es global en su registro**, pero incluye la sucursal asociada al evento cuando corresponda.
- **La consulta se acota por sucursal** según el alcance del usuario.
- **Los datos de menores y de salud** aparecen referenciados en la bitácora (por ejemplo, acceso a ficha), pero la bitácora no almacena el contenido clínico, solo la referencia y la acción.

> [SUPUESTO A VALIDAR CON EL CLIENTE] El tiempo de retención de la bitácora (por ejemplo, 5 años) y la política de exportación deben confirmarse con el cliente según sus obligaciones y buenas prácticas. También debe confirmarse si Administración puede ver la bitácora de todas sus sucursales o solo de la sucursal activa en sesión.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Ante un reclamo (“yo no anulé ese pago”, “ese turno se cambió sin avisar”), la bitácora permite reconstruir lo ocurrido con precisión: quién, cuándo, desde qué IP y con qué valores. Esto reduce conflictos internos, facilita la rendición de cuentas y permite corregir errores operativos con evidencia. Además, el registro de exportaciones evita la salida no controlada de información.

### 3.2 Justificación en la Atención al Niño y su Familia

La familia confía en que sus datos están protegidos y en que cualquier acceso o cambio queda registrado. La bitácora permite demostrar que solo personal autorizado accedió a la ficha del niño y que los cambios en turnos, inscripciones y pagos fueron realizados por las personas competentes.

### 3.3 Justificación Normativa

- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de la información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** secreto profesional y conservación de registros clínicos [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales, con obligaciones de trazabilidad [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** marco para el tratamiento electrónico seguro de información [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La bitácora es un mecanismo concreto para demostrar diligencia en el resguardo de datos sensibles y en el cumplimiento de las obligaciones anteriores.

### 3.4 Justificación Tecnológica

Frente a la ausencia de registro, la bitácora aporta: inmutabilidad (no se edita ni se borra), estructura uniforme de eventos, consulta eficiente por índices, exportación controlada y base para auditorías internas y externas. Se implementa sobre PostgreSQL con tablas dedicadas y políticas de solo inserción; el acceso se controla por rol y sucursal.

---

## 4. Funciones Principales

### FP-01: Registrar evento auditado
- **Descripción:** Cada módulo invoca el servicio de bitácora para registrar la acción realizada, con usuario, fecha, hora, IP, entidad, acción, valores anteriores/nuevos y resultado. El registro es inmutable.
- **Actor principal:** Sistema (todos los módulos).
- **Resultado esperado:** Evento almacenado de forma íntegra y disponible para consulta.

### FP-02: Consultar bitácora
- **Descripción:** Permite a Superadmin y Administración consultar los eventos con filtros por usuario, módulo, acción, entidad, rango de fechas y resultado, respetando el alcance por sucursal.
- **Actor principal:** Superadmin (todo); Administración (sus sucursales).
- **Resultado esperado:** Vista filtrada y paginada de eventos con detalle de cada uno.

### FP-03: Exportar bitácora
- **Descripción:** Permite exportar el resultado filtrado a PDF y Excel; la propia exportación queda registrada como evento auditado.
- **Actor principal:** Superadmin y Administración.
- **Resultado esperado:** Archivo generado y evento de exportación registrado.

### FP-04: Consultar detalle de un evento
- **Descripción:** Permite abrir un evento y ver todos sus campos, incluidos los valores anteriores y nuevos cuando corresponda, para investigar reclamos o incidentes.
- **Actor principal:** Superadmin y Administración (según alcance).
- **Resultado esperado:** Detalle completo del evento y trazabilidad de accesos a la bitácora.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Registrar evento | El sistema debe registrar cada acción auditada con usuario, fecha, hora, IP, módulo, entidad, acción, valores anteriores/nuevos y resultado. | Alta | Sistema |
| RF-002 | Inmutabilidad del registro | El sistema debe impedir la edición o eliminación de eventos; los errores se corrigen con nuevos eventos que referencian al anterior. | Alta | Sistema |
| RF-003 | Catálogo de acciones auditadas | El sistema debe tener un catálogo configurable de acciones auditadas por módulo, sin valores fijos en el código. | Alta | Superadmin |
| RF-004 | Consultar bitácora | El sistema debe mostrar la bitácora paginada con filtros por usuario, módulo, acción, entidad, rango de fechas y resultado. | Alta | Superadmin, Administración |
| RF-005 | Consultar detalle de evento | El sistema debe mostrar el detalle completo de un evento, incluidos valores anteriores y nuevos. | Alta | Superadmin, Administración |
| RF-006 | Aplicar alcance por sucursal | La consulta de bitácora debe filtrarse por las sucursales asignadas al usuario; Superadmin ve todo. | Alta | Sistema |
| RF-007 | Registrar accesos a datos sensibles | El sistema debe registrar cada acceso a ficha de paciente, datos de salud, pagos y anulaciones. | Alta | Sistema |
| RF-008 | Registrar exportaciones | El sistema debe registrar cada exportación de datos (PDF, Excel) con usuario, filtros aplicados y cantidad de registros. | Alta | Sistema |
| RF-009 | Exportar bitácora | El sistema debe permitir exportar la bitácora filtrada a PDF y Excel, con fecha y hora de exportación. | Alta | Superadmin, Administración |
| RF-010 | Preservar histórico ante cambios de esquema | El sistema debe mantener la legibilidad de los eventos históricos aunque cambie el esquema de las entidades referenciadas (por ejemplo, guardando snapshot de datos clave). | Media | Sistema |
| RF-011 | Reconstruir estado a una fecha | El sistema debe permitir consultar qué acciones afectaron a una entidad en un rango de fechas y reconstruir su estado. | Media | Superadmin |
| RF-012 | Retención configurable | El sistema debe permitir configurar el período de retención de la bitácora según la política del cliente, sin permitir borrados manuales. | Media | Superadmin |
| RF-013 | Alertar eventos críticos | El sistema debe poder marcar como críticos ciertos eventos (por ejemplo, anulaciones de pago) y permitir su filtrado rápido. | Media | Superadmin |
| RF-014 | Restringir acceso a bitácora | El sistema debe impedir el acceso a Recepción y a roles no autorizados. | Alta | Sistema |
| RF-015 | Registrar consultas de bitácora | El sistema debe registrar cada consulta a la bitácora como un evento auditado. | Media | Sistema |

> [SUPUESTO A VALIDAR CON EL CLIENTE] La lista exacta de acciones auditadas por módulo, la marca de “crítico” y el período de retención deben confirmarse con el cliente antes de implementar. Se asume que las exportaciones siempre se registran y que la bitácora no se puede borrar manualmente, ni siquiera por el Superadmin.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El registro de un evento no debe agregar más de 50 ms a la operación auditada.
- **RNF-002:** La consulta de bitácora filtrada por rango de un mes debe responder en menos de 2 segundos con hasta 100 000 eventos.

### 6.2 Seguridad y Privacidad

- **RNF-003:** La bitácora es de solo inserción; no se permite UPDATE ni DELETE a nivel de aplicación ni de base de datos para usuarios del sistema.
- **RNF-004:** El acceso a la bitácora debe validarse por rol y sucursal en backend.
- **RNF-005:** Los eventos no deben almacenar contenido clínico ni datos sensibles completos; solo referencias y valores de cambio necesarios para la auditoría.

### 6.3 Disponibilidad

- **RNF-006:** El servicio de bitácora debe estar disponible en el horario operativo del centro, con objetivo del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios deben incluir la bitácora y permitir su restauración íntegra.

### 6.4 Usabilidad

- **RNF-008:** La interfaz debe permitir filtrar por múltiples criterios y entender cada evento con etiquetas claras en español.
- **RNF-009:** El detalle de un evento debe mostrar los valores anteriores y nuevos en formato comparativo.

### 6.5 Escalabilidad

- **RNF-010:** El modelo debe soportar el crecimiento sostenido de eventos (millones de registros) sin degradación significativa, con particionado o archivado si es necesario.

### 6.6 Interoperabilidad

- **RNF-011:** La bitácora debe exponerse vía API REST (solo lectura) y exportarse a PDF y Excel; no se contemplan integraciones externas en esta etapa.

### 6.7 Mantenibilidad

- **RNF-012:** El catálogo de acciones auditadas debe ser configurable y versionado, con migraciones controladas.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** La bitácora debe permitir reconstruir acciones por usuario, por entidad y por período, y conservar los registros según la política de retención acordada; los registros no se borran manualmente.

---

## 7. Reglas de Negocio

- **RN-001:** Inmutabilidad — Ningún evento de bitácora puede editarse ni eliminarse; los errores se documentan con un nuevo evento que referencia al anterior.
- **RN-002:** Registro universal — Toda acción auditada, definida en el catálogo, se registra sin excepción, incluidas las consultas a la propia bitácora y las exportaciones.
- **RN-003:** Acceso restringido — Solo Superadmin y Administración (según alcance) acceden a la bitácora; Recepción no.
- **RN-004:** Scoping por sucursal — La consulta de bitácora respeta las sucursales asignadas; el Superadmin ve todo.
- **RN-005:** Sin datos sensibles completos — La bitácora no almacena contenido clínico ni datos sensibles completos; guarda referencias y valores de cambio necesarios.
- **RN-006:** Retención definida — La bitácora se conserva según la política acordada con el cliente; no se permiten borrados manuales ni siquiera por el Superadmin.
- **RN-007:** Exportaciones auditadas — Toda exportación de datos queda registrada como evento auditado con filtros y cantidad de registros.
- **RN-008:** Eventos críticos — Ciertos eventos (por ejemplo, anulación de pago, cambio de rol, desactivación de usuario) se marcan como críticos y se filtran con facilidad.
- **RN-009:** Integridad referencial — Los eventos conservan referencia a la entidad afectada y snapshot de datos clave cuando el esquema pueda cambiar.
- **RN-010:** Bitácora de bitácora — Las consultas y exportaciones de la bitácora también se auditan.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-006 (no borrado ni por Superadmin) y la RN-005 (sin contenido clínico en la bitácora) deben confirmarse; son decisiones de diseño que afectan la privacidad y la utilidad de la auditoría.

---

## 8. Casos de Uso Principales

### CU-01: Registrar evento auditado

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Registrar evento auditado |
| **Actor Principal** | Sistema (módulos consumidores) |
| **Actores Secundarios** | — |
| **Precondiciones** | Acción auditada en curso y usuario autenticado |
| **Postcondiciones** | Evento registrado de forma inmutable con todos los campos |
| **Trigger** | Un módulo ejecuta una acción del catálogo auditado |

**Flujo Principal:**
1. El módulo ejecuta la acción y, antes o después, invoca el servicio de bitácora.
2. El servicio recibe usuario, IP, módulo, entidad, acción, valores anteriores/nuevos y resultado.
3. El servicio valida la estructura y persiste el evento.
4. El servicio devuelve confirmación al módulo.
5. La operación continúa con normalidad.

**Flujos Alternativos:**
- **FA-01:** Si el servicio de bitácora falla, la acción se aborta (fail-closed) para no perder trazabilidad. [SUPUESTO A VALIDAR CON EL CLIENTE]

**Flujos de Excepción:**
- **FE-01:** Estructura inválida → el servicio rechaza el evento y alerta al equipo técnico.
- **FE-02:** Falla de persistencia → el módulo informa y permite reintentar; la acción no se considera exitosa.

### CU-02: Consultar bitácora por filtros

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Consultar bitácora por filtros |
| **Actor Principal** | Superadmin / Administración |
| **Actores Secundarios** | Sistema (scoping), módulo de auditoría |
| **Precondiciones** | Sesión activa con rol autorizado |
| **Postcondiciones** | Resultado filtrado y paginado; consulta registrada como evento |
| **Trigger** | El usuario abre la sección de bitácora y aplica filtros |

**Flujo Principal:**
1. El usuario abre la bitácora.
2. El sistema aplica el scoping por sucursal.
3. El usuario define filtros (usuario, módulo, acción, entidad, rango de fechas, resultado, crítico).
4. El sistema devuelve resultados paginados.
5. El usuario abre el detalle de un evento.
6. El sistema muestra todos los campos, incluidos valores anteriores/nuevos.
7. El sistema registra la consulta como evento.

**Flujos Alternativos:**
- **FA-01:** El usuario no define filtros; el sistema muestra el último período por defecto (por ejemplo, 7 días).

**Flujos de Excepción:**
- **FE-01:** Rango de fechas inválido → el sistema informa y ajusta.
- **FE-02:** El usuario no tiene sucursales asignadas → el sistema informa y no muestra resultados.

### CU-03: Exportar bitácora

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Exportar bitácora |
| **Actor Principal** | Superadmin / Administración |
| **Actores Secundarios** | Sistema (scoping y registro) |
| **Precondiciones** | Sesión activa con rol autorizado; filtros definidos |
| **Postcondiciones** | Archivo generado y evento de exportación registrado |
| **Trigger** | El usuario selecciona “Exportar” |

**Flujo Principal:**
1. El usuario define filtros y selecciona “Exportar”.
2. El sistema valida el alcance por sucursal.
3. El sistema genera el archivo en el formato elegido (PDF/Excel).
4. El sistema registra la exportación como evento auditado (usuario, filtros, cantidad de registros).
5. El sistema entrega el archivo al usuario.

**Flujos Alternativos:**
- **FA-01:** El usuario elige exportar todo el período visible o solo lo seleccionado.

**Flujos de Excepción:**
- **FE-01:** Falla la generación → el sistema informa y permite reintentar; la exportación no se registra como exitosa.

---

## 9. Criterios de Aceptación

### CA-001: Registro inmutable
- **Given** un evento registrado,
- **When** se intenta editar o eliminar por cualquier vía,
- **Then** el sistema rechaza la operación y registra el intento.

### CA-002: Registro de inicio de sesión
- **Given** un usuario que inicia sesión,
- **When** la autenticación es exitosa,
- **Then** el sistema registra el evento con usuario, IP y fecha/hora.

### CA-003: Registro de anulación de pago como crítico
- **Given** una anulación de pago,
- **When** se ejecuta,
- **Then** el sistema registra el evento marcado como crítico y lo hace filtrable.

### CA-004: Scoping por sucursal en consulta
- **Given** un usuario de Administración asignado solo a “Yacuiba Centro”,
- **When** consulta la bitácora,
- **Then** el sistema solo muestra eventos de esa sucursal.

### CA-005: Restricción a Recepción
- **Given** un usuario de Recepción,
- **When** intenta abrir la bitácora,
- **Then** el sistema no muestra la opción y rechaza la llamada por API.

### CA-006: Exportación auditada
- **Given** un Superadmin que exporta la bitácora,
- **When** la exportación finaliza,
- **Then** el sistema genera el archivo y registra la propia exportación como evento.

### CA-007: Detalle de evento con valores anteriores y nuevos
- **Given** un evento de cambio de teléfono de sucursal,
- **When** el usuario abre el detalle,
- **Then** el sistema muestra el valor anterior y el nuevo, junto con usuario, fecha, hora e IP.

### CA-008: Retención sin borrado manual
- **Given** una política de retención de 5 años,
- **When** el Superadmin intenta borrar eventos manualmente,
- **Then** el sistema lo impide y conserva los registros hasta su purga automática por política.

### CA-009: Consulta de bitácora auditada
- **Given** un usuario que consulta la bitácora,
- **When** aplica filtros y obtiene resultados,
- **Then** el sistema registra la consulta como un evento auditado.

### CA-010: Integridad referencial en cambios de esquema
- **Given** un evento que referencia una entidad cuyo esquema cambió,
- **When** el usuario consulta el evento,
- **Then** el sistema muestra la información original a partir del snapshot guardado.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Volumen elevado de eventos que degrade el rendimiento | Media | Alto | Alto | Índices, particionado por fecha, archivado y pruebas de carga. |
| RT-002 | Registro incompleto por módulos que no invocan la bitácora | Media | Alto | Alto | Catálogo de acciones auditadas, middleware transversal y pruebas de cobertura. |
| RT-003 | Exposición de datos sensibles dentro de la bitácora | Baja | Alto | Medio | Guardar solo referencias y valores de cambio necesarios; revisar eventos con datos personales. |
| RT-004 | Borrado accidental o malicioso de la bitácora | Baja | Alto | Medio | Política de solo inserción a nivel de base de datos y roles de aplicación restringidos. |
| RT-005 | Ambigüedad en el catálogo de acciones auditadas | Alta | Medio | Alto | Definir el catálogo con el cliente durante el diseño y revisarlo por módulo. |
| RT-006 | Falla del servicio de bitácora que bloquee operaciones | Media | Medio | Medio | Estrategia fail-closed con alertas, reintentos y monitoreo del servicio. |
| RT-007 | Crecimiento no controlado de la bitácora sin política de retención | Media | Medio | Medio | Definir retención con el cliente y automatizar la purga con registro de la propia purga. |
| RT-008 | Dependencia de una sola persona para operar la bitácora | Media | Medio | Medio | Documentación, runbook de auditoría y transferencia de conocimiento. |

---

**Fin del documento**