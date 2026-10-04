<?php

declare(strict_types=1);

/*
 * Boas práticas com o token: o componente NÃO guarda o token. A aplicação grava na sessão e, antes de gerar
 * outro, confere se o atual ainda é válido. A API Token aceita no máximo 3 requisições por segundo (HTTP 429)
 * e devolve o mesmo token até os 30 minutos finais da validade.
 */

use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Excecoes\ErroLimiteRequisicoes;
use MarceloFecchio\Correios\Token;

require __DIR__ . '/_inicio.php';

function obterToken(Correios $correios): Token
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // 1. Já existe um token válido na sessão? Reutilize (margem de 5 minutos antes da expiração).
    if (isset($_SESSION['correios_token']) && is_array($_SESSION['correios_token'])) {
        $token = Token::deArray($_SESSION['correios_token']);
        if ($token->estaValido(margemSegundos: 300)) {
            return $token;
        }
    }

    // 2. Não existe ou está perto de expirar: gere um novo e grave na sessão.
    try {
        $token = $correios->token()->gerar();
    } catch (ErroLimiteRequisicoes $e) {
        // Muitas gerações seguidas: aguarde e tente de novo, ou use o token anterior se ainda não expirou.
        throw $e;
    }
    $_SESSION['correios_token'] = $token->paraArray();

    return $token;
}

$token = obterToken(correios());

mostrar('Ambiente', $token->ambiente->value);
mostrar('Expira em', $token->expiraEm->format('d/m/Y H:i:s'));
mostrar('Válido', $token->estaValido());
mostrar('Contrato do cartão', $token->contrato);
mostrar('APIs liberadas', $token->apis);
