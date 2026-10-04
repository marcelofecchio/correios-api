<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Excecoes\CorreiosException;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Http\ClienteHttp;
use MarceloFecchio\Correios\Http\Requisicao;
use MarceloFecchio\Correios\Http\RespostaHttp;
use MarceloFecchio\Correios\Http\TratadorErros;
use MarceloFecchio\Correios\Mapeadores\Leitor;
use MarceloFecchio\Correios\Token;

/** Base dos serviços que chamam a API dos Correios. */
abstract class Servico
{
    public function __construct(
        protected readonly Configuracao $configuracao,
        protected readonly ClienteHttp $http,
    ) {}

    /** Cabeçalho `Authorization: Bearer` a partir do token recebido por parâmetro. */
    protected function bearer(Token|string $token): string
    {
        $valor = trim((string) $token);
        if ($valor === '') {
            throw ErroValidacao::campo('token', 'informe o token gerado por $correios->token()->gerar().');
        }

        return 'Bearer ' . $valor;
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     * @param array<mixed>|null                       $json
     * @param list<int>                               $sucesso
     */
    protected function chamar(
        string $metodo,
        string $caminho,
        string $operacao,
        Token|string $token,
        array $query = [],
        ?array $json = null,
        array $sucesso = [200],
    ): RespostaHttp {
        return $this->http->executar(new Requisicao(
            metodo: $metodo,
            caminho: $caminho,
            operacao: $operacao,
            autorizacao: $this->bearer($token),
            query: $query,
            json: $json,
            sucesso: $sucesso,
        ));
    }

    protected function leitor(RespostaHttp $resposta): Leitor
    {
        return Leitor::de($resposta->json());
    }

    /**
     * Resposta de sucesso sem o campo esperado. Se o corpo trouxer mensagens de erro (a API devolve, por exemplo,
     * o PPN-288 com HTTP 200 no download do rótulo), lança a exceção correspondente; senão, um `ErroApi` genérico.
     */
    protected function respostaSemCampo(RespostaHttp $resposta, string $campo, string $operacao): CorreiosException
    {
        $json = $resposta->json();
        if (is_array($json) && TratadorErros::mensagens($json) !== []) {
            return TratadorErros::excecao($resposta, new Requisicao('GET', '', $operacao));
        }

        return new ErroApi(
            sprintf('A resposta de "%s" não trouxe o campo "%s".', $operacao, $campo),
            $resposta->status,
            [],
            null,
            null,
            is_array($json) ? $json : $resposta->corpo,
        );
    }

    /** Segmento de caminho codificado para URL. */
    protected static function segmento(string $valor): string
    {
        return rawurlencode(trim($valor));
    }

    /** Contrato: da configuração ou, na falta dela, do objeto Token. */
    protected function contrato(Token|string $token): ?string
    {
        return $this->configuracao->contrato ?? ($token instanceof Token ? $token->contrato : null);
    }

    /** Diretoria regional: da configuração ou, na falta dela, do objeto Token. */
    protected function diretoriaRegional(Token|string $token): ?int
    {
        return $this->configuracao->diretoriaRegional ?? ($token instanceof Token ? $token->diretoriaRegional : null);
    }
}
