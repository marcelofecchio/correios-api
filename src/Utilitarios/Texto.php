<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

/** Funções de texto usadas na validação e na geração das etiquetas. */
final class Texto
{
    private const array ACENTOS = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ç' => 'c', 'Ç' => 'C', 'ñ' => 'n', 'Ñ' => 'N',
        'º' => 'o', 'ª' => 'a', '°' => 'o',
    ];

    private function __construct() {}

    /** Troca as letras acentuadas pela letra sem acento (tabela própria, igual em qualquer sistema). */
    public static function semAcentos(string $texto): string
    {
        return strtr($texto, self::ACENTOS);
    }

    /** Para dados de entrada: remove caracteres de controle e quebras de linha e junta espaços repetidos. */
    public static function limpar(string $texto): string
    {
        return trim(preg_replace('/\s{2,}/u', ' ', self::semControle($texto)) ?? '');
    }

    /** Para textos já formatados: troca caracteres de controle e quebras de linha por espaço, sem mexer no resto. */
    public static function semControle(string $texto): string
    {
        return preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $texto) ?? '';
    }

    /**
     * Junta as partes preenchidas e, se passar do limite, encurta só a primeira (ex.: o logradouro), preservando
     * as demais (ex.: cidade/UF e CEP).
     */
    public static function juntarCortandoPrimeira(string $separador, int $limite, ?string $primeira, ?string ...$resto): string
    {
        $final = self::juntar($separador, ...$resto);
        $completo = self::juntar($separador, $primeira, $final);
        if (self::tamanho($completo) <= $limite || $final === '') {
            return self::cortar($completo, $limite);
        }

        $disponivel = $limite - self::tamanho($final) - self::tamanho($separador);
        $inicio = $disponivel > 0 ? rtrim(self::cortar(trim((string) $primeira), $disponivel)) : '';

        return self::cortar(self::juntar($separador, $inicio, $final), $limite);
    }

    /** Corta por caracteres (não por bytes). */
    public static function cortar(string $texto, int $limite): string
    {
        return mb_substr($texto, 0, max(0, $limite));
    }

    public static function tamanho(string $texto): int
    {
        return mb_strlen($texto);
    }

    /** Mantém só os dígitos. */
    public static function digitos(string $texto): string
    {
        return preg_replace('/\D+/', '', $texto) ?? '';
    }

    /** Junta as partes preenchidas com o separador, ignorando as vazias. */
    public static function juntar(string $separador, ?string ...$partes): string
    {
        $preenchidas = array_filter(
            array_map(static fn(?string $parte): string => trim((string) $parte), $partes),
            static fn(string $parte): bool => $parte !== '',
        );

        return implode($separador, $preenchidas);
    }
}
