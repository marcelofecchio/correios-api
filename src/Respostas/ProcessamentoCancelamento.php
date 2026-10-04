<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Resultado do cancelamento assíncrono (`GET /prepostagem/v1/prepostagens/canceladas/assincrona/{idRecibo}`).
 *
 * A especificação declara só `object`. Em homologação (04/10/2026) a resposta foi uma lista com um item por
 * pré-postagem (`ItemCancelamento`). Respostas em objeto ou texto também são aceitas; a resposta completa fica
 * em `respostaBruta()`.
 */
final readonly class ProcessamentoCancelamento extends Resposta
{
    /**
     * @param list<string>           $mensagens todas as mensagens, gerais e por item
     * @param list<ItemCancelamento> $itens
     * @param array<mixed>           $bruto
     */
    public function __construct(
        public ?string $situacao,
        public array $mensagens = [],
        public array $itens = [],
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    /**
     * Itens cuja mensagem traz um código de erro (ex.: `PPN-145-5`, pré-postagem já cancelada).
     *
     * @return list<ItemCancelamento>
     */
    public function itensComErro(): array
    {
        return array_values(array_filter($this->itens, static fn(ItemCancelamento $item): bool => $item->codigoErro !== null));
    }
}
