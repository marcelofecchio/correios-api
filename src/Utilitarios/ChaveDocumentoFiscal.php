<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Utilitarios;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use Stringable;

/**
 * Chave de acesso de 44 dígitos (NF-e ou DCe), validada pelo dígito verificador (módulo 11, pesos 2 a 9).
 */
final readonly class ChaveDocumentoFiscal implements Stringable
{
    public string $valor;

    public function __construct(string $chave)
    {
        $digitos = Texto::digitos($chave);
        if (preg_match('/^[\d.\s]+$/', trim($chave)) !== 1 || strlen($digitos) !== 44) {
            throw ErroValidacao::campo('chaveNFe', 'A chave de acesso deve ter 44 dígitos.');
        }
        if (self::calcularDigito(substr($digitos, 0, 43)) !== (int) $digitos[43]) {
            throw ErroValidacao::campo('chaveNFe', sprintf('Dígito verificador da chave de acesso "%s" não confere.', $digitos));
        }
        $this->valor = $digitos;
    }

    public static function valida(string $chave): bool
    {
        $digitos = Texto::digitos($chave);

        return strlen($digitos) === 44 && self::calcularDigito(substr($digitos, 0, 43)) === (int) $digitos[43];
    }

    /** @param string $chave43 os 43 primeiros dígitos */
    public static function calcularDigito(string $chave43): int
    {
        if (preg_match('/^\d{43}$/', $chave43) !== 1) {
            throw ErroValidacao::campo('chaveNFe', 'Para calcular o dígito, informe os 43 primeiros dígitos da chave.');
        }

        $soma = 0;
        $peso = 2;
        for ($i = 42; $i >= 0; $i--) {
            $soma += (int) $chave43[$i] * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }
        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }

    public function codigoUf(): string
    {
        return substr($this->valor, 0, 2);
    }

    /** Ano e mês de emissão (AAMM). */
    public function anoMes(): string
    {
        return substr($this->valor, 2, 4);
    }

    public function cnpjEmitente(): string
    {
        return substr($this->valor, 6, 14);
    }

    /** `55` para NF-e. */
    public function modelo(): string
    {
        return substr($this->valor, 20, 2);
    }

    /** Série com 3 dígitos (`001`). */
    public function serie(): string
    {
        return substr($this->valor, 22, 3);
    }

    /** Número da nota com 9 dígitos (`000021486`). */
    public function numero(): string
    {
        return substr($this->valor, 25, 9);
    }

    public function tipoEmissao(): string
    {
        return substr($this->valor, 34, 1);
    }

    public function codigoNumerico(): string
    {
        return substr($this->valor, 35, 8);
    }

    public function digito(): int
    {
        return (int) $this->valor[43];
    }

    /** 11 grupos de 4 dígitos separados por espaço. */
    public function formatada(): string
    {
        return implode(' ', str_split($this->valor, 4));
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
