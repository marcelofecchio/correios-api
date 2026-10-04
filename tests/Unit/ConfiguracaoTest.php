<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Etiquetas\ConfiguracaoEtiquetas;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;

function configBase(array $alterar = []): array
{
    return array_merge([
        'ambiente' => 'homologacao',
        'usuario' => CNPJ_TESTE,
        'codigo_acesso' => 'segredo',
        'cnpj' => '11.222.333/0001-81',
        'cartao_postagem' => CARTAO_TESTE,
    ], $alterar);
}

describe('Ambiente', function (): void {
    it('define o host de cada ambiente', function (): void {
        expect(Ambiente::Homologacao->urlBase())->toBe('https://apihom.correios.com.br')
            ->and(Ambiente::Producao->urlBase())->toBe('https://api.correios.com.br')
            ->and(Ambiente::Homologacao->urlPortal())->toBe('https://cwshom.correios.com.br')
            ->and(Ambiente::Producao->urlPortal())->toBe('https://cws.correios.com.br');
    });

    it('interpreta textos comuns', function (string $texto, ?Ambiente $esperado): void {
        expect(Ambiente::deTexto($texto))->toBe($esperado);
    })->with([
        ['homologacao', Ambiente::Homologacao],
        ['HOM', Ambiente::Homologacao],
        ['produção', Ambiente::Producao],
        ['prod', Ambiente::Producao],
        ['outro', null],
    ]);
});

describe('Configuracao', function (): void {
    it('monta a partir de array com chaves snake_case', function (): void {
        $config = Configuracao::deArray(configBase([
            'contrato' => '9912345678',
            'diretoria_regional' => '20',
            'servicos' => ['SEDEX' => '03220', 'vazio' => '', 'pac' => '03298'],
            'timeout' => '12.5',
            'url_base' => 'http://localhost:8080/',
            'etiquetas' => ['resolucao' => '300', 'logos_por_servico' => ['sedex' => 'logo_servico_sedex']],
            'remetente_padrao' => [
                'nome' => 'Loja Teste',
                'endereco' => ['cep' => '01310100', 'logradouro' => 'Av. Paulista', 'numero' => '1000', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP'],
            ],
        ]));

        expect($config->ambiente)->toBe(Ambiente::Homologacao)
            ->and($config->cnpj)->toBe(CNPJ_TESTE)
            ->and($config->contrato)->toBe('9912345678')
            ->and($config->diretoriaRegional)->toBe(20)
            ->and($config->servicos)->toBe(['sedex' => '03220', 'pac' => '03298'])
            ->and($config->timeout)->toBe(12.5)
            ->and($config->urlApi())->toBe('http://localhost:8080')
            ->and($config->etiquetas->resolucao)->toBe(Resolucao::Dpi300)
            ->and($config->remetentePadrao)->toBeInstanceOf(Pessoa::class);
    });

    it('usa o host do ambiente quando não há urlBase', function (): void {
        expect(Configuracao::deArray(configBase(['ambiente' => 'PRODUCAO']))->urlApi())->toBe('https://api.correios.com.br')
            ->and(Configuracao::deArray(configBase(['ambiente' => Ambiente::Homologacao]))->urlApi())->toBe('https://apihom.correios.com.br');
    });

    it('lança erro claro para chave obrigatória ausente', function (string $chave): void {
        $config = configBase();
        unset($config[$chave]);
        Configuracao::deArray($config);
    })->with(['usuario', 'codigo_acesso', 'cnpj', 'cartao_postagem'])->throws(ErroConfiguracao::class, 'obrigatória');

    it('valida os valores', function (array $alterar, string $mensagem): void {
        expect(fn() => Configuracao::deArray(configBase($alterar)))->toThrow(ErroConfiguracao::class, $mensagem);
    })->with([
        [['ambiente' => 'teste-x'], 'ambiente'],
        [['ambiente' => 123], 'ambiente'],
        [['cnpj' => '11222333000180'], 'CNPJ'],
        [['cartao_postagem' => '12345678901'], 'cartão'],
        [['contrato' => '99-12'], 'contrato'],
        [['diretoria_regional' => 'dez'], 'diretoria_regional'],
        [['diretoria_regional' => '0'], 'diretoria regional'],
        [['servicos' => 'sedex'], 'servicos'],
        [['servicos' => ['sedex' => '3220']], '5 dígitos'],
        [['timeout' => 'rapido'], 'timeout'],
        [['timeout' => '0'], 'timeout'],
        [['url_base' => 'ftp://x'], 'urlBase'],
        [['usuario' => ['x']], 'texto'],
        [['remetente_padrao' => 'loja'], 'remetente_padrao'],
        [['remetente_padrao' => ['nome' => 'X']], 'remetente_padrao'],
        [['etiquetas' => 'x'], 'etiquetas'],
        [['etiquetas' => ['resolucao' => 150]], 'resolucao'],
        [['etiquetas' => ['datamatrix_servicos_adicionais' => '25']], '12 dígitos'],
        [['etiquetas' => ['diretorio_templates' => '/nao/existe']], 'não encontrado'],
        [['etiquetas' => ['nomes_servicos' => ['sedex' => ['x']]]], 'nomesServicos'],
    ]);

    it('recusa usuário ou código de acesso vazios pelo construtor', function (): void {
        expect(fn() => new Configuracao(Ambiente::Homologacao, ' ', 'x', CNPJ_TESTE, CARTAO_TESTE))->toThrow(ErroConfiguracao::class, 'usuário')
            ->and(fn() => new Configuracao(Ambiente::Homologacao, CNPJ_TESTE, ' ', CNPJ_TESTE, CARTAO_TESTE))->toThrow(ErroConfiguracao::class, 'cwshom');
    });

    it('resolve apelido ou código do serviço', function (): void {
        $config = configuracaoTeste();

        expect($config->resolverServico('SEDEX'))->toBe('03220')
            ->and($config->resolverServico('04162'))->toBe('04162')
            ->and(fn() => $config->resolverServico('carta'))->toThrow(ErroValidacao::class, 'sedex, pac')
            ->and(fn() => Configuracao::deArray(configBase())->resolverServico('x'))->toThrow(ErroValidacao::class, 'nenhum apelido');
    });

    it('não expõe o código de acesso no var_dump', function (): void {
        $config = configuracaoTeste();
        ob_start();
        var_dump($config);
        $saida = (string) ob_get_clean();

        expect($saida)->not->toContain('codigo-de-acesso-ficticio')->and($saida)->toContain('***');
    });

    it('aceita ConfiguracaoEtiquetas e Pessoa prontas', function (): void {
        $etiquetas = new ConfiguracaoEtiquetas(resolucao: Resolucao::Dpi300, marcaHomologacao: false, siglasAdicionais: ['019' => 'VD']);
        $config = Configuracao::deArray(configBase(['etiquetas' => $etiquetas, 'remetente_padrao' => lojaTeste(), 'diretoria_regional' => 20]));

        expect($config->etiquetas)->toBe($etiquetas)
            ->and($config->etiquetas->siglasAdicionais)->toBe(['019' => 'VD'])
            ->and(ConfiguracaoEtiquetas::deArray(['marca_homologacao' => 0, 'logo_correios' => 0, 'diretorio_templates' => dirname(__DIR__, 2) . '/resources/templates'])->logoCorreios)->toBeFalse();
    });
});
