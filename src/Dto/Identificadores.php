<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

/**
 * Normaliza listas de ids e de códigos de objeto: descarta itens vazios e repetidos.
 *
 * @internal
 */
final class Identificadores
{
    private function __construct() {}

    /**
     * @param array<mixed> $ids
     *
     * @return list<string>
     */
    public static function ids(array $ids): array
    {
        $limpos = [];
        foreach ($ids as $id) {
            $id = is_scalar($id) ? trim((string) $id) : '';
            if ($id !== '' && !in_array($id, $limpos, true)) {
                $limpos[] = $id;
            }
        }

        return $limpos;
    }

    /**
     * Valida o formato dos códigos (sem exigir o dígito, que é conferido pelos Correios).
     *
     * @param array<mixed> $codigos
     *
     * @return list<string>
     */
    public static function codigos(array $codigos): array
    {
        $limpos = [];
        foreach ($codigos as $codigo) {
            if ($codigo instanceof CodigoRastreamento) {
                $codigo = $codigo->valor;
            }
            $codigo = is_scalar($codigo) ? trim((string) $codigo) : '';
            if ($codigo === '') {
                continue;
            }
            $codigo = new CodigoRastreamento($codigo, validarDigito: false)->valor;
            if (!in_array($codigo, $limpos, true)) {
                $limpos[] = $codigo;
            }
        }

        return $limpos;
    }
}
