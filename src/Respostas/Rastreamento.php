<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\SituacaoRastreamento;

/**
 * Rastreamento de um objeto (API Rastro). Objeto não encontrado vem com `mensagem` e sem eventos.
 */
final readonly class Rastreamento extends Resposta
{
    /**
     * @param list<EventoRastreamento> $eventos na ordem devolvida pela API
     * @param array<mixed>             $bruto
     */
    public function __construct(
        public string $codigoObjeto,
        public array $eventos = [],
        public ?string $mensagem = null,
        public ?string $tipoPostal = null,
        public ?DateTimeImmutable $dataPrevista = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    public function encontrado(): bool
    {
        return $this->eventos !== [];
    }

    /** Evento mais recente pela data (a API não garante a ordem da lista). */
    public function ultimoEvento(): ?EventoRastreamento
    {
        return $this->eventosRecentesPrimeiro()[0] ?? null;
    }

    /**
     * Evento mais recente que ainda vale: cada `EST` ("Favor desconsiderar a informação anterior") anula a si mesmo e
     * o evento imediatamente anterior.
     */
    public function ultimoEventoValido(): ?EventoRastreamento
    {
        $anular = 0;
        foreach ($this->eventosRecentesPrimeiro() as $evento) {
            if ($evento->anulaAnterior()) {
                $anular++;
            } elseif ($anular > 0) {
                $anular--;
            } else {
                return $evento;
            }
        }

        return null;
    }

    /**
     * Situação do objeto pelo evento mais recente que vale (`Desconhecida` se não houver).
     *
     * @param array<string, SituacaoRastreamento> $mapa eventos da aplicação, com prioridade sobre o catálogo
     */
    public function situacao(array $mapa = []): SituacaoRastreamento
    {
        return $this->ultimoEventoValido()?->situacao($mapa) ?? SituacaoRastreamento::Desconhecida;
    }

    /**
     * Eventos do mais recente para o mais antigo. Sem data, vão para o fim; empate mantém a ordem da API.
     *
     * @return list<EventoRastreamento>
     */
    public function eventosRecentesPrimeiro(): array
    {
        $eventos = $this->eventos;
        usort($eventos, static fn(EventoRastreamento $a, EventoRastreamento $b): int => match (true) {
            $a->dataHora === null && $b->dataHora === null => 0,
            $a->dataHora === null => 1,
            $b->dataHora === null => -1,
            default => $b->dataHora <=> $a->dataHora,
        });

        return $eventos;
    }
}
