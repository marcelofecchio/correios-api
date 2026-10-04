<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\Identificadores;
use MarceloFecchio\Correios\Enums\ResultadoRastreamento;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Mapeadores\RastroMapeador;
use MarceloFecchio\Correios\Respostas\Rastreamento;
use MarceloFecchio\Correios\Token;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

/** API Rastro (SRO). */
final class Rastro extends Servico
{
    private const int LIMITE_OBJETOS = 50;

    /** `GET /srorastro/v1/objetos/{codigoObjeto}`. Padrão: todos os eventos. */
    public function rastrear(
        Token|string $token,
        CodigoRastreamento|string $codigoObjeto,
        ResultadoRastreamento $resultado = ResultadoRastreamento::Todos,
    ): Rastreamento {
        $codigo = Identificadores::codigos([$codigoObjeto])[0] ?? throw ErroValidacao::campo('codigoObjeto', 'informe o código do objeto.');
        $resposta = $this->chamar('GET', '/srorastro/v1/objetos/' . $codigo, 'rastrear objeto', $token, ['resultado' => $resultado->value]);

        return RastroMapeador::objetos($this->leitor($resposta))[0] ?? new Rastreamento($codigo, [], 'Objeto não retornado pela API.', bruto: $resposta->jsonArray());
    }

    /**
     * `GET /srorastro/v1/objetos?codigosObjetos=...` (até 50 objetos). Padrão: só o último evento.
     *
     * @param list<CodigoRastreamento|string> $codigosObjetos
     *
     * @return list<Rastreamento>
     */
    public function rastrearVarios(
        Token|string $token,
        array $codigosObjetos,
        ResultadoRastreamento $resultado = ResultadoRastreamento::Ultimo,
    ): array {
        $codigos = Identificadores::codigos($codigosObjetos);
        if ($codigos === [] || count($codigos) > self::LIMITE_OBJETOS) {
            throw ErroValidacao::campo('codigosObjetos', sprintf('informe de 1 a %d códigos de objeto.', self::LIMITE_OBJETOS));
        }

        $resposta = $this->chamar('GET', '/srorastro/v1/objetos', 'rastrear objetos', $token, [
            'codigosObjetos' => $codigos,
            'resultado' => $resultado->value,
        ]);

        return RastroMapeador::objetos($this->leitor($resposta));
    }
}
