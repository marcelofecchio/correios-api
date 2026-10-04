<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Http;

/**
 * Requisição a uma API dos Correios, relativa ao host do ambiente.
 *
 * @internal
 */
final readonly class Requisicao
{
    /**
     * @param string                                                $caminho     caminho a partir do host (ex.: `/preco/v1/nacional/03220`)
     * @param array<string, scalar|list<scalar>|null>               $query       listas viram parâmetros repetidos (`a=1&a=2`)
     * @param array<mixed>|null                                     $json        corpo JSON
     * @param string                                                $operacao    descrição usada nas mensagens de erro
     * @param list<int>                                             $sucesso     códigos HTTP considerados sucesso
     * @param array<int, string>                                    $dicas       texto extra na mensagem de erro, por código HTTP
     */
    public function __construct(
        public string $metodo,
        public string $caminho,
        public string $operacao,
        public ?string $autorizacao = null,
        public array $query = [],
        public ?array $json = null,
        public array $sucesso = [200],
        public array $dicas = [],
    ) {}

    /** Monta a query string repetindo os parâmetros de lista. Valores nulos e vazios são omitidos. */
    public function queryString(): string
    {
        $partes = [];
        foreach ($this->query as $nome => $valor) {
            $valores = is_array($valor) ? $valor : [$valor];
            foreach ($valores as $item) {
                if ($item === null || $item === '') {
                    continue;
                }
                $texto = is_bool($item) ? ($item ? 'true' : 'false') : (string) $item;
                $partes[] = rawurlencode($nome) . '=' . rawurlencode($texto);
            }
        }

        return implode('&', $partes);
    }
}
