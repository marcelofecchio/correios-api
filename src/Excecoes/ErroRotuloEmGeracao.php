<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

/**
 * PPN-291: o rótulo ainda está sendo gerado. Observado em homologação (11/10/2026) com HTTP 200, no download feito
 * logo depois da solicitação.
 *
 * Diferente do PPN-288, **o recibo continua válido**: baixe de novo com o mesmo recibo alguns segundos depois.
 * `idRecibo()` lê o recibo da mensagem da API (`... idRecibo: <id>`), quando ela o traz.
 */
class ErroRotuloEmGeracao extends ErroApi
{
    public function idRecibo(): ?string
    {
        foreach ($this->mensagens as $mensagem) {
            if (preg_match('/idRecibo\s*[:=]\s*([A-Za-z0-9_-]+)/i', $mensagem, $m) === 1) {
                return $m[1];
            }
        }

        return null;
    }
}
