<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/** Serviço adicional cobrado na postagem (`listaServicoAdicional` de `DadosPostagem`). */
final readonly class AdicionalPostagem
{
    public function __construct(
        public ?string $codigo,
        public ?int $valorCentavos,
        public ?string $tipoComposicao = null,
    ) {}
}
