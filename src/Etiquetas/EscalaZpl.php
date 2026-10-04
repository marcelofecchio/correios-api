<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

/**
 * Converte um template escrito em 203 dpi para outra resolução, multiplicando coordenadas, tamanhos de
 * fonte, traços, larguras, alturas e módulos dos códigos pelo fator (1,5 para 300 dpi).
 *
 * Imagens `^GFA` não são alteradas: para 300 dpi use parciais de logo próprios (`300dpi/parciais/`).
 *
 * @internal
 */
final class EscalaZpl
{
    /** Comando → posições (0 = primeiro parâmetro) que são escaladas. */
    private const array PARAMETROS = [
        'FO' => [0, 1],
        'FT' => [0, 1],
        'GB' => [0, 1, 2],
        'FB' => [0, 2],
        'PW' => [0],
        'LL' => [0],
        'LH' => [0, 1],
        'LS' => [0],
        'BY' => [0, 2],
        'BC' => [1],
        'BX' => [1],
        'A' => [1, 2],
    ];

    private function __construct() {}

    public static function aplicar(string $zpl, float $fator): string
    {
        if (abs($fator - 1.0) < 0.0001) {
            return $zpl;
        }

        return preg_replace_callback(
            '/\^(FO|FT|GB|FB|PW|LL|LH|LS|BY|BC[NRIB]?|BX[NRIB]?|A[0-9A-Z][NRIB]?)([^\^~\r\n]*)/',
            static function (array $m) use ($fator): string {
                $comando = $m[1];
                $chave = match (true) {
                    str_starts_with($comando, 'BC') => 'BC',
                    str_starts_with($comando, 'BX') => 'BX',
                    $comando[0] === 'A' => 'A',
                    default => $comando,
                };
                // ^A0N,28,28 / ^BCN,110 / ^BXN,5,200: o texto após o comando começa com vírgula, então o
                // primeiro elemento é vazio e os valores começam no índice 1.
                $parametros = explode(',', $m[2]);
                foreach (self::PARAMETROS[$chave] as $indice) {
                    if (!isset($parametros[$indice]) || preg_match('/^\d+$/', trim($parametros[$indice])) !== 1) {
                        continue;
                    }
                    $parametros[$indice] = (string) self::escalar((int) trim($parametros[$indice]), $fator);
                }

                return '^' . $comando . implode(',', $parametros);
            },
            $zpl,
        ) ?? $zpl;
    }

    /** Arredonda para o inteiro mais próximo (módulos: 2 → 3, 3 → 5, 5 → 8); valor positivo nunca vira 0. */
    private static function escalar(int $valor, float $fator): int
    {
        $resultado = (int) round($valor * $fator);

        return $valor > 0 ? max(1, $resultado) : $resultado;
    }
}
