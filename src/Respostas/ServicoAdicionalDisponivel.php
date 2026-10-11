<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\CategoriaServicoAdicional;

/**
 * Serviço adicional aceito por um serviço (`GET /preco/v1/servicos-adicionais/{coProduto}`).
 *
 * `sgIndicador`: `O` obrigatório, `P` opcional. A obrigatoriedade pode mudar com a vigência. `categoria()` diz se o
 * adicional é AR, mão própria, valor declarado ou artigos perigosos.
 */
final readonly class ServicoAdicionalDisponivel extends Resposta
{
    /**
     * @param list<string> $requeridos   códigos de adicionais exigidos junto
     * @param list<string> $excludentes  códigos de adicionais que não podem ser usados junto
     * @param array<mixed> $bruto
     */
    public function __construct(
        public string $codigo,
        public ?string $nome = null,
        public ?string $sigla = null,
        public ?string $tipo = null,
        public ?string $codigoIndicador = null,
        public ?string $siglaIndicador = null,
        public ?string $nomeIndicador = null,
        public ?int $precoCentavos = null,
        public ?int $valorMinimoDeclaradoCentavos = null,
        public ?int $valorMaximoDeclaradoCentavos = null,
        public ?string $mensagem = null,
        public array $requeridos = [],
        public array $excludentes = [],
        public ?DateTimeImmutable $inicioVigencia = null,
        public ?DateTimeImmutable $fimVigencia = null,
        array $bruto = [],
    ) {
        parent::__construct($bruto);
    }

    /** Verdadeiro se `sgIndicador` for `O` (obrigatório). */
    public function obrigatorio(): bool
    {
        return strtoupper((string) $this->siglaIndicador) === 'O';
    }

    /** Verdadeiro se `sgIndicador` for `P` (opcional). */
    public function opcional(): bool
    {
        return strtoupper((string) $this->siglaIndicador) === 'P';
    }

    /** AR, mão própria, valor declarado, artigos perigosos ou null (pela sigla e pelo nome). */
    public function categoria(): ?CategoriaServicoAdicional
    {
        return CategoriaServicoAdicional::de($this);
    }
}
