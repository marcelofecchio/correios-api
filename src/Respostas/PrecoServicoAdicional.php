<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/** Preço de um serviço adicional dentro do `ResultadoPreco`. */
final readonly class PrecoServicoAdicional
{
    public function __construct(
        public string $codigo,
        public ?int $precoCentavos,
        public ?string $tipo = null,
    ) {}
}
