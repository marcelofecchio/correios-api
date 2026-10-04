<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Dto\ConsultaDataPrevista;
use MarceloFecchio\Correios\Dto\ConsultaPrazo;
use MarceloFecchio\Correios\Dto\ConsultaPreco;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Enums\ResultadoRastreamento;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Respostas\ResultadoPrazo;
use MarceloFecchio\Correios\Respostas\ResultadoPreco;

function queryDe(Psr\Http\Message\RequestInterface $requisicao): string
{
    return urldecode($requisicao->getUri()->getQuery());
}

describe('Contrato', function (): void {
    it('lista uma página dos serviços do cartão', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'contrato_servicos_pagina0.json'));
        $pagina = correiosTeste($cliente)->contrato()->listarServicos(tokenTeste(), 0, 2);
        $requisicao = ultimaRequisicao($cliente);

        expect($requisicao->getMethod())->toBe('GET')
            ->and($requisicao->getUri()->getPath())->toBe('/meucontrato/v1/empresas/' . CNPJ_TESTE . '/contratos/' . CONTRATO_TESTE . '/cartoes/' . CARTAO_TESTE . '/servicos')
            ->and(queryDe($requisicao))->toBe('page=0&size=2')
            ->and($pagina->servicos)->toHaveCount(2)
            ->and($pagina->servicos[0]->codigo)->toBe('03220')
            ->and($pagina->servicos[0]->descricao)->toBe('SEDEX CONTRATO AG')
            ->and($pagina->servicos[0]->codigoSegmento)->toBe('1')
            ->and($pagina->totalPaginas)->toBe(2)
            ->and($pagina->temProxima())->toBeTrue()
            ->and($pagina->respostaBruta()['page']['totalElements'])->toBe(3);
    });

    it('percorre todas as páginas e aceita itens como objeto único', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'contrato_servicos_pagina0.json'), respostaFixture(200, 'contrato_servicos_pagina1.json'));
        $servicos = correiosTeste($cliente)->contrato()->listarTodosServicos(tokenTeste(), 2);

        expect(array_map(fn($s) => $s->codigo, $servicos))->toBe(['03220', '03298', '03247'])
            ->and($cliente->getRequests())->toHaveCount(2)
            ->and(queryDe(ultimaRequisicao($cliente)))->toBe('page=1&size=2');
    });

    it('consulta um serviço pelo apelido', function (): void {
        $cliente = clienteMock(respostaJson(200, ['codigo' => '03220', 'descricao' => 'SEDEX CONTRATO AG']), respostaJson(200, ['itens' => [['codigo' => '03298']]]));
        $correios = correiosTeste($cliente);
        $servico = $correios->contrato()->consultarServico(tokenTeste(), 'sedex');
        $requisicao = ultimaRequisicao($cliente);

        expect($servico->codigo)->toBe('03220')
            ->and($requisicao->getUri()->getPath())->toEndWith('/servicos/03220')
            ->and($correios->contrato()->consultarServico(tokenTeste(), '03298')->codigo)->toBe('03298');
    });

    it('lança erro se o serviço não vier na resposta', function (): void {
        correiosTeste(clienteMock(respostaJson(200, [])))->contrato()->consultarServico(tokenTeste(), 'pac');
    })->throws(ErroApi::class, '03298');

    it('usa o contrato do token quando a configuração não tem', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'contrato_servicos_pagina0.json'));
        correiosTeste($cliente, configuracaoTeste(['contrato' => null]))->contrato()->listarServicos(tokenTeste());

        expect(ultimaRequisicao($cliente)->getUri()->getPath())->toContain('/contratos/' . CONTRATO_TESTE . '/');
    });

    it('exige o contrato se o token vier como texto e a configuração não tiver', function (): void {
        correiosTeste(clienteMock(), configuracaoTeste(['contrato' => null]))->contrato()->listarServicos('token');
    })->throws(ErroConfiguracao::class, 'contrato');

    it('valida a página', function (): void {
        correiosTeste(clienteMock())->contrato()->listarServicos(tokenTeste(), -1);
    })->throws(ErroValidacao::class);
});

describe('CEP', function (): void {
    it('consulta o endereço e converte para Endereco', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'cep_01001000.json'));
        $endereco = correiosTeste($cliente)->cep()->consultar(tokenTeste(), '01001-000');

        expect((string) ultimaRequisicao($cliente)->getUri())->toBe('https://apihom.correios.com.br/cep/v2/enderecos/01001000')
            ->and($endereco->cep)->toBe('01001000')
            ->and($endereco->cidade)->toBe('São Paulo')
            ->and($endereco->uf)->toBe('SP')
            ->and($endereco->tipoCep)->toBe(2)
            ->and($endereco->codigoIbge)->toBe('3550308')
            ->and($endereco->paraEndereco('100', 'Apto 12')->cidadeUf())->toBe('São Paulo/SP');
    });

    it('valida o CEP antes de chamar a API', function (): void {
        $cliente = clienteMock();
        expect(fn() => correiosTeste($cliente)->cep()->consultar(tokenTeste(), '0100100'))->toThrow(ErroValidacao::class)
            ->and($cliente->getRequests())->toBeEmpty();
    });
});

describe('Preço', function (): void {
    $volume = new Volume(800, FormatoObjeto::CaixaPacote, comprimento: 30, largura: 20, altura: 10);

    it('consulta um serviço com contrato, DR, adicionais e valor declarado', function () use ($volume): void {
        $cliente = clienteMock(respostaFixture(200, 'preco_sedex.json'));
        $consulta = new ConsultaPreco('sedex', '01310100', '01001000', $volume, ['019', '001'], 25000, new DateTimeImmutable('2026-10-04 12:00:00', new DateTimeZone('America/Sao_Paulo')), 'pedido-1');
        $preco = correiosTeste($cliente)->preco()->calcular(tokenTeste(), $consulta);
        $requisicao = ultimaRequisicao($cliente);

        expect($requisicao->getMethod())->toBe('GET')
            ->and($requisicao->getUri()->getPath())->toBe('/preco/v1/nacional/03220')
            ->and(queryDe($requisicao))->toBe('coProduto=03220&nuRequisicao=pedido-1&nuContrato=' . CONTRATO_TESTE . '&nuDR=20&cepOrigem=01310100&cepDestino=01001000&psObjeto=800&tpObjeto=2&comprimento=30&largura=20&altura=10&servicosAdicionais=019&servicosAdicionais=001&vlDeclarado=250.00&dtEvento=04-10-2026')
            ->and($preco->precoFinalCentavos)->toBe(1992)
            ->and($preco->precoProdutoCentavos)->toBe(1532)
            ->and($preco->precoServicosAdicionaisCentavos)->toBe(460)
            ->and($preco->precoBaseCentavos)->toBe(1532)
            ->and($preco->pesoCobradoGramas)->toBe(1000)
            ->and($preco->cobradoPorPesoCubico)->toBeFalse()
            ->and($preco->adicionais[0]->codigo)->toBe('019')
            ->and($preco->adicionais[0]->precoCentavos)->toBe(460)
            ->and($preco->temErro())->toBeFalse()
            ->and($preco->respostaBruta()['pcFinal'])->toBe('19,92');
    });

    it('omite contrato e DR quando não há nenhum', function () use ($volume): void {
        $cliente = clienteMock(respostaFixture(200, 'preco_sedex.json'));
        correiosTeste($cliente, configuracaoTeste(['contrato' => null, 'diretoriaRegional' => null]))->preco()->calcular('token', new ConsultaPreco('03298', '01310100', '01001000', $volume));

        expect(queryDe(ultimaRequisicao($cliente)))->not->toContain('nuContrato')->not->toContain('nuDR');
    });

    it('exige a DR quando há contrato', function () use ($volume): void {
        correiosTeste(clienteMock(), configuracaoTeste(['diretoriaRegional' => null]))->preco()->calcular('token', new ConsultaPreco('sedex', '01310100', '01001000', $volume));
    })->throws(ErroConfiguracao::class, 'nuDR');

    it('mostra o ERP-049 quando falta o adicional obrigatório do SEDEX REVERSO', function () use ($volume): void {
        try {
            correiosTeste(clienteMock(respostaFixture(400, 'erro_erp049.json')))->preco()->calcular(tokenTeste(), new ConsultaPreco('sedex_reverso', '01001000', '01310100', $volume));
            $this->fail('Deveria lançar ErroApi');
        } catch (ErroApi $e) {
            expect($e->codigoErro)->toBe('ERP-049')->and($e->getMessage())->toContain('(016)');
        }
    });

    it('calcula lote com HTTP 206 e itens mistos, correlacionados pelo nuRequisicao', function () use ($volume): void {
        $cliente = clienteMock(respostaFixture(206, 'preco_lote_206.json'));
        $lote = correiosTeste($cliente)->preco()->calcularLote(tokenTeste(), [
            new ConsultaPreco('sedex', '01310100', '01001000', $volume),
            new ConsultaPreco('sedex_reverso', '01001000', '01310100', $volume),
        ], 'lote-teste');
        $corpo = corpoJson(ultimaRequisicao($cliente));

        expect(ultimaRequisicao($cliente)->getMethod())->toBe('POST')
            ->and(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/preco/v1/nacional')
            ->and($corpo['idLote'])->toBe('lote-teste')
            ->and($corpo['parametrosProduto'][0])->toMatchArray(['coProduto' => '03220', 'nuRequisicao' => '1', 'nuContrato' => CONTRATO_TESTE, 'nuDR' => 20, 'psObjeto' => '800', 'tpObjeto' => '2'])
            ->and($corpo['parametrosProduto'][1]['coProduto'])->toBe('03247')
            ->and($corpo['parametrosProduto'][1]['nuRequisicao'])->toBe('2')
            ->and($lote->parcial())->toBeTrue()
            ->and($lote->todosComSucesso())->toBeFalse()
            ->and($lote->porRequisicao('1')?->sucesso())->toBeTrue()
            ->and($lote->porRequisicao('1')?->resultado)->toBeInstanceOf(ResultadoPreco::class)
            ->and($lote->porRequisicao('1')?->resultado->precoFinalCentavos)->toBe(121532)
            ->and($lote->porRequisicao('2')?->erro())->toContain('ERP-049')
            ->and($lote->porRequisicao('9'))->toBeNull()
            ->and($lote->comErro())->toHaveCount(1)
            ->and($lote->respostaBruta())->toHaveCount(2);
    });

    it('limita o lote a 5 consultas e exige números de requisição únicos', function () use ($volume): void {
        $consulta = new ConsultaPreco('sedex', '01310100', '01001000', $volume, nuRequisicao: 'x');
        $preco = correiosTeste(clienteMock())->preco();

        expect(fn() => $preco->calcularLote('t', array_fill(0, 6, $consulta)))->toThrow(ErroValidacao::class, '5')
            ->and(fn() => $preco->calcularLote('t', []))->toThrow(ErroValidacao::class)
            ->and(fn() => $preco->calcularLote('t', [$consulta, $consulta]))->toThrow(ErroValidacao::class, 'únicos');
    });

    it('gera um idLote quando não informado', function () use ($volume): void {
        $cliente = clienteMock(respostaJson(200, [['nuRequisicao' => '1', 'pcFinal' => '10,00']]));
        $lote = correiosTeste($cliente)->preco()->calcularLote('t', [new ConsultaPreco('sedex', '01310100', '01001000', $volume)]);

        expect($lote->idLote)->toStartWith('lote-')->and($lote->todosComSucesso())->toBeTrue()->and($lote->parcial())->toBeFalse();
    });

    it('lista os adicionais com a obrigatoriedade (sgIndicador O e P)', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'servicos_adicionais_03247.json'));
        $adicionais = correiosTeste($cliente)->preco()->servicosAdicionais(tokenTeste(), 'sedex_reverso', 25000, new DateTimeImmutable('2026-10-04'));

        expect(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/preco/v1/servicos-adicionais/03247')
            ->and(queryDe(ultimaRequisicao($cliente)))->toBe('vlDeclarado=250.00&dtEvento=2026-10-04&sgPais=BR')
            ->and($adicionais[0]->codigo)->toBe('016')
            ->and($adicionais[0]->obrigatorio())->toBeTrue()
            ->and($adicionais[0]->opcional())->toBeFalse()
            ->and($adicionais[0]->sigla)->toBe('LRSV')
            ->and($adicionais[0]->requeridos)->toBe(['019'])
            ->and($adicionais[0]->excludentes)->toBe(['001'])
            ->and($adicionais[0]->inicioVigencia?->format('Y-m-d'))->toBe('2026-08-28')
            ->and($adicionais[0]->precoCentavos)->toBe(0)
            ->and($adicionais[1]->opcional())->toBeTrue()
            ->and($adicionais[1]->valorMaximoDeclaradoCentavos)->toBe(1000050);
    });
});

describe('Prazo', function (): void {
    it('consulta o prazo de um serviço', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'prazo_sedex.json'));
        $prazo = correiosTeste($cliente)->prazo()->calcular(tokenTeste(), new ConsultaPrazo('sedex', '01310100', '01001000', new DateTimeImmutable('2026-10-04')));

        expect(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prazo/v1/nacional/03220')
            ->and(queryDe(ultimaRequisicao($cliente)))->toBe('cepOrigem=01310100&cepDestino=01001000&dtEvento=04-10-2026')
            ->and($prazo->prazoDias)->toBe(1)
            ->and($prazo->dataMaxima?->format('Y-m-d'))->toBe('2026-10-06')
            ->and($prazo->entregaSabado)->toBeTrue()
            ->and($prazo->entregaDomingo)->toBeFalse()
            ->and($prazo->temErro())->toBeFalse();
    });

    it('calcula lote com datas no formato de cada campo', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'prazo_lote.json'));
        $lote = correiosTeste($cliente)->prazo()->calcularLote(tokenTeste(), [
            new ConsultaPrazo('sedex', '01310100', '01001000', new DateTimeImmutable('2026-10-04'), new DateTimeImmutable('2026-10-05')),
            new ConsultaPrazo('pac', '01310100', '01001000'),
        ], 'lote-prazo');
        $corpo = corpoJson(ultimaRequisicao($cliente));

        expect($corpo['parametrosPrazo'][0])->toBe(['coProduto' => '03220', 'nuRequisicao' => '1', 'dtEvento' => '04/10/2026', 'cepOrigem' => '01310100', 'cepDestino' => '01001000', 'dataPostagem' => '2026-10-05'])
            ->and($corpo['parametrosPrazo'][1]['coProduto'])->toBe('03298')
            ->and($lote->itens)->toHaveCount(2)
            ->and($lote->porRequisicao('2')?->resultado)->toBeInstanceOf(ResultadoPrazo::class)
            ->and($lote->porRequisicao('2')?->resultado->prazoDias)->toBe(5);
    });

    it('valida o lote de prazo', function (): void {
        $consulta = new ConsultaPrazo('sedex', '01310100', '01001000', nuRequisicao: 'a');
        $prazo = correiosTeste(clienteMock())->prazo();

        expect(fn() => $prazo->calcularLote('t', []))->toThrow(ErroValidacao::class)
            ->and(fn() => $prazo->calcularLote('t', [$consulta, $consulta]))->toThrow(ErroValidacao::class, 'únicos');
    });

    it('calcula a data prevista', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'data_prevista.json'));
        $data = correiosTeste($cliente)->prazo()->dataPrevista(tokenTeste(), new ConsultaDataPrevista('01310100', '01001000', 3, new DateTimeImmutable('2026-10-04'), 'pac', sabadoDiaUtil: false));

        expect(ultimaRequisicao($cliente)->getUri()->getPath())->toBe('/prazo/v1/data-prevista')
            ->and(queryDe(ultimaRequisicao($cliente)))->toBe('cepOrigem=01310100&cepDestino=01001000&prazo=3&dtPostagem=04-10-2026&isSabadoDiaUtil=false&coProduto=03298')
            ->and($data->data?->format('Y-m-d'))->toBe('2026-10-09')
            ->and($data->mensagem)->toBe('Entrega prevista');
    });
});

describe('Rastro', function (): void {
    it('rastreia um objeto com todos os eventos', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'rastro_objeto.json'));
        $rastreamento = correiosTeste($cliente)->rastro()->rastrear(tokenTeste(), 'dg 049 186 226 br');

        expect((string) ultimaRequisicao($cliente)->getUri())->toBe('https://apihom.correios.com.br/srorastro/v1/objetos/DG049186226BR?resultado=T')
            ->and($rastreamento->codigoObjeto)->toBe('DG049186226BR')
            ->and($rastreamento->encontrado())->toBeTrue()
            ->and($rastreamento->eventos)->toHaveCount(2)
            ->and($rastreamento->tipoPostal)->toBe('SEDEX')
            ->and($rastreamento->ultimoEvento()?->codigo)->toBe('BDE')
            ->and($rastreamento->ultimoEvento()?->cidade)->toBe('SÃO PAULO')
            ->and($rastreamento->eventos[1]->unidadeDestino)->toBe('CTE Vila Maria')
            ->and($rastreamento->dataPrevista?->format('Y-m-d'))->toBe('2026-10-07');
    });

    it('rastreia vários objetos repetindo o parâmetro', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'rastro_varios.json'));
        $lista = correiosTeste($cliente)->rastro()->rastrearVarios(tokenTeste(), ['AB043180865BR', 'AK039775832BR', ''], ResultadoRastreamento::Ultimo);

        expect(queryDe(ultimaRequisicao($cliente)))->toBe('codigosObjetos=AB043180865BR&codigosObjetos=AK039775832BR&resultado=U')
            ->and($lista)->toHaveCount(2)
            ->and($lista[1]->encontrado())->toBeFalse()
            ->and($lista[1]->mensagem)->toContain('SRO-020')
            ->and($lista[1]->ultimoEvento())->toBeNull();
    });

    it('limita a 50 objetos', function (): void {
        $codigos = array_fill(0, 51, 'AB043180865BR');
        $codigos = array_map(fn(int $i) => sprintf('AB%09dBR', $i), range(1, 51));
        correiosTeste(clienteMock())->rastro()->rastrearVarios(tokenTeste(), $codigos);
    })->throws(ErroValidacao::class, '50');

    it('devolve rastreamento vazio se a API não trouxer o objeto', function (): void {
        $rastreamento = correiosTeste(clienteMock(respostaJson(200, ['objetos' => []])))->rastro()->rastrear(tokenTeste(), 'AB043180865BR');

        expect($rastreamento->encontrado())->toBeFalse()->and($rastreamento->codigoObjeto)->toBe('AB043180865BR');
    });
});
