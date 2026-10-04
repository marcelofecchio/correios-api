<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios;

use DateTimeImmutable;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Datas;
use Stringable;

/**
 * Token de autorização gerado pela API Token.
 *
 * O componente **não guarda** o token. Grave-o na sessão ou no cache com `paraArray()` e recupere-o com
 * `deArray()`; antes de gerar outro, confira `estaValido()`.
 */
final readonly class Token implements Stringable
{
    /** @var list<int> */
    public array $apis;

    /**
     * @param array<int> $apis ids das APIs liberadas para o cartão
     */
    public function __construct(
        public string $valor,
        public DateTimeImmutable $emitidoEm,
        public DateTimeImmutable $expiraEm,
        public Ambiente $ambiente,
        public ?string $cartaoPostagem = null,
        public ?string $contrato = null,
        public ?int $diretoriaRegional = null,
        public ?string $cnpj = null,
        array $apis = [],
    ) {
        if (trim($valor) === '') {
            throw ErroValidacao::campo('token', 'o valor do token não pode ser vazio.');
        }
        $this->apis = array_values($apis);
    }

    /**
     * Verdadeiro se faltam mais de `$margemSegundos` para a expiração.
     *
     * @param ?DateTimeImmutable $agora para testes; padrão é a hora atual
     */
    public function estaValido(int $margemSegundos = 300, ?DateTimeImmutable $agora = null): bool
    {
        $agora ??= new DateTimeImmutable('now', Datas::fuso());

        return $this->expiraEm->getTimestamp() - $agora->getTimestamp() > $margemSegundos;
    }

    /** Segundos até a expiração (negativo se já expirou). */
    public function segundosRestantes(?DateTimeImmutable $agora = null): int
    {
        $agora ??= new DateTimeImmutable('now', Datas::fuso());

        return $this->expiraEm->getTimestamp() - $agora->getTimestamp();
    }

    /** Verdadeiro se o id da API está na lista de APIs liberadas. */
    public function permiteApi(int $idApi): bool
    {
        return in_array($idApi, $this->apis, true);
    }

    /**
     * Dados para gravar na sessão ou no cache. Contém o token: trate como informação sensível.
     *
     * @return array{valor: string, emitidoEm: string, expiraEm: string, ambiente: string, cartaoPostagem: ?string, contrato: ?string, diretoriaRegional: ?int, cnpj: ?string, apis: list<int>}
     */
    public function paraArray(): array
    {
        return [
            'valor' => $this->valor,
            'emitidoEm' => $this->emitidoEm->format(DATE_ATOM),
            'expiraEm' => $this->expiraEm->format(DATE_ATOM),
            'ambiente' => $this->ambiente->value,
            'cartaoPostagem' => $this->cartaoPostagem,
            'contrato' => $this->contrato,
            'diretoriaRegional' => $this->diretoriaRegional,
            'cnpj' => $this->cnpj,
            'apis' => $this->apis,
        ];
    }

    /**
     * Reconstrói o token gravado com `paraArray()`.
     *
     * @param array<mixed> $dados
     */
    public static function deArray(array $dados): self
    {
        $texto = static function (string $chave) use ($dados): ?string {
            $valor = $dados[$chave] ?? null;

            return is_scalar($valor) && (string) $valor !== '' ? (string) $valor : null;
        };

        $emitidoEm = Datas::interpretar($texto('emitidoEm'));
        $expiraEm = Datas::interpretar($texto('expiraEm'));
        $ambiente = Ambiente::tryFrom((string) $texto('ambiente'));
        if ($texto('valor') === null || $emitidoEm === null || $expiraEm === null || $ambiente === null) {
            throw ErroValidacao::campo('token', 'dados do token incompletos: esperados valor, emitidoEm, expiraEm e ambiente.');
        }

        $apis = [];
        foreach (is_array($dados['apis'] ?? null) ? $dados['apis'] : [] as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                $apis[] = (int) $id;
            }
        }
        $dr = $texto('diretoriaRegional');

        return new self(
            valor: (string) $texto('valor'),
            emitidoEm: $emitidoEm,
            expiraEm: $expiraEm,
            ambiente: $ambiente,
            cartaoPostagem: $texto('cartaoPostagem'),
            contrato: $texto('contrato'),
            diretoriaRegional: $dr !== null && is_numeric($dr) ? (int) $dr : null,
            cnpj: $texto('cnpj'),
            apis: $apis,
        );
    }

    /** Valor do token, para uso direto no cabeçalho `Authorization: Bearer`. */
    public function __toString(): string
    {
        return $this->valor;
    }

    /**
     * Esconde o valor do token em `var_dump()` e `print_r()`.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['valor' => '***'] + $this->paraArray();
    }
}
