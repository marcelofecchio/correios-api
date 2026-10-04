<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Http;

/**
 * Remove dados sensíveis antes de registrar requisições e respostas no log.
 *
 * - token e credenciais: substituídos por `***`;
 * - CPF/CNPJ e documentos: só os 2 últimos dígitos ficam visíveis;
 * - conteúdo Base64 de rótulos e DACE (`dados`): omitido, fica só o tamanho.
 *
 * @internal
 */
final class MascaradorLog
{
    private const array CHAVES_SEGREDO = ['token', 'codigoacesso', 'codigo_acesso', 'senha', 'password', 'authorization'];
    private const array CHAVES_DOCUMENTO = ['cpfcnpj', 'cpf', 'cnpj', 'documento', 'numeroexternocliente', 'usuario'];
    private const array CHAVES_CONTEUDO = ['dados'];

    private function __construct() {}

    public static function corpo(string $corpo): string
    {
        $json = json_decode($corpo, true);
        if (!is_array($json)) {
            return self::texto(mb_substr($corpo, 0, 2000));
        }

        return (string) json_encode(self::array($json), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<mixed> $dados
     *
     * @return array<mixed>
     */
    public static function array(array $dados): array
    {
        $resultado = [];
        foreach ($dados as $chave => $valor) {
            $nome = strtolower((string) $chave);
            $resultado[$chave] = match (true) {
                in_array($nome, self::CHAVES_SEGREDO, true) => '***',
                in_array($nome, self::CHAVES_CONTEUDO, true) && is_string($valor) => sprintf('[omitido: %d caracteres]', strlen($valor)),
                in_array($nome, self::CHAVES_DOCUMENTO, true) && is_scalar($valor) => self::documento((string) $valor),
                is_array($valor) => self::array($valor),
                is_string($valor) => self::texto($valor),
                default => $valor,
            };
        }

        return $resultado;
    }

    /** Mascara sequências de 11 ou 14 dígitos (CPF/CNPJ) em textos e URLs. */
    public static function texto(string $texto): string
    {
        return preg_replace_callback(
            '/(?<!\d)(\d{11}|\d{14})(?!\d)/',
            static fn(array $m): string => self::documento($m[1]),
            $texto,
        ) ?? $texto;
    }

    private static function documento(string $valor): string
    {
        $tamanho = strlen($valor);

        return $tamanho <= 2 ? '**' : str_repeat('*', $tamanho - 2) . substr($valor, -2);
    }
}
