<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;
use MarceloFecchio\Correios\Utilitarios\Texto;

/** Item da declaração de conteúdo (`itensDeclaracaoConteudo` da pré-postagem). */
final readonly class Item
{
    public string $descricao;
    public ?string $ncm;

    /**
     * @param int $valorUnitarioCentavos valor unitário em centavos (R$ 150,00 = 15000)
     */
    public function __construct(
        string $descricao,
        public int $quantidade,
        public int $valorUnitarioCentavos,
        public ?int $pesoGramas = null,
        ?string $ncm = null,
    ) {
        $descricao = Texto::limpar($descricao);
        $tamanho = Texto::tamanho($descricao);
        if ($tamanho < 2 || $tamanho > 60) {
            throw ErroValidacao::campo('item.descricao', 'deve ter de 2 a 60 caracteres.');
        }
        if (preg_match('/^(.)\1+$/u', $descricao) === 1) {
            throw ErroValidacao::campo('item.descricao', 'não pode ser formada só por caracteres iguais.');
        }
        if ($tamanho === 2 && preg_match('/^\p{L}{2}$/u', $descricao) !== 1) {
            throw ErroValidacao::campo('item.descricao', 'com 2 caracteres, os dois devem ser letras.');
        }
        $this->descricao = $descricao;

        if ($quantidade < 1 || $quantidade > 99_999_999_999) {
            throw ErroValidacao::campo('item.quantidade', 'deve ser um inteiro positivo com até 11 dígitos.');
        }
        if ($valorUnitarioCentavos < 0) {
            throw ErroValidacao::campo('item.valor', 'não pode ser negativo.');
        }
        if ($pesoGramas !== null && $pesoGramas <= 0) {
            throw ErroValidacao::campo('item.peso', 'deve ser positivo, em gramas.');
        }

        $ncm = Texto::digitos((string) $ncm);
        if ($ncm !== '' && strlen($ncm) > 8) {
            throw ErroValidacao::campo('item.ncm', 'deve ter até 8 dígitos.');
        }
        $this->ncm = $ncm !== '' ? $ncm : null;
    }

    /** Cria o item com o valor em texto (`'150,00'` ou `'150.00'`). */
    public static function comValor(string $descricao, int $quantidade, string $valorUnitario, ?int $pesoGramas = null, ?string $ncm = null): self
    {
        return new self($descricao, $quantidade, Dinheiro::paraCentavos($valorUnitario, 'item.valor'), $pesoGramas, $ncm);
    }

    public function valorTotalCentavos(): int
    {
        return $this->valorUnitarioCentavos * $this->quantidade;
    }
}
