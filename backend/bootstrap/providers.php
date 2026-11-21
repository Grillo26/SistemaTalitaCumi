<?php

use App\Modules\Organizacion\Providers\OrganizacionServiceProvider;
use App\Modules\Profesionales\Providers\ProfesionalesServiceProvider;
use App\Modules\Seguridad\Providers\SeguridadServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    OrganizacionServiceProvider::class,
    SeguridadServiceProvider::class,
    ProfesionalesServiceProvider::class,
];
