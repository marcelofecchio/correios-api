<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/** Recibo da solicitação do rótulo oficial. Use `idRecibo` no download. */
final readonly class ReciboRotulo extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public string $idRecibo,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
