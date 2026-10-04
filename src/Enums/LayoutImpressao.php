<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/**
 * Campo `layoutImpressao` da solicitação do rótulo oficial.
 *
 * Só `PADRAO` (folha A4) foi observado em homologação; os demais ainda não foram testados.
 */
enum LayoutImpressao: string
{
    case Padrao = 'PADRAO';
    case Linear100x150 = 'LINEAR_100_150';
    case Linear100x80 = 'LINEAR_100_80';
    case LinearA4 = 'LINEAR_A4';
    case LinearA = 'LINEAR_A';
}
