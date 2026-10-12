<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Resultado de uma tentativa de obter o rótulo oficial (`rotulo()->obter()`). */
enum SituacaoRotulo: string
{
    /** O PDF veio. */
    case Pronto = 'pronto';

    /** PPN-291: o rótulo ainda está sendo gerado. Baixe de novo com o **mesmo** recibo. */
    case EmGeracao = 'em_geracao';

    /** PPN-288: a pré-postagem ainda está PENDENTE. O recibo morreu: na próxima tentativa, peça **outro**. */
    case Pendente = 'pendente';
}
