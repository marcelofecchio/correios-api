<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Formato do objeto (`codigoFormatoObjetoInformado` na Pré-postagem, `tpObjeto` no Preço). */
enum FormatoObjeto: string
{
    case Envelope = '1';
    case CaixaPacote = '2';
    case CilindroRolo = '3';
}
