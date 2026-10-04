<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;

/** Prazo de entrega de um serviço (API Prazo). */
final readonly class ResultadoPrazo extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public ?string $codigoServico,
        public ?int $prazoDias,
        public ?DateTimeImmutable $dataMaxima = null,
        public ?bool $entregaDomiciliar = null,
        public ?bool $entregaSabado = null,
        public ?bool $entregaDomingo = null,
        public ?string $mensagem = null,
        public ?string $nuRequisicao = null,
        public ?string $erro = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    public function temErro(): bool
    {
        return $this->erro !== null;
    }
}
