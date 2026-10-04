<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Resultado de um item de lote (Preço ou Prazo), correlacionado pelo `nuRequisicao`.
 *
 * @template T of ResultadoPreco|ResultadoPrazo
 */
final readonly class ItemLote
{
    /**
     * @param T $resultado
     */
    public function __construct(
        public ?string $nuRequisicao,
        public ResultadoPreco|ResultadoPrazo $resultado,
    ) {}

    public function sucesso(): bool
    {
        return !$this->resultado->temErro();
    }

    public function erro(): ?string
    {
        return $this->resultado->erro;
    }
}
