**Archivo:** `01_ORGANIZACION_SUCURSALES/03_configuracion_general_parametros.md`

---

# Configuración general y parámetros
**Módulo:** Organización y sucursales
**Etapa:** 1
**Versión:** 1.0
**Fecha:** 2025-06-15
**Estado:** Borrador

---

## 1. Introducción

Este submódulo concentra los parámetros generales que gobiernan el comportamiento del sistema y que el cliente debe poder ajustar sin necesidad de cambios de código: duración de la sesión individual (por defecto 50 minutos), moneda (bolivianos), zona horaria, datos identificatorios del centro, formato de numeración de comprobantes, horario operativo y demás valores de configuración global. Su función es garantizar que el sistema sea configurable y no dependa de valores fijos dispersos en el código.

Hoy estos parámetros viven en la práctica diaria del personal (por ejemplo, la duración de la sesión se conoce “de memoria” y los formatos de comprobante se manejan por costumbre) y no existe una fuente única que los documente. Este submódulo centraliza esa configuración, la deja auditable y asegura que todos los módulos consuman el mismo valor vigente. Se integra con todos los módulos del sistema: agenda y turnos (duración de sesión y horario operativo), pagos y caja (moneda y formato de comprobante), reportes (zona horaria y período), salas y ambientes (turnos operativos) y auditoría (registro de cambios de parámetros).

El valor que aporta es la consistencia: un único lugar donde el cliente define cómo funciona el sistema, con control de acceso por rol, historial de cambios y validaciones que impiden configuraciones inválidas. Esto reduce errores operativos, facilita la incorporación de nuevas sucursales y prepara al sistema para futuras etapas sin recompilar.

---

## 2. Alcance

### Incluido en este módulo

- Administración de **parámetros globales del sistema** (clave–valor) agrupados por categoría: identidad del centro, operación, agenda, pagos, formato de documentos, zona horaria y moneda.
- Parámetros operativos clave: duración de la sesión individual (por defecto 50 minutos), horario operativo general, tolerancia de registro de asistencia, días de la semana habilitados para agendar.
- Parámetros de pagos: moneda única (bolivianos), formato y prefijo de numeración de comprobantes, datos que se imprimen en el comprobante (nombre del centro, dirección, teléfono, leyenda “NO es factura”).
- Parámetros de agenda: horas de inicio y fin de los turnos mañana y tarde, intervalo mínimo entre turnos, formato de visualización de la agenda.
- Validaciones de tipo, rango y obligatoriedad por parámetro.
- Registro en bitácora de cada cambio con valor anterior, valor nuevo, usuario, fecha y motivo.
- Restauración de un parámetro a su valor por defecto.
- Búsqueda y filtrado de parámetros por clave, categoría y estado.
- Exportación del listado de parámetros vigentes a PDF y Excel.

### Excluido de este módulo

- **Catálogo de sucursales, salas y ambientes:** corresponde a los submódulos `01_gestion_sucursales.md` y `02_gestion_salas_ambientes.md`.
- **Catálogo de servicios, tarifas y colores por área:** corresponde al módulo 05; aquí solo se referencian los parámetros de formato que esos catálogos consumen.
- **Reglas de negocio específicas de inscripción, agenda o pagos:** se documentan en los módulos 06, 07 y 08; los parámetros que las alimentan sí están incluidos aquí.
- **Plan de cuotas y reportes avanzados:** Etapa 2, según el contexto.
- **Gestión de usuarios, roles y permisos:** corresponde al módulo 02; aquí solo se valida el rol que puede editar parámetros.
- **Respaldo, restauración y monitoreo de infraestructura:** fuera del alcance funcional; se abordan como RNF.
- **Integraciones externas, facturación electrónica y app móvil:** fuera de alcance del sistema.

### Actores / Roles involucrados

| Rol | Participación en este submódulo |
|-----|---------------------------------|
| Superadmin | Consulta y edita todos los parámetros del sistema; restaura valores por defecto. |
| Administración | Consulta los parámetros vigentes; **no edita** parámetros globales (ver RN-002 y [SUPUESTO A VALIDAR CON EL CLIENTE]). |
| Recepción | Solo consulta parámetros operativos visibles (por ejemplo, duración de sesión) si la interfaz lo expone; no edita. |
| Profesionales y padres | **No usan el sistema** (según contexto). No son actores de este submódulo. |

### Sucursales aplicables

- **La mayoría de los parámetros son globales** (identidad del centro, moneda, zona horaria, duración de sesión, formatos de documentos) y aplican a todas las sucursales.
- **Algunos parámetros pueden admitir valor por sucursal** (horario operativo, horas de inicio y fin de turnos) si el cliente lo requiere. En esta etapa se asume configuración global; la variante por sucursal se marca como supuesto.
- **Pacientes y tutores son globales** y no se ven afectados por esta configuración.

> [SUPUESTO A VALIDAR CON EL CLIENTE] Se asume que en la Etapa 1 los parámetros son **globales** (una única configuración para toda la empresa). Si el cliente requiere horarios operativos distintos por sucursal, se debe definir la variante “parámetro global con override por sucursal” antes de implementar. También se asume que solo el Superadmin puede editar parámetros globales.

---

## 3. Justificación en el Negocio

### 3.1 Justificación Operativa

Recepción y Administración necesitan saber con certeza cuánto dura una sesión, cuál es el horario operativo, qué formato tiene el comprobante y en qué moneda se cobra. Al centralizar estos parámetros, se elimina la dependencia de la memoria del personal y de anotaciones en cuadernos, y se evita que módulos distintos operen con supuestos diferentes. Además, el Superadmin puede ajustar un parámetro (por ejemplo, cambiar la duración de la sesión de 50 a 45 minutos) sin asistencia técnica y con registro de quién lo hizo y por qué.

### 3.2 Justificación en la Atención al Niño y su Familia

La correcta configuración de la duración de la sesión y del horario operativo impacta directamente en la planificación de la atención: los padres reciben información consistente sobre la duración de la terapia, los turnos no se superponen y el comprobante que reciben refleja los datos reales del centro. Una configuración errónea (por ejemplo, un horario mal definido) generaría esperas y reprogramaciones que afectan la experiencia de la familia y la continuidad del tratamiento.

### 3.3 Justificación Normativa

El submódulo no introduce una norma específica sobre parámetros de configuración, pero su trazabilidad y control de acceso contribuyen al cumplimiento de:

- **Ley 548, Código Niña, Niño y Adolescente:** protección integral y resguardo de la información de menores [verificar vigencia].
- **Ley 3131, de Ejercicio Profesional Médico:** conservación de registros clínicos y secreto profesional, aplicable a los datos que el sistema almacena [verificar vigencia].
- **Ley 1080 de Ciudadanía Digital:** tratamiento y resguardo de datos personales [verificar vigencia].
- **Constitución Política del Estado:** derechos a la privacidad e intimidad [verificar vigencia y articulado aplicable].

La existencia de bitácora de cambios de parámetros (RNF-013) sostiene la trazabilidad exigible en caso de auditoría y permite demostrar qué configuración estuvo vigente en cada período.

### 3.4 Justificación Tecnológica

Frente a valores fijos en el código, la configuración parametrizada aporta: (i) flexibilidad para que el cliente ajuste el sistema sin desarrollo; (ii) una fuente única de verdad consumida por todos los módulos; (iii) validaciones de tipo y rango que impiden configuraciones inválidas; (iv) bitácora con valores anteriores y nuevos; y (v) posibilidad de restaurar valores por defecto. Todo esto reduce el riesgo de inconsistencias y facilita el mantenimiento y la evolución del sistema.

---

## 4. Funciones Principales

### FP-01: Consultar y editar parámetros globales
- **Descripción:** Permite al Superadmin ver el listado de parámetros agrupados por categoría, abrir uno, editar su valor y guardar el cambio con validación de tipo y rango. El cambio se registra en bitácora.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Parámetro actualizado y vigente en todos los módulos que lo consumen, con historial de cambios.

### FP-02: Validar y restaurar valores por defecto
- **Descripción:** Permite validar automáticamente el valor ingresado según el tipo del parámetro (entero, hora, texto, moneda) y restaurar un parámetro a su valor por defecto cuando el cliente lo requiera.
- **Actor principal:** Superadmin.
- **Resultado esperado:** Configuración consistente, sin valores inválidos, y posibilidad de revertir cambios riesgosos.

### FP-03: Consultar parámetros vigentes por otros módulos
- **Descripción:** Expone los parámetros vigentes (duración de sesión, moneda, zona horaria, formato de comprobante, horario operativo) a los módulos de agenda, pagos, caja y reportes para que operen con la misma configuración.
- **Actor principal:** Sistema (invocado por los módulos consumidores).
- **Resultado esperado:** Coherencia funcional entre todos los módulos y ausencia de valores fijos dispersos.

### FP-04: Auditar cambios de configuración
- **Descripción:** Registra y muestra en bitácora cada cambio de parámetro con valor anterior, valor nuevo, usuario, fecha, hora y motivo cuando corresponda, permitiendo su consulta posterior.
- **Actor principal:** Superadmin (consulta); Sistema (registro).
- **Resultado esperado:** Trazabilidad completa de la configuración a lo largo del tiempo.

---

## 5. Requerimientos Funcionales

| ID | Nombre | Descripción | Prioridad | Actor |
|----|--------|-------------|-----------|-------|
| RF-001 | Listar parámetros | El sistema debe mostrar el listado de parámetros agrupados por categoría, con clave, descripción, valor vigente, valor por defecto y unidad, respetando el rol del usuario. | Alta | Superadmin, Administración (solo lectura) |
| RF-002 | Buscar y filtrar parámetros | El sistema debe permitir buscar por clave o descripción y filtrar por categoría. | Alta | Superadmin, Administración |
| RF-003 | Editar parámetro | El sistema debe permitir al Superadmin editar el valor de un parámetro, validando tipo y rango, y registrando el cambio en bitácora con valores anteriores y nuevos. | Alta | Superadmin |
| RF-004 | Validar tipo y rango | El sistema debe validar que el valor ingresado coincida con el tipo declarado (entero, decimal, hora, texto, booleano) y esté dentro del rango permitido; si no, rechaza el guardado con mensaje claro. | Alta | Sistema |
| RF-005 | Restaurar valor por defecto | El sistema debe permitir restaurar un parámetro a su valor por defecto, registrando la acción en bitácora. | Media | Superadmin |
| RF-006 | Registrar duración de sesión | El sistema debe permitir configurar la duración de la sesión individual (por defecto 50 minutos), entre un mínimo y un máximo validados con el cliente, y aplicarla a todos los turnos individuales. | Alta | Superadmin |
| RF-007 | Configurar horario operativo y turnos | El sistema debe permitir configurar el horario operativo general y las horas de inicio y fin de los turnos mañana y tarde. | Alta | Superadmin |
| RF-008 | Configurar moneda y formato de comprobante | El sistema debe permitir configurar la moneda (bolivianos, fija en esta etapa), el prefijo y la numeración del comprobante y los datos que se imprimen en él. | Alta | Superadmin |
| RF-009 | Configurar identidad del centro | El sistema debe permitir configurar nombre del centro, dirección, teléfonos y logo que aparecen en documentos y pantallas. | Media | Superadmin |
| RF-010 | Configurar zona horaria | El sistema debe permitir seleccionar la zona horaria del sistema (por defecto la de Bolivia) y aplicarla a fechas y reportes. | Alta | Superadmin |
| RF-011 | Validar coherencia entre parámetros | El sistema debe rechazar combinaciones inválidas (por ejemplo, hora de fin de turno anterior a la hora de inicio, o duración de sesión mayor que el bloque de agenda). | Alta | Sistema |
| RF-012 | Exponer parámetros a otros módulos | El sistema debe exponer los parámetros vigentes vía API para que agenda, pagos, caja y reportes los consuman. | Alta | Sistema |
| RF-013 | Registrar bitácora de cambios | El sistema debe registrar en bitácora cada modificación o restauración de parámetro con usuario, fecha, hora, valor anterior, valor nuevo y motivo. | Alta | Sistema |
| RF-014 | Restringir edición por rol | El sistema debe impedir que Administración y Recepción editen parámetros globales; solo el Superadmin puede hacerlo. | Alta | Sistema |
| RF-015 | Exportar parámetros vigentes | El sistema debe permitir exportar el listado de parámetros vigentes a PDF y Excel. | Baja | Superadmin, Administración |
| RF-016 | Alertar cambios sensibles | El sistema debe solicitar confirmación explícita y motivo cuando se modifiquen parámetros marcados como sensibles (duración de sesión, formato de comprobante, zona horaria). | Media | Sistema |

> [SUPUESTO A VALIDAR CON EL CLIENTE] Los rangos válidos de duración de sesión (por ejemplo, entre 30 y 90 minutos) y las horas de inicio y fin de los turnos mañana y tarde deben confirmarse con el cliente. Asimismo, la lista definitiva de parámetros y sus valores por defecto debe validarse antes de implementar.

---

## 6. Requerimientos No Funcionales

### 6.1 Rendimiento

- **RNF-001:** El listado de parámetros debe cargar en menos de 1 segundo con hasta 200 parámetros configurados.
- **RNF-002:** La lectura de parámetros por parte de otros módulos debe resolverse en menos de 100 ms (cacheada) para no afectar la agenda ni la caja.

### 6.2 Seguridad y Privacidad

- **RNF-003:** Todo el tráfico debe viajar por HTTPS y las operaciones de escritura exigir autenticación con rol Superadmin.
- **RNF-004:** Los parámetros sensibles (por ejemplo, formato de comprobante) deben validarse en el backend, no solo en la interfaz.
- **RNF-005:** Los valores de configuración no deben incluir datos personales de menores ni de tutores; si en el futuro se incorporara algún dato sensible, se aplicará la política de privacidad.

### 6.3 Disponibilidad

- **RNF-006:** El módulo debe estar disponible en el horario operativo del centro, con un objetivo de disponibilidad del 99,5 % mensual.
- **RNF-007:** Los respaldos diarios de la base de datos deben incluir la tabla de parámetros y sus históricos.

### 6.4 Usabilidad

- **RNF-008:** La interfaz debe presentar los parámetros agrupados por categoría con descripción en español y valor por defecto visible, y permitir editar en pocos clics.
- **RNF-009:** La interfaz debe ser usable en computadora y tablet por personal sin formación técnica.

### 6.5 Escalabilidad

- **RNF-010:** El modelo clave–valor debe permitir agregar nuevos parámetros sin cambios de esquema; si en el futuro se requiere override por sucursal, debe poder incorporarse sin rediseño.

### 6.6 Interoperabilidad

- **RNF-011:** El módulo debe exponer los parámetros vía API REST y exportarlos a PDF y Excel; no se contemplan integraciones externas en esta etapa.

### 6.7 Mantenibilidad

- **RNF-012:** Los parámetros deben definirse en un catálogo versionado (semilla) que permita agregar o renombrar claves con migraciones controladas.

### 6.8 Trazabilidad y Auditoría

- **RNF-013:** Todo cambio de parámetro debe quedar en bitácora con usuario, fecha, hora, valor anterior, valor nuevo y motivo; los registros no se borran. Se debe poder reconstruir la configuración vigente en una fecha dada.

---

## 7. Reglas de Negocio

- **RN-001:** Fuente única de verdad — Toda configuración global del sistema proviene de este submódulo; no se admiten valores fijos dispersos en el código para parámetros administrables.
- **RN-002:** Edición exclusiva del Superadmin — Solo el Superadmin puede modificar parámetros globales; Administración y Recepción solo consultan.
- **RN-003:** Moneda única — La moneda del sistema es el boliviano; no se admite configuración multimoneda en esta etapa.
- **RN-004:** Duración de sesión — La duración de la sesión individual es configurable (por defecto 50 minutos) y se aplica a todos los turnos individuales nuevos; los turnos ya programados conservan la duración con la que fueron creados. [SUPUESTO A VALIDAR CON EL CLIENTE]
- **RN-005:** Horario operativo coherente — La hora de fin de un turno debe ser posterior a su hora de inicio; el sistema rechaza configuraciones incoherentes.
- **RN-006:** Parámetros sensibles — Los parámetros marcados como sensibles (duración de sesión, formato de comprobante, zona horaria) requieren confirmación y motivo al modificarse.
- **RN-007:** Trazabilidad de configuración — Todo cambio de parámetro queda en bitácora con valores anteriores y nuevos; los registros no se eliminan.
- **RN-008:** Restauración auditada — Restaurar un parámetro a su valor por defecto es un cambio más y se registra con el mismo nivel de detalle.
- **RN-009:** Independencia de sucursal en Etapa 1 — En esta etapa los parámetros son globales; la variante por sucursal queda fuera de alcance salvo confirmación del cliente.
- **RN-010:** Formato de comprobante — El comprobante no es factura; debe imprimir siempre la leyenda “NO es factura” y los datos configurados del centro.
- **RN-011:** Zona horaria — El sistema opera con la zona horaria configurada (por defecto Bolivia) y todas las fechas y reportes la respetan.

> [SUPUESTO A VALIDAR CON EL CLIENTE] La RN-004 (los turnos existentes conservan la duración con la que fueron creados) debe confirmarse: la alternativa sería recalcular todos los turnos futuros al cambiar la duración. También debe confirmarse la lista de parámetros “sensibles”.

---

## 8. Casos de Uso Principales

### CU-01: Editar un parámetro global

| Campo | Detalle |
|-------|---------|
| **ID** | CU-01 |
| **Nombre** | Editar un parámetro global |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; parámetro existente en el catálogo |
| **Postcondiciones** | Parámetro actualizado, vigente en todos los módulos, con registro en bitácora |
| **Trigger** | El Superadmin selecciona un parámetro y elige “Editar” |

**Flujo Principal:**
1. El Superadmin accede al listado de parámetros y busca el parámetro deseado.
2. El sistema muestra la ficha del parámetro con clave, descripción, tipo, valor vigente, valor por defecto y unidad.
3. El Superadmin selecciona “Editar”.
4. El sistema habilita el campo de valor respetando el tipo.
5. El Superadmin ingresa el nuevo valor.
6. El sistema valida tipo, rango y coherencia con otros parámetros.
7. Si el parámetro es sensible, el sistema solicita confirmación y motivo.
8. El sistema guarda el cambio y registra en bitácora el valor anterior y el nuevo.
9. El sistema confirma la actualización y refleja el nuevo valor en la ficha.

**Flujos Alternativos:**
- **FA-01:** El Superadmin cancela la edición; el sistema descarta los cambios y mantiene el valor vigente.

**Flujos de Excepción:**
- **FE-01:** Valor fuera de rango o de tipo incorrecto → el sistema rechaza el guardado y muestra el error.
- **FE-02:** Combinación incoherente con otro parámetro (por ejemplo, hora fin < hora inicio) → el sistema rechaza y explica la causa.
- **FE-03:** Otro usuario modificó el parámetro durante la edición → el sistema advierte el conflicto y solicita recargar.

### CU-02: Restaurar parámetro a su valor por defecto

| Campo | Detalle |
|-------|---------|
| **ID** | CU-02 |
| **Nombre** | Restaurar parámetro a su valor por defecto |
| **Actor Principal** | Superadmin |
| **Actores Secundarios** | Sistema (validaciones), módulo de auditoría |
| **Precondiciones** | Sesión iniciada con rol Superadmin; parámetro existente cuyo valor actual difiere del valor por defecto |
| **Postcondiciones** | Parámetro con su valor por defecto vigente, registrado en bitácora |
| **Trigger** | El Superadmin selecciona “Restaurar valor por defecto” en la ficha del parámetro |

**Flujo Principal:**
1. El Superadmin abre la ficha del parámetro.
2. El sistema muestra el valor vigente y el valor por defecto.
3. El Superadmin selecciona “Restaurar valor por defecto”.
4. El sistema solicita confirmación y, si el parámetro es sensible, el motivo.
5. El Superadmin confirma.
6. El sistema aplica el valor por defecto y registra en bitácora el cambio.
7. El sistema confirma la restauración.

**Flujos Alternativos:**
- **FA-01:** El valor vigente ya es igual al valor por defecto; el sistema deshabilita la acción.

**Flujos de Excepción:**
- **FE-01:** El Superadmin no confirma → el sistema no aplica cambios.

### CU-03: Consultar parámetros vigentes desde otro módulo (integración)

| Campo | Detalle |
|-------|---------|
| **ID** | CU-03 |
| **Nombre** | Consultar parámetros vigentes desde otro módulo |
| **Actor Principal** | Sistema (módulos consumidores) |
| **Actores Secundarios** | — |
| **Precondiciones** | Parámetros configurados y servicio de consulta disponible |
| **Postcondiciones** | El módulo consumidor obtiene los valores vigentes y opera en consecuencia |
| **Trigger** | Llamada del módulo de agenda, pagos, caja o reportes al servicio de parámetros |

**Flujo Principal:**
1. Un módulo consumidor solicita uno o varios parámetros por clave.
2. El servicio de parámetros valida que la clave exista y devuelve el valor vigente, la unidad y la fecha de última modificación.
3. El módulo consumidor aplica el valor en su operación (por ejemplo, genera el turno con la duración configurada).

**Flujos Alternativos:**
- **FA-01:** El módulo consumidor puede solicitar todos los parámetros de una categoría.

**Flujos de Excepción:**
- **FE-01:** La clave solicitada no existe → el servicio devuelve error controlado y el módulo consumidor aplica el valor por defecto documentado, registrando una alerta técnica.

---

## 9. Criterios de Aceptación

### CA-001: Edición exitosa de parámetro
- **Given** un Superadmin autenticado y el parámetro “duración de sesión” con valor 50 minutos,
- **When** lo cambia a 45 minutos y guarda,
- **Then** el sistema actualiza el valor, registra en bitácora el valor anterior (50) y el nuevo (45) con usuario y fecha, y la agenda usa 45 en los nuevos turnos.

### CA-002: Rechazo por valor fuera de rango
- **Given** el parámetro “duración de sesión” con rango válido de 30 a 90 minutos,
- **When** el Superadmin ingresa 20,
- **Then** el sistema rechaza el guardado y muestra el rango permitido.

### CA-003: Rechazo por incoherencia entre parámetros
- **Given** el turno “Mañana” con hora de inicio 08:00,
- **When** el Superadmin configura la hora de fin a las 07:30,
- **Then** el sistema rechaza el cambio e informa la incoherencia.

### CA-004: Confirmación obligatoria en parámetro sensible
- **Given** el parámetro “formato de comprobante” marcado como sensible,
- **When** el Superadmin intenta modificarlo,
- **Then** el sistema exige confirmación y motivo antes de guardar.

### CA-005: Restauración a valor por defecto con bitácora
- **Given** el parámetro “zona horaria” modificado a otra zona,
- **When** el Superadmin elige “Restaurar valor por defecto” y confirma,
- **Then** el sistema aplica la zona de Bolivia y registra la acción en bitácora.

### CA-006: Restricción de edición por rol
- **Given** un usuario de Administración autenticado,
- **When** intenta editar un parámetro global,
- **Then** el sistema no muestra la acción y, si se invoca por API, responde con error de permisos.

### CA-007: Consumo del parámetro por otro módulo
- **Given** el parámetro “duración de sesión” en 50 minutos,
- **When** se programa un nuevo turno individual,
- **Then** el turno se genera con 50 minutos y refleja el valor vigente.

### CA-008: Validación de tipo
- **Given** un parámetro de tipo entero,
- **When** el Superadmin ingresa texto,
- **Then** el sistema rechaza el guardado e informa el tipo esperado.

### CA-009: Exportación del listado
- **Given** un Superadmin autenticado,
- **When** exporta el listado de parámetros vigentes,
- **Then** el sistema genera el archivo PDF y Excel con los valores actuales y la fecha de exportación.

### CA-010: Comprobante con leyenda “NO es factura”
- **Given** el formato de comprobante configurado,
- **When** se imprime un comprobante de pago,
- **Then** el documento incluye la leyenda “NO es factura” y los datos del centro configurados.

---

## 10. Riesgos Técnicos

| ID | Riesgo | Probabilidad | Impacto | Nivel | Plan de Mitigación |
|----|--------|-------------|---------|-------|-------------------|
| RT-001 | Cambio accidental de parámetros sensibles (duración de sesión, formato de comprobante, zona horaria) que afecte la operación | Media | Alto | Alto | Confirmación explícita, motivo obligatorio y bitácora con valores anteriores y nuevos. |
| RT-002 | Valores fijos en el código que no respeten los parámetros configurados | Alta | Alto | Alto | Revisión de código, pruebas de integración con cada módulo consumidor y checklist de parámetros. |
| RT-003 | Ambigüedad sobre qué parámetros son globales y cuáles por sucursal | Alta | Medio | Alto | Validar con el cliente antes de implementar y documentar el supuesto de Etapa 1 (globales). |
| RT-004 | Configuración incoherente entre hora de inicio/fin y duración de sesión | Media | Medio | Medio | Validaciones cruzadas (RF-011) y pruebas de aceptación CA-003. |
| RT-005 | Falta de claridad del cliente sobre el valor correcto de cada parámetro al migrar desde cuadernos | Media | Medio | Medio | Taller de parametrización inicial con el cliente y acta de valores por defecto. |
| RT-006 | Dependencia de una sola persona para administrar la configuración | Media | Alto | Alto | Documentar el catálogo de parámetros, sus rangos y valores por defecto; transferencia de conocimiento. |
| RT-007 | Exposición de configuración sensible a usuarios sin permiso | Baja | Medio | Bajo | Control de acceso por rol en backend y pruebas de seguridad. |
| RT-008 | Degradación del rendimiento por consultas frecuentes a parámetros desde la agenda | Baja | Medio | Bajo | Cacheo de parámetros por categoría con invalidación al cambiar y pruebas de carga. |

---

**Fin del documento**