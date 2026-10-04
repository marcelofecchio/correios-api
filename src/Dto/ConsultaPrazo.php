<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use DateTimeImmutable;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Cep;

/** Consulta de prazo nacional (`GET /prazo/v1/nacional/{coProduto}` ou um item do lote). */
final readonly class ConsultaPrazo
{
    public string $cepOrigem;
    public string $cepDestino;

    /**
     * @param string $servico apelido configurado ou código do serviço
     */
    public function __construct(
        public string $servico,
        string $cepOrigem,
        string $cepDestino,
        public ?DateTimeImmutable $dataEvento = null,
        public ?DateTimeImmutable $dataPostagem = null,
        public ?string $nuRequisicao = null,
    ) {
        if (trim($servico) === '') {
            throw ErroValidacao::campo('servico', 'informe o apelido ou o código do serviço.');
        }
        $this->cepOrigem = Cep::normalizar($cepOrigem, 'cepOrigem');
        $this->cepDestino = Cep::normalizar($cepDestino, 'cepDestino');
    }
}
