<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/** Base das respostas tipadas: guarda o JSON original para depuração. */
abstract readonly class Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(protected array $bruto) {}

    /**
     * JSON original da API.
     *
     * @return array<mixed>
     */
    public function respostaBruta(): array
    {
        return $this->bruto;
    }
}
