<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;

/** CPF e CNPJ: normalização (só dígitos) e validação pelo dígito verificador. */
final class Documento
{
    private function __construct() {}

    /** Remove a máscara e valida como CPF (11 dígitos) ou CNPJ (14 dígitos). */
    public static function normalizar(string $documento, string $campo = 'documento'): string
    {
        $digitos = Texto::digitos($documento);
        if (preg_match('/^[\d.\/\s-]+$/', trim($documento)) !== 1 || !self::valido($digitos)) {
            throw ErroValidacao::campo($campo, sprintf('CPF ou CNPJ "%s" inválido.', $documento));
        }

        return $digitos;
    }

    public static function valido(string $documento): bool
    {
        return self::cpfValido($documento) || self::cnpjValido($documento);
    }

    public static function ehCpf(string $documento): bool
    {
        return strlen(Texto::digitos($documento)) === 11;
    }

    public static function cpfValido(string $cpf): bool
    {
        $cpf = Texto::digitos($cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($posicao = 9; $posicao <= 10; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * ($posicao + 1 - $i);
            }
            $digito = (10 * $soma) % 11 % 10;
            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(string $cnpj): bool
    {
        $cnpj = Texto::digitos($cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        foreach ([12, 13] as $posicao) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cnpj[$i] * $pesos[$i + 13 - $posicao];
            }
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    /** `52998224725` → `529.982.247-25`; `11222333000181` → `11.222.333/0001-81`. */
    public static function formatar(string $documento): string
    {
        $digitos = Texto::digitos($documento);

        return match (strlen($digitos)) {
            11 => sprintf('%s.%s.%s-%s', substr($digitos, 0, 3), substr($digitos, 3, 3), substr($digitos, 6, 3), substr($digitos, 9)),
            14 => sprintf('%s.%s.%s/%s-%s', substr($digitos, 0, 2), substr($digitos, 2, 3), substr($digitos, 5, 3), substr($digitos, 8, 4), substr($digitos, 12)),
            default => $documento,
        };
    }
}
