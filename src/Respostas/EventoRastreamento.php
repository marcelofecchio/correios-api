<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\SituacaoRastreamento;

/**
 * Evento de rastreamento. A combinação `codigo` + `tipo` identifica o evento; use `descricao` para exibir e
 * `situacao()` para regras (entregue, saiu para entrega...).
 */
final readonly class EventoRastreamento extends Resposta
{
    /** @param array<mixed> $bruto */
    public function __construct(
        public ?string $codigo,
        public ?string $tipo,
        public ?string $descricao,
        public ?DateTimeImmutable $dataHora,
        public ?string $detalhe = null,
        public ?string $unidade = null,
        public ?string $cidade = null,
        public ?string $uf = null,
        public ?string $unidadeDestino = null,
        public ?string $cidadeDestino = null,
        public ?string $ufDestino = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    /**
     * Situação pelo `codigo` + `tipo`. Ver `SituacaoRastreamento` para a tabela padrão e o formato de `$mapa`.
     *
     * @param array<string, SituacaoRastreamento> $mapa eventos da aplicação, com prioridade sobre a tabela padrão
     */
    public function situacao(array $mapa = []): SituacaoRastreamento
    {
        return SituacaoRastreamento::doEvento($this->codigo, $this->tipo, $mapa);
    }
}
