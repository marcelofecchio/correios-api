<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Enums\CategoriaServicoAdicional;
use MarceloFecchio\Correios\Enums\SituacaoRastreamento;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\FabricaCorreios;
use MarceloFecchio\Correios\Mapeadores\Leitor;
use MarceloFecchio\Correios\Mapeadores\PrePostagemMapeador;
use MarceloFecchio\Correios\Respostas\EventoRastreamento;
use MarceloFecchio\Correios\Respostas\Rastreamento;
use MarceloFecchio\Correios\Respostas\ServicoAdicionalDisponivel;

function adicionalDisponivel(string $codigo, ?string $nome, ?string $sigla = null): ServicoAdicionalDisponivel
{
    return new ServicoAdicionalDisponivel(codigo: $codigo, nome: $nome, sigla: $sigla);
}

function eventoRastreamento(?string $codigo, ?string $tipo, string $data = '2026-10-07 10:00:00'): EventoRastreamento
{
    return new EventoRastreamento($codigo, $tipo, null, new DateTimeImmutable($data));
}

describe('CategoriaServicoAdicional', function (): void {
    it('identifica a categoria pela sigla ou pelo nome', function (ServicoAdicionalDisponivel $adicional, ?CategoriaServicoAdicional $esperada): void {
        expect($adicional->categoria())->toBe($esperada);
    })->with([
        'VD (API Preço)' => [adicionalDisponivel('019', 'VALOR DECLARADO NACIONAL', 'VD'), CategoriaServicoAdicional::ValorDeclarado],
        'VDS (rótulo do PAC)' => [adicionalDisponivel('064', null, 'VDS'), CategoriaServicoAdicional::ValorDeclarado],
        'MP pela sigla' => [adicionalDisponivel('002', 'QUALQUER', 'MP'), CategoriaServicoAdicional::MaoPropria],
        'MP pelo nome com acento' => [adicionalDisponivel('002', 'Mão Própria Nacional'), CategoriaServicoAdicional::MaoPropria],
        'AR pela sigla' => [adicionalDisponivel('001', null, 'AR'), CategoriaServicoAdicional::AvisoRecebimento],
        'AR pelo nome' => [adicionalDisponivel('001', 'AVISO DE RECEBIMENTO'), CategoriaServicoAdicional::AvisoRecebimento],
        'artigos perigosos pelo nome' => [adicionalDisponivel('099', 'Transporte de artigos perigosos'), CategoriaServicoAdicional::ArtigosPerigosos],
        'logística reversa' => [adicionalDisponivel('016', 'LOGISTICA REV SIMULTANEA VOLTA', 'LRSV'), null],
        'sem nome nem sigla' => [adicionalDisponivel('123', null), null],
    ]);

    it('localiza o adicional de uma categoria na lista do serviço', function (): void {
        $adicionais = [adicionalDisponivel('016', 'LOGISTICA REV SIMULTANEA VOLTA', 'LRSV'), adicionalDisponivel('019', 'VALOR DECLARADO NACIONAL', 'VD')];

        expect(CategoriaServicoAdicional::ValorDeclarado->localizar($adicionais)?->codigo)->toBe('019')
            ->and(CategoriaServicoAdicional::MaoPropria->localizar($adicionais))->toBeNull();
    });

    it('monta o adicional da pré-postagem a partir do disponível', function (): void {
        $vd = ServicoAdicional::de(adicionalDisponivel('064', 'VALOR DECLARADO NACIONAL', 'VDS'), 25000);
        $mp = ServicoAdicional::de(adicionalDisponivel('002', null, 'MP'));

        expect($vd->codigo)->toBe('064')
            ->and($vd->ehValorDeclarado())->toBeTrue()
            ->and($mp->codigo)->toBe('002')
            ->and($mp->ehValorDeclarado())->toBeFalse()
            ->and(ServicoAdicional::maoPropria('002')->codigo)->toBe('002')
            ->and(fn() => ServicoAdicional::de(adicionalDisponivel('019', null, 'VD')))->toThrow(ErroValidacao::class, 'valor declarado');
    });
});

describe('SituacaoRastreamento', function (): void {
    it('classifica os eventos confirmados na documentação', function (string $codigo, string $tipo, SituacaoRastreamento $esperada): void {
        expect(eventoRastreamento($codigo, $tipo)->situacao())->toBe($esperada);
    })->with([
        ['PO', '01', SituacaoRastreamento::Postado],
        ['RO', '01', SituacaoRastreamento::EmTransito],
        ['OEC', '03', SituacaoRastreamento::SaiuParaEntrega],
        ['bde', '1', SituacaoRastreamento::Entregue],
    ]);

    it('não classifica código conhecido com outro tipo nem código fora da tabela', function (): void {
        expect(eventoRastreamento('BDE', '20')->situacao())->toBe(SituacaoRastreamento::Desconhecida)
            ->and(eventoRastreamento('LDI', '01')->situacao())->toBe(SituacaoRastreamento::Desconhecida)
            ->and(eventoRastreamento(null, null)->situacao())->toBe(SituacaoRastreamento::Desconhecida)
            ->and(SituacaoRastreamento::doEvento(' ', '01'))->toBe(SituacaoRastreamento::Desconhecida);
    });

    it('usa o mapa da aplicação com prioridade: código e tipo, depois só o código', function (): void {
        $mapa = [
            'BDE-20' => SituacaoRastreamento::Problema,
            'ldi' => SituacaoRastreamento::AguardandoRetirada,
            'PO-01' => SituacaoRastreamento::EmTransito,
            'FC-a1' => SituacaoRastreamento::Devolvido,
        ];

        expect(eventoRastreamento('BDE', '20')->situacao($mapa))->toBe(SituacaoRastreamento::Problema)
            ->and(eventoRastreamento('BDE', '01')->situacao($mapa))->toBe(SituacaoRastreamento::Entregue)
            ->and(eventoRastreamento('LDI', '14')->situacao($mapa))->toBe(SituacaoRastreamento::AguardandoRetirada)
            ->and(eventoRastreamento('PO', '1')->situacao($mapa))->toBe(SituacaoRastreamento::EmTransito)
            ->and(eventoRastreamento('FC', 'A1')->situacao($mapa))->toBe(SituacaoRastreamento::Devolvido);
    });

    it('usa o evento mais recente do objeto', function (): void {
        $rastreamento = new Rastreamento('AB043180865BR', [
            eventoRastreamento('BDE', '01', '2026-10-07 10:00:00'),
            eventoRastreamento('PO', '01', '2026-10-05 10:00:00'),
        ]);

        expect($rastreamento->situacao())->toBe(SituacaoRastreamento::Entregue)
            ->and($rastreamento->situacao()->finalizada())->toBeTrue()
            ->and(SituacaoRastreamento::SaiuParaEntrega->finalizada())->toBeFalse()
            ->and((new Rastreamento('AB043180865BR'))->situacao())->toBe(SituacaoRastreamento::Desconhecida);
    });
});

describe('Pré-postagem', function (): void {
    it('expõe os limites de texto do schema', function (): void {
        expect(NovaPrePostagem::MAXIMO_OBSERVACAO)->toBe(50)
            ->and(NovaPrePostagem::MAXIMO_PEDIDO_EXTERNO)->toBe(25);
    });

    it('lê ehObjetoDgr como objetoPerigoso', function (?string $valor, ?bool $esperado): void {
        $dados = ['id' => 'PR1'] + ($valor !== null ? ['ehObjetoDgr' => $valor] : []);

        expect(PrePostagemMapeador::prePostagem(new Leitor($dados))->objetoPerigoso)->toBe($esperado);
    })->with([
        ['S', true],
        ['n', false],
        [null, null],
    ]);
});

describe('FabricaCorreios', function (): void {
    it('cria um Correios por configuração com o mesmo cliente HTTP', function (): void {
        $cliente = clienteMock(respostaFixture(200, 'cep_01001000.json'), respostaFixture(200, 'cep_01001000.json'));
        $fabricaHttp = new GuzzleHttp\Psr7\HttpFactory();
        $fabrica = new FabricaCorreios($cliente, $fabricaHttp, $fabricaHttp);

        $homologacao = $fabrica->criar(configuracaoTeste());
        $producao = $fabrica->deArray(['ambiente' => 'producao', 'usuario' => 'login', 'codigo_acesso' => 'segredo', 'cnpj' => CNPJ_TESTE, 'cartao_postagem' => CARTAO_TESTE]);
        $homologacao->cep()->consultar(tokenTeste(), '01001000');
        $producao->cep()->consultar(tokenTeste(), '01001000');

        expect($homologacao)->toBeInstanceOf(Correios::class)
            ->and($producao->configuracao->ambiente)->toBe(Ambiente::Producao)
            ->and(array_map(fn($r) => $r->getUri()->getHost(), $cliente->getRequests()))->toBe(['apihom.correios.com.br', 'api.correios.com.br']);
    });
});
