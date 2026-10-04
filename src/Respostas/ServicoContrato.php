<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/** Serviço habilitado no cartão de postagem (API Meu Contrato). */
final readonly class ServicoContrato extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public string $codigo,
        public ?string $descricao = null,
        public ?string $codigoSegmento = null,
        public ?string $descricaoSegmento = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
