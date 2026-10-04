<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;

/** Opções de geração das etiquetas em ZPL. Faz parte de `Configuracao` (campo `etiquetas`). */
final readonly class ConfiguracaoEtiquetas
{
    /** @var array<string, string> */
    public array $logosPorServico;

    /** @var array<string, string> */
    public array $nomesServicos;

    /** @var array<string, string> */
    public array $siglasAdicionais;

    /**
     * @param ?string               $diretorioTemplates diretório com templates próprios; arquivo ausente cai no do pacote
     * @param array<mixed>          $logosPorServico    código ou apelido do serviço → nome do parcial do logo
     * @param array<mixed>          $nomesServicos      código ou apelido do serviço → nome impresso
     * @param ?bool                 $marcaHomologacao   null = automático (imprime em homologação)
     * @param string                $dataMatrixServicosAdicionais campo fixo de 12 caracteres do DataMatrix (valor do sistema antigo)
     * @param array<mixed>          $siglasAdicionais   código do serviço adicional → sigla impressa (ex.: `['019' => 'VD']`).
     *                                                  Sem entrada: valor declarado → `VD`, aviso de recebimento → `AR`.
     */
    public function __construct(
        public Resolucao $resolucao = Resolucao::Dpi203,
        public ?string $diretorioTemplates = null,
        public bool $logoCorreios = true,
        array $logosPorServico = [],
        array $nomesServicos = [],
        public ?bool $marcaHomologacao = null,
        public string $dataMatrixServicosAdicionais = '250000000000',
        array $siglasAdicionais = [],
    ) {
        if ($diretorioTemplates !== null && !is_dir($diretorioTemplates)) {
            throw new ErroConfiguracao(sprintf('Diretório de templates "%s" não encontrado.', $diretorioTemplates));
        }
        if (preg_match('/^\d{12}$/', $dataMatrixServicosAdicionais) !== 1) {
            throw new ErroConfiguracao('etiquetas.dataMatrixServicosAdicionais deve ter exatamente 12 dígitos.');
        }

        $this->logosPorServico = self::mapa('logosPorServico', $logosPorServico);
        $this->nomesServicos = self::mapa('nomesServicos', $nomesServicos);
        $this->siglasAdicionais = self::mapa('siglasAdicionais', $siglasAdicionais);
    }

    /**
     * Chaves: `resolucao` (203 ou 300), `diretorio_templates`, `logo_correios`, `logos_por_servico`,
     * `nomes_servicos`, `marca_homologacao`, `datamatrix_servicos_adicionais`, `siglas_adicionais`
     * (também aceitas em camelCase).
     *
     * @param array<mixed> $config
     */
    public static function deArray(array $config): self
    {
        $valor = static fn(string $camel, string $snake): mixed => $config[$camel] ?? $config[$snake] ?? null;

        $resolucao = $valor('resolucao', 'resolucao');
        if ($resolucao !== null && !$resolucao instanceof Resolucao) {
            $resolucao = is_numeric($resolucao) ? Resolucao::tryFrom((int) $resolucao) : null;
            if ($resolucao === null) {
                throw new ErroConfiguracao('etiquetas.resolucao deve ser 203 ou 300.');
            }
        }

        $diretorio = $valor('diretorioTemplates', 'diretorio_templates');
        $logo = $valor('logoCorreios', 'logo_correios');
        $marca = $valor('marcaHomologacao', 'marca_homologacao');
        $dataMatrix = $valor('dataMatrixServicosAdicionais', 'datamatrix_servicos_adicionais');

        return new self(
            resolucao: $resolucao ?? Resolucao::Dpi203,
            diretorioTemplates: is_string($diretorio) && $diretorio !== '' ? $diretorio : null,
            logoCorreios: $logo === null ? true : (bool) $logo,
            logosPorServico: self::arrayOuVazio($valor('logosPorServico', 'logos_por_servico')),
            nomesServicos: self::arrayOuVazio($valor('nomesServicos', 'nomes_servicos')),
            marcaHomologacao: $marca === null ? null : (bool) $marca,
            dataMatrixServicosAdicionais: is_scalar($dataMatrix) ? (string) $dataMatrix : '250000000000',
            siglasAdicionais: self::arrayOuVazio($valor('siglasAdicionais', 'siglas_adicionais')),
        );
    }

    /**
     * @param array<mixed> $mapa
     *
     * @return array<string, string>
     */
    private static function mapa(string $nome, array $mapa): array
    {
        $limpo = [];
        foreach ($mapa as $chave => $valor) {
            if (!is_scalar($valor) || trim((string) $valor) === '') {
                throw new ErroConfiguracao(sprintf('etiquetas.%s: o valor da chave "%s" deve ser um texto.', $nome, $chave));
            }
            $limpo[trim((string) $chave)] = trim((string) $valor);
        }

        return $limpo;
    }

    /** @return array<mixed> */
    private static function arrayOuVazio(mixed $valor): array
    {
        return is_array($valor) ? $valor : [];
    }
}
