<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Resposta do cancelamento síncrono (`ResponseCancelamento`).
 *
 * Os valores possíveis de `resultadoCancelamento` não estão documentados nem foram observados em
 * homologação: ficam como texto. Confira `resultado` e `mensagem` antes de dar a pré-postagem por cancelada.
 */
final readonly class ResultadoCancelamento extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public ?string $resultado,
        public ?string $mensagem,
        public ?string $idRecibo,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
