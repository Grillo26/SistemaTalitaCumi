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
| `/pacientes` | Pacientes: listado, ficha, alta/edición, estados y tutores principales |
| `/pacientes/:id` | Ficha del paciente |
| `/pacientes/:id/resumen` | Ficha resumen de solo lectura con indicadores y acciones frecuentes (04.04) |
| `/pacientes/:id/ficha-ingreso` | Ficha administrativa de ingreso, consentimientos y observaciones del paciente (04.03 Fases A, B y C) |
| `/pacientes/tutores` | Tutores: listado, búsqueda, alta y filtros |
| `/pacientes/tutores/:id` | Ficha del tutor, contacto, estado y pacientes asociados |
| `/inscripciones` | Redirige a `/inscripciones-atenciones/salas-grupales` |
| `/inscripciones-atenciones/salas-grupales` | Inscripciones a salas grupales: listado, ficha, alta, renovación y baja |
| `/pagos` | Pagos y caja |
| `/reportes` | Reportes |
| `/catalogo` | Catálogo de servicios: áreas, servicios y tipos |
| `/catalogo-servicios/servicios` | Redirige a `/catalogo` |
| `/catalogo-servicios/tarifas` | Tarifas y categorías: vigencias, precios y consulta aplicable |
| `/catalogo-servicios/tipos-diagnostico` | Tipos de diagnóstico: especialidades requeridas, días estimados y estado |
| `/profesionales` | Profesionales: directorio, especialidades y sucursales |
| `/inventario` | Inventario |
| `/configuracion` | Redirige a `/organizacion/configuracion` |
| `/organizacion/sucursales` | Organización y sucursales |
| `/organizacion/salas-ambientes` | Salas y ambientes |
| `/organizacion/configuracion` | Configuración general y parámetros |
| `/seguridad/usuarios` | Usuarios, roles asignados y alcance por sucursal |
| `/seguridad/roles-permisos` | Matriz de roles y permisos |
| `/seguridad/bitacora` | Consulta y reconstrucción de bitácora |

Las rutas `/pacientes`, `/profesionales`, `/organizacion/sucursales`, `/organizacion/salas-ambientes`, `/organizacion/configuracion`, `/seguridad/usuarios`, `/seguridad/roles-permisos`, `/seguridad/bitacora` y `/inscripciones-atenciones/salas-grupales` ya están implementadas y consumen la API real del backend. Los módulos sin implementar muestran un estado vacío reutilizable (`app-module-placeholder`). `/pacientes` usa `permissionGuard` con `paciente.ver` y el sidebar oculta la entrada sin permiso.

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
│   │   │   ├── inscripciones-atenciones/
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

### 25. Profesionales

**Ruta:** `/profesionales`
**Entrada de sidebar:** `Profesionales` (visible con `profesional.ver`)

**Archivos:**

- `src/app/pages/profesionales/profesionales.page.ts`
- `src/app/pages/profesionales/profesionales.page.html`
- `src/app/pages/profesionales/profesionales.routes.ts`
- `src/app/pages/profesionales/profesionales.service.ts`
- `src/app/pages/profesionales/profesionales.models.ts`

**API consumida:**

| Entidad | Endpoints |
|---|---|
| Profesionales | `GET/POST /api/profesionales/profesionales`, `GET/PATCH /profesionales/{id}`, `GET /profesionales/opciones`, `POST /{id}/activar`, `POST /{id}/desactivar` |
| Sucursales de trabajo | `GET/POST /profesionales/{id}/sucursales`, `POST /{id}/sucursales/{sucursalId}/activar`, `POST /{id}/sucursales/{sucursalId}/desactivar` |
| Especialidades | `GET/POST /api/profesionales/especialidades`, `GET/PATCH /especialidades/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar` |
| Matriz | `GET /api/profesionales/matriz`, `GET /api/profesionales/matriz/opciones` |

**Funcionalidad:**

- Una sola página con tabs propias: **Directorio**, **Especialidades** y **Matriz**.
- Directorio: búsqueda por nombre/CI/correo, filtros por especialidad, sucursal y estado, paginación, ficha, alta/edición en modal y activación/desactivación con motivo.
- Ficha con especialidades, sucursales, estado, última modificación y marca **Asignable / No asignable** con los motivos (sin sucursal activa, especialidad inactiva, profesional inactivo).
- Modal de sucursales de trabajo: asignar, activar y desactivar relaciones con motivo.
- Especialidades: búsqueda, filtro por estado, paginación, alta/edición, activación/desactivación con motivo y conteo de profesionales activos. La desactivación se deshabilita si hay profesionales activos.
- Matriz: consulta filtrable por profesional, especialidad, sucursal, estado del profesional y “solo asignables”; muestra el tipo de especialidad, los estados de profesional/especialidad/sucursal y el motivo de no asignabilidad. Solo lectura, con acceso a la ficha del profesional y al modal de sucursales para quien puede administrarlas. Sin exportación.
- Administración solo puede editar contactos: el formulario deshabilita y omite nombre, CI, matrícula y especialidades. Recepción solo consulta.
- El formulario conserva lo digitado si falla la red. Errores `422` por campo (incluido documento duplicado), `409` de concurrencia con recarga, `409` de especialidad en uso, y `403`/`401` con alerta.
- Sin botón de exportación (pendiente de infraestructura).
- Sin pantalla de asignación a turnos: la agenda (07) consumirá los contratos del backend.
- Sin datos mock: todo se carga desde la API.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch` y `ToastService`. Los selectores de sucursal reutilizan `SucursalesService` en lectura cuando corresponde.

**Permisos y alcance:** `permissionGuard` con `profesional.ver`; el sidebar oculta la entrada sin permiso. La API devuelve `can` de pantalla y por fila. Sin alcance global, el usuario solo ve profesionales con relación activa a sus sucursales; un profesional sin sucursal solo lo ve el alcance global.

**Estado:** Fases 1 y 2 implementadas en backend y frontend (directorio, especialidades, sucursales, matriz y validador cacheado). La Fase 3 del backend entrega contratos/stubs de agenda, diagnósticos y exportación; no agrega pantallas. La exportación PDF/Excel y la asignación real a turnos quedan fuera de esta entrega.

---

### 26. Pacientes

**Rutas:** `/pacientes` (listado) y `/pacientes/:id` (ficha).
**Entrada de sidebar:** `Pacientes` (visible con `paciente.ver`).

**Archivos:**

- `src/app/pages/pacientes/pacientes.page.ts`
- `src/app/pages/pacientes/pacientes.page.html`
- `src/app/pages/pacientes/paciente-ficha.page.ts`
- `src/app/pages/pacientes/paciente-ficha.page.html`
- `src/app/pages/pacientes/pacientes.routes.ts`
- `src/app/pages/pacientes/pacientes.service.ts`
- `src/app/pages/pacientes/pacientes.models.ts`
- `src/app/pages/pacientes/paciente-form-modal.component.*`
- `src/app/pages/pacientes/paciente-status-modal.component.*`
- `src/app/pages/pacientes/paciente-tutor-modal.component.*`
- `src/app/pages/pacientes/paciente-date-input.component.ts`
- `src/app/pages/pacientes/tutores.page.ts`
- `src/app/pages/pacientes/tutores.page.html`
- `src/app/pages/pacientes/tutor-ficha.page.ts`
- `src/app/pages/pacientes/tutor-ficha.page.html`
- `src/app/pages/pacientes/tutor-form-modal.component.*`
- `src/app/pages/pacientes/tutor-status-modal.component.*`
- `src/app/pages/pacientes/contacto-emergencia-modal.component.*`
- `src/app/pages/pacientes/paciente-ficha-ingreso.page.*`
- `src/app/pages/pacientes/paciente-resumen.page.*`
- `src/app/pages/pacientes/paciente-resumen.models.ts`
- `src/app/pages/pacientes/ficha-ingreso.models.ts`
- `src/app/pages/pacientes/ficha-ingreso.service.ts`
- `src/app/pages/pacientes/ficha-ingreso-estado-modal.component.*`
- `src/app/pages/pacientes/ficha-ingreso-revisiones-modal.component.*`
- `src/app/pages/pacientes/consentimiento-form-modal.component.*`
- `src/app/pages/pacientes/consentimiento-revoke-modal.component.*`
- `src/app/pages/pacientes/consentimiento-tipos-modal.component.*`
- `src/app/pages/pacientes/observacion-form-modal.component.*`

**API consumida:**

| Entidad | Endpoints |
|---|---|
| Pacientes | `GET/POST /api/pacientes/pacientes`, `GET/PATCH /pacientes/{id}`, `GET /opciones`, `POST /{id}/estado`, `DELETE /{id}` |
| Tutores del paciente | `GET/POST /pacientes/{id}/tutores`, `POST /{tutorId}/principal`, `DELETE /{tutorId}` |
| Tutores | `GET/POST /api/pacientes/tutores`, `GET /tutores/opciones`, `GET /tutores/exportar`, `GET/PATCH /tutores/{id}`, `POST /tutores/{id}/estado`, `DELETE /tutores/{id}` → 409 |
| Contactos de emergencia | `GET/POST /pacientes/{id}/contactos-emergencia`, `PATCH/DELETE /contactos-emergencia/{contactoId}` |
| Ficha de ingreso 04.03 | `GET/POST /pacientes/{id}/ficha-ingreso`, `GET/PATCH /ficha-ingreso/{fichaId}`, `POST /{fichaId}/completar`, `/archivar`, `/reactivar`, `GET /{fichaId}/revisiones` |
| Consentimientos 04.03 | `GET/POST /pacientes/{id}/ficha-ingreso/{fichaId}/consentimientos`, `POST /{consentimientoId}/revocar`, `GET/POST/PATCH /ficha-ingreso/consentimientos/tipos` |
| Observaciones 04.03 | `GET/POST /pacientes/{id}/ficha-ingreso/{fichaId}/observaciones`, `DELETE /{observacionId}` → 409 |
| Búsqueda/resumen 04.04 | `GET /pacientes` extendido (tutor, apellido), `GET /pacientes/{id}/resumen`, `GET /{id}/resumen/indicadores`, `GET /pacientes/exportar` (CSV) |

**Funcionalidad:**

- Listado paginado con búsqueda por nombre/documento/código, filtros por estado, sucursal de registro y “solo sin diagnóstico”, orden y paginación. El filtro por tipo de diagnóstico está deshabilitado hasta el módulo 05; el de sucursal de inscripción, hasta el 06.
- La tabla no muestra alergias, condiciones ni observaciones.
- Ficha con datos, edad calculada por el backend, estado, sucursal de registro, diagnóstico o “sin diagnóstico definido”, observaciones, última modificación y aviso de auditoría.
- Datos sensibles (alergias/condiciones) solo con `can.view_sensibles`; si no, tarjeta protegida. El backend nunca los envía sin permiso.
- Alta en modal con tutor principal nuevo o existente. El paciente no puede quedar activo sin tutor. Si el submódulo no estuviera disponible, el submit se bloquea con mensaje explícito.
- Edición en modal reutilizando la ficha cargada (sin GET extra); campos sensibles bajo `app-switch` y permiso.
- Cambio de estado con transiciones permitidas y motivo obligatorio según la regla. `PATIENT_TUTOR_REQUIRED` se muestra si falta tutor para activar.
- Duplicados: bloqueo duro por documento y confirmación con motivo para coincidencias por nombre/fecha. Los datos fuera de alcance no se muestran; “Abrir ficha” navega y audita.
- Sección de tutores real (04.02 Fase A): principal, agregar existente/nuevo, hacer principal con motivo cuando se reemplaza y quitar. El backend impide quitar el principal de un paciente activo.
- Listado standalone de tutores con búsqueda, filtros por estado/sucursal, orden y paginación. El teléfono se muestra enmascarado (`712***67`) salvo que el usuario tenga `tutor.ver_contacto`.
- Botón **Exportar Excel** visible con `tutor.exportar`; descarga un CSV compatible con Excel respetando filtros y alcance. La exportación queda auditada en backend.
- Ficha standalone de tutor con identificación, contacto, estado, motivo de desactivación, última modificación y pacientes asociados. La consulta se audita y es fail-closed.
- Alta/edición de tutor en modal con catálogo de parentescos y sucursal de registro. Si el tutor está compartido con sucursales fuera del alcance, el modal deshabilita identidad y solo permite contacto.
- Activación/desactivación con motivo obligatorio al desactivar; se informa si el tutor es principal de un paciente activo. No hay botón de eliminación: el `DELETE` siempre es 409.
- Asociación de tutor existente con parentesco por relación; el alta de paciente usa el catálogo de parentescos y envía `parentesco_id`.
- Contactos de emergencia adicionales en la ficha del paciente: listado, alta, edición y eliminación en modal. Solo se muestran con `paciente.ver_contacto_emergencia` y se gestionan con `paciente.editar_contacto_emergencia`; el teléfono usa la misma normalización que los tutores.
- Ficha de ingreso 04.03 Fase A en ruta hija `/pacientes/:id/ficha-ingreso`, con carga diferida y auditoría explícita: crear borrador, consultar, editar, completar, archivar, reactivar y ver revisiones. Los campos sensibles se muestran con `ficha_ingreso.ver_sensibles`; sin permiso se muestran protegidos. Los contactos de emergencia existentes se muestran en modo lectura y se administran desde la ficha del paciente. No hay botón de exportación; PDF y módulo 06 quedan pendientes.
- Consentimientos 04.03 Fase B en la misma ficha: vigentes e historial, alta de otorgado/negado con tipo, tutor y fecha, revocación con motivo y catálogo administrable por Superadmin. Sin tutores activos o con la ficha archivada se muestran los bloqueos y se ocultan las acciones. El motivo libre se muestra solo con permiso de revocación o `ficha_ingreso.ver_sensibles`; la bitácora no lo guarda.
- Observaciones fechadas 04.03 Fase C en la misma ficha: listado cronológico, alta con fecha y texto, y corrección opcional referenciando una observación anterior. No hay edición ni eliminación. La ficha archivada bloquea el alta. Sin `ficha_ingreso.ver_sensibles` el texto se muestra protegido y no se permite agregar.
- Búsqueda 04.04 en `/pacientes`: nombre/apellido/documento/código y tutor principal, con teléfono solo si `tutor.ver_contacto`; orden por apellido, fecha de nacimiento o estado; ayuda visible y filtros de inscripción/servicio/pago deshabilitados hasta 06/08. La fila abre la ficha resumen.
- Ficha resumen 04.04 en `/pacientes/:id/resumen`: datos básicos, banderas sensibles, tutores y contactos con máscara/permiso, y bloques de servicios, pago, asistencia, vencimiento, sucursales y alerta con estado “no disponible” explícito mientras no existan 06/07/08/09. Incluye “Actualizar indicadores”, “Ver ficha completa” y “Ver ficha de ingreso” si corresponde. No permite editar.
- Exportación CSV 04.04 desde `/pacientes` con `paciente.exportar_listado`; columnas mínimas sin teléfonos ni datos clínicos. PDF no se muestra: pendiente de infraestructura.
- Errores 422 por campo, 409 de concurrencia con recarga, 409 de duplicado, 403/404/503 con `app-alert`. `503 AUDIT_UNAVAILABLE` no muestra la ficha (fail-closed).
- Sin prefetch: la ficha solo se carga al navegar a `/pacientes/:id`; cada apertura queda auditada.
- Sin botón de exportación (pendiente de infraestructura) y sin datos mock.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch` y `ToastService`. La fecha usa Flatpickr mediante `app-paciente-date-input`, con `maxDate: today`.

**Permisos y alcance:** el padre `/pacientes` acepta `paciente.ver` o `tutor.ver`; cada hijo exige el permiso de su pantalla (`paciente.ver` o `tutor.ver`). El sidebar oculta Pacientes y Tutores según permiso. Sin alcance global, el usuario solo ve pacientes/tutores de sus sucursales; la ficha fuera de alcance responde 404. La API devuelve `can` de pantalla y por fila; la UI solo oculta acciones, el backend revalida.

**Estado:** 04.01 Fase 1, 04.02 Fases A y B, 04.03 Fases A/B/C y 04.04 Fases A/B/C implementados en lo que no depende de infraestructura. Incluye búsqueda por tutor, ficha resumen con bloques `no_disponible`, auditoría de lectura, exportación CSV y permisos. Pendientes de 04.03: PDF y vínculo real con la entrevista 06. Pendientes de 04.04: PDF del listado, indicadores reales de 06/07/08/09 y alerta de pago. Pendientes generales: PDF de tutores/ficha de ingreso, consumo del teléfono por la alerta del módulo 09, catálogo 05, filtro/visibilidad 06, ejecución 07/08 e importación CSV.

### 27. Catálogo de servicios

**Ruta:** `/catalogo` (se mantiene la ruta existente; reemplaza el placeholder).
**Entrada de sidebar:** `Catálogo` (visible con `servicios.ver`).
**Guard:** `permissionGuard` con `areas.ver` o `servicios.ver`.

**Archivos:**

- `src/app/pages/catalogo-servicios/servicios/servicios.page.ts`
- `src/app/pages/catalogo-servicios/servicios/servicios.page.html`
- `src/app/pages/catalogo-servicios/servicios/servicios.routes.ts`
- `src/app/pages/catalogo-servicios/servicios/servicios.service.ts`
- `src/app/pages/catalogo-servicios/servicios/servicios.models.ts`

**API consumida:**

| Entidad | Endpoints |
|---|---|
| Áreas | `GET/POST /api/catalogo-servicios/areas`, `GET/PATCH /areas/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` → `409` |
| Servicios | `GET/POST /api/catalogo-servicios/servicios`, `GET/PATCH /servicios/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` → `409` |
| Tipos de servicio | `GET/POST /api/catalogo-servicios/tipos-servicio`, `GET/PATCH /tipos-servicio/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` → `409` |

**Funcionalidad:**

- Una sola página con tabs propias: **Servicios** (default), **Áreas** y **Tipos de servicio**.
- Servicios: búsqueda, filtros por área, tipo y estado, orden por nombre/área/tipo/duración/estado y paginación. La tabla muestra área con swatch de color, tipo, duración, estado y última modificación.
- Ficha de servicio en `app-modal` con área, tipo, duración, estado, descripción y última modificación.
- Áreas: búsqueda, filtro por estado, orden, servicios activos, color, estado y última modificación.
- Alta/edición de área con nombre, descripción, color (picker nativo + hex con validación) y estado inicial. El color se usa solo en el swatch/etiqueta del área.
- Tipos de servicio: búsqueda, filtro por estado, alta/edición y cambio de estado.
- Cambio de estado con motivo obligatorio; si el área tiene servicios activos se muestra el `409 AREA_CON_SERVICIOS_ACTIVOS` con el conteo y acceso a “Ver servicios del área”.
- `409 VERSION_CONFLICT` con opción de recargar; `422` por campo; `403`/`401`/`503` con alerta. El formulario conserva lo digitado si falla la red.
- Controles de escritura ocultos si el `can` correspondiente es `false`. El backend revalida permisos.
- Sin botón de eliminación: las entidades se desactivan.
- Sin exportación: RF-C17 pendiente de infraestructura, sin botón.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch` y `ToastService`.

**Permisos:** `areas.ver`/`servicios.ver` para consultar; `areas.editar`/`servicios.editar` para crear, editar, cambiar color o estado. Administración y Recepción solo consultan; Superadmin administra.

**Estado:** submódulo 01 implementado contra la API real. Pendientes: exportación PDF/Excel, migración inicial desde planilla del cliente y consolidación del mapeo `Area` con 01.02.

### 28. Tarifas y categorías

**Ruta:** `/catalogo-servicios/tarifas`.
**Entrada de sidebar:** `Tarifas` (visible con `tarifas.ver`).
**Guard:** `permissionGuard` con `tarifas.ver` o `categorias.ver`.

**Archivos:**

- `src/app/pages/catalogo-servicios/tarifas/tarifas.page.ts`
- `src/app/pages/catalogo-servicios/tarifas/tarifas.page.html`
- `src/app/pages/catalogo-servicios/tarifas/tarifas.routes.ts`
- `src/app/pages/catalogo-servicios/tarifas/tarifas.service.ts`
- `src/app/pages/catalogo-servicios/tarifas/tarifas.models.ts`
- `src/app/pages/catalogo-servicios/tarifas/tarifa-date-input.component.ts`

**API consumida:**

| Entidad | Endpoints |
|---|---|
| Categorías | `GET/POST /api/catalogo-servicios/categorias-tarifa`, `GET/PATCH /{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` → `409` |
| Tarifas | `GET/POST /api/catalogo-servicios/tarifas`, `GET/PATCH /{id}`, `POST /{id}/cerrar-vigencia`, `DELETE /{id}` → `409` |
| Aplicable | `GET /api/catalogo-servicios/tarifas/aplicable?servicio_id&sucursal_id&categoria_tarifa_id&fecha` |

**Funcionalidad:**

- Una sola página con tabs propias: **Tarifas** (default) y **Categorías**.
- Tarifas: búsqueda por servicio, filtros por servicio, sucursal (incluye “Globales”), categoría y estado de vigencia; orden y paginación.
- Tabla con servicio/área, categoría, sucursal/Global, monto `Bs 0.00`, vigencia, estado (`Vigente`, `Futura`, `Cerrada`) y acciones.
- Ficha de tarifa en `app-modal`.
- Alta/edición con servicio (API del 01), categoría, sucursal, monto y vigencia desde/hasta. En edición, `vigente_hasta` no se edita: se cierra con la acción específica.
- “Cerrar vigencia” con fecha fin y motivo obligatorio.
- “Consultar tarifa aplicable” con servicio, sucursal, categoría y fecha; muestra monto, origen sucursal/global o “sin tarifa aplicable”.
- Categorías: búsqueda, filtro por estado, tarifas vigentes/totales, alta/edición y activación/desactivación con motivo.
- `409 TARIFA_SOLAPADA` con detalle del conflicto; `409 CATEGORIA_CON_TARIFAS_VIGENTES`; `409 TARIFA_CERRADA`/`TARIFA_YA_CERRADA`; `409 VERSION_CONFLICT`.
- `422` por campo, `403`/`401`/`503` con alerta. El formulario conserva lo digitado si falla la red.
- Controles de escritura ocultos según `can` de pantalla y `can` por fila de tarifa.
- Sin eliminación y sin exportación (RF-T18 pendiente, sin botón).
- Montos como string, nunca `float`; fechas con Flatpickr (`app-tarifa-date-input`).

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch`, `SucursalesService`, `CatalogoServiciosService` del 01 y `ToastService`.

**Permisos:** `tarifas.ver`/`categorias.ver` para consultar; `tarifas.editar` para tarifas (según alcance por sucursal); `categorias.editar` para categorías (Superadmin). Administración edita tarifas de sus sucursales; Recepción solo consulta.

**Estado:** submódulo 02 implementado contra API real. Pendientes: selección de categoría en inscripción (06), integración de pagos (08), exportación PDF/Excel y migración inicial de tarifas.

### 29. Tipos de diagnóstico

**Ruta:** `/catalogo-servicios/tipos-diagnostico`.
**Entrada de sidebar:** `Diagnósticos` (visible con `tipos_diagnostico.ver`).
**Guard:** `permissionGuard` con `tipos_diagnostico.ver`.

**Archivos:**

- `src/app/pages/catalogo-servicios/tipos-diagnostico/tipos-diagnostico.page.ts`
- `src/app/pages/catalogo-servicios/tipos-diagnostico/tipos-diagnostico.page.html`
- `src/app/pages/catalogo-servicios/tipos-diagnostico/tipos-diagnostico.routes.ts`
- `src/app/pages/catalogo-servicios/tipos-diagnostico/tipos-diagnostico.service.ts`
- `src/app/pages/catalogo-servicios/tipos-diagnostico/tipos-diagnostico.models.ts`

**API consumida:**

| Entidad | Endpoints |
|---|---|
| Tipos de diagnóstico | `GET/POST /api/catalogo-servicios/tipos-diagnostico`, `GET/PATCH /tipos-diagnostico/{id}`, `POST /{id}/activar`, `POST /{id}/desactivar`, `DELETE /{id}` → `409` |
| Requisitos | `GET /api/catalogo-servicios/tipos-diagnostico/{id}/especialidades-requeridas` |
| Catálogos | `GET /api/catalogo-servicios/servicios` (filtra `tipo_servicio.codigo = diagnostico_evaluacion`) y `GET /api/profesionales/especialidades` |

**Funcionalidad:**

- Listado con búsqueda por nombre/descripción, filtros por estado y especialidad, orden por tipo/estado/días/última modificación y paginación.
- Tabla con tipo y descripción, servicio y área, días estimados, especialidades requeridas con cantidad, estado y última modificación.
- Ficha del tipo en `app-modal` con servicio, área, días, versión, descripción y especialidades requeridas; permite pasar a edición.
- Alta/edición con servicio (solo servicios activos de tipo diagnóstico/evaluación), nombre, descripción, días estimados y configurador de especialidades con cantidad mínima. El estado inicial se define con `app-switch`; en edición el estado se cambia solo con las acciones de activar/desactivar.
- Validación en cliente de al menos una especialidad, sin duplicados y cantidad `>= 1`; los errores `422` del backend se muestran por campo, incluidos los de las filas de especialidades.
- Activación/desactivación con motivo obligatorio y `version`; `409 VERSION_CONFLICT` muestra alerta y permite recargar.
- Sin eliminación: el botón no existe y el `DELETE` de la API responde `409`. Sin exportación (RF-D13 pendiente, sin botón).
- Controles de escritura ocultos según `can.tipos_diagnostico`; el backend revalida permisos.
- Los catálogos de servicios y especialidades son reales; si fallan se muestra una alerta y no se usan datos mock.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch`, `CatalogoServiciosService`, `ProfesionalesService` y `ToastService`.

**Permisos:** `tipos_diagnostico.ver` para consultar; `tipos_diagnostico.editar` para crear, editar y cambiar estado. Superadmin administra; Administración y Recepción solo consultan.

**Estado:** submódulo 03 implementado contra API real. Pendientes: exportación PDF/Excel, integración del tipo en la ficha de paciente (04), bloqueo de tipos inactivos en inscripciones (06) y snapshot de requisitos en diagnósticos (11).

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
- Exportación PDF/Excel de parámetros (RF-015), del catálogo de servicios (RF-C17) y de tarifas (RF-T18): sin librerías instaladas y sin botón en la UI.
- Tarifas: selección de categoría dentro del flujo de inscripción (06) e integración con pagos (08). El submódulo 02 expone la API y el contrato de resolución.
- Exportaciones de usuarios/matriz/bitácora/profesionales/pacientes y envío automático de contraseña temporal (pendiente de infraestructura).
- Persistencia real de asignaciones a turnos/diagnósticos (módulo 07). Los contratos/stubs de Fase 3 ya están implementados en backend.
- Asignación de profesionales a turnos/diagnósticos (módulo 07): solo se documentan los contratos.
- 04.02 Fase C: PDF de tutores y enlace de WhatsApp consumido por el módulo 09. El CSV de tutores ya está implementado.
- 04.03 Fase C: PDF y entrevista 06 (las observaciones fechadas ya están implementadas).
- 04.04: PDF del listado, indicadores reales de 06/07/08/09, alternancia de sucursal y alerta de pago. La búsqueda, la ficha resumen y el CSV ya están implementados.
- Integración de tipos de diagnóstico en pacientes (04 con 05): el catálogo ya existe en `/catalogo-servicios/tipos-diagnostico`, pero en la ficha de paciente el campo queda “sin diagnóstico definido” y el filtro deshabilitado hasta habilitar `DIAGNOSTICOS_DISPONIBLES`.
- Filtro y visibilidad por sucursal de inscripción vigente (06), y bloqueo real de inscripciones/turnos (06/07).
- Importación CSV de pacientes: no hay planilla del cliente; técnicamente viable sin librerías.
- Cifrado de campo de datos clínicos (`encrypted`): no implementado por defecto; se evalúa con el cliente.
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
- La página `/profesionales` consume la API real con dos tabs propias (Directorio y Especialidades), ficha, modales de alta/edición/estado y modal de sucursales de trabajo. El sidebar la oculta sin `profesional.ver`.
- No se agregaron datos mock ni dependencias nuevas para sucursales, salas/ambientes, configuración, profesionales ni autenticación. La exportación PDF/Excel sigue fuera de alcance y no se muestra el botón.
- `npm run build` se ejecutó correctamente después de implementar el módulo.
- No se realizó commit.

---

### 30. Inscripciones a salas grupales

**Ruta:** `/inscripciones-atenciones/salas-grupales` (la ruta heredada `/inscripciones` redirige).
**Entrada de sidebar:** `Inscripciones` (visible con `inscripcion.ver`).
**Guard:** `permissionGuard` con `inscripcion.ver`.

**Archivos:**

- `src/app/pages/inscripciones-atenciones/salas-grupales/salas-grupales.page.ts`
- `src/app/pages/inscripciones-atenciones/salas-grupales/salas-grupales.page.html`
- `src/app/pages/inscripciones-atenciones/salas-grupales/salas-grupales.routes.ts`
- `src/app/pages/inscripciones-atenciones/salas-grupales/salas-grupales.service.ts`
- `src/app/pages/inscripciones-atenciones/salas-grupales/salas-grupales.models.ts`
- `src/app/pages/inscripciones-atenciones/salas-grupales/inscripcion-paciente-search.component.ts`
- `src/app/pages/inscripciones-atenciones/salas-grupales/inscripcion-date-input.component.ts`
- `src/app/pages/inscripciones-atenciones/salas-grupales/inscripcion-comprobante.component.ts`

**API consumida:**

| Método | Endpoint | Acción |
|---|---|---|
| GET | `/api/inscripciones-atenciones/salas-grupales` | Listado paginado con filtros |
| GET | `/api/inscripciones-atenciones/salas-grupales/monto-esperado` | Vista previa del monto |
| GET | `/api/inscripciones-atenciones/salas-grupales/{id}` | Ficha |
| GET | `/api/inscripciones-atenciones/salas-grupales/{id}/renovacion` | Fechas y tarifa propuestas |
| POST | `/api/inscripciones-atenciones/salas-grupales` | Alta |
| PATCH | `/api/inscripciones-atenciones/salas-grupales/{id}` | Edición |
| POST | `/api/inscripciones-atenciones/salas-grupales/{id}/renovar` | Renovación |
| POST | `/api/inscripciones-atenciones/salas-grupales/{id}/retirar` | Baja |
| GET | `/api/inscripciones-atenciones/salas-grupales/{id}/pago` | Estado de pago (stub 503) |
| POST | `/api/inscripciones-atenciones/salas-grupales/{id}/pago` | Inicio de registro de pago (stub 503) |
| GET | `/api/inscripciones-atenciones/salas-grupales/{id}/asistencia` | Asistencia (stub 503) |
| GET | `/api/inscripciones-atenciones/salas-grupales/{id}/turnos-futuros` | Pre-chequeo de turnos (stub 503) |
| GET | `/api/inscripciones-atenciones/salas-grupales/{id}/comprobante` | Datos del comprobante |
| GET | `/api/inscripciones-atenciones/salas-grupales/{id}/comprobante/pdf` | PDF pendiente (503) |
| GET | `/api/inscripciones-atenciones/salas-grupales/exportar` | Exportación pendiente (503) |

**Funcionalidad:**

- Listado paginado con filtros por sucursal, sala, paciente (autocompletado por nombre/apellido/CI/código), mes, año y estado; orden por período, monto o estado.
- Alta en modal con búsqueda de paciente, tutor responsable, sucursal, sala, servicio de sala grupal, categoría, fechas, observaciones y vista previa del monto calculada por el backend.
- Bloqueo de paciente no activo con mensaje “Debe reactivar al paciente antes de inscribir”.
- Confirmación explícita cuando la tarifa vigente cambió (`409 TARIFA_CAMBIO`).
- Errores claros de duplicidad, sala inactiva, sucursal fuera de alcance y fechas inválidas.
- Ficha en modal con resumen, pago, asistencia, comprobante, vínculo de renovación y datos de baja.
- Edición de fechas, servicio, categoría y observaciones; paciente, tutor, sucursal y sala son inmutables.
- Renovación con fechas propuestas, tarifa vigente y vínculo con la inscripción anterior.
- Baja con motivo cerrado, detalle, fecha y pre-chequeo separado de turnos futuros; si Agenda no está disponible, se informa y la baja continúa.
- Comprobante imprimible con datos reales del centro, paciente, tutor, sala, período y leyenda de que no es factura.
- Enlace `wa.me` solo en la ficha y solo con `tutor.ver_contacto`.
- Pagos y asistencia se muestran como “No disponible” mientras los módulos 08/07 no existan; nunca con valores falsos.
- Sin eliminación física y sin botón de exportación; PDF/Excel pendientes de infraestructura.

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `app-modal`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, Flatpickr, `ToastService`, `SucursalesService`, `SalasAmbientesService`, `CatalogoServiciosService`, `TarifasService` y `PacientesService`.

**Permisos:** `inscripcion.ver` para acceder; `inscripcion.crear` para alta; `inscripcion.editar` para modificar; `inscripcion.renovar` para renovar; `inscripcion.retirar` para baja. Recepción crea/consulta/renueva; Administración además edita y da de baja. El backend revalida permisos y alcance por sucursal.

**Estado:** submódulo implementado contra API real. Pendientes: Pagos (08), Asistencia/Agenda (07), PDF real y exportación PDF/Excel, y el flujo de ajuste de RF-009 cuando existan pagos.
