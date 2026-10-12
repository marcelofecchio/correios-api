<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Http;

use MarceloFecchio\Correios\Excecoes\CorreiosException;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroAutenticacao;
use MarceloFecchio\Correios\Excecoes\ErroLimiteRequisicoes;
use MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente;
use MarceloFecchio\Correios\Excecoes\ErroRotuloEmGeracao;

/**
 * Converte uma resposta de erro na exceção adequada.
 *
 * Lê os dois formatos de erro da API: `MessageResponse` (`msgs`, `causa`...) e `{"mensagem": "PPN-288: ..."}`.
 * Corpo que não é JSON (ex.: página HTML "HTTP Status 500") fica em `respostaBruta` como texto.
 *
 * @internal
 */
final class TratadorErros
{
    private const string CODIGO_PENDENTE = 'PPN-288';
    private const string CODIGO_EM_GERACAO = 'PPN-291';

    private function __construct() {}

    public static function excecao(RespostaHttp $resposta, Requisicao $requisicao): CorreiosException
    {
        $json = $resposta->json();
        $bruto = is_array($json) ? $json : $resposta->corpo;
        $mensagens = is_array($json) ? self::mensagens($json) : [];
        $causa = is_array($json) && is_string($json['causa'] ?? null) ? $json['causa'] : null;
        $codigo = self::codigoErro($mensagens);

        $detalhe = $mensagens !== []
            ? implode(' | ', $mensagens)
            : ($json === null ? 'a resposta não veio em JSON (conteúdo guardado em respostaBruta).' : 'sem mensagem.');
        $texto = sprintf('Correios respondeu HTTP %d em "%s": %s', $resposta->status, $requisicao->operacao, $detalhe);
        if (isset($requisicao->dicas[$resposta->status])) {
            $texto .= ' ' . $requisicao->dicas[$resposta->status];
        }

        return match (true) {
            $codigo === self::CODIGO_PENDENTE => new ErroPrePostagemPendente(
                $texto . ' Aguarde a pré-postagem chegar ao status 2 (PREPOSTADO) e solicite o rótulo de novo: este recibo não volta a funcionar.',
                $resposta->status,
                $mensagens,
                $codigo,
                $causa,
                $bruto,
            ),
            $codigo === self::CODIGO_EM_GERACAO => new ErroRotuloEmGeracao(
                $texto . ' O rótulo ainda está sendo gerado: baixe de novo com o mesmo recibo daqui a alguns segundos.',
                $resposta->status,
                $mensagens,
                $codigo,
                $causa,
                $bruto,
            ),
            $resposta->status === 401, $resposta->status === 403 => new ErroAutenticacao(
                $texto . ($resposta->status === 401
                    ? ' Verifique as credenciais ou gere um novo token.'
                    : ' Verifique se a API está liberada no cartão de postagem (lista "apis" do token).'),
                $resposta->status,
                $mensagens,
                $codigo,
                $causa,
                $bruto,
            ),
            $resposta->status === 429 => new ErroLimiteRequisicoes(
                $texto . ' Limite de requisições excedido: aguarde antes de tentar de novo.',
                $resposta->status,
                $mensagens,
                $codigo,
                $causa,
                $bruto,
            ),
            default => new ErroApi($texto, $resposta->status, $mensagens, $codigo, $causa, $bruto),
        };
    }

    /**
     * @param array<mixed> $json
     *
     * @return list<string>
     */
    public static function mensagens(array $json): array
    {
        $mensagens = [];
        foreach (['msgs', 'mensagens'] as $chave) {
            if (is_array($json[$chave] ?? null)) {
                foreach ($json[$chave] as $mensagem) {
                    if (is_scalar($mensagem) && trim((string) $mensagem) !== '') {
                        $mensagens[] = trim((string) $mensagem);
                    }
                }
            }
        }
        foreach (['mensagem', 'message', 'msg', 'txErro'] as $chave) {
            if (is_string($json[$chave] ?? null) && trim($json[$chave]) !== '') {
                $mensagens[] = trim($json[$chave]);
            }
        }

        return array_values(array_unique($mensagens));
    }

    /**
     * Código no padrão `LETRAS-NÚMEROS:` no início de uma mensagem (ex.: `PPN-288`, `ERP-049`, `PPN-145-5`).
     *
     * @param list<string> $mensagens
     */
    public static function codigoErro(array $mensagens): ?string
    {
        foreach ($mensagens as $mensagem) {
            if (preg_match('/^\s*([A-Z]{2,6}-\d{1,6}(?:-\d{1,6})*)\s*:/', $mensagem, $m) === 1) {
                return $m[1];
            }
        }

        return null;
    }
}
