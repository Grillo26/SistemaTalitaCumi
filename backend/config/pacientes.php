<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exportación del listado de pacientes (04.04/RF-013)
    |--------------------------------------------------------------------------
    |
    | Límite de filas para el CSV. El PDF queda pendiente de infraestructura.
    |
    */

    'export_max_rows' => (int) env('PACIENTES_EXPORT_MAX_ROWS', 10000),

];
