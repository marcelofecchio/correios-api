<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use DateTimeImmutable;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Datas;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;

/**
 * Leitura defensiva do JSON da API: cada método devolve o tipo esperado ou null, sem lançar erro
 * por campo ausente ou com tipo diferente.
 *
 * @internal
 */
final readonly class Leitor
{
    /** @param array<mixed> $dados */
    public function __construct(private array $dados) {}

    public static function de(mixed $valor): self
    {
        return new self(is_array($valor) ? $valor : []);
    }

    /** @return array<mixed> */
    public function bruto(): array
    {
        return $this->dados;
    }

    public function tem(string $chave): bool
    {
        return array_key_exists($chave, $this->dados) && $this->dados[$chave] !== null;
    }

    /** Texto; números viram texto. Vazio vira null. */
    public function texto(string $chave): ?string
    {
        $valor = $this->dados[$chave] ?? null;
        if (is_string($valor)) {
            return trim($valor) !== '' ? $valor : null;
        }
        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        return null;
    }

    public function inteiro(string $chave): ?int
    {
        $valor = $this->dados[$chave] ?? null;
        if (is_int($valor)) {
            return $valor;
        }
        if (is_float($valor) && floor($valor) === $valor) {
            return (int) $valor;
        }
        if (is_string($valor) && preg_match('/^\s*-?\d+\s*$/', $valor) === 1) {
            return (int) $valor;
        }

        return null;
    }

    public function numero(string $chave): int|float|null
    {
        $valor = $this->dados[$chave] ?? null;

        return is_int($valor) || is_float($valor) ? $valor : null;
    }

    /** Valor monetário em centavos, aceitando texto (`"19,92"`) ou número (`19.92`). */
    public function centavos(string $chave): ?int
    {
        $valor = $this->dados[$chave] ?? null;
        if (is_int($valor) || is_float($valor)) {
            return Dinheiro::numeroParaCentavos($valor);
        }
        if (is_string($valor) && trim($valor) !== '') {
            try {
                return Dinheiro::paraCentavos($valor, $chave);
            } catch (ErroValidacao) {
                return null;
            }
        }

        return null;
    }

    public function data(string $chave): ?DateTimeImmutable
    {
        return Datas::interpretar($this->dados[$chave] ?? null);
    }

    public function booleano(string $chave): ?bool
    {
        $valor = $this->dados[$chave] ?? null;

        return match (true) {
            is_bool($valor) => $valor,
            in_array($valor, ['S', 's', 'true', '1', 1], true) => true,
            in_array($valor, ['N', 'n', 'false', '0', 0], true) => false,
            default => null,
        };
    }

    public function filho(string $chave): self
    {
        return self::de($this->dados[$chave] ?? null);
    }

    /**
     * Lista de objetos. Um objeto único (array associativo) vira uma lista com um elemento.
     *
     * @return list<self>
     */
    public function lista(string $chave): array
    {
        $valor = $this->dados[$chave] ?? null;
        if (!is_array($valor) || $valor === []) {
            return [];
        }
        if (!array_is_list($valor)) {
            return [new self($valor)];
        }

        $itens = [];
        foreach ($valor as $item) {
            if (is_array($item)) {
                $itens[] = new self($item);
            }
        }

        return $itens;
    }

    /** @return list<string> */
    public function textos(string $chave): array
    {
        $valor = $this->dados[$chave] ?? null;
        if (is_string($valor) && trim($valor) !== '') {
            return [$valor];
        }
        if (!is_array($valor)) {
            return [];
        }

        $textos = [];
        foreach ($valor as $item) {
            if (is_scalar($item) && trim((string) $item) !== '') {
                $textos[] = (string) $item;
            }
        }

        return $textos;
    }

    /**
     * Lista de objetos na raiz (resposta que é um array JSON).
     *
     * @return list<self>
     */
    public function itensRaiz(): array
    {
        if (!array_is_list($this->dados)) {
            return [$this];
        }

        $itens = [];
        foreach ($this->dados as $item) {
            if (is_array($item)) {
                $itens[] = new self($item);
            }
        }

        return $itens;
    }
}
