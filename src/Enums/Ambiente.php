<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/**
 * Ambiente da API dos Correios. O valor é o mesmo do campo `ambiente` devolvido pela API Token.
 */
enum Ambiente: string
{
    case Homologacao = 'HOMOLOGACAO';
    case Producao = 'PRODUCAO';

    /** Host das APIs (sem barra no final). */
    public function urlBase(): string
    {
        return match ($this) {
            self::Homologacao => 'https://apihom.correios.com.br',
            self::Producao => 'https://api.correios.com.br',
        };
    }

    /** Portal CWS onde se gera o código de acesso. */
    public function urlPortal(): string
    {
        return match ($this) {
            self::Homologacao => 'https://cwshom.correios.com.br',
            self::Producao => 'https://cws.correios.com.br',
        };
    }

    /** Aceita o valor do enum (`HOMOLOGACAO`), o nome do caso ou abreviações comuns (`hom`, `prod`). */
    public static function deTexto(string $texto): ?self
    {
        return match (strtolower(trim($texto))) {
            'homologacao', 'homologação', 'hom', 'homolog', 'sandbox', 'teste' => self::Homologacao,
            'producao', 'produção', 'prod', 'production' => self::Producao,
            default => null,
        };
    }
}
