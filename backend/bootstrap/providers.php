<?php

use App\Modules\AgendaTurnos\Providers\AgendaTurnosServiceProvider;
use App\Modules\AgendaTurnos\Providers\AsistenciaTurnosServiceProvider;
use App\Modules\AgendaTurnos\Providers\CambiosTurnosServiceProvider;
use App\Modules\AgendaTurnos\Providers\DiagnosticosServiceProvider;
use App\Modules\CatalogoServicios\Providers\CatalogoServiciosServiceProvider;
use App\Modules\InscripcionesAtenciones\Providers\InscripcionesAtencionesServiceProvider;
use App\Modules\Organizacion\Providers\OrganizacionServiceProvider;
use App\Modules\Pagos\Providers\PagosServiceProvider;
use App\Modules\Pacientes\Providers\PacientesServiceProvider;
use App\Modules\Profesionales\Providers\ProfesionalesServiceProvider;
use App\Modules\Seguridad\Providers\SeguridadServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    OrganizacionServiceProvider::class,
    SeguridadServiceProvider::class,
    ProfesionalesServiceProvider::class,
    PacientesServiceProvider::class,
    CatalogoServiciosServiceProvider::class,
    InscripcionesAtencionesServiceProvider::class,
    AgendaTurnosServiceProvider::class,
    CambiosTurnosServiceProvider::class,
    AsistenciaTurnosServiceProvider::class,
    DiagnosticosServiceProvider::class,
    PagosServiceProvider::class,
];
