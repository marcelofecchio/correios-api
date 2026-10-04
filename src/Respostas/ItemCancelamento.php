<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;

/**
 * Resultado do cancelamento assíncrono de uma pré-postagem.
 *
 * Observado em homologação (04/10/2026): `{"IdPrePostagem", "data", "idRecibo", "mensagem"}`. Quando o
 * cancelamento falha, `mensagem` traz o código (ex.: `PPN-145-5`).
 */
final readonly class ItemCancelamento
{
    public function __construct(
        public ?string $idPrePostagem,
        public ?string $codigoObjeto,
        public ?DateTimeImmutable $data,
        public ?string $mensagem,
        public ?string $codigoErro,
    ) {}
}
