<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Respostas\ArquivoRotulo;
use MarceloFecchio\Correios\Respostas\ReciboRotulo;
use MarceloFecchio\Correios\Token;

/**
 * Rótulo oficial dos Correios em PDF, em dois passos: `solicitar()` → `baixar()`.
 *
 * Roteiro: confirme `statusAtual = 2` → solicitar → baixar. Se o download lançar `ErroPrePostagemPendente`
 * (PPN-288), o recibo ficou inválido: aguarde o status 2 e **solicite de novo**. O componente não espera nem
 * repete chamadas. O rótulo só pode ser emitido com as mesmas credenciais que criaram a pré-postagem.
 */
final class Rotulo extends Servico
{
    /** `POST /prepostagem/v1/prepostagens/rotulo/assincrono/pdf` → `{"idRecibo": "..."}`. */
    public function solicitar(Token|string $token, SolicitacaoRotulo $solicitacao): ReciboRotulo
    {
        $corpo = array_filter([
            'idsPrePostagem' => $solicitacao->idsPrePostagem,
            'codigosObjeto' => $solicitacao->codigosObjeto,
            'tipoRotulo' => $solicitacao->tipoRotulo->value,
            'formatoRotulo' => $solicitacao->formatoRotulo->value,
            'imprimeRemetente' => $solicitacao->imprimeRemetente ? 'S' : 'N',
            'layoutImpressao' => $solicitacao->layoutImpressao->value,
        ], static fn(string|array $valor): bool => $valor !== []);

        $resposta = $this->chamar('POST', '/prepostagem/v1/prepostagens/rotulo/assincrono/pdf', 'solicitar rótulo', $token, json: $corpo, sucesso: [200, 201, 202]);
        $dados = $this->leitor($resposta);
        $idRecibo = $dados->texto('idRecibo');
        if ($idRecibo === null) {
            throw new ErroApi('A API não devolveu o "idRecibo" da solicitação do rótulo.', $resposta->status, [], null, null, $dados->bruto() ?: $resposta->corpo);
        }

        return new ReciboRotulo($idRecibo, $dados->bruto());
    }

    /**
     * `GET /prepostagem/v1/prepostagens/rotulo/download/assincrono/{idRecibo}` → `{"dados": "<PDF Base64>", "nome": "..."}`.
     *
     * @throws \MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente se a pré-postagem estava PENDENTE quando o rótulo foi pedido
     */
    public function baixar(Token|string $token, ReciboRotulo|string $idRecibo): ArquivoRotulo
    {
        $id = trim($idRecibo instanceof ReciboRotulo ? $idRecibo->idRecibo : $idRecibo);
        if ($id === '') {
            throw ErroValidacao::campo('idRecibo', 'não pode ser vazio.');
        }

        $resposta = $this->chamar('GET', '/prepostagem/v1/prepostagens/rotulo/download/assincrono/' . self::segmento($id), 'baixar rótulo', $token);
        $dados = $this->leitor($resposta);
        $base64 = $dados->texto('dados');
        if ($base64 === null) {
            throw new ErroApi('A resposta do download do rótulo não trouxe o campo "dados".', $resposta->status, [], null, null, $dados->bruto() ?: $resposta->corpo);
        }

        return new ArquivoRotulo($dados->texto('nome') ?? sprintf('idRecibo_%s.pdf', $id), $base64, $dados->bruto());
    }
}
