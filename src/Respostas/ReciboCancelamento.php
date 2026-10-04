<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Resposta do cancelamento assíncrono em lote (`DELETE /prepostagem/v1/prepostagens/assincrona`).
 *
 * A especificação declara só `object`; o recibo é procurado em qualquer campo cujo nome contenha "recibo".
 * Se não houver, `idRecibo` fica nulo e a resposta completa está em `respostaBruta()`.
 */
final readonly class ReciboCancelamento extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public ?string $idRecibo,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
