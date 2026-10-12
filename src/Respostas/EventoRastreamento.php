<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\SituacaoRastreamento;
use MarceloFecchio\Correios\Rastreamento\CatalogoEventos;

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
     * Situação pelo `codigo` + `tipo` no catálogo oficial. Ver `SituacaoRastreamento` para o formato de `$mapa`.
     *
     * @param array<string, SituacaoRastreamento> $mapa eventos da aplicação, com prioridade sobre o catálogo
     */
    public function situacao(array $mapa = []): SituacaoRastreamento
    {
        return SituacaoRastreamento::doEvento($this->codigo, $this->tipo, $mapa);
    }

    /** `EST` ("Favor desconsiderar a informação anterior"): este evento anula o anterior. */
    public function anulaAnterior(): bool
    {
        return CatalogoEventos::anulaAnterior($this->codigo);
    }
}
