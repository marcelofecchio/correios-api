<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Dto\SolicitacaoDace;
use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Enums\LayoutImpressao;
use MarceloFecchio\Correios\Enums\ModalidadePagamento;
use MarceloFecchio\Correios\Enums\SituacaoProcessamento;
use MarceloFecchio\Correios\Enums\StatusPrePostagem;
use MarceloFecchio\Correios\Enums\TipoDace;
use MarceloFecchio\Correios\Enums\TipoRotulo;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;

function novaPrePostagemSedex(array $alterar = []): NovaPrePostagem
{
    return new NovaPrePostagem(...array_merge([
        'destinatario' => clienteTeste(),
        'servico' => 'sedex',
        'volume' => new Volume(800, FormatoObjeto::CaixaPacote, comprimento: 30, largura: 20, altura: 10),
        'itens' => [Item::comValor('Teclado para notebook', 1, '250,00')],
        'adicionais' => [ServicoAdicional::valorDeclarado('019', 25000), ServicoAdicional::avisoRecebimento()],
        'observacao' => 'Teste de homologacao',
        'pedidoExterno' => 'PED-TESTE-0001',
        'modalidadePagamento' => ModalidadePagamento::AFaturar,
        'prazoPostagem' => new DateTimeImmutable('2026-10-18'),
    ], $alterar));
}

describe('Pré-postagem síncrona', function (): void {
    it('envia o corpo no formato da API (texto, ponto decimal, regiao = UF) e sem campos internos', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'prepostagem_criada.json'));
        $criada = correiosTeste($cliente)->prePostagem()->criar(tokenTeste(), novaPrePostagemSedex());
        $requisicao = ultimaRequisicao($cliente);
        $corpo = corpoJson($requisicao);

        expect($requisicao->getMethod())->toBe('POST')
            ->and((string) $requisicao->getUri())->toBe('https://apihom.correios.com.br/prepostagem/v1/prepostagens')
            ->and($requisicao->getHeaderLine('Content-Type'))->toBe('application/json')
            ->and($corpo['remetente'])->toBe([
                'nome' => 'Loja Teste Homologacao Ltda',
                'dddCelular' => '11',
                'celular' => '987654321',
                'email' => 'loja.teste@exemplo.com.br',
                'cpfCnpj' => CNPJ_TESTE,
                'endereco' => ['cep' => '01310100', 'logradouro' => 'Avenida Paulista', 'numero' => '1000', 'complemento' => 'Sala 101', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP'],
            ])
            ->and($corpo['destinatario']['endereco']['regiao'])->toBe('SP')
            ->and($corpo['destinatario']['cpfCnpj'])->toBe(CPF_TESTE)
            ->and($corpo['codigoServico'])->toBe('03220')
            ->and($corpo['listaServicoAdicional'])->toBe([['codigoServicoAdicional' => '019', 'valorDeclarado' => '250.00'], ['codigoServicoAdicional' => '001']])
            ->and($corpo['itensDeclaracaoConteudo'])->toBe([['conteudo' => 'Teclado para notebook', 'quantidade' => '1', 'valor' => '250.00']])
            ->and($corpo['pesoInformado'])->toBe('800')
            ->and($corpo['codigoFormatoObjetoInformado'])->toBe('2')
            ->and($corpo['alturaInformada'])->toBe('10')
            ->and($corpo['larguraInformada'])->toBe('20')
            ->and($corpo['comprimentoInformado'])->toBe('30')
            ->and($corpo['cienteObjetoNaoProibido'])->toBe('1')
            ->and($corpo['modalidadePagamento'])->toBe('2')
            ->and($corpo['prazoPostagem'])->toBe('18/10/2026')
            ->and($corpo['observacao'])->toBe('Teste de homologacao')
            ->and($corpo['pedidoExternoOrigem'])->toBe('PED-TESTE-0001')
            ->and($corpo)->not->toHaveKeys(['idCorreios', 'precoServico', 'precoPrePostagem', 'numeroCartaoPostagem', 'idAtendimento', 'diametroInformado', 'logisticaReversa', 'emiteDCe', 'chaveNFe'])
            ->and($criada->id)->toBe('PRnR3Wm0pDT02bN0kuyAdP0A')
            ->and($criada->codigoObjeto?->valor)->toBe('AB043180865BR')
            ->and($criada->status)->toBe(StatusPrePostagem::Prepostado)
            ->and($criada->podeImprimirRotulo())->toBeTrue()
            ->and($criada->prazoPostagem?->format('d/m/Y'))->toBe('18/10/2026')
            ->and($criada->descricaoServico)->toBe('SEDEX CONTRATO AG');
    });

    it('monta a logística reversa: remetente = cliente com e-mail, destinatário = loja', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'prepostagem_criada.json'));
        correiosTeste($cliente)->prePostagem()->criar(tokenTeste(), new NovaPrePostagem(
            destinatario: lojaTeste(),
            servico: 'sedex_reverso',
            volume: new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10),
            remetente: clienteTeste(),
            itens: [new Item('Teclado para notebook', 1, 25000)],
            logisticaReversa: true,
            dataValidadeLogReversa: new DateTimeImmutable('2026-10-14'),
            codigoObjetoIda: 'AB043180865BR',
        ));
        $corpo = corpoJson(ultimaRequisicao($cliente));

        expect($corpo['codigoServico'])->toBe('03247')
            ->and($corpo['logisticaReversa'])->toBe('S')
            ->and($corpo['dataValidadeLogReversa'])->toBe('14/10/2026')
            ->and($corpo['remetente']['cpfCnpj'])->toBe(CPF_TESTE)
            ->and($corpo['remetente']['email'])->toBe('maria.souza.teste@exemplo.com.br')
            ->and($corpo['destinatario']['cpfCnpj'])->toBe(CNPJ_TESTE)
            ->and($corpo['codigoObjetoIda'])->toBe('AB043180865BR')
            ->and($corpo)->not->toHaveKey('listaServicoAdicional');
    });

    it('envia chave da NF-e, emiteDCe e itens vazios quando só há nota', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'prepostagem_criada.json'), respostaFixture(200, 'prepostagem_criada.json'));
        $correios = correiosTeste($cliente);
        $correios->prePostagem()->criar(tokenTeste(), novaPrePostagemSedex(['itens' => [], 'adicionais' => [], 'chaveNFe' => '35261011222333000181550020000214861123456788']));
        $comNota = corpoJson(ultimaRequisicao($cliente));
        $correios->prePostagem()->criar(tokenTeste(), novaPrePostagemSedex(['emiteDCe' => true, 'cienteObjetoNaoProibido' => false]));
        $comDce = corpoJson(ultimaRequisicao($cliente));

        expect($comNota['chaveNFe'])->toBe('35261011222333000181550020000214861123456788')
            ->and($comNota['itensDeclaracaoConteudo'])->toBe([])
            ->and($comDce['emiteDCe'])->toBe('S')
            ->and($comDce['cienteObjetoNaoProibido'])->toBe('0');
    });

    it('exige remetente quando não há remetente padrão', function (): void {
        correiosTeste(clienteMock(), configuracaoTeste(['remetentePadrao' => null]))->prePostagem()->criar(tokenTeste(), novaPrePostagemSedex());
    })->throws(ErroValidacao::class, 'remetente');

    it('mantém código do objeto ausente como nulo e não permite rótulo sem status 2', function (): void {
        $criada = correiosTeste(clienteMock(respostaJson(200, ['id' => 'PR1', 'statusAtual' => 7, 'codigoObjeto' => ''])))->prePostagem()->criar(tokenTeste(), novaPrePostagemSedex());

        expect($criada->codigoObjeto)->toBeNull()
            ->and($criada->status)->toBe(StatusPrePostagem::Pendente)
            ->and($criada->podeImprimirRotulo())->toBeFalse();
    });

    it('lança erro se a resposta não tiver id', function (): void {
        correiosTeste(clienteMock(respostaJson(200, ['codigoObjeto' => 'AB043180865BR'])))->prePostagem()->criar(tokenTeste(), novaPrePostagemSedex());
    })->throws(ErroValidacao::class, 'id');
});

describe('Pré-postagem assíncrona', function (): void {
    it('aceita o recibo como texto puro, string JSON ou objeto', function (string $corpo): void {
        $cliente = clienteMock(new GuzzleHttp\Psr7\Response(200, [], $corpo));
        $recibo = correiosTeste($cliente)->prePostagem()->criarAssincrona(tokenTeste(), novaPrePostagemSedex());

        expect($recibo)->toBe('123456789')
            ->and(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/assincrona');
    })->with(['123456789', '"123456789"', '{"recibo":"123456789"}', '{"numero":123456789}']);

    it('lança erro sem recibo', function (): void {
        correiosTeste(clienteMock(respostaJson(200, ['outro' => 'x'])))->prePostagem()->criarAssincrona(tokenTeste(), novaPrePostagemSedex());
    })->throws(ErroApi::class, 'recibo');

    it('mostra PROCESSADO com status 7 e depois 2', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'assincrona_processado_pendente.json'), respostaFixture(200, 'assincrona_processado_prepostado.json'));
        $prePostagem = correiosTeste($cliente)->prePostagem();
        $primeira = $prePostagem->consultarAssincrona(tokenTeste(), 'recibo 1');
        $segunda = $prePostagem->consultarAssincrona(tokenTeste(), 'recibo 1');

        expect(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/assincrona/recibo%201')
            ->and($primeira->situacao)->toBe(SituacaoProcessamento::Processado)
            ->and($primeira->processado())->toBeTrue()
            ->and($primeira->prePostagens[0]->status)->toBe(StatusPrePostagem::Pendente)
            ->and($primeira->prontaParaRotulo())->toBeFalse()
            ->and($segunda->prePostagens[0]->status)->toBe(StatusPrePostagem::Prepostado)
            ->and($segunda->prontaParaRotulo())->toBeTrue()
            ->and($segunda->prePostagens[0]->codigoObjeto?->valor)->toBe('AK039775832BR');
    });

    it('mostra as mensagens de FALHA e trata NAO_LOCALIZADO', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'assincrona_falha.json'), respostaFixture(200, 'assincrona_nao_localizado.json'));
        $prePostagem = correiosTeste($cliente)->prePostagem();
        $falha = $prePostagem->consultarAssincrona(tokenTeste(), 'r');
        $naoLocalizado = $prePostagem->consultarAssincrona(tokenTeste(), 'r');

        expect($falha->situacao)->toBe(SituacaoProcessamento::Falha)
            ->and($falha->prontaParaRotulo())->toBeFalse()
            ->and($falha->falhas[0]->ordem)->toBe('1')
            ->and($falha->todasAsMensagens())->toBe(['Erro ao processar a pré-postagem.', '[1] O preenchimento do Nome do destinatário é obrigatório.'])
            ->and($naoLocalizado->situacao)->toBe(SituacaoProcessamento::NaoLocalizado)
            ->and($naoLocalizado->processado())->toBeFalse();
    });

    it('recusa recibo vazio', function (): void {
        correiosTeste(clienteMock())->prePostagem()->consultarAssincrona(tokenTeste(), ' ');
    })->throws(ErroValidacao::class, 'recibo');
});

describe('Cancelamento e dados da postagem', function (): void {
    it('cancela por id e por código do objeto com DELETE sem corpo', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'cancelamento.json'), respostaFixture(200, 'cancelamento.json'));
        $prePostagem = correiosTeste($cliente)->prePostagem();
        $porId = $prePostagem->cancelar(tokenTeste(), 'PRnR3Wm0pDT02bN0kuyAdP0A');
        $requisicaoId = ultimaRequisicao($cliente);
        $prePostagem->cancelarPorCodigoObjeto(tokenTeste(), 'ab 043 180 865 br');
        $requisicaoObjeto = ultimaRequisicao($cliente);

        expect($requisicaoId->getMethod())->toBe('DELETE')
            ->and($requisicaoId->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/PRnR3Wm0pDT02bN0kuyAdP0A')
            ->and((string) $requisicaoId->getBody())->toBe('')
            ->and($requisicaoObjeto->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/objeto/AB043180865BR')
            ->and((string) $requisicaoObjeto->getBody())->toBe('')
            ->and($porId->resultado)->toBe('Cancelamento realizado com sucesso!')
            ->and($porId->mensagem)->toBeNull()
            ->and($porId->idRecibo)->toBeNull();
    });

    it('cancela em lote com DELETE com corpo JSON e lê o resultado em lista (formato observado)', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'cancelamento_lote.json'), respostaFixture(200, 'cancelamento_lote_resultado_lista.json'));
        $prePostagem = correiosTeste($cliente)->prePostagem();
        $recibo = $prePostagem->cancelarLote(tokenTeste(), idsPrePostagens: ['PR1', '', 'PR2']);
        $requisicao = ultimaRequisicao($cliente);
        $resultado = $prePostagem->consultarCancelamentoLote(tokenTeste(), (string) $recibo->idRecibo);

        expect($requisicao->getMethod())->toBe('DELETE')
            ->and($requisicao->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/assincrona')
            ->and($requisicao->getHeaderLine('Content-Type'))->toBe('application/json')
            ->and(corpoJson($requisicao))->toBe(['idsPrePostagens' => ['PR1', 'PR2']])
            ->and($recibo->idRecibo)->toBe('cc5607bb-c2c1-4e5e-9607-bbc2c18e5e94')
            ->and(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/canceladas/assincrona/cc5607bb-c2c1-4e5e-9607-bbc2c18e5e94')
            ->and($resultado->situacao)->toBeNull()
            ->and($resultado->itens)->toHaveCount(2)
            ->and($resultado->itens[0]->idPrePostagem)->toBe('PRxxxxxxxxxxxxxxxxxxxxx1')
            ->and($resultado->itens[0]->codigoErro)->toBe('PPN-145-5')
            ->and($resultado->itens[0]->data?->format('Y-m-d H:i:s'))->toBe('2026-10-04 14:18:36')
            ->and($resultado->itens[1]->codigoErro)->toBeNull()
            ->and($resultado->itensComErro())->toHaveCount(1)
            ->and($resultado->mensagens)->toHaveCount(2);
    });

    it('aceita o resultado do cancelamento em lote como objeto', function (): void {
        $resultado = correiosTeste(clienteMock(respostaFixture(200, 'cancelamento_lote_resultado.json')))->prePostagem()->consultarCancelamentoLote(tokenTeste(), 'r');

        expect($resultado->situacao)->toBe('PROCESSADO')
            ->and($resultado->mensagens)->toBe(['1 pré-postagem cancelada.'])
            ->and($resultado->itens)->toBe([]);
    });

    it('aceita códigos de objeto no lote e respostas em texto', function (): void {
        $cliente = clienteMock(new GuzzleHttp\Psr7\Response(200, [], '"abc123"'), new GuzzleHttp\Psr7\Response(200, [], 'CONCLUIDO'), respostaJson(200, ['msgs' => ['ok'], 'mensagem' => 'feito']));
        $prePostagem = correiosTeste($cliente)->prePostagem();
        $recibo = $prePostagem->cancelarLote(tokenTeste(), codigosObjetos: ['AB043180865BR']);
        $corpo = corpoJson(ultimaRequisicao($cliente));
        $emTexto = $prePostagem->consultarCancelamentoLote(tokenTeste(), 'abc123');
        $comMensagens = $prePostagem->consultarCancelamentoLote(tokenTeste(), 'abc123');

        expect($corpo)->toBe(['codigosObjetos' => ['AB043180865BR']])
            ->and($recibo->idRecibo)->toBe('abc123')
            ->and($emTexto->situacao)->toBe('CONCLUIDO')
            ->and($emTexto->itens)->toBe([])
            ->and($emTexto->respostaBruta())->toBe(['resposta' => 'CONCLUIDO'])
            ->and($comMensagens->mensagens)->toBe(['ok', 'feito'])
            ->and(fn() => $prePostagem->cancelarLote(tokenTeste()))->toThrow(ErroValidacao::class);
    });

    it('lê os dados reais da postagem mantendo datas e medidas como texto', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'postada.json'));
        $dados = correiosTeste($cliente)->prePostagem()->dadosPostagem(tokenTeste(), 'AB043180865BR');

        expect(queryDe(ultimaRequisicao($cliente)))->toBe('codigoObjeto=AB043180865BR')
            ->and(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/postada')
            ->and($dados->valorAtendimentoCentavos)->toBe(2150)
            ->and($dados->dataPostagem)->toBe('2026-10-05')
            ->and($dados->pesoTarifado)->toBe('1000')
            ->and($dados->valorDeclarado)->toBe('250.00')
            ->and($dados->adicionais[0]->valorCentavos)->toBe(460)
            ->and($dados->adicionais[0]->codigo)->toBe('019');
    });
});

describe('Rótulo oficial', function (): void {
    it('solicita com idsPrePostagem sem ids vazios e lê o idRecibo', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'rotulo_recibo.json'));
        $recibo = correiosTeste($cliente)->rotulo()->solicitar(tokenTeste(), new SolicitacaoRotulo(['PR1', '', 'PR2'], layoutImpressao: LayoutImpressao::Linear100x150, imprimeRemetente: false, tipoRotulo: TipoRotulo::Reduzido));
        $requisicao = ultimaRequisicao($cliente);

        expect($requisicao->getMethod())->toBe('POST')
            ->and($requisicao->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/rotulo/assincrono/pdf')
            ->and(corpoJson($requisicao))->toBe(['idsPrePostagem' => ['PR1', 'PR2'], 'tipoRotulo' => 'R', 'formatoRotulo' => 'ET', 'imprimeRemetente' => 'N', 'layoutImpressao' => 'LINEAR_100_150'])
            ->and($recibo->idRecibo)->toBe('0bf495ce83074e1ab495ce83073e1a261002');
    });

    it('solicita por códigos de objeto', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'rotulo_recibo.json'));
        correiosTeste($cliente)->rotulo()->solicitar(tokenTeste(), SolicitacaoRotulo::porCodigos(['AB043180865BR']));

        expect(corpoJson(ultimaRequisicao($cliente)))->toBe(['codigosObjeto' => ['AB043180865BR'], 'tipoRotulo' => 'P', 'formatoRotulo' => 'ET', 'imprimeRemetente' => 'S', 'layoutImpressao' => 'PADRAO']);
    });

    it('lança erro se a solicitação não devolver idRecibo', function (): void {
        correiosTeste(clienteMock(respostaJson(200, [])))->rotulo()->solicitar(tokenTeste(), SolicitacaoRotulo::porIds(['PR1']));
    })->throws(ErroApi::class, 'idRecibo');

    it('baixa o PDF em Base64 e valida %PDF', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'rotulo_recibo.json'), respostaFixture(200, 'rotulo_download.json'));
        $rotulo = correiosTeste($cliente)->rotulo();
        $arquivo = $rotulo->baixar(tokenTeste(), $rotulo->solicitar(tokenTeste(), SolicitacaoRotulo::porIds(['PR1'])));
        $caminho = sys_get_temp_dir() . '/rotulo-teste-' . uniqid() . '.pdf';
        $arquivo->salvar($caminho);

        expect(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/rotulo/download/assincrono/0bf495ce83074e1ab495ce83073e1a261002')
            ->and($arquivo->nome)->toBe('idRecibo_0bf495ce83074e1ab495ce83073e1a261002.pdf')
            ->and($arquivo->conteudoBinario())->toStartWith('%PDF')
            ->and(base64_decode($arquivo->conteudoBase64()))->toBe($arquivo->conteudoBinario())
            ->and($arquivo->tamanhoBytes())->toBeGreaterThan(50)
            ->and($arquivo->respostaBruta()['dados'])->toContain('caracteres em Base64')
            ->and(file_get_contents($caminho))->toBe($arquivo->conteudoBinario());
        unlink($caminho);
    });

    it('recusa conteúdo que não é PDF e resposta sem dados', function (): void {
        $cliente = clienteMock(respostaJson(200, ['dados' => base64_encode('não é pdf'), 'nome' => 'x.pdf']), respostaJson(200, ['nome' => 'x.pdf']));
        $rotulo = correiosTeste($cliente)->rotulo();

        expect(fn() => $rotulo->baixar(tokenTeste(), 'r'))->toThrow(ErroApi::class, '%PDF')
            ->and(fn() => $rotulo->baixar(tokenTeste(), 'r'))->toThrow(ErroApi::class, 'dados')
            ->and(fn() => $rotulo->baixar(tokenTeste(), ' '))->toThrow(ErroValidacao::class);
    });

    it('lança ErroPrePostagemPendente no download com PPN-288 (HTTP 400 ou 200)', function (int $status): void {
        try {
            correiosTeste(clienteMock(respostaFixture($status, 'erro_ppn288.json')))->rotulo()->baixar(tokenTeste(), 'f479b6af');
            $this->fail('Deveria lançar ErroPrePostagemPendente');
        } catch (ErroPrePostagemPendente $e) {
            expect($e->codigoErro)->toBe('PPN-288')
                ->and($e->statusHttp)->toBe($status)
                ->and($e->getMessage())->toContain('baixar rótulo');
        }
    })->with([
        'HTTP 400' => 400,
        'HTTP 200, observado em homologação em 04/10/2026' => 200,
    ]);

    it('tentarObter solicita e baixa numa tentativa e devolve null em PPN-288', function (): void {
        $cliente = clienteMock(
            respostaFixture(200, 'rotulo_recibo.json'),
            respostaFixture(200, 'erro_ppn288.json'),
            respostaFixture(200, 'rotulo_recibo.json'),
            respostaFixture(200, 'rotulo_download.json'),
        );
        $rotulo = correiosTeste($cliente)->rotulo();
        $solicitacao = SolicitacaoRotulo::porIds(['PR1']);

        expect($rotulo->tentarObter(tokenTeste(), $solicitacao))->toBeNull()
            ->and($cliente->getRequests())->toHaveCount(2)
            ->and($rotulo->tentarObter(tokenTeste(), $solicitacao)?->conteudoBinario())->toStartWith('%PDF')
            ->and(array_map(fn($r) => $r->getMethod(), $cliente->getRequests()))->toBe(['POST', 'GET', 'POST', 'GET']);
    });

    it('tentarObter não esconde outros erros', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'rotulo_recibo.json'), respostaJson(400, ['msgs' => ['PPN-999: outro erro']]));

        correiosTeste($cliente)->rotulo()->tentarObter(tokenTeste(), SolicitacaoRotulo::porIds(['PR1']));
    })->throws(ErroApi::class, 'outro erro');

    it('trata mensagem de erro com HTTP 200 também na solicitação do rótulo e na DACE', function (): void {
        $erro = ['msgs' => ['Pré-postagem não encontrada.']];
        $cliente = clienteMock(respostaJson(200, $erro), respostaJson(200, $erro));
        $correios = correiosTeste($cliente);

        expect(fn() => $correios->rotulo()->solicitar(tokenTeste(), SolicitacaoRotulo::porIds(['PR1'])))->toThrow(ErroApi::class, 'Pré-postagem não encontrada')
            ->and(fn() => $correios->dce()->imprimirDace(tokenTeste(), new SolicitacaoDace(TipoDace::Completa, ['PR1'])))->toThrow(ErroApi::class, 'Pré-postagem não encontrada');
    });

    it('nomeia o arquivo pelo recibo se a API não mandar nome', function (): void {
        $corpo = json_decode(arquivoFixture('respostas/rotulo_download.json'), true);
        unset($corpo['nome']);
        $arquivo = correiosTeste(clienteMock(respostaJson(200, $corpo)))->rotulo()->baixar(tokenTeste(), 'abc');

        expect($arquivo->nome)->toBe('idRecibo_abc.pdf')
            ->and(fn() => $arquivo->salvar('/caminho/que/nao/existe/x.pdf'))->toThrow(ErroConfiguracao::class);
    });
});

describe('DCe', function (): void {
    it('imprime a DACE completa (PDF) por ids', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'dace_completa.json'));
        $dace = correiosTeste($cliente)->dce()->imprimirDace(tokenTeste(), new SolicitacaoDace(TipoDace::Completa, ['PR1', '']));

        expect(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/dce/dace/impressao')
            ->and(corpoJson(ultimaRequisicao($cliente)))->toBe(['idsPrePostagens' => ['PR1'], 'tipoDace' => 'C'])
            ->and($dace->objetos)->toBe(['AB043180865BR'])
            ->and($dace->conteudoBinario())->toStartWith('%PDF')
            ->and($dace->respostaBruta()['dados'])->toContain('caracteres')
            ->and(fn() => $dace->texto())->toThrow(ErroConfiguracao::class);
    });

    it('trata a DACE térmica como texto, puro ou em Base64', function (): void {
        $zpl = '^XA^FO50,50^A0N,30,30^FDDACE^FS^XZ';
        $cliente = clienteMock(respostaFixture(200, 'dace_termica.json'), respostaJson(200, ['objetos' => ['AB043180865BR'], 'dados' => base64_encode($zpl)]));
        $dce = correiosTeste($cliente)->dce();
        $pura = $dce->imprimirDace(tokenTeste(), new SolicitacaoDace(TipoDace::Termica, codigosObjetos: ['AB043180865BR']));
        $base64 = $dce->imprimirDace(tokenTeste(), new SolicitacaoDace(TipoDace::Termica, codigosObjetos: ['AB043180865BR']));
        $caminho = sys_get_temp_dir() . '/dace-' . uniqid() . '.zpl';
        $pura->salvar($caminho);

        expect(corpoJson(ultimaRequisicao($cliente)))->toBe(['codigosObjetos' => ['AB043180865BR'], 'tipoDace' => 'T'])
            ->and($pura->texto())->toBe($zpl)
            ->and($base64->texto())->toBe($zpl)
            ->and($pura->conteudoOriginal())->toBe($zpl)
            ->and(file_get_contents($caminho))->toBe($zpl)
            ->and(fn() => $pura->conteudoBinario())->toThrow(ErroConfiguracao::class);
        unlink($caminho);
    });

    it('recusa PDF inválido e resposta sem dados', function (): void {
        $cliente = clienteMock(respostaJson(200, ['dados' => 'AAAA']), respostaJson(200, []));
        $dce = correiosTeste($cliente)->dce();
        $dace = $dce->imprimirDace(tokenTeste(), new SolicitacaoDace(TipoDace::Resumida, ['PR1']));

        expect(fn() => $dace->conteudoBinario())->toThrow(ErroApi::class, '%PDF')
            ->and(fn() => $dace->salvar(sys_get_temp_dir() . '/x.pdf'))->toThrow(ErroApi::class)
            ->and(fn() => $dce->imprimirDace(tokenTeste(), new SolicitacaoDace(TipoDace::Resumida, ['PR1'])))->toThrow(ErroApi::class, 'dados');
    });

    it('atualiza o documento fiscal com PATCH', function (): void {
        $cliente = clienteMock(new GuzzleHttp\Psr7\Response(200), new GuzzleHttp\Psr7\Response(204));
        $dce = correiosTeste($cliente)->dce();
        $dce->atualizarDocumentoFiscal(tokenTeste(), 'AB043180865BR', '3526 1011 2223 3300 0181 5500 2000 0214 8611 2345 6788', '21486');
        $requisicao = ultimaRequisicao($cliente);
        $dce->atualizarDocumentoFiscal(tokenTeste(), 'AB043180865BR', '35261011222333000181550020000214861123456788');

        expect($requisicao->getMethod())->toBe('PATCH')
            ->and($requisicao->getUri()->getPath())->toBe('/prepostagem/v1/prepostagens/dce')
            ->and(corpoJson($requisicao))->toBe(['codigoObjeto' => 'AB043180865BR', 'chaveNFe' => '35261011222333000181550020000214861123456788', 'numeroNotaFiscal' => '21486'])
            ->and(corpoJson(ultimaRequisicao($cliente)))->toBe(['codigoObjeto' => 'AB043180865BR', 'chaveNFe' => '35261011222333000181550020000214861123456788']);
    });

    it('valida chave e número da nota antes do PATCH', function (): void {
        $dce = correiosTeste(clienteMock())->dce();

        expect(fn() => $dce->atualizarDocumentoFiscal(tokenTeste(), 'AB043180865BR', '123'))->toThrow(ErroValidacao::class, '44')
            ->and(fn() => $dce->atualizarDocumentoFiscal(tokenTeste(), 'AB043180865BR', '35261011222333000181550020000214861123456788', '1.234'))->toThrow(ErroValidacao::class, 'numeroNotaFiscal')
            ->and(fn() => $dce->atualizarDocumentoFiscal(tokenTeste(), '', '35261011222333000181550020000214861123456788'))->toThrow(ErroValidacao::class, 'codigoObjeto');
    });
});
