<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use DateTimeImmutable;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Cep;

/** Parâmetros de `GET /prazo/v1/data-prevista`. */
final readonly class ConsultaDataPrevista
{
    public string $cepOrigem;
    public string $cepDestino;

    /**
     * @param ?string $servico apelido configurado ou código do serviço (opcional na API)
     */
    public function __construct(
        string $cepOrigem,
        string $cepDestino,
        public int $prazoDias,
        public DateTimeImmutable $dataPostagem,
        public ?string $servico = null,
        public ?bool $sabadoDiaUtil = null,
        public ?bool $domingoDiaUtil = null,
        public ?bool $encomendaUpu = null,
        public ?bool $postagemDh = null,
    ) {
        $this->cepOrigem = Cep::normalizar($cepOrigem, 'cepOrigem');
        $this->cepDestino = Cep::normalizar($cepDestino, 'cepDestino');
        if ($prazoDias < 0) {
            throw ErroValidacao::campo('prazo', 'não pode ser negativo.');
        }
    }
}
