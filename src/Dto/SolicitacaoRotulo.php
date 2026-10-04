<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Enums\FormatoRotulo;
use MarceloFecchio\Correios\Enums\LayoutImpressao;
use MarceloFecchio\Correios\Enums\TipoRotulo;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

/**
 * Solicitação do rótulo oficial em PDF (`POST /prepostagem/v1/prepostagens/rotulo/assincrono/pdf`).
 *
 * Informe os ids das pré-postagens **ou** os códigos dos objetos. Ids e códigos vazios são descartados.
 * Só solicite o rótulo com a pré-postagem em `statusAtual = 2` (PREPOSTADO).
 */
final readonly class SolicitacaoRotulo
{
    /** @var list<string> */
    public array $idsPrePostagem;

    /** @var list<string> */
    public array $codigosObjeto;

    /**
     * @param list<string> $idsPrePostagem
     * @param list<string|CodigoRastreamento> $codigosObjeto
     */
    public function __construct(
        array $idsPrePostagem = [],
        array $codigosObjeto = [],
        public TipoRotulo $tipoRotulo = TipoRotulo::Padrao,
        public FormatoRotulo $formatoRotulo = FormatoRotulo::Etiqueta,
        public LayoutImpressao $layoutImpressao = LayoutImpressao::Padrao,
        public bool $imprimeRemetente = true,
    ) {
        $this->idsPrePostagem = Identificadores::ids($idsPrePostagem);
        $this->codigosObjeto = Identificadores::codigos($codigosObjeto);

        if ($this->idsPrePostagem === [] && $this->codigosObjeto === []) {
            throw ErroValidacao::campo('idsPrePostagem', 'informe ao menos um id de pré-postagem ou código de objeto.');
        }
        if ($this->idsPrePostagem !== [] && $this->codigosObjeto !== []) {
            throw ErroValidacao::campo('idsPrePostagem', 'informe ids de pré-postagem ou códigos de objeto, não os dois.');
        }
    }

    /** @param list<string> $ids */
    public static function porIds(array $ids, TipoRotulo $tipoRotulo = TipoRotulo::Padrao, LayoutImpressao $layoutImpressao = LayoutImpressao::Padrao): self
    {
        return new self(idsPrePostagem: $ids, tipoRotulo: $tipoRotulo, layoutImpressao: $layoutImpressao);
    }

    /** @param list<string|CodigoRastreamento> $codigos */
    public static function porCodigos(array $codigos, TipoRotulo $tipoRotulo = TipoRotulo::Padrao, LayoutImpressao $layoutImpressao = LayoutImpressao::Padrao): self
    {
        return new self(codigosObjeto: $codigos, tipoRotulo: $tipoRotulo, layoutImpressao: $layoutImpressao);
    }
}
