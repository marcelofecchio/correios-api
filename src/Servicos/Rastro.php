<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Servicos;

use MarceloFecchio\Correios\Dto\Identificadores;
use MarceloFecchio\Correios\Enums\ResultadoRastreamento;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Mapeadores\Leitor;
use MarceloFecchio\Correios\Mapeadores\RastroMapeador;
use MarceloFecchio\Correios\Respostas\ArDigital;
use MarceloFecchio\Correios\Respostas\Rastreamento;
use MarceloFecchio\Correios\Token;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

/** API Rastro (SRO): eventos de rastreamento e imagem do AR Digital. */
final class Rastro extends Servico
{
    private const int LIMITE_OBJETOS = 50;
    private const int LIMITE_AR_DIGITAL = 10;

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

    /**
     * Imagem do AR Digital de um objeto (`GET /srorastro/v1/ar-digital`). Só existe para objetos postados com o
     * adicional AR Digital e já entregues: confira `disponivel()` antes de usar a imagem.
     */
    public function arDigital(Token|string $token, CodigoRastreamento|string $codigoObjeto): ArDigital
    {
        $codigo = Identificadores::codigos([$codigoObjeto])[0] ?? throw ErroValidacao::campo('codigoObjeto', 'informe o código do objeto.');

        return $this->arDigitalVarios($token, [$codigo])[0];
    }

    /**
     * Imagens do AR Digital de até 10 objetos, na ordem dos códigos informados (códigos repetidos são ignorados).
     * Um objeto sem imagem não lança erro: vem com `disponivel()` false e o motivo em `codigoErro`/`mensagem`.
     *
     * @param list<CodigoRastreamento|string> $codigosObjetos
     *
     * @return list<ArDigital>
     */
    public function arDigitalVarios(Token|string $token, array $codigosObjetos): array
    {
        $codigos = Identificadores::codigos($codigosObjetos);
        if ($codigos === [] || count($codigos) > self::LIMITE_AR_DIGITAL) {
            throw ErroValidacao::campo('codigosObjetos', sprintf('informe de 1 a %d códigos de objeto.', self::LIMITE_AR_DIGITAL));
        }

        $resposta = $this->chamar('GET', '/srorastro/v1/ar-digital', 'consultar AR Digital', $token, ['objetos' => $codigos]);
        $json = $resposta->json();
        if (!is_array($json) || !array_is_list($json)) {
            throw $this->respostaSemCampo($resposta, 'lista de objetos', 'consultar AR Digital');
        }

        $porCodigo = [];
        foreach (Leitor::de($json)->itensRaiz() as $item) {
            $ar = RastroMapeador::arDigital($item);
            $porCodigo[strtoupper($ar->codigoObjeto)] ??= $ar;
        }

        return array_map(
            static fn(string $codigo): ArDigital => $porCodigo[$codigo] ?? new ArDigital($codigo, false, mensagem: 'Objeto não retornado pela API.'),
            $codigos,
        );
    }
}
