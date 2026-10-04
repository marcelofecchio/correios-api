<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

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
}
