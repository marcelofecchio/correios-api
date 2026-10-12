<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

/** Tamanho da página do PDF gerado a partir do ZPL (cabeçalho `X-Page-Size` do Labelary). */
enum TamanhoPagina: string
{
    case A4 = 'A4';
    case A5 = 'A5';
    case A6 = 'A6';
    case Carta = 'Letter';
    case Oficio = 'Legal';

    /** Aceita o valor (`A4`, `Letter`) ou o nome do caso (`Carta`, `oficio`), sem diferenciar maiúsculas. */
    public static function deTexto(string $texto): ?self
    {
        $texto = strtolower(trim($texto));
        foreach (self::cases() as $caso) {
            if ($texto === strtolower($caso->value) || $texto === strtolower($caso->name)) {
                return $caso;
            }
        }

        return null;
    }
}
