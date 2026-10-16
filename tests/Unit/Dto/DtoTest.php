<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Dto\ConsultaDataPrevista;
use MarceloFecchio\Correios\Dto\ConsultaPrazo;
use MarceloFecchio\Correios\Dto\ConsultaPreco;
use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Item;
use MarceloFecchio\Correios\Dto\NovaPrePostagem;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Dto\ServicoAdicional;
use MarceloFecchio\Correios\Dto\SolicitacaoDace;
use MarceloFecchio\Correios\Dto\SolicitacaoRotulo;
use MarceloFecchio\Correios\Dto\Volume;
use MarceloFecchio\Correios\Enums\FormatoObjeto;
use MarceloFecchio\Correios\Enums\TipoDace;
use MarceloFecchio\Correios\Enums\Uf;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;

function enderecoValido(array $alterar = []): Endereco
{
    $dados = array_merge([
        'cep' => '01001-000',
        'logradouro' => 'Praça da Sé',
        'numero' => '100',
        'bairro' => 'Sé',
        'cidade' => 'São Paulo',
        'uf' => 'sp',
        'complemento' => 'Apto 12',
    ], $alterar);

    return new Endereco(...$dados);
}

describe('Endereco', function (): void {
    it('normaliza CEP, UF e número S/N', function (): void {
        $endereco = enderecoValido(['numero' => 's/n']);

        expect($endereco->cep)->toBe('01001000')
            ->and($endereco->uf)->toBe(Uf::SP)
            ->and($endereco->numero)->toBe('S/N')
            ->and($endereco->pais)->toBe('BR')
            ->and($endereco->cidadeUf())->toBe('São Paulo/SP');
    });

    it('valida os tamanhos máximos do arquivo 06', function (string $campo, string $valor): void {
        enderecoValido([$campo => $valor]);
    })->with([
        ['logradouro', str_repeat('a', 51)],
        ['numero', '1234567'],
        ['complemento', str_repeat('b', 31)],
        ['bairro', str_repeat('c', 31)],
        ['cidade', str_repeat('d', 31)],
        ['logradouro', '  '],
    ])->throws(ErroValidacao::class);

    it('recusa UF inexistente', function (): void {
        enderecoValido(['uf' => 'XX']);
    })->throws(ErroValidacao::class, 'UF');

    it('trata complemento vazio como nulo e monta a partir de array', function (): void {
        $endereco = Endereco::deArray(['cep' => '01310100', 'logradouro' => 'Avenida Paulista', 'numero' => 1000, 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP', 'complemento' => ' ']);

        expect($endereco->complemento)->toBeNull()
            ->and($endereco->numero)->toBe('1000');
    });
});

describe('Pessoa', function (): void {
    it('guarda documento e telefones só com dígitos', function (): void {
        $pessoa = new Pessoa('Maria Aparecida Souza', enderecoValido(), '529.982.247-25', 'maria@exemplo.com', '(11) 3333-4444', '(11) 98765-4321');

        expect($pessoa->documento)->toBe('52998224725')
            ->and($pessoa->telefone)->toBe('1133334444')
            ->and($pessoa->dddTelefone())->toBe('11')
            ->and($pessoa->numeroTelefone())->toBe('33334444')
            ->and($pessoa->dddCelular())->toBe('11')
            ->and($pessoa->numeroCelular())->toBe('987654321');
    });

    it('aceita campos opcionais vazios', function (): void {
        $pessoa = new Pessoa('Loja Teste', enderecoValido(), ' ', '', null, null);

        expect($pessoa->documento)->toBeNull()
            ->and($pessoa->email)->toBeNull()
            ->and($pessoa->dddTelefone())->toBeNull()
            ->and($pessoa->numeroCelular())->toBeNull();
    });

    it('valida nome, documento, e-mail e telefones', function (array $dados, string $mensagem): void {
        expect(fn() => new Pessoa(...array_merge(['nome' => 'Maria Souza', 'endereco' => enderecoValido()], $dados)))
            ->toThrow(ErroValidacao::class, $mensagem);
    })->with([
        [['nome' => 'Jo'], 'nome'],
        [['nome' => str_repeat('x', 51)], 'nome'],
        [['documento' => '123.456.789-00'], 'documento'],
        [['email' => 'email-invalido'], 'email'],
        [['telefone' => '11 9999'], 'telefone'],
        [['celular' => '1199999999'], 'celular'],
    ]);

    it('monta a partir de array', function (): void {
        $pessoa = Pessoa::deArray([
            'nome' => 'Loja Teste',
            'documento' => '11222333000181',
            'endereco' => ['cep' => '01310100', 'logradouro' => 'Av. Paulista', 'numero' => '1000', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP'],
        ]);

        expect($pessoa->documento)->toBe('11222333000181')
            ->and(fn() => Pessoa::deArray(['nome' => 'Sem Endereco']))->toThrow(ErroValidacao::class, 'endereco');
    });

    it('lê contribuinte_icms do array', function (mixed $valor, ?bool $esperado): void {
        $endereco = ['cep' => '01310100', 'logradouro' => 'Av. Paulista', 'numero' => '1000', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP'];

        expect(Pessoa::deArray(['nome' => 'Loja Teste', 'endereco' => $endereco, 'contribuinte_icms' => $valor])->contribuinteIcms)->toBe($esperado)
            ->and(Pessoa::deArray(['nome' => 'Loja Teste', 'endereco' => $endereco, 'contribuinteIcms' => true])->contribuinteIcms)->toBeTrue()
            ->and(fn() => Pessoa::deArray(['nome' => 'Loja Teste', 'endereco' => $endereco, 'contribuinte_icms' => 'talvez']))->toThrow(ErroValidacao::class, 'contribuinte_icms');
    })->with([
        [true, true],
        [false, false],
        ['true', true],
        ['0', false],
        ['', null],
        [null, null],
    ]);
});

describe('Item', function (): void {
    it('guarda o valor em centavos e calcula o total', function (): void {
        $item = Item::comValor('Teclado para notebook', 2, '150,00', 300, '8471.60.52');

        expect($item->valorUnitarioCentavos)->toBe(15000)
            ->and($item->valorTotalCentavos())->toBe(30000)
            ->and($item->ncm)->toBe('84716052');
    });

    it('aplica as regras da descrição e dos números', function (string $descricao, int $quantidade, int $valor): void {
        new Item($descricao, $quantidade, $valor);
    })->with([
        'curta' => ['A', 1, 100],
        'longa' => [str_repeat('a', 61), 1, 100],
        'caracteres iguais' => ['aaaa', 1, 100],
        'dois caracteres não letras' => ['A1', 1, 100],
        'quantidade zero' => ['Livro', 0, 100],
        'valor negativo' => ['Livro', 1, -1],
    ])->throws(ErroValidacao::class);

    it('aceita duas letras e recusa peso e NCM inválidos', function (): void {
        expect(new Item('TV', 1, 100)->descricao)->toBe('TV')
            ->and(fn() => new Item('Livro', 1, 100, 0))->toThrow(ErroValidacao::class, 'peso')
            ->and(fn() => new Item('Livro', 1, 100, null, '123456789'))->toThrow(ErroValidacao::class, 'ncm');
    });
});

describe('Volume', function (): void {
    it('exige altura, largura e comprimento para pacote', function (): void {
        new Volume(500, FormatoObjeto::CaixaPacote, comprimento: 20, largura: 15);
    })->throws(ErroValidacao::class, 'altura');

    it('exige comprimento e diâmetro para rolo', function (): void {
        new Volume(500, FormatoObjeto::CilindroRolo, comprimento: 20);
    })->throws(ErroValidacao::class, 'diametro');

    it('aceita envelope só com o peso e valida os limites', function (): void {
        expect(new Volume(100, FormatoObjeto::Envelope)->pesoGramas)->toBe(100)
            ->and(fn() => new Volume(0))->toThrow(ErroValidacao::class, 'peso')
            ->and(fn() => new Volume(1_000_000, FormatoObjeto::Envelope))->toThrow(ErroValidacao::class, 'peso')
            ->and(fn() => new Volume(100, FormatoObjeto::CaixaPacote, 1000, 10, 10))->toThrow(ErroValidacao::class, 'comprimento');
    });
});

describe('ServicoAdicional', function (): void {
    it('oferece construtores para AR e valor declarado', function (): void {
        expect(ServicoAdicional::avisoRecebimento()->codigo)->toBe('001')
            ->and(ServicoAdicional::avisoRecebimento()->ehValorDeclarado())->toBeFalse()
            ->and(ServicoAdicional::valorDeclarado('064', 25000)->valorDeclaradoCentavos)->toBe(25000)
            ->and(ServicoAdicional::valorDeclarado('019', 25000)->ehValorDeclarado())->toBeTrue();
    });

    it('valida o código e o valor', function (): void {
        expect(fn() => new ServicoAdicional('19'))->toThrow(ErroValidacao::class, 'código')
            ->and(fn() => new ServicoAdicional('019', 0))->toThrow(ErroValidacao::class, 'valorDeclarado');
    });
});

describe('NovaPrePostagem', function (): void {
    $volume = new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10);

    it('exige documento fiscal', function () use ($volume): void {
        new NovaPrePostagem(destinatario: clienteTeste(), servico: 'sedex', volume: $volume);
    })->throws(ErroValidacao::class, 'documentoFiscal');

    it('aceita chave da NF-e, número da nota ou itens', function () use ($volume): void {
        $comChave = new NovaPrePostagem(clienteTeste(), 'sedex', $volume, chaveNFe: '35261011222333000181550020000214861123456788');
        $comNota = new NovaPrePostagem(clienteTeste(), 'sedex', $volume, numeroNotaFiscal: '1234');
        $comItens = new NovaPrePostagem(clienteTeste(), 'sedex', $volume, itens: ['a' => new Item('Livro', 1, 1000)]);

        expect($comChave->chaveNFe?->numero())->toBe('000021486')
            ->and($comNota->numeroNotaFiscal)->toBe('1234')
            ->and($comItens->itens)->toHaveCount(1)
            ->and(array_is_list($comItens->itens))->toBeTrue();
    });

    it('valida número da nota, observação e pedido', function (array $dados, string $campo) use ($volume): void {
        expect(fn() => new NovaPrePostagem(...array_merge([
            'destinatario' => clienteTeste(),
            'servico' => 'sedex',
            'volume' => $volume,
            'itens' => [new Item('Livro', 1, 1000)],
        ], $dados)))->toThrow(ErroValidacao::class, $campo);
    })->with([
        [['numeroNotaFiscal' => '1234567890123'], 'numeroNotaFiscal'],
        [['numeroNotaFiscal' => '12.34'], 'numeroNotaFiscal'],
        [['observacao' => str_repeat('o', 51)], 'observacao'],
        [['pedidoExterno' => str_repeat('p', 26)], 'pedidoExterno'],
        [['servico' => ' '], 'servico'],
        [['codigoObjetoIda' => 'AB043180866BR'], 'codigoObjeto'],
    ]);

    it('exige itens quando os Correios emitem a DCe', function () use ($volume): void {
        new NovaPrePostagem(clienteTeste(), 'sedex', $volume, numeroNotaFiscal: '1', emiteDCe: true);
    })->throws(ErroValidacao::class, 'emiteDCe');

    it('exige remetente com e-mail na logística reversa', function () use ($volume): void {
        $semEmail = new Pessoa('Maria Souza', clienteTeste()->endereco, CPF_TESTE);

        expect(fn() => new NovaPrePostagem(lojaTeste(), 'sedex_reverso', $volume, itens: [new Item('Livro', 1, 1000)], logisticaReversa: true))
            ->toThrow(ErroValidacao::class, 'remetente')
            ->and(fn() => new NovaPrePostagem(lojaTeste(), 'sedex_reverso', $volume, remetente: $semEmail, itens: [new Item('Livro', 1, 1000)], logisticaReversa: true))
            ->toThrow(ErroValidacao::class, 'remetente.email');
    });

    it('aceita código do objeto de ida', function () use ($volume): void {
        $nova = new NovaPrePostagem(lojaTeste(), 'sedex_reverso', $volume, remetente: clienteTeste(), itens: [new Item('Livro', 1, 1000)], logisticaReversa: true, codigoObjetoIda: new CodigoRastreamento('AB043180865BR'));

        expect($nova->codigoObjetoIda?->valor)->toBe('AB043180865BR');
    });
});

describe('Consultas e solicitações', function (): void {
    $volume = new Volume(800, FormatoObjeto::CaixaPacote, 30, 20, 10);

    it('normaliza CEPs e códigos de adicionais na consulta de preço', function () use ($volume): void {
        $consulta = new ConsultaPreco('sedex', '01310-100', '01001-000', $volume, [' 019 ']);

        expect($consulta->cepOrigem)->toBe('01310100')
            ->and($consulta->servicosAdicionais)->toBe(['019'])
            ->and($consulta->formato())->toBe(FormatoObjeto::CaixaPacote)
            ->and(fn() => new ConsultaPreco('sedex', '01310100', '01001000', $volume, ['19']))->toThrow(ErroValidacao::class, 'servicosAdicionais')
            ->and(fn() => new ConsultaPreco('', '01310100', '01001000', $volume))->toThrow(ErroValidacao::class, 'servico')
            ->and(fn() => new ConsultaPreco('sedex', '01310100', '01001000', $volume, valorDeclaradoCentavos: 0))->toThrow(ErroValidacao::class, 'valorDeclarado');
    });

    it('valida consulta de prazo e de data prevista', function (): void {
        expect(new ConsultaPrazo('pac', '01310100', '01001-000')->cepDestino)->toBe('01001000')
            ->and(fn() => new ConsultaPrazo(' ', '01310100', '01001000'))->toThrow(ErroValidacao::class)
            ->and(fn() => new ConsultaDataPrevista('01310100', '01001000', -1, new DateTimeImmutable()))->toThrow(ErroValidacao::class, 'prazo');
    });

    it('remove ids vazios e repetidos na solicitação do rótulo', function (): void {
        $solicitacao = SolicitacaoRotulo::porIds(['PR1', '', ' ', 'PR1', 'PR2']);

        expect($solicitacao->idsPrePostagem)->toBe(['PR1', 'PR2'])
            ->and(SolicitacaoRotulo::porCodigos(['ab043180865br', new CodigoRastreamento('AK039775832BR')])->codigosObjeto)->toBe(['AB043180865BR', 'AK039775832BR']);
    });

    it('exige ids ou códigos, nunca os dois', function (): void {
        expect(fn() => new SolicitacaoRotulo(['', ' ']))->toThrow(ErroValidacao::class)
            ->and(fn() => new SolicitacaoRotulo(['PR1'], ['AB043180865BR']))->toThrow(ErroValidacao::class, 'não os dois')
            ->and(fn() => new SolicitacaoDace(TipoDace::Termica))->toThrow(ErroValidacao::class)
            ->and(fn() => new SolicitacaoDace(TipoDace::Termica, ['PR1'], ['AB043180865BR']))->toThrow(ErroValidacao::class, 'não os dois')
            ->and(new SolicitacaoDace(TipoDace::Completa, codigosObjetos: ['AB043180865BR'])->codigosObjetos)->toBe(['AB043180865BR']);
    });
});
