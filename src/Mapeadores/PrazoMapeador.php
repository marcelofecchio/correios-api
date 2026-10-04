<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use MarceloFecchio\Correios\Dto\ConsultaDataPrevista;
use MarceloFecchio\Correios\Dto\ConsultaPrazo;
use MarceloFecchio\Correios\Respostas\DataPrevista;
use MarceloFecchio\Correios\Respostas\ResultadoPrazo;
use MarceloFecchio\Correios\Utilitarios\Datas;

/**
 * API Prazo. Os formatos de data mudam por endpoint: `dtEvento` é `DD-MM-YYYY` no GET e `DD/MM/YYYY` no
 * lote; `dataPostagem` do lote é `YYYY-MM-DD`; `dtPostagem` da data prevista é `DD-MM-YYYY`.
 *
 * @internal
 */
final class PrazoMapeador
{
    private function __construct() {}

    /** @return array<string, string> */
    public static function query(ConsultaPrazo $consulta): array
    {
        return array_filter([
            'cepOrigem' => $consulta->cepOrigem,
            'cepDestino' => $consulta->cepDestino,
            'dtEvento' => $consulta->dataEvento !== null ? Datas::formatar($consulta->dataEvento, 'd-m-Y') : null,
        ], static fn(?string $valor): bool => $valor !== null);
    }

    /** @return array<string, string> */
    public static function itemLote(ConsultaPrazo $consulta, string $codigoServico, string $nuRequisicao): array
    {
        return array_filter([
            'coProduto' => $codigoServico,
            'nuRequisicao' => $nuRequisicao,
            'dtEvento' => $consulta->dataEvento !== null ? Datas::formatar($consulta->dataEvento, 'd/m/Y') : null,
            'cepOrigem' => $consulta->cepOrigem,
            'cepDestino' => $consulta->cepDestino,
            'dataPostagem' => $consulta->dataPostagem !== null ? Datas::formatar($consulta->dataPostagem, 'Y-m-d') : null,
        ], static fn(?string $valor): bool => $valor !== null);
    }

    /** @return array<string, string|int|bool> */
    public static function queryDataPrevista(ConsultaDataPrevista $consulta, ?string $codigoServico): array
    {
        return array_filter([
            'cepOrigem' => $consulta->cepOrigem,
            'cepDestino' => $consulta->cepDestino,
            'prazo' => $consulta->prazoDias,
            'dtPostagem' => Datas::formatar($consulta->dataPostagem, 'd-m-Y'),
            'isEncomendaUPU' => $consulta->encomendaUpu,
            'isSabadoDiaUtil' => $consulta->sabadoDiaUtil,
            'isDomingoDiaUtil' => $consulta->domingoDiaUtil,
            'isPostagemDH' => $consulta->postagemDh,
            'coProduto' => $codigoServico,
        ], static fn(string|int|bool|null $valor): bool => $valor !== null);
    }

    public static function resultado(Leitor $dados): ResultadoPrazo
    {
        return new ResultadoPrazo(
            codigoServico: $dados->texto('coProduto'),
            prazoDias: $dados->inteiro('prazoEntrega'),
            dataMaxima: $dados->data('dataMaxima'),
            entregaDomiciliar: $dados->booleano('entregaDomiciliar'),
            entregaSabado: $dados->booleano('entregaSabado'),
            entregaDomingo: $dados->booleano('entregaDomingo'),
            mensagem: $dados->texto('msgPrazo'),
            nuRequisicao: $dados->texto('nuRequisicao'),
            erro: $dados->texto('txErro'),
            bruto: $dados->bruto(),
        );
    }

    public static function dataPrevista(Leitor $dados): DataPrevista
    {
        return new DataPrevista($dados->data('dataPrevista'), $dados->texto('msgPrazo'), $dados->bruto());
    }
}
