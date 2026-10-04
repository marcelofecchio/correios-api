<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Enums\Uf;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Cep;
use MarceloFecchio\Correios\Utilitarios\Texto;

/** Endereço nacional, com os limites de tamanho da API Pré-postagem (arquivo 06). */
final readonly class Endereco
{
    public string $cep;
    public string $logradouro;
    public string $numero;
    public ?string $complemento;
    public string $bairro;
    public string $cidade;
    public Uf $uf;
    public string $pais;

    public function __construct(
        string $cep,
        string $logradouro,
        string $numero,
        string $bairro,
        string $cidade,
        Uf|string $uf,
        ?string $complemento = null,
        string $pais = 'BR',
    ) {
        $this->cep = Cep::normalizar($cep, 'endereco.cep');
        $this->logradouro = self::obrigatorio('endereco.logradouro', $logradouro, 50);
        $this->numero = self::numero($numero);
        $this->complemento = self::opcional('endereco.complemento', $complemento, 30);
        $this->bairro = self::obrigatorio('endereco.bairro', $bairro, 30);
        $this->cidade = self::obrigatorio('endereco.cidade', $cidade, 30);
        $this->uf = $uf instanceof Uf ? $uf : (Uf::deTexto($uf) ?? throw ErroValidacao::campo('endereco.uf', sprintf('UF "%s" inválida.', $uf)));
        $this->pais = strtoupper(trim($pais)) ?: 'BR';
    }

    /**
     * Monta o endereço a partir de um array com as chaves `cep`, `logradouro`, `numero`, `complemento`,
     * `bairro`, `cidade` e `uf`.
     *
     * @param array<mixed> $dados
     */
    public static function deArray(array $dados): self
    {
        $texto = static function (string $chave) use ($dados): string {
            $valor = $dados[$chave] ?? '';

            return is_scalar($valor) ? (string) $valor : '';
        };

        return new self(
            cep: $texto('cep'),
            logradouro: $texto('logradouro'),
            numero: $texto('numero'),
            bairro: $texto('bairro'),
            cidade: $texto('cidade'),
            uf: $texto('uf'),
            complemento: $texto('complemento') !== '' ? $texto('complemento') : null,
            pais: $texto('pais') !== '' ? $texto('pais') : 'BR',
        );
    }

    /** `Cidade/UF`. */
    public function cidadeUf(): string
    {
        return $this->cidade . '/' . $this->uf->value;
    }

    private static function numero(string $numero): string
    {
        $numero = Texto::limpar($numero);
        if (preg_match('/^s\/?n$/i', $numero) === 1) {
            return 'S/N';
        }

        return self::obrigatorio('endereco.numero', $numero, 6);
    }

    private static function obrigatorio(string $campo, string $valor, int $maximo): string
    {
        $valor = Texto::limpar($valor);
        if ($valor === '') {
            throw ErroValidacao::campo($campo, 'é obrigatório.');
        }
        if (Texto::tamanho($valor) > $maximo) {
            throw ErroValidacao::campo($campo, sprintf('deve ter no máximo %d caracteres.', $maximo));
        }

        return $valor;
    }

    private static function opcional(string $campo, ?string $valor, int $maximo): ?string
    {
        $valor = Texto::limpar((string) $valor);
        if ($valor === '') {
            return null;
        }
        if (Texto::tamanho($valor) > $maximo) {
            throw ErroValidacao::campo($campo, sprintf('deve ter no máximo %d caracteres.', $maximo));
        }

        return $valor;
    }
}
