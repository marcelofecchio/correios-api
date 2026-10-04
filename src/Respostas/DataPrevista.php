<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;

/** Data prevista de entrega (`GET /prazo/v1/data-prevista`). */
final readonly class DataPrevista extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public ?DateTimeImmutable $data,
        public ?string $mensagem = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
