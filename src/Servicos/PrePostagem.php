<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\Identificadores;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Mapeadores\PrePostagemMapeador;
use MarceloFecchio\Correios\Respostas\DadosPostagem;
use MarceloFecchio\Correios\Respostas\PrePostagemCriada;
use MarceloFecchio\Correios\Respostas\ProcessamentoAssincrono;
use MarceloFecchio\Correios\Respostas\ProcessamentoCancelamento;
use MarceloFecchio\Correios\Respostas\ReciboCancelamento;
use MarceloFecchio\Correios\Respostas\ResultadoCancelamento;
use MarceloFecchio\Correios\Token;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

/**
 * API Pré-postagem: criação (síncrona e assíncrona), cancelamento e dados da postagem.
 *
 * Não há endpoint documentado para consultar uma pré-postagem síncrona por id: o status vem da resposta da
 * criação. O componente não faz espera nem repetição: a aplicação decide quando consultar de novo.
 */
final class PrePostagem extends Servico
{
    /** `POST /prepostagem/v1/prepostagens`. */
    public function criar(Token|string $token, NovaPrePostagem $prePostagem): PrePostagemCriada
    {
        $resposta = $this->chamar('POST', '/prepostagem/v1/prepostagens', 'criar pré-postagem', $token, json: $this->corpo($prePostagem), sucesso: [200, 201]);

        return PrePostagemMapeador::prePostagem($this->leitor($resposta));
    }

    /**
     * `POST /prepostagem/v1/prepostagens/assincrona`. Devolve o recibo, usado em `consultarAssincrona()`.
     *
     * O recibo é aceito como texto puro, string JSON ou objeto com um campo de recibo.
     */
    public function criarAssincrona(Token|string $token, NovaPrePostagem $prePostagem): string
    {
        $resposta = $this->chamar('POST', '/prepostagem/v1/prepostagens/assincrona', 'criar pré-postagem assíncrona', $token, json: $this->corpo($prePostagem), sucesso: [200, 201, 202]);

        $recibo = PrePostagemMapeador::recibo($resposta->json(), $resposta->corpo);
        if ($recibo === null) {
            throw new ErroApi('A API não devolveu o recibo da pré-postagem assíncrona.', $resposta->status, [], null, null, $resposta->corpo);
        }

        return $recibo;
    }

    /** `GET /prepostagem/v1/prepostagens/assincrona/{recibo}`. */
    public function consultarAssincrona(Token|string $token, string $recibo): ProcessamentoAssincrono
    {
        $resposta = $this->chamar('GET', '/prepostagem/v1/prepostagens/assincrona/' . $this->identificador($recibo, 'recibo'), 'consultar pré-postagem assíncrona', $token);

        return PrePostagemMapeador::processamento($this->leitor($resposta));
    }

    /** `DELETE /prepostagem/v1/prepostagens/{id}`. Irreversível; só para pré-postagens não postadas. */
    public function cancelar(Token|string $token, string $idPrePostagem): ResultadoCancelamento
    {
        $resposta = $this->chamar('DELETE', '/prepostagem/v1/prepostagens/' . $this->identificador($idPrePostagem, 'id'), 'cancelar pré-postagem', $token);

        return PrePostagemMapeador::cancelamento($this->leitor($resposta));
    }

    /** `DELETE /prepostagem/v1/prepostagens/objeto/{codigoObjeto}`. Irreversível. */
    public function cancelarPorCodigoObjeto(Token|string $token, CodigoRastreamento|string $codigoObjeto): ResultadoCancelamento
    {
        $codigo = Identificadores::codigos([$codigoObjeto])[0] ?? throw ErroValidacao::campo('codigoObjeto', 'informe o código do objeto.');
        $resposta = $this->chamar('DELETE', '/prepostagem/v1/prepostagens/objeto/' . $codigo, 'cancelar pré-postagem pelo código do objeto', $token);

        return PrePostagemMapeador::cancelamento($this->leitor($resposta));
    }

    /**
     * `DELETE /prepostagem/v1/prepostagens/assincrona` com corpo JSON (`idsPrePostagens` ou `codigosObjetos`).
     * Confirme o resultado com `consultarCancelamentoLote()`.
     *
     * @param list<string>                    $idsPrePostagens
     * @param list<string|CodigoRastreamento> $codigosObjetos
     */
    public function cancelarLote(Token|string $token, array $idsPrePostagens = [], array $codigosObjetos = []): ReciboCancelamento
    {
        $ids = Identificadores::ids($idsPrePostagens);
        $codigos = Identificadores::codigos($codigosObjetos);
        if ($ids === [] && $codigos === []) {
            throw ErroValidacao::campo('idsPrePostagens', 'informe ao menos um id de pré-postagem ou código de objeto.');
        }

        $corpo = array_filter(['idsPrePostagens' => $ids, 'codigosObjetos' => $codigos], static fn(array $lista): bool => $lista !== []);
        $resposta = $this->chamar('DELETE', '/prepostagem/v1/prepostagens/assincrona', 'cancelar pré-postagens em lote', $token, json: $corpo, sucesso: [200, 202]);

        return PrePostagemMapeador::reciboCancelamento($resposta->json(), $resposta->corpo);
    }

    /** `GET /prepostagem/v1/prepostagens/canceladas/assincrona/{idRecibo}`. */
    public function consultarCancelamentoLote(Token|string $token, string $idRecibo): ProcessamentoCancelamento
    {
        $resposta = $this->chamar('GET', '/prepostagem/v1/prepostagens/canceladas/assincrona/' . $this->identificador($idRecibo, 'idRecibo'), 'consultar cancelamento em lote', $token);

        return PrePostagemMapeador::processamentoCancelamento($resposta->json(), $resposta->corpo);
    }

    /** `GET /prepostagem/v1/prepostagens/postada?codigoObjeto=`: dados tarifados, só depois da postagem. */
    public function dadosPostagem(Token|string $token, CodigoRastreamento|string $codigoObjeto): DadosPostagem
    {
        $codigo = Identificadores::codigos([$codigoObjeto])[0] ?? throw ErroValidacao::campo('codigoObjeto', 'informe o código do objeto.');
        $resposta = $this->chamar('GET', '/prepostagem/v1/prepostagens/postada', 'consultar dados da postagem', $token, ['codigoObjeto' => $codigo]);

        return PrePostagemMapeador::dadosPostagem($this->leitor($resposta));
    }

    /** @return array<string, mixed> */
    private function corpo(NovaPrePostagem $prePostagem): array
    {
        $remetente = $prePostagem->remetente ?? $this->configuracao->remetentePadrao
            ?? throw ErroValidacao::campo('remetente', 'informe o remetente ou configure o remetente padrão.');

        return PrePostagemMapeador::paraApi($prePostagem, $remetente, $this->configuracao->resolverServico($prePostagem->servico));
    }

    private function identificador(string $valor, string $campo): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            throw ErroValidacao::campo($campo, 'não pode ser vazio.');
        }

        return self::segmento($valor);
    }
}
