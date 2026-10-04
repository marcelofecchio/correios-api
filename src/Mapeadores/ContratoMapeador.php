<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use MarceloFecchio\Correios\Respostas\PaginaServicosContrato;
use MarceloFecchio\Correios\Respostas\ServicoContrato;

/** @internal */
final class ContratoMapeador
{
    private function __construct() {}

    /**
     * `itens` é tratado de forma defensiva: a especificação o declara como objeto único, mas a lista paginada
     * deve trazer uma lista.
     */
    public static function pagina(Leitor $dados, int $paginaPedida, int $tamanhoPedido): PaginaServicosContrato
    {
        $servicos = [];
        foreach ($dados->lista('itens') as $item) {
            $servico = self::servico($item);
            if ($servico !== null) {
                $servicos[] = $servico;
            }
        }

        $pagina = $dados->filho('page');

        return new PaginaServicosContrato(
            servicos: $servicos,
            pagina: $pagina->inteiro('number') ?? $paginaPedida,
            tamanho: $pagina->inteiro('size') ?? $tamanhoPedido,
            totalItens: $pagina->inteiro('totalElements') ?? count($servicos),
            totalPaginas: $pagina->inteiro('totalPages') ?? 1,
            bruto: $dados->bruto(),
        );
    }

    public static function servico(Leitor $dados): ?ServicoContrato
    {
        $codigo = $dados->texto('codigo');
        if ($codigo === null) {
            return null;
        }

        return new ServicoContrato(
            codigo: $codigo,
            descricao: $dados->texto('descricao'),
            codigoSegmento: $dados->texto('coSegmento'),
            descricaoSegmento: $dados->texto('descSegmento'),
            bruto: $dados->bruto(),
        );
    }
}
