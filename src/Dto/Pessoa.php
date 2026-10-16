<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Dto;

use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Documento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Remetente ou destinatário.
 *
 * - `nome`: nome e sobrenome (ou razão social), de 3 a 50 caracteres;
 * - `documento`: CPF ou CNPJ, com ou sem máscara (guardado só com dígitos);
 * - `telefone`: DDD + 8 dígitos; `celular`: DDD + 9 dígitos;
 * - `contribuinteIcms`: o remetente tem inscrição estadual ativa (contribuinte do ICMS). Desde 02/11/2026 a SEFAZ
 *   não emite DC-e para contribuinte (Rejeição 812): com `true`, o componente recusa `emiteDCe` antes de chamar a
 *   API. `null` = não informado (nada é verificado localmente).
 */
final readonly class Pessoa
{
    public string $nome;
    public ?string $documento;
    public ?string $email;
    public ?string $telefone;
    public ?string $celular;

    public function __construct(
        string $nome,
        public Endereco $endereco,
        ?string $documento = null,
        ?string $email = null,
        ?string $telefone = null,
        ?string $celular = null,
        public ?bool $contribuinteIcms = null,
    ) {
        $nome = Texto::limpar($nome);
        $tamanho = Texto::tamanho($nome);
        if ($tamanho < 3 || $tamanho > 50) {
            throw ErroValidacao::campo('nome', 'deve ter de 3 a 50 caracteres (nome e sobrenome).');
        }
        $this->nome = $nome;

        $this->documento = self::preenchido($documento) ? Documento::normalizar((string) $documento, 'documento') : null;
        $this->email = self::email($email);
        $this->telefone = self::telefone('telefone', $telefone, 10, 'DDD com 2 dígitos + telefone com 8 dígitos');
        $this->celular = self::telefone('celular', $celular, 11, 'DDD com 2 dígitos + celular com 9 dígitos');
    }

    /**
     * Monta a pessoa a partir de um array com as chaves `nome`, `documento`, `email`, `telefone`, `celular`,
     * `contribuinte_icms` (bool) e `endereco` (array no formato de `Endereco::deArray()`).
     *
     * @param array<mixed> $dados
     */
    public static function deArray(array $dados): self
    {
        $texto = static function (string $chave) use ($dados): ?string {
            $valor = $dados[$chave] ?? null;

            return is_scalar($valor) && (string) $valor !== '' ? (string) $valor : null;
        };
        $endereco = $dados['endereco'] ?? null;
        if (!is_array($endereco)) {
            throw ErroValidacao::campo('endereco', 'informe o endereço como array.');
        }

        return new self(
            nome: $texto('nome') ?? '',
            endereco: Endereco::deArray($endereco),
            documento: $texto('documento'),
            email: $texto('email'),
            telefone: $texto('telefone'),
            celular: $texto('celular'),
            contribuinteIcms: self::logico($dados['contribuinte_icms'] ?? $dados['contribuinteIcms'] ?? null),
        );
    }

    public function dddTelefone(): ?string
    {
        return $this->telefone !== null ? substr($this->telefone, 0, 2) : null;
    }

    public function numeroTelefone(): ?string
    {
        return $this->telefone !== null ? substr($this->telefone, 2) : null;
    }

    public function dddCelular(): ?string
    {
        return $this->celular !== null ? substr($this->celular, 0, 2) : null;
    }

    public function numeroCelular(): ?string
    {
        return $this->celular !== null ? substr($this->celular, 2) : null;
    }

    private static function logico(mixed $valor): ?bool
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_bool($valor)) {
            return $valor;
        }
        $logico = is_scalar($valor) ? filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        return $logico ?? throw ErroValidacao::campo('contribuinte_icms', 'informe true ou false.');
    }

    private static function preenchido(?string $valor): bool
    {
        return $valor !== null && trim($valor) !== '';
    }

    private static function email(?string $email): ?string
    {
        if (!self::preenchido($email)) {
            return null;
        }
        $email = trim((string) $email);
        if (strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ErroValidacao::campo('email', sprintf('e-mail "%s" inválido.', $email));
        }

        return $email;
    }

    private static function telefone(string $campo, ?string $valor, int $digitos, string $regra): ?string
    {
        if (!self::preenchido($valor)) {
            return null;
        }
        $numero = Texto::digitos((string) $valor);
        if (strlen($numero) !== $digitos) {
            throw ErroValidacao::campo($campo, sprintf('"%s" inválido: informe %s.', $valor, $regra));
        }

        return $numero;
    }
}
