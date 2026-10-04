<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;

/**
 * Evento de rastreamento. A combinação `codigo` + `tipo` identifica o evento; use `descricao` para exibir.
 */
final readonly class EventoRastreamento extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public ?string $codigo,
        public ?string $tipo,
        public ?string $descricao,
        public ?DateTimeImmutable $dataHora,
        public ?string $detalhe = null,
        public ?string $unidade = null,
        public ?string $cidade = null,
        public ?string $uf = null,
        public ?string $unidadeDestino = null,
        public ?string $cidadeDestino = null,
        public ?string $ufDestino = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
