<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Campo `formatoRotulo` da solicitação do rótulo oficial. */
enum FormatoRotulo: string
{
    case Etiqueta = 'ET';
    case Envelope = 'EV';
}
