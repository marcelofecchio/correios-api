<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;

/** CEP: sempre string com 8 dígitos, preservando os zeros à esquerda. */
final class Cep
{
    private function __construct() {}

    /** Remove a máscara (`01001-000` → `01001000`) e valida os 8 dígitos. */
    public static function normalizar(string $cep, string $campo = 'cep'): string
    {
        $digitos = Texto::digitos($cep);
        if (!self::valido($digitos) || preg_match('/^[\d.\s-]+$/', trim($cep)) !== 1) {
            throw ErroValidacao::campo($campo, sprintf('CEP "%s" inválido: informe 8 dígitos.', $cep));
        }

        return $digitos;
    }

    public static function valido(string $cep): bool
    {
        return preg_match('/^\d{8}$/', $cep) === 1;
    }

    /** `01001000` → `01001-000`. */
    public static function formatar(string $cep): string
    {
        $digitos = self::normalizar($cep);

        return substr($digitos, 0, 5) . '-' . substr($digitos, 5);
    }
}
