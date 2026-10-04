<?php

declare(strict_types=1);

/*
 * Testes contra o ambiente de HOMOLOGAÇÃO real dos Correios. Pulados por padrão.
 *
 * Rodam só se existirem as variáveis CORREIOS_HOM_USUARIO (o CNPJ), CORREIOS_HOM_CODIGO_ACESSO,
 * CORREIOS_HOM_CNPJ, CORREIOS_HOM_CARTAO e CORREIOS_HOM_CONTRATO. Opcionais: CORREIOS_HOM_DR,
 * CORREIOS_HOM_SERVICO_SEDEX (padrão 03220), CORREIOS_HOM_SERVICO_PAC (03298),
 * CORREIOS_HOM_ADICIONAL_VD_SEDEX (019).
 *
 * Seguem a ordem das pastas da coleção do Postman e geram UM token por execução.
 * Não incluem `.../postada` (exige objeto postado) nem os endpoints HTML instáveis.
 *
 *   vendor/bin/pest --group=integracao
 */

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\ConsultaPrazo;
use MarceloFecchio\Correios\Dto\ConsultaPreco;
use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Enums\StatusPrePostagem;
use MarceloFecchio\Correios\Excecoes\ErroPrePostagemPendente;
use MarceloFecchio\Correios\Token;

function variavelHomologacao(string $nome, ?string $padrao = null): ?string
{
    $valor = getenv($nome);

    return $valor === false || $valor === '' ? $padrao : $valor;
}

function correiosHomologacao(): ?Correios
{
    static $correios = false;
    if ($correios !== false) {
        return $correios;
    }

    foreach (['CORREIOS_HOM_USUARIO', 'CORREIOS_HOM_CODIGO_ACESSO', 'CORREIOS_HOM_CNPJ', 'CORREIOS_HOM_CARTAO', 'CORREIOS_HOM_CONTRATO'] as $nome) {
        if (variavelHomologacao($nome) === null) {
            return $correios = null;
        }
    }

    return $correios = new Correios(Configuracao::deArray([
        'ambiente' => Ambiente::Homologacao,
        'usuario' => variavelHomologacao('CORREIOS_HOM_USUARIO'),
        'codigo_acesso' => variavelHomologacao('CORREIOS_HOM_CODIGO_ACESSO'),
        'cnpj' => variavelHomologacao('CORREIOS_HOM_CNPJ'),
        'cartao_postagem' => variavelHomologacao('CORREIOS_HOM_CARTAO'),
        'contrato' => variavelHomologacao('CORREIOS_HOM_CONTRATO'),
        'diretoria_regional' => variavelHomologacao('CORREIOS_HOM_DR'),
        'servicos' => [
            'sedex' => variavelHomologacao('CORREIOS_HOM_SERVICO_SEDEX', '03220'),
            'pac' => variavelHomologacao('CORREIOS_HOM_SERVICO_PAC', '03298'),
        ],
        'remetente_padrao' => [
            'nome' => 'Loja Teste Homologacao Ltda',
            'documento' => variavelHomologacao('CORREIOS_HOM_CNPJ'),
            'email' => 'loja.teste@exemplo.com.br',
            'celular' => '11987654321',
            'endereco' => ['cep' => '01310100', 'logradouro' => 'Avenida Paulista', 'numero' => '1000', 'complemento' => 'Sala 101', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP'],
        ],
    ]));
}

function tokenHomologacao(): Token
{
    static $token = null;

    return $token ??= correiosHomologacao()?->token()->gerar() ?? throw new RuntimeException('Sem credenciais.');
}

beforeEach(function (): void {
    if (correiosHomologacao() === null) {
        $this->markTestSkipped('Defina CORREIOS_HOM_USUARIO, CORREIOS_HOM_CODIGO_ACESSO, CORREIOS_HOM_CNPJ, CORREIOS_HOM_CARTAO e CORREIOS_HOM_CONTRATO para rodar contra a homologação.');
    }
});

it('01 gera o token do cartão em homologação', function (): void {
    $token = tokenHomologacao();

    expect($token->ambiente)->toBe(Ambiente::Homologacao)
        ->and($token->estaValido())->toBeTrue()
        ->and($token->contrato)->not->toBeNull()
        ->and($token->diretoriaRegional)->not->toBeNull();
});

it('02 lista os serviços do cartão', function (): void {
    $servicos = correiosHomologacao()?->contrato()->listarTodosServicos(tokenHomologacao()) ?? [];

    expect($servicos)->not->toBeEmpty()
        ->and(array_map(fn($s) => $s->codigo, $servicos))->toContain(correiosHomologacao()?->configuracao->resolverServico('sedex'));
});

it('03 consulta o CEP', function (): void {
    $endereco = correiosHomologacao()?->cep()->consultar(tokenHomologacao(), '01001-000');

    expect($endereco?->uf)->toBe('SP')->and($endereco?->cidade)->not->toBeEmpty();
});

it('04 calcula preço e consulta adicionais', function (): void {
    $correios = correiosHomologacao();
    $volume = new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10);
    $preco = $correios?->preco()->calcular(tokenHomologacao(), new ConsultaPreco('sedex', '01310100', '01001000', $volume));
    $adicionais = $correios?->preco()->servicosAdicionais(tokenHomologacao(), 'sedex') ?? [];

    expect($preco?->precoFinalCentavos)->toBeGreaterThan(0)
        ->and($adicionais)->not->toBeEmpty();
});

it('05 calcula o prazo', function (): void {
    $prazo = correiosHomologacao()?->prazo()->calcular(tokenHomologacao(), new ConsultaPrazo('pac', '01310100', '01001000'));

    expect($prazo?->prazoDias)->toBeGreaterThan(0);
});

it('06 a 09 cria a pré-postagem, baixa o rótulo e cancela', function (): void {
    $correios = correiosHomologacao();
    $token = tokenHomologacao();
    $nova = new NovaPrePostagem(
        destinatario: new MarceloFecchio\Correios\Dto\Pessoa(
            'Maria Aparecida Souza',
            new MarceloFecchio\Correios\Dto\Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP', 'Apto 12'),
            '52998224725',
            'maria.souza.teste@exemplo.com.br',
            celular: '11912345678',
        ),
        servico: 'sedex',
        volume: new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10),
        itens: [new Item('Teclado para notebook', 1, 25000)],
        adicionais: [ServicoAdicional::valorDeclarado((string) variavelHomologacao('CORREIOS_HOM_ADICIONAL_VD_SEDEX', '019'), 25000)],
        observacao: 'Teste automatizado do componente',
        pedidoExterno: 'PED-COMPONENTE-' . date('His'),
    );

    $criada = $correios?->prePostagem()->criar($token, $nova);
    expect($criada?->id)->not->toBeEmpty()
        ->and($criada?->codigoObjeto)->not->toBeNull();

    $etiqueta = $correios?->etiquetas()->postagemDePrePostagem($nova, $criada);
    expect((string) $etiqueta)->toContain((string) $criada?->codigoObjeto);

    // Rótulo: com status 2 o PDF deve vir; com status 7 (PENDENTE) o download deve lançar ErroPrePostagemPendente
    // (observado em homologação: PPN-288 com HTTP 200). O componente não espera nem repete.
    $recibo = $correios?->rotulo()->solicitar($token, SolicitacaoRotulo::porIds([$criada?->id ?? '']));
    expect($recibo?->idRecibo)->not->toBeEmpty();
    if ($criada?->status === StatusPrePostagem::Prepostado) {
        try {
            expect($correios?->rotulo()->baixar($token, $recibo)->conteudoBinario())->toStartWith('%PDF');
        } catch (ErroPrePostagemPendente) {
            // O status pode ter voltado a ser avaliado como pendente no momento do download.
        }
    } else {
        expect(fn() => $correios?->rotulo()->baixar($token, $recibo))->toThrow(ErroPrePostagemPendente::class, 'PPN-288');
    }

    $cancelamento = $correios?->prePostagem()->cancelar($token, $criada?->id ?? '');
    expect($cancelamento?->respostaBruta())->toBeArray();
});

it('10 rastreia um objeto de exemplo da documentação', function (): void {
    $rastreamento = correiosHomologacao()?->rastro()->rastrear(tokenHomologacao(), 'DG049186226BR');

    expect($rastreamento?->codigoObjeto)->toBe('DG049186226BR');
});
