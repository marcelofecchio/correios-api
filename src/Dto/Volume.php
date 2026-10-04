<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;

/**
 * Peso (gramas), formato e medidas (centímetros) do objeto.
 *
 * Só as regras documentadas são validadas: valores positivos, medidas com até 3 dígitos e peso com até
 * 6 dígitos; caixa/pacote exige altura, largura e comprimento; cilindro/rolo exige comprimento e diâmetro.
 * Os limites mínimos e máximos de cada serviço ficam a cargo da API.
 */
final readonly class Volume
{
    public function __construct(
        public int $pesoGramas,
        public FormatoObjeto $formato = FormatoObjeto::CaixaPacote,
        public ?int $comprimento = null,
        public ?int $largura = null,
        public ?int $altura = null,
        public ?int $diametro = null,
    ) {
        if ($pesoGramas < 1 || $pesoGramas > 999_999) {
            throw ErroValidacao::campo('volume.pesoGramas', 'deve ser positivo e ter até 6 dígitos (gramas).');
        }

        foreach (['comprimento' => $comprimento, 'largura' => $largura, 'altura' => $altura, 'diametro' => $diametro] as $campo => $valor) {
            if ($valor !== null && ($valor < 1 || $valor > 999)) {
                throw ErroValidacao::campo('volume.' . $campo, 'deve ser positivo e ter até 3 dígitos (centímetros).');
            }
        }

        $exigidos = match ($formato) {
            FormatoObjeto::CaixaPacote => ['altura' => $altura, 'largura' => $largura, 'comprimento' => $comprimento],
            FormatoObjeto::CilindroRolo => ['comprimento' => $comprimento, 'diametro' => $diametro],
            FormatoObjeto::Envelope => [],
        };
        foreach ($exigidos as $campo => $valor) {
            if ($valor === null) {
                throw ErroValidacao::campo('volume.' . $campo, sprintf('é obrigatório no formato %s.', $formato->name));
            }
        }
    }
}
