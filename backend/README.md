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
- **Todavía no existe login real** (módulo 02). La UI Angular no puede obtener token; los tests usan `Sanctum::actingAs`.
- **Spatie Permission** es el mecanismo de permisos. Los roles base (`superadmin`, `administracion`, `recepcion`) se crean en `DatabaseSeeder`; cada submódulo crea sus permisos en su propio seeder (`OrganizacionSucursalesPermissionSeeder`, `OrganizacionSalasAmbientesPermissionSeeder`, `OrganizacionConfiguracionPermissionSeeder`).
- El código de aplicación **nunca decide por nombre de rol**: decide por permisos.
- Las aliases de middleware de Spatie no están registradas en `bootstrap/app.php`; se usa directamente `Spatie\Permission\Middleware\PermissionMiddleware::using(..., 'sanctum')`.

### Permisos del módulo de sucursales

| Código | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `sucursal.ver` | sí | sí | sí |
| `sucursal.crear` | sí | no | no |
| `sucursal.editar` (código/nombre) | sí | no | no |
| `sucursal.editar_contacto` (dirección/teléfono/email) | sí | sí | no |
| `sucursal.cambiar_estado` (activar/desactivar) | sí | no | no |
| `sucursal.alcance_global` (ver todas) | sí | no | no |

> La asignación usuario–sucursal (`usuario_sucursal`) pertenece al módulo 02. Mientras no exista, `UserBranchAccessContract` se bindea a `StubUserBranchAccessContract`, que lanza `ModulePendingException` y responde `503 MODULE_02_PENDING`. No se devuelven datos falsos ni “todas las sucursales” como atajo.

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

> Mientras el módulo 02 no formalice `usuario`, la migración de `bitacora.usuario_id` referencia `users.id`. Está documentado como deuda temporal en `database/database.md`.

### Contratos expuestos

**`BranchDirectoryContract`** — implementado por `EloquentBranchDirectory`:

```php
public function existe(int $sucursalId): bool;
public function estaActiva(int $sucursalId): bool;
public function asegurarActiva(int $sucursalId): void; // InactiveBranchException / BranchNotFoundException
```

Los módulos de inscripciones, turnos, pagos y caja deben llamar a `asegurarActiva()` antes de operar (RN-003).

**`UserBranchAccessContract`** — stub:

```php
public function accesibles(int $usuarioId): array;      // list<int>
public function puedeAcceder(int $usuarioId, int $sucursalId): bool;
```

`StubUserBranchAccessContract` lanza `ModulePendingException` (`LogicException`) con el mensaje `Módulo 02 Usuarios y roles pendiente`. **TODO módulo 02:** reemplazar el binding por una implementación real basada en `usuario_sucursal`.

### Estado del módulo

| Cobertura | Estado |
|---|---|
| RF-001, RF-002, RF-003, RF-007, RF-011, RF-013 | Implementados |
| RF-004, RF-005, RF-006, RF-014 | Implementados con alcance global; alcance por usuario bloqueado por módulo 02 |
| RF-008 | Rechazo explícito de eliminación; chequeo real de asociaciones pendiente |
| RF-009, RF-010 | Stub/contrato; dependen del módulo 02 |
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

El alcance por sucursal reutiliza `UserBranchAccessContract` y `sucursal.alcance_global`. Sin alcance global se responde `503 MODULE_02_PENDING`; nunca se devuelven todas las sucursales como atajo.

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
| RF-008, RF-009, RF-010, RF-017 | Implementados con alcance global; alcance por usuario bloqueado por módulo 02 |
| RF-012 | Rechazo explícito de eliminación; chequeo real de asociaciones pendiente |
| RF-013, RF-014 | Contratos implementados; consumidores 06/07 pendientes |
| RF-016 (PDF/Excel) | Pendiente; sin librerías y sin botón |
| RN-001, RN-002, RN-003, RN-004, RN-008, RN-010 | Implementadas |
| RN-005, RN-006 | Contratos implementados; consumidores pendientes |
| RN-007 | Parcial: no hay borrado físico; falta chequeo de asociaciones |
| RN-009 | No se implementa aquí; solo se referencia `area` sin administrar colores |
| RN-011 | Parcial: permisos + 503 módulo 02 |

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

## Notas

- No se agregaron librerías de PDF/Excel ni se modificó ningún otro módulo de negocio.
- El login y la autenticación real pertenecen al módulo 02; hasta entonces la UI de sucursales mostrará `401` si intenta consumir la API sin token.
- Si se reconstruye la imagen con `--no-dev`, volver a ejecutar `composer install` dentro del contenedor para correr tests.
