<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;

/**
 * Valores monetários em centavos (`int`). O componente nunca usa `float` para dinheiro.
 *
 * As APIs usam formatos diferentes: a Preço responde `"19,92"` (vírgula decimal, como texto) e a
 * Pré-postagem recebe `"150.00"` (ponto e 2 casas).
 */
final class Dinheiro
{
    private function __construct() {}

    /**
     * Lê um valor em texto e devolve centavos.
     *
     * Aceita `19,92`, `1.234,56`, `150.00`, `1,234.56` e `0,1000`: o último separador (vírgula ou ponto)
     * é o decimal. Com 3 ou mais casas, arredonda para 2 (meio para cima).
     */
    public static function paraCentavos(string $valor, string $campo = 'valor'): int
    {
        $texto = trim(str_replace(['R$', ' ', "\u{00A0}"], '', $valor));
        if ($texto === '' || preg_match('/^-?[\d.,]+$/', $texto) !== 1) {
            throw ErroValidacao::campo($campo, sprintf('Valor monetário "%s" inválido.', $valor));
        }

        $negativo = str_starts_with($texto, '-');
        $texto = ltrim($texto, '-');
        $posicaoDecimal = max((int) strrpos($texto, ','), (int) strrpos($texto, '.'));
        $temSeparador = str_contains($texto, ',') || str_contains($texto, '.');

        if ($temSeparador) {
            $inteiro = Texto::digitos(substr($texto, 0, $posicaoDecimal));
            $fracao = Texto::digitos(substr($texto, $posicaoDecimal + 1));
        } else {
            $inteiro = $texto;
            $fracao = '';
        }

        $fracao = str_pad($fracao, 3, '0');
        $centavos = (int) ($inteiro === '' ? '0' : $inteiro) * 100 + (int) substr($fracao, 0, 2);
        if ((int) $fracao[2] >= 5) {
            $centavos++;
        }

        return $negativo ? -$centavos : $centavos;
    }

    /** Converte um número vindo do JSON da API (ex.: `valorAtendimento: 25.5`). */
    public static function numeroParaCentavos(int|float $valor): int
    {
        return (int) round($valor * 100);
    }

    /** Formato da Pré-postagem e do Preço (requisição): `150.00`. */
    public static function formatarApi(int $centavos): string
    {
        $sinal = $centavos < 0 ? '-' : '';
        $absoluto = abs($centavos);

        return sprintf('%s%d.%02d', $sinal, intdiv($absoluto, 100), $absoluto % 100);
    }

    /** Formato brasileiro, sem símbolo: `1.234,56`. */
    public static function formatarBr(int $centavos): string
    {
        $sinal = $centavos < 0 ? '-' : '';
        $absoluto = abs($centavos);

        return $sinal . number_format(intdiv($absoluto, 100), 0, ',', '.') . sprintf(',%02d', $absoluto % 100);
    }
}
