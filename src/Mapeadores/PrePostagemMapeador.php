<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Mapeadores;

use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Enums\SituacaoProcessamento;
use MarceloFecchio\Correios\Enums\StatusPrePostagem;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Http\TratadorErros;
use MarceloFecchio\Correios\Respostas\AdicionalPostagem;
use MarceloFecchio\Correios\Respostas\DadosPostagem;
use MarceloFecchio\Correios\Respostas\FalhaProcessamento;
use MarceloFecchio\Correios\Respostas\ItemCancelamento;
use MarceloFecchio\Correios\Respostas\PrePostagemCriada;
use MarceloFecchio\Correios\Respostas\ProcessamentoAssincrono;
use MarceloFecchio\Correios\Respostas\ProcessamentoCancelamento;
use MarceloFecchio\Correios\Respostas\ReciboCancelamento;
use MarceloFecchio\Correios\Respostas\ResultadoCancelamento;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;
use MarceloFecchio\Correios\Utilitarios\Datas;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;

/**
 * Corpo da pré-postagem (`RequestPrePostagemExternaDTO`) e leitura das respostas da API Pré-postagem.
 *
 * Regras do mapeamento:
 * - números (peso, medidas, quantidade) vão como texto; valores com ponto e 2 casas (`"150.00"`);
 * - datas `dd/MM/yyyy`;
 * - `destinatario.endereco.regiao` recebe a UF em envios nacionais;
 * - os campos de credenciais internas dos Correios (`idCorreios`, `precoServico`, `precoPrePostagem`,
 *   `numeroCartaoPostagem`, `idAtendimento`) nunca são enviados.
 *
 * @internal
 */
final class PrePostagemMapeador
{
    private function __construct() {}

    /** @return array<string, mixed> */
    public static function paraApi(NovaPrePostagem $prePostagem, Pessoa $remetente, string $codigoServico): array
    {
        $volume = $prePostagem->volume;

        $corpo = [
            'remetente' => self::pessoa($remetente, false),
            'destinatario' => self::pessoa($prePostagem->destinatario, true),
            'codigoServico' => $codigoServico,
            'numeroNotaFiscal' => $prePostagem->numeroNotaFiscal,
            'chaveNFe' => $prePostagem->chaveNFe?->valor,
            'listaServicoAdicional' => $prePostagem->adicionais !== []
                ? array_map(self::adicional(...), $prePostagem->adicionais)
                : null,
            'itensDeclaracaoConteudo' => array_map(self::item(...), $prePostagem->itens),
            'pesoInformado' => (string) $volume->pesoGramas,
            'codigoFormatoObjetoInformado' => $volume->formato->value,
            'alturaInformada' => self::medida($volume->altura),
            'larguraInformada' => self::medida($volume->largura),
            'comprimentoInformado' => self::medida($volume->comprimento),
            'diametroInformado' => self::medida($volume->diametro),
            'cienteObjetoNaoProibido' => $prePostagem->cienteObjetoNaoProibido ? '1' : '0',
            'observacao' => $prePostagem->observacao,
            'modalidadePagamento' => $prePostagem->modalidadePagamento?->value,
            'logisticaReversa' => $prePostagem->logisticaReversa ? 'S' : null,
            'dataValidadeLogReversa' => $prePostagem->dataValidadeLogReversa !== null
                ? Datas::formatar($prePostagem->dataValidadeLogReversa, 'd/m/Y')
                : null,
            'prazoPostagem' => $prePostagem->prazoPostagem !== null ? Datas::formatar($prePostagem->prazoPostagem, 'd/m/Y') : null,
            'codigoObjetoIda' => $prePostagem->codigoObjetoIda?->valor,
            'emiteDCe' => $prePostagem->emiteDCe ? 'S' : null,
            'pedidoExternoOrigem' => $prePostagem->pedidoExterno,
        ];

        return self::semNulos($corpo);
    }

    public static function prePostagem(Leitor $dados): PrePostagemCriada
    {
        $id = $dados->texto('id');
        if ($id === null) {
            throw new ErroValidacao('A resposta da Pré-postagem não trouxe o campo "id".', 'id');
        }

        $codigo = $dados->texto('codigoObjeto');
        $status = $dados->inteiro('statusAtual');

        return new PrePostagemCriada(
            id: $id,
            codigoObjeto: $codigo !== null && CodigoRastreamento::formatoValido($codigo) ? new CodigoRastreamento($codigo, validarDigito: false) : null,
            status: $status !== null ? StatusPrePostagem::tryFrom($status) : null,
            prazoPostagem: $dados->data('prazoPostagem'),
            codigoServico: $dados->texto('codigoServico'),
            descricaoServico: $dados->texto('servico'),
            motivoCancelamento: $dados->texto('motivoCancelamento'),
            objetoPerigoso: match (strtoupper((string) $dados->texto('ehObjetoDgr'))) {
                'S' => true,
                'N' => false,
                default => null,
            },
            bruto: $dados->bruto(),
        );
    }

    public static function processamento(Leitor $dados): ProcessamentoAssincrono
    {
        $falhas = [];
        foreach ($dados->lista('sequencias') as $sequencia) {
            $mensagens = $sequencia->textos('mensagens');
            if ($mensagens !== []) {
                $falhas[] = new FalhaProcessamento($sequencia->texto('ordem'), $mensagens);
            }
        }

        $prePostagens = [];
        foreach ($dados->lista('prePostagens') as $item) {
            if ($item->texto('id') !== null) {
                $prePostagens[] = self::prePostagem($item);
            }
        }

        return new ProcessamentoAssincrono(
            situacao: SituacaoProcessamento::tryFrom(strtoupper((string) $dados->texto('situacao'))),
            mensagens: $dados->textos('mensagens'),
            falhas: $falhas,
            prePostagens: $prePostagens,
            bruto: $dados->bruto(),
        );
    }

    public static function cancelamento(Leitor $dados): ResultadoCancelamento
    {
        return new ResultadoCancelamento(
            resultado: $dados->texto('resultadoCancelamento'),
            mensagem: $dados->texto('mensagem'),
            idRecibo: $dados->texto('idRecibo'),
            bruto: $dados->bruto(),
        );
    }

    /**
     * O retorno do cancelamento em lote é declarado só como `object`: procura qualquer campo com "recibo"
     * no nome; aceita também um texto puro.
     */
    public static function reciboCancelamento(mixed $json, string $corpo): ReciboCancelamento
    {
        return new ReciboCancelamento(self::recibo($json, $corpo), is_array($json) ? $json : ['recibo' => self::recibo($json, $corpo)]);
    }

    /**
     * Recibo de uma resposta assíncrona: texto puro, string JSON (`"123"`) ou objeto com um campo cujo nome
     * contenha "recibo" (ou `numero`).
     */
    public static function recibo(mixed $json, string $corpo): ?string
    {
        if (is_array($json)) {
            foreach ($json as $chave => $valor) {
                if (is_string($chave) && (stripos($chave, 'recibo') !== false || $chave === 'numero') && is_scalar($valor) && (string) $valor !== '') {
                    return (string) $valor;
                }
            }

            return null;
        }

        $texto = is_scalar($json) ? trim((string) $json) : trim($corpo, " \t\n\r\"");

        return $texto !== '' ? $texto : null;
    }

    public static function processamentoCancelamento(mixed $json, string $corpo): ProcessamentoCancelamento
    {
        if (!is_array($json)) {
            $texto = is_string($json) ? $json : trim($corpo);

            return new ProcessamentoCancelamento(situacao: $texto !== '' ? $texto : null, bruto: ['resposta' => $texto]);
        }

        $dados = new Leitor($json);
        $mensagens = array_merge($dados->textos('mensagens'), $dados->textos('msgs'));
        if (($mensagem = $dados->texto('mensagem')) !== null) {
            $mensagens[] = $mensagem;
        }

        // Observado: lista na raiz, um item por pré-postagem
        $itens = [];
        $fonte = array_is_list($json) ? $dados->itensRaiz() : $dados->lista('itens');
        foreach ($fonte as $item) {
            $mensagemItem = $item->texto('mensagem');
            if ($mensagemItem !== null) {
                $mensagens[] = $mensagemItem;
            }
            $itens[] = new ItemCancelamento(
                idPrePostagem: $item->texto('IdPrePostagem') ?? $item->texto('idPrePostagem') ?? $item->texto('id'),
                codigoObjeto: $item->texto('codigoObjeto'),
                data: $item->data('data'),
                mensagem: $mensagemItem,
                codigoErro: $mensagemItem !== null ? TratadorErros::codigoErro([$mensagemItem]) : null,
            );
        }

        return new ProcessamentoCancelamento(
            situacao: array_is_list($json) ? null : ($dados->texto('situacao') ?? $dados->texto('resultadoCancelamento') ?? $dados->texto('status')),
            mensagens: array_values(array_unique($mensagens)),
            itens: $itens,
            bruto: $json,
        );
    }

    public static function dadosPostagem(Leitor $dados): DadosPostagem
    {
        $adicionais = [];
        foreach ($dados->lista('listaServicoAdicional') as $item) {
            $adicionais[] = new AdicionalPostagem(
                $item->texto('codigoServicoAdicional'),
                $item->centavos('valor'),
                $item->texto('tipoComposicao'),
            );
        }

        return new DadosPostagem(
            codigoObjeto: $dados->texto('codigoObjeto'),
            codigoServico: $dados->texto('codigoServico'),
            nomeServico: $dados->texto('nomeServico'),
            dataPostagem: $dados->texto('dataPostagem'),
            dataHoraAtendimento: $dados->texto('dataHoraAtendimento'),
            valorAtendimentoCentavos: $dados->centavos('valorAtendimento'),
            pesoObjeto: $dados->texto('pesoObjeto'),
            pesoTarifado: $dados->texto('pesoTarifadoObjeto'),
            altura: $dados->texto('alturaObjeto'),
            largura: $dados->texto('larguraObjeto'),
            comprimento: $dados->texto('comprimentoObjeto'),
            diametro: $dados->texto('diametroObjeto'),
            valorDeclarado: $dados->texto('valorDeclaradoObjeto'),
            cepAgencia: $dados->texto('cepAgencia'),
            cepDestino: $dados->texto('cepDestino'),
            adicionais: $adicionais,
            bruto: $dados->bruto(),
        );
    }

    /** @return array<string, mixed> */
    private static function pessoa(Pessoa $pessoa, bool $destinatario): array
    {
        $endereco = $pessoa->endereco;

        return self::semNulos([
            'nome' => $pessoa->nome,
            'dddTelefone' => $pessoa->dddTelefone(),
            'telefone' => $pessoa->numeroTelefone(),
            'dddCelular' => $pessoa->dddCelular(),
            'celular' => $pessoa->numeroCelular(),
            'email' => $pessoa->email,
            'cpfCnpj' => $pessoa->documento,
            'endereco' => self::semNulos([
                'cep' => $endereco->cep,
                'logradouro' => $endereco->logradouro,
                'numero' => $endereco->numero,
                'complemento' => $endereco->complemento,
                'bairro' => $endereco->bairro,
                'cidade' => $endereco->cidade,
                'uf' => $endereco->uf->value,
                'regiao' => $destinatario ? $endereco->uf->value : null,
            ]),
        ]);
    }

    /** @return array<string, string> */
    private static function adicional(ServicoAdicional $adicional): array
    {
        return self::semNulos([
            'codigoServicoAdicional' => $adicional->codigo,
            'valorDeclarado' => $adicional->valorDeclaradoCentavos !== null ? Dinheiro::formatarApi($adicional->valorDeclaradoCentavos) : null,
        ]);
    }

    /** @return array<string, string> */
    private static function item(Item $item): array
    {
        return [
            'conteudo' => $item->descricao,
            'quantidade' => (string) $item->quantidade,
            'valor' => Dinheiro::formatarApi($item->valorUnitarioCentavos),
        ];
    }

    private static function medida(?int $valor): ?string
    {
        return $valor !== null ? (string) $valor : null;
    }

    /**
     * @template T
     *
     * @param array<string, T|null> $dados
     *
     * @return array<string, T>
     */
    private static function semNulos(array $dados): array
    {
        return array_filter($dados, static fn(mixed $valor): bool => $valor !== null);
    }
}
