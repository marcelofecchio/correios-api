<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;

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
        $ultimo = null;
        foreach ($this->eventos as $evento) {
            if ($ultimo === null || ($evento->dataHora !== null && ($ultimo->dataHora === null || $evento->dataHora > $ultimo->dataHora))) {
                $ultimo = $evento;
            }
        }

        return $ultimo;
    }
}
