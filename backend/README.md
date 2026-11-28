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
| API a través de Nginx | `http://localhost:8090/api` |
| Healthcheck API + BD | `http://localhost:8090/api/health` |
| PostgreSQL desde el host | `localhost:5434` |

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

Módulos actuales: `Organizacion`, `Seguridad`, `Profesionales`, `Pacientes`, `CatalogoServicios`, `InscripcionesAtenciones` y `AgendaTurnos`.

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
  - `docs/04_PACIENTES_TUTORES/03_ficha_ingreso_entrevista.md` — implementado en 04.03 (ficha, revisiones, consentimientos y observaciones).
  - `docs/04_PACIENTES_TUTORES/04_busqueda_ficha_resumen.md` — implementado en 04.04 (`recurso=ficha_resumen`).
  - `docs/11_EVALUACIONES_RESULTADOS/04_acceso_informacion_clinica.md` — pendiente del módulo 11.
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
| `ProfessionalScheduleContract` | `haySolapamiento(profesionalId, inicio, fin)`, `tieneAsignacionesFuturas(profesionalId, desde)` | Implementación real en 07.05: `App\Modules\AgendaTurnos\Contracts\EloquentProfessionalSchedule`. Consulta turnos vigentes de todas las sucursales y no expone datos de otras sucursales. |
| `ProfessionalDiagnosticRequirementsContract` | `especialidadesRequeridas(tipoDiagnosticoId)` | `StubProfessionalDiagnosticRequirementsContract` lanza `LogicException('Módulo 05/06 — Diagnósticos no implementado todavía.')` |
| `ProfessionalExporterContract` | `exportarDirectorio`, `exportarMatriz`, `exportarAsignaciones` | `UnavailableProfessionalExporter` lanza `LogicException('Exportación PDF/Excel pendiente de infraestructura.')` |

Los stubs están bindeados en `ProfesionalesServiceProvider`. Las Actions de desactivación **no** llaman todavía a `tieneAsignacionesFuturas()`: hacerlo haría fallar la operación hasta que exista 07. El límite queda documentado.

#### TODOs exactos para módulos consumidores

- **07 — Agenda y turnos** (`docs/07_AGENDA_TURNOS/02_programacion_turnos_patrones.md`, `03_reprogramacion_cancelacion_turnos.md`, `05_diagnostico_varios_profesionales.md`):
  - implementar `ProfessionalScheduleContract` con turnos reales (hecho en 07.05: `EloquentProfessionalSchedule`);
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

## Módulo 04 — Pacientes y tutores (04.01 Fase 1 + 04.02 Fases A y B + export CSV)

Implementa el registro de pacientes (04.01) y las Fases A y B del registro de tutores y contactos (04.02): alta, edición, listado y ficha standalone de tutores, asociación, tutor principal, activación/desactivación, parentesco por relación, sucursal de registro del tutor, duplicados, enmascarado de teléfonos, auditoría y contactos de emergencia adicionales por paciente. Los pacientes y tutores son globales; `sucursal_registro_id` es el ancla de visibilidad, no una duplicación por sucursal. No hay SoftDeletes: el retiro es `estado = retirado` y los tutores se desactivan con motivo.

La exportación de tutores (04.02/RF-016) y el consumo del teléfono por la alerta de próximo pago (04.02/RF-013, módulo 09) quedan para la Fase C.

### Estructura

```text
app/Modules/Pacientes/
├── Actions/Registro/       Alta, edición, estado y rechazo de eliminación
├── Actions/Tutores/        Alta, edición, estado, asociación y desasociación
├── Actions/Contactos/      Alta, edición y eliminación de contactos de emergencia
├── Contracts/              Directorio de pacientes, tutores, stubs y exportación
├── Enums/                  EstadoPaciente, Sexo, TipoCoincidencia
├── Events/                 Eventos de paciente y de tutores
├── Exceptions/             Errores de dominio con render HTTP
├── Http/Controllers/       Registro de pacientes y tutores
├── Http/Requests/          Validación y permisos por campo
├── Http/Resources/         Paciente y tutor
├── Listeners/              RecordPacientesAudit
├── Models/                 Paciente, Tutor, Parentesco, PacienteTutor, ContactoEmergencia, historial y bitácora
├── Providers/              PacientesServiceProvider
├── Queries/                Listados, ficha y opciones
├── Rules/                  Reglas puras
├── Support/                Permisos, alcance, snapshots, normalización, edad y duplicados
├── routes-registro.php
└── routes-tutores.php
```

`PacientesServiceProvider` se registra después de `ProfesionalesServiceProvider`. Prefijo público: `App\Modules\Pacientes\Support\PacientesModule::MODULE_URL = '/pacientes'`. Prefijo real de la API: `/api/pacientes`; los endpoints de la tabla se muestran relativos a ese prefijo (`/pacientes` → `/api/pacientes/pacientes`, `/tutores` → `/api/pacientes/tutores`).

### Endpoints

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/pacientes` | `ListPacientesQuery` | `paciente.ver` |
| GET | `/pacientes/opciones` | `GetPacienteFormOptionsQuery` | `paciente.ver` |
| GET | `/pacientes/{id}` | `GetPacienteQuery` + `AuditRecorderContract` | `paciente.ver` |
| POST | `/pacientes` | `CreatePacienteAction` | `paciente.crear` |
| PATCH | `/pacientes/{id}` | `UpdatePacienteAction` | `paciente.editar` (+ `paciente.ver_sensibles` si envía campos sensibles) |
| POST | `/pacientes/{id}/estado` | `ChangePacienteStatusAction` | `paciente.cambiar_estado` |
| DELETE | `/pacientes/{id}` | `RejectPacienteDeletionAction` → 409 | `paciente.cambiar_estado` |
| GET | `/pacientes/{id}/tutores` | `ListPacienteTutoresQuery` | `paciente.ver` |
| POST | `/pacientes/{id}/tutores` | `AssociateTutorAction` | `tutor.asociar` |
| POST | `/pacientes/{id}/tutores/{tutorId}/principal` | `SetPrincipalTutorAction` | `tutor.asociar` |
| DELETE | `/pacientes/{id}/tutores/{tutorId}` | `DissociateTutorAction` | `tutor.asociar` |
| GET | `/pacientes/{id}/contactos-emergencia` | `ListContactosEmergenciaQuery` | `paciente.ver_contacto_emergencia` |
| POST | `/pacientes/{id}/contactos-emergencia` | `CreateContactoEmergenciaAction` | `paciente.editar_contacto_emergencia` |
| PATCH | `/pacientes/{id}/contactos-emergencia/{contactoId}` | `UpdateContactoEmergenciaAction` | `paciente.editar_contacto_emergencia` |
| DELETE | `/pacientes/{id}/contactos-emergencia/{contactoId}` | `DeleteContactoEmergenciaAction` | `paciente.editar_contacto_emergencia` |
| GET | `/tutores` | `ListTutoresQuery` | `tutor.ver` |
| GET | `/tutores/opciones` | `GetTutorFormOptionsQuery` | `tutor.ver` |
| GET | `/tutores/exportar` | `TutorCsvExporter` + `AuditRecorderContract` | `tutor.exportar` |
| GET | `/tutores/{id}` | `GetTutorQuery` + consulta auditada | `tutor.ver` |
| POST | `/tutores` | `CreateTutorAction` | `tutor.crear` |
| PATCH | `/tutores/{id}` | `UpdateTutorAction` | `tutor.editar` o `tutor.editar_contacto` según campos |
| POST | `/tutores/{id}/estado` | `ChangeTutorStatusAction` | `tutor.cambiar_estado` |
| DELETE | `/tutores/{id}` | `RejectTutorDeletionAction` → 409 | `tutor.editar` |

Parámetros de pacientes: `search` (nombre/documento/código), `estado`, `tipo_diagnostico_id`, `sin_diagnostico`, `sucursal_registro_id`, `page`, `per_page` (máx. 50), `sort` (`nombre_completo|codigo|fecha_nacimiento|estado|updated_at`), `direction`. `sucursal_inscripcion_id` está **prohibido** hasta el módulo 06.

Parámetros de tutores: `search` (nombre/documento/teléfono), `activo`, `sucursal_registro_id`, `page`, `per_page` (máx. 50), `sort` (`nombre_completo|ci|activo|updated_at`), `direction`.

La respuesta de pacientes incluye `data`, `links`, `meta` y `can` de pantalla. Cada fila incluye `can` (`edit`, `change_status`, `view_sensibles`, `delete=false`, `view_tutors`, `manage_tutors`, `view_emergency_contacts`, `manage_emergency_contacts`). La ficha incluye `edad`, `sucursal_registro`, `sin_diagnostico_definido`, `observaciones`, `sensibles_protegidos`, `tutores_disponibles`, `tutores`, `contactos_emergencia` y `can`. Los contactos solo se envían si el actor tiene `paciente.ver_contacto_emergencia`; sin ese permiso la sección no se expone.

La respuesta de tutores incluye `data`, `links`, `meta` y `can` (`create`, `edit_data`, `edit_contact`, `change_status`, `view_contact`, `export`, `delete=false`, `manage_tutors`, `view_patients`). El listado enmascara teléfonos como `712***67` salvo `tutor.ver_contacto`; la ficha devuelve el valor completo a quien tiene ese permiso. La ficha incluye `pacientes` solo si el actor tiene `paciente.ver` y respeta su alcance por sucursal; nunca devuelve conteos de pacientes ocultos.

### Permisos

Seeders: `PacientesPermissionSeeder` (marca `pacientes`), `TutoresPermissionSeeder` (marca `pacientes.tutores`) y `ContactosEmergenciaPermissionSeeder` (marca `pacientes.contactos_emergencia`). Las matrices son independientes para no revertir cambios del Superadmin al re-sembrar.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `paciente.ver` | sí | sí | sí |
| `paciente.crear` | sí | no | sí |
| `paciente.editar` | sí | sí | sí |
| `paciente.cambiar_estado` | sí | no | sí |
| `paciente.ver_sensibles` | sí | sí | sí |
| `paciente.exportar` (reservado) | sí | sí | sí |
| `tutor.ver` | sí | sí | sí |
| `tutor.crear` | sí | no | sí |
| `tutor.editar` (identidad + contacto) | sí | no | sí |
| `tutor.editar_contacto` (teléfonos, email, dirección) | sí | sí | no |
| `tutor.ver_contacto` (valores completos) | sí | sí | sí |
| `tutor.asociar` (asociar, desasociar, principal) | sí | no | sí |
| `tutor.cambiar_estado` | sí | no | no |
| `tutor.exportar` | sí | sí | no |
| `paciente.ver_contacto_emergencia` | sí | sí | sí |
| `paciente.editar_contacto_emergencia` | sí | sí | sí |

No se creó `paciente.eliminar` ni `tutor.eliminar`. La aplicación decide por permiso, nunca por nombre de rol. Administración edita solo contacto y no crea tutores ni cambia estado; Recepción crea y edita completamente, asocia y no cambia estado; solo Superadmin activa/desactiva. El `DELETE` de tutor siempre responde `409 TUTOR_DELETE_NOT_ALLOWED` para quien tiene `tutor.editar`. Administración, Recepción y Superadmin gestionan contactos de emergencia; un rol con `paciente.ver` pero sin `paciente.ver_contacto_emergencia` no los recibe en la ficha y recibe 403 en el endpoint.

### Estados y reglas

- Enum `EstadoPaciente`: `activo`, `en_pausa`, `retirado` (04.01/RN-004).
- Transiciones: activo ↔ en_pausa; activo → retirado; en_pausa → retirado; retirado → activo.
- Motivo obligatorio al retirar, al volver de retirado y al pasar a pausa (04.01/RN-005 + supuesto 06.05/RF-006). Opcional al reactivar desde pausa.
- `version` optimista: `409 PATIENT_VERSION_CONFLICT`.
- `DELETE` siempre responde `409 PATIENT_DELETE_NOT_ALLOWED`; no hay borrado físico ni SoftDeletes. El chequeo real de historial queda pendiente de 06/07/08.
- Activación exige tutor principal (`TutorDirectoryContract::asegurarTutorPrincipal`); si falta, `409 PATIENT_TUTOR_REQUIRED`.
- La búsqueda/unicidad usa columnas normalizadas en PHP (sin acentos, minúsculas, espacios colapsados). No se usan `unaccent` ni `pg_trgm`.
- Documento opcional; único cuando existe (`documento_normalizado`). Sin documento se permiten múltiples NULL.
- `codigo` se genera como `PAC-` + id de 6 dígitos.

### Duplicados y privacidad

- **Bloqueo duro:** mismo documento normalizado → `409 PATIENT_DOCUMENT_DUPLICATE`, sin override.
- **Confirmable:** mismo nombre + apellidos + fecha de nacimiento (o identidad parcial con el mismo tutor principal cuando existe) → `409 POTENTIAL_DUPLICATE` con `data.requiere_confirmacion=true`.
- La confirmación requiere `motivo_confirmacion`; se audita como `CONFIRMAR_DUPLICADO`.
- Si la coincidencia está fuera del alcance, **no** se devuelven id, nombre, documento ni datos clínicos; solo `data.fuera_de_alcance=true` o `data.coincidencias_fuera_de_alcance=true`.
- La ficha de un paciente fuera de alcance responde `404 PATIENT_NOT_FOUND` para no revelar su existencia.

### Reglas de tutores (04.02 Fase A)

- **Parentesco híbrido:** catálogo `parentesco`; `tutor.parentesco_id` es el valor de referencia y `paciente_tutor.parentesco_id` es el autoritativo por relación. Al asociar se puede indicar otro parentesco; si se omite, se usa el del tutor. El texto legacy `tutor.parentesco` se conserva por compatibilidad y se resuelve contra el catálogo al crear/editar.
- **Sucursal de registro del tutor:** `tutor.sucursal_registro_id` (home branch). Es obligatoria cuando el usuario tiene alcance global o varias sucursales; con una sola sucursal accesible se toma automáticamente. No se cambia desde la edición.
- **Visibilidad fail-closed:** un tutor es visible por su home branch **o** por la sucursal de un paciente asociado. Un tutor sin pacientes y sin home branch solo lo ve el alcance global. El filtro por sucursal usa la misma regla.
- **Identidad compartida:** si el tutor está asociado a sucursales fuera del alcance del actor, este solo puede editar contacto; nombre, documento y parentesco de referencia responden `403 TUTOR_IDENTITY_OUT_OF_SCOPE`.
- **Estados:** desactivar exige `motivo` (`422 STATUS_REASON_REQUIRED`); reactivar no. No se permite el mismo estado (`409 TUTOR_STATUS_UNCHANGED`). Un tutor inactivo no puede asociarse ni designarse principal (`409 TUTOR_INACTIVE`). No se puede desactivar al principal de un paciente activo (`409 TUTOR_PRINCIPAL_ACTIVE_PATIENT`): primero hay que designar otro principal.
- **Eliminación:** siempre `409 TUTOR_DELETE_NOT_ALLOWED`; los tutores se desactivan y conservan historial.
- **Unicidad:** documento normalizado único si existe; si no, nombre completo normalizado + teléfono principal normalizado. El índice único refuerza carreras y `UniqueViolationTranslator` devuelve `409 TUTOR_DUPLICATE`. Los duplicados fuera de alcance no exponen id, nombre ni teléfono.
- **Teléfonos:** se normalizan `+591`, `591` y `0` inicial. Principal: 8 dígitos que empiezan en 6 o 7. Alternativo: 8 dígitos que empiezan en 2, 3, 4, 6 o 7. Formato propuesto a validar con el cliente.
- **Enmascarado:** listados y respuestas sin `tutor.ver_contacto` muestran `712***67`; email y dirección se omiten. La ficha y la ficha de paciente devuelven valores completos con el permiso.
- **Tutor principal:** exactamente uno por paciente; índice parcial `(paciente_id) WHERE es_principal`. Reemplazar al principal exige `motivo` (`422 TUTOR_PRINCIPAL_REASON_REQUIRED`) y se audita. Un paciente activo no puede quedar sin principal (`409 TUTOR_PRINCIPAL_REQUIRED`).

### Alcance por sucursal

- Paciente global; visibilidad inicial por `sucursal_registro_id ∈ sucursales accesibles`.
- Sin `sucursal.alcance_global`, el listado se filtra; sin sucursales asignadas devuelve lista vacía y el alta responde `403 BRANCH_ACCESS_DENIED`.
- Un filtro por sucursal fuera de alcance devuelve lista vacía; un alta con sucursal ajena responde 403.
- El filtro por “sucursal de inscripción vigente” (04.01/RF-005, 04.01/CA-010) queda deshabilitado hasta el módulo 06. `PatientEnrollmentDirectoryContract` está stubeado y no se invoca en listado/ficha.
- Los tutores son globales; el buscador muestra tutores por su home branch o por pacientes de las sucursales accesibles, y tutores todavía sin pacientes si su home branch está en alcance.

### Eventos y bitácora

`RecordPacientesAudit` es síncrono y fail-closed (`503 AUDIT_UNAVAILABLE`). Escribe `modulo='pacientes'`, `resultado='exito'`, `usuario_id`, `ip` y una fila por sucursal visible. Para pacientes y asociaciones se usa la sucursal del paciente; para cambios de tutor se usa la home branch + las sucursales de sus pacientes asociados (sin duplicados).

| Entidad (`bitacora.entidad`) | Acciones | `critico` |
|---|---|---|
| `paciente` | `CREAR`, `ACTUALIZAR`, `CAMBIAR_ESTADO`, `CONSULTAR`, `CONFIRMAR_DUPLICADO` | `true` al retirar y al volver de retirado; `false` en pausa/resto |
| `tutor` | `CREAR`, `ACTUALIZAR`, `CAMBIAR_ESTADO`, `CONSULTAR` | `true` al desactivar; `false` al reactivar/resto |
| `paciente_tutor` | `ASOCIAR`, `DESASOCIAR`, `CAMBIAR_PRINCIPAL` | `false` |
| `contacto_emergencia` | `CREAR`, `ACTUALIZAR`, `ELIMINAR` | `false` |

**Qué NO se guarda en los snapshots:** contenido de `alergias`, `condiciones_medicas` ni `observaciones`. Se guardan booleanos `tiene_alergias`, `tiene_condiciones_medicas`, `tiene_observaciones`, la lista `campos_sensibles_modificados` y valores no clínicos (nombre, apellidos, fecha, sexo, documento, diagnóstico por id, sucursal, estado, versión). Los motivos de estado se guardan en `motivo` porque 04.01/RN-005 lo exige; no se copian a `datos_nuevos`.

Para tutores, la bitácora guarda valores completos de nombre, documento, teléfonos, email y dirección en `datos_anteriores`/`datos_nuevos` (04.02/CA-005). La consulta de la ficha **no** copia teléfono ni dirección en `contexto`; solo `{recurso: 'ficha_tutor'}`.

`PacientesAccionAuditadaSeeder`, `TutoresAccionAuditadaSeeder` y `ContactosEmergenciaAccionAuditadaSeeder` agregan las acciones al catálogo `accion_auditada` sin modificar el seeder de Seguridad. `AuditModuleResolver` ya resuelve `paciente`/`tutor` → `pacientes`; no se modificó código de Seguridad. Los contactos de emergencia usan `entidad='contacto_emergencia'` y la sucursal de registro del paciente.

### Auditoría de consulta

Solo `GET /pacientes/{id}` y `GET /tutores/{id}` llaman a `AuditRecorderContract::registrarConsultaSensible(...)`. Los listados no auditan. Si el recorder falla, la ficha responde `503 AUDIT_UNAVAILABLE` y no se devuelve contenido (fail-closed). Cada apertura de ficha desde el frontend es un GET nuevo; no hay prefetch. La consulta del tutor registra la primera sucursal accesible que otorga visibilidad (home branch o paciente asociado).

### Contrato expuesto — `PatientDirectoryContract`

```php
public function existe(int $pacienteId): bool;

public function resumen(int $pacienteId): PacienteResumen;
// PacienteNotFoundException

public function asegurarPermiteInscripcion(int $pacienteId): void;
// PacienteNoInscribibleException (409 PATIENT_NOT_INSCRIBIBLE)

public function asegurarPermiteTurno(int $pacienteId): void;
// PacienteNoAgendableException (409 PATIENT_NOT_SCHEDULABLE)
```

`PacienteResumen` devuelve `id`, `codigo`, `nombre_completo`, `fecha_nacimiento`, `edad_anios`, `estado`, `sucursal_registro_id`, `tipo_diagnostico_id`, `sin_diagnostico_definido`. No expone datos sensibles.

Ejemplo para 06/07/08/11:

```php
$directorio = app(PatientDirectoryContract::class);

try {
    $directorio->asegurarPermiteInscripcion($pacienteId);
} catch (PacienteNoInscribibleException $e) {
    // $e->motivoCodigo(): PATIENT_RETIRED | PATIENT_PAUSED
}
```

Documentos consumidores exactos:

- 06 — `docs/06_INSCRIPCIONES_ATENCIONES/01_inscripcion_salas_grupales.md`, `02_paquetes_sesiones_individuales.md`, `04_vigencia_vencimiento_renovacion.md`, `05_estados_paciente_inscripcion.md`.
- 07 — `docs/07_AGENDA_TURNOS/02_programacion_turnos_patrones.md`, `03_reprogramacion_cancelacion_turnos.md`.
- 08 — `docs/08_PAGOS_CAJA/01_registro_pagos_saldos.md`, `04_anulacion_pagos.md`, `05_plan_cuotas.md`.
- 11 — `docs/11_EVALUACIONES_RESULTADOS/01_informe_entrevista.md`, `02_resultados_diagnostico.md`, `04_acceso_informacion_clinica.md`.
- 05 — `docs/05_CATALOGO_SERVICIOS_TARIFAS/03_tipos_diagnostico_evaluacion.md`.

No se tocaron esos módulos.

### Stubs y pendientes

| Contrato | Implementación | Mensaje |
|---|---|---|
| `DiagnosticTypeDirectoryContract` | `StubDiagnosticTypeDirectoryContract` | `Módulo 05 — Catálogo de tipos de diagnóstico no implementado todavía.` |
| `PatientEnrollmentDirectoryContract` | `StubPatientEnrollmentDirectoryContract` | `Contrato de resumen de inscripciones del paciente aún no implementado por 06.` |
| `PacienteExporterContract` | `UnavailablePacienteExporter` | `Exportación PDF/Excel pendiente de infraestructura.` |

- `TutorDirectoryContract` tiene implementación real (`EloquentTutorDirectory`): `existe`, `estaActivo`, `resumen`, `tutoresDePaciente`, `tieneTutorPrincipal`, `tutorPrincipal` y `asegurarTutorPrincipal`. `tieneTutorPrincipal` y `tutorPrincipal` exigen que el principal esté **activo** (04.02/RN-007); `tutorPrincipal` devuelve `null` si el principal está inactivo. `TutorResumen` incluye el parentesco de la relación.
- La Action rechaza `tipo_diagnostico_id` no nulo mientras `DIAGNOSTICOS_DISPONIBLES=false`; el stub no se invoca en flujos normales.
- El listado y la ficha no invocan los stubs de inscripciones ni de diagnóstico.
- Exportación de tutores: `GET /tutores/exportar` genera un CSV compatible con Excel, respeta filtros y alcance, enmascara el teléfono sin `tutor.ver_contacto` y audita la exportación con cantidad y filtros. El PDF queda pendiente de infraestructura.
- Importación CSV: no implementada; no hay planilla del cliente. Técnicamente viable con `fgetcsv` sin librerías.
- 04.02 Fase C parcial: exportación CSV de tutores implementada; PDF y consumo del teléfono por la alerta de próximo pago (módulo 09) quedan pendientes. Los contactos de emergencia de la Fase B están implementados.
- Cifrado de campo (`encrypted`) no implementado por defecto; HTTPS + cifrado de infraestructura/backups. Si el cliente lo exige, se decide en una fase posterior.

### Migraciones y seeders

- `2026_11_23_000000_create_tutor_table.php`
- `2026_11_23_000100_create_paciente_table.php`
- `2026_11_23_000200_create_paciente_tutor_table.php`
- `2026_11_23_000300_create_estado_paciente_historial_table.php`
- `2026_11_24_000000_create_parentesco_table.php`
- `2026_11_24_000100_add_tutor_fields_table.php` (home branch, parentesco de referencia, unicidad nombre+teléfono y backfill)
- `2026_11_24_000200_add_parentesco_to_paciente_tutor_table.php` (recrea el índice parcial de principal en SQLite)
- `2026_11_24_000300_create_contacto_emergencia_table.php`
- Seeders: `PacientesPermissionSeeder`, `PacientesAccionAuditadaSeeder`, `ParentescoCatalogoSeeder`, `TutoresPermissionSeeder`, `TutoresAccionAuditadaSeeder`, `ContactosEmergenciaPermissionSeeder`, `ContactosEmergenciaAccionAuditadaSeeder`.
- `database/database.md` y `database/talitacumi_schema.sql` quedan sincronizados. El .sql no aplica el trigger genérico sobre `paciente`/`tutor` para no duplicar la bitácora ni copiar contenido clínico.

### Checklist 04.02 Fases A y B

| Requisito | Estado | Evidencia |
|---|---|---|
| 04.02/RF-001 crear tutor | Completo | `CreateTutorAction`, `TutorCoreTest`, `TutorAuthorizationTest` |
| 04.02/RF-002 campos obligatorios + teléfono | Completo | `StoreTutorRequest`, `TutorPhoneRuleTest` |
| 04.02/RF-003 unicidad documento / nombre+teléfono | Completo | `TutorDuplicateDetector`, `UniqueViolationTranslator`, `TutorDataProtectionTest` |
| 04.02/RF-004 asociar tutores | Completo | `AssociateTutorAction`, `PacienteTutorController` |
| 04.02/RF-005 tutor principal único + motivo al reemplazar | Completo | `TutorPrincipalRule`, `TutorPrincipalChangeRule`, tests |
| 04.02/RF-006 desasociar sin dejar activo sin principal | Completo | `DissociateTutorAction`, `TutorCoreTest` |
| 04.02/RF-007 listar/buscar por alcance | Completo | `ListTutoresQuery`, `TutorAccessScope`, tests |
| 04.02/RF-008 ficha con pacientes y estado | Completo | `GetTutorQuery`, `TutorResource`, `TutorContractTest` |
| 04.02/RF-009 editar con bitácora | Completo | `UpdateTutorAction`, `TutorAuditTest` |
| 04.02/RF-010 activar/desactivar con motivo | Completo | `ChangeTutorStatusAction`, `TutorStatusTest` |
| 04.02/RF-011 bloquear eliminación | Completo | `RejectTutorDeletionAction`, `TutorStatusTest` |
| 04.02/RF-014 bitácora completa | Completo | `RecordPacientesAudit`, `TutorAuditTest` |
| 04.02/RF-015 alcance por rol/sucursal | Completo (proxy paciente) | `TutorAccessScope`; inscripción 06 pendiente |
| 04.02/RNF-005 enmascarado de teléfono | Completo | `PhoneMask`, `TutorDataProtectionTest` |
| 04.02/RNF-012 catálogo de parentescos | Completo | `parentesco`, `ParentescoResolver`, `TutorParentescoTest` |
| 04.02/RF-012 contactos de emergencia | Completo | `ContactoEmergencia`, `PacienteContactoEmergenciaController`, `ContactoEmergenciaTest` |
| 04.02/RF-013 teléfono para módulo 09 | Fase C | contrato listo; consumidor 09 pendiente |
| 04.02/RF-016 exportar tutores | Completo (CSV; PDF pendiente) | `TutorCsvExporter`, `TutorExportTest`, botón en frontend |

### Checklist Fase 1 (numeración 04.01)

| Requisito | Estado | Evidencia |
|---|---|---|
| 04.01/RF-001 crear paciente | Completo | `CreatePacienteAction`, `PacienteCrudTest` |
| 04.01/RF-002 calcular edad | Completo | `PacienteAgeCalculator`, unit tests |
| 04.01/RF-003 unicidad | Completo | documento + nombre/fecha/tutor, tests |
| 04.01/RF-004 obligatorios + tutor | Completo | `StorePacienteRequest`, `PacienteTutorRequirementRule` |
| 04.01/RF-005 listar/buscar | Parcial | filtro de inscripción pendiente 06; diagnóstico deshabilitado hasta 05 |
| 04.01/RF-006 ficha | Completo | `GetPacienteQuery`, `PacienteResource` |
| 04.01/RF-007 editar | Completo | `UpdatePacienteAction`, auditoría |
| 04.01/RF-008 cambiar estado | Completo | `ChangePacienteStatusAction`, tests |
| 04.01/RF-009 bloquear eliminación | Parcial | DELETE 409; historial real 06/07/08 |
| 04.02/RF-004 asociar tutores | Completo | `AssociateTutorAction`, principal |
| 04.01/RF-011 observaciones/condiciones | Completo | campos + `ver_sensibles` |
| 04.01/RF-012 bitácora de cambios | Completo | `RecordPacientesAudit` |
| 04.01/RF-013 bitácora de consultas | Completo | `PacienteConsultationAuditTest` |
| 04.01/RF-014 restringir acceso | Completo | `PacienteAccessScope`, tests |
| 04.01/RF-015 exportar | Pendiente infraestructura | stub, sin botón |
| 04.01/RF-016 alerta duplicado | Completo | `PacienteDuplicateDetector`, tests |
| 04.01/RN-001…RN-011 | Completo/Parcial | ver notas de 05/06/07/08 |
| 04.01/CU-01…CU-03 | Completo | actions, tests |
| 04.01/CA-001…CA-010 | Completo/Parcial | CA-007 contrato; CA-010 registro; inscripción 06 |
| 04.01/FP-01…FP-04 | Completo/Parcial | FP-02 parcial por filtros 05/06 |

### Decisiones tomadas

- Tutores: se implementó 04.02 Fase A (incluye ficha/listado standalone, estados, parentesco por relación, home branch, duplicados, enmascarado y auditoría). No se inventó un cuarto estado ni se permitió un paciente activo sin tutor.
- Visibilidad: `sucursal_registro_id` + fail-closed. Ficha fuera de alcance = 404. Inscripción vigente se suma cuando exista 06.
- Duplicados de paciente: documento = bloqueo duro; nombre+fecha (+tutor) = confirmable; fuera de alcance no filtra datos.
- Duplicados de tutor: documento o nombre+teléfono = bloqueo duro; fuera de alcance no filtra id/nombre/teléfono.
- Auditoría de consulta: fail-closed 503 en fichas de paciente y tutor.
- Edad: se calcula en lectura con `zona_horaria`; formato días/meses/años.
- Diagnóstico: opcional al alta; FK diferida hasta 05.
- Sin cifrado de campo por defecto; sin exportación; sin importación CSV.
- No se modificó código de 01/02/03 ni el catálogo de Seguridad.

---

## Módulo 04.03 — Ficha de ingreso (Fase A)

Implementa la ficha **administrativa y de contexto** del ingreso. No implementa la entrevista terapéutica del módulo 06 ni el registro clínico del módulo 11.

### Alcance de la Fase A

- Crear ficha en borrador, consultar, editar y completar.
- Archivar y reactivar con motivo.
- Una sola ficha no archivada por paciente.
- Persona que deriva.
- Campos sensibles protegidos.
- Revisiones inmutables y bitácora sin contenido clínico.
- Auditoría de consulta de ficha y revisiones.
- Contactos de emergencia existentes, solo lectura dentro de la ficha.
- Stubs de entrevista 06 y exportación PDF.

### Endpoints

Prefijo real: `/api/pacientes`.

| Método | Ruta | Acción / Query | Permiso |
|---|---|---|---|
| GET | `/pacientes/{id}/ficha-ingreso` | `GetFichaIngresoQuery` + `ListFichaIngresoHistorialQuery` | `ficha_ingreso.ver` |
| GET | `/pacientes/{id}/ficha-ingreso/{fichaId}` | `GetFichaIngresoQuery` + consulta auditada | `ficha_ingreso.ver` |
| POST | `/pacientes/{id}/ficha-ingreso` | `CreateFichaIngresoAction` | `ficha_ingreso.crear` |
| PATCH | `/pacientes/{id}/ficha-ingreso/{fichaId}` | `UpdateFichaIngresoAction` | `ficha_ingreso.editar` |
| POST | `/pacientes/{id}/ficha-ingreso/{fichaId}/completar` | `CompleteFichaIngresoAction` | `ficha_ingreso.editar` |
| POST | `/pacientes/{id}/ficha-ingreso/{fichaId}/archivar` | `ArchiveFichaIngresoAction` | `ficha_ingreso.archivar` |
| POST | `/pacientes/{id}/ficha-ingreso/{fichaId}/reactivar` | `ReactivateFichaIngresoAction` | `ficha_ingreso.reactivar` |
| GET | `/pacientes/{id}/ficha-ingreso/{fichaId}/revisiones` | `GetFichaIngresoRevisionesQuery` + auditoría | `ficha_ingreso.ver` |
| DELETE | `/pacientes/{id}/ficha-ingreso/{fichaId}` | `RejectFichaIngresoDeletionAction` → 409 | `ficha_ingreso.editar` |

`GET` devuelve `data` de la ficha vigente o `null`, `paciente` (resumen no sensible) e `historial` de fichas archivadas. El listado no existe en Fase A.

### Reglas

- `paciente_id` existente; la ficha siempre pertenece a un paciente.
- `motivo_consulta_inicial` y `fecha_ingreso` obligatorios.
- Fecha de ingreso no futura; se usa `zona_horaria` de 01 vía `PacienteAgeCalculator`.
- Estados: `borrador`, `completa`, `archivada`.
- “Completa” no agrega campos obligatorios nuevos hasta validar con el cliente. **PROPUESTO - VALIDAR.**
- Borrador exige motivo y fecha; los demás campos son opcionales.
- Archivada bloquea edición, completar y nuevas observaciones/consentimientos. Reactivar solo Superadmin y vuelve al estado anterior con motivo.
- DELETE siempre `409 FICHA_DELETE_NOT_ALLOWED`. El chequeo real de inscripciones/turnos/pagos queda pendiente de 06/07/08.
- `tutor_principal_id` es un snapshot nullable; la ficha se permite sin tutor, pero los consentimientos quedan bloqueados hasta asociar un tutor activo.
- `sucursal_ingreso_id` obligatoria; por defecto la sucursal de registro del paciente. El alcance exige paciente accesible y sucursal de la ficha accesible; si no, `404 FICHA_NOT_FOUND`.
- `version` optimista: `409 FICHA_VERSION_CONFLICT`. Crear una segunda ficha abierta: `409 FICHA_ALREADY_EXISTS`.
- Prefijo documental: todo usa `04.03/`.

### Campos sensibles (propuesta a confirmar)

`ficha_ingreso.ver_sensibles` protege:

- `motivo_consulta_inicial`
- `composicion_familiar`
- `antecedentes_familiares`
- `observaciones`
- `derivado_por_contacto`
- `motivo_archivado`

Sin permiso, el backend omite los valores y devuelve `sensibles_protegidos=true` y `campos_protegidos`. Editar campos sensibles exige `ficha_ingreso.editar` + `ficha_ingreso.ver_sensibles`.

### Revisiones y bitácora

`ficha_ingreso_revision` guarda un snapshot JSON completo e inmutable por versión, con `accion`, `version`, `campos_modificados` y actor. El snapshot solo se expone con `ficha_ingreso.ver_sensibles`; sin ese permiso se devuelven metadatos y `contenido_protegido=true`.

La bitácora guarda:

- `entidad='ficha_ingreso'`.
- Acciones `CREAR`, `ACTUALIZAR`, `COMPLETAR`, `ARCHIVAR`, `REACTIVAR`, `CONSULTAR`, `EXPORTAR` (reservada).
- Ids, estado, fechas, versión, `campos_modificados`, `revision_id` y banderas `tiene_*`.
- **No** guarda motivo de consulta, composición, antecedentes, observaciones ni contacto del derivante.
- Una fila por sucursal visible: sucursal de ingreso + sucursal de registro del paciente.
- `critico=true` al archivar. El motivo de archivo/reactivación se guarda en la columna `motivo` de la bitácora para trazabilidad; en la ficha queda protegido por `ver_sensibles`.

La consulta de ficha y de revisiones usa `AuditRecorderContract::registrarConsultaSensible`; el listado/historial no genera una auditoría adicional. El fallo del recorder responde `503 AUDIT_UNAVAILABLE`.

### Permisos

Seeder: `FichaIngresoPermissionSeeder` con `InitialPermissionMatrix` y marca `pacientes.ficha_ingreso`.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `ficha_ingreso.ver` | sí | sí | sí |
| `ficha_ingreso.crear` | sí | no | sí |
| `ficha_ingreso.editar` | sí | sí | sí |
| `ficha_ingreso.archivar` | sí | sí | no |
| `ficha_ingreso.reactivar` | sí | no | no |
| `ficha_ingreso.ver_sensibles` | sí | sí | sí |
| `ficha_ingreso.exportar` (reservado) | sí | sí | no |

Recepción no exporta, igual que en tutores. La exportación PDF queda pendiente de infraestructura y no tiene botón.

### Contratos y stubs

**`FichaIngresoDirectoryContract`**:

```php
public function existe(int $pacienteId): bool;
public function resumen(int $pacienteId): ?FichaIngresoResumen;
public function tieneFichaCompleta(int $pacienteId): bool;
public function fichaVigenteId(int $pacienteId): ?int;
```

`FichaIngresoResumen` no expone contenido sensible. Consumidores previstos: `docs/04_PACIENTES_TUTORES/04_busqueda_ficha_resumen.md`.

**`PatientConsentContract`**: implementado en Fase B (`EloquentPatientConsent`). Consumidores previstos: `docs/06_INSCRIPCIONES_ATENCIONES/03_entrevista_diagnostico_atenciones.md` y `docs/11_EVALUACIONES_RESULTADOS/04_acceso_informacion_clinica.md`.

**`EntrevistaDirectoryContract`**: stub con `disponible() === false`; `existe()` lanza `LogicException('Módulo 06 — Entrevista y diagnóstico no implementado todavía.')`. No se agregó `entrevista_id` a la ficha.

**`FichaIngresoExporterContract`**: bindeado a `UnavailableFichaIngresoExporter`, que lanza `LogicException('Exportación PDF de la ficha de ingreso pendiente de infraestructura.')`. Sin endpoint ni botón.

### Contactos de emergencia

04.03/RF-007 queda cubierto por **04.02/RF-012**. La ficha muestra los contactos existentes en modo lectura; se administran desde la ficha del paciente. No se crea tabla, Action ni endpoint nuevo.

### Migraciones y seeders

- `2026_11_24_000400_create_ficha_ingreso_tables.php`
- `FichaIngresoPermissionSeeder`
- `FichaIngresoAccionAuditadaSeeder`

`database/database.md` y `database/talitacumi_schema.sql` quedan sincronizados.

### Checklist Fase A

| Requisito | Estado | Evidencia |
|---|---|---|
| 04.03/RF-001 crear ficha | Completo | `CreateFichaIngresoAction`, `FichaIngresoTest` |
| 04.03/RF-002 motivo y fecha obligatorios | Completo | `StoreFichaIngresoRequest`, `FichaIngresoTest` |
| 04.03/RF-003 consentimientos | Completo (Fase B) | `RegisterConsentimientoAction`, `ConsentimientoTest` |
| 04.03/RF-004 consultar consentimientos | Completo (Fase B) | GET auditado de consentimientos |
| 04.03/RF-005 editar con antes/después | Completo | Revisiones + bitácora |
| 04.03/RF-006 observaciones fechadas | Completo (Fase C) | `CreateObservacionAction`, `ObservacionTest` |
| 04.03/RF-007 contactos de emergencia | Completo por 04.02/RF-012 | Reutilización en lectura |
| 04.03/RF-008 persona que deriva | Completo | Campos en ficha |
| 04.03/RF-009 consultar ficha | Completo | GET auditado |
| 04.03/RF-010 exportar PDF | Fase C / infraestructura | Stub, sin botón |
| 04.03/RF-011 bitácora | Completo (A, B y C) | `RecordPacientesAudit` |
| 04.03/RF-012 vincular entrevista 06 | Fase C / stub | `EntrevistaDirectoryContract` |
| 04.03/RF-013 rol y sucursal | Completo | `FichaIngresoAccessScope` |
| 04.03/RF-014 bloquear eliminación | Completo (DELETE 409; historial 06/07/08 pendiente) | `RejectFichaIngresoDeletionAction` |
| 04.03/RF-015 datos sensibles | Completo | `FichaIngresoFields`, `ver_sensibles` |
| 04.03/RN-001 a RN-010 | Implementadas según fase | Ver reglas |
| 04.03/CU-01, CU-03; CA-001, 002, 006, 008, 010 | Completo | `FichaIngresoTest` |

### Pendiente

- Fase C: PDF y entrevista 06; las observaciones fechadas ya están implementadas.
- PDF, cifrado en reposo y chequeo de historial 06/07/08.
- Validar con el cliente la lista inicial de tipos de consentimiento y la semántica de reingreso (si el consentimiento debe repetirse en cada ficha).

---

## Módulo 04.03 — Ficha de ingreso (Fase B: consentimientos)

Implementa el catálogo configurable y el registro/consulta/revocación de consentimientos. No implementa la entrevista de 06 ni el registro clínico de 11.

### Alcance de la Fase B

- Catálogo `consentimiento_tipo` administrable por Superadmin; sin enum y sin eliminación física.
- Registro de consentimientos otorgados y negados por tipo y tutor.
- Consulta de vigentes e historial completo.
- Revocación como evento nuevo, con motivo obligatorio; el registro original no se modifica ni se borra.
- Bloqueo si la ficha está archivada, si no hay tutores activos o si el tutor no está asociado.
- Un solo consentimiento otorgado vigente por ficha + tipo + tutor.
- Bitácora sin el texto libre del motivo.
- Contrato `PatientConsentContract` para 06 y 11.

### Endpoints

Prefijo real: `/api/pacientes`.

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/pacientes/{id}/ficha-ingreso/{fichaId}/consentimientos` | Vigentes, historial, tipos y tutores | `ficha_ingreso.consentimiento_ver` |
| POST | `/pacientes/{id}/ficha-ingreso/{fichaId}/consentimientos` | `RegisterConsentimientoAction` | `ficha_ingreso.consentimiento_registrar` |
| POST | `/pacientes/{id}/ficha-ingreso/{fichaId}/consentimientos/{consentimientoId}/revocar` | `RevokeConsentimientoAction` | `ficha_ingreso.consentimiento_revocar` |
| DELETE | `/pacientes/{id}/ficha-ingreso/{fichaId}/consentimientos/{consentimientoId}` | `RejectConsentimientoDeletionAction` → 409 | `ficha_ingreso.consentimiento_revocar` |
| GET | `/ficha-ingreso/consentimientos/tipos` | `ListConsentimientoTiposQuery` | `ficha_ingreso.consentimiento_catalogo` |
| POST | `/ficha-ingreso/consentimientos/tipos` | `CreateConsentimientoTipoAction` | `ficha_ingreso.consentimiento_catalogo` |
| PATCH | `/ficha-ingreso/consentimientos/tipos/{tipoId}` | `UpdateConsentimientoTipoAction` | `ficha_ingreso.consentimiento_catalogo` |
| DELETE | `/ficha-ingreso/consentimientos/tipos/{tipoId}` | `RejectConsentimientoTipoDeletionAction` → 409 | `ficha_ingreso.consentimiento_catalogo` |

### Reglas

- `resultado` inicial: `otorgado` o `negado`. `revocado` solo se crea por la acción de revocación.
- `fecha` no futura; se usa `zona_horaria` vía `PacienteAgeCalculator`.
- Un consentimiento otorgado vigente por `ficha + tipo + tutor`; duplicado → `409 CONSENT_ALREADY_ACTIVE`.
- Ficha archivada → `409 FICHA_ARCHIVED` para registrar o revocar.
- Paciente sin tutores activos → `409 CONSENT_TUTOR_REQUIRED` (CA-005).
- Tutor no asociado o inactivo → `422 CONSENT_TUTOR_NOT_ASSOCIATED`.
- Tipo inactivo → `409 CONSENT_TYPE_INACTIVE`.
- La negativa se conserva y puede seguirse de un nuevo consentimiento otorgado.
- La revocación exige motivo; si ya fue revocado o no es un otorgado vigente → `409 CONSENT_NOT_REVOCABLE`.
- DELETE siempre `409`: consentimientos (`CONSENT_DELETE_NOT_ALLOWED`) y tipos (`CONSENT_TYPE_DELETE_NOT_ALLOWED`).
- El código del tipo es inmutable después del alta; la edición usa `version` optimista (`409 CONSENT_TYPE_VERSION_CONFLICT`).
- `PatientConsentContract` consulta la ficha más reciente del paciente. Si hay reingreso con ficha nueva, exige consentimiento de esa ficha; la semántica queda **pendiente de validar**.

### Catálogo inicial (PROPUESTO - VALIDAR)

`ConsentimientoTipoSeeder` crea, sin pisar cambios del cliente:

- `atencion` — Consentimiento para la atención.
- `uso_datos_contacto` — Uso de datos de contacto.
- `uso_imagen` — Uso de imágenes.

### Permisos

Seeder: `ConsentimientoPermissionSeeder`, marca `pacientes.ficha_ingreso_consentimiento`.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `ficha_ingreso.consentimiento_ver` | sí | sí | sí |
| `ficha_ingreso.consentimiento_registrar` | sí | sí | sí |
| `ficha_ingreso.consentimiento_revocar` | sí | sí | no |
| `ficha_ingreso.consentimiento_catalogo` | sí | no | no |

### Bitácora y contrato

- Entidad `consentimiento`: `OTORGAR`, `NEGAR`, `REVOCAR` (crítico), `CONSULTAR`.
- Entidad `consentimiento_tipo`: `CREAR`, `ACTUALIZAR`.
- El snapshot guarda ids, tipo, tutor, resultado, fecha y `revoca_a_id`; **no** guarda el motivo libre.
- La consulta de consentimientos se audita como `ficha_ingreso` con contexto `recurso=ficha_ingreso_consentimientos`.
- `PatientConsentContract`:
  ```php
  public function tieneConsentimientoVigente(int $pacienteId, string $tipoCodigo = 'atencion'): bool;
  public function consentimientoVigente(int $pacienteId, string $tipoCodigo = 'atencion'): ?ConsentimientoResumen;
  ```
  `ConsentimientoResumen` no expone motivos ni contacto del tutor.

### Migraciones y seeders de Fase B

- `2026_11_24_000500_create_consentimiento_tables.php`
- `ConsentimientoPermissionSeeder`
- `ConsentimientoTipoSeeder`
- `ConsentimientoAccionAuditadaSeeder`

### Checklist Fase B

| Requisito | Estado | Evidencia |
|---|---|---|
| 04.03/RF-003 registrar consentimientos | Completo | `RegisterConsentimientoAction` |
| 04.03/RF-004 consultar vigentes | Completo | GET con vigentes e historial |
| 04.03/RN-004 vigencia y revocación | Completo | `Consentimiento::scopeVigentes`, `RevokeConsentimientoAction` |
| 04.03/CA-003 tipo, tutor y fecha | Completo | `ConsentimientoTest` |
| 04.03/CA-005 ficha sin tutores | Completo | `ConsentimientoTutorRequiredException` |
| 04.03/FA-01 negativa visible | Completo | Resultado `negado` |
| 04.03/FE-01 tutor no asociado | Completo | `ConsentimientoTutorNotAssociatedException` |
| 04.03/RN-010 uso de imágenes | Catálogo propuesto | Tipo `uso_imagen`, sin bloqueo de uso |
| 04.03/RF-011 bitácora | Completo | `RecordPacientesAudit` |
| 04.03/CU-02 | Completo | `ConsentimientoTest` |

### Pendiente de Fase B

- Confirmar con el cliente los tipos iniciales, la obligatoriedad exacta del motivo de revocación y si el consentimiento se repite en cada reingreso.
- El uso de imágenes no bloquea todavía ningún flujo del sistema (no hay módulo de imágenes).
- PDF y entrevista 06 siguen fuera de alcance.

---

## Módulo 04.03 — Ficha de ingreso (Fase C: observaciones fechadas)

Implementa **RF-006** y **RN-003**: observaciones fechadas, inmutables y corregibles mediante una nueva observación que referencia la anterior. La entrevista de 06 y la exportación PDF siguen bloqueadas.

### Alcance de la Fase C

- Observaciones fechadas con texto, fecha y usuario.
- Solo inserción: no se editan ni se borran.
- Corrección mediante `observacion_referencia_id` a una observación de la misma ficha.
- Ficha archivada bloquea nuevas observaciones.
- Texto sensible: se oculta sin `ficha_ingreso.ver_sensibles` y el alta exige ese permiso.
- La bitácora guarda metadatos y `tiene_texto`, nunca el texto.

### Endpoints

Prefijo real: `/api/pacientes`.

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/pacientes/{id}/ficha-ingreso/{fichaId}/observaciones` | Listado + auditoría de consulta | `ficha_ingreso.observacion_ver` |
| POST | `/pacientes/{id}/ficha-ingreso/{fichaId}/observaciones` | `CreateObservacionAction` | `ficha_ingreso.observacion_agregar` + `ver_sensibles` |
| DELETE | `/pacientes/{id}/ficha-ingreso/{fichaId}/observaciones/{observacionId}` | `RejectObservacionDeletionAction` → 409 | `ficha_ingreso.observacion_agregar` |

### Reglas

- `fecha` no futura; se usa `zona_horaria` vía `PacienteAgeCalculator`.
- `texto` obligatorio, máximo 4000 caracteres.
- La referencia debe pertenecer a la misma ficha; si no, `422 OBSERVACION_REFERENCE_INVALID`.
- Ficha archivada → `409 FICHA_ARCHIVED`.
- Sin `ver_sensibles`: el GET devuelve `texto=null` y `texto_protegido=true`; el POST responde `403 FICHA_SENSITIVE_FIELDS_FORBIDDEN`.
- DELETE siempre `409 OBSERVACION_DELETE_NOT_ALLOWED`.
- El modelo rechaza `update()` y `delete()` con `LogicException`.

### Permisos

Seeder: `ObservacionPermissionSeeder`, marca `pacientes.ficha_ingreso_observacion`.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `ficha_ingreso.observacion_ver` | sí | sí | sí |
| `ficha_ingreso.observacion_agregar` | sí | sí | sí |

### Bitácora

- Entidad `ficha_ingreso_observacion`, acción `CREAR`.
- Snapshot: `id`, `ficha_ingreso_id`, `fecha`, `observacion_referencia_id`, `usuario_id`, `tiene_texto` y `created_at`. **No** incluye `texto`.
- La consulta del listado se audita como `ficha_ingreso` con contexto `recurso=ficha_ingreso_observaciones`.

### Migraciones y seeders de Fase C

- `2026_11_24_000600_create_ficha_ingreso_observacion_table.php`
- `ObservacionPermissionSeeder`
- `ObservacionAccionAuditadaSeeder`

### Checklist Fase C

| Requisito | Estado | Evidencia |
|---|---|---|
| 04.03/RF-006 observaciones fechadas | Completo | `CreateObservacionAction`, `ObservacionTest` |
| 04.03/RN-003 observaciones no destructivas | Completo | `FichaIngresoObservacion`, referencia |
| 04.03/CA-004 agregar sin borrar anteriores | Completo | `ObservacionTest` |
| 04.03/RF-010 exportar PDF | Pendiente de infraestructura | `UnavailableFichaIngresoExporter` |
| 04.03/RF-012 vincular entrevista 06 | Pendiente del módulo 06 | `StubEntrevistaDirectoryContract` |

### Pendiente de Fase C

- PDF: no hay librería instalada; requiere autorización para agregar una dependencia o definir el servicio externo.
- Entrevista 06: no existe el módulo; la ficha mantiene el mensaje y el contrato stub.
- Confirmar con el cliente si el texto de las observaciones debe tener un permiso propio distinto de `ver_sensibles`.

---

## Módulo 04.04 — Búsqueda y ficha resumen de pacientes

Implementa el agregador de **solo lectura** de búsqueda y ficha resumen. No crea ni edita pacientes, tutores, inscripciones, turnos ni pagos. La escritura se limita a la bitácora.

### Alcance entregado

- **Fase A — Búsqueda:** extensión de `ListPacientesQuery` y `/pacientes` con búsqueda por tutor principal activo, teléfono exacto con permiso, orden por apellido, mínimo de 2 caracteres y filtros no disponibles deshabilitados.
- **Fase B — Ficha resumen:** endpoint `/pacientes/{id}/resumen`, bloques de datos básicos, tutores, contactos, banderas sensibles, servicios, pago, asistencia, vencimiento, sucursales y alerta; degradación a `no_disponible`.
- **Fase C — Exportación/alertas:** CSV del listado con permiso propio y auditoría; contratos/stubs de alerta de pago. El PDF queda pendiente de infraestructura.

### Endpoints

Prefijo real: `/api/pacientes`.

| Método | Ruta | Query/Acción | Permiso |
|---|---|---|---|
| GET | `/pacientes` | `ListPacientesQuery` extendido | `paciente.ver` |
| GET | `/pacientes/{id}/resumen` | `GetPacienteResumenQuery` + auditoría | `paciente.ver` |
| GET | `/pacientes/{id}/resumen/indicadores` | `GetPacienteResumenIndicadoresQuery`; sin auditoría | `paciente.ver` |
| GET | `/pacientes/exportar` | `ListPacientesQuery::builder` + `PacienteCsvExporter` | `paciente.exportar_listado` |

### Búsqueda: cambios al contrato de 04.01

- `search` ahora busca también el **tutor principal activo** por nombre normalizado y, con `tutor.ver_contacto`, por teléfono exacto de 8 dígitos.
- `search` exige mínimo 2 caracteres; vacío lista todo.
- `sort` agrega `apellido`; `nombre_completo` se mantiene y sigue ordenando por apellido.
- `sucursal_inscripcion_id`, `tipo_servicio_id` y `estado_pago` quedan **prohibidos** hasta 06/08.
- `per_page` máximo 50.
- El listado no audita. El teléfono del tutor no se devuelve en la respuesta; solo se muestra en la ficha resumen según permiso.

### Ficha resumen

Cada bloque tiene:

```json
{
  "estado": "disponible | no_disponible",
  "motivo": null | "MODULO_NO_IMPLEMENTADO" | "SIN_SERVICIOS_VIGENTES" | "SIN_PERMISO" | "SIN_TUTOR" | "ERROR",
  "mensaje": "texto para la UI",
  "data": {}
}
```

Hoy:

- Disponibles: datos básicos, banderas sensibles (`tiene_alergias`, `tiene_condiciones_medicas`, `tiene_observaciones`, nunca contenido), tutores con teléfono enmascarado/completo y contactos de emergencia con permiso.
- No disponibles por módulo inexistente: servicios (06), pago (08), asistencia (07), próximo vencimiento (06/08), sucursales múltiples (06) y alerta (04.02 Fase C + 09).
- Nunca se devuelven ceros, “al día” por defecto ni datos de ejemplo.
- Un bloque que falla se degrada a `ERROR`; no produce 500.

### Auditoría

- Apertura del resumen: `entidad='paciente'`, `entidad_id=paciente`, `sucursal_id=sucursal_registro_id`, `contexto=['recurso'=>'ficha_resumen']`, una sola fila, fail-closed `503 AUDIT_UNAVAILABLE`.
- El resumen **no llama** a `GET /pacientes/{id}`. “Ver ficha completa” es una navegación separada y genera su propia auditoría `ficha_paciente`.
- Refresco de indicadores: sin auditoría; solo devuelve bloques de indicadores, sin identidad, tutores ni contactos.
- Búsqueda/listado: sin auditoría, consistente con 04.01.
- Exportación: `entidad='exportacion'`, `accion='EXPORTAR'`, `critico=true`, filtros seguros (sin `search` crudo, sin teléfonos/documentos), cantidad y formato.

### Permisos

Seeder: `PacienteResumenPermissionSeeder`, marca `pacientes.resumen`.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `paciente.ver` (ver resumen y buscar) | sí | sí | sí |
| `tutor.ver_contacto` (teléfono y búsqueda por teléfono) | sí | sí | sí |
| `paciente.ver_contacto_emergencia` | sí | sí | sí |
| `paciente.exportar_listado` (nuevo) | sí | sí | **no** |
| `paciente.exportar` (legado de 04.01) | sí | sí | sí |

`paciente.exportar` **no se modificó** en 04.04: queda reservado para la ficha completa de 04.01. La exportación del listado usa el permiso nuevo. La asignación de Recepción al permiso legado queda como deuda documentada.

### Contratos nuevos

Todos incluyen `disponible(): bool` y reciben el alcance `?array $sucursalesAccesibles` (null = global) y, cuando aplica, `?int $sucursalContexto`.

| Contrato | Implementación actual | Módulo futuro |
|---|---|---|
| `PatientServicesSummaryContract` | `StubPatientServicesSummaryContract` | 06 |
| `PatientPaymentStatusContract` | `StubPatientPaymentStatusContract` | 08 |
| `PatientAttendanceSummaryContract` | `StubPatientAttendanceSummaryContract` | 07 |
| `TutorContactContract` | `StubTutorContactContract` | 04.02 Fase C |
| `PatientPaymentAlertContract` | `StubPatientPaymentAlertContract` | 09 |

DTOs: `ServicioVigenteResumen`, `EstadoPagoResumen`, `AsistenciaResumen`, `ContactoTutorResumen` y `AlertaPagoResumen`. Ningún stub devuelve datos falsos: `disponible()` es false y los métodos lanzan `LogicException`. La Query comprueba `disponible()` antes de invocar y captura errores por bloque.

Documentos exactos para los consumidores:

- 06: `docs/06_INSCRIPCIONES_ATENCIONES/01_inscripcion_salas_grupales.md`, `02_paquetes_sesiones_individuales.md`, `04_vigencia_vencimiento_renovacion.md`, `05_estados_paciente_inscripcion.md`.
- 07: `docs/07_AGENDA_TURNOS/04_registro_asistencia_turno.md`.
- 08: `docs/08_PAGOS_CAJA/01_registro_pagos_saldos.md`, `05_plan_cuotas.md`.
- 09: `docs/09_REPORTES_ALERTAS/02_alertas_proximo_pago.md`.
- 04.02 Fase C: `docs/04_PACIENTES_TUTORES/02_registro_tutores_contactos.md` (RF-013, RN-009).

Los módulos 06/07/08/09 deben filtrar por las sucursales accesibles y por el contexto; el README de cada módulo debe conservar ese requisito.

### Exportación CSV

- Columnas: `ID`, `Código`, `Nombre completo`, `Documento`, `Fecha de nacimiento`, `Sexo`, `Estado`, `Sucursal de registro`.
- Sin teléfonos, tutores, alergias, condiciones, observaciones ni datos clínicos.
- BOM UTF-8, delimitador `;`, escape de fórmulas, `lazy(200)`.
- Límite configurable: `config('pacientes.export_max_rows')` (10 000 por defecto); exceso → `422 PACIENTES_EXPORT_TOO_LARGE`.
- PDF: contrato `PacienteListadoPdfExporterContract` + implementación `Unavailable...` con `LogicException`; sin endpoint ni botón.
- Permiso: `paciente.exportar_listado`; Recepción no exporta.

### Archivos principales

- Búsqueda: `Rules/PacienteBusquedaRule.php`, `Queries/Registro/ListPacientesQuery.php`, `Http/Requests/Registro/IndexPacienteRequest.php`.
- Resumen: `Queries/Resumen/GetPacienteResumenQuery.php`, `GetPacienteResumenIndicadoresQuery.php`, `Support/PacienteResumenBlock.php`, `PacienteResumenBlocksBuilder.php`, `Http/Controllers/Resumen/PacienteResumenController.php`, `routes-resumen.php`.
- Exportación: `Http/Requests/Registro/ExportPacienteRequest.php`, `Support/PacienteCsvExporter.php`, `PacienteResumenPermissionSeeder`.
- Frontend: `paciente-resumen.page.*`, `paciente-resumen.models.ts`, `pacientes.page.*`, `pacientes.service.ts`, `paciente-ficha.page.*`.

No se agregaron tablas, columnas ni índices nuevos. Se usan los índices existentes de `paciente`, `tutor` y `paciente_tutor` (unique parcial del principal). `database.md` y `talitacumi_schema.sql` no cambian.

### Fases y checklist 04.04

| Fase | Requisitos | Estado |
|---|---|---|
| A | 04.04/RF-001, RF-002, RF-003, RF-004 (parcial), RF-010 (proxy), RF-017 | Implementada |
| B | 04.04/RF-005 (parcial), RF-006, RF-007, RF-008, RF-009 (parcial), RF-011, RF-012, RF-014, RF-015, RF-016 | Implementada con bloques `no_disponible` |
| C | 04.04/RF-013 (CSV; PDF pendiente), RF-009 (alerta deshabilitada) | Implementada parcialmente |

| Requisito | Estado | Evidencia |
|---|---|---|
| 04.04/RF-001 nombre/apellido | Completo | `PacienteBusquedaRule`, `PacienteSearchTest` |
| 04.04/RF-002 documento | Completo | `scopeBuscar`, `PacienteSearchTest` |
| 04.04/RF-003 tutor | Completo | búsqueda por tutor principal activo |
| 04.04/RF-004 estado/sucursal | Parcial: inscripción pendiente 06 | filtros actuales |
| 04.04/RF-005 ficha resumen | Parcial: básicos/tutores/contactos + bloques no disponibles | `PacienteResumenTest` |
| 04.04/RF-006 servicios | Contrato + `no_disponible` | `StubPatientServicesSummaryContract` |
| 04.04/RF-007 pago | Contrato + `no_disponible` | `StubPatientPaymentStatusContract` |
| 04.04/RF-008 asistencia | Contrato + `no_disponible` | `StubPatientAttendanceSummaryContract` |
| 04.04/RF-009 acciones frecuentes | Parcial: ficha completa/ficha ingreso; resto deshabilitado | página de resumen |
| 04.04/RF-010 scoping | Completo por proxy `sucursal_registro_id` | `PacienteAccessScope` |
| 04.04/RF-011 sucursales múltiples | Contrato + `no_disponible` | `StubPatientServicesSummaryContract` |
| 04.04/RF-012 bitácora de resumen | Completo | `PacienteResumenController::show` |
| 04.04/RF-013 exportar | CSV completo; PDF pendiente | `PacienteCsvExporter`, `PacienteResumenExportTest` |
| 04.04/RF-014 sensibles | Completo con banderas | `datos_sensibles` |
| 04.04/RF-015 actualizar indicadores | Abrir + botón; sin datos hasta 07/08 | `/resumen/indicadores` |
| 04.04/RF-016 próximo vencimiento | Contrato + `no_disponible` | `proximo_vencimiento` |
| 04.04/RF-017 orden | Completo | `sort=apellido`, `fecha_nacimiento`, `estado` |

RN-001 a RN-010, CU-01 a CU-03 y CA-001 a CA-010 quedan cubiertos según las dependencias anteriores. Las reglas de pago/asistencia siguen **PROPUESTO - VALIDAR** con el cliente.

### Pendiente de 04.04

- Implementar los contratos cuando existan 06/07/08/09; no cambiar la ficha resumen salvo el binding.
- PDF del listado: pendiente de infraestructura.
- Confirmar con el cliente la visibilidad de Recepción entre sucursales, la definición de “al día/con saldo/vencido”, el período de asistencia, el uso de teléfono para búsqueda y la exportación de Recepción.
- La alternancia de contexto de sucursal queda preparada por `sucursal_id`, pero la UI la habilitará con 06.

---

## Módulo 05 — Catálogo de servicios y tarifas (submódulo 01)

Este es el primer submódulo del módulo 05. Crea la base del módulo padre para que los submódulos 02 (tarifas/categorías) y 03 (tipos de diagnóstico) reutilicen el provider, el prefijo y los catálogos de auditoría.

### Estructura

```text
app/Modules/CatalogoServicios/
├── Actions/
│   ├── Areas/
│   ├── Servicios/
│   └── TiposServicio/
├── Contracts/       Directorio, uso de servicios y exportador
├── Events/          Área, servicio y tipo
├── Exceptions/      Dominio del catálogo
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Listeners/       Bitácora e invalidación de caché
├── Models/          Area, Servicio, TipoServicio, BitacoraEntry
├── Providers/       CatalogoServiciosServiceProvider
├── Queries/         Listados, fichas y directorio cacheado
├── Rules/           Color, normalización, unicidad, estado y motivo
├── Support/         Constantes, permisos, abilities, snapshots y guard
├── routes-areas.php
├── routes-servicios.php
└── routes-tipos-servicio.php
```

`CatalogoServiciosServiceProvider` se registra después de `PacientesServiceProvider`. Prefijo público: `App\Modules\CatalogoServicios\Support\CatalogoServiciosModule::MODULE_URL = '/catalogo-servicios'`. Prefijo real de la API: `/api/catalogo-servicios`.

> **Propiedad de `area`:** la tabla nació en 01.02 como prerequisito de solo lectura de `sala.area_id`. El módulo 05 es el dueño del CRUD y del color. Para no romper 01.02 se conserva su modelo de solo lectura; el modelo canónico es `CatalogoServicios\Models\Area`. Queda como TODO consolidar ambos mapeos en una fase posterior.

### Endpoints

| Entidad | Endpoints |
|---|---|
| Áreas | `GET /areas`, `GET /areas/{id}`, `POST /areas`, `PATCH /areas/{id}`, `POST /areas/{id}/activar`, `POST /areas/{id}/desactivar`, `DELETE /areas/{id}` → `409` |
| Servicios | `GET /servicios`, `GET /servicios/{id}`, `POST /servicios`, `PATCH /servicios/{id}`, `POST /servicios/{id}/activar`, `POST /servicios/{id}/desactivar`, `DELETE /servicios/{id}` → `409` |
| Tipos de servicio | `GET /tipos-servicio`, `GET /tipos-servicio/{id}`, `POST /tipos-servicio`, `PATCH /tipos-servicio/{id}`, `POST /tipos-servicio/{id}/activar`, `POST /tipos-servicio/{id}/desactivar`, `DELETE /tipos-servicio/{id}` → `409` |

Filtros de servicios: `search`, `area_id`, `tipo_servicio_id`, `estado`, `page`, `per_page` (máx. 50), `sort` (`nombre|area|tipo|duracion_minutos|activo|updated_at`), `direction`.
Filtros de áreas: `search`, `estado`, `sort`, `direction`.
Filtros de tipos: `search`, `estado`, `sort`, `direction`.

Todas las rutas usan `auth:sanctum` + `EnsureSessionIsValid` + `PermissionMiddleware`. No hay scoping por sucursal: áreas, servicios y tipos son globales (RN-C01).

### Permisos

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `areas.ver` | sí | sí | sí |
| `areas.editar` | sí | no | no |
| `servicios.ver` | sí | sí | sí |
| `servicios.editar` | sí | no | no |

`areas.editar` gobierna alta, edición, color y cambio de estado de áreas. `servicios.editar` gobierna servicios y tipos de servicio. La API devuelve `can` y la UI oculta acciones. Seeder: `CatalogoServiciosPermissionSeeder`, con asignación inicial única por módulo.

### Eventos y bitácora

| Entidad (`bitacora.entidad`) | Eventos |
|---|---|
| `area` | `AreaCreated`, `AreaUpdated`, `AreaColorChanged`, `AreaStatusChanged` |
| `servicio` | `ServicioCreated`, `ServicioUpdated`, `ServicioStatusChanged` |
| `tipo_servicio` | `TipoServicioCreated`, `TipoServicioUpdated`, `TipoServicioStatusChanged` |

`RecordCatalogoServiciosAudit` escribe `CREAR`, `ACTUALIZAR`, `ACTIVAR`, `DESACTIVAR` y `CAMBIAR_COLOR`, con snapshots antes/después, actor, IP y `motivo` en los cambios de estado. `sucursal_id` es `NULL` porque el catálogo es global. `InvalidateCatalogoServiciosCache` olvida las claves del directorio.

### Contratos y caché

- **`CatalogoServiciosDirectoryContract`** (RF-C15): `areas()`, `servicios()`, `servicio()`, `asegurarServicioActivo()` y `tiposServicio()`. Devuelve DTOs `AreaResumen`, `ServicioResumen` y `TipoServicioResumen`. Incluye el color del área para la agenda.
- `CatalogoServiciosDirectoryQuery` cachea las listas completas con TTL de 60 segundos. Driver `file` (no hay Redis ni tags); la invalidación es explícita por listener.
- **`ServicioUsageContract`** + `StubServicioUsageContract`: `hasTarifas`, `hasInscripciones`, `hasTurnos`. Hoy no se invoca porque no existe endpoint de borrado. Se conectará en el submódulo 02 y en los módulos 06/07.
- **`CatalogoServiciosExporterContract`** + `UnavailableCatalogoServiciosExporter`: RF-C17 pendiente de infraestructura. No hay ruta ni botón.

### Schema, migraciones y seeders

Migraciones nuevas:

- `2026_11_25_000000_create_tipo_servicio_table.php`.
- `2026_11_25_000100_create_servicio_table.php`.
- `2026_11_25_000200_add_descripcion_and_version_to_area_table.php`.

Cambios:

- `area`: `descripcion` nullable, `version` default 1 e índice único funcional `lower(trim(nombre))`.
- `tipo_servicio`: catálogo configurable con `nombre`, `activo`, `version` e índice único funcional.
- `servicio`: `area_id` NOT NULL, `tipo_servicio_id` NOT NULL, `nombre`, `descripcion`, `duracion_minutos`, `activo`, `version`; índice único `(area_id, lower(trim(nombre)))`.
- Sin `deleted_at`: RN-C08 exige no eliminar; se desactiva con motivo. El motivo queda solo en `bitacora.motivo`.
- No se crean `tarifa`, `categoria_tarifa`, `tipo_diagnostico`, `inscripcion_sala` ni `turno`: son de 02/03/06/07.

Seeders idempotentes: `AreaSeeder` (Intervención Terapéutica `#671C61` y Salas Grupales `#F9733F`), `TipoServicioSeeder` (Entrevista, Diagnóstico / Evaluación, Terapia individual, Sala grupal), `CatalogoServiciosPermissionSeeder` y `CatalogoServiciosAccionAuditadaSeeder`. No se crean servicios de ejemplo.

En una instalación existente, ejecutar `php artisan migrate` y luego `php artisan db:seed --class=CatalogoServiciosPermissionSeeder`. Si la base ya tenía las dos áreas base con otros colores, el seeder **no los sobrescribe**.

### Estado del submódulo

| Cobertura | Estado |
|---|---|
| RF-C01 a RF-C12, RF-C14, RF-C15, RF-C16 | Implementados |
| RF-C13 | Sin DELETE; `409`; contrato de uso en stub hasta 02/06/07 |
| RF-C17 | Pendiente de infraestructura; contrato stub y sin botón |
| RN-C01 a RN-C05, RN-C07 a RN-C10 | Implementadas |
| RN-C06 | API expone `activo`; consumo real en 06/07 |

Tests: unit de reglas (color, normalización, unicidad, motivo, área con servicios activos) y feature de áreas, servicios, tipos, permisos, bitácora, contratos y seeders.

Checklist RF-C:

| RF | Evidencia | Estado |
|---|---|---|
| RF-C01 | `CreateAreaAction`, `StoreAreaRequest`, `AreaSeeder` | Completo |
| RF-C02 | `UpdateAreaAction`, `ChangeAreaStatusAction`, `AreaStateRule` | Completo |
| RF-C03 | `AreaNombreUnicoRule`, índice funcional | Completo |
| RF-C04 | `ColorRule`, selector/picker en UI | Completo |
| RF-C05 | `CreateServicioAction`, `tipo_servicio` | Completo |
| RF-C06 | `ServicioNombreUnicoRule`, índice por área | Completo |
| RF-C07 | `servicio.area_id NOT NULL` + FK | Completo |
| RF-C08 | `ListServiciosQuery` + filtros UI | Completo |
| RF-C09 | `ListAreasQuery` con color/estado/conteo | Completo |
| RF-C10 | `GetServicioQuery`, ficha con `updated_at` | Completo |
| RF-C11 | `UpdateServicioAction`, eventos y bitácora | Completo |
| RF-C12 | `ChangeServicioStatusAction` | Completo |
| RF-C13 | DELETE → `409`; `ServicioUsageContract` stub | Parcial: falta chequeo real 02/06/07 |
| RF-C14 | `RecordCatalogoServiciosAudit` | Completo |
| RF-C15 | Endpoints + `CatalogoServiciosDirectoryContract` + caché | Completo |
| RF-C16 | Permisos, seeder, middleware y `can` | Completo |
| RF-C17 | `CatalogoServiciosExporterContract` stub | Pendiente de infraestructura |

Checklist RN-C:

| RN | Evidencia | Estado |
|---|---|---|
| RN-C01 | Sin `sucursal_id`; catálogo global | Completo |
| RN-C02 | `AreaNombreUnicoRule` + índice | Completo |
| RN-C03 | `ServicioNombreUnicoRule` + índice por área | Completo |
| RN-C04 | FK `servicio.area_id` NOT NULL | Completo |
| RN-C05 | Color solo en swatch/etiqueta y API | Completo |
| RN-C06 | API expone `activo`; consumo en 06/07 | Parcial, fuera de alcance |
| RN-C07 | `AreaStateRule` + bloqueo de servicio en área inactiva | Completo |
| RN-C08 | Sin DELETE; desactivación con motivo | Completo |
| RN-C09 | `tipo_servicio` + CRUD + seeder | Completo |
| RN-C10 | `MotivoObligatorioRule` en activar/desactivar | Completo |

**TODOs:** `ServicioUsageContract::hasTarifas` ya es real; `hasInscripciones`/`hasTurnos` siguen en stub. Quedan exportación PDF/Excel, migración inicial del cliente, consolidación del modelo `Area` de 01.02 y `ambiente_id` en agenda (07).

---

## Módulo 05 — Catálogo de servicios y tarifas (submódulo 02)

Submódulo de **tarifas, categorías y vigencias**. Reutiliza el módulo padre y el prefijo creado por el 01.

### Estructura agregada

```text
app/Modules/CatalogoServicios/
├── Actions/
│   ├── CategoriasTarifa/
│   └── Tarifas/
├── Contracts/       TarifaAplicableResolver, TarifaUsage, EloquentServicioUsage
├── Events/          Categoría y tarifa
├── Exceptions/      Solapamiento, cierre, categoría en uso, etc.
├── Http/            Categorías y tarifas
├── Listeners/       RecordCatalogoTarifasAudit
├── Models/          CategoriaTarifa, Tarifa
├── Queries/         Listados, fichas y resolución aplicable
├── Rules/           Monto, vigencia, solapamiento y estado
├── Support/         Snapshots, scoping y guard de solapamiento
├── routes-categorias-tarifa.php
└── routes-tarifas.php
```

### Endpoints

Prefijo: `/api/catalogo-servicios`.

| Entidad | Endpoints |
|---|---|
| Categorías | `GET/POST /categorias-tarifa`, `GET/PATCH /categorias-tarifa/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` → `409` |
| Tarifas | `GET/POST /tarifas`, `GET/PATCH /tarifas/{id}`, `POST /tarifas/{id}/cerrar-vigencia`, `DELETE /tarifas/{id}` → `409` |
| Resolución | `GET /tarifas/aplicable?servicio_id&sucursal_id&categoria_tarifa_id&fecha` |

Filtros de tarifas: `search`, `servicio_id`, `categoria_tarifa_id`, `sucursal_id`, `solo_global`, `estado_vigencia` (`vigente|futura|cerrada`), `page`, `per_page` (máx. 50), `sort`, `direction`.
`GET /tarifas/aplicable` devuelve `data: null` si no hay tarifa; error de dominio si el servicio/categoría están inactivos o la sucursal no corresponde.

### Permisos y alcance

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `tarifas.ver` | sí | sí | sí |
| `tarifas.editar` | sí | sí, solo sus sucursales | no |
| `categorias.ver` | sí | sí | sí |
| `categorias.editar` | sí | no | no |

`TarifaAccessScope` usa `UserBranchAccessContract` y `sucursal.alcance_global`:

- Las tarifas globales son visibles para usuarios con sucursales asignadas.
- Solo alcance global crea, edita o cierra tarifas globales.
- Administración edita/cierra tarifas de sus sucursales.
- Una tarifa de otra sucursal se presenta como inexistente (`404`).
- El endpoint `/tarifas/aplicable` exige sucursal accesible.
- Las categorías son globales y no llevan scoping.

Seeder: `CatalogoTarifasPermissionSeeder`, con marcador propio `catalogo.tarifas` para no pisar la matriz del 01.

### Eventos y bitácora

| Entidad (`bitacora.entidad`) | Eventos |
|---|---|
| `categoria_tarifa` | `CategoriaTarifaCreated`, `CategoriaTarifaUpdated`, `CategoriaTarifaStatusChanged` |
| `tarifa` | `TarifaCreated`, `TarifaUpdated`, `TarifaVigenciaClosed` |

`RecordCatalogoTarifasAudit` escribe `CREAR`, `ACTUALIZAR`, `ACTIVAR`, `DESACTIVAR` y `CERRAR_VIGENCIA`, con snapshots antes/después, actor, IP y motivo. `sucursal_id` es la sucursal de la tarifa o `NULL` para globales/categorías.

### Contratos y caché

- **`TarifaAplicableResolverContract`** + `EloquentTarifaAplicableResolver`: resuelve servicio + sucursal + categoría + fecha con precedencia sucursal > global.
- `TarifaUsageContract` + `StubTarifaUsageContract`: `hasInscripcionesOrPagos`; pendiente de 06/08.
- `ServicioUsageContract` → `EloquentServicioUsageContract`: `hasTarifas` real; `hasInscripciones`/`hasTurnos` siguen en `LogicException`.
- `CatalogoServiciosExporterContract` extendido con `exportarTarifas`/`exportarCategorias`; `Unavailable...` lanza `LogicException`. Sin ruta ni botón.
- `ResolveTarifaAplicableQuery` cachea la lista activa con TTL 60 s; `InvalidateCatalogoServiciosCache` la invalida ante altas/ediciones/cierres/cambios de categoría.

### Schema, migraciones y seeders

Migraciones:

- `2026_11_25_000300_create_categoria_tarifa_table.php`.
- `2026_11_25_000400_create_tarifa_table.php`.

Cambios:

- `categoria_tarifa`: `version`, índice único funcional de nombre.
- `tarifa`: `version`, índices de solapamiento y vigencia, FK a servicio/categoría/sucursal.
- Sin `deleted_at`; sin columnas de motivo. `tarifa.activo` es bandera técnica; el cierre usa `vigente_hasta`.
- Sin `moneda`; todo en Bs con `numeric(12,2)`.
- No se agrega constraint `EXCLUDE` de PostgreSQL; el no-solapamiento se valida en la Action con bloqueo del servicio.

Seeders: `CategoriaTarifaSeeder` (General, sin tarifas), `CatalogoTarifasPermissionSeeder` y `CatalogoTarifasAccionAuditadaSeeder`.

### Checklist RF-T / RN-T

| RF | Evidencia | Estado |
|---|---|---|
| RF-T01 | `CreateCategoriaTarifaAction`, `StoreCategoriaTarifaRequest` | Completo |
| RF-T02 | `ChangeCategoriaTarifaStatusAction`, `CategoriaTarifaStateRule` | Completo |
| RF-T03 | `CreateTarifaAction` | Completo |
| RF-T04 | FK `categoria_tarifa_id` | Completo |
| RF-T05 | `TarifaOverlapRule` + `TarifaOverlapGuard` | Completo |
| RF-T06 | `TarifaMontoRule` + cast decimal | Completo |
| RF-T07 | `TarifaVigenciaRule` | Completo |
| RF-T08 | `ResolveTarifaAplicableQuery` + endpoint | Completo |
| RF-T09 | `ListTarifasQuery` + filtros UI | Completo |
| RF-T10 | `GetTarifaQuery` + ficha | Completo |
| RF-T11 | `UpdateTarifaAction` | Completo |
| RF-T12 | `CloseTarifaVigenciaAction` | Completo |
| RF-T13 | DELETE `409`; `TarifaUsageContract` stub | Completo hoy; integración 06/08 pendiente |
| RF-T14 | API de categorías activas | Parcial: selección real en 06 |
| RF-T15 | `RecordCatalogoTarifasAudit` | Completo |
| RF-T16 | `/tarifas/aplicable` + contract | API completa; consumo 06/08 fuera |
| RF-T17 | Permisos + `TarifaAccessScope` | Completo con asunción de Administración |
| RF-T18 | Exportador stub | Pendiente de infraestructura |

| RN | Evidencia | Estado |
|---|---|---|
| RN-T01 | `TarifaMontoRule` + `decimal:2` | Completo |
| RN-T02 | FK `servicio_id` + servicio activo al crear | Completo |
| RN-T03 | `TarifaVigenciaRule` | Completo |
| RN-T04 | `TarifaOverlapRule` | Completo |
| RN-T05 | `ResolveTarifaAplicableQuery` | Completo |
| RN-T06 | Categoría elegida por el usuario; API no infiere | API; UI en 06 |
| RN-T07 | Snapshot en inscripción (06) | Fuera de este submódulo |
| RN-T08 | Cierre no altera históricos | Fuera de este submódulo |
| RN-T09 | Sin DELETE; cierre de vigencia | Completo |
| RN-T10 | Categoría inactiva no listada para selección | API; consumo 06 |
| RN-T11 | Permisos + `TarifaAccessScope` | Completo con asunción |
| RN-T12 | Motivo en desactivación y cierre | Completo |

**TODOs:** implementar `TarifaUsageContract` con 06/08; exportación PDF/Excel; migración inicial de tarifas desde planilla; confirmar si Administración edita categorías (hoy no).

---

## Módulo 05 — Catálogo de servicios y tarifas (submódulo 03)

Submódulo de **tipos de diagnóstico y evaluación**. Un tipo es un servicio del catálogo con atributos adicionales (RN-D08): días estimados y especialidades requeridas con cantidad mínima. El catálogo es global y no se elimina físicamente.

### Estructura agregada

```text
app/Modules/CatalogoServicios/
├── Actions/TiposDiagnostico/
│   ├── CreateTipoDiagnosticoAction
│   ├── UpdateTipoDiagnosticoAction
│   ├── ChangeTipoDiagnosticoStatusAction
│   └── RejectTipoDiagnosticoDeletionAction
├── Contracts/       TipoDiagnosticoUsage, EloquentProfessionalDiagnosticRequirements
├── Events/          TipoDiagnosticoCreated, Updated, StatusChanged
├── Exceptions/      Nombre/servicio duplicado, servicio no diagnóstico,
│                    especialidad inactiva/duplicada, cantidad y días inválidos,
│                    sin especialidades, DELETE no permitido
├── Http/            Controller, requests y resource de tipos de diagnóstico
├── Listeners/       RecordCatalogoDiagnosticosAudit
├── Models/          TipoDiagnostico
├── Queries/         Listado, ficha, requisitos cacheados
├── Rules/           Nombre único, servicio diagnóstico, especialidades,
│                    días, estado y participantes requeridos
├── Support/         Snapshot, unicidad y guard
└── routes-tipos-diagnostico.php
```

También se agregó `codigo` a `tipo_servicio` para identificar de forma estable el tipo `diagnostico_evaluacion`, sin depender del nombre configurable.

### Endpoints

Prefijo: `/api/catalogo-servicios`.

| Entidad | Endpoints |
|---|---|
| Tipos de diagnóstico | `GET/POST /tipos-diagnostico`, `GET/PATCH /tipos-diagnostico/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` → `409` |
| Requisitos | `GET /tipos-diagnostico/{id}/especialidades-requeridas` |

Filtros del listado: `search` (nombre/descripción), `estado` (`activo|inactivo`), `especialidad_id`, `page`, `per_page` (máx. 50), `sort` (`nombre|activo|dias_estimados|updated_at`) y `direction`.

Reglas de la API:

- Alta: servicio activo de tipo `diagnostico_evaluacion`, nombre único normalizado, al menos una especialidad activa y cantidad mínima `>= 1`.
- Edición: exige `version`; el estado no se cambia por `PATCH`. Las especialidades enviadas reemplazan el conjunto completo.
- Activación/desactivación: exige `motivo` y `version`; el estado debe ser distinto.
- `DELETE` responde siempre `409 TIPO_DIAGNOSTICO_DELETE_NOT_ALLOWED`.
- `409 VERSION_CONFLICT` para concurrencia; `409 TIPO_DIAGNOSTICO_SERVICIO_DUPLICADO` si el servicio ya tiene tipo; `422` para nombre duplicado, especialidad inactiva/duplicada, cantidad o días inválidos.

### Permisos y alcance

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `tipos_diagnostico.ver` | sí | sí | sí |
| `tipos_diagnostico.editar` | sí | no | no |

El catálogo es global: no hay scoping por sucursal. La API devuelve `can.tipos_diagnostico` y la UI oculta las acciones de escritura; el backend revalida con `PermissionMiddleware`. Seeder: `CatalogoDiagnosticosPermissionSeeder`, con marcador propio `catalogo.diagnosticos`.

### Eventos y bitácora

| Entidad (`bitacora.entidad`) | Eventos |
|---|---|
| `tipo_diagnostico` | `TipoDiagnosticoCreated`, `TipoDiagnosticoUpdated`, `TipoDiagnosticoStatusChanged` |

`RecordCatalogoDiagnosticosAudit` escribe `CREAR`, `ACTUALIZAR`, `ACTIVAR` y `DESACTIVAR`, con snapshots antes/después que incluyen nombre, descripción, días, estado, servicio y **especialidades con cantidad mínima**. `sucursal_id` es `NULL` porque el catálogo es global. `InvalidateCatalogoServiciosCache` invalida la clave `catalogo_servicios:tipos_diagnostico`.

### Contratos y caché

- **`ProfessionalDiagnosticRequirementsContract`** (módulo 03) → `EloquentProfessionalDiagnosticRequirements`: implementación real que devuelve `especialidad_id` + `cantidad_minima` desde el catálogo 05. Reemplaza el stub anterior.
- **`EspecialidadDirectoryContract`** (módulo 03) → `EloquentEspecialidadDirectory`: existencia, estado y profesionales activos por especialidad y sucursal. Es la base para RN-D05 en agenda/diagnósticos.
- **`ParticipantesRequeridosRule`**: regla pura que compara requisitos contra participantes y devuelve faltantes por especialidad.
- **`TipoDiagnosticoUsageContract`** + `StubTipoDiagnosticoUsageContract`: `hasDiagnosticosRegistrados`; se implementará con 06/11.
- `GetEspecialidadesRequeridasQuery` cachea los requisitos con TTL 60 s. `CatalogoServiciosExporterContract` se extendió con `exportarTiposDiagnostico`; `Unavailable...` lanza `LogicException`. Sin ruta ni botón.

### Schema, migraciones y seeders

Migraciones:

- `2026_11_25_000500_add_codigo_to_tipo_servicio_table.php`.
- `2026_11_25_000600_create_tipo_diagnostico_table.php`.
- `2026_11_25_000700_create_tipo_diagnostico_profesional_table.php`.

Cambios:

- `tipo_servicio.codigo` nullable y único; el seeder asigna `entrevista`, `diagnostico_evaluacion`, `terapia_individual` y `sala_grupal`.
- `tipo_diagnostico`: `servicio_id` único con FK, nombre único exacto y funcional, `dias_estimados > 0`, `activo`, `version`.
- `tipo_diagnostico_profesional`: PK compuesta, `cantidad_minima >= 1`, FKs con `ON DELETE CASCADE` e índice por especialidad.
- Sin `deleted_at`; el borrado se rechaza con `409`.
- La FK `paciente.tipo_diagnostico_id` sigue diferida y `PacientesModule::DIAGNOSTICOS_DISPONIBLES` continúa en `false`: la integración con 04 no forma parte de este submódulo.

Seeders: `CatalogoDiagnosticosPermissionSeeder` y `CatalogoDiagnosticosAccionAuditadaSeeder`. No se siembran tipos de diagnóstico ni especialidades: se administran desde la UI.

### Checklist RF-D / RN-D

| RF | Evidencia | Estado |
|---|---|---|
| RF-D01 | `CreateTipoDiagnosticoAction`, `StoreTipoDiagnosticoRequest` | Completo |
| RF-D02 | Pivot `tipo_diagnostico_profesional` + `TipoDiagnosticoEspecialidadesRule` | Completo |
| RF-D03 | Unicidad normalizada + guard + 422 | Completo |
| RF-D04 | `UpdateTipoDiagnosticoAction` + snapshots en bitácora | Completo |
| RF-D05 | `ChangeTipoDiagnosticoStatusAction` con motivo; bloqueo de nuevas inscripciones en 06 | API completa; consumo 06 |
| RF-D06 | `ListTiposDiagnosticoQuery` + filtros UI | Completo |
| RF-D07 | `GetTipoDiagnosticoQuery` + ficha | Completo |
| RF-D08 | Endpoint de requisitos + contrato real de 03 | Completo; consumo 07/11 |
| RF-D09 | `DELETE` siempre `409`; `TipoDiagnosticoUsageContract` stub | Bloqueo completo; uso histórico 06/11 |
| RF-D10 | `RecordCatalogoDiagnosticosAudit` | Completo |
| RF-D11 | API de catálogo + `ProfessionalDiagnosticRequirementsContract` | API completa; consumo 07/11 |
| RF-D12 | `tipos_diagnostico.ver/editar` + `PermissionMiddleware` | Completo |
| RF-D13 | `CatalogoServiciosExporterContract::exportarTiposDiagnostico` stub | Pendiente de infraestructura |

| RN | Evidencia | Estado |
|---|---|---|
| RN-D01 | Catálogo global sin `sucursal_id` | Completo |
| RN-D02 | Índice único funcional + `TipoDiagnosticoNombreUnicoRule` | Completo |
| RN-D03 | Mínimo una especialidad en request, regla y Action | Completo |
| RN-D04 | `cantidad_minima` en pivot y validación `>= 1` | Completo |
| RN-D05 | `EspecialidadDirectoryContract` expone cobertura por especialidad/sucursal | Contrato listo; asignación en 07 |
| RN-D06 | `activo` en API y ficha; históricos conservan su tipo | API completa; consumo 06/11 |
| RN-D07 | Sin `DELETE`; solo desactivación | Completo |
| RN-D08 | `servicio_id` único + validación de `tipo_servicio.codigo` | Completo |
| RN-D09 | `motivo` obligatorio en activar/desactivar | Completo |
| RN-D10 | Superadmin administra; Administración/Recepción solo consultan | Completo |
| RN-D11 | `version` y snapshots; requisitos editables sin tocar diagnósticos previos | Catálogo versionado; snapshot en 06/11 |

**TODOs:** implementar `TipoDiagnosticoUsageContract` con 06/11; exportación PDF/Excel; filtrado de tipos inactivos y snapshot de requisitos en inscripciones/diagnósticos (06/07/11); integrar la FK diferida de `paciente` cuando 04 lo habilite.


---

## Módulo 06 — Inscripciones y atenciones

Submódulos implementados: **inscripción a salas grupales**, **paquetes de sesiones individuales**, **entrevistas y diagnósticos como atenciones** y **vigencia, vencimiento y renovación**. Crean la base del módulo padre `App\Modules\InscripcionesAtenciones` para que los próximos submódulos (estados/pausa) reutilicen el prefijo, el provider y el catálogo de auditoría.

### Estructura

```text
app/Modules/InscripcionesAtenciones/
├── Actions/          Salas: CreateInscripcionSalaGrupal, UpdateInscripcion,
│                     RenovarInscripcion, BajaInscripcion
│                     Paquetes: CreatePaqueteSesionesAction, UpdatePaqueteAction,
│                     RenovarPaqueteAction, BajaPaqueteAction,
│                     RejectPaqueteDeletionAction
│                     Vigencias: RenovarDesdePanelAction, RenovarMultipleAction,
│                     UpdateVentanaAlertaAction
│                     Estados: CambiarEstadoPacienteAction,
│                     RetirarPacienteConCascadaAction, PausarInscripcionAction,
│                     ReactivarInscripcionAction, RetirarInscripcionAction
├── Contracts/        PagoInscripcion, AsistenciaInscripcion,
│                     TurnosFuturosInscripcion, ComprobantePdfExporter,
│                     InscripcionExporter + stubs/unavailable
│                     Paquetes: ConsumoPaquete, PagoPaquete, TurnosFuturosPaquete,
│                     ComprobantePaquetePdfExporter + stubs/unavailable
│                     Estados: PacienteEstadoContract, EstadoExporterContract
├── Enums/            EstadoInscripcionSala, MotivoBaja,
│                     EstadoPaqueteSesiones, MotivoBajaPaquete, DiaSemana,
│                     EstadoInscripcion, AccionEstado, MotivoPausa, MotivoPaciente
├── Events/           InscripcionCreated/Updated/Renewed/Retirada/Pausada/Reactivada,
│                     PaqueteCreated/Updated/Renewed/Retirado, PacienteRetirado
├── Exceptions/       Dominio con render HTTP
├── Http/             Controllers, Requests y Resources de los submódulos
├── Listeners/        RecordInscripcionAudit, RecordPaqueteAudit,
│                     RecordInscripcionEstadoAudit
├── Models/           InscripcionSala, PaqueteSesiones, BitacoraEntry
├── Providers/        InscripcionesAtencionesServiceProvider
├── Queries/          Salas y paquetes: listado, ficha, preview de monto,
│                     renovación, comprobante, pago y turnos futuros
│                     Estados: listados, historial, reporte y prechequeos
├── Rules/            Reglas puras de período, solapamiento, estado,
│                     motivo, tarifa, monto, observaciones, días/hora,
│                     cantidad, duración, saldo, estimación y estados efectivos
├── Support/          Constantes, permisos, abilities, snapshots, joins,
│                     guards, catálogo de motivos y cálculos de período
├── routes.php
├── routes-salas-grupales.php
├── routes-paquetes-sesiones.php
├── routes-entrevista-diagnostico.php
├── routes-vigencias.php
└── routes-estados.php
```

Constantes:

```php
InscripcionesAtencionesModule::MODULE_URL = '/inscripciones-atenciones';
InscripcionesAtencionesModule::MODULE_URL_SALAS_GRUPALES = '/inscripciones-atenciones/salas-grupales';
InscripcionesAtencionesModule::MODULE_URL_PAQUETES = '/inscripciones-atenciones/paquetes-sesiones';
InscripcionesAtencionesModule::MODULE_URL_ENTREVISTA_DIAGNOSTICO = '/inscripciones-atenciones/entrevista-diagnostico';
InscripcionesAtencionesModule::MODULE_URL_VIGENCIAS = '/inscripciones-atenciones/vigencias';
InscripcionesAtencionesModule::MODULE_URL_ESTADOS = '/inscripciones-atenciones/estados';
InscripcionesAtencionesModule::AUDIT_MODULE = 'inscripciones';
InscripcionesAtencionesModule::ENTITY_INSCRIPCION_SALA = 'inscripcion_sala';
InscripcionesAtencionesModule::ENTITY_PAQUETE_SESIONES = 'paquete_sesiones';
InscripcionesAtencionesModule::ENTITY_ATENCION_ENTREVISTA = 'atencion_entrevista';
InscripcionesAtencionesModule::ENTITY_ATENCION_DIAGNOSTICO = 'atencion_diagnostico';
InscripcionesAtencionesModule::ENTITY_RENOVACION_MULTIPLE = 'renovacion_multiple';
```

El provider se registra en `bootstrap/providers.php` después de `CatalogoServiciosServiceProvider`. Los futuros providers de Pagos/Agenda deben registrarse después para reemplazar los stubs de sus contratos.

### Migración

`2026_11_25_000800_create_inscripcion_sala_table.php`:

- Tabla real **`inscripcion_sala`** (no existe una tabla genérica `inscripciones`).
- FKs a paciente, tutor, sala, sucursal, servicio, tarifa, categoría y self-FK `inscripcion_origen_id`.
- `monto_esperado numeric(12,2)` con checks de no negativo y fechas coherentes.
- `estado varchar(20)` con `CHECK IN ('activa','en_pausa','vencida','renovada','retirada')`.
- PostgreSQL: `btree_gist` + constraint `EXCLUDE` `(paciente_id, sala_id, daterange(fecha_inicio, fecha_fin, '[]'))` para estados `activa` y `en_pausa`.
- Índices por sucursal/período, sala/período, paciente/estado y origen.
- **No** se crea `estado_inscripcion_historial`; queda para el submódulo de estados/pausa. La baja se audita en `bitacora`.
- **No** se crean `pago` ni `turno`.

### Permisos

Seeder `InscripcionesAtencionesPermissionSeeder`, marcador `inscripciones.salas_grupales`:

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `inscripcion.ver` | sí | sí | sí |
| `inscripcion.crear` | sí | sí | sí |
| `inscripcion.editar` | sí | sí | no |
| `inscripcion.renovar` | sí | sí | sí |
| `inscripcion.retirar` | sí | sí | no |
| `inscripcion.exportar` (reservado) | sí | sí | no |

El código decide por permiso. No hay comparaciones por nombre de rol. Recepción crea/consulta/renueva; Administración además edita y da de baja.

### Endpoints

Prefijo real: **`/api/inscripciones-atenciones/salas-grupales`**.

| Método | Ruta | Acción/Query | Permiso |
|---|---|---|---|
| GET | `/` | `ListInscripcionesQuery` | `inscripcion.ver` |
| GET | `/monto-esperado` | `PreviewMontoEsperadoQuery` | `inscripcion.ver` |
| GET | `/{id}` | `GetInscripcionQuery` | `inscripcion.ver` |
| GET | `/{id}/renovacion` | `GetRenovacionPreviewQuery` | `inscripcion.renovar` |
| GET | `/{id}/pago` | `GetPagoInscripcionQuery` | `inscripcion.ver` |
| POST | `/{id}/pago` | `IniciarPagoInscripcionQuery` | `inscripcion.ver` (TODO: `pago.crear` cuando exista 08) |
| GET | `/{id}/asistencia` | `GetAsistenciaInscripcionQuery` | `inscripcion.ver` |
| GET | `/{id}/turnos-futuros` | `GetTurnosFuturosQuery` | `inscripcion.retirar` |
| GET | `/{id}/comprobante` | `GetComprobanteDataQuery` | `inscripcion.ver` |
| GET | `/{id}/comprobante/pdf` | `GenerateComprobantePdfQuery` | `inscripcion.ver` |
| GET | `/exportar` | `ExportInscripcionesQuery` | `inscripcion.exportar` |
| POST | `/` | `CreateInscripcionSalaGrupalAction` | `inscripcion.crear` |
| PATCH | `/{id}` | `UpdateInscripcionAction` | `inscripcion.editar` |
| POST | `/{id}/renovar` | `RenovarInscripcionAction` | `inscripcion.renovar` |
| POST | `/{id}/retirar` | `BajaInscripcionAction` | `inscripcion.retirar` |
| DELETE | `/{id}` | siempre `409 INSCRIPCION_DELETE_NOT_ALLOWED` | `inscripcion.retirar` |

Filtros del listado: `search`, `sucursal_id`, `sala_id`, `paciente_id`, `periodo_mes`, `periodo_anio`, `estado` (`activa`, `vencida`, `retirada`, `en_pausa`, `renovada`), `page`, `per_page` (máx. 50), `sort`, `direction`. La respuesta incluye `data`, `links`, `meta` y `can`.

### Alcance, estado y snapshot

- **Alcance por sucursal:** filtro explícito `whereIn('sucursal_id', UserBranchAccessContract::accesibles())` en las Queries; `puedeAcceder()` en las Actions. Sin `GlobalScope`. Ficha/acción fuera de alcance → `403 BRANCH_ACCESS_DENIED`. Listado con filtro de sucursal fuera de alcance → `403`.
- **Sucursal inmutable:** `sucursal_id` y `sala_id` no se modifican por PATCH. Para cambiar de sucursal o sala se da de baja y se crea otra inscripción (RN-007).
- **Estados:** este submódulo solo escribe `activa` y `retirada`. `vencida` se deriva de `fecha_fin`; `en_pausa` y `renovada` quedan reservados para el submódulo de estados/pausa. Sin soft-delete y sin `DELETE`.
- **Dinero:** `monto_esperado` se guarda como `decimal:2`/string. El cliente nunca envía el monto; la Action lo resuelve. `MontoEsperadoRule` usa bcmath y regex de dos decimales.
- **Snapshot de tarifa:** `tarifa_id` + `categoria_tarifa_id` + `monto_esperado`. Cambios posteriores del catálogo no alteran inscripciones existentes. En RF-009, si la tarifa resuelta cambia, se exige `confirmar_tarifa_cambio=true` y se re-snapshotea con bitácora antes/después.
- **Renovación:** nueva inscripción con `inscripcion_origen_id`; por defecto inicio = día siguiente al fin anterior y fin = inicio + 1 mes - 1 día. La anterior no se marca `renovada`; se muestra vencida por fecha.
- **Concurrencia:** `InscripcionOverlapRule` en la Action + `EXCLUDE` PostgreSQL para doble envío.
- **Pagos/Agenda:** la ficha principal no los llama. `GET/POST /{id}/pago` y `GET /{id}/asistencia` son endpoints separados; con stubs responden `503 MODULO_NO_DISPONIBLE`. `BajaInscripcionAction` no llama Agenda: el pre-chequeo de turnos es un endpoint separado y la baja funciona sin él.

### Eventos y bitácora

Listener `RecordInscripcionAudit`, síncrono y fail-closed (`503 AUDIT_UNAVAILABLE`). Entidad `inscripcion_sala`, módulo `inscripciones`.

| Evento | Acción | Payload mínimo |
|---|---|---|
| `InscripcionCreated` | `CREAR` | `inscripcion_id`, paciente, tutor, sucursal, sala, servicio, tarifa, categoría, período, `monto_esperado`, origen, actor, IP, snapshot |
| `InscripcionUpdated` | `ACTUALIZAR` | id, snapshot antes/después, campos cambiados, actor, IP |
| `InscripcionRenewed` | `RENOVAR` | nueva inscripción, origen, paciente, tutor, sucursal, sala, servicio, tarifa, categoría, período, monto, antes/después |
| `InscripcionRetirada` | `RETIRAR` | id, sucursal, paciente, sala, motivo, detalle, `fecha_baja`, antes/después, actor, IP |

`InscripcionCreated`/`InscripcionRenewed` dejan la expectativa de pago para Pagos y Caja. `InscripcionRetirada` deja el bloqueo de nuevas asistencias para Agenda. **No se llaman esos módulos desde las Actions.** La alerta de RN-005 (período iniciado sin pago) pertenece a Reportes/Alertas.

Seeder `InscripcionesAtencionesAccionAuditadaSeeder`: `CREAR`, `ACTUALIZAR`, `RENOVAR` no críticos; `RETIRAR` crítico.

### Contratos

Reales, sin modificar otros módulos:

- `PatientDirectoryContract` (paciente activo y resumen).
- `TutorDirectoryContract` (tutores del paciente, resumen y estado).
- `SalaDirectoryContract` (sala activa en sucursal).
- `BranchDirectoryContract` y `UserBranchAccessContract`.
- `TarifaAplicableResolverContract`.
- `ParameterReaderContract` para los datos del centro y la leyenda.

Stubs propios (503 con `MODULE_NO_DISPONIBLE`):

- `PagoInscripcionContract` / `StubPagoInscripcionContract`.
- `AsistenciaInscripcionContract` / `StubAsistenciaInscripcionContract`.
- `TurnosFuturosInscripcionContract` / `StubTurnosFuturosInscripcionContract`.
- `ComprobantePdfExporterContract` / `UnavailableComprobantePdfExporter`.
- `InscripcionExporterContract` / `UnavailableInscripcionExporter`.

`SalaGrupalServicioGuard` hace una lectura puntual de `servicio`+`tipo_servicio` porque el contrato de Catálogo no expone `codigo`; no modifica el módulo 05. Los joins de presentación (`InscripcionQueryJoins`) son de solo lectura para evitar N+1 y no cambian Pacientes/Organización/Catálogo.

### Comprobante

`GET /{id}/comprobante` devuelve datos reales: centro desde `configuracion`, logo público, paciente, tutor, sala/tipo/turno, sucursal, servicio, categoría, período, monto y leyenda `comprobante.leyenda_no_factura`. El frontend imprime con `window.print()`. El PDF real y la exportación PDF/Excel quedan pendientes de infraestructura (`dompdf`/`snappy`/`maatwebsite/excel` no están instalados).

### Tests

- Unit: período, solapamiento con casos de borde, transición de estados, motivo, tarifa vigente, monto/observaciones.
- Feature: alta, paciente inactivo, duplicado, misma paciente en otra sala, sala inactiva, sala de otra sucursal, scoping, permisos, preview, modificación con bitácora, snapshot inmutable, cambio de tarifa con confirmación, renovación vinculada, baja con motivo, DELETE 409, stubs 503 y comprobante.
- PostgreSQL: tests de la constraint `EXCLUDE` con `phpunit.pgsql.xml`.

### Checklist del submódulo

| RF | Evidencia | Estado |
|---|---|---|
| RF-001 | `CreateInscripcionSalaGrupalAction` + form | Completo |
| RF-002 | `PatientDirectoryContract::asegurarPermiteInscripcion` | Completo |
| RF-003 | `InscripcionOverlapRule` + `EXCLUDE` | Completo |
| RF-004 | Autocompletado sobre API Pacientes | Completo |
| RF-005 | `PreviewMontoEsperadoQuery` + resolver | Completo |
| RF-006 | `InscripcionObservacionesRule` + max 500 | Completo |
| RF-007 | `ListInscripcionesQuery` | Completo |
| RF-008 | `GetInscripcionQuery` + ficha; pago/asistencia separados | Parcial por 08/07 |
| RF-009 | `UpdateInscripcionAction` + bitácora | Completo; pagos pendientes |
| RF-010 | `RenovarInscripcionAction` + `inscripcion_origen_id` | Completo |
| RF-011 | `BajaInscripcionAction` + bitácora | Completo; bloqueo Agenda en 07 |
| RF-012 | Datos + comprobante imprimible; PDF pendiente | Parcial por infraestructura |
| RF-013 | Contrato/stub + endpoints separados | Pendiente 08 |
| RF-014 | Contrato/stub + endpoint separado | Pendiente 07 |
| RF-015 | Contrato/stub + endpoint 503; sin botón | Pendiente infraestructura |
| RF-016 | Queries + Actions + middleware | Completo |

| RN | Evidencia |
|---|---|
| RN-001 | `InscripcionPeriodoRule` |
| RN-002 | `SalaDirectoryContract::asegurarActivaEnSucursal` |
| RN-003 | `InscripcionOverlapRule` + `EXCLUDE` |
| RN-004 | Categoría seleccionada manualmente, sin inferencia |
| RN-005 | Evento con monto/período; alerta en Reportes (pendiente) |
| RN-006 | Sin control de cupo |
| RN-007 | Sucursal/sala inmutables; baja + alta |
| RN-008 | FormRequest + `InscripcionDatosObligatoriosRule` |
| RN-009 | Estado `retirada`; DELETE 409 |
| RN-010 | Leyenda desde configuración |
| RN-011 | Resolver + `TarifaVigenteRule` + confirmación |

| CA | Evidencia |
|---|---|
| CA-001 | Feature de alta |
| CA-002 | Feature de paciente inactivo |
| CA-003 | Feature de duplicidad |
| CA-004 | Feature de preview |
| CA-005 | Feature de modificación + bitácora |
| CA-006 | Feature de renovación vinculada |
| CA-007 | Feature de baja; asistencias en 07 |
| CA-008 | Feature de scoping |
| CA-009 | Comprobante imprimible; PDF pendiente |
| CA-010 | Pendiente 08; UI “No disponible” |

**TODOs:**

- Implementar `PagoInscripcionContract` en 08 y `AsistenciaInscripcionContract`/`TurnosFuturosInscripcionContract` en 07.
- Definir el comportamiento de RF-009 cuando existan pagos: `tienePagos` en el contrato y bloqueo/ajuste de tarifa/categoría/monto.
- Implementar PDF y exportación PDF/Excel cuando se instale la infraestructura.
- Implementar `ServicioUsageContract::hasInscripciones` y separar `TarifaUsageContract` en la conexión real con Catálogo (propuesta pendiente de confirmación).
- La alerta RN-005 corresponde a Reportes/Alertas.

### Submódulo 02 — Paquetes de sesiones individuales

Implementación de `docs/06_INSCRIPCIONES_ATENCIONES/02_paquetes_sesiones_individuales.md` (RF-001 a RF-017, RN-001 a RN-011, CA-001 a CA-010). No modifica Salas Grupales, Catálogo, Pacientes, Profesionales ni Organización; solo consume sus contratos y lecturas puntuales.

Decisiones de modelado:

- Tabla propia **`paquete_sesiones`**; no se usa una tabla genérica `inscripciones`.
- La tarifa es **por sesión**; `monto_esperado` total = tarifa × `cantidad_sesiones`, calculado siempre por el backend con bcmath y guardado como `numeric(12,2)`.
- `faltadas` consume saldo igual que `realizadas`; `canceladas` no descuenta. `saldo = max(0, pactadas − realizadas − faltadas)`.
- `vencido` es un estado **derivado** de `fecha_fin`, sin job ni contador persistido. Solo se escriben `activo` y `retirado`; `en_pausa`/`renovado` quedan reservados.
- Renovación: inicio = día siguiente al fin anterior; fin = misma duración inclusiva del período anterior. El paquete anterior no cambia de estado.
- `dias_semana` se guarda como CSV numérico `1..6` (lunes a sábado) y se expone como arreglo + labels.
- Profesional opcional, validado activo y con relación activa a la sucursal. No se valida especialidad porque Catálogo no tiene relación servicio → especialidad.
- Configuración: se reutiliza `duracion_sesion_minutos` (50) y se agrega `paquete.cantidad_sesiones_default` (10) con seeder.

#### Migración y seeders

`2026_11_26_000000_create_paquete_sesiones_table.php`:

- FKs a paciente, tutor, sucursal, servicio, profesional (nullable), tarifa, categoría y self-FK `paquete_origen_id`.
- Checks de estado, fechas coherentes, monto no negativo, cantidad > 0, duración > 0 y regex de días `^[1-6](,[1-6])*$`.
- PostgreSQL: `btree_gist` + `EXCLUDE` sobre `(paciente_id, servicio_id, daterange(fecha_inicio, fecha_fin, '[]'))` para estados `activo` y `en_pausa`.
- Índices por estado, fechas, sucursal/período, paciente/estado, servicio/fecha, profesional/fecha y origen.

Seeders agregados a `DatabaseSeeder`:

| Seeder | Contenido |
|---|---|
| `PaquetesSesionesPermissionSeeder` | permisos `paquete.*` y asignación por matriz inicial |
| `PaquetesSesionesAccionAuditadaSeeder` | `CREAR`, `ACTUALIZAR`, `RENOVAR` no críticos; `RETIRAR` crítico |
| `PaquetesSesionesConfiguracionSeeder` | `paquete.cantidad_sesiones_default = 10`; no pisa valores existentes |

Para desarrollo: `php artisan migrate` y luego `php artisan db:seed` (o los tres seeders de paquetes por separado). Los usuarios demo se crean aparte con `php artisan db:seed --class=SeguridadDemoUsersSeeder`.

#### Permisos

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `paquete.ver` | sí | sí | sí |
| `paquete.crear` | sí | sí | sí |
| `paquete.editar` | sí | sí | no |
| `paquete.renovar` | sí | sí | sí |
| `paquete.retirar` | sí | sí | no |
| `paquete.exportar` (reservado) | sí | sí | no |

El backend decide siempre por permiso; no hay comparaciones por nombre de rol. Recepción crea/consulta/renueva; Administración además edita y da de baja.

#### Endpoints

Prefijo real: **`/api/inscripciones-atenciones/paquetes-sesiones`**.

| Método | Ruta | Acción/Query | Permiso |
|---|---|---|---|
| GET | `/` | `ListPaquetesQuery` | `paquete.ver` |
| GET | `/opciones` | `GetPaqueteOpcionesQuery` | `paquete.ver` |
| GET | `/monto-esperado` | `PreviewMontoPaqueteQuery` | `paquete.ver` |
| GET | `/exportar` | `ExportPaquetesQuery` | `paquete.exportar` |
| POST | `/` | `CreatePaqueteSesionesAction` | `paquete.crear` |
| GET | `/{id}` | `GetPaqueteQuery` + `PaqueteResource` | `paquete.ver` |
| GET | `/{id}/renovacion` | `GetPaqueteRenovacionPreviewQuery` | `paquete.renovar` |
| GET | `/{id}/consumo` | `GetConsumoPaqueteQuery` | `paquete.ver` |
| GET | `/{id}/turnos-futuros` | `GetTurnosFuturosPaqueteQuery` | `paquete.ver` |
| GET | `/{id}/pago` | `GetPagoPaqueteQuery` | `paquete.ver` |
| POST | `/{id}/pago` | `IniciarPagoPaqueteQuery` | `paquete.ver` (TODO: permiso de 08) |
| GET | `/{id}/comprobante` | `GetPaqueteComprobanteDataQuery` | `paquete.ver` |
| GET | `/{id}/comprobante/pdf` | `GeneratePaqueteComprobantePdfQuery` | `paquete.ver` |
| PATCH | `/{id}` | `UpdatePaqueteAction` | `paquete.editar` |
| POST | `/{id}/renovar` | `RenovarPaqueteAction` | `paquete.renovar` |
| POST | `/{id}/retirar` | `BajaPaqueteAction` | `paquete.retirar` |
| DELETE | `/{id}` | siempre `409 PAQUETE_DELETE_NOT_ALLOWED` | `paquete.retirar` |

Filtros del listado: `search` (nombre/apellido/código/CI del paciente), `sucursal_id`, `paciente_id`, `servicio_id`, `profesional_id`, `periodo_desde`, `periodo_hasta`, `estado` (`activo`, `vencido`, `retirado`, `en_pausa`, `renovado`), `page`, `per_page` (máx. 50), `sort`, `direction`. La respuesta incluye `data`, `links`, `meta` y `can`.

#### Reglas y alcance

| Regla | Evidencia |
|---|---|
| RN-001 cantidad | `CantidadSesionesRule` + default configurable |
| RN-002 días/hora | `DiasHoraRule` + `DiasSemanaCast` + check SQL |
| RN-003 duración | `DuracionSesionRule` + default `duracion_sesion_minutos` |
| RN-004 falta descuenta | `SaldoSesionesRule` |
| RN-005 vencimiento | `EstadoPaqueteSesiones::mostrado()`; no traslada saldo |
| RN-006 profesional opcional | `profesional_id` nullable + `ProfesionalPaqueteGuard` solo si viene |
| RN-007 múltiples paquetes | `PaqueteOverlapRule` + `EXCLUDE` por paciente+servicio |
| RN-008 datos obligatorios | `PaqueteDatosObligatoriosRule` + FormRequests |
| RN-009 pago adelantado | evento/contrato; alerta pertenece a Pagos/Reportes |
| RN-010 no se borra | `BajaPaqueteAction` + `409` en DELETE |
| RN-011 saldo en tiempo real | `GetConsumoPaqueteQuery` + `SaldoSesionesRule`; sin contador persistido |

- **Alcance por sucursal:** filtro explícito `whereIn` en el listado y `puedeAcceder()` en Actions/Queries. Sin `GlobalScope`. Ficha/acción fuera de alcance → `403 BRANCH_ACCESS_DENIED`.
- **Snapshot:** `tarifa_id`, `categoria_tarifa_id` y `monto_esperado` quedan fijos; cambios de catálogo no alteran paquetes existentes. Para una nueva tarifa se exige confirmación (`409 TARIFA_CAMBIO`) y se re-calcula.
- **Modificación (RF-012):** solo profesional, días, hora y observaciones; fechas, cantidad, servicio, categoría, duración y tarifa no se modifican por PATCH. Para cambiar el período se renueva.
- **Concurrencia:** la Action bloquea períodos del paciente/servicio con `lockForUpdate` y revalida solapamiento; el `EXCLUDE` cubre doble envío.
- **Renovación:** crea un paquete nuevo con `paquete_origen_id`; no emite `PaqueteCreated`, emite `PaqueteRenewed`. No modifica el estado del origen.
- **Baja:** cambia a `retirado` y registra motivo/detalle/fecha en bitácora. No consulta Agenda ni cancela turnos: el pre-chequeo es un endpoint separado.

#### Eventos, contratos y bitácora

Listener `RecordPaqueteAudit`, síncrono y fail-closed (`503 AUDIT_UNAVAILABLE`). Entidad `paquete_sesiones`, módulo `inscripciones`.

| Evento | Acción | Payload mínimo |
|---|---|---|
| `PaqueteCreated` | `CREAR` | paquete, paciente, tutor, sucursal, servicio, profesional, tarifa, categoría, cantidad, días, hora, duración, fechas, monto, actor, IP, snapshot |
| `PaqueteUpdated` | `ACTUALIZAR` | id, snapshot antes/después, campos cambiados, actor, IP |
| `PaqueteRenewed` | `RENOVAR` | paquete nuevo, origen, configuración copiada, monto, antes/después |
| `PaqueteRetirado` | `RETIRAR` | id, sucursal, paciente, servicio, motivo, detalle, fecha de baja, antes/después |

Contratos nuevos, sin tocar los de Salas:

- `ConsumoPaqueteContract` / implementación real de Agenda (07.04).
- `PagoPaqueteContract` / `StubPagoPaqueteContract` (503: Pagos y caja).
- `TurnosFuturosPaqueteContract` / implementación real de Agenda (07.03).
- `ComprobantePaquetePdfExporterContract` / `UnavailableComprobantePaquetePdfExporter` (503: PDF).
- `ExportPaquetesQuery` reutiliza `InscripcionExporterContract`; sin infraestructura responde 503.

`TerapiaIndividualServicioGuard` lee `servicio` + `tipo_servicio` para exigir `terapia_individual`; `ProfesionalPaqueteGuard` valida activo y `profesional_sucursal` activa. Son lecturas de solo lectura, sin modificar esos módulos.

#### Tests

- Unit: cantidad, días/hora, duración, vigencia, solapamiento, estados, motivo, monto, saldo, datos obligatorios, observaciones, estimación y período de renovación.
- Feature: alta con/sin profesional, monto calculado, duplicidad, paciente inactivo, servicio no individual, profesional de otra sucursal, alcance por sucursal, tarifa cambiada, preview, actualización con bitácora e inmutables, renovación vinculada, baja con motivo, DELETE 409, listado/filtros/estado derivado, permisos, stubs 503, consumo fake y comprobante.
- PostgreSQL (`phpunit.pgsql.xml`): `PaqueteConstraintTest` valida el `EXCLUDE` de solapamiento y que otro servicio sí permite el mismo período.

#### Checklist RF / RN / CA / RNF

| RF | Evidencia | Estado |
|---|---|---|
| RF-001 | `CreatePaqueteSesionesAction` + formulario Angular | Completo |
| RF-002 | `cantidad_sesiones` + `paquete.cantidad_sesiones_default` | Completo |
| RF-003 | `duracion_minutos` + `duracion_sesion_minutos` | Completo |
| RF-004 | `DiasHoraRule` + selector de días/hora | Completo |
| RF-005 | `profesional_id` nullable + "Sin asignar" | Completo |
| RF-006 | `PatientDirectoryContract::asegurarPermiteInscripcion` | Completo |
| RF-007 | `TarifaAplicableResolverContract` + categoría | Completo |
| RF-008 | `PreviewMontoPaqueteQuery` + `MontoPaqueteRule` | Completo |
| RF-009 | `GetConsumoPaqueteQuery` + pestaña Consumo | Completo con contrato real de Agenda |
| RF-010 | `ConsumoPaqueteContract` implementado por 07.04 | Completo |
| RF-011 | `ListPaquetesQuery` + filtros frontend | Completo |
| RF-012 | `UpdatePaqueteAction` + bitácora antes/después | Completo |
| RF-013 | `RenovarPaqueteAction` + `paquete_origen_id` | Completo |
| RF-014 | `BajaPaqueteAction` + motivo/fecha | Completo |
| RF-015 | Comprobante HTML imprimible; PDF pendiente | Parcial por infraestructura |
| RF-016 | Endpoints separados + contrato de pago | Pendiente módulo 08 |
| RF-017 | Scoping por sucursal en Queries/Actions | Completo |

| RNF | Evidencia | Estado |
|---|---|---|
| RNF-001 | Índices y paginación; sin benchmark formal | Parcial |
| RNF-002 | Saldo calculado en una consulta + regla pura | Completo |
| RNF-003 | Sanctum + hash de Laravel | Completo (infraestructura) |
| RNF-004 | HTTPS y contacto de tutor enmascarado sin permiso | Completo (infraestructura + resource) |
| RNF-005 | Ficha sin datos clínicos | Completo |
| RNF-006/007 | Dependen de infraestructura y respaldos | Fuera del código |
| RNF-008 | Formulario único en español | Completo |
| RNF-009 | Catálogos y sucursales sin hardcodeo | Completo |
| RNF-010 | Impresión disponible; PDF/Excel y `wa.me` del tutor | Parcial (PDF/Excel pendientes) |
| RNF-011 | Catálogos, migración versionada y documentación | Completo |
| RNF-012 | Bitácora obligatoria y DELETE prohibido | Completo |

| CA | Evidencia |
|---|---|
| CA-001 | `PaqueteCreateTest` + `PaqueteCreateSmokeTest` |
| CA-002 | Alta sin profesional |
| CA-003 | Duplicidad mismo servicio/período |
| CA-004 | Contrato de consumo + fake en test (Agenda pendiente) |
| CA-005 | `SaldoSesionesRule` (falla descuenta) |
| CA-006 | Estado vencido derivado sin traslado |
| CA-007 | Renovación vinculada y preview |
| CA-008 | Baja con motivo + bitácora crítica |
| CA-009 | Listado con scoping por sucursal |
| CA-010 | Comprobante HTML con leyenda "No es factura" |

**Pendientes:** implementar Agenda (07) para turnos y consumo real, Pagos (08) para estado/registro de pago, infraestructura PDF/Excel y benchmark de RNF-001. **Fuera de alcance:** creación/cancelación de turnos, asistencias, pagos, pausas/renovaciones automáticas de estado y datos clínicos.

---

### Submódulo 03 — Entrevistas y diagnósticos como atenciones

Implementación de `docs/06_INSCRIPCIONES_ATENCIONES/03_entrevista_diagnostico_atenciones.md` (RF-001 a RF-015, RN-001 a RN-011, CA-001 a CA-010). Reutiliza el módulo padre, la bitácora, los contratos de Pacientes/Tutores/Organización/Catálogo y el patrón de Salas y Paquetes.

#### Migraciones y schema

- `2026_11_26_000100_create_atencion_entrevista_table.php`: tabla `atencion_entrevista`.
- `2026_11_26_000200_create_atencion_diagnostico_table.php`: tabla `atencion_diagnostico`, con `motivo_omision_entrevista` y checks de estado, fechas, monto y coherencia de omisión/entrevista vinculada.
- `2026_11_26_000300_create_atencion_diagnostico_profesional_table.php`: pivote de profesionales.
- `2026_11_26_000400_create_atencion_diagnostico_requisito_table.php`: snapshot de especialidades y cantidades requeridas (RN-D11).

Se actualizaron `database/database.md` y `database/talitacumi_schema.sql`. `estado_general` queda reservado para la etapa 2 y no se expone en este submódulo. La baja se guarda en `bitacora` (motivo y fecha), sin columnas adicionales, igual que en Salas y Paquetes.

#### Permisos

Seeder `EntrevistaDiagnosticoPermissionSeeder`, marcador `inscripciones.entrevista_diagnostico`:

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `atencion.ver` | sí | sí | sí |
| `atencion.crear` | sí | sí | sí |
| `atencion.editar` | sí | sí | no |
| `atencion.retirar` | sí | sí | no |
| `atencion.exportar` (reservado) | sí | sí | no |

#### Endpoints

Prefijo real: **`/api/inscripciones-atenciones/entrevista-diagnostico`**.

| Método | Ruta | Acción/Query | Permiso |
|---|---|---|---|
| GET | `/entrevistas` | `ListAtencionesQuery` (entrevistas) | `atencion.ver` |
| GET | `/entrevistas/monto-esperado` | `PreviewMontoEntrevistaQuery` | `atencion.ver` |
| GET | `/entrevistas/exportar` | Exportación PDF/Excel (stub 503) | `atencion.exportar` |
| POST | `/entrevistas` | `CreateEntrevistaAction` | `atencion.crear` |
| GET | `/entrevistas/{id}` | `GetAtencionQuery` | `atencion.ver` |
| PATCH | `/entrevistas/{id}` | `UpdateEntrevistaAction` | `atencion.editar` |
| POST | `/entrevistas/{id}/retirar` | `BajaAtencionAction` | `atencion.retirar` |
| DELETE | `/entrevistas/{id}` | siempre `409` | `atencion.retirar` |
| GET/POST | `/entrevistas/{id}/pago` | `PagoAtencionContract` (stub 503) | `atencion.ver` |
| GET | `/entrevistas/{id}/comprobante` | `GetAtencionComprobanteDataQuery` | `atencion.ver` |
| GET | `/entrevistas/{id}/comprobante/pdf` | PDF (stub 503) | `atencion.ver` |
| GET | `/diagnosticos` | `ListAtencionesQuery` (diagnósticos) | `atencion.ver` |
| GET | `/diagnosticos/monto-esperado` | `PreviewMontoDiagnosticoQuery` | `atencion.ver` |
| GET | `/diagnosticos/requisitos` | `GetRequisitosDiagnosticoQuery` | `atencion.ver` |
| GET | `/diagnosticos/exportar` | Exportación PDF/Excel (stub 503) | `atencion.exportar` |
| GET | `/entrevistas-previas` | `BuscarEntrevistaPreviaQuery` | `atencion.ver` |
| POST | `/diagnosticos` | `CreateDiagnosticoAction` | `atencion.crear` |
| GET | `/diagnosticos/{id}` | `GetAtencionQuery` | `atencion.ver` |
| PATCH | `/diagnosticos/{id}` | `UpdateDiagnosticoAction` | `atencion.editar` |
| POST | `/diagnosticos/{id}/retirar` | `BajaAtencionAction` | `atencion.retirar` |
| POST | `/diagnosticos/{id}/vincular-entrevista` | `VincularEntrevistaADiagnosticoAction` | `atencion.editar` |
| DELETE | `/diagnosticos/{id}` | siempre `409` | `atencion.retirar` |
| GET/POST | `/diagnosticos/{id}/pago` | `PagoAtencionContract` (stub 503) | `atencion.ver` |
| GET | `/diagnosticos/{id}/comprobante` | `GetAtencionComprobanteDataQuery` | `atencion.ver` |
| GET | `/diagnosticos/{id}/comprobante/pdf` | PDF (stub 503) | `atencion.ver` |

Filtros comunes: `search`, `sucursal_id`, `paciente_id`, `periodo_desde`, `periodo_hasta`, `estado`, `page`, `per_page` (máx. 50), `sort`, `direction`. Diagnósticos agregan `tipo_diagnostico_id` y `profesional_id`.

#### Reglas y decisiones

- 0 profesionales = “Sin asignar” permitido; 1 o más sin cubrir las cantidades del snapshot = `422 PROFESIONALES_INSUFICIENTES` con mensaje “Faltan profesionales requeridos por el tipo”.
- El tipo de diagnóstico fija el `servicio_id`; el cliente no lo envía.
- La tarifa se resuelve por servicio + sucursal + categoría + fecha de inicio. El cliente nunca envía monto; si la tarifa cambió entre preview y guardado se exige confirmación (`409 TARIFA_CAMBIO`).
- Snapshot de tarifa: `tarifa_id`, `categoria_tarifa_id`, `monto_esperado`.
- Snapshot de requisitos: `atencion_diagnostico_requisito`; editar profesionales se valida contra ese snapshot.
- Entrevista: una fecha. Diagnóstico: rango con `fecha_fin >= fecha_inicio`; un solo día permitido; sin máximo. Fechas pasadas permitidas.
- Si el diagnóstico no vincula entrevista, debe marcarse `omitio_entrevista=true`; el motivo es opcional (máx. 500). `BuscarEntrevistaPrevia` solo informa; no bloquea.
- Edición: entrevista solo fecha/notas; diagnóstico solo fechas/profesionales/notas. La atención retirada no se edita. El monto no se recalcula en edición.
- Baja: `activa -> retirada`, motivo cerrado (`retiro_paciente`, `error_registro`, `otro`), detalle obligatorio para “otro”, fecha no futura. Sin borrado físico.
- Scope explícito por `UserBranchAccessContract` en Queries/Actions; sin GlobalScope.

#### Eventos y bitácora

Listener `RecordAtencionAudit`, fail-closed. Entidades `atencion_entrevista` y `atencion_diagnostico`; acciones `CREAR`, `ACTUALIZAR`, `RETIRAR` (crítica) y `VINCULAR`. Se agregaron las etiquetas al `AuditModuleResolver` y el seeder `EntrevistaDiagnosticoAccionAuditadaSeeder`.

Eventos: `EntrevistaCreated`, `DiagnosticoCreated`, `AtencionUpdated`, `AtencionRetirada`, `EntrevistaVinculada`. Los eventos de alta llevan `monto_esperado` y datos suficientes para que Pagos cree la expectativa.

#### Contratos

- `PagoAtencionContract` / `StubPagoAtencionContract` (503).
- `ComprobanteAtencionPdfExporterContract` / `UnavailableComprobanteAtencionPdfExporter` (503).
- `AtencionExporterContract` / `UnavailableAtencionExporter` (503).
- Se reutilizan `EstadoPagoInscripcion` y `RegistroPagoInscripcion`.

#### Tests

- `EntrevistaDiagnosticoTest`: alta de entrevista, alta de diagnóstico con profesionales y snapshot, bloqueo por profesionales insuficientes, diagnóstico sin asignar/omitido, obligación de omitir o vincular, baja y bloqueo posterior, permisos, DELETE 409, pago/exportación stub 503, listado/filtros, rango multidía, vinculación, comprobante, actualizaciones y profesional de otra sucursal.
- `AtencionRulesTest`: fechas, cobertura de profesionales, omisión de entrevista, notas y motivo de baja.
- `InscripcionesAtenciones` completo: 269 passed, 3 skipped PostgreSQL-only, 1042 assertions.

**Pendientes:** Pagos/Agenda/Reportes reales, PDF/Excel, implementación real de `EntrevistaDirectoryContract` de Pacientes y de los stubs de uso del Catálogo (`hasInscripciones`, `hasDiagnosticosRegistrados`, `hasInscripcionesOrPagos`).

---

### Submódulo 04 — Vigencia, vencimiento y renovación

Implementación de `docs/06_INSCRIPCIONES_ATENCIONES/04_vigencia_vencimiento_renovacion.md` (RF-001 a RF-017). Es transversal sobre Salas Grupales y Paquetes: **no crea una entidad nueva** y **delega la renovación** en `RenovarInscripcionAction` y `RenovarPaqueteAction`.

#### Decisión de modelado

- El estado de vigencia es **derivado**, no almacenado:
  `Retirado > Renovado > Vencido > Por vencer > Vigente`.
- `Renovado` se deriva de la existencia de una entidad sucesora no retirada.
- No se modifica el Enum de estado de los submódulos de origen.
- No hay job, columna `estado_vigencia` ni evento por cambio natural de vigencia.
- La ventana de alerta se lee de `configuracion` (`vigencia.ventana_alerta_dias`) y no se hardcodea.
- El panel/reporte une `inscripcion_sala` y `paquete_sesiones` con `UNION ALL` paginable; no se crea vista.
- “Por vencer” es inclusivo: `0 <= fecha_fin - hoy <= ventana`.
- Zona horaria: `America/La_Paz`.

#### Configuración y migraciones

- `VigenciasConfiguracionSeeder`: crea `vigencia.ventana_alerta_dias = 7` (rango 1–365, global) sin pisar valores existentes.
- `2026_11_26_000500_add_vigencia_indexes.php`: índices de panel para `(sucursal_id, fecha_fin)` y `(paciente_id, fecha_fin)` en ambas tablas.
- `ParameterAdminContract` (Organización): lectura/edición encapsulada del parámetro, reutilizando `UpdateParameterAction` para conservar versión, validación, caché y bitácora.
- No se agregan tablas ni columnas de negocio.

#### Permisos

Seeder `VigenciasPermissionSeeder`, marcador `inscripciones.vigencias`:

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `vigencia.ver` | sí | sí | sí |
| `vigencia.reportes` | sí | sí | no |
| `vigencia.exportar` | sí | sí | no |
| `vigencia.configurar` | sí | sí | no |

No existe `vigencia.renovar`: la renovación exige `inscripcion.renovar` o `paquete.renovar`. Los tipos visibles se filtran además por `inscripcion.ver` y `paquete.ver`.

#### Endpoints

Prefijo real: **`/api/inscripciones-atenciones/vigencias`**.

| Método | Ruta | Acción/Query | Permiso |
|---|---|---|---|
| GET | `/panel` | `GetPanelVigenciasQuery` | `vigencia.ver` |
| GET | `/reportes` | `ListVencimientosReporteQuery` | `vigencia.reportes` |
| GET | `/reportes/exportar?formato=pdf\|excel` | `ExportVigenciasQuery`; stub 503 | `vigencia.exportar` |
| GET | `/pacientes/{pacienteId}/resumen` | `GetResumenVigenciaPacienteQuery` | `vigencia.ver` |
| GET | `/entidades/{tipo}/{id}/renovacion` | `PreviewRenovacionQuery` | permiso de renovación del tipo |
| POST | `/entidades/{tipo}/{id}/renovar` | `RenovarDesdePanelAction` | permiso de renovación del tipo |
| POST | `/renovaciones` | `RenovarMultipleAction` | permiso de renovación del tipo |
| GET | `/configuracion/ventana` | `GetVentanaAlertaQuery` | `vigencia.configurar` |
| PATCH | `/configuracion/ventana` | `UpdateVentanaAlertaAction` | `vigencia.configurar` |

Filtros de panel/reportes: `search`, `sucursal_id`, `paciente_id`, `servicio_id`, `tipo_entidad`, `estado`, `periodo_desde`, `periodo_hasta`, `page`, `per_page` (máx. 50), `sort`, `direction`. El panel usa por defecto “por vencer + vencidos”. Los reportes aceptan los cinco estados.

#### Renovación y tarifa

- `PreviewRenovacionQuery` devuelve tarifa anterior, tarifa vigente, `monto_anterior`, `monto_vigente` y `cambio_tarifa`.
- `RenovarDesdePanelAction`:
  - valida permiso subyacente y sucursal;
  - resuelve tarifa actual;
  - exige `monto_visto` y confirmación cuando cambió;
  - delega en la Action de origen con `tarifa_id_esperada` vigente;
  - después de crear, verifica el monto final y revierte si cambió durante la operación.
- `RenovarMultipleAction` es atómica y exige mismo paciente; registra el lote en bitácora con `renovacion_multiple` y mantiene las auditorías individuales.
- No se trasladan sesiones: se crea un paquete nuevo con la cantidad completa.

#### Agenda y pacientes

- `VigenciaEntidadContract` / `EloquentVigenciaEntidad`: `puedeRegistrarAsistencia(tipo, id, fechaTurno)`.
- Usa la fecha del turno, no “hoy”; permite cargar asistencias históricas dentro del período y bloquea fuera del período o si la entidad está retirada.
- El mensaje “Debe renovar...” y el bloqueo de Agenda se implementan en el módulo 07.
- RF-015 expone `GET /pacientes/{id}/resumen` y el componente reutilizable `VigenciaAlertaComponent`; la integración visual en la ficha de Pacientes queda pendiente de autorización de ese módulo.

#### Exportación

`VigenciaExporterContract` es stub (`503`) hasta instalar infraestructura PDF/Excel. Los filtros se envían al contrato para que el archivo real los incluya. No se genera un archivo vacío ni simulado.

#### Tests

- Unit: clasificación con bordes, ventana, elegibilidad, comparación de tarifa.
- Feature: panel, scoping, tipos visibles, reportes, preview, renovación individual, cambio de tarifa entre preview y ejecución, no traslado, múltiple atómica, contrato de Agenda, resumen del paciente, exportación 503 y configuración.
- Índices: migración aplicada en PostgreSQL local.
- Suite completa: **1013 passed, 6 skipped (PostgreSQL-only), 5060 assertions** con `make test`.

**Pendientes:** Agenda (07) para consumo/asistencias reales, Pagos (08), exportación PDF/Excel, integración en la ficha de Pacientes y widget del panel en el inicio (CA-003).

---

### Submódulo 05 — Estados del paciente y de la inscripción

Implementación de `docs/06_INSCRIPCIONES_ATENCIONES/05_estados_paciente_inscripcion.md` (RF-001 a RF-016, RN-001 a RN-012, CA-001 a CA-012). Es transversal sobre Salas, Paquetes y Pacientes.

#### Modelo de estados

- **Persistidos:** paciente (`activo`, `en_pausa`, `retirado`) e inscripción (`activa/activo`, `en_pausa`, `retirada/retirado`).
- **Derivados:** `vencida` y `renovada`; no se escriben ni tienen endpoint manual.
- **Precedencia efectiva:** `Retirada > EnPausa > Renovada > Vencida > Activa`.
- **Pausa:** opción A. Se usa el `estado` existente; `inscripcion_sala.estado` y `paquete_sesiones.estado` ya admitían `en_pausa` en el `CHECK`. No cambia `fecha_inicio`, `fecha_fin` ni el saldo.
- **Paciente:** la máquina base sigue en Pacientes (`EstadoPaciente::allowedTransitions` + `ChangePacienteStatusAction`). 06.05 la consume por `PacienteEstadoContract`; no la duplica.
- **Regla nueva de inscripción:** `EstadoInscripcionTransitionRule`; la regla de retiro existente de Salas/Paquetes se amplió para aceptar `en_pausa`.
- **Vencida/Renovada:** el estado efectivo se calcula con `EstadoEfectivoInscripcionRule`, que reutiliza `VigenciaClassifierRule` con ventana 0 y recibe `hoy` inyectado.
- **Bitácora como historial:** RF-010 se resuelve leyendo la bitácora con permisos de estados; no se creó `estado_inscripcion_historial`. Las bajas y renovaciones previas ya están en bitácora y se muestran como legado real, sin inventar datos.
- **Pacientes:** se separaron permisos: `paciente.cambiar_estado` (pausa/reactivación, Administración) y `paciente.retirar` (solo Administración/Superadmin). Recepción solo consulta estados de paciente. El endpoint histórico de Pacientes exige el permiso específico según el destino.
- **Motivos:** catálogo cerrado en Enums (`MotivoPausa`, `MotivoPaciente`, más los Enums existentes de Salas/Paquetes). Una tabla administrable queda como evolución posterior para no cambiar el schema en esta entrega.
- **Fecha:** pausa/reactivación usan el timestamp de auditoría; el retiro de inscripción admite hasta 30 días hacia atrás; el retiro de paciente usa hoy.

#### Endpoints

Prefijo real: **`/api/inscripciones-atenciones/estados`**.

| Método | Ruta | Permiso |
|---|---|---|
| GET | `/opciones` | `estado.ver` |
| GET | `/pacientes` | `estado.ver` + `paciente.ver` |
| GET | `/pacientes/{id}/historial` | `estado.ver` + `paciente.ver` |
| POST | `/pacientes/{id}/pausar` | `paciente.cambiar_estado` |
| POST | `/pacientes/{id}/reactivar` | `paciente.cambiar_estado` |
| POST | `/pacientes/{id}/retirar` | `paciente.retirar` |
| GET | `/pacientes/{id}/retiro/preview` | `paciente.retirar` + `paciente.ver` |
| GET | `/pacientes/{id}/retiro/prechequeo` | `paciente.retirar` + `paciente.ver` |
| GET | `/inscripciones` | `estado.ver`; tipos filtrados por `inscripcion.ver`/`paquete.ver` |
| GET | `/inscripciones/{tipo}/{id}/historial` | `estado.ver` + permiso de vista del tipo |
| POST | `/inscripciones/{tipo}/{id}/pausar` | `inscripcion.pausar` + vista del tipo |
| POST | `/inscripciones/{tipo}/{id}/reactivar` | `inscripcion.reactivar` + vista del tipo |
| POST | `/inscripciones/{tipo}/{id}/retirar` | `inscripcion.retirar` o `paquete.retirar` |
| GET | `/inscripciones/{tipo}/{id}/prechequeo-pausa` | `inscripcion.pausar` |
| GET | `/inscripciones/{tipo}/{id}/prechequeo-retiro` | permiso de retiro del tipo |
| GET | `/inscripciones/{tipo}/{id}/prechequeo-reactivacion` | `inscripcion.reactivar` |
| GET | `/reportes` | `estado.reportes` |
| GET | `/reportes/exportar` | `estado.exportar`; stub `503` |

#### Matriz de transiciones de inscripción

| Desde | Hacia | Tipo |
|---|---|---|
| Activa | En pausa | Manual |
| Activa | Vencida | Sistema/derivada |
| Activa | Renovada | Sistema/derivada |
| Activa | Retirada | Manual |
| En pausa | Activa | Manual |
| En pausa | Retirada | Manual |
| Vencida | Renovada | Sistema/derivada |

El paciente mantiene la matriz de Pacientes: `activo ↔ en_pausa`, `activo → retirado`, `en_pausa → retirado`, `retirado → activo`.

#### Permisos

Seeder `EstadosPermissionSeeder`:

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `estado.ver` | sí | sí | sí |
| `estado.reportes` | sí | sí | no |
| `estado.exportar` | sí | sí | no |
| `inscripcion.pausar` | sí | sí | sí |
| `inscripcion.reactivar` | sí | sí | sí |

El retiro de inscripción reutiliza `inscripcion.retirar`/`paquete.retirar`. El estado del paciente usa `paciente.cambiar_estado` y `paciente.retirar`; Recepción no los tiene.

#### Eventos y auditoría

- `InscripcionPausada` y `InscripcionReactivada` → `RecordInscripcionEstadoAudit` (`PAUSAR`/`REACTIVAR`).
- `InscripcionRetirada` y `PaqueteRetirado` se reutilizan; no se duplican.
- `PacienteStatusChanged` se reutiliza para pausa/reactivación/retiro.
- `PacienteRetirado` agrega el detalle de la cascada (inscripciones y sucursales) para futuros consumidores.
- El retiro en cascada es una sola transacción: paciente + inscripciones activas/en pausa, mismo motivo y fecha. Si una baja falla, se revierte todo.

#### Agenda, Pagos y exportación

- `VigenciaEntidadContract` ahora usa el estado efectivo: solo `Activa` admite asistencia; el mensaje “La inscripción no está activa” lo muestra Agenda.
- Los prechequeos de turnos y pagos son endpoints separados y no bloquean. Con los stubs responden `disponible=false` y la UI muestra “verificación no disponible”/“estado de pago no disponible”, nunca “sin turnos”/“sin saldo”.
- `EstadoExporterContract` / `UnavailableEstadoExporter` responden `503`; falta infraestructura PDF/Excel.

#### Tests

- Unit: matriz completa de paciente e inscripción, precedencia y bordes de fecha, motivos, asistencia y cascada.
- Feature: pausa/reactivación, bloqueo de inscripción a paciente en pausa/retirado en Salas/Paquetes/Atenciones, retiro individual y en cascada, rollback, permisos, prechequeos tolerantes a stubs, historial, filtros, 409 y exclusión de Vigencia.
- Integración: `EstadosVigenciaTest` y `EstadosAsistenciaTest`.

**Pendientes:** motivos administrables en tabla, integración visual de los componentes en las fichas de Pacientes/Salas/Paquetes, Agenda (07), Pagos (08), PDF/Excel, alertas de próximo pago y benchmark RNF-001.

---

## Módulo 07 — Agenda y turnos (submódulo 01: agenda semanal)

Implementación de `docs/07_AGENDA_TURNOS/01_agenda_semanal_sucursal.md` (RF-001 a RF-014, RN-001 a RN-008, CA-001 a CA-008). Este submódulo es de **solo consulta** (RN-007): no crea, programa, reprograma, cancela ni registra asistencia. Deja la base del módulo padre para 7.2 a 7.5.

### Estructura

```text
app/Modules/AgendaTurnos/
├── Contracts/        AgendaExporterContract + UnavailableAgendaExporter
├── Enums/            EstadoTurno (programado, asistio, falto, cancelado, reprogramado)
├── Exceptions/       Dominio con render HTTP
├── Http/
│   ├── Controllers/  AgendaSemanal, TurnoDetalle, AgendaExport
│   ├── Requests/     Filtros de semana y opciones
│   └── Resources/    Semana, turno, detalle, opciones y sucursal
├── Models/           Turno (lectura) + HoraCast
├── Providers/        AgendaTurnosServiceProvider
├── Queries/          Semana, detalle, opciones, sucursales y datos de exportación
├── Rules/            Semana, rango horario, resumen, sin asignar, acciones y contraste
├── Support/          Scope, resolver batch, DTOs y registry de acciones
└── routes.php
```

Constantes:

```php
AgendaTurnosModule::MODULE_URL = '/agenda-turnos/agenda-semanal';
AgendaTurnosPermission::VER = 'turno.ver';
AgendaTurnosPermission::EXPORTAR = 'turno.exportar';
```

### Schema y migración

`2026_11_26_000600_create_turno_table.php` crea `turno` y `turno_profesional` con el schema de referencia:

- `turno`: sucursal, paciente, sala/paquete/diagnóstico (exactamente uno), servicio, profesional nullable, fecha, horas, duración, estado, motivo de cancelación, origen de reprogramación, usuarios de registro/asistencia y `asistencia_registrada_en`.
- `turno_profesional`: pivote para diagnósticos con varios profesionales (lo poblará 7.5).
- CHECK PostgreSQL: estado válido, `hora_fin > hora_inicio`, duración positiva y un solo origen.
- Índices: `(fecha)`, `(sucursal_id, fecha)`, `(paciente_id)`, `(profesional_id)`, `(estado)`, `(sala_id, fecha)`.
- FKs de usuario a `users` (tabla física real).
- `TurnoFactory` solo para tests; `AgendaTurnosDemoSeeder` es local, no está en `DatabaseSeeder`.

El modelo `Turno` es de lectura en esta entrega; incluye casts de fecha/horas/estado, scopes por sucursal, rango, estado, profesional, sala, servicios y orden.

### Endpoints

Prefijo real: **`/api/agenda-turnos/agenda-semanal`**.

| Método | Ruta | Query | Permiso |
|---|---|---|---|
| GET | `/` | `GetAgendaSemana` | `turno.ver` |
| GET | `/opciones` | `GetOpcionesFiltros` | `turno.ver` |
| GET | `/sucursales` | `GetSucursalesDisponibles` | `turno.ver` |
| GET | `/turnos/{id}` | `GetTurnoDetalle` | `turno.ver` |
| GET | `/exportar` | `GetAgendaExportData` + `AgendaExporterContract` | `turno.exportar` |

Parámetros: `sucursal_id`, `semana` (`YYYY-MM-DD`; se normaliza al lunes), `area_id`, `sala_id`, `sala_sin_asignar`, `profesional_id`, `profesional_sin_asignar`, `servicio_id`, `estado`.

La respuesta de semana incluye sucursal, días visibles, rango horario, turnos, resumen por estado, avisos de días ocultos, disponibilidad de datos y zona horaria. El detalle incluye `acciones`, `acciones_disponibles` y `mensaje_acciones`.

### Alcance, semana y colores

- **Scope explícito** en cada Query con `UserBranchAccessContract`; nunca `GlobalScope`. Sin sucursales → `403 SIN_SUCURSALES_ASIGNADAS`; sucursal ajena → `403 BRANCH_ACCESS_DENIED`. La predeterminada es la primera asignada activa ordenada por nombre.
- **Semana**: lunes a domingo como rango de consulta; los días visibles salen de `agenda.dias_semana_habilitados` (default 1–6). Si hay turnos en días ocultos, se devuelve un aviso con cantidad y fechas.
- **Rango horario**: `agenda.horario_apertura`/`agenda.horario_cierre` expandido por los turnos visibles y redondeado a 30 minutos.
- **Colores**: salen del área del servicio en Catálogo; `ContrasteColorRule` calcula el texto. Un color inválido no se aplica.
- **Resumen**: cuenta sobre turnos filtrados y visibles (`base = turnos_filtrados_visibles`).
- **Zona horaria**: `config('app.timezone')` / `America/La_Paz`. “Hoy” se inyecta a las reglas.

### Datos de otros módulos

Se agregaron métodos batch aditivos, con tests propios, para evitar N+1:

- `PatientDirectoryContract::nombres(array $ids)`.
- `ProfessionalDirectoryContract::nombres(array $ids)` y `nombresPorSucursal(int $id, bool $soloActivos = true)`.
- `SalaDirectoryContract::nombres(array $ids)` y `nombresPorSucursal(int $id, bool $soloActivas = true)`.
- `BranchDirectoryContract::resumenes(array $ids)` + `BranchResumen`.

`DatosOperativosResolver` resuelve pacientes, profesionales, salas, servicios y áreas una vez por request. Si un contrato falla, el dominio queda `disponible=false`, los campos afectados en `null` y la UI muestra **“No disponible”**. Nunca se inventan datos.

### Permisos y acciones

Seeder `AgendaTurnosPermissionSeeder` (marcador `agenda.agenda_semanal`):

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `turno.ver` | sí | sí | sí |
| `turno.exportar` | sí | sí | no |

`AccionesTurnoRegistry` es el punto de integración de 7.3/7.4: 7.3 registra reprogramar/cancelar/historial; 07.04 integra la asistencia desde su propio componente en el panel de detalle y desde FullCalendar. `AccionesDisponiblesRule` sigue filtrando por estado, permisos y acciones ya ejecutadas.

### Frontend e integración

La ruta frontend es `/agenda-turnos/agenda-semanal` (con redirect desde `/agenda`) y usa `turno.ver`. La vista semanal usa FullCalendar 7.0.2 con plugins gratuitos (`timegrid`); las vistas por profesional/sala se resuelven con un tablero agrupado, sin `resource-timegrid` premium.

RF-012 queda preparado con `AgendaRefreshService.notificarCambio()` y refresco periódico configurable (`environment.agendaRefreshSeconds`). No hay websockets.

La exportación PDF usa `AgendaExporterContract` + `UnavailableAgendaExporter`: responde `503 EXPORTACION_NO_DISPONIBLE` hasta instalar infraestructura. La alternativa de vista imprimible se implementa en el frontend con `@media print`.

### Tests

- Unit: semana (incluye 23:30/00:30 en La Paz), resumen, sin asignar, acciones, contraste y rango horario.
- Feature: 500 turnos sin crecimiento de consultas, navegación, colores, sin asignar, filtros, detalle, scope, domingo oculto/configurable, resumen, exportación 503, permisos, contratos caídos y opciones.
- Batch contracts: Pacientes, Profesionales, Salas y Sucursales.

**Pendientes:** 7.5 multi-profesional, `ProfessionalScheduleContract` y PDF backend. 7.2, 7.3 y 7.4 están implementados.

---

## Módulo 07 — Agenda y turnos (submódulo 02: programación y patrones de días)

Implementación de `docs/07_AGENDA_TURNOS/02_programacion_turnos_patrones.md` (RF-001 a RF-016, RN-001 a RN-010, CA-001 a CA-010). Reutiliza la tabla `turno`, el modelo, el enum `EstadoTurno`, el prefijo del padre y el registry de 7.1. Escribe turnos **solo** por generación y regeneración manuales; no hay alta individual, reprogramación, cancelación individual ni asistencia (7.3/7.4/7.5 fuera de alcance).

### Estructura

```text
app/Modules/AgendaTurnos/
├── Actions/
│   ├── GenerarTurnosDesdeOrigen.php        (transacción compartida)
│   ├── GenerarTurnosDesdePaquete.php
│   ├── GenerarTurnosDesdeInscripcionSala.php
│   ├── RegenerarTurnos.php
│   ├── AsignarProfesionalATurnos.php
│   └── Feriados/ (Create, Update, Deactivate)
├── Enums/            TipoOrigenTurno, AccionLoteGeneracion, SeveridadHallazgo,
│                     EstadoOrigenProgramacion
├── Events/           TurnosGenerados, TurnosRegenerados,
│                     TurnoCanceladoPorRegeneracion, ProfesionalAsignadoALote,
│                     FeriadoCreado/Actualizado/Desactivado
├── Http/
│   ├── Controllers/  ProgramacionTurnosController, FeriadoController
│   ├── Requests/     Preview/Generar/Regenerar/Asignar/Feriados
│   └── Resources/    Origen, previews, lote, turno de origen, feriado
├── Listeners/        RecordAgendaGeneracionAudit (fail-closed)
├── Models/           Turno (ahora escribe), TurnoLoteGeneracion, Feriado, BitacoraEntry
├── Queries/          GetOrigenProgramacion, PreviewGeneracionTurnos,
│                     PreviewRegeneracion, GetTurnosDeOrigen, ListFeriados
├── Rules/            GeneradorFechas, PatronDias, DuracionTurno,
│                     TurnoSolapamiento, OrigenGenerable, Regeneracion,
│                     CantidadRestante, ResumenGeneracion, SesionGrupal
├── Support/          GeneradorTurnos, OrigenProgramacionResolver, ProgramacionGuard,
│                     TurnosExistentesResolver, FeriadosResolver, DirectoriosAgenda,
│                     TurnoFilasFactory, TurnoBatchWriter, LoteIdempotencia, DTOs
└── routes-programacion.php
```

Constantes nuevas:

```php
AgendaTurnosModule::MODULE_URL_PROGRAMACION = '/agenda-turnos/programacion-turnos';
AgendaTurnosPermission::GENERAR = 'turno.generar';
AgendaTurnosPermission::REGENERAR = 'turno.regenerar';
AgendaTurnosPermission::ASIGNAR_PROFESIONAL = 'turno.asignar_profesional';
AgendaTurnosPermission::FERIADOS = 'turno.feriados';
```

### Schema y migraciones

- `2026_11_27_000100_create_turno_lote_generacion_table.php`: lote con `tipo_origen`, `origen_id` lógico, `sucursal_id`, `accion`, `clave_idempotencia` única, actor/IP, cantidades y advertencias confirmadas.
- `2026_11_27_000200_add_programacion_fields_to_turno_table.php`: `inscripcion_sala_id`, `lote_generacion_id`, `cancelado_por_lote_id`, `cancelacion_origen`, `cancelado_en`; CHECK de origen de sala y de cancelación; índices únicos parciales por origen/fecha/hora y `EXCLUDE USING gist` por paciente sobre `tsrange(fecha + hora_inicio, fecha + hora_fin, '[)')` para turnos no cancelados/reprogramados. No hay EXCLUDE de profesional ni sala: la sesión grupal los comparte legítimamente.
- `2026_11_27_000300_create_feriado_table.php`: catálogo global o por sucursal, sin precarga, baja lógica y unicidad por fecha/alcance.

### Definiciones clave

- **Sesión grupal:** `sala_id + fecha + hora_inicio + hora_fin + servicio_id`. Hay un turno por inscripción/paciente; los turnos de la misma sesión cuentan como uno solo para profesional y sala.
- **Solapamiento:** por rango (`inicio_a < fin_b && inicio_b < fin_a`), ignorando cancelados y reprogramados. Paciente bloquea siempre; profesional bloquea contra otra sesión; sala advierte contra otra sesión. Los turnos contiguos no se solapan.
- **Futuro:** fecha > hoy, o fecha = hoy con hora de inicio > hora actual, calculado en `America/La_Paz`.
- **Generación:** solo desde una entidad origen en estado efectivo Activa. Las fechas pasadas se omiten; los feriados se excluyen; en paquetes se genera como máximo la cantidad pactada y, si el período tiene menos fechas, se advierte “faltan N sesiones”.
- **Regeneración:** cancela solo turnos futuros en estado Programado del origen; preserva Asistió, Faltó, Cancelado, Reprogramado y los Programados pasados sin marcar. La cantidad restante de un paquete es `pactadas − Asistió − Faltó` (Cancelado no consume). Con 0 restantes solo se cancela/preserva.
- **Asignación de profesional:** por lote o subconjunto; solo turnos futuros Programados; todo o nada; valida activo y asignado a la sucursal; no sincroniza `paquete_sesiones.profesional_id`.
- **Idempotencia:** advisory lock transaccional por `(tipo_origen, origen_id)` en PostgreSQL + `clave_idempotencia` única + bloqueo natural “ya tiene turnos activos” + índices únicos parciales. La previsualización no persiste y la confirmación recalcula todo.
- **Transaccionalidad:** lote + turnos + bitácora (evento síncrono) dentro de una sola `DB::transaction()`. Si falla cualquier inserción o la bitácora, no queda nada.

### Endpoints

Prefijo real: **`/api/agenda-turnos/programacion-turnos`**.

| Método | Ruta | Action/Query | Permiso |
|---|---|---|---|
| GET | `/origenes/{tipo}/{id}` | `GetOrigenProgramacion` | `turno.ver` + ver origen |
| GET | `/origenes/{tipo}/{id}/turnos` | `GetTurnosDeOrigen` | `turno.ver` + ver origen |
| GET | `/origenes/{tipo}/{id}/preview-generacion` | `PreviewGeneracionTurnos` | `turno.generar` + ver origen |
| POST | `/origenes/{tipo}/{id}/generar` | `GenerarTurnosDesdePaquete` / `...InscripcionSala` | `turno.generar` + ver origen |
| GET | `/origenes/{tipo}/{id}/preview-regeneracion` | `PreviewRegeneracion` | `turno.regenerar` + ver origen |
| POST | `/origenes/{tipo}/{id}/regenerar` | `RegenerarTurnos` | `turno.regenerar` + ver origen |
| POST | `/turnos/asignar-profesional` | `AsignarProfesionalATurnos` | `turno.asignar_profesional` |
| GET | `/feriados` | `ListFeriados` | `turno.feriados` |
| POST | `/feriados` | `CreateFeriadoAction` | `turno.feriados` |
| PATCH | `/feriados/{id}` | `UpdateFeriadoAction` | `turno.feriados` |
| POST | `/feriados/{id}/desactivar` | `DeactivateFeriadoAction` | `turno.feriados` |

`{tipo}` es `inscripcion_sala` o `paquete_sesiones`. **No existe `DELETE` de turnos.** La cancelación por regeneración es interna; la individual pertenece a 7.3.

`generar`/`regenerar` requieren `clave_idempotencia` (UUID) y aceptan `confirmar_advertencias`. La generación acepta `profesional_id` (nullable) y `usar_profesional_origen`.

### Permisos

Seeder `AgendaTurnosProgramacionPermissionSeeder` (marcador `agenda.programacion_turnos`):

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `turno.ver` | sí | sí | sí |
| `turno.generar` | sí | sí | sí |
| `turno.asignar_profesional` | sí | sí | sí |
| `turno.regenerar` | sí | sí | no |
| `turno.feriados` | sí | sí | no |

Además, cada endpoint de origen exige el permiso de vista del origen (`inscripcion.ver` o `paquete.ver`) y el scope de sucursal se valida en la Query/Action. No se decide por nombre de rol.

### Eventos y bitácora

- `TurnosGenerados(loteId, tipoOrigen, origenId, sucursalId, cantidadCreados, turnoIds, advertencias, actorId, ip)`.
- `TurnosRegenerados(loteId, tipoOrigen, origenId, sucursalId, cantidadCreados, cantidadCancelados, cantidadPreservados, turnosCreadosIds, turnosCanceladosIds, advertencias, actorId, ip)`.
- `TurnoCanceladoPorRegeneracion(loteId, tipoOrigen, origenId, sucursalId, turnoIds, actorId, ip)`.
- `ProfesionalAsignadoALote(loteId, profesionalId, sucursalId, turnoIds, actorId, ip)`.
- `FeriadoCreado/Actualizado/Desactivado`.

`RecordAgendaGeneracionAudit` escribe en `bitacora` (`modulo=agenda`) con `GENERAR`, `REGENERAR`, `ASIGNAR_PROFESIONAL` y acciones de feriados. Es síncrona y fail-closed: si la bitácora falla, la transacción se revierte.

### Contratos cross-módulo

- **Implementado en Inscripciones:** `OrigenTurnosContract::obtener(tipo, id): ?OrigenTurnosResumen`, con `EloquentOrigenTurnos`. Expone sucursal, paciente, servicio, sala, profesional, período, cantidad, estado efectivo y —solo paquetes— días/hora/duración.
- **Implementado en Organización:** `SalaPatronContract::patron(salaId): ?SalaPatronResumen`. El binding actual es `StubSalaPatronContract`, que lanza `LogicException`; por eso **RF-001 (generación desde sala) queda pendiente** y responde `422 PATRON_NO_DISPONIBLE` con mensaje claro.
- **Pacientes/Profesionales/Salas/Catálogo:** se reutilizan los métodos batch de 7.1. Si fallan, los nombres muestran “No disponible” sin romper el cálculo.

### Conexiones pendientes con Inscripciones

- `TurnosFuturosInscripcionContract` y `TurnosFuturosPaqueteContract` todavía apuntan a stubs; pueden implementarse contra `turno` sin depender de 7.4. No se reemplazaron en esta entrega para no alterar el comportamiento de pausa/baja.
- `ConsumoPaqueteContract`, `ConsumoPaqueteBatchContract` y `AsistenciaInscripcionContract` fueron implementados por 07.04 sobre `turno`; no escriben contadores de 06.
- Los botones “Generar turnos” de las fichas de Salas y Paquetes navegan a `/agenda-turnos/programacion-turnos?tipo=...&id=...&accion=generar`. No se generan turnos automáticamente al crear, renovar o editar.
- El seeder demo de 7.1 (`AgendaTurnosDemoSeeder`) sigue siendo solo local y no es fuente de turnos de 7.2. Para pruebas manuales conviene generar desde un paquete real.

### Frontend

Ruta `/agenda-turnos/programacion-turnos` con `permissionGuard` `turno.ver` y entrada “Programación” en el sidebar. Incluye:

- Pestaña **Programación**: abre un origen por tipo/ID o por query params desde las fichas, muestra el patrón, los turnos existentes y lanza el wizard.
- Pestaña **Feriados** (solo `turno.feriados`): filtro por año/sucursal, alta global o por sucursal y baja lógica.
- `GenerarTurnosWizardComponent` autocontenido, máximo 4 pasos: patrón, previsualización, profesional opcional (“Sin asignar”) y confirmación. Muestra bloqueos/advertencias con texto, exige confirmación explícita, protege el doble clic y usa una clave de idempotencia por intento. Al terminar llama a `AgendaRefreshService.notificarCambio()`.
- Si Salas no expone el patrón, muestra el mensaje de pendiente; si Pacientes/Profesionales/Salas fallan, muestra “No disponible”.

### Tests

- Unit: `GeneradorFechasRule` (CA-001: 9 fechas; CA-005; domingo; feriados; pasadas; tope), `TurnoSolapamientoRule` (parcial, contiguos, mismo rango, cancelados, misma sesión grupal, bloqueo vs advertencia, fechas distintas), `OrigenGenerableRule`, `RegeneracionRule`, `CantidadRestanteRule`, `ResumenGeneracionRule`, `PatronDiasRule`, `DuracionTurnoRule`, `SesionGrupalRule`.
- Feature: generación CA-001, preview sin persistir CA-002, solapamiento paciente/profesional CA-003/CA-004, fuera de vigencia CA-005, sin profesional CA-006, regeneración CA-007, transaccionalidad CA-008, sucursal CA-009, bitácora CA-010, idempotencia/doble envío, origen ya generado, feriados, grupo sin falsos bloqueos, asignación todo o nada, 50 turnos sin N+1, sin DELETE, sala sin patrón y contrato de origen.
- `OrigenTurnosContractTest` y `SalaPatronContractTest` cubren las conexiones cross-módulo.

**Pendientes:** RF-001 real (patrón de sala), RNF-010 PDF (503 + vista imprimible), sincronización opcional del profesional del paquete (decisión de Inscripciones). Los contratos `TurnosFuturos*`, `ConsumoPaquete*` y `AsistenciaInscripcion` quedaron implementados en 7.3/07.04.

---

## Módulo 07 — Agenda y turnos (submódulo 03: reprogramación y cancelación)

Implementación de `docs/07_AGENDA_TURNOS/03_reprogramacion_cancelacion_turnos.md` (RF-001 a RF-015, RN-001 a RN-010, CA-001 a CA-010). Reutiliza `turno`, `EstadoTurno` (que ya incluía `reprogramado`), `turno_origen_id`, `TurnoSolapamientoRule`, `SesionGrupalRule`, `AgendaAccessScope`, `AgendaRefreshService` y la bitácora. No implementa 7.4 (asistencia), 7.5 (multi-profesional) ni generación/regeneración.

### Estructura

```text
app/Modules/AgendaTurnos/
├── Actions/Cambios/
│   ├── ReprogramarTurno.php
│   ├── CancelarTurno.php
│   └── CancelarTurnosMasivo.php
├── Enums/            AccionCambioTurno, OrigenMotivoTurno
├── Events/           TurnoReprogramado, TurnoCancelado, TurnosCanceladosMasivamente
├── Exceptions/       TurnoNoModificable, EstadoTurnoDesactualizado, MotivoObligatorio,
│                     ConflictoDisponibilidad, TurnoFueraDeVigencia, OrigenNoReprogramable,
│                     CancelacionMasivaFueraDeSucursal, ConjuntoCancelacionCambio, ...
├── Http/             Controllers/Cambios, Requests/Cambios, Resources/Cambios
├── Listeners/        RecordCambiosTurnoAudit (bitácora = historial)
├── Models/           MotivoTurno; Turno ampliado
├── Providers/        CambiosTurnosServiceProvider
├── Queries/Cambios/  GetTurnoCambio, PreviewReprogramacion, PreviewCancelacion,
│                     PreviewCancelacionMasiva, GetOrigenesCancelables, GetHistorialTurno,
│                     ListMotivosTurno, ReporteCambiosTurnos, ExportCambiosTurnos,
│                     GetTurnoContactoWhatsApp
├── Rules/            TurnoModificable, EstadoEsperado, MotivoObligatorio, NuevaFecha,
│                     DisponibilidadReprogramacion, OrigenReprogramable, EfectoSaldo,
│                     CancelacionMasiva
├── Support/          calculadoras compartidas, locks, saldo, motivos, WhatsApp, historial
├── Contracts/        CambiosTurnosExporter + Unavailable + TurnosFuturos reales
└── routes-cambios.php
```

### Schema

- `2026_11_28_000100_create_motivo_turno_table.php`: catálogo configurable; `origen` y `descuenta_saldo` nullable a propósito.
- `2026_11_28_000200_add_cambios_fields_to_turno_table.php`: `motivo_turno_id`, `motivo_detalle`, `consume_saldo`, `cancelado_por_usuario_id`; índice `(sucursal_id, fecha, estado)`; único parcial `turno_origen_unico` sobre `turno_origen_id`; CHECK `consume_saldo` solo en cancelado.
- No se cambia el CHECK de estado: `reprogramado` ya existía desde 7.1/7.2.
- **No se crea tabla de historial**: se reutiliza `bitacora` (`entidad='turno'`, `entidad_id`, `accion`, `datos_anteriores/nuevos`, `usuario_id`), igual que Estados. Es inmutable y 7.4 escribirá `ASISTIO`/`FALTO` en el mismo lugar.

### Máquina de estados y trazabilidad

- `Programado → Reprogramado`: original preservado, nuevo turno Programado con `turno_origen_id`, historial con vínculo bidireccional.
- `Programado → Cancelado`: `cancelacion_origen='manual'`, motivo, usuario, fecha, snapshot `consume_saldo`; no se borra.
- `Asistió`/`Faltó`: los escribe 7.4; 7.3 no los modifica.
- El original reprogramado no se vuelve a reprogramar/cancelar; el turno nuevo Programado sí.
- Bitácora: `REPROGRAMAR` + `CREAR_POR_REPROGRAMACION`; `CANCELAR`; `CANCELAR_MASIVO` (por turno y por lote `turno_cancelacion_masiva`).

### Saldo

- `consume_saldo` es un snapshot inmutable resuelto al cancelar desde `motivo_turno.descuenta_saldo`; `null/false` = no consume.
- El preview muestra saldo actual/resultante solo si `ConsumoPaqueteContract` está disponible; con el stub muestra “Efecto en saldo no disponible” y la cancelación funciona igual.
- `CantidadRestanteRule` de 7.2 ahora resta también `Cancelado` con `consume_saldo=true`; `TurnoExistente` expone `consumeSaldo` y `turnoOrigenId`.
- `SaldoSesionesRule`/consumo real de Paquetes sigue pendiente de 7.4; no se inventan saldos.

### Motivos

Seeder `MotivosTurnoSeeder`: cinco motivos de la spec (`enfermedad_paciente`, `ausencia_profesional`, `feriado`, `motivo_familiar`, `otro`), idempotente. `origen` y `descuenta_saldo` quedan NULL hasta confirmación funcional. `otro` exige texto. No hay CRUD administrativo de motivos en esta entrega; el catálogo se administra por seeder/DB. 07.05 agrega `cancelacion_diagnostico` con `DiagnosticoMotivoTurnoSeeder` (`aplica_cancelar=true`, `aplica_reprogramar=false`, sin texto obligatorio).

### Endpoints

Prefijo **`/api/agenda-turnos/cambios-turnos`**:

| Método | Ruta | Query/Action | Permiso |
|---|---|---|---|
| GET | `/turnos/{id}` | `GetTurnoCambio` | `turno.ver` |
| GET | `/turnos/{id}/historial` | `GetHistorialTurno` | `turno.historial` |
| GET | `/turnos/{id}/contacto-whatsapp` | `GetTurnoContactoWhatsApp` | `turno.ver` + `tutor.ver_contacto` |
| POST | `/turnos/{id}/preview-reprogramacion` | `PreviewReprogramacion` | `turno.reprogramar` |
| POST | `/turnos/{id}/reprogramar` | `ReprogramarTurno` | `turno.reprogramar` |
| POST | `/turnos/{id}/preview-cancelacion` | `PreviewCancelacion` | `turno.cancelar` |
| POST | `/turnos/{id}/cancelar` | `CancelarTurno` | `turno.cancelar` |
| GET | `/motivos?accion=` | `ListMotivosTurno` | `turno.ver` |
| GET | `/pacientes/{id}/origenes-cancelables` | `GetOrigenesCancelables` | `turno.cancelar_masivo` |
| POST | `/cancelacion-masiva/preview` | `PreviewCancelacionMasiva` | `turno.cancelar_masivo` |
| POST | `/cancelacion-masiva` | `CancelarTurnosMasivo` | `turno.cancelar_masivo` |
| GET | `/reporte-cambios` | `ReporteCambiosTurnos` | `turno.reporte_cambios` |
| GET | `/reporte-cambios/exportar` | `ExportCambiosTurnos` | `turno.exportar_cambios` |

No existe `DELETE`. La ejecución exige `estado_esperado` (individual) o el conjunto exacto de ids (masiva); si cambió, 409. La exportación responde 503 con contrato `Unavailable`.

### Permisos

Seeder `CambiosTurnosPermissionSeeder` (marcador `agenda.cambios_turnos`):

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `turno.ver` | sí | sí | sí |
| `turno.reprogramar` | sí | sí | sí |
| `turno.cancelar` | sí | sí | sí |
| `turno.historial` | sí | sí | sí |
| `turno.cancelar_masivo` | sí | sí | no |
| `turno.reporte_cambios` | sí | sí | no |
| `turno.exportar_cambios` | sí | sí | no |

WhatsApp requiere además `tutor.ver_contacto` (Recepción ya lo tiene). El backend revalida permisos y sucursal.

### Eventos y contratos

- `TurnoReprogramado(turnoOriginalId, turnoNuevoId, sucursalId, pacienteId, motivo, original, nuevo, advertencias, actorId, ip)`.
- `TurnoCancelado(turnoId, sucursalId, pacienteId, fecha, horaInicio, horaFin, motivo, consumeSaldo, advertencias, actorId, ip, masivo, loteId)`.
- `TurnosCanceladosMasivamente(loteId, pacienteId, entidades, turnoIds, sucursales, motivo, actorId, ip)`.
- `RecordCambiosTurnoAudit` escribe bitácora/historial fail-closed.
- `TurnosFuturosInscripcionContract` y `TurnosFuturosPaqueteContract` ahora tienen implementación real en Agenda (`EloquentTurnosFuturos*`) bindeada en Inscripciones; el pre-chequeo de pausa/baja devuelve cantidades reales. `ConsumoPaquete*` y `AsistenciaInscripcionContract` fueron implementados por 07.04.
- `CambiosTurnosExporterContract` + `Unavailable` para PDF/Excel (503).

### Frontend

- Ruta `/agenda-turnos/cambios-turnos`; entrada “Cambios de turnos” en sidebar con `turno.cancelar_masivo`; tabs “Cancelación masiva” y “Reporte de cambios” según permisos.
- Componentes reutilizables `reprogramar-turno-modal`, `cancelar-turno-modal`, `historial-turno`; se integran en el panel de detalle de 7.1 (botones Reprogramar/Cancelar/Ver historial).
- Preview con bloqueos/advertencias, confirmación explícita, saldo o “no disponible”, WhatsApp manual, refresco de `AgendaRefreshService`.
- Reporte con filtros, paginación y exportación 503 clara.

### Tests

- Unit: `TurnoModificableRule`, `EstadoEsperadoRule`, `MotivoObligatorioRule`, `NuevaFechaRule`, `DisponibilidadReprogramacionRule`, `OrigenReprogramableRule`, `EfectoSaldoRule`, `CancelacionMasivaRule`; `CantidadRestanteRule` con cancelado que consume.
- Feature: CA-001 a CA-010, transacción con fallo, doble envío, sucursal, WhatsApp, reporte/scoping, export 503, snapshot inmutable, no DELETE.
- Conexiones: tests de 7.1, 7.2 e Inscripciones/Estados actualizados al comportamiento real de `TurnosFuturos*`.

**Pendientes:** exportación PDF/Excel, autorización expresa de Administración para Faltó (fuera de alcance), reprogramación de una sala completa (limitación documentada) y decisión sobre cancelaciones 07.03 con `consume_saldo` en el saldo de 06.02.

---

## Módulo 07 — Agenda y turnos (submódulo 04: registro de asistencia)

Implementación de `docs/07_AGENDA_TURNOS/04_registro_asistencia_turno.md` (07.04/RF-001 a 07.04/RF-015, 07.04/RN-001 a 07.04/RN-010, 07.04/CU-01 a 07.04/CU-03, 07.04/CA-001 a 07.04/CA-010). Reutiliza `turno`, `EstadoTurno`, `AccionCambioTurno`, `AgendaAccessScope`, `bitacora`, `AgendaRefreshService` y los contratos de lectura de 06.01/06.02 y 04.04. No implementa 07.03 ni 07.05.

### Estructura

```text
app/Modules/AgendaTurnos/
├── Actions/Asistencia/          RegistrarAsistencia, CorregirAsistencia
├── Contracts/                   EloquentAsistenciaInscripcion, EloquentConsumoPaquete,
│                                EloquentConsumoPaqueteBatch, EloquentPatientAttendanceSummary,
│                                AsistenciaPdfExporterContract, UnavailableAsistenciaPdfExporter
├── Enums/                       AsistenciaEvento
├── Events/                      AsistenciaRegistrada, AsistenciaCorregida
├── Exceptions/                  TurnoNoAsistible, AsistenciaYaRegistrada,
│                                AsistenciaFueraDeVentana, MotivoAsistenciaRequerido,
│                                TurnoVersionConflicto, ConfiguracionAsistenciaNoDisponible,
│                                ExportacionAsistenciaNoDisponible
├── Http/                        Controllers/Asistencia, Requests/Asistencia, Resources/Asistencia
├── Listeners/                   RecordAsistenciaAudit
├── Providers/                   AsistenciaTurnosServiceProvider
├── Queries/Asistencia/          GetAsistenciaTurno, GetIndicadoresAsistencia,
│                                GetResumenDiarioAsistencia, ExportarAsistenciaCsv
├── Rules/                       TransicionAsistencia, TurnoAsistible, VentanaEdicionAsistencia,
│                                MotivoAsistencia, PorcentajeAsistencia, RangoIndicadores
├── Support/                     AsistenciaAcceso, AsistenciaCalculadora, AsistenciaClock,
│                                AsistenciaTurnosModule, AsistenciaTurnosPermission,
│                                SaldoAsistenciaResolver, IndicadorAsistenciaResolver
└── routes-asistencia.php
```

### Schema

- `2026_11_29_000100_add_version_to_turno_table.php`: agrega `turno.version` (`unsignedInteger`, default 1). Es la versión optimista de 07.04; 07.03 no la incrementa, pero el estado `cancelado/reprogramado` bloquea la asistencia.
- No se crean columnas de asistencia: `usuario_asistencia_id` y `asistencia_registrada_en` ya existen desde la tabla base.
- No se crea tabla de historial: la bitácora es el historial (`entidad='turno'`, `accion=ASISTIO|FALTO`, `datos_nuevos.evento`, `fuera_ventana`, `motivo`).
- El saldo sigue siendo derivado de 06.02; 07.04 no escribe contadores ni tablas de Inscripciones.

### Máquina de estados

| Desde | Hacia | Quién |
|---|---|---|
| `programado` | `asistio` / `falto` | 07.04 |
| `asistio` | `falto` | 07.04 (corrección) |
| `falto` | `asistio` | 07.04 (corrección) |
| `cancelado` / `reprogramado` | cualquier asistencia | bloqueado |
| `asistio` / `falto` | `programado` | no permitido |

`reprogramado` es terminal para el turno original; el turno nuevo nace `programado`. 07.04 solo escribe `ASISTIO`/`FALTO`.

### Ventana y motivo

- `ventana_edicion_asistencia_horas` se lee de Configuración. `0` = sin ventana: toda corrección requiere permiso especial.
- La ventana corre desde `asistencia_registrada_en` (primer registro) y no se reinicia con cada corrección.
- Primer registro: desde `fecha + hora_inicio` en `zona_horaria`; no se permite antes. Sin límite superior por defecto (registro tardío permitido).
- Dentro de ventana: `asistencia.registrar`, motivo opcional.
- Fuera de ventana: `asistencia.corregir_fuera_ventana`, motivo obligatorio, bitácora crítica.
- `zona_horaria` se lee con `ParameterReaderContract`; `asistencia_registrada_en` se guarda como timestamp UTC.

### Endpoints

Prefijo **`/api/agenda-turnos/asistencia`**:

| Método | Ruta | Query/Action | Permiso |
|---|---|---|---|
| GET | `/turnos/{id}` | `GetAsistenciaTurno` | `asistencia.ver` |
| POST | `/turnos/{id}/registrar` | `RegistrarAsistencia` | `asistencia.registrar` |
| POST | `/turnos/{id}/corregir` | `CorregirAsistencia` | `asistencia.registrar`; fuera de ventana exige `asistencia.corregir_fuera_ventana` |
| GET | `/indicadores` | `GetIndicadoresAsistencia` | `asistencia.ver_indicadores` |
| GET | `/resumen-diario` | `GetResumenDiarioAsistencia` | `asistencia.resumen_diario` |
| GET | `/exportar` | `ExportarAsistenciaCsv` / PDF 503 | `asistencia.exportar` |

El historial se reutiliza de 07.03: `GET /api/agenda-turnos/cambios-turnos/turnos/{id}/historial` (`turno.historial`). La bitácora ya incluía `ASISTIO`/`FALTO`; 07.04 agrega `evento` y `fuera_ventana` al presenter. No existe `DELETE`.

### Permisos

Seeder `AsistenciaTurnosPermissionSeeder` (marcador `agenda.asistencia_turnos`):

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `asistencia.ver` | sí | sí | sí |
| `asistencia.registrar` | sí | sí | sí |
| `asistencia.corregir_fuera_ventana` | sí | sí | no |
| `asistencia.ver_indicadores` | sí | sí | sí |
| `asistencia.resumen_diario` | sí | sí | sí |
| `asistencia.exportar` | sí | sí | no (SUPUESTO: FP-04 menciona Recepción) |

El backend revalida permisos, sucursal, estado y ventana; `can` por turno se calcula en backend y un turno fuera de alcance responde 404.

### Contratos

- Implementa `AsistenciaInscripcionContract` (06.01), `ConsumoPaqueteContract` y `ConsumoPaqueteBatchContract` (06.02) y `PatientAttendanceSummaryContract` (04.04).
- Los bindings se hacen en `InscripcionesAtencionesServiceProvider` y `PacientesServiceProvider`.
- `OrigenTurnosContract` aporta estado efectivo, período y cantidad de sesiones.
- No hay escritura cruzada: 06.02 sigue calculando el saldo.

### Bitácora

- Registro: `ASISTIO`/`FALTO`, `datos_nuevos.evento=registro`.
- Corrección: `evento=correccion` o `correccion_fuera_ventana`; `fuera_ventana`; `motivo`.
- `RecordAsistenciaAudit` es síncrono y fail-closed.
- No guarda nombres de pacientes, servicios ni datos clínicos. Las lecturas del listado no se auditan.
- Catálogo `accion_auditada`: `(agenda, turno, ASISTIO)` y `(agenda, turno, FALTO)`.

### Frontend

- Registro rápido en `app-agenda-calendario` (FullCalendar) con botones ✓/✕ en turnos `programado` ya iniciados; refresco con `AgendaRefreshService`.
- Panel de detalle: `AsistenciaTurnoAccionesComponent` con estado actual, registro, corrección e historial.
- Corrección: `CorregirAsistenciaModalComponent` con motivo, confirmación y efecto en saldo/indicadores.
- Ruta `/agenda-turnos/asistencia` con tabs **Indicadores** y **Resumen diario**; exportación CSV solo agregada, sin PDF.
- Sin dependencias nuevas, sin NgModules.

### Tests

- Unit: transiciones, turno asistible (futuro, origen, período), ventana (borde exacto y 0), motivo, porcentaje, rango.
- Feature: registro, idempotencia, conflicto de versión, cancelado/reprogramado, futuro, sucursal 404, bitácora fail-closed, corrección dentro/fuera de ventana, motivo, ventana 0, contratos 06.01/06.02/04.04, indicadores, resumen diario, CSV, PDF 503.
- SQLite y `phpunit.pgsql.xml` para locks/constraints.

### Checklist 07.04

| RF | Estado | Evidencia |
|---|---|---|
| 07.04/RF-001 | Completo | `RegistrarAsistencia` + UI agenda/detalle |
| 07.04/RF-002 | Completo | Saldo derivado + `EloquentConsumoPaquete` |
| 07.04/RF-003 | Completo | `EstadoTurno` + `TransicionAsistenciaRule` |
| 07.04/RF-004 | Completo | `VentanaEdicionAsistenciaRule` |
| 07.04/RF-005 | Completo | `CorregirAsistencia` + `MotivoAsistenciaRule` |
| 07.04/RF-006 | Completo | Bitácora + historial de 07.03 |
| 07.04/RF-007 | Completo | Botones ✓/✕ en FullCalendar |
| 07.04/RF-008 | Completo | `TurnoNoAsistibleException` |
| 07.04/RF-009 | Completo | `GetIndicadoresAsistencia` |
| 07.04/RF-010 | Parcial | CSV; PDF 503 |
| 07.04/RF-011 | Completo | `RecordAsistenciaAudit` |
| 07.04/RF-012 | Completo | `AsistenciaAcceso` 404 + filtros |
| 07.04/RF-013 | Completo | Efecto saldo/indicadores en GET |
| 07.04/RF-014 | Completo | `GetResumenDiarioAsistencia` |
| 07.04/RF-015 | Completo | `EloquentPatientAttendanceSummary` |

| RN/CU/CA | Estado |
|---|---|
| 07.04/RN-001, 07.04/RN-002, 07.04/RN-003, 07.04/RN-005, 07.04/RN-006, 07.04/RN-007, 07.04/RN-008, 07.04/RN-009 | Completos |
| 07.04/RN-004 | Completo |
| 07.04/RN-010 | Completo |
| 07.04/CU-01, 07.04/CU-02, 07.04/CU-03 | Completos |
| 07.04/CA-001 a 07.04/CA-010 | Completos; 07.04/CA-002/003 visibles vía 06.02 |

**Pendientes:** PDF de 07.04/RF-010 (503), exportación por paciente (privacidad; requiere decisión), “pasar lista” masivo en sala grupal (no implementado), fórmula/período de indicadores marcados PROPUESTO - VALIDAR, y decisión sobre cancelaciones 07.03 con `consume_saldo` en el saldo de 06.02.

---

## Módulo 07 — Agenda y turnos (submódulo 05: diagnóstico con varios profesionales)

Implementación de `docs/07_AGENDA_TURNOS/05_diagnostico_varios_profesionales.md`. El documento numera **07.05/RF-001 a 07.05/RF-015** (15 RF, aunque el pedido inicial menciona 14) y **07.05/RN-001 a 07.05/RN-010**. Reutiliza 05.03, 06.03, 07.1, 07.2, 07.3 y 07.4. No crea diagnósticos, no asigna profesionales, no registra asistencia ni reprograma: esas responsabilidades siguen en sus módulos.

### Estructura

```text
app/Modules/AgendaTurnos/
├── Actions/Diagnosticos/        ProgramarTurnoDiagnostico,
│                                ActualizarEstadoGeneralDiagnostico, CancelarDiagnostico
├── Contracts/                   EloquentProfessionalSchedule,
│                                DiagnosticosPdfExporterContract,
│                                UnavailableDiagnosticosPdfExporter
├── Enums/                       EstadoGeneralDiagnostico
├── Events/                      TurnoDiagnosticoProgramado,
│                                EstadoGeneralDiagnosticoCambiado, DiagnosticoCancelado
├── Exceptions/                  DiagnosticoNoEncontrado/NoCoordinable/FueraDePeriodo,
│                                ProfesionalNoAsignado/NoCubreRequisito,
│                                TurnoDiagnosticoDuplicado, EstadoGeneralInvalido,
│                                CierreDiagnosticoIncompleto, DisponibilidadDiagnostico,
│                                ConfiguracionDiagnosticoNoDisponible,
│                                WhatsappDiagnosticoNoDisponible,
│                                ExportacionDiagnosticosNoDisponible
├── Http/Controllers/Diagnosticos/ DiagnosticoCoordinacionController
├── Http/Requests/Diagnosticos/  Index, Exportar, Opciones, Programar,
│                                ActualizarEstadoGeneral, Cancelar
├── Listeners/                   RecordDiagnosticoAudit
├── Providers/                   DiagnosticosServiceProvider
├── Queries/Diagnosticos/        ListDiagnosticosCoordinacion, GetCoordinacionDiagnostico,
│                                GetOpcionesDiagnosticos, GetWhatsappDiagnostico,
│                                ExportarDiagnosticosCsv
├── Rules/                       EstadoGeneralDiagnosticoRule, DiasHabilitadosRule,
│                                HorarioAgendaRule, IntervaloMinimoRule
├── Support/                     DiagnosticosModule, DiagnosticosPermission,
│                                DiagnosticoClock, DiagnosticoParametros,
│                                DiagnosticoTurnosResolver,
│                                DiagnosticoEstadoSincronizador,
│                                EstadoGeneralResultado, WhatsappDiagnosticoLink,
│                                DiagnosticoAuditAction
└── routes-diagnosticos.php
app/Modules/InscripcionesAtenciones/Contracts/
├── DiagnosticoAtencionContract.php
├── DiagnosticoAtencionResumen.php
├── DiagnosticoAtencionPagina.php
└── EloquentDiagnosticoAtencion.php
```

### Schema

- `2026_11_30_000100_add_diagnosticos_indexes.php` agrega `turno(atencion_diagnostico_id, fecha, hora_inicio)` y `atencion_diagnostico(sucursal_id, estado_general, fecha_inicio)`.
- No se crean tablas ni columnas: `atencion_diagnostico.estado_general` ya existía con `CHECK IN ('incompleto','en_proceso','completo','cancelado')`.
- `turno_profesional` **no se usa**: un turno por profesional con `turno.profesional_id`, igual que 07.2/07.3/07.4.
- Seeder `DiagnosticoMotivoTurnoSeeder`: agrega el motivo `cancelacion_diagnostico` (no pisa configuraciones existentes).
- `atencion_diagnostico.estado` se conserva `activa` al cancelar la coordinación; `retirada` sigue siendo la baja de 06.03.

### Estado general

`estado_general` se persiste y se recalcula en la misma transacción del cambio del turno. La regla pura `EstadoGeneralDiagnosticoRule` recibe requisitos, turnos y especialidades por profesional:

| Estado | Regla |
|---|---|
| `cancelado` | Solo cancelación manual con motivo; terminal. |
| `completo` | Todos los puestos requeridos tienen al menos un turno `asistio`. |
| `en_proceso` | Todos los puestos tienen turno `programado` o `asistio`, pero no todos asistieron. |
| `incompleto` | Al menos un puesto no tiene turno vigente. `falto`, `cancelado` y `reprogramado` dejan el puesto descubierto. |

- Un puesto se cubre con profesionales distintos que tengan la especialidad requerida; `cantidad_minima` se respeta por especialidad.
- RF-011 se expone como `faltantes` (`sin_turno`/`sin_asistencia`); el estado general no se cambia solo por una advertencia.
- Un turno `falto` se muestra con su estado en el puesto y habilita programar un reemplazo, pero no cubre el puesto: el estado general sigue incompleto hasta que exista un turno vigente.
- `DiagnosticoEstadoSincronizador` se invoca desde `ProgramarTurnoDiagnostico`, `ReprogramarTurno` (07.3), `CancelarTurno` (07.3), `CancelarTurnosMasivo` (07.3), `RegistrarAsistencia` (07.4) y `CorregirAsistencia` (07.4).
- El cierre manual a `completo` exige todas las asistencias (RF-012). La actualización manual solo acepta el estado calculado; si no coincide, responde 409 con `faltantes`.

### Programación y disponibilidad

- Solo se programa a profesionales ya asociados al diagnóstico por 06.03. 07.05 no crea la asociación (RF-001 se cubre en 06.03).
- El profesional debe estar activo y asignado a la sucursal (`ProgramacionGuard`), cubrir alguna especialidad requerida y no tener otro turno vigente en el mismo diagnóstico.
- El turno debe caer dentro de `fecha_inicio..fecha_fin` del diagnóstico.
- Días habilitados: `agenda.dias_semana_habilitados` (ISO 1–7). Horario: `agenda.horario_apertura`/`agenda.horario_cierre`. Intervalo: `agenda.intervalo_minimo_entre_turnos_minutos`.
- Solapamiento global por profesional: `ProfessionalScheduleContract::haySolapamiento` (incluye otras sucursales; el mensaje es genérico). Solapamiento de paciente: `TurnoSolapamientoRule`.
- Duración (RN-010): `ServicioResumen.duracionMinutos` si es > 0; si no, `duracion_sesion_minutos`. El request admite `duracion_minutos` opcional por turno (RF-015 parcial). No se hardcodea 50.
- Fechas y horas se guardan en la zona local de la agenda (`zona_horaria` para el cálculo y la agenda); el contrato de 03 recibe UTC y lo convierte.
- No se agrega `version` a `atencion_diagnostico`: la concurrencia de programar/actualizar/cancelar se resuelve con `lockForUpdate` sobre la fila del diagnóstico. La reprogramación y la asistencia siguen usando `turno.version`/`estado_esperado` de 07.3/07.4.
- La reprogramación de un turno de diagnóstico (07.3) conserva el profesional y fuerza `sala_id=NULL`; el CHECK de origen único no admite sala y diagnóstico en el mismo turno. El modal de 07.3 refleja esa restricción.

### Endpoints

Prefijo **`/api/agenda-turnos/diagnosticos`**:

| Método | Ruta | Action/Query | Permiso |
|---|---|---|---|
| GET | `/` | `ListDiagnosticosCoordinacion` | `asignacion.ver` |
| GET | `/opciones` | `GetOpcionesDiagnosticos` | `asignacion.ver` |
| GET | `/exportar` | `ExportarDiagnosticosCsv` / PDF 503 | `asignacion.exportar` |
| GET | `/{id}` | `GetCoordinacionDiagnostico` (audita) | `asignacion.ver` |
| POST | `/{id}/turnos` | `ProgramarTurnoDiagnostico` | `asignacion.crear` |
| PATCH | `/{id}/estado-general` | `ActualizarEstadoGeneralDiagnostico` | `diagnostico_agenda.actualizar_estado` |
| POST | `/{id}/cancelar` | `CancelarDiagnostico` | `diagnostico_agenda.cancelar` |
| GET | `/{id}/whatsapp` | `GetWhatsappDiagnostico` | `tutor.ver_contacto` |

Acciones reutilizadas por la UI (no reimplementadas): reprogramación de 07.3 y registro/corrección de asistencia de 07.4. No existe `DELETE`.

### Permisos

Seeder `DiagnosticosPermissionSeeder` (marcador `agenda.diagnosticos`). Solo crea los códigos nuevos; reutiliza los de 03.

| Permiso | Superadmin | Administración | Recepción |
|---|---:|---:|---:|
| `asignacion.ver` | sí | sí | sí |
| `asignacion.crear` | sí | sí | sí |
| `asignacion.exportar` | sí | sí | no |
| `diagnostico.asignar_profesionales` (06.03) | sí | sí | sí |
| `diagnostico_agenda.cancelar` | sí | sí | no |
| `diagnostico_agenda.actualizar_estado` | sí | sí | no |
| `tutor.ver_contacto` | sí | sí | sí |
| `turno.reprogramar`, `asistencia.registrar` (07.3/07.4) | sí | sí | según matriz vigente |

El backend revalida permiso, sucursal y estado. Un diagnóstico de otra sucursal responde 404 en el detalle (CA-009); el listado con `sucursal_id` ajeno responde 403.

> **02 no se modifica:** los códigos `diagnostico_agenda.*` se muestran con la etiqueta de fallback de `PermissionCatalog`; `AuditModuleResolver` sigue resolviendo `atencion_diagnostico` como `inscripciones` para la auditoría de consulta. La bitácora del listener usa `modulo='agenda'` explícitamente.

### Contratos

- **`DiagnosticoAtencionContract` (06.03):** `buscar`, `resumen`, `resumenParaActualizar`, `actualizarEstadoGeneral`, `tiposDiagnostico`. Implementación `EloquentDiagnosticoAtencion`; bind en `InscripcionesAtencionesServiceProvider`.
- **`ProfessionalScheduleContract` (03):** implementación real `EloquentProfessionalSchedule` en Agenda; bind en `ProfesionalesServiceProvider`. Reemplaza el stub; el test de stubs de 03 ahora verifica la implementación real.
- **`PatientDirectoryContract` / `ProfessionalDirectoryContract` / `BranchDirectoryContract` / `ParameterReaderContract` / `AuditRecorderContract` / `TutorDirectoryContract`:** se consumen sin cambios.
- **`DiagnosticosPdfExporterContract`:** `UnavailableDiagnosticosPdfExporter` responde 503. No hay botón de PDF.
- `ProfessionalDiagnosticRequirementsContract` ya estaba implementado por `EloquentProfessionalDiagnosticRequirements`; 07.05 no lo necesita porque usa el snapshot `atencion_diagnostico_requisito` de 06.03.

### Bitácora y auditoría

- `RecordDiagnosticoAudit` es síncrono y fail-closed:
  - `turno` / `PROGRAMAR_DIAGNOSTICO` (no crítico);
  - `atencion_diagnostico` / `ACTUALIZAR_ESTADO_GENERAL` (crítico);
  - `atencion_diagnostico` / `CANCELAR` (crítico).
- La reprogramación y la asistencia siguen escribiendo su bitácora en 07.3/07.4.
- El detalle de coordinación se audita con `AuditRecorderContract::registrarConsultaSensible(entidad='atencion_diagnostico', contexto=['recurso'=>'diagnostico_coordinacion'])`; si falla, responde 503 `AUDIT_UNAVAILABLE`. El listado **no** se audita.
- `AuditModuleResolver` resuelve `atencion_diagnostico` al módulo `inscripciones`; la fila de bitácora del listener usa `modulo='agenda'`. Es el fallback vigente; no se modifica 02.
- Catálogo `accion_auditada`: `DiagnosticosAccionAuditadaSeeder`.

### WhatsApp

- `WhatsappDiagnosticoLink` construye `wa.me` con el tutor principal (`TutorDirectoryContract`), `TutorPhoneRule` y `whatsapp.prefijo_pais` (si está vacío o no existe, usa `591`).
- El mensaje lista fechas, horas y profesionales; no incluye tipo de diagnóstico ni datos clínicos. No envía nada.
- Sin `tutor.ver_contacto` no se devuelve teléfono ni URL. No se escribe el teléfono en bitácora ni logs.

### Cancelación

- `CancelarDiagnostico` exige motivo (3–500 caracteres), bloquea la fila del diagnóstico y cancela solo turnos futuros `programado` reutilizando `CancelarTurno` de 07.3.
- Los turnos pasados (`asistio`/`falto`), cancelados o reprogramados se conservan.
- `estado_general=cancelado`; `atencion_diagnostico.estado` sigue `activa`.
- Motivo de catálogo: `cancelacion_diagnostico` (`requiere_texto=false`, `aplica_cancelar=true`).

### Reporte y exportación

- Filtros: sucursal (alcance), período, tipo, estado general y profesional. `per_page` máximo 50.
- CSV compatible con Excel: BOM UTF-8, delimitador `;`, escape de fórmulas `=+-@`, una fila por profesional. Límite `MAX_FILAS_EXPORTACION = 5000` (422 si lo supera).
- La exportación se audita con `registrarExportacion` (filtros seguros y cantidad; no datos de pacientes).
- PDF: contrato `Unavailable` → 503; sin botón.

### Frontend

- Rutas: `/agenda-turnos/diagnosticos` (listado/reporte) y `/agenda-turnos/diagnosticos/:id` (coordinación), con `permissionGuard` `asignacion.ver` y carga diferida.
- Sidebar: “Diagnósticos” después de “Agenda”.
- Botón “Coordinar” en la fila de diagnóstico de 06.03.
- Listado: filtros, tabla, paginación y exportación CSV. Estado general con `app-badge`; advertencia de puesto faltante.
- Coordinación: puestos por especialidad, profesionales asignados, turnos, semáforo, advertencias, programar turno, reprogramar (modal 07.3), asistencia (componente 07.4), WhatsApp, actualización manual y cancelación.
- Se reutilizan `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-modal`, `app-pagination`, `TABLE_IMPORTS`, `app-select-field`, `input[app-input]`, `textarea[app-textarea]`, `InscripcionDateInputComponent` (Flatpickr), `ReprogramarTurnoModalComponent`, `AsistenciaTurnoAccionesComponent` y `ToastService`.
- Errores: 422 por campo, 409 de regla/estado con mensaje, 403/404/503 con `app-alert`; 503 `AUDIT_UNAVAILABLE` no muestra el detalle. Sin botón de PDF.

### Tests

- Unit: `EstadoGeneralDiagnosticoRuleTest`, `HorarioAgendaRuleTest`, `DiasHabilitadosRuleTest`, `IntervaloMinimoRuleTest`.
- Feature: `DiagnosticoProgramacionTest`, `DiagnosticoCoordinacionTest`, `DiagnosticoCancelacionExportTest`, `DiagnosticoReprogramacionTest` (CA-001 a CA-010, permisos, alcance, auditoría fail-closed, CSV, PDF 503, WhatsApp, reprogramación 07.3).
- SQLite y `phpunit.pgsql.xml` (locks, joins y `FOR UPDATE`).
- `npm run build` y `npx tsc --noEmit`.

### Checklist 07.05

| RF | Estado | Evidencia |
|---|---|---|
| 07.05/RF-001 | Cubierto por 06.03; 07.05 lo consume | `CreateDiagnosticoAction`/`UpdateDiagnosticoAction`; la coordinación valida profesionales asociados |
| 07.05/RF-002 | Completo | `ProgramarTurnoDiagnostico` + modal |
| 07.05/RF-003 | Completo | `ProfessionalScheduleContract` + `HorarioAgendaRule`, `DiasHabilitadosRule`, `IntervaloMinimoRule` |
| 07.05/RF-004 | Completo (reutiliza 07.4) | Estado por turno y panel de asistencia |
| 07.05/RF-005 | Completo | `EstadoGeneralDiagnosticoRule` + sincronizador |
| 07.05/RF-006 | Completo | `GetCoordinacionDiagnostico` + pantalla |
| 07.05/RF-007 | Completo | `ActualizarEstadoGeneralDiagnostico`, `CancelarDiagnostico` |
| 07.05/RF-008 | Completo CSV; PDF 503 | `ExportarDiagnosticosCsv`, `UnavailableDiagnosticosPdfExporter` |
| 07.05/RF-009 | Completo | `RecordDiagnosticoAudit` + bitácora 07.3/07.4 |
| 07.05/RF-010 | Completo | `AgendaAccessScope`; detalle 404, listado 403 |
| 07.05/RF-011 | Completo | `faltantes` de la regla + alertas |
| 07.05/RF-012 | Completo | `CierreDiagnosticoIncompletoException` |
| 07.05/RF-013 | Completo (reutiliza 07.3) | `ReprogramarTurnoModalComponent` |
| 07.05/RF-014 | Completo | `WhatsappDiagnosticoLink` + botón |
| 07.05/RF-015 | Parcial | Duración por turno (`duracion_minutos`) y default servicio > parámetro; no hay configuración por profesional |

| RN | Estado |
|---|---|
| 07.05/RN-001, RN-002 | Completos: requisitos y advertencias |
| 07.05/RN-003 | Completo: disponibilidad global + agenda |
| 07.05/RN-004, RN-005 | Completos: regla y cierre |
| 07.05/RN-006 | Completo: reprogramación 07.3 |
| 07.05/RN-007 | Completo: bitácora con usuario/fecha/motivo |
| 07.05/RN-008 | Completo: alcance por sucursal |
| 07.05/RN-009 | Completo: asignación manual; 07.05 no asigna |
| 07.05/RN-010 | Completo: servicio > parámetro; RF-015 parcial |

**Pendientes:** PDF de RF-008 (503), límite de exportación como parámetro configurable (hoy constante 5000), duración distinta por profesional (RF-015) y cancelación automática de turnos al retirar un diagnóstico desde 06.03 (`BajaAtencionAction` no toca turnos; queda como TODO). `RT-004` (unicidad de diagnóstico por tipo/período) no tiene RF y no se implementó.

---

## Notas

- No se agregaron librerías de PDF/Excel. Los únicos cambios en módulos existentes son métodos batch de lectura acordados en `PatientDirectoryContract`, `ProfessionalDirectoryContract`, `SalaDirectoryContract` y `BranchDirectoryContract`, con tests propios; no se alteró su comportamiento previo.
- El login real está implementado en la Fase 1 del módulo 02 y el alcance real por sucursal está activo desde la Fase 2. La UI obtiene token mediante el interceptor y las páginas de 01/02/03 ya respetan las sucursales asignadas.
- `database/database.md` y `database/talitacumi_schema.sql` se actualizaron con la implementación real: `users` es la tabla física, Spatie administra roles/permisos y Sanctum usa `personal_access_tokens`. Las definiciones `usuario`/`rol`/`permiso`/`sesion` del SQL quedan como referencia aspiracional.
- Si se reconstruye la imagen con `--no-dev`, volver a ejecutar `composer install` dentro del contenedor para correr tests.
