<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\Identificadores;
use MarceloFecchio\Correios\Dto\SolicitacaoDace;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Respostas\Dace;
use MarceloFecchio\Correios\Token;
use MarceloFecchio\Correios\Utilitarios\ChaveDocumentoFiscal;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/** Emissão de DCe: impressão da DACE e inclusão da chave do documento fiscal. */
final class Dce extends Servico
{
    /**
     * `POST /prepostagem/v1/prepostagens/dce/dace/impressao`. Só atende pré-postagens criadas com `emiteDCe`.
     * Tipo `T` devolve texto para impressora térmica; `R` e `C` devolvem PDF em Base64.
     */
    public function imprimirDace(Token|string $token, SolicitacaoDace $solicitacao): Dace
    {
        $corpo = array_filter([
            'idsPrePostagens' => $solicitacao->idsPrePostagens,
            'codigosObjetos' => $solicitacao->codigosObjetos,
            'tipoDace' => $solicitacao->tipoDace->value,
        ], static fn(string|array $valor): bool => $valor !== []);

        $resposta = $this->chamar('POST', '/prepostagem/v1/prepostagens/dce/dace/impressao', 'imprimir DACE', $token, json: $corpo);
        $dados = $this->leitor($resposta);
        $conteudo = $dados->texto('dados') ?? throw $this->respostaSemCampo($resposta, 'dados', 'imprimir DACE');

        return new Dace($solicitacao->tipoDace, $dados->textos('objetos'), $conteudo, $dados->bruto());
    }

    /**
     * `PATCH /prepostagem/v1/prepostagens/dce`: informa a chave da NF-e ou da DCe depois da criação.
     * A API não define corpo de resposta: sucesso é o HTTP 200.
     */
    public function atualizarDocumentoFiscal(
        Token|string $token,
        CodigoRastreamento|string $codigoObjeto,
        ChaveDocumentoFiscal|string $chave,
        ?string $numeroNotaFiscal = null,
    ): void {
        $codigo = Identificadores::codigos([$codigoObjeto])[0] ?? throw ErroValidacao::campo('codigoObjeto', 'informe o código do objeto.');
        $chave = $chave instanceof ChaveDocumentoFiscal ? $chave : new ChaveDocumentoFiscal($chave);

        $numero = null;
        if ($numeroNotaFiscal !== null && trim($numeroNotaFiscal) !== '') {
            $numero = Texto::digitos($numeroNotaFiscal);
            if ($numero === '' || strlen($numero) > 12 || $numero !== trim($numeroNotaFiscal)) {
                throw ErroValidacao::campo('numeroNotaFiscal', 'deve ter até 12 dígitos numéricos.');
            }
        }

        $this->chamar('PATCH', '/prepostagem/v1/prepostagens/dce', 'atualizar documento fiscal', $token, json: array_filter([
            'codigoObjeto' => $codigo,
            'chaveNFe' => $chave->valor,
            'numeroNotaFiscal' => $numero,
        ], static fn(?string $valor): bool => $valor !== null), sucesso: [200, 204]);
    }
}
