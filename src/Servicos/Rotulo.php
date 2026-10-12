<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente;
use MarceloFecchio\Correios\Excecoes\ErroRotuloEmGeracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Respostas\ArquivoRotulo;
use MarceloFecchio\Correios\Respostas\ReciboRotulo;
use MarceloFecchio\Correios\Respostas\TentativaRotulo;
use MarceloFecchio\Correios\Token;

/**
 * Rótulo oficial dos Correios em PDF, em dois passos: `solicitar()` → `baixar()`.
 *
 * Roteiro: solicitar → baixar. O download pode responder (com HTTP 200):
 * - **PPN-291** (`ErroRotuloEmGeracao`): o rótulo ainda está sendo gerado; o recibo vale, baixe de novo com ele;
 * - **PPN-288** (`ErroPrePostagemPendente`): a pré-postagem estava PENDENTE; o recibo nunca mais funciona, solicite
 *   outro quando ela chegar ao status 2.
 *
 * `obter()` faz uma tentativa e diz qual dos casos aconteceu. O componente não espera nem repete chamadas: a
 * aplicação reagenda. O rótulo só pode ser emitido com as mesmas credenciais que criaram a pré-postagem.
 *
 * Pré-postagem síncrona: não há endpoint para consultar o status por id. O próprio download diz se ela ainda está
 * pendente.
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
     * Uma tentativa de obter o rótulo: solicita (se não houver recibo) e baixa.
     *
     * ```php
     * // job da aplicação, reagendado com intervalo crescente (5, 10, 30, 60, 120 s...)
     * $tentativa = $correios->rotulo()->obter($token, SolicitacaoRotulo::porIds([$id]), $envio->recibo_rotulo);
     * if (!$tentativa->pronto()) {
     *     $envio->recibo_rotulo = $tentativa->reciboParaProximaTentativa()?->idRecibo;   // PPN-291 guarda, PPN-288 limpa
     *     $this->release($proximoIntervalo);
     *     return;
     * }
     * $tentativa->arquivo->salvar($caminho);
     * ```
     *
     * @param ReciboRotulo|string|null $recibo recibo de uma tentativa anterior que voltou `EmGeracao`; null = solicitar um novo
     */
    public function obter(Token|string $token, SolicitacaoRotulo $solicitacao, ReciboRotulo|string|null $recibo = null): TentativaRotulo
    {
        $recibo = match (true) {
            $recibo instanceof ReciboRotulo => $recibo,
            $recibo === null || trim($recibo) === '' => $this->solicitar($token, $solicitacao),
            default => new ReciboRotulo(trim($recibo)),
        };

        try {
            return TentativaRotulo::comArquivo($this->baixar($token, $recibo), $recibo);
        } catch (ErroRotuloEmGeracao) {
            return TentativaRotulo::emGeracao($recibo);
        } catch (ErroPrePostagemPendente) {
            return TentativaRotulo::pendente();
        }
    }

    /**
     * Solicita e baixa o rótulo numa tentativa. Devolve null se ele ainda não estiver disponível (PPN-288 ou PPN-291).
     *
     * @deprecated desde 0.3.0: use `obter()`, que reaproveita o recibo no PPN-291 (rótulo em geração). Aqui cada
     *             tentativa pede um recibo novo, e o download logo depois da solicitação costuma cair no PPN-291.
     */
    public function tentarObter(Token|string $token, SolicitacaoRotulo $solicitacao): ?ArquivoRotulo
    {
        return $this->obter($token, $solicitacao)->arquivo;
    }
}
