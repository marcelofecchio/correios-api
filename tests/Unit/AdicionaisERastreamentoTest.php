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
use MarceloFecchio\Correios\Rastreamento\CatalogoEventos;
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
        'AP (homologação)' => [adicionalDisponivel('095', 'ARTIGOS PERIGOSOS ANAC', 'AP'), CategoriaServicoAdicional::ArtigosPerigosos],
        'EAR pela sigla' => [adicionalDisponivel('021', 'AVISO DE RECEBIMENTO ELETRONICO', 'EAR'), CategoriaServicoAdicional::AvisoRecebimentoEletronico],
        'ARDIG pela sigla' => [adicionalDisponivel('037', 'AVISO DE RECEBIMENTO DIGITAL', 'ARDIG'), CategoriaServicoAdicional::AvisoRecebimentoDigital],
        'AR eletrônico pelo nome' => [adicionalDisponivel('021', 'Aviso de Recebimento Eletrônico'), CategoriaServicoAdicional::AvisoRecebimentoEletronico],
        'AR digital pelo nome' => [adicionalDisponivel('037', 'AVISO DE RECEBIMENTO DIGITAL'), CategoriaServicoAdicional::AvisoRecebimentoDigital],
        'logística reversa' => [adicionalDisponivel('016', 'LOGISTICA REV SIMULTANEA VOLTA', 'LRSV'), null],
        'sem nome nem sigla' => [adicionalDisponivel('123', null), null],
    ]);

    it('localiza o adicional de uma categoria na lista do serviço', function (): void {
        $adicionais = [adicionalDisponivel('016', 'LOGISTICA REV SIMULTANEA VOLTA', 'LRSV'), adicionalDisponivel('019', 'VALOR DECLARADO NACIONAL', 'VD')];

        expect(CategoriaServicoAdicional::ValorDeclarado->localizar($adicionais)?->codigo)->toBe('019')
            ->and(CategoriaServicoAdicional::MaoPropria->localizar($adicionais))->toBeNull();
    });

    it('não confunde o AR convencional com o eletrônico e o digital', function (): void {
        $adicionais = [
            adicionalDisponivel('021', 'AVISO DE RECEBIMENTO ELETRONICO', 'EAR'),
            adicionalDisponivel('037', 'AVISO DE RECEBIMENTO DIGITAL', 'ARDIG'),
            adicionalDisponivel('001', 'AVISO DE RECEBIMENTO', 'AR'),
        ];

        expect(CategoriaServicoAdicional::AvisoRecebimento->localizar($adicionais)?->codigo)->toBe('001')
            ->and(CategoriaServicoAdicional::AvisoRecebimentoEletronico->localizar($adicionais)?->codigo)->toBe('021')
            ->and(CategoriaServicoAdicional::AvisoRecebimentoDigital->localizar($adicionais)?->codigo)->toBe('037');
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

    it('classifica pelo catálogo oficial, com o tipo mudando o significado do código', function (string $codigo, string $tipo, SituacaoRastreamento $esperada): void {
        expect(eventoRastreamento($codigo, $tipo)->situacao())->toBe($esperada);
    })->with([
        'etiqueta emitida (homologação)' => ['FC', '82', SituacaoRastreamento::AguardandoPostagem],
        'coleta solicitada' => ['CO', '2', SituacaoRastreamento::AguardandoPostagem],
        'objeto coletado' => ['CO', '1', SituacaoRastreamento::Postado],
        'carteiro não atendido' => ['BDE', '20', SituacaoRastreamento::Problema],
        'em devolução' => ['BDI', '05', SituacaoRastreamento::EmDevolucao],
        'entregue ao remetente' => ['BDR', '14', SituacaoRastreamento::Devolvido],
        'disponível em locker' => ['BDE', '77', SituacaoRastreamento::AguardandoRetirada],
        'aguardando retirada' => ['LDI', '01', SituacaoRastreamento::AguardandoRetirada],
        'encaminhado para retirada' => ['LDI', '14', SituacaoRastreamento::EmTransito],
        'saiu para entrega ao remetente' => ['OEC', '09', SituacaoRastreamento::EmDevolucao],
        'coleta cancelada' => ['CO', '10', SituacaoRastreamento::Cancelado],
        'sem descrição no catálogo' => ['FC', '66', SituacaoRastreamento::Desconhecida],
        'correção EST' => ['EST', '1', SituacaoRastreamento::Desconhecida],
        'fora do catálogo' => ['XYZ', '1', SituacaoRastreamento::Desconhecida],
    ]);

    it('não classifica evento sem código', function (): void {
        expect(eventoRastreamento(null, null)->situacao())->toBe(SituacaoRastreamento::Desconhecida)
            ->and(SituacaoRastreamento::doEvento(' ', '01'))->toBe(SituacaoRastreamento::Desconhecida)
            ->and(SituacaoRastreamento::Postado->emPosseDosCorreios())->toBeTrue()
            ->and(SituacaoRastreamento::AguardandoPostagem->emPosseDosCorreios())->toBeFalse();
    });

    it('ignora o evento anulado por EST e o próprio EST', function (): void {
        $rastreamento = new Rastreamento('AB043180865BR', [
            eventoRastreamento('PO', '01', '2026-10-05 10:00:00'),
            eventoRastreamento('BDE', '01', '2026-10-07 10:00:00'),
            eventoRastreamento('EST', '1', '2026-10-07 11:00:00'),
            eventoRastreamento('RO', '01', '2026-10-06 10:00:00'),
        ]);
        $doisEst = new Rastreamento('AB043180865BR', [
            eventoRastreamento('PO', '01', '2026-10-05 10:00:00'),
            eventoRastreamento('RO', '01', '2026-10-06 10:00:00'),
            eventoRastreamento('BDE', '01', '2026-10-07 10:00:00'),
            eventoRastreamento('EST', '1', '2026-10-07 11:00:00'),
            eventoRastreamento('EST', '2', '2026-10-07 12:00:00'),
        ]);

        expect($rastreamento->ultimoEvento()?->codigo)->toBe('EST')
            ->and($rastreamento->ultimoEventoValido()?->codigo)->toBe('RO')
            ->and($rastreamento->situacao())->toBe(SituacaoRastreamento::EmTransito)
            ->and($doisEst->situacao())->toBe(SituacaoRastreamento::Postado)
            ->and((new Rastreamento('X', [eventoRastreamento('EST', '1')]))->ultimoEventoValido())->toBeNull();
    });

    it('ordena os eventos sem data para o fim', function (): void {
        $semData = new EventoRastreamento('RO', '01', null, null);
        $rastreamento = new Rastreamento('AB043180865BR', [$semData, eventoRastreamento('PO', '01')]);

        expect($rastreamento->eventosRecentesPrimeiro()[1])->toBe($semData)
            ->and($rastreamento->ultimoEvento()?->codigo)->toBe('PO');
    });

    it('traz no catálogo os 376 eventos do arquivo oficial, com as mesmas descrições', function (): void {
        $arquivo = arquivoDoc('referencia/correios-eventos-rastreio.json');
        if (!is_file($arquivo)) {
            $this->markTestSkipped('doc/ não disponível');
        }
        $oficial = json_decode((string) file_get_contents($arquivo), true)[0]['tipoEventos'];
        $catalogo = CatalogoEventos::todos();

        expect($catalogo)->toHaveCount(376)->and(count($oficial))->toBe(376);
        foreach ($oficial as $evento) {
            $chave = trim($evento['tipo']);
            $descricao = trim($evento['descricao']);
            expect(CatalogoEventos::existe($chave, null))->toBeTrue();
            if (str_replace(' ', '-', $descricao) !== $chave) {
                expect(CatalogoEventos::descricao($chave, null))->toBe($descricao);
            }
        }
        expect(CatalogoEventos::descricao('FC', '82'))->toBe('Etiqueta emitida')
            ->and(CatalogoEventos::descricao('FC', '66'))->toBeNull()
            ->and(CatalogoEventos::descricao('BDE', '01'))->toBe('Objeto entregue ao destinatário')
            ->and(CatalogoEventos::chave('bde-01'))->toBe('BDE-1')
            ->and(CatalogoEventos::anulaAnterior('est'))->toBeTrue();
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
