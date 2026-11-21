# TalitaCumi — Backend

API del **Sistema de Gestión TalitaCumi** (Laravel 13 + PostgreSQL 16 + Sanctum + Spatie Permission), ejecutada en Docker.

La documentación funcional vive en [`../docs/`](../docs/). El esquema de base de datos de referencia está en [`database/database.md`](database/database.md) y [`database/talitacumi_schema.sql`](database/talitacumi_schema.sql).

---

## Ejecución

```bash
cd /home/grilloportatil/Programing/TalitaCumi
docker compose up -d --build
```

| Servicio | URL |
|---|---|
| API a través de Nginx | `http://localhost:8080/api` |
| Healthcheck API + BD | `http://localhost:8080/api/health` |
| PostgreSQL desde el host | `localhost:5433` |

Comandos útiles:

```bash
make migrate
make seed
make shell-backend
```

Las migraciones corren automáticamente al levantar el contenedor `backend`.

### Tests

El volumen `vendor` de la imagen de desarrollo se construye con `--no-dev`; para ejecutar la suite hay que instalar las dependencias de desarrollo en el contenedor:

```bash
docker compose exec backend composer install
docker compose exec backend php artisan test
```

La suite usa SQLite en memoria por defecto (`phpunit.xml`) y está aislada de la base de desarrollo: las variables de test se declaran tanto en `$_ENV` como en `$_SERVER` para que no las pise el entorno del contenedor.

Para verificar PostgreSQL real se usa una base de test separada:

```bash
docker compose exec db psql -U talitacumi -d postgres -c 'CREATE DATABASE talitacumi_test;'
docker compose exec backend php artisan test --configuration=phpunit.pgsql.xml
docker compose exec db psql -U talitacumi -d postgres -c 'DROP DATABASE talitacumi_test;'
```

`phpunit.pgsql.xml` apunta a `talitacumi_test` y toma host, usuario y contraseña del entorno del contenedor. La suite por defecto nunca toca la base `talitacumi`.

---

## Convención de módulos de negocio

Los módulos viven en `app/Modules/<Dominio>/`. El autoload PSR-4 `App\` ya cubre `app/Modules/...`; no hace falta modificar `composer.json`. Cada módulo registra su `ServiceProvider` en `bootstrap/providers.php`.

```text
app/Modules/Organizacion/
├── Actions/        Casos de uso invocables. Aquí vive DB::transaction().
├── Queries/        Lecturas paginadas, fichas y datos de exportación.
├── Models/         Eloquent del dominio.
├── Rules/          Reglas de negocio puras (sin DB, Request ni facades).
├── Events/         Eventos de dominio con ids, snapshots y actor.
├── Listeners/      Efectos secundarios (bitácora). Nunca invocan Actions.
├── Contracts/      Interfaces para otros módulos.
├── Exceptions/     Excepciones de dominio con render HTTP.
├── Enums/          Enums de dominio (por ejemplo, UsoAmbiente).
├── Http/
│   ├── Controllers/  Delgados: Request -> Action | Query -> Resource.
│   ├── Requests/     Validación de formato y autorización por campo.
│   └── Resources/    JsonResource de salida.
├── Providers/      Bindings, listeners y carga de rutas.
├── routes.php                    Sucursales (01)
├── routes-salas-ambientes.php    Salas y ambientes (02)
└── routes-configuracion.php      Configuración (03)
```

Reglas de dependencia:

- Controller → Action o Query. Nunca usa Models directamente.
- Action → Models + Rules + Events + Contracts. Puede invocar otra Action del mismo módulo.
- Query → Models + Contracts de lectura. Nunca invoca Actions.
- Rule → datos puros.
- Listener → solo efectos secundarios; si necesita algo complejo, despacha un Job.
- Cross-módulo: solo a través de `Contracts/`.

Transacciones: `DB::transaction()` dentro de la Action, incluyendo la mutación y el dispatch de eventos. La bitácora se escribe en la misma transacción mediante un listener síncrono.

---

## Autenticación y permisos

- **Sanctum** está instalado y los endpoints usan `auth:sanctum`.
- **Login real implementado en la Fase 1 del módulo 02**: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me` y `POST /api/auth/cambiar-password`. Ver la sección “Módulo 02”.
- **Spatie Permission** es el mecanismo de permisos. Los roles base (`superadmin`, `administracion`, `recepcion`) se crean en `DatabaseSeeder`; cada submódulo crea sus permisos en su propio seeder (`OrganizacionSucursalesPermissionSeeder`, `OrganizacionSalasAmbientesPermissionSeeder`, `OrganizacionConfiguracionPermissionSeeder`, `SeguridadPermissionSeeder`).
- El código de aplicación **nunca decide por nombre de rol**: decide por permisos. El único dato que identifica al rol protegido es `roles.es_superadmin`, usado por la regla del último Superadmin.
- Las aliases de middleware de Spatie no están registradas en `bootstrap/app.php`; se usa directamente `Spatie\Permission\Middleware\PermissionMiddleware::using(..., 'sanctum')`.
- `EnsureSessionIsValid` se ejecuta después de `auth:sanctum` en todo el grupo `api`: valida usuario activo, inactividad, expiración y cambio forzado de contraseña, y actualiza `last_used_at`.

### Permisos del módulo de sucursales

| Código | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `sucursal.ver` | sí | sí | sí |
| `sucursal.crear` | sí | no | no |
| `sucursal.editar` (código/nombre) | sí | no | no |
| `sucursal.editar_contacto` (dirección/teléfono/email) | sí | sí | no |
| `sucursal.cambiar_estado` (activar/desactivar) | sí | no | no |
| `sucursal.alcance_global` (ver todas) | sí | no | no |

> La asignación usuario–sucursal vive en `usuario_sucursal` y su implementación real es `EloquentUserBranchAccess`, activa desde la Fase 2. El contrato es fail-closed: un usuario sin sucursales asignadas y sin `sucursal.alcance_global` no opera; nunca se devuelven “todas las sucursales” como atajo.

---

## Módulo 01 — Organización y sucursales

Constante pública: `App\Modules\Organizacion\Support\OrganizacionModule::MODULE_URL = '/organizacion/sucursales'`.

### Endpoints

Prefijo real: `/api/organizacion/sucursales`.

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/api/organizacion/sucursales` | `ListBranchesQuery` | `sucursal.ver` |
| GET | `/api/organizacion/sucursales/{id}` | `GetBranchQuery` | `sucursal.ver` |
| POST | `/api/organizacion/sucursales` | `CreateBranchAction` | `sucursal.crear` |
| PATCH | `/api/organizacion/sucursales/{id}` | `UpdateBranchAction` | `sucursal.editar` o `sucursal.editar_contacto` según campos |
| POST | `/api/organizacion/sucursales/{id}/activar` | `ActivateBranchAction` | `sucursal.cambiar_estado` |
| POST | `/api/organizacion/sucursales/{id}/desactivar` | `DeactivateBranchAction` | `sucursal.cambiar_estado` |
| DELETE | `/api/organizacion/sucursales/{id}` | `RejectBranchDeletionAction` | `sucursal.cambiar_estado` |

El `DELETE` **siempre responde `409 BRANCH_DELETE_NOT_ALLOWED`**: no se elimina físicamente ninguna sucursal; se ofrece desactivarla. El chequeo real de datos asociados se agregará cuando existan los módulos de salas, inscripciones, turnos, pagos y caja.

Parámetros del listado: `search`, `estado` (`activa|inactiva`), `page`, `per_page` (máx. 50), `sort` (`codigo|nombre|activo|updated_at`), `direction` (`asc|desc`).

La respuesta incluye `data`, `links`, `meta` y `can` (`create`, `update`, `update_contact`, `change_status`, `delete`). La UI usa `can` solo para ocultar acciones; el backend siempre revalida.

### Eventos y bitácora

| Evento | Acción en bitácora | Datos |
|---|---|---|
| `BranchCreated` | `CREAR` | snapshot nuevo |
| `BranchUpdated` | `ACTUALIZAR` | snapshot anterior/nuevo y campos cambiados |
| `BranchActivated` | `ACTIVAR` | snapshots y motivo |
| `BranchDeactivated` | `DESACTIVAR` | snapshots y motivo |

`RecordBranchAudit` escribe en `bitacora` (`usuario_id`, `sucursal_id`, `entidad`, `entidad_id`, `datos_anteriores`, `datos_nuevos`, `motivo`). La tabla es solo inserción y en PostgreSQL tiene un trigger `BEFORE UPDATE OR DELETE` que la hace inmutable.

> El módulo 02 mantiene **`users` como tabla física** de autenticación y agrega los campos de negocio. La FK `bitacora.usuario_id` sigue apuntando a `users.id`; no se migra a una tabla `usuario` inexistente.

### Contratos expuestos

**`BranchDirectoryContract`** — implementado por `EloquentBranchDirectory`:

```php
public function existe(int $sucursalId): bool;
public function estaActiva(int $sucursalId): bool;
public function asegurarActiva(int $sucursalId): void; // InactiveBranchException / BranchNotFoundException
```

Los módulos de inscripciones, turnos, pagos y caja deben llamar a `asegurarActiva()` antes de operar (RN-003).

**`UserBranchAccessContract`** — implementación real desde Fase 2:

```php
public function accesibles(int $usuarioId): array;      // list<int>
public function puedeAcceder(int $usuarioId, int $sucursalId): bool;
```

`EloquentUserBranchAccess` consulta `usuario_sucursal` y `sucursal.activo`; con `sucursal.alcance_global` devuelve todas las sucursales activas. Usuario inexistente, inactivo o error de lectura → lista vacía (fail-closed). Ya no existe `StubUserBranchAccessContract` ni `ModulePendingException`.

### Estado del módulo

| Cobertura | Estado |
|---|---|
| RF-001, RF-002, RF-003, RF-007, RF-011, RF-013 | Implementados |
| RF-004, RF-005, RF-006, RF-014 | Implementados; alcance real por sucursal desde Fase 2 |
| RF-008 | Rechazo explícito de eliminación; chequeo real de asociaciones pendiente |
| RF-009, RF-010 | Implementados: contrato real de alcance + directorio de usuarios (Fase 2) |
| RF-012 (PDF/Excel) | Pendiente; no hay librerías instaladas y el botón no se muestra |
| RN-001, RN-002, RN-003, RN-005, RN-009 | Implementadas |
| RN-004 | Parcial: no hay borrado físico; falta contrato de uso |
| RN-006 | Parcial: permisos + contrato stub |
| RN-007, RN-008 | Estructurales; módulos consumidores pendientes |

### Migraciones

- `2026_11_19_210000_create_sucursal_table.php`: `codigo`, `nombre`, datos de contacto, `activo`, `version`, timestamps e índices únicos funcionales.
- `2026_11_19_210100_create_bitacora_table.php`: bitácora, FKs, índices y trigger de inmutabilidad en PostgreSQL.

El esquema de referencia se mantiene sincronizado en `database/database.md` y `database/talitacumi_schema.sql`.

---

## Módulo 01.02 — Salas y ambientes

Vive dentro del mismo `OrganizacionServiceProvider`. Usa prefijos de entidad (`TipoSala*`, `TurnoCatalogo*`, `Sala*`, `Ambiente*`) para mantener el patrón plano de sucursales, y carga sus rutas desde `routes-salas-ambientes.php`.

Constante pública: `App\Modules\Organizacion\Support\SalasAmbientesModule::MODULE_URL = '/organizacion/salas-ambientes'`.

### Endpoints

Prefijo real: `/api/organizacion/salas-ambientes`.

| Entidad | Endpoints |
|---|---|
| Áreas | `GET /areas` (solo lectura; el CRUD y colores pertenecen al módulo 05) |
| Tipos de sala | `GET /tipos-sala`, `GET /tipos-sala/{id}`, `POST /tipos-sala`, `PATCH /tipos-sala/{id}`, `POST /tipos-sala/{id}/activar`, `POST /tipos-sala/{id}/desactivar`, `DELETE /tipos-sala/{id}` → `409` |
| Turnos operativos | `GET /turnos-catalogo`, `GET /turnos-catalogo/{id}`, `POST /turnos-catalogo`, `PATCH /turnos-catalogo/{id}`, `POST /turnos-catalogo/{id}/activar`, `POST /turnos-catalogo/{id}/desactivar`, `DELETE /turnos-catalogo/{id}` → `409` |
| Salas | `GET /salas`, `GET /salas/{id}`, `POST /salas`, `PATCH /salas/{id}`, `POST /salas/{id}/activar`, `POST /salas/{id}/desactivar`, `DELETE /salas/{id}` → `409` |
| Ambientes | `GET /ambientes`, `GET /ambientes/{id}`, `POST /ambientes`, `PATCH /ambientes/{id}`, `POST /ambientes/{id}/activar`, `POST /ambientes/{id}/desactivar`, `DELETE /ambientes/{id}` → `409` |

`DELETE` siempre responde `409` (`SPACE_DELETE_NOT_ALLOWED` o `CATALOG_DELETE_NOT_ALLOWED`). No hay borrado físico; se ofrece desactivar.

Filtros de salas: `search`, `sucursal_id`, `tipo_sala_id`, `turno_catalogo_id`, `estado`, `page`, `per_page` (máx. 50), `sort`, `direction`.
Filtros de ambientes: `search`, `sucursal_id`, `uso`, `estado`, `page`, `per_page`, `sort`, `direction`.

### Permisos

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `catalogo.ver` | sí | sí | sí |
| `tipo_sala.administrar` | sí | no | no |
| `turno.administrar` | sí | no | no |
| `sala.ver` | sí | sí | sí |
| `sala.crear` | sí | no | no |
| `sala.editar_estructura` (sucursal, área, tipo, turno) | sí | no | no |
| `sala.editar_datos` (nombre, observaciones) | sí | sí | no |
| `sala.cambiar_estado` | sí | no | no |
| `ambiente.ver` | sí | sí | sí |
| `ambiente.crear` | sí | no | no |
| `ambiente.editar_estructura` (sucursal, uso) | sí | no | no |
| `ambiente.editar_datos` (nombre, observaciones) | sí | sí | no |
| `ambiente.cambiar_estado` | sí | no | no |
| `espacio.exportar` | sí | sí | no |

Seeder: `OrganizacionSalasAmbientesPermissionSeeder`. Los catálogos iniciales de áreas, tipos y turnos se cargan con `OrganizacionSalasAmbientesCatalogSeeder`; ambos se invocan desde `DatabaseSeeder`.

El alcance por sucursal reutiliza `UserBranchAccessContract` y `sucursal.alcance_global`. Sin alcance global, las listas se filtran por las sucursales asignadas y las operaciones sobre otra sucursal responden `403`; nunca se devuelven todas las sucursales como atajo.

### Eventos y bitácora

| Entidad (`bitacora.entidad`) | Eventos |
|---|---|
| `tipo_sala` | `TipoSalaCreated`, `TipoSalaUpdated`, `TipoSalaStatusChanged` |
| `turno_catalogo` | `TurnoCatalogoCreated`, `TurnoCatalogoUpdated`, `TurnoCatalogoStatusChanged` |
| `sala` | `SalaCreated`, `SalaUpdated`, `SalaStatusChanged` |
| `ambiente` | `AmbienteCreated`, `AmbienteUpdated`, `AmbienteStatusChanged` |

`RecordSalasAmbientesAudit` escribe `CREAR`, `ACTUALIZAR`, `ACTIVAR` o `DESACTIVAR`, con snapshots y `motivo` solo en la desactivación. Los catálogos globales guardan `sucursal_id = NULL`; salas y ambientes guardan su sucursal. La tabla `bitacora` y su trigger de inmutabilidad no cambian.

### Contratos expuestos

**`SalaDirectoryContract`** — implementado por `EloquentSalaDirectory`:

```php
public function existe(int $salaId): bool;
public function estaActiva(int $salaId): bool;
public function perteneceASucursal(int $salaId, int $sucursalId): bool;
public function asegurarActivaEnSucursal(int $salaId, int $sucursalId): void;
```

**`AmbienteDirectoryContract`** — implementado por `EloquentAmbienteDirectory`:

```php
public function existe(int $ambienteId): bool;
public function estaActiva(int $ambienteId): bool;
public function perteneceASucursal(int $ambienteId, int $sucursalId): bool;
public function asegurarActivaEnSucursal(int $ambienteId, int $sucursalId): void;
```

Los módulos 06 (Inscripciones a salas grupales) y 07 (Agenda y turnos) deben llamar a `asegurarActivaEnSucursal()` antes de operar (RN-005/RN-006). **TODO módulo 07:** la tabla `turno` actual solo tiene `sala_id`; al implementar terapias individuales habrá que agregar `ambiente_id` y consumir `AmbienteDirectoryContract`. No se modificó `turno` en este submódulo.

### Estado del submódulo

| Cobertura | Estado |
|---|---|
| RF-001, RF-002, RF-003, RF-004, RF-005, RF-006, RF-007, RF-011, RF-015 | Implementados |
| RF-008, RF-009, RF-010, RF-017 | Implementados; alcance real por sucursal desde Fase 2 |
| RF-012 | Rechazo explícito de eliminación; chequeo real de asociaciones pendiente |
| RF-013, RF-014 | Contratos implementados; consumidores 06/07 pendientes |
| RF-016 (PDF/Excel) | Pendiente; sin librerías y sin botón |
| RN-001, RN-002, RN-003, RN-004, RN-008, RN-010 | Implementadas |
| RN-005, RN-006 | Contratos implementados; consumidores pendientes |
| RN-007 | Parcial: no hay borrado físico; falta chequeo de asociaciones |
| RN-009 | No se implementa aquí; solo se referencia `area` sin administrar colores |
| RN-011 | Implementada: permiso + alcance real por sucursal (Fase 2) |

### Migraciones

- `2026_11_20_000000_create_area_table.php`: catálogo de áreas de solo lectura, prerequisito de `sala.area_id`.
- `2026_11_20_000100_create_tipo_sala_table.php`.
- `2026_11_20_000200_create_turno_catalogo_table.php` (`turno_catalogo` evita colisión con `turno` de agenda).
- `2026_11_20_000300_create_sala_table.php`: FKs a sucursal/área/tipo/turno, `observaciones`, `version` y unique `(sucursal_id, tipo_sala_id, turno_catalogo_id)`.
- `2026_11_20_000400_create_ambiente_table.php`: `uso` con CHECK, `version` y unique funcional por sucursal + nombre.

El esquema de referencia se mantiene sincronizado en `database/database.md` y `database/talitacumi_schema.sql`.

---

## Módulo 01.03 — Configuración general y parámetros

Vive dentro del mismo `OrganizacionServiceProvider`, con prefijos `Parameter*`/`Parametro*` y rutas en `routes-configuracion.php`. Los parámetros son **globales**: no usan `UserBranchAccessContract` ni alcance por sucursal; el único control es por permiso.

Constante pública: `App\Modules\Organizacion\Support\ConfiguracionModule::MODULE_URL = '/organizacion/configuracion'`.

### Endpoints

Prefijo real: `/api/organizacion/configuracion`.

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/parametros` | `ListParametersQuery` | `parametro.ver` o `parametro.ver_operativos` |
| GET | `/parametros/{id}` | `GetParameterQuery` | `parametro.ver` o `parametro.ver_operativos` (con filtro de visibilidad) |
| PATCH | `/parametros/{id}` | `UpdateParameterAction` | `parametro.editar` |
| POST | `/parametros/{id}/restaurar` | `RestoreParameterDefaultAction` | `parametro.restaurar` |
| GET | `/parametros/{id}/historial` | `GetParameterHistoryQuery` | `parametro.ver` |
| POST | `/logo` | `UploadCenterLogoAction` | `parametro.editar` |

No existen `POST /parametros`, `DELETE`, activación/desactivación ni exportación. El catálogo se define por seeder/migración versionados (RNF-012). Recepción solo recibe parámetros con `visible_recepcion = true`; la ficha de uno no visible responde `403 PARAMETRO_ACCESS_DENIED`.

Payload de edición:

```json
{
  "valor": "45",
  "version": 1,
  "confirmado": true,
  "motivo": "Ajuste operativo aprobado por dirección"
}
```

- `409 PARAMETRO_VERSION_CONFLICT` si `version` no coincide.
- `422` por tipo/rango/coherencia, por falta de `confirmado`/`motivo` en sensibles, o por intentar editar un parámetro de solo lectura.
- `404 PARAMETRO_NOT_FOUND` para ID o clave inexistente.
- La restauración con el valor ya vigente responde `409 PARAMETRO_YA_EN_DEFECTO` (la UI deshabilita la acción).

### Permisos

Seeder: `OrganizacionConfiguracionPermissionSeeder`.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `parametro.ver` | sí | sí | no |
| `parametro.ver_operativos` | no | no | sí |
| `parametro.editar` | sí | no | no |
| `parametro.restaurar` | sí | no | no |
| `parametro.exportar` (reservado RF-015) | sí | sí | no |

La API devuelve `can` (`view`, `view_all`, `view_operatives`, `update`, `update_logo`, `restore`, `export`). `export` siempre es `false` hasta implementar RF-015.

### Eventos y bitácora

| Evento | Acción en bitácora | Datos |
|---|---|---|
| `ParameterCreated` | `CREAR` | snapshot inicial; `usuario_id = NULL` cuando lo emite el seeder |
| `ParameterUpdated` | `EDITAR` | snapshots anterior/nuevo, usuario y motivo |
| `ParameterRestored` | `RESTAURAR` | snapshots anterior/nuevo, usuario y motivo |

`RecordParameterAudit` escribe `entidad = 'parametro'`, `sucursal_id = NULL` y snapshots completos (clave, categoría, tipo, valor, valor por defecto, unidad, límites, banderas, versión y `updated_at`). `bitacora` sigue siendo inmutable. El seeder deja un `CREAR` por cada parámetro nuevo, lo que permite reconstruir la configuración vigente en una fecha con `GetParameterSnapshotAtDateQuery` (RNF-013). Esa Query no se expone como endpoint; los consumidores internos pueden usarla.

### Catálogo inicial

`OrganizacionConfiguracionCatalogSeeder` carga 23 claves y no pisa valores existentes al volver a ejecutarse. Los valores/rangos con **PROPUESTO - VALIDAR** deben confirmarse con el cliente.

| Clave | Tipo | Defecto | Rango | Sensible | Recepción | Estado |
|---|---|---|---|---|---|---|
| `nombre_centro` | texto | TalitaCumi | máx. 200 | no | no | Existente |
| `direccion_centro` | texto | Yacuiba, Bolivia | máx. 500 | no | no | Existente |
| `telefono_centro` | texto | *(vacío)* | máx. 30 | no | no | Existente |
| `centro.logo` | texto (ruta) | *(vacío)* | — | no | no | Implementado con carga de archivo |
| `sesion_inactividad_minutos` | entero | 15 | 5–120 | no | no | Existente |
| `duracion_sesion_minutos` | entero | 50 | 30–90 | sí | sí | Spec + rango PROPUESTO |
| `agenda.horario_apertura` | hora | 07:00 | 00:00–23:59 | no | sí | PROPUESTO - VALIDAR |
| `agenda.horario_cierre` | hora | 19:00 | 00:00–23:59 | no | sí | PROPUESTO - VALIDAR |
| `agenda.turno_manana.inicio` | hora | 08:00 | 00:00–23:59 | no | sí | PROPUESTO - VALIDAR |
| `agenda.turno_manana.fin` | hora | 12:00 | 00:00–23:59 | no | sí | PROPUESTO - VALIDAR |
| `agenda.turno_tarde.inicio` | hora | 14:00 | 00:00–23:59 | no | sí | PROPUESTO - VALIDAR |
| `agenda.turno_tarde.fin` | hora | 18:00 | 00:00–23:59 | no | sí | PROPUESTO - VALIDAR |
| `agenda.intervalo_minimo_entre_turnos_minutos` | entero | 0 | 0–60 | no | sí | PROPUESTO - VALIDAR |
| `agenda.dias_semana_habilitados` | texto | 1,2,3,4,5,6 | días 1–7 sin repetir | no | sí | PROPUESTO - VALIDAR |
| `agenda.formato_visualizacion` | texto | semanal | semanal/mensual | no | sí | PROPUESTO - VALIDAR |
| `ventana_edicion_asistencia_horas` | entero | 24 | 0–72 | no | no | Existente / rango PROPUESTO |
| `dias_alerta_vencimiento` | entero | 7 | 1–30 | no | sí | Existente / rango PROPUESTO |
| `mensaje_whatsapp` | texto | plantilla actual | máx. 1000 | no | sí | Existente |
| `comprobante.prefijo` | texto | TC | 1–10 alfanumérico | sí | no | PROPUESTO - VALIDAR |
| `comprobante.formato` | texto | `{prefijo}-{numero:6}` | marcadores permitidos | sí | no | PROPUESTO - VALIDAR |
| `comprobante.leyenda_no_factura` | texto | Este documento NO es factura | fijo | no | no | RN-010, no editable |
| `zona_horaria` | texto | America/La_Paz | identificador IANA | sí | no | Spec |
| `moneda` | texto | BOB | fijo | no | sí | RN-003, no editable |

Reglas de coherencia (RF-011): la hora de fin de cada turno debe ser posterior a su inicio; el cierre debe ser posterior a la apertura; y la duración de sesión no puede superar el bloque de agenda más corto. Los parámetros fijos (moneda y leyenda) y `centro.logo` no se editan por `PATCH`; el logo se reemplaza por `POST /logo`.

### Contrato de lectura

**`ParameterReaderContract`** — implementado por `EloquentParameterReader`:

```php
public function obtener(string $clave): ParameterValue;
public function obtenerVarios(array $claves): array; // clave => ParameterValue
public function porCategoria(string $categoria): array; // clave => ParameterValue
```

`ParameterValue` expone `clave`, `valor` tipado, `tipo`, `unidad`, `categoria`, `sensible`, `visibleRecepcion` y `actualizadoEn`. Los decimales se devuelven como `string` para no perder precisión; los enteros como `int`, los booleanos como `bool`.

Ejemplo de uso desde Agenda (módulo 07):

```php
use App\Modules\Organizacion\Contracts\ParameterReaderContract;

$duracion = app(ParameterReaderContract::class)
    ->obtener('duracion_sesion_minutos')
    ->valor; // int 50

$agenda = app(ParameterReaderContract::class)->porCategoria('agenda'); // clave => ParameterValue
```

Clave inexistente: se registra `Log::warning` y se lanza `ParameterNotFoundException` (`404`). **Nunca se devuelve null ni un valor inventado**; el módulo consumidor decide su valor por defecto documentado.

Caché (RNF-002 / RT-008): store `file` (no hay Redis ni tags), TTL de 1 hora, claves `configuracion.parametro.{clave}` y `configuracion.categoria.{categoria}`. `InvalidateParameterCache` olvida ambas claves al crear, editar o restaurar. Los tests usan `CACHE_STORE=array` y verifican la invalidación.

### TODOs de módulos consumidores

- **07 — Agenda y turnos:** aplicar `duracion_sesion_minutos` a turnos individuales nuevos; los turnos existentes conservan su duración (RN-004). Consumir el horario de `agenda.*` en lugar de valores fijos.
- **08 — Pagos y caja:** usar `moneda`, `comprobante.prefijo` y `comprobante.formato` para la numeración. El contador y la emisión siguen siendo del módulo 08. Imprimir siempre `comprobante.leyenda_no_factura` (RN-010).
- **Reportes:** presentar fechas con `zona_horaria`; la base sigue en UTC y no se cambia `config('app.timezone')` en caliente.
- **01/02:** tienen `America/La_Paz` fija en el frontend; queda como TODO migrarlas al contrato cuando corresponda.
- **RF-015:** exportación PDF/Excel pendiente de infraestructura; permiso reservado y botón no expuesto.

### Estado del submódulo

| Cobertura | Estado |
|---|---|
| RF-001 a RF-014 | Implementados |
| RF-015 (PDF/Excel) | Pendiente de infraestructura |
| RF-016 (sensibles) | Implementado con confirmación + motivo revalidados en backend |
| RN-001 a RN-011 | Implementadas; RN-004 y RN-011 tienen TODOs en los consumidores |
| CA-001 a CA-008, CA-010 | Cubiertos por tests |
| CA-009 | Pendiente junto con RF-015 |

### Migraciones

- `2026_11_20_000500_create_configuracion_table.php`: tabla `configuracion` con categoría, tipo, valor/defecto, límites, banderas, `version`, `updated_at`, índice único de clave e índices de categoría/visibilidad. En PostgreSQL agrega el CHECK de `tipo`.

El esquema de referencia se mantiene sincronizado en `database/database.md` y `database/talitacumi_schema.sql`.

---

## Módulo 02 — Usuarios, roles y seguridad (Fase 1)

Esta fase implementa identidad, autenticación real, política de contraseñas, bloqueo, sesiones y bitácora de accesos. El CRUD de usuarios, el alcance real por sucursal, la matriz editable y la consulta de bitácora llegan en las fases 2–4.

### Estructura

```text
app/Modules/Seguridad/
├── Actions/Autenticacion/        LoginAction, LogoutAction, ChangePasswordAction
├── Console/Commands/             CreateSuperadminCommand
├── Enums/                        AuditAction, AuditModule, AuditResult
├── Events/                       UserLoggedIn, UserLoginFailed, UserLoggedOut, UserPasswordChanged, UserLocked
├── Exceptions/                   Errores de dominio con render HTTP
├── Http/Controllers/             AuthController
├── Http/Middleware/              EnsureSessionIsValid
├── Http/Requests/                LoginRequest, ChangePasswordRequest
├── Http/Resources/               SessionResource, AuthenticatedUserResource
├── Listeners/                    RecordSecurityAudit
├── Models/                       BitacoraEntry, UsuarioPasswordHistorial
├── Providers/                    SeguridadServiceProvider
├── Queries/Autenticacion/        GetAuthenticatedUserQuery
├── Rules/                        PasswordPolicy (pura)
├── Support/                      SecurityParameters, PasswordHistory, TemporaryPasswordGenerator, UserSnapshot
└── routes-auth.php
```

`SeguridadServiceProvider` se registra después de `OrganizacionServiceProvider` en `bootstrap/providers.php`.

### Endpoints

| Método | Ruta | Acción / Query | Acceso |
|---|---|---|---|
| POST | `/api/auth/login` | `LoginAction` | público, throttle 20/min por IP |
| POST | `/api/auth/logout` | `LogoutAction` | `auth:sanctum` |
| GET | `/api/auth/me` | `GetAuthenticatedUserQuery` | `auth:sanctum` |
| POST | `/api/auth/cambiar-password` | `ChangePasswordAction` | `auth:sanctum` |

`me` devuelve usuario, rol, permisos efectivos, sucursales, `alcance_global`, `requires_branch_assignment`, `debe_cambiar_password`, `ultimo_acceso`, `password_expires_at` y `password_policy`.

### Tokens y sesiones

- Store: `personal_access_tokens` de Sanctum. **No se crea la tabla `sesion`.**
- Token bearer con `expires_at` desde `sesion_duracion_absoluta_minutos`.
- `sanctum.last_used_at = false`: la inactividad se mide contra el valor anterior y luego `EnsureSessionIsValid` actualiza `last_used_at`.
- `sesion_inactividad_minutos` (existente, default 15) expira la sesión.
- `sesion_maximas_por_usuario` (default 1): un login nuevo revoca la sesión más antigua y lo deja en la bitácora.
- Invalidación: desactivación, cambio de rol y reseteo (Fase 2); cambio propio de contraseña revoca las demás sesiones.
- Logout elimina el token actual y audita.
- `EnsureSessionIsValid` bloquea todo salvo `/auth/me`, `/auth/logout` y `/auth/cambiar-password` cuando `debe_cambiar_password = true`.

### Contraseñas y bloqueo

- Hash nativo bcrypt (`BCRYPT_ROUNDS=12` en desarrollo, 4 en tests). Sin `config/hashing.php`.
- `usuario_password_historial` guarda solo hashes previos; nunca contraseñas en claro.
- Política leída de `configuracion` (categoría `seguridad`). Clave faltante → `503 SECURITY_CONFIGURATION_MISSING`, sin valores inventados.
- `login_intentos_maximos` (5) y `login_bloqueo_minutos` (15): bloqueo temporal con `bloqueado_hasta`; al expirar, el contador se reinicia. El desbloqueo manual anticipado llega en Fase 2.
- Mensajes de login siempre genéricos (`INVALID_CREDENTIALS`), incluso para usuario desconocido, inactivo o bloqueado. Se ejecuta un `Hash::check` dummy para no filtrar por tiempo.

### Catálogo de parámetros de seguridad

Se agregaron 12 claves a `OrganizacionConfiguracionCatalogSeeder`, categoría `seguridad`, con marca **PROPUESTO - VALIDAR** en la descripción:

| Clave | Defecto | Rango |
|---|---:|---|
| `password_longitud_minima` | 10 | 8–72 |
| `password_requiere_mayuscula` | true | — |
| `password_requiere_minuscula` | true | — |
| `password_requiere_numero` | true | — |
| `password_requiere_simbolo` | false | — |
| `password_historial_cantidad` | 5 | 0–24 |
| `password_caducidad_dias` | 0 | 0–365 |
| `login_intentos_maximos` | 5 | 3–10 |
| `login_bloqueo_minutos` | 15 | 5–1440 |
| `sesion_duracion_absoluta_minutos` | 480 | 30–1440 |
| `sesion_maximas_por_usuario` | 1 | 1–10 |
| `bitacora_retencion_anios` | 5 | 1–10 |

`sesion_inactividad_minutos` (categoría `operacion`, default 15) se reutiliza sin cambios.

### Bootstrap del primer Superadmin

```bash
docker compose exec backend php artisan seguridad:crear-superadmin
```

- Sin contraseña por defecto en el repositorio.
- Acepta opciones (`--name`, `--email` (opcional), `--username`, `--documento`, `--password`, `--branch=*`), variables de entorno `SUPERADMIN_*` o prompt interactivo.
- Si no hay sucursales activas, el comando crea el Superadmin sin sucursales y muestra una advertencia. El Superadmin tiene alcance global y puede crear la primera sucursal desde Organización → Sucursales.
- Crea el usuario con `debe_cambiar_password = true` y todos los permisos del rol Superadmin.
- `--promote=ID` promueve un usuario existente sin cambiarle la contraseña (útil para instalaciones previas).
- La instalación existente con usuarios en `users` queda intacta: las columnas nuevas son nullable/default; esos usuarios deben ser promovidos o completados antes de operar.

### Usuarios demo para iniciar sesión

Solo para desarrollo. No se ejecuta desde `DatabaseSeeder` y el seeder se bloquea en producción.

```bash
make seed
make seed-demo
```

o:

```bash
docker compose exec backend php artisan db:seed --class=SeguridadDemoUsersSeeder
```

| Rol | Identificador | Email | Contraseña |
|---|---|---|---|
| Superadmin | `superadmin.demo` | `superadmin.demo@talitacumi.test` | `Password12` |
| Administración | `admin.demo` | `admin.demo@talitacumi.test` | `Password12` |
| Recepción | `recepcion.demo` | `recepcion.demo@talitacumi.test` | `Password12` |

- La contraseña se configura con `DEMO_PASSWORD` en `backend/.env` y por defecto es `Password12`, que cumple la política inicial (10 caracteres, mayúscula, minúscula y número).
- `DEMO_FORCE_PASSWORD_CHANGE=false` por defecto para que el login demo sea estable. Si se activa, el primer acceso obliga a cambiarla.
- Si no hay sucursales activas, se crea `DEMO-CENTRAL` / `Sucursal Demo`. Administración y Recepción se asignan a esa sucursal; el Superadmin, a todas las activas.
- Es idempotente: si el correo ya existe, no se resetea la contraseña ni se modifica el usuario. Para rotarla, usá el reseteo desde la UI o recreá el entorno demo.
- No audita las altas: es bootstrap, igual que `seguridad:crear-superadmin`.

### Bitácora de accesos

Eventos nuevos: `INICIAR_SESION`, `LOGIN_FALLIDO`, `CERRAR_SESION`, `BLOQUEAR`, `CAMBIAR_PASSWORD`.

Columnas nuevas: `modulo`, `resultado` (`exito|fallo|denegado`), `critico`. `ip` ya existía y ahora se completa.

- Login fallido desconocido: `usuario_id = NULL`, `datos_nuevos = { identificador }`, sin revelar existencia.
- Los eventos de usuario se registran con `sucursal_id` de cada sucursal activa del usuario afectado. Si pertenece a varias, se escribe una fila por sucursal para que Administración los vea con su alcance. Los usuarios con `sucursal.alcance_global` se registran una sola vez como globales. La matriz de permisos sigue siendo global y solo la ve el Superadmin.
- Nunca se escriben contraseñas, hashes ni tokens. Los snapshots usan whitelist (`UserSnapshot`).
- El listener es síncrono y fail-closed: si falla la bitácora, la operación se revierte o responde `503 AUDIT_UNAVAILABLE`.
- No se modificaron los listeners de los módulos 01 y 03. Sus filas siguen con `ip`, `modulo` y `resultado` null; la consulta de Fase 4 derivará el módulo desde `entidad`.

### Permisos de Fase 1

Seeder: `SeguridadPermissionSeeder`, con asignación inicial única por módulo.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `usuario.ver` | sí | sí | no |
| `usuario.crear` | sí | no | no |
| `usuario.editar` | sí | no | no |
| `usuario.cambiar_estado` | sí | no | no |
| `usuario.resetear_password` | sí | no | no |
| `usuario.desbloquear` | sí | no | no |
| `usuario.cerrar_sesiones` | sí | no | no |
| `usuario.exportar` (reservado RF-U15) | sí | sí | no |
| `rol.ver` | sí | sí | no |
| `rol.editar_permisos` | sí | no | no |
| `matriz.exportar` (reservado RF-R03) | sí | sí | no |
| `bitacora.ver` | sí | sí | no |
| `bitacora.exportar` (reservado RF-B09) | sí | sí | no |

Los endpoints de auth no requieren permiso de módulo, solo autenticación.

### Re-seed seguro (RF-R04)

`modulo_permisos_inicializado` + `Database\Seeders\Support\InitialPermissionMatrix` evitan que `db:seed` vuelva a conceder permisos revocados desde la UI.

- Instalación nueva: asigna la matriz inicial y marca el módulo.
- Instalación existente: detecta asignaciones previas, marca y no reasigna.
- Re-ejecución: no toca la matriz.
- Los seeders de 01/02/03 ya usan este helper.

### Estado de la Fase 1

| Cobertura | Estado |
|---|---|
| RF-A01, A02, A03, A05, A07, A08, A11, A12, A13, A14, A15, A16 | Implementados |
| RF-A04, A06, A09, A10 | Completados en Fase 2 |
| RF-B01 (auth), RF-B02 | Implementados |
| RF-B03–B15 | Fase 4, salvo B14 (permisos base ya definidos) |
| RN-06 (login), RN-11, RN-12, RN-13 (bloqueo), RN-18 | Implementadas |
| RN-14 | Implementada en Fase 2 |
| RN-16 | Implementada en Fase 2 (último Superadmin) |
| RN-17, RN-19–RN-22 | Fases 3–4 |
| CU-07, CU-08, CU-10 (auth) | Implementados |
| CU-09 | Implementado en Fase 2 |
| CU-06, CU-11, CU-12 | Fases 3–4 / pendiente infraestructura |

#### Checklist RF/RN de la Fase 1 (numeración consolidada)

| Requisito | Estado | Evidencia |
|---|---|---|
| RF-A01 login con token | Completo | `LoginAction`, `LoginTest` |
| RF-A02 política de contraseñas | Completo | `PasswordPolicy`, `PasswordPolicyTest`, `ChangePasswordTest` |
| RF-A03 cambio propio | Completo | `ChangePasswordAction`, `ChangePasswordTest` |
| RF-A04 reseteo con temporal | Completo (Fase 2) | `ResetUserPasswordAction`, `UserPasswordResetTest` |
| RF-A05 bloqueo por intentos | Completo | `LoginAction`, `LoginLockoutTest` |
| RF-A06 desbloqueo manual | Completo (Fase 2) | `UnlockUserAction`, `UserUnlockTest` |
| RF-A07 expiración por inactividad | Completo | `EnsureSessionIsValid`, `SessionInactivityTest` |
| RF-A08 logout local | Completo | `LogoutAction`, `LogoutTest` |
| RF-A09 cierre remoto | Completo (Fase 2) | `CloseUserSessionsAction`, `UserSessionsTest` |
| RF-A10 invalidación por desactivación/rol | Completo (Fase 2) | `UpdateUserAction`, `ChangesUserStatus`, tests |
| RF-A11 mensajes genéricos | Completo | `InvalidCredentialsException`, `LoginTest` |
| RF-A12 bitácora de accesos | Completo (auth) | `RecordSecurityAudit`, `AuthAuditTest` |
| RF-A13 cambio forzado | Completo (Fase 1 + reset Fase 2) | `EnsureSessionIsValid`, `ForcedPasswordChangeTest` |
| RF-A14 tokens protegidos | Completo (sessionStorage + `EnsureHttps`); riesgo XSS residual | `HttpsTest`, README |
| RF-A15 límite de sesiones | Completo | `LoginAction`, `SessionLimitTest` |
| RF-A16 último acceso | Completo | `GetAuthenticatedUserQuery`, `LoginTest` |
| RF-B01 campos mínimos | Completo para auth/usuarios; resto Fase 4 | `RecordSecurityAudit` |
| RF-B02 inmutabilidad | Completo | Trigger existente + `BitacoraImmutabilityTest` |
| RF-R05/R06 validación backend + UI | Parcial: auth + usuarios; matriz Fase 3 | `EnsureSessionIsValid`, `permissionGuard`, `me` |
| RN-06 login | Completo (login + asignabilidad) | `LoginAction`, `UserDirectoryContractTest` |
| RN-11 autenticación obligatoria | Completo | `auth:sanctum` + guards |
| RN-12 política vigente | Completo | `SecurityParameters` |
| RN-13 bloqueo | Completo (bloqueo + desbloqueo) | `LoginLockoutTest`, `UserUnlockTest` |
| RN-18 bitácora inmutable | Completo | `BitacoraImmutabilityTest` |
| CU-07 iniciar sesión | Completo | `LoginTest` |
| CU-08 cambiar contraseña | Completo | `ChangePasswordTest` |
| CU-10 registrar evento auditado | Completo para auth/usuarios; resto Fase 4 | `RecordSecurityAudit` |

### Migraciones

- `2026_11_21_000000_add_security_fields_to_users_table.php`: columnas de negocio nullable/default en `users`.
- `2026_11_21_000100_create_usuario_sucursal_table.php`.
- `2026_11_21_000200_create_usuario_password_historial_table.php`.
- `2026_11_21_000300_add_audit_fields_to_bitacora_table.php`: `modulo`, `resultado`, `critico` e índices.
- `2026_11_21_000400_add_matrix_fields_to_roles_table.php`: `version`, `es_superadmin` e índice único parcial.
- `2026_11_21_000500_create_modulo_permisos_inicializado_table.php`.

### Fase 2 — Usuarios y alcance por sucursal

Implementada sobre el mismo módulo `Seguridad`. La migración `2026_11_21_000700` hace opcional `users.email`; el resto usa las tablas de Fase 1.

#### Endpoints

Prefijo real: `/api/seguridad/usuarios`.

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/` | `ListUsersQuery` | `usuario.ver` |
| GET | `/opciones` | `GetUserFormOptionsQuery` (roles + sucursales activas) | `usuario.ver` |
| GET | `/{id}` | `GetUserQuery` | `usuario.ver` |
| GET | `/{id}/historial` | `GetUserHistoryQuery` | `usuario.ver` |
| POST | `/` | `CreateUserAction` | `usuario.crear` |
| PATCH | `/{id}` | `UpdateUserAction` | `usuario.editar` |
| POST | `/{id}/activar` | `ActivateUserAction` | `usuario.cambiar_estado` |
| POST | `/{id}/desactivar` | `DeactivateUserAction` | `usuario.cambiar_estado` |
| POST | `/{id}/resetear-password` | `ResetUserPasswordAction` | `usuario.resetear_password` |
| POST | `/{id}/desbloquear` | `UnlockUserAction` | `usuario.desbloquear` |
| POST | `/{id}/cerrar-sesiones` | `CloseUserSessionsAction` | `usuario.cerrar_sesiones` |
| DELETE | `/{id}` | `RejectUserDeletionAction` | `usuario.cambiar_estado` → 409 |

La lista devuelve `data`, `links`, `meta`, `can` (`create`, `export=false`) y un `can` por fila (`update`, `change_status`, `reset_password`, `unlock`, `close_sessions`, `delete=false`). El alta y el reseteo devuelven `password_temporal` una sola vez.

#### Contratos

- **`UserBranchAccessContract`** implementado por `EloquentUserBranchAccess`:
  - usuario inactivo/inexistente → `[]`;
  - con `sucursal.alcance_global` → todas las sucursales activas;
  - sin alcance global → solo `usuario_sucursal` activas.
- **`UserDirectoryContract`** implementado por `EloquentUserDirectory`: `existe`, `estaActivo`, `asegurarActivo` (excepciones `UserNotFoundException` / `InactiveUserException`).

#### Reglas aplicadas

- Un rol por usuario con `syncRoles([$rol])`.
- Al menos una sucursal activa para crear o activar usuarios sin alcance global. El Superadmin con `sucursal.alcance_global` puede operar sin pivotes; el bootstrap lo crea sin sucursales si aún no hay ninguna y luego puede crear la primera.
- `username` y `documento` únicos. `email` es opcional; si se informa, es único y debe tener formato válido. El login acepta `username` o `email` cuando existe.
- `version` optimista en edición, activación/desactivación y desbloqueo; 409 con recarga.
- Desactivar exige motivo; activar lo permite opcional.
- Desactivar, cambiar rol y resetear invalidan todas las sesiones.
- Reset genera temporal, cierra sesiones y fuerza cambio; se muestra una sola vez.
- Desbloqueo manual con motivo; si la cuenta no está bloqueada → 409.
- Cierre remoto con motivo; devuelve cuántas sesiones cerró.
- `DELETE` siempre 409 (`USER_DELETE_NOT_ALLOWED`).
- El último Superadmin activo no puede desactivarse ni perder su rol (`LAST_SUPERADMIN_PROTECTED`).
- Alcance por sucursal fail-closed en listado, ficha, historial y acciones.

#### Tests actualizados de 01/02

Los cinco tests que esperaban `503 MODULE_02_PENDING` ahora verifican el comportamiento real:

- listados sin sucursales → `200` con `data` vacía;
- ficha/edición fuera de alcance → `403 BRANCH_ACCESS_DENIED`.

Se eliminaron `StubUserBranchAccessContract` y `ModulePendingException`.

#### Frontend

- Página `/seguridad/usuarios` con listado paginado, búsqueda y filtros por rol, sucursal y estado.
- Ficha, alta/edición en modal compartido, activación/desactivación con motivo.
- El selector de sucursales del formulario se carga desde `/opciones` en cada apertura: solo sucursales activas del alcance del usuario, no la foto del login.
- El nombre de usuario se genera automáticamente desde el nombre completo al crear; deja de autocompletarse si se edita manualmente. En edición se conserva el usuario existente.
- El correo es opcional en el alta y edición; si se deja vacío se envía `null`. No se envían correos.
- Reseteo con contraseña temporal mostrada una vez y botón de copiar.
- Desbloqueo y cierre remoto con motivo; historial con antes/después.
- `permissionGuard` con `usuario.ver`; el sidebar muestra “Seguridad → Usuarios” solo con permiso.
- Sin eliminación física y sin exportación (RF-U15 pendiente de infraestructura).

#### Checklist Fase 2 (numeración consolidada)

| Requisito | Estado | Evidencia |
|---|---|---|
| RF-U01 crear usuario | Completo | `CreateUserAction`, `UserCrudTest` |
| RF-U02 unicidad documento/usuario y correo cuando se informa | Completo | `UserUniquenessGuard`, `UserCrudTest` |
| RF-U03 campos obligatorios | Completo: nombre, documento, username, rol y sucursales; email opcional | `StoreUserRequest`, `UserCrudTest` |
| RF-U04 un rol por usuario | Completo | `syncRoles`, `UserCrudTest` |
| RF-U05 ≥1 sucursal activa | Completo | `UserAccessScope`, `UserStatusTest` |
| RF-U06 listado/filtros | Completo | `ListUsersQuery`, `UserListTest` |
| RF-U07 ficha | Completo | `GetUserQuery`, `UserListTest` |
| RF-U08 edición + bitácora | Completo | `UpdateUserAction`, `UserCrudTest` |
| RF-U09 activar/desactivar con motivo | Completo | `ChangesUserStatus`, `UserStatusTest` |
| RF-U10 reset + cambio forzado | Completo | `ResetUserPasswordAction`, `UserPasswordResetTest` |
| RF-U11 bloquear eliminación | Completo | `RejectUserDeletionAction`, `UserCrudTest` |
| RF-U12 auditar altas/ediciones/roles/estado/reseteos | Completo | `RecordSecurityAudit` |
| RF-U13 Administración/Recepción no crean ni editan | Completo | permisos + `UserCrudTest` |
| RF-U14 impedir inactivos + historial | Completo | `UserDirectoryContract`, `UserHistoryTest` |
| RF-U15 exportar | Pendiente de infraestructura | sin endpoint ni botón |
| RF-U16 scoping backend | Completo | `EloquentUserBranchAccess`, `UserBranchAccessContractTest` |
| RF-A04 reset con temporal | Completo | `ResetUserPasswordAction` |
| RF-A06 desbloqueo manual | Completo | `UnlockUserAction` |
| RF-A09 cierre remoto | Completo | `CloseUserSessionsAction` |
| RF-A10 invalidación por estado/rol/reseteo | Completo | `UpdateUserAction`, `ChangesUserStatus`, `ResetUserPasswordAction` |
| RN-01 unicidad | Completo | `UserUniquenessGuard` |
| RN-02 un rol | Completo | `syncRoles` |
| RN-03 sucursal obligatoria | Completo | `UserAccessScope` |
| RN-05 no eliminación | Completo | `RejectUserDeletionAction` |
| RN-06 inactivo no opera | Completo | `EnsureSessionIsValid`, `InactiveUserException` |
| RN-07 reset fuerza cambio | Completo | `ResetUserPasswordAction` |
| RN-08 cambio de rol aplica desde el cambio | Completo | `UpdateUserAction` |
| RN-10 motivo al desactivar/cerrar sesiones | Completo | `UserStatusChangeRule`, requests |
| RN-14 invalidación de sesiones | Completo | Acciones + tests |
| RN-16 último Superadmin activo | Completo | `LastSuperadminRule`, `SuperadminGuard`, `UserStatusTest` |
| RF-R08 protección del último Superadmin | Completo (adelantado de Fase 3) | `LastSuperadminRule`, `UserStatusTest` |
| CU-01 registrar usuario | Completo | `UserCrudTest` |
| CU-02 editar rol/sucursales | Completo | `UserCrudTest` |
| CU-03 desactivar usuario | Completo | `UserStatusTest` |
| CU-09 resetear contraseña | Completo | `UserPasswordResetTest` |
| FP-01 a FP-04 y FP-08 | Completos | Acciones y tests |

### Fase 3 — Roles y matriz de permisos

Implementada sobre las tablas de Fase 1 (`roles.version`, `roles.es_superadmin`, `bitacora`). No agrega migraciones.

#### Endpoints

Prefijo real: `/api/seguridad/roles`.

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/` | `ListRolesMatrixQuery` | `rol.ver` |
| GET | `/{id}` | `GetRoleMatrixQuery` | `rol.ver` |
| GET | `/{id}/historial?fecha=YYYY-MM-DD` | `GetRoleMatrixAtDateQuery` | `rol.ver` |
| PUT | `/{id}/permisos` | `UpdateRolePermissionsAction` | `rol.editar_permisos` |
| DELETE | `/{id}` | `RejectRoleDeletionAction` | `rol.editar_permisos` → 409 |

La respuesta incluye `roles` (con `permisos`, `version`, `es_superadmin` y `can.edit`), `modulos` agrupados por módulo/acción, `total_permisos` y `can` (`edit`, `export=false`).

#### Matriz y concurrencia

- Módulo derivado del prefijo `entidad.accion`; etiquetas en `PermissionCatalog`.
- Edición con `version` optimista por rol: 409 `MATRIX_VERSION_CONFLICT` con recarga.
- Sin cambios: 409 `MATRIX_UNCHANGED` (no genera auditoría falsa).
- Permiso inválido: 422.
- Tras guardar: `syncPermissions`, `roles.version++` y `PermissionRegistrar::forgetCachedPermissions()`. El cambio aplica en la siguiente operación (RN-22).
- Cada edición deja `entidad='matriz_permisos'`, `accion='EDITAR_PERMISOS'`, antes/después y `critico=true`.
- Los roles no se crean ni se eliminan (RN-17). `DELETE` siempre 409.

#### Permisos críticos del Superadmin

`UpdateRolePermissionsAction` impide quitar del rol marcado `es_superadmin` el subconjunto crítico: `rol.ver`, `rol.editar_permisos`, `usuario.ver`, `usuario.crear`, `usuario.editar`, `usuario.cambiar_estado`, `usuario.resetear_password`, `usuario.desbloquear`, `usuario.cerrar_sesiones`, `bitacora.ver` y `sucursal.alcance_global`. Si falta alguno → `409 CRITICAL_PERMISSION_PROTECTED`. No se decide por nombre de rol.

#### Contrato evaluador

**`PermissionEvaluatorContract`** implementado por `EloquentPermissionEvaluator`:

```php
public function puede(int $usuarioId, string $permiso, ?int $sucursalId = null): bool;
public function permisos(int $usuarioId): array; // list<string>
```

- Usuario inexistente o inactivo → `false` / `[]`.
- Permiso ausente → `false`.
- Con `sucursalId`, además exige alcance por sucursal.
- Cualquier error → log y `false` (fail-closed).

#### Línea base e histórico

`SeguridadMatrixBaselineSeeder` registra una vez por instalación un evento `EDITAR_PERMISOS` por rol, con `motivo='Línea base de instalación'`, `critico=false` y `linea_base=true`. Se marca en `modulo_permisos_inicializado` (`seguridad.matriz_baseline`) y no se duplica al re-ejecutar.

`GetRoleMatrixAtDateQuery` toma el último evento `matriz_permisos` del rol anterior al fin del día consultado (convertido desde `America/La_Paz` a UTC) y devuelve `permisos`, `version`, `version_actual` y `registrado_en`. Sin eventos → `encontrado=false`. No hay tabla de versiones paralela.

#### Frontend

- Página `/seguridad/roles-permisos` (sidebar: Seguridad → Roles y permisos, con `rol.ver`).
- Selector de rol, grilla por módulo/acción, contador de permisos y versión.
- Superadmin edita; Administración solo consulta; Recepción no accede.
- Permisos críticos del Superadmin bloqueados con candado.
- Guardado con `version`; 409 con recarga; `MATRIX_UNCHANGED` y `CRITICAL_PERMISSION_PROTECTED` con mensajes claros.
- Vigencia histórica con selector de fecha Flatpickr y consulta a la bitácora.
- Sin botón de exportación (RF-R03 pendiente de infraestructura).

#### Checklist Fase 3 (numeración consolidada)

| Requisito | Estado | Evidencia |
|---|---|---|
| RF-R01 catálogo de roles | Completo (roles fijos; sin crear/eliminar) | `RoleMatrixBuilder`, `RoleMatrixListTest` |
| RF-R02 matriz rol × módulo × acción | Completo | `ListRolesMatrixQuery`, `RoleMatrixBuilder` |
| RF-R03 consultar/exportar matriz | Consulta completa; export pendiente de infraestructura | API + página, sin botón |
| RF-R04 editar permisos con bitácora | Completo | `UpdateRolePermissionsAction`, `RoleMatrixUpdateTest` |
| RF-R05 validar permisos en backend | Completo | Middleware Spatie + `PermissionEvaluatorContract` |
| RF-R06 ocultar acciones en UI | Completo | `permissionGuard`, `can.edit` |
| RF-R08 último Superadmin / críticos | Completo | `CriticalPermissionProtectedException`, `LastSuperadminRule` |
| RF-R10 registrar cambios de matriz | Completo | `RolePermissionsUpdated`, `RecordSecurityAudit` |
| RF-R11 exponer matriz a módulos | Completo | `PermissionEvaluatorContract` |
| RF-R12 recalcular al cambiar rol/sucursales | Completo | Spatie + invalidación de sesiones (Fase 2) |
| RF-R13 concurrencia de matriz | Completo | `version` + 409 |
| RF-R14 versionado histórico | Completo | `GetRoleMatrixAtDateQuery` + baseline |
| RF-R15 solo Superadmin edita | Completo | permisos + tests |
| RN-16 último Superadmin | Completo | Regla + críticos |
| RN-17 roles no eliminables | Completo | `RejectRoleDeletionAction`, DELETE 409 |
| RN-22 aplicación inmediata | Completo | `forgetCachedPermissions` + test |
| CU-04 editar permisos | Completo | `RoleMatrixUpdateTest` |
| CU-05 consultar/exportar matriz | Consulta completa; export pendiente | API + página |
| CU-06 evaluar permiso | Completo | `PermissionEvaluatorContractTest` |
| FP-05 gestionar matriz | Completo | Acciones + página |

### Fase 4 — Auditoría y bitácora

Implementada sobre `bitacora` y la nueva tabla de catálogo `accion_auditada`. No se creó una tabla `auditoria` paralela.

#### Endpoints

Prefijo real: `/api/seguridad/bitacora`.

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/` | `ListAuditEntriesQuery` | `bitacora.ver` |
| GET | `/opciones` | `GetAuditFilterOptionsQuery` | `bitacora.ver` |
| GET | `/reconstruir?entidad=&entidad_id=&fecha=` | `GetEntitySnapshotAtDateQuery` | `bitacora.ver` |
| GET | `/{id}` | `GetAuditEntryQuery` | `bitacora.ver` |

No hay endpoint de exportación: RF-B09 queda pendiente de infraestructura y no se muestra botón.

#### Filtros y scoping

- Fechas desde/hasta (convertidas de `America/La_Paz` a UTC), usuario, módulo, entidad, `entidad_id`, acción, resultado (`exito`, `fallo`, `denegado`), crítico y sucursal.
- Paginación con `per_page` máximo 50, orden descendente por fecha.
- Superadmin ve todo. Administración ve eventos con `sucursal_id` en sus sucursales asignadas, incluidos los eventos de seguridad de usuarios de esas sucursales. Los eventos globales (`sucursal_id NULL`, por ejemplo la matriz de permisos o las consultas a la bitácora) solo los ve el alcance global. Recepción no accede.
- El detalle aplica el mismo scoping (`403 AUDIT_ACCESS_DENIED`). La reconstrucción filtra antes de buscar: fuera de alcance responde `encontrado=false`, sin revelar la existencia del evento.

#### Catálogo de acciones auditadas

Tabla `accion_auditada` (`modulo`, `entidad`, `accion`, `critico`, `activo`, `descripcion`, unique por módulo/entidad/acción). Seeder idempotente `AccionAuditadaSeeder` con 40 acciones y 7 críticas al momento de esta fase.

Es un catálogo, no la bitácora. Los módulos consumidores agregan sus acciones cuando implementen sus eventos.

#### Registro para consumidores

**`AuditRecorderContract`** implementado por `EloquentAuditRecorder`:

```php
registrarConsultaSensible(entidad, entidadId, ?sucursalId, contexto, ?usuarioId, ?ip);
registrarExportacion(recurso, filtros, cantidad, ?sucursalId, ?usuarioId, ?ip);
registrarConsultaBitacora(filtros, cantidad, ?usuarioId, ?ip);
```

- Consulta sensible: `accion=CONSULTAR`, módulo derivado de la entidad; el contexto se sanitiza y solo conserva referencias (`entidad`, `entidad_id`, `recurso`, `campo`, `filtros`, `cantidad`). Nunca contenido clínico, contraseñas, hashes ni tokens.
- Exportación: `entidad='exportacion'`, `accion=EXPORTAR`, `critico=true`, filtros y cantidad (sin datos exportados).
- Consulta de bitácora: `entidad='bitacora'`, `accion=CONSULTAR`, filtros y cantidad.
- Todas son síncronas y fail-closed: si la escritura falla, se responde `503 AUDIT_UNAVAILABLE`.

#### Consulta auditada y reconstrucción

- Cada listado, detalle, opciones y reconstrucción registra un `CONSULTAR` sobre `bitacora` (RF-B15). Esas filas son globales y solo las ve el alcance global.
- `GetEntitySnapshotAtDateQuery` toma el último evento **exitoso con snapshot completo** de la entidad antes del fin del día y devuelve `datos_nuevos` como estado, `datos_anteriores` como snapshot previo, acción, fecha, usuario y sucursal. Ignora eventos fallidos, consultas y acciones sin snapshot completo, como `CAMBIAR_PASSWORD` o `CERRAR_SESIONES`.
- Los eventos históricos de 01/02/03 conservan `modulo`, `resultado` e `ip` null; la consulta deriva el módulo desde `entidad` y muestra “—”. Para reconstrucción se aceptan resultados `exito` y `resultado NULL` histórico.

#### Retención y eventos críticos

- `bitacora_retencion_anios` (default 5) se expone en las opciones. **No hay purga ni borrado manual**; particionado/archivado queda como TODO de infraestructura.
- El filtro `critico` y el badge visual permiten ubicar desactivaciones de usuario, reseteos, cierres de sesiones, ediciones de matriz, bloqueos y exportaciones.
- La anulación de pago como evento crítico (RF-B13) queda para el módulo 08; debe llamar al recorder y marcar `critico=true` en su evento.

#### Frontend

- Página `/seguridad/bitacora` (sidebar: Seguridad → Bitácora, con `bitacora.ver`).
- Filtros múltiples, fechas Flatpickr, paginación y detalle con antes/después.
- Marca visual de críticos y resultado.
- Panel de reconstrucción por entidad/ID/fecha.
- Nota de retención configurada y ausencia de exportación.
- Sin botón de exportar.

#### TODOs de módulos consumidores

- **RF-B07 — datos sensibles:**
  - `docs/04_PACIENTES_TUTORES/03_ficha_ingreso_entrevista.md`
  - `docs/04_PACIENTES_TUTORES/04_busqueda_ficha_resumen.md`
  - `docs/11_EVALUACIONES_RESULTADOS/04_acceso_informacion_clinica.md`
  - Deben inyectar `AuditRecorderContract::registrarConsultaSensible` con referencias/ids, nunca contenido clínico.
- **RF-B13 — anulación de pago crítica:**
  - `docs/08_PAGOS_CAJA/04_anulacion_pagos.md`
  - Debe registrar el evento con `critico=true` (o mediante el recorder).
- **RF-B08/RN-19 — exportaciones auditadas:** los exportadores de 09/08 deben llamar `registrarExportacion` con filtros y cantidad.
- **RF-B09 — exportar bitácora:** pendiente de infraestructura; sin endpoint ni botón.

#### Checklist Fase 4 (numeración consolidada)

| Requisito | Estado | Evidencia |
|---|---|---|
| RF-B01 registrar evento con campos mínimos | Completo para eventos nuevos; históricos conservan null | `AuditEntryResource`, consultas |
| RF-B02 inmutabilidad | Completo | Trigger existente + `BitacoraImmutabilityTest` |
| RF-B03 catálogo de acciones | Completo | `accion_auditada`, `AccionAuditadaSeeder` |
| RF-B04 consulta paginada con filtros | Completo | `ListAuditEntriesQuery`, `AuditQueryTest` |
| RF-B05 detalle con antes/después | Completo | `GetAuditEntryQuery`, detalle UI |
| RF-B06 scoping por sucursal | Completo | `AuditScope`, tests |
| RF-B07 accesos a datos sensibles | Mecanismo completo; consumidores 04/08/11 pendientes | `AuditRecorderContract` |
| RF-B08 exportaciones auditadas | Mecanismo completo; exportadores pendientes | `registrarExportacion` |
| RF-B09 exportar bitácora | Pendiente de infraestructura | sin endpoint ni botón |
| RF-B10 snapshot de datos clave | Completo | Snapshots existentes + reconstrucción |
| RF-B11 reconstruir estado por entidad/fecha | Completo | `GetEntitySnapshotAtDateQuery` |
| RF-B12 retención configurable | Parcial: parámetro y aviso; sin purga/particionado | `bitacora_retencion_anios` |
| RF-B13 eventos críticos | Mecanismo completo; anulación de pago pendiente en 08 | `critico`, filtro |
| RF-B14 Recepción bloqueada | Completo | permiso + tests |
| RF-B15 consultas a bitácora auditadas | Completo | `RecordAuditQueryAction` |
| RN-18 bitácora inmutable | Completo | Trigger |
| RN-19 exportaciones auditadas | Mecanismo completo; consumidores pendientes | recorder |
| RN-20 críticos filtrables | Completo | filtro/badge |
| RN-21 sin contenido clínico completo | Completo en el mecanismo | sanitización de contexto |
| CU-10 registrar evento auditado | Completo | recorder + listener |
| CU-11 consultar bitácora por filtros | Completo | página + API |
| CU-12 exportar bitácora | Pendiente de infraestructura | sin botón |
| FP-09 registrar/consultar/exportar bitácora | Consulta completa; export pendiente | API + página |

## Módulo 03 — Profesionales (Fases 1, 2 y 3)

Implementa el directorio de profesionales, el catálogo de especialidades, las sucursales de trabajo y la consulta de la matriz profesional–especialidad–sucursal. Los profesionales **no son usuarios del sistema** (03/RN-19): no tienen cuenta, rol Spatie ni FK a `users`.

### Estructura

```text
app/Modules/Profesionales/
├── Actions/Directorio/       CRUD, estado, especialidades y sucursales
├── Actions/Especialidades/   CRUD y estado del catálogo
├── Contracts/                Directorio y validador de asignación
├── Enums/                    TipoEspecialidad, MotivoNoAsignable
├── Events/                   Eventos de dominio por pilar
├── Exceptions/               Errores con render HTTP
├── Http/Controllers/         Directorio y especialidades
├── Http/Requests/            Validación y autorización por campo
├── Http/Resources/           Profesional, especialidad y relación
├── Listeners/                RecordProfesionalesAudit
├── Models/                   Profesional, Especialidad, pivotes y bitácora
├── Providers/                ProfesionalesServiceProvider
├── Queries/                  Listados, ficha, opciones y sucursales
├── Rules/                    Reglas puras
├── Support/                  Permisos, habilidades, alcance y snapshots
├── routes-directorio.php
├── routes-especialidades.php
└── routes-matriz.php
```

`ProfesionalesServiceProvider` se registra después de `SeguridadServiceProvider`. Prefijo público: `App\Modules\Profesionales\Support\ProfesionalesModule::MODULE_URL = '/profesionales'`.

### Endpoints

Prefijo real: `/api/profesionales`.

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/profesionales` | `ListProfesionalesQuery` | `profesional.ver` |
| GET | `/profesionales/opciones` | `GetProfesionalFormOptionsQuery` | `profesional.ver` |
| GET | `/profesionales/{id}` | `GetProfesionalQuery` | `profesional.ver` |
| POST | `/profesionales` | `CreateProfesionalAction` | `profesional.crear` |
| PATCH | `/profesionales/{id}` | `UpdateProfesionalAction` | `profesional.editar_datos` o `profesional.editar_contacto` según campos |
| POST | `/profesionales/{id}/activar` | `ActivateProfesionalAction` | `profesional.cambiar_estado` |
| POST | `/profesionales/{id}/desactivar` | `DeactivateProfesionalAction` | `profesional.cambiar_estado` |
| DELETE | `/profesionales/{id}` | `RejectProfesionalDeletionAction` → 409 | `profesional.cambiar_estado` |
| GET | `/profesionales/{id}/sucursales` | `ListProfesionalSucursalesQuery` | `profesional.ver` |
| POST | `/profesionales/{id}/sucursales` | `AssignProfesionalSucursalAction` | `profesional_sucursal.administrar` |
| POST | `/profesionales/{id}/sucursales/{sucursalId}/activar` | `ActivateProfesionalSucursalAction` | `profesional_sucursal.administrar` |
| POST | `/profesionales/{id}/sucursales/{sucursalId}/desactivar` | `DeactivateProfesionalSucursalAction` | `profesional_sucursal.administrar` |
| DELETE | `/profesionales/{id}/sucursales/{sucursalId}` | `RejectProfesionalSucursalDeletionAction` → 409 | `profesional_sucursal.administrar` |
| GET | `/especialidades` | `ListEspecialidadesQuery` | `especialidad.ver` |
| GET | `/especialidades/{id}` | `GetEspecialidadQuery` | `especialidad.ver` |
| POST | `/especialidades` | `CreateEspecialidadAction` | `especialidad.administrar` |
| PATCH | `/especialidades/{id}` | `UpdateEspecialidadAction` | `especialidad.administrar` |
| POST | `/especialidades/{id}/activar` | `ActivateEspecialidadAction` | `especialidad.administrar` |
| POST | `/especialidades/{id}/desactivar` | `DeactivateEspecialidadAction` | `especialidad.administrar` |
| DELETE | `/especialidades/{id}` | `RejectEspecialidadDeletionAction` → 409 | `especialidad.administrar` |
| GET | `/matriz` | `ListMatrizQuery` | `profesional.ver_matriz` |
| GET | `/matriz/opciones` | `GetMatrizFilterOptionsQuery` | `profesional.ver_matriz` |

Parámetros de profesionales: `search`, `especialidad_id`, `sucursal_id`, `estado` (`activo|inactivo`), `page`, `per_page` (máx. 50), `sort` (`nombre_completo|ci|activo|updated_at`), `direction`.
Parámetros de especialidades: `search`, `estado`, `page`, `per_page`, `sort` (`nombre|activo|updated_at`), `direction`.
Parámetros de matriz: `search`, `profesional_id`, `especialidad_id`, `sucursal_id`, `estado` (`activo|inactivo` del profesional), `solo_asignables`, `page`, `per_page`, `sort` (`profesional_nombre|profesional_ci|especialidad_nombre|sucursal_nombre`), `direction`.

La respuesta incluye `data`, `links`, `meta` y `can` de pantalla. Cada fila incluye su propio `can` (`update_data`, `update_contact`, `change_status`, `delete=false`, `manage_branches`). La UI usa `can` solo para ocultar acciones; el backend siempre revalida.

### Matriz profesional–especialidad–sucursal (03/RF-E08)

`GET /api/profesionales/matriz` devuelve la grilla vigente de relaciones activas:

- profesional × especialidad (principal o secundaria) × sucursal de trabajo;
- solo relaciones activas (`profesional_especialidad.activo` y `profesional_sucursal.activo`);
- estados de profesional, especialidad y sucursal;
- `asignable` y `motivos_no_asignable` por fila (`PROFESIONAL_INACTIVO`, `ESPECIALIDAD_INACTIVA`, `SUCURSAL_INACTIVA`);
- alcance por sucursal fail-closed: sin alcance global solo se ven las sucursales accesibles; si el filtro pide una sucursal fuera de alcance, la respuesta es vacía;
- el profesional sin sucursal o sin especialidad no genera filas; se ve en el directorio con `asignable=false`;
- `GET /matriz/opciones` devuelve profesionales visibles, especialidades y sucursales accesibles para los filtros.

Es solo lectura. La administración de la relación profesional–sucursal se hace desde la ficha o desde el acceso directo de la fila (mismo modal). `can.export=false`.

### Reglas y estados

- Sin SoftDeletes ni borrado físico: `activo`, `motivo_estado`, `estado_cambiado_en` y `version`.
- `DELETE` siempre responde `409` (`PROFESIONAL_DELETE_NOT_ALLOWED`, `ESPECIALIDAD_DELETE_NOT_ALLOWED`, `PROFESIONAL_SUCURSAL_DELETE_NOT_ALLOWED`). El chequeo real de historial queda pendiente de 06/07/11.
- El motivo es obligatorio al activar y desactivar profesional, especialidad y relación profesional–sucursal.
- `version` optimista: `409 PROFESIONAL_VERSION_CONFLICT`, `ESPECIALIDAD_VERSION_CONFLICT` o `PROFESIONAL_SUCURSAL_VERSION_CONFLICT`.
- Documento único funcional (`lower(trim(ci))`) y nombre de especialidad único funcional.
- Exactamente una especialidad principal activa por profesional. Se guarda solo en `profesional_especialidad` (`tipo='principal'`) con índice parcial.
- Especialidades secundarias: el modelo y la API las soportan, pero `ProfesionalesModule::SECUNDARIAS_HABILITADAS = false` hasta confirmación del cliente (03/RF-D09, 03/RF-E05).
- Un profesional sin sucursal activa no es asignable; la ficha lo muestra con `asignable=false` y `motivos_no_asignable`.
- No se permite desactivar una especialidad con profesionales activos asociados (`409 ESPECIALIDAD_IN_USE`).
- `matricula` es nullable y opcional (supuesto a validar).

### Permisos

Seeder: `ProfesionalesPermissionSeeder`, con `InitialPermissionMatrix` y marca `profesionales`.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `profesional.ver` | sí | sí | sí |
| `profesional.crear` | sí | no | no |
| `profesional.editar_datos` | sí | no | no |
| `profesional.editar_contacto` | sí | sí | no |
| `profesional.cambiar_estado` | sí | no | no |
| `profesional.exportar` (reservado) | sí | sí | no |
| `profesional.ver_matriz` (reservado Fase 2) | sí | sí | sí |
| `especialidad.ver` | sí | sí | sí |
| `especialidad.administrar` | sí | no | no |
| `profesional_sucursal.administrar` | sí | no | no |
| `asignacion.ver` (reservado hasta 07) | sí | sí | sí |
| `asignacion.crear` (reservado hasta 07) | sí | sí | sí |
| `asignacion.reasignar` (reservado hasta 07) | sí | sí | sí |
| `asignacion.desasignar` (reservado hasta 07) | sí | sí | sí |
| `asignacion.exportar` (reservado) | sí | sí | no |
| `diagnostico.asignar_profesionales` (reservado hasta 07) | sí | sí | sí |

No se creó ningún `matriz.*` nuevo: `matriz.exportar` sigue perteneciendo al módulo 02.

### Alcance por sucursal

- Profesional y catálogo de especialidades son globales; la visibilidad del directorio depende de `profesional_sucursal`.
- Sin `sucursal.alcance_global`, el usuario solo ve profesionales con una relación **activa** con alguna de sus sucursales accesibles. Sin sucursales asignadas, la lista es vacía (fail-closed).
- Un profesional sin sucursal solo es visible para el alcance global.
- La ficha filtra las sucursales devueltas al alcance del usuario; nunca expone sucursales ajenas.
- Administración edita solo contactos de profesionales de sus sucursales; Recepción solo consulta.

### Eventos y bitácora

| Entidad (`bitacora.entidad`) | Acciones | `sucursal_id` |
|---|---|---|
| `profesional` | `CREAR`, `ACTUALIZAR`, `ACTIVAR`, `DESACTIVAR` | Una fila por sucursal activa del profesional; `NULL` si no tiene. |
| `especialidad` | `CREAR`, `ACTUALIZAR`, `ACTIVAR`, `DESACTIVAR` | `NULL` (catálogo global). |
| `profesional_especialidad` | `ACTUALIZAR` | Una fila por sucursal activa del profesional; `NULL` si no tiene. |
| `profesional_sucursal` | `CREAR`, `ACTIVAR`, `DESACTIVAR` | La sucursal afectada. |

`RecordProfesionalesAudit` es síncrono y fail-closed. Escribe `modulo='profesionales'`, `resultado='exito'`, `ip` y snapshots con whitelist. Las desactivaciones de profesional y especialidad se marcan `critico=true`.

`ProfesionalesAccionAuditadaSeeder` agrega las acciones al catálogo compartido `accion_auditada` sin modificar el seeder del módulo 02. `AuditModuleResolver` puede mostrar etiquetas de fallback; no se modificó código de 02.

### Catálogo inicial

`EspecialidadCatalogoSeeder` siembra **solo si la tabla está vacía** (instalación nueva):

- Psicología
- Psicopedagogía
- Terapia Ocupacional
- Fisioterapia
- Terapia de Lenguaje

Están marcadas como **PROPUESTO - VALIDAR**. El consolidado menciona “6” pero lista cinco. Si el cliente renombra o agrega especialidades, el seeder no vuelve a sembrar ni pisa cambios.

### Contratos expuestos

**`ProfessionalDirectoryContract`** — implementado por `EloquentProfessionalDirectory`:

```php
public function existe(int $profesionalId): bool;
public function estaActivo(int $profesionalId): bool;
public function asegurarActivo(int $profesionalId): void; // ProfesionalNotFoundException / ProfesionalInactivoException
```

Agenda, diagnósticos, asistencia y reportes deben llamar a `asegurarActivo()` antes de asignar (03/RF-D13, 03/RF-A11).

**`ProfessionalAssignmentValidatorContract`** — implementado por `EloquentProfessionalAssignmentValidator`:

```php
public function puedeAsignar(
    int $profesionalId,
    int $especialidadId,
    int $sucursalId,
): ResultadoAsignacion;

public function asegurarAsignable(
    int $profesionalId,
    int $especialidadId,
    int $sucursalId,
): void; // ProfesionalNotAssignableException (409 PROFESIONAL_NOT_ASSIGNABLE)
```

`ResultadoAsignacion` devuelve `puede`, `codigo` y `mensaje`. Códigos: `ASIGNACION_VALIDA`, `PROFESIONAL_NO_ENCONTRADO`, `PROFESIONAL_INACTIVO`, `ESPECIALIDAD_NO_ENCONTRADA`, `ESPECIALIDAD_INACTIVA`, `ESPECIALIDAD_NO_ASIGNADA`, `SUCURSAL_INACTIVA`, `SUCURSAL_NO_ASIGNADA`.

Ejemplo para el módulo 07:

```php
$resultado = app(ProfessionalAssignmentValidatorContract::class)
    ->puedeAsignar($profesionalId, $especialidadId, $sucursalId);

if (! $resultado->puede) {
    // Mostrar $resultado->codigo y $resultado->mensaje; no consultar otras sucursales.
}
```

El validador verifica profesional activo, especialidad activa, relación especialidad–profesional activa, sucursal activa (en vivo con `BranchDirectoryContract`) y relación profesional–sucursal activa. No verifica solapamiento ni estado del turno: eso es del módulo 07.

### Caché del validador

- Store `file` (sin tags), TTL 300 s.
- Clave de época `profesionales.validador.epoch`; claves `profesionales.validador.{epoch}.{profesional}.{especialidad}.{sucursal}`.
- `InvalidateProfesionalValidatorCache` incrementa la época al crear/editar/cambiar estado de profesional, especialidad, asignación de especialidad o relación con sucursal.
- La sucursal activa se consulta en vivo en cada validación para no quedar obsoleta si 01 la desactiva.
- Tests: acierto de caché, invalidación por estado/relación, rechazo inmediato por sucursal inactiva.

### Fase 3 — Contratos y stubs (sin persistencia)

Fase 3 se entrega como contratos + stubs que fallan de forma explícita. No hay endpoints, pantalla ni cambios a `turno`.

| Contrato | Métodos | Implementación actual |
|---|---|---|
| `ProfessionalScheduleContract` | `haySolapamiento(profesionalId, inicio, fin)`, `tieneAsignacionesFuturas(profesionalId, desde)` | `StubProfessionalScheduleContract` lanza `LogicException('Módulo 07 — Agenda y turnos no implementado todavía.')` |
| `ProfessionalDiagnosticRequirementsContract` | `especialidadesRequeridas(tipoDiagnosticoId)` | `StubProfessionalDiagnosticRequirementsContract` lanza `LogicException('Módulo 05/06 — Diagnósticos no implementado todavía.')` |
| `ProfessionalExporterContract` | `exportarDirectorio`, `exportarMatriz`, `exportarAsignaciones` | `UnavailableProfessionalExporter` lanza `LogicException('Exportación PDF/Excel pendiente de infraestructura.')` |

Los stubs están bindeados en `ProfesionalesServiceProvider`. Las Actions de desactivación **no** llaman todavía a `tieneAsignacionesFuturas()`: hacerlo haría fallar la operación hasta que exista 07. El límite queda documentado.

#### TODOs exactos para módulos consumidores

- **07 — Agenda y turnos** (`docs/07_AGENDA_TURNOS/02_programacion_turnos_patrones.md`, `03_reprogramacion_cancelacion_turnos.md`, `05_diagnostico_varios_profesionales.md`):
  - implementar `ProfessionalScheduleContract` con turnos reales;
  - persistir `turno.profesional_id` nullable («sin asignar»), reasignación con motivo, desasignación, estado `programado` y `turno_profesional`;
  - llamar a `ProfessionalAssignmentValidatorContract::asegurarAsignable()` antes de guardar;
  - validar solapamiento global incluyendo otras sucursales, sin revelar datos de sucursales fuera del alcance en el mensaje;
  - convertir fecha/hora con `zona_horaria` y guardar UTC (RT-06);
  - cubrir RT-09 antes de desactivar profesionales/especialidades.
- **05 — Catálogo de servicios** (`docs/05_CATALOGO_SERVICIOS_TARIFAS/03_tipos_diagnostico_evaluacion.md`) y **06 — Inscripciones/atenciones** (`docs/06_INSCRIPCIONES_ATENCIONES/03_entrevista_diagnostico_atenciones.md`): implementar `ProfessionalDiagnosticRequirementsContract` desde `tipo_diagnostico_profesional` (especialidad y cantidad mínima) y validar la asignación múltiple.
- **11 — Evaluaciones/resultados** (`docs/11_EVALUACIONES_RESULTADOS/02_resultados_diagnostico.md`): historial clínico para el chequeo real de `DELETE` de profesional.
- **Infraestructura:** implementar `ProfessionalExporterContract` y llamar a `AuditRecorderContract::registrarExportacion`; no se instalan librerías ni se muestra botón.
- La asignación real a turnos, “sin asignar”, reasignación, desasignación y diagnóstico múltiple pertenecen al módulo 07. Los permisos `asignacion.*` y `diagnostico.asignar_profesionales` quedan sembrados y documentados, sin endpoint.
- No se modificó `turno`: su creación y la columna `profesional_id` nullable corresponden a 07.

### Checklist Fases 1 y 2 (numeración consolidada)

| Requisito | Estado | Evidencia |
|---|---|---|
| 03/RF-D01 crear profesional | Completo | `CreateProfesionalAction`, `ProfesionalCrudTest` |
| 03/RF-D02 unicidad de documento | Completo | `ProfesionalDocumentUniquenessRule`, índice funcional, tests |
| 03/RF-D03 obligatorios | Completo | `StoreProfesionalRequest`, tests |
| 03/RF-D04 listado/filtros/alcance | Completo | `ListProfesionalesQuery`, `ProfesionalListTest` |
| 03/RF-D05 ficha | Completo | `GetProfesionalQuery`, `ProfesionalResource` |
| 03/RF-D06 edición + bitácora | Completo | `UpdateProfesionalAction`, `ProfesionalAuditTest` |
| 03/RF-D07 activar/desactivar con motivo | Completo | `ChangesProfesionalStatus`, `ProfesionalStatusTest` |
| 03/RF-D08 bloquear eliminación | Parcial | `DELETE` 409; historial real pendiente de 06/07/11 |
| 03/RF-D09 especialidad principal/secundarias | Parcial/condicional | Principal completa; secundarias tras bandera |
| 03/RF-D10 exponer profesional a matriz | Completo | `ProfessionalDirectoryContract` + pivote |
| 03/RF-D11 bitácora | Completo | `RecordProfesionalesAudit` |
| 03/RF-D12 API a consumidores | Completo | REST + contrato |
| 03/RF-D13 inactivo no asignable | Completo | `asegurarActivo`, `asignable=false` |
| 03/RF-D14 restricción por rol | Completo | permisos + tests |
| 03/RF-D15 exportar | Pendiente infraestructura | sin librerías ni botón; `UnavailableProfessionalExporter` |
| 03/RF-E01 crear especialidad | Completo | `CreateEspecialidadAction` |
| 03/RF-E02 editar/desactivar con bloqueo | Completo | `EspecialidadDeactivationRule`, tests |
| 03/RF-E03 listar especialidades | Completo | `ListEspecialidadesQuery` |
| 03/RF-E04 una principal | Completo | pivote + índice parcial + regla |
| 03/RF-E05 secundarias | Condicional | igual que D09 |
| 03/RF-E06 sucursales de trabajo | Completo | acciones y pivote |
| 03/RF-E07 no duplicar relaciones | Completo | reglas + índices parciales |
| 03/RF-E08 matriz | Completo | `ListMatrizQuery`, `MatrizTest`, tab Matriz |
| 03/RF-E09 validación agenda/diagnóstico | Completo (fundación) | `ProfessionalAssignmentValidatorContract` |
| 03/RF-E10 bitácora de matriz | Completo | eventos de sucursal |
| 03/RF-E11 eliminación con historial | Parcial | `DELETE` 409; historial pendiente |
| 03/RF-E12 exportar matriz | Pendiente infraestructura | sin botón; `UnavailableProfessionalExporter` |
| 03/RF-E13 especialidad inactiva | Completo | validación de alta; lado turnos en Fase 2 |
| 03/RF-E14 roles de catálogo/matriz | Completo | permisos + tests |
| 03/RF-A01–A15 | Fase 3 / módulo 07 | A03/A11/A14 tienen validador/alcance; A04/A07/A08/A15 tienen contratos/stubs; persistencia en 07; export pendiente |
| 03/RN-01, RN-02, RN-04–RN-07, RN-09, RN-10, RN-17–RN-20 | Completo/Parcial | ver tests y limitaciones anteriores |
| 03/RN-03, RN-08, RN-11–RN-16 | Fase 2/3 o 07 | validador y contratos pendientes |
| 03/CU-01–CU-05 | Completo | acciones y tests |
| 03/CU-06 | Fase 2 | validador pendiente |
| 03/CU-07–CU-09 | Fase 3 / 07 | contratos/stubs listos; UI y persistencia en 07 |
| 03/CU-10 | Pendiente infraestructura | exportación |

#### RN y CU de las Fases 1 y 2

| Requisito | Estado |
|---|---|
| 03/RN-01 unicidad de documento | Completo |
| 03/RN-02 una principal | Completo |
| 03/RN-03 inactivo no asignable | Completo: contrato + validador |
| 03/RN-04 no eliminación | Parcial: `DELETE` 409, historial real pendiente |
| 03/RN-05 catálogo configurable | Completo |
| 03/RN-06 nombre único | Completo |
| 03/RN-07 sucursal activa para asignar | Completo en asignabilidad |
| 03/RN-08 validación de matriz | Completo: `ProfessionalAssignmentValidatorContract` |
| 03/RN-09 especialidad inactiva | Completo: alta + validador |
| 03/RN-10 relación independiente de especialidad | Completo |
| 03/RN-11 asignación manual | Módulo 07 |
| 03/RN-12 asignación opcional | Módulo 07 |
| 03/RN-13 sin solapamiento | Stub `ProfessionalScheduleContract`; 07 |
| 03/RN-14 solo programado | Pendiente 07 (estado del turno) |
| 03/RN-15 reasignación con motivo | Pendiente 07 |
| 03/RN-16 diagnóstico integral | Stub de requisitos; 05/06/07 |
| 03/RN-17 trazabilidad | Completo para directorio/catálogo; asignaciones en 07 |
| 03/RN-18 alcance por sucursal | Completo |
| 03/RN-19 profesional sin usuario | Completo |
| 03/RN-20 motivo obligatorio | Completo |
| 03/CU-01 registrar profesional | Completo |
| 03/CU-02 editar profesional | Completo |
| 03/CU-03 desactivar profesional | Completo |
| 03/CU-04 registrar especialidad | Completo |
| 03/CU-05 asignar sucursales | Completo |
| 03/CU-06 validar asignación | Completo: contrato interno |
| 03/CU-07 asignar a turno | Módulo 07 |
| 03/CU-08 reasignar/desasignar | Módulo 07 |
| 03/CU-09 diagnóstico múltiple | Fase 3 / 05, 06, 07 |
| 03/CU-10 exportar | Pendiente infraestructura |

#### Checklist Fase 3 (contratos y stubs)

| Requisito | Estado | Evidencia |
|---|---|---|
| 03/RF-A04 solapamiento | Stub | `ProfessionalScheduleContract::haySolapamiento`; 07 implementa |
| 03/RF-A07 diagnóstico múltiple | Stub | `ProfessionalDiagnosticRequirementsContract`; 05/06/07 implementan |
| 03/RF-A08 especialidades requeridas | Stub | mismo contrato; origen `tipo_diagnostico_profesional` |
| 03/RF-A15 exportar asignaciones | Pendiente infraestructura | `UnavailableProfessionalExporter::exportarAsignaciones` |
| 03/RF-D15 / 03/RF-E12 exportar | Pendiente infraestructura | `UnavailableProfessionalExporter`; sin botón |
| 03/RN-13 sin solapamiento | Stub | `ProfessionalScheduleContract`; 07 |
| 03/RN-14 solo programado | Pendiente 07 | estado del turno |
| 03/RN-15 reasignación con motivo | Pendiente 07 | persistencia y bitácora en 07 |
| 03/RN-16 diagnóstico integral | Stub de requisitos | 05/06/07 |
| 03/CU-07 asignar a turno | Pendiente 07 | no hay endpoint en 03 |
| 03/CU-08 reasignar/desasignar | Pendiente 07 | no hay endpoint en 03 |
| 03/CU-09 diagnóstico múltiple | Stub | requisitos + validación; 05/06/07 |

### Migraciones

- `2026_11_22_000000_create_especialidad_table.php`
- `2026_11_22_000100_create_profesional_table.php`
- `2026_11_22_000200_create_profesional_especialidad_table.php`
- `2026_11_22_000300_create_profesional_sucursal_table.php`

El esquema de referencia se mantiene sincronizado en `database/database.md` y `database/talitacumi_schema.sql`.

### Pendientes de fases siguientes

- Solo infraestructura: PDF/Excel (RF-U15, RF-R03, RF-B09, RF-D15, RF-E12, RF-A15), envío automático de contraseña temporal y plantilla de importación RT-08.
- Módulo 03: persistencia real de asignaciones en 07 y exportación PDF/Excel. Los contratos/stubs de Fase 3 ya están implementados.
- Importación inicial de profesionales desde planilla del cliente: pendiente de que exista la planilla; un CSV se puede procesar sin librerías externas.
- Consumidores de auditoría de datos sensibles y exportaciones en los módulos 04, 08, 09 y 11.

---

## Notas

- No se agregaron librerías de PDF/Excel ni se modificó ningún otro módulo de negocio.
- El login real está implementado en la Fase 1 del módulo 02 y el alcance real por sucursal está activo desde la Fase 2. La UI obtiene token mediante el interceptor y las páginas de 01/02/03 ya respetan las sucursales asignadas.
- `database/database.md` y `database/talitacumi_schema.sql` se actualizaron con la implementación real: `users` es la tabla física, Spatie administra roles/permisos y Sanctum usa `personal_access_tokens`. Las definiciones `usuario`/`rol`/`permiso`/`sesion` del SQL quedan como referencia aspiracional.
- Si se reconstruye la imagen con `--no-dev`, volver a ejecutar `composer install` dentro del contenedor para correr tests.
