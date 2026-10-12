<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios;

use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Etiquetas\ConfiguracaoEtiquetas;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Documento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Configuração do componente. Tudo vem da aplicação: o componente não lê `.env`, arquivos nem variáveis globais.
 *
 * Atenção: em **homologação** o `usuario` é o **CNPJ** (14 dígitos); em **produção**, o login do Meu Correios.
 * O componente envia exatamente o que for informado e não deduz um a partir do outro.
 */
final readonly class Configuracao
{
    public string $usuario;
    public string $codigoAcesso;
    public string $cnpj;
    public string $cartaoPostagem;
    public ?string $contrato;
    public ConfiguracaoEtiquetas $etiquetas;
    public ?string $urlBase;

    /** @var array<string, string> */
    public array $servicos;

    /**
     * @param array<string, string> $servicos apelido → código do serviço (ex.: `['sedex' => '03220']`)
     * @param float                 $timeout  segundos (aplicado quando o componente cria o cliente Guzzle)
     * @param ?string               $urlBase  sobrescreve o host do ambiente (útil em testes)
     * @param bool                  $logarCorpo grava no log (DEBUG) o corpo das requisições e respostas, já mascarado;
     *                                          false = só método, URL, status e duração
     */
    public function __construct(
        public Ambiente $ambiente,
        string $usuario,
        string $codigoAcesso,
        string $cnpj,
        string $cartaoPostagem,
        ?string $contrato = null,
        public ?int $diretoriaRegional = null,
        array $servicos = [],
        public ?Pessoa $remetentePadrao = null,
        ?ConfiguracaoEtiquetas $etiquetas = null,
        public float $timeout = 30.0,
        ?string $urlBase = null,
        public bool $logarCorpo = true,
    ) {
        $this->usuario = trim($usuario);
        if ($this->usuario === '') {
            throw new ErroConfiguracao('Informe o usuário da API (em homologação, o CNPJ com 14 dígitos; em produção, o login do Meu Correios).');
        }
        $this->codigoAcesso = trim($codigoAcesso);
        if ($this->codigoAcesso === '') {
            throw new ErroConfiguracao(sprintf('Informe o código de acesso gerado no portal CWS do mesmo ambiente (%s).', $ambiente->urlPortal()));
        }

        $cnpjDigitos = Texto::digitos($cnpj);
        if (!Documento::cnpjValido($cnpjDigitos)) {
            throw new ErroConfiguracao('Informe o CNPJ do contrato com 14 dígitos válidos.');
        }
        $this->cnpj = $cnpjDigitos;

        $cartao = trim($cartaoPostagem);
        if (preg_match('/^\d{1,10}$/', $cartao) !== 1) {
            throw new ErroConfiguracao('Informe o cartão de postagem como texto com até 10 dígitos (mantenha os zeros à esquerda).');
        }
        $this->cartaoPostagem = $cartao;

        $contrato = trim((string) $contrato);
        if ($contrato !== '' && preg_match('/^\d{1,15}$/', $contrato) !== 1) {
            throw new ErroConfiguracao('O número do contrato deve conter só dígitos.');
        }
        $this->contrato = $contrato !== '' ? $contrato : null;

        if ($diretoriaRegional !== null && $diretoriaRegional <= 0) {
            throw new ErroConfiguracao('A diretoria regional (DR) deve ser um número positivo.');
        }

        $mapa = [];
        foreach ($servicos as $apelido => $codigo) {
            $codigo = trim($codigo);
            if (preg_match('/^\d{5}$/', $codigo) !== 1) {
                throw new ErroConfiguracao(sprintf('servicos["%s"]: o código do serviço deve ser um texto com 5 dígitos (ex.: "03220").', $apelido));
            }
            $mapa[strtolower(trim($apelido))] = $codigo;
        }
        $this->servicos = $mapa;

        $this->etiquetas = $etiquetas ?? new ConfiguracaoEtiquetas();

        if ($timeout <= 0) {
            throw new ErroConfiguracao('O timeout deve ser maior que zero (segundos).');
        }

        $urlBase = trim((string) $urlBase);
        if ($urlBase !== '' && preg_match('#^https?://[^\s/]+#i', $urlBase) !== 1) {
            throw new ErroConfiguracao(sprintf('urlBase "%s" inválida: informe uma URL http(s).', $urlBase));
        }
        $this->urlBase = $urlBase !== '' ? rtrim($urlBase, '/') : null;
    }

    /**
     * Monta a configuração a partir de um array (ex.: o `config/correios.php` do Laravel).
     *
     * Chaves (snake_case ou camelCase): `ambiente`, `usuario`, `codigo_acesso`, `cnpj`, `cartao_postagem`,
     * `contrato`, `diretoria_regional`, `servicos`, `remetente_padrao`, `etiquetas`, `timeout`, `url_base`,
     * `log_corpo`.
     *
     * @param array<mixed> $config
     */
    public static function deArray(array $config): self
    {
        $valor = static fn(string $camel, string $snake): mixed => $config[$camel] ?? $config[$snake] ?? null;
        $texto = static function (string $camel, string $snake, bool $obrigatorio) use ($valor): ?string {
            $v = $valor($camel, $snake);
            if ($v === null || $v === '') {
                if ($obrigatorio) {
                    throw new ErroConfiguracao(sprintf('Configuração incompleta: a chave "%s" é obrigatória.', $snake));
                }

                return null;
            }
            if (!is_scalar($v)) {
                throw new ErroConfiguracao(sprintf('Configuração inválida: a chave "%s" deve ser um texto.', $snake));
            }

            return (string) $v;
        };

        $ambienteBruto = $valor('ambiente', 'ambiente');
        $ambiente = match (true) {
            $ambienteBruto instanceof Ambiente => $ambienteBruto,
            is_string($ambienteBruto) => Ambiente::tryFrom(strtoupper(trim($ambienteBruto))) ?? Ambiente::deTexto($ambienteBruto),
            default => null,
        };
        if ($ambiente === null) {
            throw new ErroConfiguracao('Configuração inválida: "ambiente" deve ser "homologacao" ou "producao".');
        }

        $servicos = $valor('servicos', 'servicos') ?? [];
        if (!is_array($servicos)) {
            throw new ErroConfiguracao('Configuração inválida: "servicos" deve ser um mapa apelido → código.');
        }
        $mapaServicos = [];
        foreach ($servicos as $apelido => $codigo) {
            if (!is_scalar($codigo) || $codigo === '') {
                continue;
            }
            $mapaServicos[(string) $apelido] = (string) $codigo;
        }

        $remetente = $valor('remetentePadrao', 'remetente_padrao');
        if ($remetente !== null && !$remetente instanceof Pessoa) {
            if (!is_array($remetente)) {
                throw new ErroConfiguracao('Configuração inválida: "remetente_padrao" deve ser um array ou uma Pessoa.');
            }
            try {
                $remetente = Pessoa::deArray($remetente);
            } catch (ErroValidacao $e) {
                throw new ErroConfiguracao('Configuração inválida em "remetente_padrao": ' . $e->getMessage(), 0, $e);
            }
        }

        $etiquetas = $valor('etiquetas', 'etiquetas');
        if ($etiquetas !== null && !$etiquetas instanceof ConfiguracaoEtiquetas) {
            if (!is_array($etiquetas)) {
                throw new ErroConfiguracao('Configuração inválida: "etiquetas" deve ser um array.');
            }
            $etiquetas = ConfiguracaoEtiquetas::deArray($etiquetas);
        }

        $dr = $texto('diretoriaRegional', 'diretoria_regional', false);
        if ($dr !== null && preg_match('/^\d+$/', $dr) !== 1) {
            throw new ErroConfiguracao('Configuração inválida: "diretoria_regional" deve ser um número.');
        }
        $timeout = $texto('timeout', 'timeout', false);
        if ($timeout !== null && !is_numeric($timeout)) {
            throw new ErroConfiguracao('Configuração inválida: "timeout" deve ser um número de segundos.');
        }

        $logarCorpo = $valor('logarCorpo', 'log_corpo');
        if ($logarCorpo !== null && !is_bool($logarCorpo)) {
            $logarCorpo = is_scalar($logarCorpo) ? filter_var($logarCorpo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
            if ($logarCorpo === null) {
                throw new ErroConfiguracao('Configuração inválida: "log_corpo" deve ser verdadeiro ou falso.');
            }
        }

        return new self(
            ambiente: $ambiente,
            usuario: (string) $texto('usuario', 'usuario', true),
            codigoAcesso: (string) $texto('codigoAcesso', 'codigo_acesso', true),
            cnpj: (string) $texto('cnpj', 'cnpj', true),
            cartaoPostagem: (string) $texto('cartaoPostagem', 'cartao_postagem', true),
            contrato: $texto('contrato', 'contrato', false),
            diretoriaRegional: $dr !== null ? (int) $dr : null,
            servicos: $mapaServicos,
            remetentePadrao: $remetente,
            etiquetas: $etiquetas,
            timeout: $timeout !== null ? (float) $timeout : 30.0,
            urlBase: $texto('urlBase', 'url_base', false),
            logarCorpo: $logarCorpo ?? true,
        );
    }

    /** Host usado nas requisições: `urlBase`, se informada, ou o host do ambiente. */
    public function urlApi(): string
    {
        return $this->urlBase ?? $this->ambiente->urlBase();
    }

    /**
     * Resolve um apelido (`sedex`) ou código (`03220`) para o código do serviço.
     *
     * @throws ErroValidacao se não for um apelido configurado nem um código de 5 dígitos
     */
    public function resolverServico(string $apelidoOuCodigo): string
    {
        $chave = strtolower(trim($apelidoOuCodigo));
        if (isset($this->servicos[$chave])) {
            return $this->servicos[$chave];
        }
        if (preg_match('/^\d{5}$/', $chave) === 1) {
            return $chave;
        }

        throw ErroValidacao::campo('servico', sprintf(
            '"%s" não é um apelido configurado (%s) nem um código de serviço com 5 dígitos.',
            $apelidoOuCodigo,
            $this->servicos === [] ? 'nenhum apelido configurado' : implode(', ', array_keys($this->servicos)),
        ));
    }

    /**
     * Esconde o código de acesso em `var_dump()` e `print_r()`.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'ambiente' => $this->ambiente,
            'usuario' => $this->usuario,
            'codigoAcesso' => '***',
            'cnpj' => $this->cnpj,
            'cartaoPostagem' => $this->cartaoPostagem,
            'contrato' => $this->contrato,
            'diretoriaRegional' => $this->diretoriaRegional,
            'servicos' => $this->servicos,
            'timeout' => $this->timeout,
            'urlBase' => $this->urlBase,
        ];
    }
}
