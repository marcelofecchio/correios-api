<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Excecoes\ErroTemplate;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Preenche templates ZPL.
 *
 * Marcadores:
 * - `{{nome}}` — valor escapado para campos com `^FH` (`_` → `_5F`, `^` → `_5E`, `~` → `_7E`, `\` → `_5C`);
 * - `{{{nome}}}` — valor cru, só para dados já validados (códigos de barras e DataMatrix);
 * - `{{>nome}}` — parcial: conteúdo de `parciais/<arquivo>.zpl`, ou nada se o parcial não for informado.
 *
 * Ordem: carrega o template da resolução → inclui os parciais → troca os marcadores em uma única passada →
 * remove as linhas `^FX` → lança `ErroTemplate` se sobrar `{{` → normaliza as quebras de linha para `\n`.
 *
 * Acentuação: os templates usam `^CI28` (UTF-8); os valores são gravados em UTF-8.
 *
 * Resolução: os templates do pacote estão em 203 dpi. Em 300 dpi o renderizador usa
 * `300dpi/<template>.zpl` se existir; senão escala o template de 203 dpi (×1,5). Parciais seguem a mesma regra
 * (`300dpi/parciais/`), o que permite logotipos próprios para 300 dpi.
 */
final class RenderizadorZpl
{
    private readonly string $diretorioPacote;

    /**
     * @param ?string $diretorioTemplates diretório da aplicação; arquivo ausente nele cai no do pacote
     */
    public function __construct(
        private readonly ?string $diretorioTemplates = null,
        private readonly Resolucao $resolucaoPadrao = Resolucao::Dpi203,
    ) {
        $this->diretorioPacote = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'templates';
    }

    /**
     * @param string                 $template  nome do template (`postagem`) ou caminho de um arquivo `.zpl`
     * @param array<string, string>  $valores   marcador => valor ainda sem escape
     * @param array<string, ?string> $parciais  marcador de parcial => nome do arquivo parcial (null = omitir)
     */
    public function renderizar(string $template, array $valores, array $parciais = [], ?Resolucao $resolucao = null): string
    {
        $resolucao ??= $this->resolucaoPadrao;

        $zpl = $this->carregar($template, $resolucao, false);

        $zpl = preg_replace_callback('/\{\{>\s*([A-Za-z0-9_]+)\s*\}\}/', function (array $m) use ($parciais, $resolucao): string {
            $arquivo = $parciais[$m[1]] ?? null;
            if ($arquivo === null || trim($arquivo) === '') {
                return '';
            }

            return rtrim($this->carregar($arquivo, $resolucao, true), "\r\n");
        }, $zpl) ?? $zpl;

        $zpl = preg_replace_callback('/\{\{\{\s*([A-Za-z0-9_]+)\s*\}\}\}|\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', static function (array $m) use ($valores): string {
            // {{{nome}}} preenche só o grupo 1; {{nome}} preenche o grupo 2
            $cru = !isset($m[2]);
            $nome = $m[2] ?? $m[1];
            if (!array_key_exists($nome, $valores)) {
                throw new ErroTemplate(sprintf('O marcador "%s" do template não recebeu valor.', $nome));
            }
            $valor = Texto::semControle($valores[$nome]);
            if ($cru) {
                if (preg_match('/[\^~{}]/', $valor) === 1) {
                    throw new ErroTemplate(sprintf('O valor cru do marcador "%s" não pode conter "^", "~", "{" nem "}".', $nome));
                }

                return $valor;
            }

            return self::escapar($valor);
        }, $zpl) ?? $zpl;

        $linhas = preg_split('/\r\n|\r|\n/', $zpl) ?: [];
        $linhas = array_filter($linhas, static fn(string $linha): bool => !str_starts_with(ltrim($linha), '^FX') && trim($linha) !== '');
        $zpl = implode("\n", $linhas) . "\n";

        if (str_contains($zpl, '{{')) {
            throw new ErroTemplate('Sobrou um marcador "{{...}}" sem substituição no ZPL gerado.');
        }
        if (!str_starts_with($zpl, '^XA') || !str_ends_with(rtrim($zpl), '^XZ')) {
            throw new ErroTemplate(sprintf('O template "%s" deve começar com ^XA e terminar com ^XZ.', $template));
        }

        return $zpl;
    }

    /**
     * Escape para campos com `^FH` (indicador hexadecimal `_`). `strtr` troca tudo em uma passada, então o
     * `_` gerado por um escape não é escapado de novo. Chaves também viram hexadecimal, para que um valor
     * com `{{` não seja confundido com marcador.
     */
    public static function escapar(string $valor): string
    {
        return strtr($valor, ['_' => '_5F', '^' => '_5E', '~' => '_7E', '\\' => '_5C', '{' => '_7B', '}' => '_7D']);
    }

    private function carregar(string $nome, Resolucao $resolucao, bool $parcial): string
    {
        if (str_ends_with(strtolower($nome), '.zpl') && is_file($nome)) {
            return $this->ler($nome, $resolucao, true);
        }

        $arquivo = preg_replace('/\.zpl$/i', '', $nome) . '.zpl';
        if (preg_match('/^[A-Za-z0-9_-]+\.zpl$/', $arquivo) !== 1) {
            throw new ErroTemplate(sprintf('Nome de template inválido: "%s".', $nome));
        }
        $relativo = ($parcial ? 'parciais' . DIRECTORY_SEPARATOR : '') . $arquivo;

        foreach ($this->diretorios() as $diretorio) {
            if ($resolucao !== Resolucao::Dpi203) {
                $especifico = $diretorio . DIRECTORY_SEPARATOR . $resolucao->value . 'dpi' . DIRECTORY_SEPARATOR . $relativo;
                if (is_file($especifico)) {
                    return $this->ler($especifico, $resolucao, false);
                }
            }
        }
        foreach ($this->diretorios() as $diretorio) {
            $base = $diretorio . DIRECTORY_SEPARATOR . $relativo;
            if (is_file($base)) {
                return $this->ler($base, $resolucao, true);
            }
        }

        throw new ErroTemplate(sprintf('Template "%s" não encontrado em %s.', $relativo, implode(' nem em ', $this->diretorios())));
    }

    /** @return list<string> */
    private function diretorios(): array
    {
        return $this->diretorioTemplates !== null
            ? [rtrim($this->diretorioTemplates, '/\\'), $this->diretorioPacote]
            : [$this->diretorioPacote];
    }

    private function ler(string $caminho, Resolucao $resolucao, bool $escalar): string
    {
        $conteudo = @file_get_contents($caminho);
        if ($conteudo === false) {
            throw new ErroTemplate(sprintf('Não foi possível ler o template "%s".', $caminho));
        }
        if (str_starts_with($conteudo, "\u{FEFF}")) {
            $conteudo = substr($conteudo, 3);
        }

        return $escalar ? EscalaZpl::aplicar($conteudo, $resolucao->fator()) : $conteudo;
    }
}
