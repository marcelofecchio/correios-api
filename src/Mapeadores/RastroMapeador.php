<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use MarceloFecchio\Correios\Respostas\ArDigital;
use MarceloFecchio\Correios\Respostas\EventoRastreamento;
use MarceloFecchio\Correios\Respostas\Rastreamento;

/** @internal */
final class RastroMapeador
{
    private function __construct() {}

    /** @return list<Rastreamento> */
    public static function objetos(Leitor $sro): array
    {
        $objetos = [];
        foreach ($sro->lista('objetos') as $objeto) {
            $objetos[] = self::objeto($objeto);
        }

        return $objetos;
    }

    public static function objeto(Leitor $objeto): Rastreamento
    {
        $eventos = [];
        foreach ($objeto->lista('eventos') as $evento) {
            $unidade = $evento->filho('unidade');
            $destino = $evento->filho('unidadeDestino');
            $eventos[] = new EventoRastreamento(
                codigo: $evento->texto('codigo'),
                tipo: $evento->texto('tipo'),
                descricao: $evento->texto('descricao'),
                dataHora: $evento->data('dtHrCriado'),
                detalhe: $evento->texto('detalhe'),
                unidade: $unidade->texto('nome'),
                cidade: $unidade->filho('endereco')->texto('cidade'),
                uf: $unidade->filho('endereco')->texto('uf'),
                unidadeDestino: $destino->texto('nome'),
                cidadeDestino: $destino->filho('endereco')->texto('cidade'),
                ufDestino: $destino->filho('endereco')->texto('uf'),
                bruto: $evento->bruto(),
            );
        }

        return new Rastreamento(
            codigoObjeto: (string) $objeto->texto('codObjeto'),
            eventos: $eventos,
            mensagem: $objeto->texto('mensagem'),
            tipoPostal: $objeto->filho('tipoPostal')->texto('nome'),
            dataPrevista: $objeto->data('dtPrevista'),
            bruto: $objeto->bruto(),
        );
    }

    /** Item `ArDigitalTO`. Sem `resultadoProcessamento`, o sucesso é a presença da imagem. */
    public static function arDigital(Leitor $item): ArDigital
    {
        $resultado = $item->filho('resultadoProcessamento');
        $imagem = $item->texto('imagemBase64');

        return new ArDigital(
            codigoObjeto: (string) $item->texto('objeto'),
            sucesso: ($resultado->booleano('sucesso') ?? true) && $imagem !== null,
            codigoErro: $resultado->texto('codigo'),
            mensagem: $resultado->texto('resultado'),
            dataBaixa: $item->data('dataBaixa'),
            contentType: $item->texto('contentType'),
            formato: $item->texto('formato'),
            base64: $imagem !== null ? (string) preg_replace('/\s+/', '', $imagem) : null,
            bruto: $item->bruto(),
        );
    }
}
