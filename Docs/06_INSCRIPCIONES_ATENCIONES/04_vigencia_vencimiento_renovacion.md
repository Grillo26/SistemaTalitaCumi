# Vigencia, Vencimiento y Renovación
**Módulo:** Inscripciones y Atenciones
**Etapa:** 1
**Versión:** 1.0
**Fecha:** Noviembre de 2025
**Estado:** Borrador

---

## 1. Introducción

Este submódulo define y gestiona las reglas de vigencia, vencimiento y renovación de las inscripciones a salas grupales y de los paquetes de sesiones individuales. Su propósito es que el sistema sepa en todo momento qué está vigente, qué está por vencer, qué venció y qué debe renovarse, sin depender de la memoria del personal ni de anotaciones en cuadernos.

El centro trabaja con períodos mensuales para salas grupales y con paquetes de N sesiones para terapia individual. En ambos casos las sesiones tienen fecha de vigencia y no se trasladan al siguiente período. Esta regla, simple en apariencia, es la que más problemas genera hoy: se pierden vencimientos, se confunden saldos y no hay alertas oportunas para renovar. El resultado es doble: familias que llegan y se enteran de que el paquete venció, y administración que no puede proyectar ingresos con certeza.

El valor de este submódulo es doble: por un lado, deja explícita la regla de negocio (lo que vence, vence; lo que no se usó, no se recupera); por otro, automatiza el cálculo de vencimientos, genera alertas de próximos vencimientos, habilita la renovación con un clic y produce los reportes de vencidos y por vencer. Se integra con Inscripción a Salas Grupales (6.1), Paquetes de Sesiones Individuales (6.2), Agenda y Turnos, Pagos y Caja, y Reportes y Alertas.

---

## 2. Alcance

**Incluido en este módulo:**
- Cálculo automático de la fecha de vencimiento de cada inscripción y paquete a partir de su fecha de fin.
- Clasificación de estados de vigencia: vigente, por vencer, vencido, renovado, retirado.
- Panel de alertas de próximos vencimientos y vencidos, segmentado por sucursal.
- Configuración de la ventana de antelación para "por vencer" (por ejemplo 7 o 15 días).
- Renovación de inscripciones y paquetes con generación de nueva entidad vinculada a la anterior.
- Registro de renovaciones y su trazabilidad en bitácora.
- Reportes de inscripciones y paquetes vencidos y por vencer, con exportación a PDF y Excel.
- Bloqueo de nuevas asistencias sobre inscripciones o paquetes vencidos.
- Reglas explícitas de no traslado de sesiones entre períodos.
- Visualización del estado de vigencia en la ficha resumen del paciente.

**Excluido de este módulo:**
- Recuperación de sesiones perdidas (no aplica; las sesiones vencen y se pierden).
- Descuentos o compensaciones por vencimiento.
- Renovación automática sin intervención humana (la renovación es siempre manual).
- Notificaciones automáticas por WhatsApp o correo (fuera de alcance del proyecto).
- Plan de cuotas (etapa 2).
- Facturación.

**Actores / Roles involucrados:**
- **Recepción:** consulta vencimientos y ejecuta renovaciones de su sucursal.
- **Administración:** consulta reportes, renueva, da de baja y configura la ventana de alertas.
- **Superadmin:** acceso total y configuración.
- Los profesionales y los padres NO usan el sistema.

**Sucursales aplicables:**
- Las inscripciones y paquetes pertenecen a una sucursal.
- Los reportes y el panel de alertas se segmentan por sucursal.
- El paciente es global.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa
Elimina el cálculo manual de fechas de vencimiento y reduce los olvidos de renovación. Recepción ve en un panel qué está por vencer esta semana y qué ya venció, y puede renovar con un clic reutilizando la configuración vigente. Administración obtiene reportes de vencidos y por vencer sin revisar cuadernos, y puede anticipar la carga de trabajo y la proyección de ingresos.

### 3.2 Justificación en la Atención al Niño y su Familia
Asegura continuidad: la familia recibe aviso oportuno de que debe renovar y no se entera tarde de que el paquete o la inscripción venció. Al respetar la regla de no traslado de sesiones, el sistema es transparente y evita reclamos. La familia percibe orden y previsibilidad en el servicio.

### 3.3 Justificación Normativa
- Constitución Política del Estado (protección de la niñez y privacidad) [verificar vigencia].
- Ley 548 Código Niña, Niño y Adolescente [verificar vigencia].
- Ley 1080 de Ciudadanía Digital (resguardo de información digital) [verificar vigencia].
- No existe una norma específica sobre vencimiento de paquetes de servicios de desarrollo infantil; la regla es contractual y se documenta como regla de negocio interna, aprobada por el cliente.

### 3.4 Justificación Tecnológica
El cálculo automático de vencimientos, las alertas y la renovación con un clic reducen errores humanos y liberan tiempo operativo. La base de datos PostgreSQL permite consultas rápidas por sucursal y período, y los reportes se generan sin intervención manual. La máquina de estados de vigencia queda centralizada en el backend, evitando combinaciones inválidas.

---

## 4. Funciones Principales

### FP-01: Calcular y clasificar vencimientos
- **Descripción:** Calcula automáticamente la fecha de vencimiento de inscripciones y paquetes a partir de la fecha de fin y clasifica el estado de vigencia (vigente, por vencer, vencido, renovado, retirado) según la ventana de alerta configurada.
- **Actor principal:** Sistema (con supervisión de Administración).
- **Resultado esperado:** Cada inscripción y paquete tiene su estado de vigencia actualizado y visible en ficha, listados y reportes.

### FP-02: Alertar próximos vencimientos y vencidos
- **Descripción:** Genera un panel de alertas con las inscripciones y paquetes que vencen dentro de la ventana configurada y los que ya vencieron, segmentado por sucursal.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Panel visible al iniciar sesión con la lista de próximos vencimientos y vencidos, con acceso directo a la renovación.

### FP-03: Renovar inscripciones y paquetes
- **Descripción:** Permite generar la renovación de una inscripción o paquete con un clic, reutilizando la configuración vigente, aplicando la tarifa vigente al nuevo período y vinculando la nueva entidad a la anterior.
- **Actor principal:** Recepción y Administración.
- **Resultado esperado:** Nueva inscripción o paquete creado para el siguiente período, entidad origen marcada como "Renovada" y registro en bitácora.

### FP-04: Reportar vencidos y por vencer
- **Descripción:** Genera reportes por sucursal de inscripciones y paquetes vencidos y por vencer, con filtros por período, sucursal y servicio, y exportación a PDF y Excel.
- **Actor principal:** Administración.
- **Resultado esperado:** Reportes descargables con datos de paciente, tutor, servicio, período, estado y saldo de sesiones.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Cálculo de vencimiento | El sistema debe calcular la fecha de vencimiento de cada inscripción o paquete a partir de su fecha de fin. | Alta | Sistema |
| RF-002 | Clasificación de vigencia | El sistema debe clasificar cada entidad como "Vigente", "Por vencer", "Vencido", "Renovado" o "Retirado" según su fecha de fin y la ventana de alerta. | Alta | Sistema |
| RF-003 | Configuración de ventana de alerta | Administración debe poder configurar los días de antelación para considerar una entidad "Por vencer" (por ejemplo 7 o 15 días). | Alta | Administración |
| RF-004 | Panel de alertas | El sistema debe mostrar un panel de alertas con las inscripciones y paquetes por vencer y vencidos, segmentado por sucursal. | Alta | Recepción / Administración |
| RF-005 | Renovación con un clic | El sistema debe permitir renovar una inscripción o paquete con un solo clic, generando una nueva entidad vinculada con la configuración vigente. | Alta | Recepción / Administración |
| RF-006 | Tarifa vigente en renovación | Al renovar, el sistema debe aplicar la tarifa vigente a la nueva fecha de inicio y pedir confirmación si cambió respecto a la anterior. | Alta | Sistema |
| RF-007 | Bloqueo por vencimiento | El sistema debe bloquear el registro de nuevas asistencias sobre inscripciones o paquetes vencidos. | Alta | Sistema |
| RF-008 | Reporte de vencidos | El sistema debe generar un reporte de inscripciones y paquetes vencidos con filtros por sucursal, período y servicio. | Alta | Administración |
| RF-009 | Reporte de por vencer | El sistema debe generar un reporte de inscripciones y paquetes por vencer en la ventana configurada. | Alta | Administración |
| RF-010 | Exportación de reportes | El sistema debe permitir exportar los reportes de vencidos y por vencer a PDF y Excel. | Media | Administración |
| RF-011 | Bitácora de renovaciones | El sistema debe registrar en bitácora cada renovación con usuario, fecha, entidad origen y entidad destino. | Alta | Sistema |
| RF-012 | No traslado de sesiones | El sistema no debe trasladar sesiones no consumidas de un paquete vencido al siguiente; el saldo vencido se cierra. | Alta | Sistema |
| RF-013 | Estado de la entidad origen | Al renovar, el sistema debe marcar la entidad origen como "Renovada" y vincularla con la nueva. | Alta | Sistema |
| RF-014 | Control de acceso por sucursal | El panel y los reportes deben respetar las sucursales asignadas al usuario. | Alta | Sistema |
| RF-015 | Alerta en ficha del paciente | La ficha resumen del paciente debe mostrar si tiene inscripciones o paquetes por vencer o vencidos. | Media | Recepción / Administración |
| RF-016 | Renovación múltiple | El sistema debe permitir renovar varias entidades de un mismo paciente en una sola operación (por ejemplo, sala grupal y terapia individual). | Media | Recepción / Administración |
| RF-017 | Enlace a WhatsApp desde alerta | El panel de alertas debe ofrecer un botón que abra WhatsApp Web con el número del tutor para avisar del vencimiento (sin envío automático). | Media | Recepción |

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento
- **RNF-001:** El panel de alertas debe cargar en menos de 2 segundos con hasta 1.000 entidades por vencer por sucursal y 10 usuarios concurrentes.
- **RNF-002:** La renovación con un clic debe completarse en menos de 3 segundos.

### 6.2 Seguridad y Privacidad
- **RNF-003:** Autenticación obligatoria; contraseñas almacenadas con hash seguro (bcrypt o Argon2).
- **RNF-004:** Comunicación por HTTPS; el panel y los reportes respetan rol y sucursal.
- **RNF-005:** Los datos de menores y tutores visibles en el panel y reportes se limitan a lo necesario para la gestión (nombre, servicio, período, estado); no exponen datos clínicos.

### 6.3 Disponibilidad
- **RNF-006:** Disponibilidad del 99% en horario de atención (lunes a sábado, 07:00 a 20:00).
- **RNF-007:** Respaldo automático diario; RTO de 4 horas y RPO de 24 horas.

### 6.4 Usabilidad
- **RNF-008:** El panel de alertas debe mostrarse al iniciar sesión y permitir renovar sin salir de la vista, en un máximo de 3 clics.

### 6.5 Escalabilidad
- **RNF-009:** El modelo debe soportar el crecimiento de sucursales y entidades sin degradar los tiempos del RNF-001.

### 6.6 Interoperabilidad
- **RNF-010:** Exportación a PDF y Excel; generación de enlaces `wa.me` para contacto con el tutor; sin integraciones externas en esta etapa.

### 6.7 Mantenibilidad
- **RNF-011:** La ventana de alerta es configurable sin cambios de código; migraciones versionadas; pruebas automatizadas de la máquina de estados de vigencia.

### 6.8 Trazabilidad y Auditoría
- **RNF-012:** Toda renovación, baja y cambio de estado de vigencia queda en bitácora con usuario, fecha y datos modificados; el histórico de vencimientos se preserva y no se elimina.

---

## 7. Reglas de Negocio

- **RN-001:** Vencimiento al terminar el período — Las inscripciones y paquetes vencen al llegar su fecha de fin; no se extienden automáticamente.
- **RN-002:** Sesiones no trasladables — Las sesiones no consumidas al vencimiento no pasan al siguiente período; se cierran con el paquete o inscripción.
- **RN-003:** Renovación manual — La renovación es siempre una acción humana; el sistema no renueva automáticamente.
- **RN-004:** Renovación vinculada — La entidad renovada queda marcada como "Renovada" y vinculada a la nueva entidad, preservando el historial.
- **RN-005:** Tarifa vigente en renovación — Al renovar, se aplica la tarifa vigente a la nueva fecha de inicio; si cambió, se pide confirmación explícita.
- **RN-006:** Bloqueo por vencimiento — No se pueden registrar asistencias sobre entidades vencidas; se debe renovar primero.
- **RN-007:** Ventana de alerta configurable — Administración define los días de antelación para "Por vencer"; el valor por defecto es 7 días.
- **RN-008:** Sin recuperación — Las sesiones no consumidas por inasistencia se pierden y no se recuperan al renovar.
- **RN-009:** Historial preservado — Las entidades renovadas, vencidas y retiradas se conservan en el histórico para reportes.
- **RN-010:** Acceso por sucursal — El panel y los reportes respetan las sucursales asignadas al usuario.
- **RN-011:** Consistencia de cálculo — La fecha de vencimiento se calcula siempre a partir de la fecha de fin de la entidad; no puede editarse manualmente.

---

## 8. Casos de Uso Principales

### CU-01: Consultar panel de próximos vencimientos

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Consultar panel de próximos vencimientos |
| **Actor Principal** | Recepción |
| **Actores Secundarios** | Administración |
| **Precondiciones** | Usuario autenticado con al menos una sucursal asignada; existen inscripciones o paquetes vigentes. |
| **Postcondiciones** | Recepción visualiza las entidades por vencer y vencidas de su sucursal, con acceso a renovación. |
| **Trigger** | Inicio de sesión o acceso manual al panel. |

**Flujo Principal:**
1. Recepción inicia sesión.
2. El sistema carga el panel de alertas.
3. Muestra inscripciones y paquetes por vencer en la ventana configurada y vencidos, con datos del paciente, servicio, período y estado.
4. Recepción puede hacer clic en una entidad para ver el detalle o renovar directamente.
5. Recepción puede usar el botón de WhatsApp para contactar al tutor (sin envío automático).

**Flujos Alternativos:**
- **FA-01:** El usuario tiene varias sucursales — el panel muestra pestañas por sucursal y permite alternar entre ellas.

**Flujos de Excepción:**
- **FE-01:** Sin entidades por vencer — el panel muestra el mensaje "Sin vencimientos próximos" y sugiere revisar el listado completo.

---

### CU-02: Renovar inscripción o paquete desde el panel

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Renovar inscripción o paquete desde el panel |
| **Actor Principal** | Recepción / Administración |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Entidad vigente o por vencer; paciente activo; tarifa vigente. |
| **Postcondiciones** | Nueva entidad creada y vinculada a la anterior; entidad origen marcada como "Renovada". |
| **Trigger** | Recepción decide renovar una entidad listada en el panel. |

**Flujo Principal:**
1. Recepción hace clic en "Renovar" sobre una entidad.
2. El sistema propone fechas del siguiente período, tarifa vigente y configuración previa.
3. Si la tarifa cambió, el sistema pide confirmación explícita.
4. Recepción confirma.
5. El sistema crea la nueva entidad, la vincula, marca la origen como "Renovada" y registra en bitácora.
6. El sistema ofrece imprimir comprobante y registrar pago.

**Flujos Alternativos:**
- **FA-01:** El usuario prefiere crear una entidad nueva sin renovación — puede hacerlo desde el formulario de alta correspondiente; la entidad anterior no se marca como renovada.
- **FA-02:** El paciente tiene varias entidades por vencer — el sistema permite renovarlas en una sola operación (RF-016).

**Flujos de Excepción:**
- **FE-01:** Paciente inactivo — el sistema bloquea la renovación y sugiere reactivar al paciente primero.
- **FE-02:** Servicio o sala inactiva — el sistema bloquea la renovación y sugiere cambiar de servicio o sala, o crear una entidad nueva.

---

### CU-03: Generar reporte de vencidos y por vencer

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Generar reporte de vencidos y por vencer |
| **Actor Principal** | Administración |
| **Actores Secundarios** | Sistema |
| **Precondiciones** | Usuario autenticado con rol Administración; existen entidades vigentes, vencidas o por vencer. |
| **Postcondiciones** | Reporte generado con filtros aplicados y exportación disponible en PDF y Excel. |
| **Trigger** | Administración necesita revisar la situación de vencimientos para planificar renovaciones o proyectar ingresos. |

**Flujo Principal:**
1. Administración abre el módulo de reportes de vencimientos.
2. Selecciona el tipo de entidad (inscripción a sala grupal o paquete de sesiones individuales).
3. Aplica filtros por sucursal, período, servicio y estado de vigencia.
4. El sistema muestra el reporte en pantalla con los datos solicitados.
5. Administración exporta el reporte a PDF o Excel.

**Flujos Alternativos:**
- **FA-01:** El reporte está vacío — el sistema muestra "Sin resultados para los filtros aplicados" y sugiere ampliar el rango.

**Flujos de Excepción:**
- **FE-01:** El usuario no tiene sucursales asignadas — el sistema muestra error y sugiere contactar al Superadmin.
- **FE-02:** El rango de fechas es inválido — el sistema valida y pide corrección.

---

## 9. Criterios de Aceptación

### CA-001: Cálculo de vencimiento
- **Given** un paquete con fecha de fin 30/11/2025,
- **When** el sistema evalúa su vigencia,
- **Then** clasifica la entidad como "Vigente" hasta el 30/11 y como "Vencido" a partir del 01/12.

### CA-002: Clasificación "Por vencer"
- **Given** una ventana de alerta de 7 días y una entidad con fecha de fin 20/11/2025,
- **When** se consulta el panel el 15/11/2025,
- **Then** la entidad aparece como "Por vencer".

### CA-003: Panel visible al iniciar sesión
- **Given** un usuario con sucursal asignada y entidades por vencer,
- **When** inicia sesión,
- **Then** el panel de alertas se muestra con las entidades de su sucursal.

### CA-004: Renovación con un clic
- **Given** una entidad por vencer con tarifa vigente,
- **When** Recepción hace clic en "Renovar" y confirma,
- **Then** el sistema crea la nueva entidad, la vincula a la anterior y marca la origen como "Renovada".

### CA-005: Confirmación por cambio de tarifa
- **Given** una entidad con tarifa anterior de Bs. 400 y tarifa vigente de Bs. 450,
- **When** Recepción intenta renovar,
- **Then** el sistema muestra la nueva tarifa y pide confirmación antes de guardar.

### CA-006: Bloqueo de asistencias en vencido
- **Given** un paquete en estado "Vencido",
- **When** Recepción intenta registrar una asistencia,
- **Then** el sistema bloquea y muestra "Debe renovar el paquete antes de registrar asistencias".

### CA-007: No traslado de sesiones
- **Given** un paquete de 10 sesiones con 3 sin consumir al vencimiento,
- **When** se renueva el paquete,
- **Then** el nuevo paquete inicia con 10 sesiones nuevas y las 3 anteriores quedan cerradas en el histórico.

### CA-008: Reporte de vencidos
- **Given** varias entidades vencidas en la sucursal,
- **When** Administración genera el reporte de vencidos,
- **Then** el sistema lista las entidades con paciente, servicio, período y estado.

### CA-009: Reporte de por vencer
- **Given** varias entidades por vencer en la ventana configurada,
- **When** Administración genera el reporte de por vencer,
- **Then** el sistema lista las entidades con la fecha de vencimiento y el saldo de sesiones.

### CA-010: Exportación de reportes
- **Given** un reporte generado,
- **When** Administración selecciona "Exportar a PDF",
- **Then** el sistema descarga el archivo con los datos y filtros aplicados.

### CA-011: Filtro por sucursal
- **Given** un usuario asignado solo a la Sucursal Norte,
- **When** consulta el panel o los reportes,
- **Then** solo ve entidades de la Sucursal Norte.

### CA-012: Bitácora de renovaciones
- **Given** una renovación ejecutada por Recepción,
- **When** Administración consulta la bitácora,
- **Then** encuentra el registro con usuario, fecha, entidad origen y entidad destino.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Cálculo erróneo de vencimientos por fechas mal cargadas | Media | Alto | Alto | Validación de fechas al crear la entidad; cálculo centralizado en backend; pruebas unitarias. |
| RT-002 | Confusión entre renovación y creación nueva | Media | Medio | Medio | Interfaz con acciones separadas y textos claros; manual breve con ejemplos. |
| RT-003 | Traslado accidental de sesiones no consumidas | Baja | Alto | Medio | Regla explícita en backend (RN-002) y pruebas automatizadas que impidan el traslado. |
| RT-004 | Panel de alertas saturado en sucursales grandes | Media | Medio | Medio | Paginación del panel, filtros por defecto y caché de cálculo de vigencia. |
| RT-005 | Baja adopción por Recepción | Media | Alto | Alto | Capacitación, soporte en sitio la primera semana y recordatorio del panel al iniciar sesión. |
| RT-006 | Cambios de alcance durante el desarrollo | Alta | Medio | Alto | Congelar alcance por etapa y aprobación formal del cliente. |
| RT-007 | Dependencia de una sola persona para soporte | Media | Alto | Alto | Documentación técnica y transferencia de conocimiento al equipo del cliente. |
| RT-008 | Desfase horario en el cálculo de vencimientos | Baja | Medio | Bajo | Usar zona horaria de Bolivia (UTC-4) de forma consistente en backend y base de datos. |

---

**Fin del documento**