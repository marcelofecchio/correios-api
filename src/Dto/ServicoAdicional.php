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

    /** Isento Doc Fiscal: postagem dispensada da DC-e pelo Ajuste SINIEF nº 35/2026 (comunicado de 27/10/2026). */
    public const string ISENTO_DOC_FISCAL = '082';

    public string $codigo;

    /**
     * @param ?string $tipo `tipoServicoAdicional`, enviado só quando informado (a API o preenche sozinha)
     * @param ?string $nome `nomeServicoAdicional`, idem
     */
    public function __construct(
        string $codigo,
        public ?int $valorDeclaradoCentavos = null,
        public ?string $tipo = null,
        public ?string $nome = null,
    ) {
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
     * Isento Doc Fiscal (082): postagem dispensada da DC-e e da NF-e pela legislação (documentos em papel, bens com
     * documento exigido por lei ou de órgão público, remessas internacionais com documentação aduaneira, amostras para
     * exame). Vai com o tipo e o nome, como no comunicado dos Correios. Os itens da declaração de conteúdo continuam
     * obrigatórios, e a dispensa é responsabilidade do remetente: o código não é uma validação fiscal dos Correios.
     */
    public static function isentoDocFiscal(): self
    {
        return new self(self::ISENTO_DOC_FISCAL, tipo: 'ISENTO_DOC_FISCAL', nome: 'Isento Doc Fiscal');
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

        if ($disponivel->categoria() === CategoriaServicoAdicional::IsentoDocFiscal) {
            return self::isentoDocFiscal();
        }

        return new self($disponivel->codigo, $valorDeclaradoCentavos);
    }

    public function ehValorDeclarado(): bool
    {
        return $this->valorDeclaradoCentavos !== null;
    }

    public function ehIsentoDocFiscal(): bool
    {
        return $this->codigo === self::ISENTO_DOC_FISCAL;
    }
}
