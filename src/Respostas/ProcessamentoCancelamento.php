<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Resultado do cancelamento assíncrono (`GET /prepostagem/v1/prepostagens/canceladas/assincrona/{idRecibo}`).
 *
 * A especificação declara só `object`, sem campos, e o formato não foi registrado em homologação. A classe
 * expõe as mensagens encontradas e a resposta completa em `respostaBruta()`.
 */
final readonly class ProcessamentoCancelamento extends Resposta
{
    /**
     * @param list<string> $mensagens
     * @param array<mixed> $bruto
     */
    public function __construct(
        public ?string $situacao,
        public array $mensagens = [],
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
