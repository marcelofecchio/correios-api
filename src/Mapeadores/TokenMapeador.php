<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Token;

/** @internal */
final class TokenMapeador
{
    private function __construct() {}

    /**
     * Lê a resposta da API Token. A data vem sem fuso e é interpretada como `America/Sao_Paulo`.
     *
     * @param Ambiente $ambientePadrao usado se a resposta não trouxer `ambiente`
     */
    public static function deApi(Leitor $dados, Ambiente $ambientePadrao): Token
    {
        $valor = $dados->texto('token');
        $expiraEm = $dados->data('expiraEm');
        if ($valor === null || $expiraEm === null) {
            throw new ErroApi('A API Token respondeu sem os campos "token" e "expiraEm".', 200, [], null, null, $dados->bruto());
        }

        $cartao = $dados->filho('cartaoPostagem');
        $contrato = $dados->filho('contrato');
        $ambiente = Ambiente::tryFrom((string) $dados->texto('ambiente')) ?? $ambientePadrao;

        $apis = [];
        foreach (['api', 'apis'] as $chave) {
            $lista = $dados->bruto()[$chave] ?? null;
            if (!is_array($lista)) {
                continue;
            }
            foreach ($lista as $item) {
                $id = is_array($item) ? ($item['api'] ?? null) : $item;
                if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                    $apis[] = (int) $id;
                }
            }
        }
        if ($apis === []) {
            foreach ($cartao->textos('api') as $id) {
                if (ctype_digit($id)) {
                    $apis[] = (int) $id;
                }
            }
        }

        return new Token(
            valor: $valor,
            emitidoEm: $dados->data('emissao') ?? new DateTimeImmutable('now'),
            expiraEm: $expiraEm,
            ambiente: $ambiente,
            cartaoPostagem: $cartao->texto('numero'),
            contrato: $cartao->texto('contrato') ?? $contrato->texto('numero'),
            diretoriaRegional: $cartao->inteiro('dr') ?? $contrato->inteiro('dr'),
            cnpj: $dados->texto('cnpj'),
            apis: array_values(array_unique($apis)),
        );
    }

    /** Valor bruto do campo `ambiente` (pode ser `DESENVOLVIMENTO` ou `LOCAL`, que o componente não usa). */
    public static function ambienteInformado(Leitor $dados): ?string
    {
        return $dados->texto('ambiente');
    }
}
