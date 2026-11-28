<?php

return [
    /*
    | Máximo de filas por exportación CSV (08.01/RNF-010). Por encima responde 422.
    */
    'export_max_rows' => (int) env('PAGOS_EXPORT_MAX_ROWS', 10000),
];
