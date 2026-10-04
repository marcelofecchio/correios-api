<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Respostas\PrePostagemCriada;
use MarceloFecchio\Correios\Utilitarios\Cep;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;
use MarceloFecchio\Correios\Utilitarios\Documento;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Gera etiquetas em ZPL a partir de templates. Não chama a API, não recebe token, não grava arquivos e não
 * imprime: só devolve `EtiquetaZpl`. Mesma entrada → mesma saída, byte a byte.
 *
 * A etiqueta de declaração de conteúdo ainda não está disponível nesta versão: use a DCe emitida pelos
 * Correios (`emiteDCe`) e imprima a DACE com `$correios->dce()->imprimirDace()`.
 */
final class GeradorEtiquetas
{
    /** Limites (em caracteres) dos marcadores da etiqueta de postagem — guia, seção 8.3. */
    private const array LIMITES_POSTAGEM = [
        'linha_nf' => 16,
        'contrato' => 10,
        'volume' => 7,
        'peso_gramas' => 6,
        'nome_servico' => 22,
        'siglas_adicionais' => 12,
        'destinatario_nome' => 50,
        'destinatario_endereco' => 90,
        'destinatario_bairro' => 30,
        'destinatario_cidade_uf' => 34,
        'remetente_nome' => 50,
        'remetente_endereco' => 90,
        'remetente_bairro' => 30,
        'remetente_cidade_uf' => 34,
        'observacoes' => 110,
    ];

    /** Limites dos marcadores do DANFE — guia, seção 7.3. */
    private const array LIMITES_DANFE = [
        'emitente_nome' => 50,
        'emitente_ie' => 15,
        'emitente_endereco' => 60,
        'linha_protocolo' => 68,
        'destinatario_nome' => 50,
        'destinatario_documento' => 45,
        'destinatario_endereco_linha1' => 68,
        'destinatario_endereco_linha2' => 68,
        'observacoes' => 180,
    ];

    private readonly RenderizadorZpl $renderizador;

    public function __construct(
        private readonly Configuracao $configuracao,
        ?RenderizadorZpl $renderizador = null,
    ) {
        $this->renderizador = $renderizador ?? new RenderizadorZpl(
            $configuracao->etiquetas->diretorioTemplates,
            $configuracao->etiquetas->resolucao,
        );
    }

    /** Etiqueta de postagem 10x15 cm. */
    public function postagem(DadosEtiquetaPostagem $dados, ?Resolucao $resolucao = null): EtiquetaZpl
    {
        $resolucao ??= $this->configuracao->etiquetas->resolucao;
        $config = $this->configuracao;
        $etiquetas = $config->etiquetas;

        $codigoServico = $config->resolverServico($dados->codigoServico);
        $remetente = $dados->remetente;
        $destinatario = $dados->destinatario;

        $dataMatrix = new DataMatrixCorreios(
            cepDestino: $destinatario->endereco->cep,
            cepOrigem: $remetente->endereco->cep,
            codigoObjeto: $dados->codigoObjeto,
            cartaoPostagem: $dados->cartaoPostagem ?? $config->cartaoPostagem,
            codigoServico: $codigoServico,
            numeroLogradouro: $destinatario->endereco->numero,
            complemento: (string) $destinatario->endereco->complemento,
            servicosAdicionais: $etiquetas->dataMatrixServicosAdicionais,
        );

        $linhaNf = match (true) {
            $dados->numeroNotaFiscal !== null => 'NF: ' . $dados->numeroNotaFiscal,
            $dados->numeroPedido !== null => 'Pedido: ' . $dados->numeroPedido,
            default => '',
        };

        $valores = self::cortar([
            'linha_nf' => $linhaNf,
            'contrato' => (string) ($dados->contrato ?? $config->contrato),
            'volume' => $dados->volume,
            'peso_gramas' => (string) $dados->pesoGramas,
            'nome_servico' => $dados->nomeServico ?? $this->nomeServico($dados->codigoServico, $codigoServico),
            'siglas_adicionais' => implode(' ', $dados->siglasAdicionais),
            'destinatario_nome' => $destinatario->nome,
            'destinatario_endereco' => Texto::juntar(', ', $destinatario->endereco->logradouro, $destinatario->endereco->numero, $destinatario->endereco->complemento),
            'destinatario_bairro' => $destinatario->endereco->bairro,
            'destinatario_cep_hifen' => Cep::formatar($destinatario->endereco->cep),
            'destinatario_cidade_uf' => $destinatario->endereco->cidadeUf(),
            'remetente_nome' => $remetente->nome,
            'remetente_endereco' => Texto::juntar(', ', $remetente->endereco->logradouro, $remetente->endereco->numero, $remetente->endereco->complemento),
            'remetente_bairro' => $remetente->endereco->bairro,
            'remetente_cep_hifen' => Cep::formatar($remetente->endereco->cep),
            'remetente_cidade_uf' => $remetente->endereco->cidadeUf(),
            'observacoes' => (string) $dados->observacoes,
        ], self::LIMITES_POSTAGEM);

        $valores['datamatrix'] = $dataMatrix->gerar();
        $valores['codigo_objeto'] = $dados->codigoObjeto->valor;
        $valores['destinatario_cep'] = $destinatario->endereco->cep;

        $homologacao = $etiquetas->marcaHomologacao ?? ($config->ambiente === Ambiente::Homologacao);
        $zpl = $this->renderizador->renderizar('postagem', $valores, [
            'logo_correios' => $etiquetas->logoCorreios ? 'logo_correios' : null,
            'logo_servico' => $this->logoServico($dados->codigoServico, $codigoServico),
            'marca_homologacao' => $homologacao ? 'marca_homologacao' : null,
        ], $resolucao);

        return new EtiquetaZpl($zpl, $resolucao);
    }

    /**
     * Atalho: monta `DadosEtiquetaPostagem` com o que a aplicação enviou (`NovaPrePostagem`) e o que a API
     * devolveu (`PrePostagemCriada`, de onde vem o código do objeto) e chama `postagem()`.
     *
     * Siglas dos adicionais: `ConfiguracaoEtiquetas::$siglasAdicionais` ou, sem entrada, `VD` para valor
     * declarado e `AR` para aviso de recebimento; adicionais sem sigla conhecida são omitidos.
     */
    public function postagemDePrePostagem(
        NovaPrePostagem $enviada,
        PrePostagemCriada $criada,
        ?string $observacoes = null,
        string $volume = '1/1',
        ?Resolucao $resolucao = null,
    ): EtiquetaZpl {
        $codigo = $criada->codigoObjeto ?? throw ErroValidacao::campo(
            'codigoObjeto',
            sprintf('a pré-postagem %s ainda não tem código do objeto; ele é obtido na emissão do rótulo pelos Correios.', $criada->id),
        );

        $remetente = $enviada->remetente ?? $this->configuracao->remetentePadrao
            ?? throw ErroValidacao::campo('remetente', 'informe o remetente ou configure o remetente padrão.');

        $siglas = [];
        foreach ($enviada->adicionais as $adicional) {
            $sigla = $this->siglaAdicional($adicional);
            if ($sigla !== null) {
                $siglas[] = $sigla;
            }
        }

        $numeroNota = $enviada->numeroNotaFiscal
            ?? ($enviada->chaveNFe !== null ? (ltrim($enviada->chaveNFe->numero(), '0') ?: '0') : null);

        return $this->postagem(new DadosEtiquetaPostagem(
            codigoObjeto: new CodigoRastreamento($codigo->valor),
            codigoServico: $enviada->servico,
            remetente: $remetente,
            destinatario: $enviada->destinatario,
            pesoGramas: $enviada->volume->pesoGramas,
            volume: $volume,
            numeroNotaFiscal: $numeroNota,
            numeroPedido: $enviada->pedidoExterno,
            siglasAdicionais: $siglas,
            observacoes: $observacoes ?? $enviada->observacao,
        ), $resolucao);
    }

    /**
     * DANFE Simplificado - Etiqueta. `$codigoEnvio` é o código de rastreamento dos Correios (opcional).
     * A etiqueta não lista itens nem valores.
     */
    public function danfeSimplificada(
        DadosDanfe $dados,
        CodigoRastreamento|string|null $codigoEnvio = null,
        ?string $observacoes = null,
        ?Resolucao $resolucao = null,
    ): EtiquetaZpl {
        $resolucao ??= $this->configuracao->etiquetas->resolucao;
        $emitente = $dados->emitente;
        $destinatario = $dados->destinatario;

        $documentoDestinatario = $destinatario->documento !== null
            ? (Documento::ehCpf($destinatario->documento) ? 'CPF: ' : 'CNPJ: ') . Documento::formatar($destinatario->documento)
            : '';
        if ($destinatario->inscricaoEstadual !== null) {
            $documentoDestinatario = Texto::juntar('  ', $documentoDestinatario, 'IE: ' . $destinatario->inscricaoEstadual);
        }

        $valores = self::cortar([
            'tipo_operacao' => $dados->tipoOperacao === 0 ? '0 - ENTRADA' : '1 - SAÍDA',
            'numero_nf' => $dados->numeroFormatado(),
            'serie' => $dados->serie,
            'data_emissao' => $dados->dataEmissao->format('d/m/Y'),
            'emitente_nome' => $emitente->nome,
            'emitente_cnpj' => $emitente->documento !== null ? Documento::formatar($emitente->documento) : '',
            'emitente_ie' => (string) $emitente->inscricaoEstadual,
            'emitente_uf' => $emitente->uf,
            // Sem o bairro, para caber em uma linha; se ainda passar do limite, encurta o logradouro e mantém cidade/UF e CEP
            'emitente_endereco' => Texto::juntarCortandoPrimeira(' - ', self::LIMITES_DANFE['emitente_endereco'], $emitente->enderecoLinha1(), $emitente->cidadeUf(), $emitente->cepFormatado()),
            'chave_acesso_formatada' => $dados->chaveAcesso->formatada(),
            'linha_protocolo' => sprintf('Protocolo de autorização: %s - %s', $dados->protocoloAutorizacao, $dados->dataAutorizacao->format('d/m/Y H:i:s')),
            'destinatario_nome' => $destinatario->nome,
            'destinatario_documento' => $documentoDestinatario,
            'destinatario_uf' => $destinatario->uf,
            'destinatario_endereco_linha1' => $destinatario->enderecoLinha1(),
            'destinatario_endereco_linha2' => Texto::juntarCortandoPrimeira(' - ', self::LIMITES_DANFE['destinatario_endereco_linha2'], $destinatario->bairro, $destinatario->cidadeUf(), $destinatario->cepFormatado()),
            'observacoes' => (string) $observacoes,
        ], self::LIMITES_DANFE);
        $valores['chave_acesso'] = $dados->chaveAcesso->valor;

        $parcialEnvio = null;
        if ($codigoEnvio !== null && (!is_string($codigoEnvio) || trim($codigoEnvio) !== '')) {
            $valores['codigo_envio'] = ($codigoEnvio instanceof CodigoRastreamento ? $codigoEnvio : new CodigoRastreamento($codigoEnvio))->valor;
            $parcialEnvio = 'danfe_codigo_envio';
        }

        $zpl = $this->renderizador->renderizar('danfe_simplificada', $valores, ['danfe_codigo_envio' => $parcialEnvio], $resolucao);

        return new EtiquetaZpl($zpl, $resolucao);
    }

    /**
     * Junta várias etiquetas em um único envio à impressora, na ordem recebida. Todas as `EtiquetaZpl` devem ter
     * a mesma resolução; textos puros são aceitos se começarem com `^XA` e terminarem com `^XZ`.
     */
    public function juntar(EtiquetaZpl|string ...$etiquetas): EtiquetaZpl
    {
        if ($etiquetas === []) {
            throw ErroValidacao::campo('etiquetas', 'informe ao menos uma etiqueta.');
        }

        $resolucao = null;
        $blocos = [];
        foreach ($etiquetas as $indice => $etiqueta) {
            if ($etiqueta instanceof EtiquetaZpl) {
                if ($resolucao !== null && $resolucao !== $etiqueta->resolucao) {
                    throw ErroValidacao::campo('etiquetas', sprintf(
                        'não é possível juntar etiquetas de resoluções diferentes (%d e %d dpi).',
                        $resolucao->value,
                        $etiqueta->resolucao->value,
                    ));
                }
                $resolucao = $etiqueta->resolucao;
                $texto = $etiqueta->zpl;
            } else {
                $texto = $etiqueta;
            }

            $texto = trim(str_replace(["\r\n", "\r"], "\n", $texto));
            if (!str_starts_with($texto, '^XA') || !str_ends_with($texto, '^XZ')) {
                throw ErroValidacao::campo('etiquetas', sprintf('a etiqueta %d deve começar com ^XA e terminar com ^XZ.', (int) $indice + 1));
            }
            $blocos[] = $texto . "\n";
        }

        return new EtiquetaZpl(implode('', $blocos), $resolucao ?? $this->configuracao->etiquetas->resolucao);
    }

    private function nomeServico(string $informado, string $codigo): string
    {
        return self::valorPorServico($this->configuracao->etiquetas->nomesServicos, $informado, $codigo, $this->configuracao->servicos) ?? $codigo;
    }

    private function logoServico(string $informado, string $codigo): ?string
    {
        return self::valorPorServico($this->configuracao->etiquetas->logosPorServico, $informado, $codigo, $this->configuracao->servicos);
    }

    /**
     * Procura no mapa pelo código, pelo apelido informado ou por qualquer apelido configurado para o código.
     *
     * @param array<string, string> $mapa
     * @param array<string, string> $servicos apelido → código
     */
    private static function valorPorServico(array $mapa, string $informado, string $codigo, array $servicos): ?string
    {
        $alvo = $mapa[$codigo] ?? $mapa[strtolower(trim($informado))] ?? $mapa[trim($informado)] ?? null;
        if ($alvo !== null) {
            return $alvo;
        }
        foreach ($servicos as $apelido => $codigoApelido) {
            if ($codigoApelido === $codigo && isset($mapa[$apelido])) {
                return $mapa[$apelido];
            }
        }

        return null;
    }

    private function siglaAdicional(ServicoAdicional $adicional): ?string
    {
        $mapa = $this->configuracao->etiquetas->siglasAdicionais;

        return $mapa[$adicional->codigo] ?? match (true) {
            $adicional->ehValorDeclarado() => 'VD',
            $adicional->codigo === ServicoAdicional::AVISO_RECEBIMENTO => 'AR',
            default => null,
        };
    }

    /**
     * Corta cada valor no limite (por caracteres) antes do escape feito pelo renderizador.
     *
     * @param array<string, string> $valores
     * @param array<string, int>    $limites
     *
     * @return array<string, string>
     */
    private static function cortar(array $valores, array $limites): array
    {
        foreach ($valores as $nome => $valor) {
            $valor = trim(Texto::semControle($valor));
            $valores[$nome] = isset($limites[$nome]) ? Texto::cortar($valor, $limites[$nome]) : $valor;
        }

        return $valores;
    }
}
