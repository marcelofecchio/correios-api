<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Enums\TipoDace;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

/**
 * Impressão da DACE (`POST /prepostagem/v1/prepostagens/dce/dace/impressao`).
 *
 * Só atende pré-postagens criadas com `emiteDCe`. Informe os ids **ou** os códigos dos objetos.
 */
final readonly class SolicitacaoDace
{
    /** @var list<string> */
    public array $idsPrePostagens;

    /** @var list<string> */
    public array $codigosObjetos;

    /**
     * @param list<string> $idsPrePostagens
     * @param list<string|CodigoRastreamento> $codigosObjetos
     */
    public function __construct(
        public TipoDace $tipoDace = TipoDace::Completa,
        array $idsPrePostagens = [],
        array $codigosObjetos = [],
    ) {
        $this->idsPrePostagens = Identificadores::ids($idsPrePostagens);
        $this->codigosObjetos = Identificadores::codigos($codigosObjetos);

        if ($this->idsPrePostagens === [] && $this->codigosObjetos === []) {
            throw ErroValidacao::campo('idsPrePostagens', 'informe ao menos um id de pré-postagem ou código de objeto.');
        }
        if ($this->idsPrePostagens !== [] && $this->codigosObjetos !== []) {
            throw ErroValidacao::campo('idsPrePostagens', 'informe ids de pré-postagem ou códigos de objeto, não os dois.');
        }
    }
}
