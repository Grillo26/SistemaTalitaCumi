# TalitaCumi — Frontend

Frontend del **Sistema de Gestión TalitaCumi**, un sistema de turnos, inscripciones y cobros para un centro de desarrollo infantil. Está construido con Angular standalone y Tailwind CSS, tomando como base visual y técnica el template TailAdmin Angular.

> Esta entrega incluye la base del sistema, autenticación real (Fase 1 del módulo 02) y las páginas de referencia de componentes. Los módulos del negocio están creados como rutas vacías listas para implementarse.

---

## Stack

| Tecnología | Versión |
|---|---|
| Angular | 22.3 |
| TypeScript | 6.0 |
| Tailwind CSS | 4.x |
| Spartan NG Brain | 1.6.3 |
| FullCalendar | 7.0.2 |
| ApexCharts / ng-apexcharts | 7.x / 3.x |
| Flatpickr | 4.6 |
| Zone.js | 0.15 |

---

## Requisitos

- Node.js 22.x
- npm 10.x

El proyecto fue verificado con `npm install` y `npm run build`. No se levantó el servidor de desarrollo.

---

## Instalación y ejecución

```bash
cd frontend
npm install
npm run build
npm start
```

`npm start` ejecuta `ng serve`. Por defecto usa el puerto `4200`; si necesitas otro puerto:

```bash
npm start -- --port 4300
```

---

## Acceso y autenticación

La aplicación abre en el login si no hay una sesión activa.

- Ruta: `/login`
- El acceso es exclusivo para usuarios creados por el backend.
- El primer Superadmin se crea con `php artisan seguridad:crear-superadmin` (ver README del backend). No hay contraseñas de demostración.
- “Recordar mi correo” guarda **solo el identificador** en `localStorage` para prellenar el formulario. El token se guarda en `sessionStorage` y nunca en `localStorage`.
- Si el usuario tiene una contraseña temporal (`debe_cambiar_password`), el sistema lo redirige a `/cambiar-password` y bloquea el resto de las pantallas hasta que la cambie.
- El cierre de sesión está en el menú de usuario del header y también en el mismo menú está “Cambiar contraseña”.

> La autenticación es real contra `/api/auth/*`. Los servicios de 01/02/03 consumen la API con el token mediante un interceptor HTTP.

---

## Rutas

### Autenticación

| Ruta | Descripción |
|---|---|
| `/login` | Inicio de sesión real |
| `/cambiar-password` | Cambio de contraseña propio o forzado |
| `/` | Inicio (protegido) |

### Módulos del sistema

Todas las rutas están dentro del layout principal y protegidas por `authGuard`.

| Ruta | Módulo |
|---|---|
| `/` | Inicio |
| `/agenda` | Agenda |
| `/pacientes` | Pacientes |
| `/inscripciones` | Inscripciones |
| `/pagos` | Pagos y caja |
| `/reportes` | Reportes |
| `/catalogo` | Catálogo |
| `/profesionales` | Profesionales |
| `/inventario` | Inventario |
| `/configuracion` | Redirige a `/organizacion/configuracion` |
| `/organizacion/sucursales` | Organización y sucursales |
| `/organizacion/salas-ambientes` | Salas y ambientes |
| `/organizacion/configuracion` | Configuración general y parámetros |
| `/seguridad/usuarios` | Usuarios, roles asignados y alcance por sucursal |
| `/seguridad/roles-permisos` | Matriz de roles y permisos |
| `/seguridad/bitacora` | Consulta y reconstrucción de bitácora |

Los módulos sin implementar muestran un estado vacío reutilizable (`app-module-placeholder`). Las rutas `/organizacion/sucursales`, `/organizacion/salas-ambientes`, `/organizacion/configuracion`, `/seguridad/usuarios`, `/seguridad/roles-permisos` y `/seguridad/bitacora` ya están implementadas y consumen la API real del backend.

### Páginas de referencia

No aparecen en el menú principal. Se acceden por URL y sirven como material de apoyo:

| Ruta | Página |
|---|---|
| `/shared` | Índice de referencia UI |
| `/shared/form-elements` | Formularios |
| `/shared/basic-tables` | Tabla básica 2 |
| `/shared/data-tables` | Data Tables |
| `/shared/calendar` | Calendario |
| `/shared/line-chart` | Gráfico de líneas |
| `/shared/bar-chart` | Gráfico de barras |
| `/shared/pie-chart` | Gráfico circular |
| `/shared/buttons` | Botones |
| `/shared/badge` | Badges |
| `/shared/alerts` | Alertas y toasts |
| `/shared/invoice` | Comprobante de pago |
| `/shared/blank` | Página en blanco |

### Errores

| Ruta | Página |
|---|---|
| `/error-404` | Página no encontrada |
| `/error-500` | Error interno |
| `/error-503` | Servicio no disponible |
| `/mantenimiento` | Mantenimiento |
| `/exito` | Operación exitosa |
| `/**` | Redirige visualmente al 404 |

---

## Estructura de carpetas

```text
frontend/
├── public/
│   ├── favicon.ico
│   ├── favicon.png
│   └── images/
│       ├── error/
│       ├── shape/
│       └── user/
├── src/
│   ├── app/
│   │   ├── core/
│   │   │   ├── guards/
│   │   │   │   ├── auth.guard.ts
│   │   │   │   └── guest.guard.ts
│   │   │   └── services/
│   │   │       ├── auth.service.ts
│   │   │       ├── dropdown.service.ts
│   │   │       ├── live-directionality.service.ts
│   │   │       ├── sidebar.service.ts
│   │   │       ├── theme.service.ts
│   │   │       └── toast.service.ts
│   │   ├── layout/
│   │   │   ├── header/
│   │   │   │   ├── notification-dropdown/
│   │   │   │   └── user-dropdown/
│   │   │   └── shell/
│   │   │       ├── app-header/
│   │   │       ├── app-layout/
│   │   │       ├── app-sidebar/
│   │   │       ├── auth-page-layout/
│   │   │       └── backdrop/
│   │   ├── pages/
│   │   │   ├── agenda/
│   │   │   ├── auth-pages/sign-in/
│   │   │   ├── blank/
│   │   │   ├── calendar/
│   │   │   ├── catalogo/
│   │   │   ├── charts/
│   │   │   ├── configuracion/
│   │   │   ├── ecommerce/
│   │   │   ├── forms/
│   │   │   ├── home/
│   │   │   ├── inscripciones/
│   │   │   ├── inventario/
│   │   │   ├── other-page/
│   │   │   ├── organizacion/
│   │   │   │   ├── configuracion/
│   │   │   │   ├── salas-ambientes/
│   │   │   │   └── sucursales/
│   │   │   ├── pacientes/
│   │   │   ├── pagos/
│   │   │   ├── profesionales/
│   │   │   ├── reference/
│   │   │   ├── reportes/
│   │   │   ├── tables/
│   │   │   └── ui-elements/
│   │   ├── shared/
│   │   │   ├── components/
│   │   │   │   ├── common/
│   │   │   │   ├── form/
│   │   │   │   ├── full-width-pages/
│   │   │   │   └── ui/
│   │   │   ├── directive/
│   │   │   └── pipe/
│   │   ├── app.component.*
│   │   ├── app.config.ts
│   │   └── app.routes.ts
│   ├── environments/
│   ├── index.html
│   ├── main.ts
│   └── styles.css
├── .postcssrc.json
├── angular.json
├── package.json
├── tsconfig.json
└── README.md
```

---

## Componentes reutilizables

### 1. Layout principal

**Selectores:** `app-layout`, `app-sidebar`, `app-header`, `app-backdrop`

- `app-layout`: contenedor del área autenticada. Incluye sidebar, header y `router-outlet`.
- `app-sidebar`: menú lateral colapsable y responsive. Se expande al hacer hover cuando está colapsado y se abre como panel en mobile.
- `app-header`: header sticky con toggle del menú, búsqueda, notificaciones, modo oscuro y menú de usuario.
- `app-backdrop`: fondo oscuro para cerrar el menú en mobile.

```html
<app-layout />
```

> Las rutas hijas se renderizan dentro del layout; no se usa el componente directamente en páginas.

---

### 2. BrandLogo

**Selector:** `app-brand-logo`
**Archivo:** `src/app/shared/components/common/brand-logo/brand-logo.component.ts`

Muestra el logo oficial de TalitaCumi (`public/images/logos/`) dentro de una tarjeta blanca, para que se vea bien en modo claro, oscuro y en el panel morado del login.

**Inputs:**

| Input | Tipo | Default | Descripción |
|---|---|---|---|
| `compact` | `boolean` | `false` | Muestra solo el isotipo (sidebar colapsado) |
| `size` | `'sm' \| 'md' \| 'lg'` | `'md'` | Altura del logo: 48, 72 o 104 px (isotipo: 32, 40 o 52 px) |

```html
<app-brand-logo size="lg" />
<app-brand-logo [compact]="true" />
```

Archivos de imagen:

| Archivo | Uso |
|---|---|
| `logo-talitacumi.png` | Original (no se usa en la web) |
| `logo-talitacumi-web.png` | Logo completo para la web (800 px) |
| `logo-talitaCumi-isotipo.png` | Isotipo original |
| `logo-talitacumi-isotipo-web.png` | Isotipo para la web (256 px) |
| `public/favicon.png` y `favicon.ico` | Favicon generado a partir del isotipo |

Para cambiar el logo, reemplaza los archivos `-web.png` manteniendo el mismo nombre (o regenera los derivados desde los originales).

---

### 3. Tema oscuro

**Servicio:** `src/app/core/services/theme.service.ts`
**Selectores:** `app-theme-toggle-button`, `app-theme-toggle-two`

`ThemeService` guarda la preferencia en `localStorage`, aplica la clase `dark` al documento y expone `isDarkMode$`.

```ts
import { ThemeService } from '../../core/services/theme.service';

private readonly theme = inject(ThemeService);
this.theme.toggleTheme();
```

```html
<app-theme-toggle-button />
```

La página de login usa `app-theme-toggle-two` (botón circular sobre el contenido).

---

### 4. Autenticación real

**Archivos:**

- `src/app/core/models/auth.models.ts`
- `src/app/core/services/session-store.service.ts`
- `src/app/core/services/auth.service.ts`
- `src/app/core/interceptors/auth.interceptor.ts`
- `src/app/core/guards/auth.guard.ts`
- `src/app/core/guards/guest.guard.ts`
- `src/app/core/guards/permission.guard.ts`
- `src/app/pages/seguridad/cambiar-password/`
- `src/app/pages/other-page/forbidden/`

**API de `AuthService`:**

```ts
readonly user = this.auth.user;                          // Signal<SessionUser | null>
readonly isAuthenticated = this.auth.isAuthenticated;
readonly mustChangePassword = this.auth.mustChangePassword;
readonly permissions = this.auth.permissions;

await this.auth.initialize();                            // rehidrata con GET /api/auth/me
await this.auth.login(identificador, password, remember);
await this.auth.logout();
await this.auth.changePassword(actual, nueva, confirmacion);
this.auth.hasPermission('usuario.ver');
```

- `SessionStore` guarda el token en `sessionStorage`; “Recordar mi correo” solo persiste el identificador en `localStorage`.
- El login acepta nombre de usuario o correo. El correo es opcional en el alta y no se usa para envíos; el identificador principal es `username`.
- `auth.initialize()` se ejecuta con `provideAppInitializer` antes de la primera navegación.
- El interceptor adjunta `Authorization: Bearer`, limpia la sesión y redirige a `/login?expirada=1` ante un 401, y redirige a `/cambiar-password` ante `PASSWORD_CHANGE_REQUIRED`.
- `authGuard` protege el layout y fuerza el cambio de contraseña; `guestGuard` evita volver al login con sesión activa; `permissionGuard` usa `route.data.permission` y redirige a `/error-403`.
- La página `/cambiar-password` muestra la política vigente que llega en `me`, valida 422 por campo y conserva lo digitado si falla la red.

---

### 5. ToastService y Toast

**Selector:** `app-toast`
**Archivo:** `src/app/shared/components/ui/toast/toast.component.ts`
**Servicio:** `src/app/core/services/toast.service.ts`

**API del servicio:**

```ts
this.toast.success('Guardado', 'Los cambios se guardaron.');
this.toast.info('Información', 'Operación completada.');
this.toast.warning('Atención', 'Revisa los datos.');
this.toast.error('Error', 'No se pudo completar la operación.');
```

**Inputs del componente:** `variant`, `title`, `message`, `show`, `closing`
**Output:** `close`

Los toasts se crean programáticamente y se apilan en la esquina inferior derecha.

---

### 6. Paginación

**Selector:** `app-pagination`
**Archivo:** `src/app/shared/components/ui/pagination/pagination.component.ts`

**Inputs:** `currentPage = 1`, `totalPages = 10`, `variant: 'default' | 'icon' | 'text' | 'text-icon'`
**Output:** `pageChange: number`

```html
<app-pagination
  [currentPage]="page"
  [totalPages]="10"
  variant="text-icon"
  (pageChange)="goToPage($event)"
/>
```

---

### 7. Directivas de tabla

**Archivo:** `src/app/shared/components/ui/table/table.directive.ts`

**Selectores:** `table[app-table]`, `thead[app-table-header]`, `tbody[app-table-body]`, `tr[app-table-row]`, `th[app-table-cell]`, `td[app-table-cell]`

```html
<table app-table>
  <thead app-table-header>
    <tr app-table-row>
      <th app-table-cell>Nombre</th>
    </tr>
  </thead>
  <tbody app-table-body>
    <tr app-table-row>
      <td app-table-cell>María López</td>
    </tr>
  </tbody>
</table>
```

También se exporta `TABLE_IMPORTS` para importar todas las directivas juntas.

---

### 7.1 Modal compartido

**Selector:** `app-modal`
**Archivo:** `src/app/shared/components/ui/modal/modal.component.ts`

Modal centrado con altura máxima del 90% del viewport, scroll interno y foco atrapado. Reemplaza los overlays Tailwind que estaban repetidos en las páginas de negocio.

**Inputs:**

| Input | Tipo | Default | Descripción |
|---|---|---|---|
| `open` | `boolean` | `true` | Controla la visibilidad. También se puede usar `@if` desde la página |
| `title` | `string` | `''` | Título del header estándar |
| `subtitle` | `string` | `''` | Subtítulo del header estándar |
| `size` | `'sm' \| 'md' \| 'lg' \| 'xl'` | `'md'` | Ancho máximo del panel |
| `customHeader` | `boolean` | `false` | Usa el header propio proyectado con `modal-header` |
| `showClose` | `boolean` | `true` | Muestra el botón de cierre del header estándar |
| `showFooter` | `boolean` | `false` | Muestra el wrapper de footer estándar |
| `padded` | `boolean` | `true` | Agrega padding al cuerpo; desactivarlo para contenido que ya trae `p-*` |

**Output:** `closed`

```html
<app-modal
  [open]="open()"
  title="Nueva sucursal"
  subtitle="Los campos marcados con * son obligatorios."
  size="lg"
  (closed)="close()"
>
  <div modal-body>...</div>
  <div modal-footer class="flex justify-end gap-2">...</div>
</app-modal>
```

Para páginas con header propio:

```html
<app-modal [customHeader]="true" [showClose]="false" size="lg" (closed)="close()">
  <div modal-header>...</div>
  <div>...</div>
</app-modal>
```

El componente bloquea el scroll del body, cierra con Escape/backdrop y usa `cdkTrapFocus` para devolver el foco al cerrar.

---

### 8. DataTableOne — ordenamiento y paginación

**Selector:** `app-data-table-one`
**Archivo:** `src/app/pages/tables/components/data-tables/data-table-one/data-table-one.component.ts`

Tabla de pacientes de ejemplo con:

- Ordenamiento ascendente/descendente por columnas (nombre, sala, sucursal, edad, ingreso y cuota).
- Paginación funcional de 5 registros por página.
- Selección por fila y selección de la página actual.
- Formato de moneda en Bs y fechas en español.

```html
<app-data-table-one />
```

---

### 9. TableTwo — búsqueda y filtro

**Selector:** `app-table-two`
**Archivo:** `src/app/pages/tables/components/basic-tables/table-two/table-two.component.ts`

Tabla con búsqueda por texto, filtro por estado y selección de filas.

```html
<app-table-two />
```

---

### 10. Formularios

**Input de texto:** `input[app-input]`

```html
<input app-input type="text" placeholder="Nombre del paciente" />
<input app-input type="text" [error]="true" />
```

**Select:** `app-select-field`

```ts
options = [
  { value: 'sala-cuna', label: 'Sala cuna' },
  { value: 'inicial', label: 'Inicial' },
];
```

```html
<app-select-field
  [options]="options"
  placeholder="Selecciona una sala"
  (valueChange)="room = $event"
/>
```

**Textarea:** `textarea[app-textarea]`

```html
<textarea app-textarea rows="4" placeholder="Observaciones"></textarea>
```

**Switch:** `app-switch`
**Inputs:** `checked`, `disabled`, `size`, `ariaLabel`
**Output:** `checkedChange`

```html
<app-switch [checked]="active" (checkedChange)="active = $event" />
```

**Radio:** `app-radio`
Debe ir dentro de un grupo `brnRadioGroup` de Spartan.

**Fecha y hora:** Flatpickr

Los ejemplos `app-date-picker-input` y `app-time-select-input` muestran el uso de Flatpickr con formato de fecha y hora.

**Validación visual:** input y textarea aceptan `[error]="true"`; además, el login usa validadores reactivos y mensajes de error.

---

### 11. Botones

**Selector:** `app-button`, `button[app-button]`, `a[app-button]`
**Archivo:** `src/app/shared/components/ui/button/button.component.ts`

**Inputs:** `variant`, `size`, `type`, `disabled`, `ariaLabel`, `startIcon`, `endIcon`, `className`
**Output:** `btnClick`

**Variantes:** `default`, `outline`, `ghost`, `destructive`, `link`, `unstyled`

```html
<button app-button type="button">Guardar</button>
<button app-button variant="outline" size="sm">Cancelar</button>
<button app-button variant="destructive">Eliminar</button>
```

---

### 12. Badges

**Selector:** `app-badge`
**Archivo:** `src/app/shared/components/ui/badge/badge.component.ts`

**Inputs:** `variant`, `size`, `color`, `className`, `startIcon`, `endIcon`

**Colores:** `primary`, `success`, `error`, `warning`, `info`, `gray`, `light`, `dark`
**Variantes:** `light`, `solid`, `ghost`

```html
<app-badge color="success" size="sm">Activo</app-badge>
<app-badge color="warning" variant="light">Pendiente</app-badge>
<app-badge color="error" variant="solid">Anulado</app-badge>
```

---

### 13. Alertas

**Selector:** `app-alert`
**Archivo:** `src/app/shared/components/ui/alert/alert.component.ts`

**Inputs:** `variant`, `title`, `message`, `showLink`, `linkHref`, `linkText`

**Variantes:** `default`, `success`, `warning`, `error`, `destructive`, `info`

```html
<app-alert
  variant="success"
  title="Pago registrado"
  message="El comprobante fue emitido correctamente."
/>
```

---

### 14. Página de error / estado

**Selector:** `app-error-page-layout`
**Archivo:** `src/app/shared/components/common/error-page-layout/error-page-layout.component.ts`

**Inputs:** `title`, `message`, `imageLight`, `imageDark`, `imageAlt`, `imageWrapperClass`, `titleAbove`

```html
<app-error-page-layout
  title="ERROR 500"
  message="Ocurrió un error interno."
  imageLight="/images/error/500.svg"
  imageDark="/images/error/500-dark.svg"
  imageAlt="Error 500"
/>
```

---

### 15. Calendario / agenda

**Selector:** `app-calendar`
**Archivo:** `src/app/pages/calendar/calendar.component.ts`

Base de la agenda con FullCalendar:

- Vistas año, mes, semana y día.
- Creación y edición de turnos con modal.
- Colores por área mediante `extendedProps.calendar`.
- Áreas disponibles: `Primary`, `Secondary`, `Accent`, `Highlight`, `Danger`, `Success`, `Warning`, `Info`.
- Adaptación automática a modo oscuro.

```ts
{
  title: 'Turno de terapia',
  start: '2026-11-19',
  extendedProps: { calendar: 'Primary' },
}
```

La vista semanal (`timeGridWeek`) es la base para la grilla de turnos de la agenda.

---

### 16. Gráficos

**Base:** `ng-apexcharts` + `apexcharts`
**Páginas:** `src/app/pages/charts/`

Se incluyen gráficos de líneas, barras y circular, con soporte de modo oscuro y ejemplos de ingresos.

```ts
import { NgApexchartsModule } from 'ng-apexcharts';

@Component({
  imports: [NgApexchartsModule],
  template: `
    <apx-chart
      [series]="series"
      [chart]="chart"
      [xaxis]="xaxis"
    />
  `,
})
```

Los componentes de ejemplo están en:

- `pages/charts/components/line/`
- `pages/charts/components/bar/`
- `pages/charts/components/pie/`

---

### 17. Comprobante de pago

**Página:** `src/app/pages/ecommerce/invoice/invoice.component.ts`
**Componentes:** `src/app/pages/ecommerce/components/invoice-detail/`

Incluye:

- `app-invoice-sidebar`: búsqueda y listado de comprobantes.
- `app-invoice-main`: comprobante completo.
- `app-invoice-header`, `app-invoice-address`, `app-invoice-table`, `app-invoice-summary`, `app-invoice-actions`.

La acción **Imprimir** usa `window.print()`. Es la base para el comprobante de pago imprimible.

---

### 18. Componentes de apoyo

**`app-page-breadcrumb`**
Input: `pageTitle`. Muestra el título de página y la navegación a Inicio.

**`app-component-card`**
Inputs: `title`, `desc`, `className`. Tarjeta contenedora usada en las páginas de referencia.

**`app-grid-shape`**
Decoración de fondo para login y páginas de error.

**`app-module-placeholder`**
Inputs: `title`, `description`. Estado vacío de los módulos pendientes.

```html
<app-module-placeholder
  title="Pacientes"
  description="Ficha, búsqueda y seguimiento de los niños y niñas del centro."
/>
```

---

### 19. Organización y sucursales

**Ruta:** `/organizacion/sucursales`
**Entrada de sidebar:** `Organización`
**Archivos:**

- `src/app/pages/organizacion/sucursales/sucursales.page.ts`
- `src/app/pages/organizacion/sucursales/sucursales.page.html`
- `src/app/pages/organizacion/sucursales/sucursales.routes.ts`
- `src/app/pages/organizacion/sucursales/sucursales.service.ts`
- `src/app/pages/organizacion/sucursales/sucursales.models.ts`

**API consumida:**

| Método | Endpoint | Acción |
|---|---|---|
| GET | `/api/organizacion/sucursales` | Listado paginado con búsqueda, estado, orden y paginación |
| GET | `/api/organizacion/sucursales/{id}` | Ficha de sucursal |
| POST | `/api/organizacion/sucursales` | Alta |
| PATCH | `/api/organizacion/sucursales/{id}` | Edición de datos/contacto |
| POST | `/api/organizacion/sucursales/{id}/activar` | Activación con motivo |
| POST | `/api/organizacion/sucursales/{id}/desactivar` | Desactivación con motivo |

**Funcionalidad:**

- Listado paginado con búsqueda por código o nombre, filtro por estado, orden por columnas y paginación.
- Ficha con datos, estado, fecha de creación y última modificación.
- Alta y edición en diálogo; el formulario conserva lo digitado si falla la red.
- Activación/desactivación con motivo obligatorio y advertencia de RN-003.
- Sin eliminación física ni exportación PDF/Excel (RF-008 y RF-012 pendientes).
- Errores `422` resaltados por campo, `409` de edición concurrente con opción de recargar, y `403`/`503` con alerta.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch` y `ToastService`. Los diálogos usan el modal compartido centrado.

**Permisos y alcance:** la API devuelve `can` (`create`, `update`, `update_contact`, `change_status`, `delete`) y la UI oculta las acciones no permitidas. Existe `permissionGuard` para rutas y el sidebar filtra por permiso; la seguridad real se valida en Laravel. El alcance por sucursal es real desde la Fase 2 del módulo 02: las listas se filtran por las sucursales asignadas y las operaciones fuera de alcance responden `403`.

**Autenticación:** `AuthService` es real desde la Fase 1 del módulo 02; el token se adjunta con el interceptor y la página redirige al login si la sesión expira. No se usan datos mock.

**Estado:** RF-001 a RF-008, RF-011 y RF-013 implementados o parciales según el backend; RF-009, RF-010, RF-012 y el alcance por usuario quedan pendientes.

---

### 20. Salas y ambientes

**Ruta:** `/organizacion/salas-ambientes`
**Entrada de sidebar:** `Salas y ambientes` (entrada plana, después de Organización)

**Archivos:**

- `src/app/pages/organizacion/salas-ambientes/salas-ambientes.page.ts`
- `src/app/pages/organizacion/salas-ambientes/salas-ambientes.page.html`
- `src/app/pages/organizacion/salas-ambientes/salas-ambientes.routes.ts`
- `src/app/pages/organizacion/salas-ambientes/salas-ambientes.service.ts`
- `src/app/pages/organizacion/salas-ambientes/salas-ambientes.models.ts`

**API consumida:**

| Entidad | Endpoints |
|---|---|
| Áreas | `GET /api/organizacion/salas-ambientes/areas` |
| Tipos de sala | `GET/POST /tipos-sala`, `GET/PATCH /tipos-sala/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` |
| Turnos | `GET/POST /turnos-catalogo`, `GET/PATCH /turnos-catalogo/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` |
| Salas | `GET/POST /salas`, `GET/PATCH /salas/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` |
| Ambientes | `GET/POST /ambientes`, `GET/PATCH /ambientes/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` |

**Funcionalidad:**

- Una sola página con cuatro secciones: **Salas grupales**, **Ambientes**, **Tipos de sala** y **Turnos**.
- Salas: búsqueda, filtros por sucursal (solo alcance global), tipo, turno y estado; paginación; ficha; alta/edición; activar/desactivar con motivo al desactivar.
- Ambientes: búsqueda, filtros por sucursal, uso y estado; paginación; ficha; alta/edición; activar/desactivar.
- Tipos y turnos: listas con búsqueda/estado, alta/edición y cambio de estado.
- Formularios con estado inicial explícito, tolerancia a errores de red y conservación de lo digitado.
- Errores `422` por campo, duplicado de sala/ambiente con conflicto visible, `409` de concurrencia con recarga, y `403`/`503`/`401` con alerta.
- Sin eliminación física y sin exportación PDF/Excel (RF-012 y RF-016 pendientes).

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch` y `ToastService`. Los diálogos usan el modal compartido centrado; las tabs de secciones siguen siendo propias de la página.

**Permisos y alcance:** la API devuelve `can` para catálogos, salas y ambientes; la UI oculta las acciones no permitidas. Existe `permissionGuard` para rutas y el sidebar filtra por permiso. El selector de sucursales reutiliza `SucursalesService` en modo lectura. El alcance por sucursal es real desde la Fase 2 del módulo 02.

**Autenticación:** `AuthService` es real desde la Fase 1 del módulo 02; el token se adjunta con el interceptor y la página redirige al login si la sesión expira. No se usan datos mock.

**Estado:** RF-001 a RF-011, RF-015 y RF-017 implementados o parciales según el backend; RF-012 (chequeo de asociaciones), RF-013/RF-014 (consumidores 06/07), RF-016 y el alcance por usuario quedan pendientes. El contrato `AmbienteDirectoryContract` está listo, pero la agenda actual no tiene `ambiente_id`; el módulo 07 deberá agregarlo.

---

### 21. Configuración general y parámetros

**Ruta:** `/organizacion/configuracion` (la ruta heredada `/configuracion` redirige aquí)
**Entrada de sidebar:** `Configuración`

**Archivos:**

- `src/app/pages/organizacion/configuracion/configuracion.page.ts`
- `src/app/pages/organizacion/configuracion/configuracion.page.html`
- `src/app/pages/organizacion/configuracion/configuracion.routes.ts`
- `src/app/pages/organizacion/configuracion/configuracion.service.ts`
- `src/app/pages/organizacion/configuracion/configuracion.models.ts`
- `src/app/pages/organizacion/configuracion/parameter-time-input.component.ts` (campo de hora con Flatpickr y `ControlValueAccessor`)

**API consumida:**

| Método | Endpoint | Acción |
|---|---|---|
| GET | `/api/organizacion/configuracion/parametros` | Listado con búsqueda y filtro por categoría |
| GET | `/api/organizacion/configuracion/parametros/{id}` | Ficha del parámetro |
| PATCH | `/api/organizacion/configuracion/parametros/{id}` | Edición con versión, confirmación y motivo |
| POST | `/api/organizacion/configuracion/parametros/{id}/restaurar` | Restauración al valor por defecto |
| GET | `/api/organizacion/configuracion/parametros/{id}/historial` | Historial paginado desde bitácora |
| POST | `/api/organizacion/configuracion/logo` | Carga del logo del centro (multipart) |

**Funcionalidad:**

- Listado agrupado por categoría con clave, descripción, valor vigente, valor por defecto, unidad y marcas (`Sensible`, `Solo lectura`, `Recepción`).
- Búsqueda por clave/descripción y filtro por categoría; ambas consultas se resuelven en el backend.
- Ficha con tipo, unidad, rango permitido, versión y última modificación.
- Edición con control adaptado al tipo: numérico para entero/decimal, Flatpickr para hora, input/textarea para texto y `app-switch` para booleano.
- Parámetros sensibles: alerta, confirmación explícita y motivo obligatorio antes de guardar; el backend revalida.
- Restauración con confirmación y motivo cuando el parámetro es sensible. La acción se oculta si el valor ya es el de defecto.
- Historial con usuario, fecha, acción, motivo y valores anterior/nuevo, con paginación.
- Logo del centro con vista previa y carga de JPG/PNG/WebP (máx. 2 MB) desde la ficha de `centro.logo`.
- Errores `422` por campo, `409` de concurrencia con opción de recargar, `403`/`401` con alerta. El formulario conserva lo digitado si falla la red.
- Sin botón de exportación: RF-015 queda pendiente de infraestructura.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch`, Flatpickr y `ToastService`. Los diálogos usan el modal compartido centrado.

**Permisos:** la API devuelve `can` (`view`, `view_all`, `view_operatives`, `update`, `update_logo`, `restore`, `export`). Recepción solo ve parámetros operativos con `visible_recepcion = true`; Administración ve todo en modo lectura; Superadmin edita y restaura. Existe `permissionGuard` para rutas; la seguridad real se valida en Laravel.

**Autenticación:** `AuthService` es real desde la Fase 1 del módulo 02; el token se adjunta con el interceptor y la página redirige al login si la sesión expira. No se usan datos mock.

**Estado:** RF-001 a RF-014 y RF-016 implementados en backend y frontend; RF-015 (PDF/Excel) pendiente y sin botón. El logo de RF-009 está implementado con el disco `public`.

---

### 22. Usuarios y alcance por sucursal

**Ruta:** `/seguridad/usuarios`
**Entrada de sidebar:** `Seguridad → Usuarios` (visible con `usuario.ver`)

**Archivos:**

- `src/app/pages/seguridad/usuarios/usuarios.page.ts`
- `src/app/pages/seguridad/usuarios/usuarios.page.html`
- `src/app/pages/seguridad/usuarios/usuarios.routes.ts`
- `src/app/pages/seguridad/usuarios/usuarios.service.ts`
- `src/app/pages/seguridad/usuarios/usuarios.models.ts`
- `src/app/pages/seguridad/usuarios/username.util.ts` (generación de usuario desde el nombre)

**API consumida:**

| Método | Endpoint | Acción |
|---|---|---|
| GET | `/api/seguridad/usuarios` | Listado paginado con búsqueda y filtros por rol, sucursal y estado |
| GET | `/api/seguridad/usuarios/opciones` | Catálogo de roles y sucursales activas del alcance |
| GET | `/api/seguridad/usuarios/{id}` | Ficha |
| GET | `/api/seguridad/usuarios/{id}/historial` | Historial de eventos |
| POST | `/api/seguridad/usuarios` | Alta con rol y sucursales; devuelve contraseña temporal |
| PATCH | `/api/seguridad/usuarios/{id}` | Edición con versión |
| POST | `/api/seguridad/usuarios/{id}/activar` | Activación |
| POST | `/api/seguridad/usuarios/{id}/desactivar` | Desactivación con motivo |
| POST | `/api/seguridad/usuarios/{id}/resetear-password` | Reseteo con temporal |
| POST | `/api/seguridad/usuarios/{id}/desbloquear` | Desbloqueo con motivo |
| POST | `/api/seguridad/usuarios/{id}/cerrar-sesiones` | Cierre remoto con motivo |
| DELETE | `/api/seguridad/usuarios/{id}` | Siempre `409 USER_DELETE_NOT_ALLOWED` |

**Funcionalidad:**

- Listado paginado con búsqueda por nombre, correo, usuario o documento; filtros por rol, sucursal y estado (`activo`, `inactivo`, `bloqueado`).
- Ficha con datos, rol, sucursales, estado, último acceso, sesiones activas, intentos fallidos y versión.
- Alta/edición en modal compartido con rol único y selector de sucursales; el selector se refresca desde `/opciones` en cada apertura y solo muestra sucursales activas del alcance. El formulario conserva lo digitado si falla la red.
- El nombre de usuario se autogenera desde el nombre completo en el alta (`Juan Pérez` → `juan.perez`), quitando tildes y normalizando espacios. Si se edita manualmente, deja de autocompletarse; en edición se conserva el existente.
- El correo es opcional en el alta y la edición. Si se deja vacío se envía `null`; no se envían correos desde el sistema.
- La contraseña temporal se muestra una sola vez, con botón de copiar; no se guarda en el frontend.
- Activación/desactivación con motivo; desactivar invalida las sesiones del usuario.
- Reseteo con cierre de sesiones y cambio forzado; desbloqueo y cierre remoto con motivo.
- Historial con acción, actor, fecha, IP, motivo, resultado, marca de crítico y detalle antes/después.
- Errores `422` por campo, `409` de versión con recarga, `403`/`401` con alerta.
- Sin eliminación física y sin exportación (RF-U15 pendiente de infraestructura).

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]` y `ToastService`. Los diálogos usan el modal compartido centrado.

**Permisos:** la API devuelve `can` (`create`, `export=false`) y un `can` por fila (`update`, `change_status`, `reset_password`, `unlock`, `close_sessions`, `delete=false`). Superadmin administra; Administración solo consulta usuarios de sus sucursales; Recepción no accede. El backend revalida permisos y alcance en cada endpoint.

**Estado:** RF-U01 a RF-U14 y RF-U16 implementados en backend y frontend; RF-U15 (exportación) pendiente y sin botón. El alcance por sucursal es real desde la Fase 2 del módulo 02.

---

### 23. Roles y permisos

**Ruta:** `/seguridad/roles-permisos`
**Entrada de sidebar:** `Seguridad → Roles y permisos` (visible con `rol.ver`)

**Archivos:**

- `src/app/pages/seguridad/roles-permisos/roles-permisos.page.ts`
- `src/app/pages/seguridad/roles-permisos/roles-permisos.page.html`
- `src/app/pages/seguridad/roles-permisos/roles-permisos.routes.ts`
- `src/app/pages/seguridad/roles-permisos/roles-permisos.service.ts`
- `src/app/pages/seguridad/roles-permisos/roles-permisos.models.ts`
- `src/app/pages/seguridad/roles-permisos/role-matrix-date-input.component.ts` (Flatpickr)

**API consumida:**

| Método | Endpoint | Acción |
|---|---|---|
| GET | `/api/seguridad/roles` | Matriz completa: roles, módulos, acciones y total |
| GET | `/api/seguridad/roles/{id}` | Matriz de un rol |
| GET | `/api/seguridad/roles/{id}/historial?fecha=AAAA-MM-DD` | Vigencia histórica desde bitácora |
| PUT | `/api/seguridad/roles/{id}/permisos` | Edición con `version` |
| DELETE | `/api/seguridad/roles/{id}` | Siempre `409 ROLE_DELETE_NOT_ALLOWED` |

**Funcionalidad:**

- Selector de rol con contador de permisos y versión.
- Grilla por módulo y acción con checkboxes; el Superadmin edita y Administración consulta.
- Permisos críticos del Superadmin bloqueados con candado y aviso.
- Guardado con `version`; 409 de concurrencia con recarga; `MATRIX_UNCHANGED` y `CRITICAL_PERMISSION_PROTECTED` con mensajes claros.
- Consulta de vigencia por fecha con selector Flatpickr y detalle de permisos vigentes.
- Los cambios aplican en la siguiente operación (RN-22).
- Sin creación/eliminación de roles y sin exportación (RF-R03 pendiente de infraestructura).

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert` y Flatpickr. No se agregó una primitiva compartida de tabs; el selector de roles usa botones Tailwind.

**Permisos:** `rol.ver` para consultar; `rol.editar_permisos` para editar. Superadmin edita; Administración solo consulta; Recepción no accede. La API devuelve `can.edit` y `can.export=false`.

**Estado:** RF-R01–R15 implementados en backend y frontend, salvo la exportación de RF-R03, pendiente de infraestructura.

---

### 24. Bitácora

**Ruta:** `/seguridad/bitacora`
**Entrada de sidebar:** `Seguridad → Bitácora` (visible con `bitacora.ver`)

**Archivos:**

- `src/app/pages/seguridad/bitacora/bitacora.page.ts`
- `src/app/pages/seguridad/bitacora/bitacora.page.html`
- `src/app/pages/seguridad/bitacora/bitacora.routes.ts`
- `src/app/pages/seguridad/bitacora/bitacora.service.ts`
- `src/app/pages/seguridad/bitacora/bitacora.models.ts`
- `src/app/pages/seguridad/bitacora/bitacora-date-input.component.ts` (Flatpickr)

**API consumida:**

| Método | Endpoint | Acción |
|---|---|---|
| GET | `/api/seguridad/bitacora` | Listado paginado con filtros |
| GET | `/api/seguridad/bitacora/opciones` | Usuarios, módulos, entidades, acciones, resultados y retención |
| GET | `/api/seguridad/bitacora/reconstruir` | Estado de una entidad en una fecha |
| GET | `/api/seguridad/bitacora/{id}` | Detalle del evento |

**Funcionalidad:**

- Filtros por fecha desde/hasta (Flatpickr), usuario, módulo, entidad, acción, resultado, criticidad y sucursal.
- Paginación y tabla con usuario, módulo, entidad/ID, acción, resultado, crítico, IP y sucursal.
- Detalle con antes/después en JSON, motivo, IP, actor y resultado.
- Marca visual de eventos críticos.
- Panel de reconstrucción por entidad, ID y fecha.
- Nota de retención configurada y ausencia de purga manual.
- Sin botón de exportación: RF-B09 pendiente de infraestructura.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field` y Flatpickr.

**Permisos:** `bitacora.ver`. Superadmin ve todo; Administración ve eventos de sus sucursales, incluidos los eventos de seguridad de sus usuarios; la matriz de permisos queda solo para Superadmin. Recepción no accede. La API devuelve `can.export=false`.

**Estado:** RF-B01–B08 y B10–B15 implementados en backend y frontend; RF-B09 (exportar bitácora) pendiente de infraestructura.

---

## Paleta de colores

Definida en `src/styles.css` dentro de `@theme`.

| Token | Color | Uso |
|---|---|---|
| `brand-500` | `#671c61` | Color primario: botones, links, sidebar activo |
| `brand-600` | `#5a1855` | Hover del primario |
| `secondary-400` | `#d27eba` | Color secundario |
| `accent-500` | `#f9733f` | Acento / llamadas de atención |
| `highlight-500` | `#ffbd5d` | Resaltados |
| `white` | `#fdfefe` | Fondo claro |
| `gray-900` | `#000000` | Fondo oscuro |

Los gráficos, el calendario, Flatpickr y los focus rings usan estos tokens en lugar de azules hardcodeados.

---

## Dependencias principales añadidas

- `@angular/cdk`
- `@spartan-ng/brain`
- `@fullcalendar/angular` + `fullcalendar` + `temporal-polyfill`
- `apexcharts` + `ng-apexcharts`
- `flatpickr`
- `tailwindcss` + `@tailwindcss/postcss` + `postcss`
- `tw-animate-css`
- `zone.js`
- `clsx`

No se agregaron `swiper` ni `jsvectormap` porque sus componentes no forman parte de esta entrega.

---

## Qué se dejó fuera

- Dashboards de demostración (Ecommerce, Analytics, Marketing, Stocks, SaaS).
- Profile.
- FAQs, Pricing Tables, File Manager.
- Tasks y Kanban.
- Coming Soon.
- Carrusel, mapas vectoriales, avatares, tabs y popovers no solicitados. Los diálogos de las páginas de negocio usan el modal compartido `app-modal`; las tabs de salas/ambientes siguen siendo propias de esa página.
- Exportación PDF/Excel de parámetros (RF-015): sin librerías instaladas y sin botón en la UI.
- Exportaciones de usuarios/matriz/bitácora y envío automático de contraseña temporal (pendiente de infraestructura).
- Registro, recuperación de contraseña y verificación en dos pasos.
- Widget promocional del sidebar.

---

## Verificación

```bash
npm install
npm run build
```

Resultado esperado: compilación exitosa en `dist/talitacumi`.

No se ejecutó `ng serve` ni se levantó el servidor.

---

## Notas

- Todos los componentes son standalone.
- Se usa la sintaxis moderna `@if` / `@for`.
- El layout y las rutas están preparados para implementar cada módulo de forma independiente.
- La autenticación es real desde la Fase 1 del módulo 02: token Sanctum en `sessionStorage`, interceptor HTTP, `authGuard`/`guestGuard`/`permissionGuard` y cambio de contraseña forzado. El alcance real por sucursal y la pantalla de usuarios están en la Fase 2; la matriz de roles y permisos en la Fase 3; la bitácora en la Fase 4.
- La página `/organizacion/sucursales` ya consume la API real con el token del interceptor. El alcance por sucursal es real desde la Fase 2: Administración y Recepción ven solo sus sucursales asignadas; las operaciones fuera de alcance responden `403`.
- La página `/organizacion/salas-ambientes` también consume la API real, con cuatro secciones y overlays propios. No se agregaron dependencias nuevas.
- La página `/organizacion/configuracion` consume la API real, agrupa parámetros por categoría (incluida la nueva categoría `seguridad`) y usa overlays propios para ficha, edición, restauración e historial. `/configuracion` redirige a la ruta canónica.
- La pantalla `/cambiar-password` es la única operación permitida cuando el usuario tiene contraseña temporal; el resto de las rutas se redirige allí.
- **Estado Fase 4:** consulta/detalle de bitácora, filtros, scoping, catálogo de acciones, reconstrucción por fecha y mecanismo de auditoría de datos sensibles/exportaciones implementados. Solo quedan las exportaciones PDF/Excel y los consumidores de los módulos 04/08/11.
- No se agregaron datos mock ni dependencias nuevas para sucursales, salas/ambientes, configuración ni autenticación. La exportación PDF/Excel (RF-015) sigue fuera de alcance y no se muestra el botón.
- `npm run build` se ejecutó correctamente después de implementar el módulo.
- No se realizó commit.
