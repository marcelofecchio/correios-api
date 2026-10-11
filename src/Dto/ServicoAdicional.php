<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Enums\CategoriaServicoAdicional;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Respostas\ServicoAdicionalDisponivel;

/**
 * Serviço adicional da pré-postagem (`listaServicoAdicional`).
 *
 * O código do valor declarado **muda por serviço** (`019` no SEDEX e `064` no PAC, nos testes) e por
 * isso é sempre informado pela aplicação. Consulte os adicionais aceitos em `$correios->preco()->servicosAdicionais()`
 * e localize o de cada categoria com `CategoriaServicoAdicional` (ver `de()`).
 */
final readonly class ServicoAdicional
{
    /** AR Convencional (arquivo 06: "O objeto consultado não possui o serviço AR Convencional 001 associado"). */
    public const string AVISO_RECEBIMENTO = '001';

    public string $codigo;

    public function __construct(string $codigo, public ?int $valorDeclaradoCentavos = null)
    {
        $codigo = trim($codigo);
        if (preg_match('/^\d{3}$/', $codigo) !== 1) {
            throw ErroValidacao::campo('servicoAdicional.codigo', sprintf('"%s" inválido: informe o código com 3 dígitos.', $codigo));
        }
        if ($valorDeclaradoCentavos !== null && $valorDeclaradoCentavos <= 0) {
            throw ErroValidacao::campo('servicoAdicional.valorDeclarado', 'deve ser positivo.');
        }
        $this->codigo = $codigo;
    }

    /** Aviso de recebimento (AR Convencional, código 001). Obrigatório para emitir o AR. */
    public static function avisoRecebimento(): self
    {
        return new self(self::AVISO_RECEBIMENTO);
    }

    /**
     * Valor declarado. O código depende do serviço (ex.: `019` no SEDEX, `064` no PAC).
     *
     * @param int $valorCentavos valor declarado em centavos
     */
    public static function valorDeclarado(string $codigo, int $valorCentavos): self
    {
        return new self($codigo, $valorCentavos);
    }

    /**
     * Mão própria. O código não está na documentação: localize-o com
     * `CategoriaServicoAdicional::MaoPropria->localizar($correios->preco()->servicosAdicionais($token, $servico))`.
     */
    public static function maoPropria(string $codigo): self
    {
        return new self($codigo);
    }

    /**
     * A partir de um adicional devolvido por `preco()->servicosAdicionais()`.
     *
     * ```php
     * $vd = CategoriaServicoAdicional::ValorDeclarado->localizar($adicionais);
     * $adicional = ServicoAdicional::de($vd, 25000);
     * ```
     *
     * @param ?int $valorDeclaradoCentavos obrigatório se o adicional for de valor declarado
     */
    public static function de(ServicoAdicionalDisponivel $disponivel, ?int $valorDeclaradoCentavos = null): self
    {
        if ($valorDeclaradoCentavos === null && $disponivel->categoria() === CategoriaServicoAdicional::ValorDeclarado) {
            throw ErroValidacao::campo('servicoAdicional.valorDeclarado', sprintf('informe o valor declarado do adicional %s.', $disponivel->codigo));
        }

        return new self($disponivel->codigo, $valorDeclaradoCentavos);
    }

    public function ehValorDeclarado(): bool
    {
        return $this->valorDeclaradoCentavos !== null;
    }
}
