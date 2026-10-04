<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use Stringable;

/**
 * Código do objeto (rastreamento), no formato `AA123456789BR`.
 *
 * O 11º caractere é o dígito verificador: módulo 11 com pesos 8, 6, 4, 2, 3, 5, 9, 7 sobre os 8 primeiros
 * dígitos; resto 0 vira 5, resto 1 vira 0 e nos demais casos o dígito é 11 − resto.
 */
final readonly class CodigoRastreamento implements Stringable
{
    private const array PESOS = [8, 6, 4, 2, 3, 5, 9, 7];

    public string $valor;

    /**
     * Aceita o código com espaços e letras minúsculas (`ab 043 180 865 br`).
     *
     * @param bool $validarDigito false valida só o formato
     */
    public function __construct(string $codigo, bool $validarDigito = true)
    {
        $normalizado = self::normalizar($codigo);
        if (!self::formatoValido($normalizado)) {
            throw ErroValidacao::campo('codigoObjeto', sprintf('Código do objeto "%s" fora do formato AA123456789BR.', $codigo));
        }
        if ($validarDigito && !self::digitoValido($normalizado)) {
            throw ErroValidacao::campo('codigoObjeto', sprintf('Dígito verificador do código do objeto "%s" não confere.', $codigo));
        }
        $this->valor = $normalizado;
    }

    public static function normalizar(string $codigo): string
    {
        return strtoupper(preg_replace('/\s+/', '', $codigo) ?? '');
    }

    public static function formatoValido(string $codigo): bool
    {
        return preg_match('/^[A-Z]{2}\d{9}[A-Z]{2}$/', self::normalizar($codigo)) === 1;
    }

    public static function digitoValido(string $codigo): bool
    {
        $codigo = self::normalizar($codigo);
        if (!self::formatoValido($codigo)) {
            return false;
        }

        return self::calcularDigito(substr($codigo, 2, 8)) === (int) $codigo[10];
    }

    /** @param string $numero os 8 primeiros dígitos */
    public static function calcularDigito(string $numero): int
    {
        if (preg_match('/^\d{8}$/', $numero) !== 1) {
            throw ErroValidacao::campo('codigoObjeto', 'Para calcular o dígito, informe os 8 dígitos do número.');
        }

        $soma = 0;
        foreach (self::PESOS as $i => $peso) {
            $soma += (int) $numero[$i] * $peso;
        }
        $resto = $soma % 11;

        return match ($resto) {
            0 => 5,
            1 => 0,
            default => 11 - $resto,
        };
    }

    /** Formato impresso nas etiquetas: `AB 043 180 865 BR`. */
    public function formatado(): string
    {
        return sprintf(
            '%s %s %s %s %s',
            substr($this->valor, 0, 2),
            substr($this->valor, 2, 3),
            substr($this->valor, 5, 3),
            substr($this->valor, 8, 3),
            substr($this->valor, 11, 2),
        );
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
