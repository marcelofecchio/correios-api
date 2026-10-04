<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Campo `modalidadePagamento` da pré-postagem. */
enum ModalidadePagamento: string
{
    case AVista = '1';
    case AFaturar = '2';
    case AVistaEAFaturar = '3';
    case PrestacaoDeContas = '4';
}
