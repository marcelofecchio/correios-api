<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Dados reais da postagem (`GET /prepostagem/v1/prepostagens/postada`): valor, peso e medidas tarifados.
 *
 * Endpoint ainda não testado em homologação. As datas e as medidas ficam como texto, como vierem da API,
 * até que uma resposta real confirme o formato.
 */
final readonly class DadosPostagem extends Resposta
{
    /**
     * @param list<AdicionalPostagem> $adicionais
     * @param array<mixed>            $bruto
     */
    public function __construct(
        public ?string $codigoObjeto,
        public ?string $codigoServico = null,
        public ?string $nomeServico = null,
        public ?string $dataPostagem = null,
        public ?string $dataHoraAtendimento = null,
        public ?int $valorAtendimentoCentavos = null,
        public ?string $pesoObjeto = null,
        public ?string $pesoTarifado = null,
        public ?string $altura = null,
        public ?string $largura = null,
        public ?string $comprimento = null,
        public ?string $diametro = null,
        public ?string $valorDeclarado = null,
        public ?string $cepAgencia = null,
        public ?string $cepDestino = null,
        public array $adicionais = [],
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }
}
