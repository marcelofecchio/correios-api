<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;

/**
 * Serviço adicional da pré-postagem (`listaServicoAdicional`).
 *
 * O código do valor declarado **muda por serviço** (`019` no SEDEX e `064` no PAC, nos testes) e por
 * isso é sempre informado pela aplicação. Consulte os adicionais aceitos em `$correios->preco()->servicosAdicionais()`.
 */
final readonly class ServicoAdicional
{
    /** AR Convencional (arquivo 06: "O objeto consultado não possui o serviço AR Convencional 001 associado"). */
    public const string AVISO_RECEBIMENTO = '001';

    public string $codigo;

    public function __construct(string $codigo, public ?int $valorDeclaradoCentavos = null)
    {
        $codigo = trim($codigo);
        if (preg_match('/^\d{3}$/', $codigo) !== 1) {
            throw ErroValidacao::campo('servicoAdicional.codigo', sprintf('"%s" inválido: informe o código com 3 dígitos.', $codigo));
        }
        if ($valorDeclaradoCentavos !== null && $valorDeclaradoCentavos <= 0) {
            throw ErroValidacao::campo('servicoAdicional.valorDeclarado', 'deve ser positivo.');
        }
        $this->codigo = $codigo;
    }

    /** Aviso de recebimento (AR Convencional, código 001). Obrigatório para emitir o AR. */
    public static function avisoRecebimento(): self
    {
        return new self(self::AVISO_RECEBIMENTO);
    }

    /**
     * Valor declarado. O código depende do serviço (ex.: `019` no SEDEX, `064` no PAC).
     *
     * @param int $valorCentavos valor declarado em centavos
     */
    public static function valorDeclarado(string $codigo, int $valorCentavos): self
    {
        return new self($codigo, $valorCentavos);
    }

    public function ehValorDeclarado(): bool
    {
        return $this->valorDeclaradoCentavos !== null;
    }
}
