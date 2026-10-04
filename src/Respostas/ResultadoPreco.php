<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

/**
 * Preço de um serviço (API Preço). Valores em centavos; os textos originais (`"19,92"`) ficam em `respostaBruta()`.
 */
final readonly class ResultadoPreco extends Resposta
{
    /**
     * @param list<PrecoServicoAdicional> $adicionais
     * @param array<mixed>                $bruto
     */
    public function __construct(
        public ?string $codigoServico,
        public ?int $precoFinalCentavos,
        public ?int $precoProdutoCentavos = null,
        public ?int $precoServicosAdicionaisCentavos = null,
        public ?int $precoBaseCentavos = null,
        public ?int $pesoCobradoGramas = null,
        public ?bool $cobradoPorPesoCubico = null,
        public array $adicionais = [],
        public ?string $nuRequisicao = null,
        public ?string $erro = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    public function temErro(): bool
    {
        return $this->erro !== null;
    }
}
