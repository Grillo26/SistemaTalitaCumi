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
├── Http/
│   ├── Controllers/  Delgados: Request -> Action | Query -> Resource.
│   ├── Requests/     Validación de formato y autorización por campo.
│   └── Resources/    JsonResource de salida.
├── Providers/      Bindings, listeners y carga de rutas.
└── routes.php
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
- **Spatie Permission** es el mecanismo de permisos. Los roles base (`superadmin`, `administracion`, `recepcion`) se crean en `DatabaseSeeder`; los permisos del módulo se crean en `OrganizacionSucursalesPermissionSeeder`.
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

## Notas

- No se agregaron librerías de PDF/Excel ni se modificó ningún otro módulo de negocio.
- El login y la autenticación real pertenecen al módulo 02; hasta entonces la UI de sucursales mostrará `401` si intenta consumir la API sin token.
- Si se reconstruye la imagen con `--no-dev`, volver a ejecutar `composer install` dentro del contenedor para correr tests.
