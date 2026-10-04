<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/** Mensagens de falha de um item da pré-postagem assíncrona (`sequencias[]`). */
final readonly class FalhaProcessamento
{
    /** @param list<string> $mensagens */
    public function __construct(
        public ?string $ordem,
        public array $mensagens,
    ) {}
}
