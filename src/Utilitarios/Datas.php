<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Datas das APIs dos Correios. Os formatos mudam por campo:
 *
 * - ISO sem fuso (`2026-09-29T14:55:29`) → interpretado em `America/Sao_Paulo`;
 * - ISO com fuso (`2026-09-29T14:55:29-03:00`) → mantém o fuso informado;
 * - `dd/MM/yyyy`, `DD-MM-YYYY` e `YYYY-MM-DD`.
 */
final class Datas
{
    public const string FUSO = 'America/Sao_Paulo';

    private const array FORMATOS = [
        '!Y-m-d\TH:i:sP',
        '!Y-m-d\TH:i:s.uP',
        '!Y-m-d\TH:i:s',
        '!Y-m-d\TH:i:s.u',
        '!Y-m-d\TH:i',
        '!Y-m-d H:i:s',
        '!Y-m-d',
        '!d/m/Y H:i:s',
        '!d/m/Y H:i',
        '!d/m/Y',
        '!d-m-Y H:i:s',
        '!d-m-Y',
    ];

    private function __construct() {}

    public static function fuso(): DateTimeZone
    {
        return new DateTimeZone(self::FUSO);
    }

    /** Interpreta uma data da API. Devolve null se o texto for vazio ou não estiver em um formato conhecido. */
    public static function interpretar(mixed $valor): ?DateTimeImmutable
    {
        if (!is_string($valor) || trim($valor) === '') {
            return null;
        }

        $texto = trim($valor);
        foreach (self::FORMATOS as $formato) {
            $data = DateTimeImmutable::createFromFormat($formato, $texto, self::fuso());
            $erros = DateTimeImmutable::getLastErrors();
            if ($data !== false && ($erros === false || ($erros['warning_count'] === 0 && $erros['error_count'] === 0))) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Formata uma data de calendário para a API, exatamente como foi informada (sem converter o fuso):
     * `new DateTimeImmutable('2026-10-18')` vira `18/10/2026` em qualquer fuso do servidor.
     */
    public static function formatar(DateTimeInterface $data, string $formato): string
    {
        return $data->format($formato);
    }
}
