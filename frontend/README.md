# TalitaCumi — Frontend

Frontend del **Sistema de Gestión TalitaCumi**, un sistema de turnos, inscripciones y cobros para un centro de desarrollo infantil. Está construido con Angular standalone y Tailwind CSS, tomando como base visual y técnica el template TailAdmin Angular.

> Esta entrega incluye la base del sistema, el login simulado y las páginas de referencia de componentes. Los módulos del negocio están creados como rutas vacías listas para implementarse.

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

## Acceso demo

La aplicación abre en el login si no hay una sesión activa.

- Ruta: `/login`
- Correo: cualquiera con formato válido (por ejemplo `recepcion@talitacumi.bo`)
- Contraseña: cualquiera
- “Recordarme” guarda la sesión en `localStorage`; sin marcarlo se guarda en `sessionStorage`.

Al ingresar se carga el layout principal y la página de Inicio. El cierre de sesión está en el menú de usuario del header.

> La autenticación es simulada. No hay validación contra backend todavía.

---

## Rutas

### Autenticación

| Ruta | Descripción |
|---|---|
| `/login` | Inicio de sesión |
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
| `/configuracion` | Configuración |
| `/organizacion/sucursales` | Organización y sucursales |

Los módulos sin implementar muestran un estado vacío reutilizable (`app-module-placeholder`). La ruta `/organizacion/sucursales` ya está implementada y consume la API real del backend.

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
│   ├── favicon.svg
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

**Inputs:**

| Input | Tipo | Default | Descripción |
|---|---|---|---|
| `variant` | `'default' \| 'light'` | `'default'` | Versión para fondos claros u oscuros |
| `compact` | `boolean` | `false` | Muestra solo el isotipo |
| `showTagline` | `boolean` | `true` | Muestra “Sistema de Gestión” |
| `size` | `'sm' \| 'md'` | `'md'` | Tamaño del logo |

```html
<app-brand-logo />
<app-brand-logo variant="light" [compact]="true" />
```

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

### 4. Autenticación simulada

**Archivos:**

- `src/app/core/services/auth.service.ts`
- `src/app/core/guards/auth.guard.ts`
- `src/app/core/guards/guest.guard.ts`

**API de `AuthService`:**

```ts
readonly user = this.auth.user;                  // Signal<SessionUser | null>
readonly isAuthenticated = this.auth.isAuthenticated;

await this.auth.login(email, remember);          // simula login
this.auth.logout();
```

`authGuard` protege el layout; `guestGuard` evita volver al login con sesión activa.

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

**Componentes reutilizados:** `app-page-breadcrumb`, `app-component-card`, `app-button`, `app-badge`, `app-alert`, `app-pagination`, `TABLE_IMPORTS`, `input[app-input]`, `app-select-field`, `textarea[app-textarea]`, `app-switch` y `ToastService`. Los diálogos son overlays Tailwind dentro de la página; no se agregó una primitiva compartida de modal.

**Permisos y alcance:** la API devuelve `can` (`create`, `update`, `update_contact`, `change_status`, `delete`) y la UI oculta las acciones no permitidas. No existe un guard de permisos en Angular; la seguridad real se valida en Laravel. Hasta que exista el módulo 02, Administración y Recepción reciben `503 MODULE_02_PENDING` por el contrato de alcance y la página muestra el error.

**Autenticación:** `AuthService` sigue siendo simulado. Los endpoints requieren `auth:sanctum`; sin login real, la página mostrará `401` hasta el módulo 02. No se usan datos mock.

**Estado:** RF-001 a RF-008, RF-011 y RF-013 implementados o parciales según el backend; RF-009, RF-010, RF-012 y el alcance por usuario quedan pendientes.

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
- Carrusel, mapas vectoriales, avatares, modales compartidos, tabs, drawer, popovers y demás componentes no solicitados. La página de sucursales usa overlays Tailwind propios.
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
- El login y la sesión son simulados; al conectar Laravel se debe reemplazar `AuthService` por una implementación real.
- La página `/organizacion/sucursales` ya consume la API real; mientras no exista login (módulo 02), la API responderá `401`. Administración y Recepción recibirán `503` por el contrato de alcance pendiente.
- No se agregaron datos mock ni dependencias nuevas para sucursales.
- `npm run build` se ejecutó correctamente después de implementar el módulo.
- No se realizó commit.
