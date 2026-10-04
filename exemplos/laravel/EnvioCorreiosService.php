<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as Cache;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Etiquetas\EtiquetaZpl;
use MarceloFecchio\Correios\Respostas\PrePostagemCriada;
use MarceloFecchio\Correios\Token;

/**
 * Exemplo de service no Laravel. `Correios` vem do container (registrado pelo CorreiosServiceProvider).
 *
 * Em filas e jobs não há sessão: o token fica no cache compartilhado, com trava para que vários processos não
 * gerem tokens ao mesmo tempo (a API Token aceita 3 requisições por segundo).
 */
final class EnvioCorreiosService
{
    public function __construct(
        private readonly Correios $correios,
        private readonly Cache $cache,
    ) {}

    public function token(): Token
    {
        $salvo = $this->cache->get('correios:token');
        if (is_array($salvo)) {
            $token = Token::deArray($salvo);
            if ($token->estaValido()) {
                return $token;
            }
        }

        return $this->cache->lock('correios:token:trava', 10)->block(10, function (): Token {
            // Outro processo pode ter renovado enquanto este esperava a trava
            $salvo = $this->cache->get('correios:token');
            if (is_array($salvo) && Token::deArray($salvo)->estaValido()) {
                return Token::deArray($salvo);
            }

            $token = $this->correios->token()->gerar();
            $this->cache->put('correios:token', $token->paraArray(), $token->expiraEm->modify('-5 minutes'));

            return $token;
        });
    }

    /** @return array{0: PrePostagemCriada, 1: EtiquetaZpl} */
    public function enviar(NovaPrePostagem $prePostagem): array
    {
        $criada = $this->correios->prePostagem()->criar(token: $this->token(), prePostagem: $prePostagem);
        $etiqueta = $this->correios->etiquetas()->postagemDePrePostagem($prePostagem, $criada);

        return [$criada, $etiqueta];
    }
}
