<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Campo `tipoRotulo` da solicitação do rótulo oficial. */
enum TipoRotulo: string
{
    case Padrao = 'P';
    case Reduzido = 'R';
}
