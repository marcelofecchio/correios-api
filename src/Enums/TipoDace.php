<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Campo `tipoDace` da impressão da DACE. */
enum TipoDace: string
{
    /** Texto para impressora térmica (não é PDF). */
    case Termica = 'T';

    /** PDF em Base64. */
    case Resumida = 'R';

    /** PDF em Base64. */
    case Completa = 'C';

    public function ehPdf(): bool
    {
        return $this !== self::Termica;
    }
}
