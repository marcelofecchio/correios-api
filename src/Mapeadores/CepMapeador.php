<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use MarceloFecchio\Correios\Respostas\EnderecoCep;

/** @internal */
final class CepMapeador
{
    private function __construct() {}

    public static function deApi(Leitor $dados, string $cepConsultado): EnderecoCep
    {
        return new EnderecoCep(
            cep: $dados->texto('cep') ?? $cepConsultado,
            uf: $dados->texto('uf'),
            cidade: $dados->texto('localidade'),
            logradouro: $dados->texto('logradouro'),
            bairro: $dados->texto('bairro'),
            complemento: $dados->texto('complemento'),
            tipoCep: $dados->inteiro('tipoCEP'),
            codigoIbge: $dados->texto('codigoIbge'),
            nome: $dados->texto('nome'),
            bruto: $dados->bruto(),
        );
    }
}
