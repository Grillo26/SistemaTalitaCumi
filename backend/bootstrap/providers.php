<?php

use App\Modules\CatalogoServicios\Providers\CatalogoServiciosServiceProvider;
use App\Modules\Organizacion\Providers\OrganizacionServiceProvider;
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
];
