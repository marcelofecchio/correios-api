<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente;
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
 *
 * Pré-postagem síncrona: não há endpoint para consultar o status por id. Use `tentarObter()` a cada volta do job da
 * aplicação: o próprio download diz se a pré-postagem ainda está pendente.
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
        $idRecibo = $dados->texto('idRecibo') ?? throw $this->respostaSemCampo($resposta, 'idRecibo', 'solicitar rótulo');

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
        // Observado em homologação: o PPN-288 (pré-postagem pendente) chega com HTTP 200 e {"mensagem": "PPN-288: ..."}
        $base64 = $dados->texto('dados') ?? throw $this->respostaSemCampo($resposta, 'dados', 'baixar rótulo');

        return new ArquivoRotulo($dados->texto('nome') ?? sprintf('idRecibo_%s.pdf', $id), $base64, $dados->bruto());
    }

    /**
     * Solicita e baixa o rótulo numa tentativa. Devolve null se a pré-postagem ainda estiver PENDENTE (PPN-288).
     *
     * Cada tentativa usa um recibo novo, como a API exige; o código do objeto não muda entre as solicitações. Não há
     * espera nem repetição: chame de novo, depois de um intervalo, enquanto o retorno for null.
     *
     * ```php
     * // job da aplicação, reagendado com intervalo crescente (5, 10, 30, 60, 120 s...)
     * $arquivo = $correios->rotulo()->tentarObter($token, SolicitacaoRotulo::porIds([$id]));
     * if ($arquivo === null) {
     *     $this->release($proximoIntervalo);   // ainda pendente
     * }
     * ```
     */
    public function tentarObter(Token|string $token, SolicitacaoRotulo $solicitacao): ?ArquivoRotulo
    {
        try {
            return $this->baixar($token, $this->solicitar($token, $solicitacao));
        } catch (ErroPrePostagemPendente) {
            return null;
        }
    }
}
