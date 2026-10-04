<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Enums\StatusPrePostagem;
use MarceloFecchio\Correios\Etiquetas\ConfiguracaoEtiquetas;
use MarceloFecchio\Correios\Etiquetas\DadosDanfe;
use MarceloFecchio\Correios\Etiquetas\DadosEtiquetaPostagem;
use MarceloFecchio\Correios\Etiquetas\EtiquetaZpl;
use MarceloFecchio\Correios\Etiquetas\GeradorEtiquetas;
use MarceloFecchio\Correios\Etiquetas\ParticipanteDanfe;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Respostas\PrePostagemCriada;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

function gerador(array $config = []): GeradorEtiquetas
{
    return new GeradorEtiquetas(configuracaoTeste($config));
}

function dadosPostagem(array $alterar = []): DadosEtiquetaPostagem
{
    return new DadosEtiquetaPostagem(...array_merge([
        'codigoObjeto' => 'AB043180865BR',
        'codigoServico' => 'sedex',
        'remetente' => lojaTeste(),
        'destinatario' => clienteTeste(),
        'pesoGramas' => 800,
        'numeroNotaFiscal' => '21486',
        'siglasAdicionais' => ['VD', 'AR'],
        'observacoes' => 'Pedido 12345',
    ], $alterar));
}

function xmlNfe(string $arquivo): string
{
    return arquivoFixture('xml/' . $arquivo);
}

describe('Etiqueta de postagem', function (): void {
    it('gera o ZPL esperado em 203 dpi (snapshot)', function (): void {
        $etiqueta = gerador()->postagem(dadosPostagem());

        expect($etiqueta)->toBeInstanceOf(EtiquetaZpl::class)
            ->and($etiqueta->quantidade)->toBe(1)
            ->and($etiqueta->resolucao)->toBe(Resolucao::Dpi203);
        compararSnapshot('postagem_203dpi', $etiqueta->zpl);
    });

    it('gera o ZPL esperado em 300 dpi (snapshot)', function (): void {
        $etiqueta = gerador()->postagem(dadosPostagem(), Resolucao::Dpi300);

        expect($etiqueta->resolucao)->toBe(Resolucao::Dpi300)
            ->and($etiqueta->zpl)->toContain('^PW1200')
            ->and($etiqueta->zpl)->toContain('^BXN,8,200')
            ->and($etiqueta->zpl)->toContain('^FO62,84^GFA,14112,14112,42')
            ->and($etiqueta->zpl)->toContain('^FO768,96^GFA,14112,14112,42');
        compararSnapshot('postagem_300dpi', $etiqueta->zpl);
    });

    it('produz UTF-8 sem BOM, de ^XA a ^XZ, e a mesma saída para a mesma entrada', function (): void {
        $primeira = gerador()->postagem(dadosPostagem())->zpl;
        $segunda = gerador()->postagem(dadosPostagem())->zpl;

        expect($primeira)->toBe($segunda)
            ->and($primeira)->toStartWith('^XA')
            ->and($primeira)->toEndWith("^XZ\n")
            ->and(str_starts_with($primeira, "\u{FEFF}"))->toBeFalse()
            ->and(mb_check_encoding($primeira, 'UTF-8'))->toBeTrue()
            ->and($primeira)->not->toContain('^FX')
            ->and($primeira)->toContain('^CI28');
    });

    it('preenche DataMatrix, código do objeto, CEP e textos', function (): void {
        $zpl = gerador()->postagem(dadosPostagem())->zpl;

        expect($zpl)->toContain('^FD01001000000000131010000000851AB043180865BR250000000000' . CARTAO_TESTE . '032200000100APTO 12')
            ->and($zpl)->toContain('^BCN,,Y,Y^FDAB043180865BR^FS')
            ->and($zpl)->toContain('^BCN,,N,N^FD01001000^FS')
            ->and($zpl)->toContain('^FDNF: 21486^FS')
            ->and($zpl)->toContain('^FDContrato: ' . CONTRATO_TESTE . '^FS')
            ->and($zpl)->toContain('^FDVol: 1/1^FS')
            ->and($zpl)->toContain('^FDPeso: 800 g^FS')
            ->and($zpl)->toContain('^FDSEDEX^FS')
            ->and($zpl)->toContain('^FDVD AR^FS')
            ->and($zpl)->toContain('^FDPraça da Sé, 100, Apto 12^FS')
            ->and($zpl)->toContain('^FD01001-000^FS')
            ->and($zpl)->toContain('^FDSão Paulo/SP^FS')
            ->and($zpl)->toContain('^FD01310-100 São Paulo/SP^FS')
            ->and($zpl)->toContain('^FDPedido 12345^FS');
    });

    it('usa o logo do serviço pelo código ou apelido e omite quando não há mapeamento', function (): void {
        $sedex = gerador()->postagem(dadosPostagem(['codigoServico' => '03220']))->zpl;
        $pac = gerador()->postagem(dadosPostagem(['codigoServico' => 'pac']))->zpl;
        $semLogo = gerador()->postagem(dadosPostagem(['codigoServico' => '04162']))->zpl;

        expect($sedex)->toContain('^FO512,64^GFA,6272')
            ->and($pac)->toContain('^FO560,100^GFA,2940')
            ->and($semLogo)->not->toContain('^FO512,64')
            ->and($semLogo)->not->toContain('^FO560,100')
            ->and($semLogo)->toContain('^FD04162^FS')
            ->and($semLogo)->toContain('^FO41,56^GFA');
    });

    it('imprime a marca de homologação só em homologação, salvo configuração', function (): void {
        $homologacao = gerador()->postagem(dadosPostagem())->zpl;
        $producao = gerador(['ambiente' => Ambiente::Producao, 'usuario' => 'login'])->postagem(dadosPostagem())->zpl;
        $forcada = gerador(['ambiente' => Ambiente::Producao, 'usuario' => 'login', 'etiquetas' => new ConfiguracaoEtiquetas(marcaHomologacao: true, logoCorreios: false)])->postagem(dadosPostagem())->zpl;

        expect($homologacao)->toContain('HOMOLOGACAO - IMPRESSAO DE TESTE')
            ->and($producao)->not->toContain('HOMOLOGACAO')
            ->and($forcada)->toContain('HOMOLOGACAO')
            ->and($forcada)->not->toContain('^FO41,56^GFA');
    });

    it('escapa caracteres especiais e corta textos longos por caracteres', function (): void {
        $destinatario = new MarceloFecchio\Correios\Dto\Pessoa(
            'José_da Silva ^Teste~',
            new MarceloFecchio\Correios\Dto\Endereco('01001000', str_repeat('Avenida Ação ', 3) . 'Longa', '123456', 'Bairro \\ Barra', 'Cidade Muito Comprida do Sul', 'SP', 'Complemento muito comprido aq'),
        );
        $zpl = gerador()->postagem(dadosPostagem(['destinatario' => $destinatario, 'observacoes' => str_repeat('obs ', 50), 'numeroNotaFiscal' => null, 'numeroPedido' => 'PED-9']))->zpl;

        preg_match('/\^FB710,2,0,L\^FH\^FD([^\^]*)\^FS/', $zpl, $endereco);
        preg_match('/\^FO50,1082[^\n]*\^FD([^\^]*)\^FS/', $zpl, $observacoes);

        expect($zpl)->toContain('^FDJosé_5Fda Silva _5ETeste_7E^FS')
            ->and($zpl)->toContain('^FDBairro _5C Barra^FS')
            ->and($zpl)->toContain('^FDPedido: PED-9^FS')
            ->and(mb_strlen($endereco[1]))->toBeLessThanOrEqual(90)
            ->and(mb_strlen($observacoes[1]))->toBe(110);
    });

    it('omite a linha da nota quando não há nota nem pedido', function (): void {
        $zpl = gerador()->postagem(dadosPostagem(['numeroNotaFiscal' => null, 'siglasAdicionais' => [], 'observacoes' => null]))->zpl;

        expect($zpl)->toContain('^FT31,305^A0N,28,28^FH^FD^FS');
    });

    it('valida os dados da etiqueta', function (array $alterar, string $campo): void {
        expect(fn() => dadosPostagem($alterar))->toThrow(ErroValidacao::class, $campo);
    })->with([
        [['codigoObjeto' => 'AB043180866BR'], 'codigoObjeto'],
        [['codigoServico' => ' '], 'codigoServico'],
        [['pesoGramas' => 0], 'pesoGramas'],
        [['volume' => '1 de 2'], 'volume'],
    ]);

    it('recusa serviço desconhecido', function (): void {
        gerador()->postagem(dadosPostagem(['codigoServico' => 'carta']));
    })->throws(ErroValidacao::class, 'servico');

    it('normaliza siglas e usa o nome de serviço informado', function (): void {
        $dados = dadosPostagem(['siglasAdicionais' => ['vd', 'VD', ' ', 'mp'], 'nomeServico' => 'SEDEX 12', 'cartaoPostagem' => '99', 'contrato' => '1']);
        $zpl = gerador()->postagem($dados)->zpl;

        expect($dados->siglasAdicionais)->toBe(['VD', 'MP'])
            ->and($zpl)->toContain('^FDSEDEX 12^FS')
            ->and($zpl)->toContain('^FDContrato: 1^FS')
            ->and($zpl)->toContain('AB043180865BR2500000000000000000099');
    });
});

describe('postagemDePrePostagem', function (): void {
    $nova = static fn(array $alterar = []) => new NovaPrePostagem(...array_merge([
        'destinatario' => clienteTeste(),
        'servico' => 'sedex',
        'volume' => new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10),
        'itens' => [new Item('Teclado', 1, 25000)],
        'adicionais' => [ServicoAdicional::valorDeclarado('019', 25000), ServicoAdicional::avisoRecebimento(), new ServicoAdicional('016')],
        'numeroNotaFiscal' => '21486',
        'observacao' => 'Pedido 12345',
    ], $alterar));
    $criada = new PrePostagemCriada('PR1', new CodigoRastreamento('AB043180865BR'), StatusPrePostagem::Prepostado);

    it('gera o mesmo ZPL que postagem() com os mesmos dados', function () use ($nova, $criada): void {
        expect(gerador()->postagemDePrePostagem($nova(), $criada)->zpl)->toBe(gerador()->postagem(dadosPostagem())->zpl);
    });

    it('usa a chave da NF-e para o número da nota e o pedido externo', function () use ($nova, $criada): void {
        $zpl = gerador()->postagemDePrePostagem($nova(['numeroNotaFiscal' => null, 'chaveNFe' => '35261011222333000181550020000214861123456788']), $criada, 'Outra obs', '2/3')->zpl;
        $comPedido = gerador()->postagemDePrePostagem($nova(['numeroNotaFiscal' => null, 'pedidoExterno' => 'PED-1']), $criada)->zpl;

        expect($zpl)->toContain('^FDNF: 21486^FS')
            ->and($zpl)->toContain('^FDVol: 2/3^FS')
            ->and($zpl)->toContain('^FDOutra obs^FS')
            ->and($comPedido)->toContain('^FDPedido: PED-1^FS');
    });

    it('usa as siglas configuradas', function () use ($nova, $criada): void {
        $config = ['etiquetas' => new ConfiguracaoEtiquetas(siglasAdicionais: ['016' => 'LRSV', '019' => 'VD'])];

        expect(gerador($config)->postagemDePrePostagem($nova(), $criada)->zpl)->toContain('^FDVD AR LRSV^FS');
    });

    it('exige código do objeto e remetente', function () use ($nova, $criada): void {
        expect(fn() => gerador()->postagemDePrePostagem($nova(), new PrePostagemCriada('PR9', null, StatusPrePostagem::Pendente)))->toThrow(ErroValidacao::class, 'PR9')
            ->and(fn() => gerador(['remetentePadrao' => null])->postagemDePrePostagem($nova(), $criada))->toThrow(ErroValidacao::class, 'remetente');
    });
});

describe('DadosDanfe', function (): void {
    it('lê o XML de venda para pessoa física', function (): void {
        $danfe = DadosDanfe::deXml(xmlNfe('nfe-venda-pf.xml'));

        expect($danfe->chaveAcesso->modelo())->toBe('55')
            ->and($danfe->numero)->toBe('21486')
            ->and($danfe->numeroFormatado())->toBe('000.021.486')
            ->and($danfe->serie)->toBe($danfe->chaveAcesso->serie() === '001' ? '1' : ltrim($danfe->chaveAcesso->serie(), '0'))
            ->and($danfe->tipoOperacao)->toBe(1)
            ->and($danfe->protocoloAutorizacao)->toBe('135260000012345')
            ->and($danfe->emitente->nome)->toBe('Loja Teste Homologação Comércio Ltda')
            ->and($danfe->emitente->documento)->toBe(CNPJ_TESTE)
            ->and($danfe->emitente->inscricaoEstadual)->toBe('123456789110')
            ->and($danfe->destinatario->documento)->toBe(CPF_TESTE)
            ->and($danfe->destinatario->inscricaoEstadual)->toBeNull()
            ->and($danfe->destinatario->uf)->toBe('RJ')
            ->and($danfe->destinatario->cepFormatado())->toBe('20040-000');
    });

    it('lê o XML de venda para pessoa jurídica com IE', function (): void {
        $danfe = DadosDanfe::deXml(xmlNfe('nfe-venda-pj.xml'));

        expect($danfe->numero)->toBe('12345')
            ->and($danfe->destinatario->documento)->toBe('12345678000195')
            ->and($danfe->destinatario->inscricaoEstadual)->toBe('000000000000')
            ->and($danfe->emitente->cidadeUf())->toBe('Porto Alegre/RS');
    });

    it('aceita autorização fora de prazo (cStat 150)', function (): void {
        expect(DadosDanfe::deXml(xmlNfe('nfe-autorizada-fora-de-prazo.xml'))->numero)->toBe('21486');
    });

    it('recusa XML inválido, sem protocolo, não autorizado, EPEC ou com chave divergente', function (string $xml, string $mensagem): void {
        expect(fn() => DadosDanfe::deXml($xml))->toThrow(ErroValidacao::class, $mensagem);
    })->with([
        'vazio' => ['', 'inválido'],
        'malformado' => ['<nfeProc><NFe>', 'inválido'],
        'sem protocolo' => [arquivoFixture('xml/nfe-sem-protocolo.xml'), 'protocolo'],
        'denegada' => [arquivoFixture('xml/nfe-denegada.xml'), 'cStat 302'],
        'EPEC' => [arquivoFixture('xml/nfe-epec.xml'), 'EPEC'],
        'chave divergente' => [arquivoFixture('xml/nfe-chave-divergente.xml'), 'diferente'],
    ]);

    it('acusa tag obrigatória ausente', function (): void {
        $xml = str_replace(['<xNome>Maria Aparecida Souza</xNome>'], [''], xmlNfe('nfe-venda-pf.xml'));
        DadosDanfe::deXml($xml);
    })->throws(ErroValidacao::class, 'nome do destinatário');

    it('valida a construção manual', function (array $alterar, string $mensagem): void {
        $padrao = [
            'chaveAcesso' => '35261011222333000181550020000214861123456788',
            'numero' => '21486',
            'serie' => '2',
            'dataEmissao' => new DateTimeImmutable('2026-10-02T10:00:00-03:00'),
            'tipoOperacao' => 1,
            'protocoloAutorizacao' => '135260000012345',
            'dataAutorizacao' => new DateTimeImmutable('2026-10-02T10:05:00-03:00'),
            'emitente' => new ParticipanteDanfe('Loja Teste', CNPJ_TESTE, 'SP'),
            'destinatario' => new ParticipanteDanfe('Maria Souza', CPF_TESTE, 'SP'),
        ];
        expect(fn() => new DadosDanfe(...array_merge($padrao, $alterar)))->toThrow(ErroValidacao::class, $mensagem);
    })->with([
        [['numero' => '21487'], 'número'],
        [['serie' => '1'], 'série'],
        [['tipoOperacao' => 2], 'tipoOperacao'],
        [['protocoloAutorizacao' => ' '], 'protocolo'],
        [['chaveAcesso' => '35261011222333000181650020000214861123456780'], 'modelo'],
    ]);

    it('valida o participante', function (): void {
        expect(fn() => new ParticipanteDanfe(' ', null, 'SP'))->toThrow(ErroValidacao::class, 'nome')
            ->and(fn() => new ParticipanteDanfe('X', null, 'São Paulo'))->toThrow(ErroValidacao::class, 'UF')
            ->and(fn() => new ParticipanteDanfe('X', '123', 'SP'))->toThrow(ErroValidacao::class)
            ->and(new ParticipanteDanfe('X', null, 'sp', cidade: ' ')->cidadeUf())->toBe('SP')
            ->and(new ParticipanteDanfe('X', null, 'SP')->cepFormatado())->toBeNull();
    });
});

describe('DANFE simplificado', function (): void {
    it('gera o ZPL esperado com código de envio, em 203 e 300 dpi (snapshots)', function (): void {
        $dados = DadosDanfe::deXml(xmlNfe('nfe-venda-pf.xml'));
        $etiqueta = gerador()->danfeSimplificada($dados, 'AB043180865BR', 'Pedido 12345');

        compararSnapshot('danfe_203dpi', $etiqueta->zpl);
        compararSnapshot('danfe_300dpi', gerador()->danfeSimplificada($dados, 'AB043180865BR', 'Pedido 12345', Resolucao::Dpi300)->zpl);
        expect($etiqueta->quantidade)->toBe(1);
    });

    it('preenche chave, protocolo, emitente e destinatário', function (): void {
        $dados = DadosDanfe::deXml(xmlNfe('nfe-venda-pf.xml'));
        $zpl = gerador()->danfeSimplificada($dados, new CodigoRastreamento('AB043180865BR'))->zpl;

        expect($zpl)->toContain('^FD>;' . $dados->chaveAcesso->valor . '^FS')
            ->and($zpl)->toContain('^FD' . $dados->chaveAcesso->formatada() . '^FS')
            ->and($zpl)->toContain('^FD>:AB043180865BR^FS')
            ->and($zpl)->toContain('CÓDIGO DE ENVIO')
            ->and($zpl)->toContain('^FD1 - SAÍDA^FS')
            ->and($zpl)->toContain('^FDNº 000.021.486^FS')
            ->and($zpl)->toContain('Protocolo de autorização: 135260000012345 - ')
            ->and($zpl)->toContain('^FDCNPJ: 11.222.333/0001-81  IE: 123456789110  UF: SP^FS')
            ->and($zpl)->toContain('^FDCPF: 529.982.247-25  UF: RJ^FS')
            ->and($zpl)->toContain('^FDPraça da Sé, 100, Apto 12^FS')
            ->and($zpl)->toContain('^FDSé - Rio de Janeiro/RJ - 20040-000^FS');
    });

    it('não lista itens nem valores da nota', function (): void {
        $xml = xmlNfe('nfe-venda-pj.xml');
        $zpl = gerador()->danfeSimplificada(DadosDanfe::deXml($xml))->zpl;
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('n', 'http://www.portalfiscal.inf.br/nfe');

        foreach ($xp->query('//n:det/n:prod/n:xProd') ?: [] as $produto) {
            expect($zpl)->not->toContain(mb_substr(trim($produto->textContent), 0, 12));
        }
        expect($zpl)->not->toContain((string) $xp->evaluate('string(//n:total/n:ICMSTot/n:vNF)'))
            ->and($zpl)->not->toContain('300,00')
            ->and($zpl)->toContain('CNPJ: 12.345.678/0001-95  IE: 000000000000');
    });

    it('omite o bloco do código de envio quando não há código', function (): void {
        $zpl = gerador()->danfeSimplificada(DadosDanfe::deXml(xmlNfe('nfe-venda-pf.xml')), ' ')->zpl;

        expect($zpl)->not->toContain('CÓDIGO DE ENVIO')
            ->and($zpl)->not->toContain('>:')
            ->and($zpl)->not->toContain('{{');
    });

    it('imprime 0 - ENTRADA e destinatário sem documento', function (): void {
        $dados = new DadosDanfe(
            chaveAcesso: '35261011222333000181550020000214861123456788',
            numero: '21486',
            serie: '002',
            dataEmissao: new DateTimeImmutable('2026-10-02T10:00:00-03:00'),
            tipoOperacao: 0,
            protocoloAutorizacao: '135260000012345',
            dataAutorizacao: new DateTimeImmutable('2026-10-02T10:05:00-03:00'),
            emitente: new ParticipanteDanfe(str_repeat('Razão Social Longa ', 4), CNPJ_TESTE, 'SP', '123'),
            destinatario: new ParticipanteDanfe('Buyer Abroad', null, 'EX', 'ISENTO'),
        );
        $zpl = gerador()->danfeSimplificada($dados)->zpl;

        expect($zpl)->toContain('^FD0 - ENTRADA^FS')
            ->and($zpl)->toContain('^FDIE: ISENTO  UF: EX^FS')
            ->and($zpl)->toContain('^FDSérie: 2^FS')
            ->and($zpl)->toContain('^FD' . mb_substr(str_repeat('Razão Social Longa ', 4), 0, 50) . '^FS');
    });
});

describe('juntar', function (): void {
    it('concatena na ordem recebida e conta os blocos', function (): void {
        $postagem = gerador()->postagem(dadosPostagem());
        $danfe = gerador()->danfeSimplificada(DadosDanfe::deXml(xmlNfe('nfe-venda-pf.xml')));
        $tudo = gerador()->juntar($postagem, $danfe, "^XA^FDextra^FS^XZ\r\n");

        expect($tudo->quantidade)->toBe(3)
            ->and($tudo->zpl)->toBe($postagem->zpl . $danfe->zpl . "^XA^FDextra^FS^XZ\n")
            ->and((string) $tudo)->toBe($tudo->zpl)
            ->and($tudo->resolucao)->toBe(Resolucao::Dpi203);
    });

    it('recusa resoluções diferentes, texto inválido e lista vazia', function (): void {
        $a = gerador()->postagem(dadosPostagem());
        $b = gerador()->postagem(dadosPostagem(), Resolucao::Dpi300);

        expect(fn() => gerador()->juntar($a, $b))->toThrow(ErroValidacao::class, 'resoluções')
            ->and(fn() => gerador()->juntar('^FDsolto^FS'))->toThrow(ErroValidacao::class, '^XA')
            ->and(fn() => gerador()->juntar())->toThrow(ErroValidacao::class)
            ->and(gerador()->juntar('^XA^XZ')->resolucao)->toBe(Resolucao::Dpi203);
    });

    it('grava o arquivo e lança erro se não conseguir', function (): void {
        $etiqueta = new EtiquetaZpl("^XA^XZ\n", Resolucao::Dpi203);
        $caminho = sys_get_temp_dir() . '/etiqueta-' . uniqid() . '.zpl';
        $etiqueta->salvar($caminho);

        expect(file_get_contents($caminho))->toBe("^XA^XZ\n")
            ->and(fn() => $etiqueta->salvar('/nao/existe/etiqueta.zpl'))->toThrow(ErroConfiguracao::class);
        unlink($caminho);
    });
});

describe('Templates próprios da aplicação', function (): void {
    it('usa o template do diretório da aplicação e cai no do pacote para os parciais', function (): void {
        $dir = sys_get_temp_dir() . '/templates-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/postagem.zpl', "^XA\n^FO10,10^FH^FD{{destinatario_nome}} {{codigo_objeto_texto}}^FS\n{{>logo_correios}}\n^XZ\n");
        $config = configuracaoTeste(['etiquetas' => new ConfiguracaoEtiquetas(diretorioTemplates: $dir)]);

        expect(fn() => new GeradorEtiquetas($config)->postagem(dadosPostagem()))->toThrow(MarceloFecchio\Correios\Excecoes\ErroTemplate::class, 'codigo_objeto_texto');

        file_put_contents($dir . '/postagem.zpl', "^XA\n^FO10,10^FH^FD{{destinatario_nome}}^FS\n{{>logo_correios}}\n^XZ\n");
        $zpl = new GeradorEtiquetas($config)->postagem(dadosPostagem())->zpl;
        unlink($dir . '/postagem.zpl');
        rmdir($dir);

        expect($zpl)->toStartWith("^XA\n^FO10,10^FH^FDMaria Aparecida Souza^FS\n^FO41,56^GFA");
    });
});
