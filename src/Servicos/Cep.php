<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Mapeadores\CepMapeador;
use MarceloFecchio\Correios\Respostas\EnderecoCep;
use MarceloFecchio\Correios\Token;
use MarceloFecchio\Correios\Utilitarios\Cep as UtilCep;

/** API CEP. */
final class Cep extends Servico
{
    /** `GET /cep/v2/enderecos/{cep}`. Aceita o CEP com ou sem hífen; envia só os 8 dígitos. */
    public function consultar(Token|string $token, string $cep): EnderecoCep
    {
        $cep = UtilCep::normalizar($cep);
        $resposta = $this->chamar('GET', '/cep/v2/enderecos/' . $cep, 'consultar CEP', $token);

        return CepMapeador::deApi($this->leitor($resposta), $cep);
    }
}
