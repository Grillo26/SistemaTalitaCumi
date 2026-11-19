# Autenticación y sesiones
**Módulo:** Usuarios, roles y seguridad
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo gobierna el acceso al sistema: cómo se autentica un usuario, cómo se gestionan sus credenciales, cómo se crean y cierran sesiones, cómo se bloquean cuentas por intentos fallidos y cómo se invalidan sesiones ante eventos de seguridad. Aplica a los tres roles operativos (Superadmin, Administración y Recepción); profesionales y padres no acceden al sistema.

Hoy el acceso no está formalizado: no hay política de contraseñas, no hay control de sesiones y no hay registro sistemático de intentos fallidos. Este submódulo reemplaza esa informalidad por autenticación con credenciales robustas, políticas de contraseña, bloqueo por intentos fallidos, expiración de sesión y posibilidad de cierre remoto por parte del Superadmin. Todo ello con bitácora y respeto a la normativa de datos personales.

El valor que aporta es la protección del acceso: sin autenticación sólida, la separación por roles y el scoping por sucursal pierden sentido. Se integra con Gestión de usuarios, Roles y permisos, Auditoría y bitácora, y con el frontend Angular y la API Laravel sobre PostgreSQL, operados en Docker.

---

## 2. Alcance

### Incluido en este módulo

- Autenticación por credenciales (usuario/correo + contraseña).
- Política de contraseñas: longitud mínima, complejidad, caducidad opcional, historial de no repetición.
- Cambio de contraseña por el propio usuario y reseteo por el Superadmin.
- Bloqueo temporal de la cuenta tras N intentos fallidos configurables y desbloqueo por el Superadmin.
- Creación, expiración y cierre de sesiones, con cierre remoto por el Superadmin.
- Invalidación de sesiones al desactivar un usuario, cambiar su rol o resetear su contraseña.
- Registro en bitácora de inicios de sesión exitosos y fallidos, cierres, bloqueos y reseteos.
- Mensajes de error genéricos que no revelan si el usuario existe.
- Control de contraseña temporal con cambio forzado en el siguiente inicio de sesión.

### Excluido de este módulo

- **Gestión de usuarios (altas, bajas, asignaciones):** submódulo `01_gestion_usuarios.md`.
- **Definición de roles y permisos:** submódulo `02_roles_permisos_sucursal.md`.
- **Bitácora detallada:** submódulo `04_auditoria_bitacora.md`.
- **Doble factor (2FA), SSO, OAuth, biometría:** fuera de alcance en Etapa 1.
- **Autenticación de profesionales o padres:** no aplica (no usan el sistema).
- **Gestión de dispositivos confiables:** fuera de alcance.
- **Integraciones externas de identidad:** fuera de alcance.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Autentica; resetea contraseñas; desbloquea cuentas; cierra sesiones remotas. |
| Administración | Autentica; cambia su contraseña; sin acceso a reseteos ni desbloqueos. |
| Recepción | Autentica; cambia su contraseña. |
| Profesionales y padres | **No usan el sistema**. |

### Sucursales aplicables

- **La autenticación es global** y previa a cualquier operación de sucursal.
- **El alcance por sucursal se aplica después de la autenticación**, según los roles y permisos del usuario.
- **Los datos de menores y de salud** quedan protegidos desde el momento de la autenticación.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La política exacta de contraseñas (longitud mínima, complejidad, caducidad y número de intentos antes del bloqueo) y la duración de la sesión inactiva deben confirmarse con el cliente. Se proponen valores razonables por defecto.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción y Administración inician sesión muchas veces al día; una autenticación ágil pero segura no debe convertirse en un obstáculo. Con sesiones bien gestionadas, el personal no pierde trabajo por expiraciones inesperadas y el Superadmin puede cerrar sesiones remotas si sospecha un uso indebido. El bloqueo por intentos fallidos evita ataques de fuerza bruta y la política de contraseñas reduce el riesgo de accesos no autorizados.

### 3.2 Justificación en la Atención al Niño y su Familia

La confianza de la familia depende de que sus datos estén protegidos. Una autenticación sólida garantiza que solo personal autorizado acceda a la ficha del niño, sus turnos y sus pagos, y que las sesiones no queden abiertas en equipos compartidos.

### 3.3 Justificación Normativa

- **Ley 1080 de Ciudadanía Digital:** obligaciones de resguardo de datos personales y de credenciales de acceso [verificar vigencia].
- **Ley 164 General de Telecomunicaciones y TIC:** marco para el tratamiento electrónico seguro de la información [verificar vigencia].
- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** secreto profesional y conservación de registros [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La bitácora de inicios de sesión y los bloqueos son mecanismos que permiten demostrar diligencia en la protección del acceso.

### 3.4 Justificación Tecnológica

Frente a la ausencia de controles, el módulo aporta: hashing seguro de contraseñas, tokens de sesión con expiración, invalidación remota, bloqueo por intentos fallidos y bitácora completa. En el stack Laravel + Angular se implementa con mecanismos nativos de autenticación por token y middleware de sesión, sobre PostgreSQL y Docker.

---

## 4. Funciones Principales

### FP-01: Autenticar usuario
- **Descripción:** Permite a un usuario iniciar sesión con sus credenciales. El sistema valida contra la política vigente, controla intentos fallidos y crea una sesión con expiración.
- **Actor principal:** Superadmin, Administración y Recepción.
- **Resultado esperado:** Sesión activa con token válido, bitácora del inicio y acceso a los módulos según rol y sucursal.

### FP-02: Gestionar contraseñas
- **Descripción:** Permite al usuario cambiar su contraseña respetando la política y permite al Superadmin resetearla, forzando el cambio en el siguiente inicio de sesión.
- **Actor principal:** Superadmin (reseteo); usuario (cambio).
- **Resultado esperado:** Contraseña vigente, histórica registrada y bitácora del cambio.

### FP-03: Gestionar sesiones
- **Descripción:** Crea, expira y cierra sesiones; permite al Superadmin cerrar sesiones remotas de un usuario y las invalida ante cambios de rol, desactivación o reseteo.
- **Actor principal:** Sistema; Superadmin (cierre remoto).
- **Resultado esperado:** Sesiones controladas y revocables, con bitácora de cierres.

### FP-04: Bloquear y desbloquear cuentas
- **Descripción:** Bloquea temporalmente una cuenta tras N intentos fallidos y permite al Superadmin desbloquearla, registrando ambos eventos.
- **Actor principal:** Sistema (bloqueo); Superadmin (desbloqueo).
- **Resultado esperado:** Protección contra fuerza bruta y control de desbloqueos con bitácora.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Iniciar sesión | El sistema debe autenticar al usuario con usuario/correo y contraseña, devolviendo un token de sesión con expiración. | Alta | Todos los roles |
| RF-002 | Validar política de contraseñas | El sistema debe exigir contraseñas con longitud y complejidad mínimas y, si se configura, caducidad y no repetición de las últimas N contraseñas. | Alta | Sistema |
| RF-003 | Cambiar contraseña | El sistema debe permitir al usuario cambiar su contraseña validando la actual y la nueva contra la política. | Alta | Todos los roles |
| RF-004 | Resetear contraseña | El sistema debe permitir al Superadmin resetear la contraseña de un usuario, generando una temporal y forzando el cambio en el siguiente inicio. | Alta | Superadmin |
| RF-005 | Bloquear por intentos fallidos | El sistema debe bloquear temporalmente la cuenta tras N intentos fallidos configurables, registrando el evento. | Alta | Sistema |
| RF-006 | Desbloquear cuenta | El sistema debe permitir al Superadmin desbloquear una cuenta bloqueada, registrando el motivo. | Alta | Superadmin |
| RF-007 | Expirar sesión por inactividad | El sistema debe expirar la sesión tras un tiempo de inactividad configurable. | Alta | Sistema |
| RF-008 | Cerrar sesión | El sistema debe permitir al usuario cerrar su sesión y debe invalidar el token inmediatamente. | Alta | Todos los roles |
| RF-009 | Cerrar sesión remota | El sistema debe permitir al Superadmin cerrar todas las sesiones activas de un usuario, registrando la acción. | Alta | Superadmin |
| RF-010 | Invalidar sesiones al cambiar rol o estado | El sistema debe invalidar las sesiones activas de un usuario al desactivarlo o cambiarle el rol. | Alta | Sistema |
| RF-011 | Mensajes de error genéricos | El sistema debe devolver mensajes genéricos que no revelen si el usuario existe. | Alta | Sistema |
| RF-012 | Registrar bitácora de accesos | El sistema debe registrar inicios exitosos, intentos fallidos, bloqueos, reseteos, cierres y cierres remotos. | Alta | Sistema |
| RF-013 | Forzar cambio de contraseña temporal | El sistema debe forzar el cambio de contraseña temporal en el primer inicio de sesión tras un reseteo. | Alta | Sistema |
| RF-014 | Proteger tokens | El sistema debe proteger los tokens de sesión (transporte HTTPS y almacenamiento seguro en el cliente). | Alta | Sistema |
| RF-015 | Limitar sesiones concurrentes | El sistema debe permitir configurar un número máximo de sesiones concurrentes por usuario. | Media | Sistema |
| RF-016 | Mostrar último acceso | El sistema debe mostrar al usuario la fecha y hora de su último acceso exitoso. | Baja | Todos los roles |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Los valores por defecto (longitud mínima 10, complejidad media, bloqueo tras 5 intentos, sesión inactiva 30 minutos, máximo 1 sesión concurrente) deben confirmarse con el cliente. Asimismo, debe decidirse si se implementa caducidad de contraseña y cada cuánto.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El inicio de sesión debe responder en menos de 1 segundo en condiciones normales con 30 usuarios concurrentes.
- **RNF-002:** La validación de token en cada solicitud debe resolverse en menos de 50 ms.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Las contraseñas se almacenan con hash seguro (bcrypt o Argon2) y nunca se registran en bitácora.
- **RNF-004:** Todo el tráfico debe viajar por HTTPS.
- **RNF-005:** Los tokens de sesión deben tener expiración, rotación al iniciar sesión y revocación en eventos de seguridad.
- **RNF-006:** Los mensajes de error no deben revelar si el usuario existe.
- **RNF-007:** Los datos de menores y de salud accedidos durante la sesión deben tratarse conforme a la normativa aplicable.

### 6.3 Disponibilidad

- **RNF-008:** El servicio de autenticación debe estar disponible en el horario operativo del centro, con objetivo del 99,5 % mensual.
- **RNF-009:** Los respaldos diarios deben incluir usuarios, contraseñas hasheadas y bitácora de accesos.

### 6.4 Usabilidad

- **RNF-010:** La pantalla de inicio de sesión debe ser simple, en español y funcionar en computadora y tablet.
- **RNF-011:** El cambio de contraseña debe ser claro y guiado, con mensajes sobre la política vigente.

### 6.5 Escalabilidad

- **RNF-012:** El modelo debe soportar el crecimiento de usuarios y sesiones concurrentes sin cambios de arquitectura.

### 6.6 Interoperabilidad

- **RNF-013:** La autenticación debe exponerse vía API REST para el frontend Angular; no se contemplan integraciones externas en esta etapa.

### 6.7 Mantenibilidad

- **RNF-014:** Los parámetros de política de contraseñas y sesión deben ser configurables desde el módulo 01 (configuración general).

### 6.8 Trazabilidad y Auditoría

- **RNF-015:** Todos los eventos de autenticación y sesión deben quedar en bitácora con usuario, fecha, hora e IP; los registros no se borran.

---

## 7. Reglas de Negocio

- **RN-001:** Autenticación obligatoria — Ninguna operación del sistema puede ejecutarse sin sesión válida.
- **RN-002:** Credenciales únicas — El identificador de login (usuario/correo) es único y lo define el módulo de usuarios.
- **RN-003:** Política de contraseñas — Toda contraseña debe cumplir la política vigente (longitud, complejidad y, si se configura, caducidad y no repetición).
- **RN-004:** Bloqueo por intentos fallidos — Tras N intentos fallidos consecutivos, la cuenta se bloquea temporalmente y solo el Superadmin puede desbloquearla.
- **RN-005:** Sesión inactiva — La sesión expira tras el tiempo de inactividad configurado; el usuario debe autenticarse nuevamente.
- **RN-006:** Invalidación por eventos de seguridad — Desactivar un usuario, cambiar su rol o resetear su contraseña invalida sus sesiones activas.
- **RN-007:** Cambio forzado de contraseña temporal — Tras un reseteo, el usuario debe cambiar la contraseña en el siguiente inicio de sesión antes de operar.
- **RN-008:** Mensajes genéricos — Los errores de autenticación no revelan si el usuario existe.
- **RN-009:** Cierre remoto auditado — El cierre remoto de sesiones por el Superadmin requiere motivo y queda en bitácora.
- **RN-010:** Sin autenticación para profesionales o padres — Solo los tres roles operativos se autentican; no se habilitan cuentas para otros actores.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-004 (N intentos, bloqueo temporal con duración definida) y la RN-005 (tiempo de inactividad) deben confirmarse con el cliente; se proponen valores por defecto pero la decisión final es del negocio.

---

## 8. Casos de Uso Principales

### CU-01: Iniciar sesión

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Iniciar sesión |
| **Actor Principal** | Superadmin / Administración / Recepción |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Usuario activo y no bloqueado; credenciales vigentes |
| **Postcondiciones** | Sesión activa con token válido, bitácora del inicio y acceso a módulos según rol y sucursal |
| **Trigger** | El usuario ingresa sus credenciales en la pantalla de inicio de sesión |

**Flujo Principal:**
1. El usuario ingresa usuario/correo y contraseña.
2. El sistema valida las credenciales contra el hash almacenado.
3. El sistema verifica que la cuenta esté activa y no bloqueada.
4. El sistema crea la sesión con expiración y devuelve el token.
5. El sistema registra el inicio en bitácora.
6. El usuario es redirigido al panel principal.

**Flujos Alternativos:**
- **FA-01:** El usuario tiene una contraseña temporal y el sistema lo redirige al cambio obligatorio antes de operar.

**Flujos de Excepción:**
- **FE-01:** Credenciales incorrectas → el sistema devuelve mensaje genérico, incrementa el contador y, si corresponde, bloquea la cuenta.
- **FE-02:** Cuenta bloqueada → el sistema informa el bloqueo y sugiere contactar al Superadmin.
- **FE-03:** Cuenta inactiva → el sistema rechaza el acceso con mensaje genérico y registra el intento.

### CU-02: Cambiar contraseña

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Cambiar contraseña |
| **Actor Principal** | Usuario autenticado |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión activa; contraseña actual conocida |
| **Postcondiciones** | Contraseña actualizada, histórica registrada y bitácora del cambio |
| **Trigger** | El usuario selecciona “Cambiar contraseña” |

**Flujo Principal:**
1. El usuario ingresa contraseña actual, nueva y confirmación.
2. El sistema valida la contraseña actual.
3. El sistema valida la nueva contra la política.
4. El sistema verifica que la nueva no esté en el histórico reciente (si aplica).
5. El sistema guarda el nuevo hash y actualiza el histórico.
6. El sistema registra el cambio en bitácora.
7. El sistema confirma el cambio.

**Flujos Alternativos:**
- **FA-01:** El usuario cancela; la contraseña actual se mantiene.

**Flujos de Excepción:**
- **FE-01:** Contraseña actual incorrecta → el sistema rechaza el cambio.
- **FE-02:** Nueva contraseña no cumple la política → el sistema informa los requisitos.
- **FE-03:** Nueva contraseña igual a una reciente → el sistema rechaza según la política.

### CU-03: Resetear contraseña y forzar cambio

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Resetear contraseña y forzar cambio |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; usuario existente |
| **Postcondiciones** | Contraseña temporal vigente, sesiones activas invalidadas y cambio forzado en el siguiente inicio |
| **Trigger** | El Superadmin selecciona “Resetear contraseña” |

**Flujo Principal:**
1. El Superadmin abre la ficha del usuario y selecciona “Resetear contraseña”.
2. El sistema solicita confirmación y motivo.
3. El Superadmin confirma.
4. El sistema genera una contraseña temporal, la asigna y marca al usuario como “debe cambiar contraseña”.
5. El sistema invalida las sesiones activas del usuario.
6. El sistema registra en bitácora la acción.
7. El sistema confirma el reseteo.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela; la contraseña actual se mantiene.

**Flujos de Excepción:**
- **FE-01:** Motivo vacío → el sistema no permite continuar.

---

## 9. Criterios de Aceptación

### CA-001: Inicio de sesión exitoso
- **Given** un usuario activo con credenciales vigentes,
- **When** ingresa sus datos,
- **Then** el sistema crea la sesión, devuelve token y registra el inicio en bitácora.

### CA-002: Bloqueo por intentos fallidos
- **Given** un usuario con 4 intentos fallidos y un máximo de 5,
- **When** falla un quinto intento,
- **Then** el sistema bloquea la cuenta y registra el bloqueo en bitácora.

### CA-003: Mensaje genérico en credenciales incorrectas
- **Given** un usuario o contraseña inválidos,
- **When** el usuario intenta iniciar sesión,
- **Then** el sistema responde con mensaje genérico que no revela si el usuario existe.

### CA-004: Expiración de sesión por inactividad
- **Given** una sesión activa con límite de inactividad configurado,
- **When** el tiempo transcurre sin actividad,
- **Then** el sistema invalida la sesión y solicita autenticarse nuevamente.

### CA-005: Cambio forzado de contraseña temporal
- **Given** un usuario con contraseña temporal,
- **When** inicia sesión,
- **Then** el sistema lo redirige obligatoriamente al cambio de contraseña antes de operar.

### CA-006: Política de contraseñas
- **Given** una política vigente,
- **When** el usuario ingresa una contraseña que no la cumple,
- **Then** el sistema rechaza el cambio e informa los requisitos.

### CA-007: Invalidación al desactivar usuario
- **Given** un usuario con sesión activa,
- **When** el Superadmin lo desactiva,
- **Then** el sistema invalida sus sesiones y le bloquea el acceso inmediato.

### CA-008: Cierre remoto por Superadmin
- **Given** un usuario con sesiones activas,
- **When** el Superadmin cierra sesiones remotas con motivo,
- **Then** el sistema invalida las sesiones y registra la acción en bitácora.

### CA-009: Protección del token
- **Given** una sesión activa,
- **When** el cliente intenta usar el token fuera de HTTPS o con expiración cumplida,
- **Then** el sistema rechaza la solicitud.

### CA-010: Cambio de contraseña con histórico
- **Given** una política de no repetición de las últimas 5 contraseñas,
- **When** el usuario intenta usar una contraseña reciente,
- **Then** el sistema rechaza el cambio.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Almacenamiento inseguro de contraseñas | Baja | Alto | Medio | Uso de bcrypt/Argon2, revisión de código y pruebas de seguridad. |
| RT-002 | Bloqueos frecuentes por política demasiado estricta que afecten la operación | Media | Medio | Medio | Validar política con el cliente y permitir desbloqueo rápido por Superadmin. |
| RT-003 | Sesiones que no se invalidan al cambiar rol o estado | Media | Alto | Alto | Pruebas de invalidación por evento (RF-010) y CA-007. |
| RT-004 | Exposición de tokens en logs o errores | Baja | Alto | Medio | Configuración de logs, exclusión de tokens y pruebas de seguridad. |
| RT-005 | Ataques de fuerza bruta no mitigados | Media | Alto | Alto | Bloqueo por intentos, mensajes genéricos y monitoreo de bitácora. |
| RT-006 | Conectividad limitada que interrumpa sesiones | Media | Medio | Medio | Reintentos controlados, sesión inactiva configurable y mensajes claros. |
| RT-007 | Ambigüedad en la política de contraseñas | Alta | Medio | Alto | Acordar política con el cliente y parametrizarla en el módulo 01. |
| RT-008 | Dependencia de una sola persona para administrar la seguridad | Media | Medio | Medio | Documentación, runbook de incidentes y transferencia de conocimiento. |

---

**Fin del documento**