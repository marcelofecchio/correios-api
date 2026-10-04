<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Cep;

/**
 * Consulta de preço nacional (`GET /preco/v1/nacional/{coProduto}` ou um item do lote).
 *
 * O contrato e a DR são preenchidos pelo serviço (configuração ou token).
 */
final readonly class ConsultaPreco
{
    public string $cepOrigem;
    public string $cepDestino;

    /** @var list<string> */
    public array $servicosAdicionais;

    /**
     * @param string       $servico            apelido configurado ou código do serviço
     * @param list<string> $servicosAdicionais códigos de 3 dígitos (ex.: `['019']`)
     */
    public function __construct(
        public string $servico,
        string $cepOrigem,
        string $cepDestino,
        public Volume $volume,
        array $servicosAdicionais = [],
        public ?int $valorDeclaradoCentavos = null,
        public ?DateTimeImmutable $dataEvento = null,
        public ?string $nuRequisicao = null,
    ) {
        if (trim($servico) === '') {
            throw ErroValidacao::campo('servico', 'informe o apelido ou o código do serviço.');
        }
        $this->cepOrigem = Cep::normalizar($cepOrigem, 'cepOrigem');
        $this->cepDestino = Cep::normalizar($cepDestino, 'cepDestino');

        $codigos = [];
        foreach ($servicosAdicionais as $codigo) {
            $codigo = trim($codigo);
            if (preg_match('/^\d{3}$/', $codigo) !== 1) {
                throw ErroValidacao::campo('servicosAdicionais', sprintf('"%s" inválido: informe códigos com 3 dígitos.', $codigo));
            }
            $codigos[] = $codigo;
        }
        $this->servicosAdicionais = $codigos;

        if ($valorDeclaradoCentavos !== null && $valorDeclaradoCentavos <= 0) {
            throw ErroValidacao::campo('valorDeclarado', 'deve ser positivo.');
        }
    }

    public function formato(): FormatoObjeto
    {
        return $this->volume->formato;
    }
}
