<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Enums\StatusPrePostagem;
use MarceloFecchio\Correios\Enums\TipoDace;
use MarceloFecchio\Correios\Enums\Uf;
use MarceloFecchio\Correios\Http\MascaradorLog;
use MarceloFecchio\Correios\Http\Requisicao;
use MarceloFecchio\Correios\Http\RespostaHttp;
use MarceloFecchio\Correios\Http\TratadorErros;
use MarceloFecchio\Correios\Mapeadores\Leitor;
use MarceloFecchio\Correios\Mapeadores\TokenMapeador;

describe('Enums', function (): void {
    it('descrevem status, resolução, UF e tipo da DACE', function (): void {
        expect(array_map(fn(StatusPrePostagem $s) => $s->descricao(), StatusPrePostagem::cases()))
            ->toBe(['PREATENDIDO', 'PREPOSTADO', 'POSTADO', 'EXPIRADO', 'CANCELADO', 'ESTORNADO', 'PENDENTE'])
            ->and(StatusPrePostagem::Prepostado->podeImprimirRotulo())->toBeTrue()
            ->and(StatusPrePostagem::Pendente->podeImprimirRotulo())->toBeFalse()
            ->and(Resolucao::Dpi203->pontosPorMilimetro())->toBe(8)
            ->and(Resolucao::Dpi300->pontosPorMilimetro())->toBe(12)
            ->and(Resolucao::Dpi300->fator())->toBe(1.5)
            ->and(Uf::cases())->toHaveCount(27)
            ->and(Uf::deTexto(' rj '))->toBe(Uf::RJ)
            ->and(TipoDace::Termica->ehPdf())->toBeFalse();
    });
});

describe('Leitor', function (): void {
    it('lê tipos de forma defensiva', function (): void {
        $leitor = new Leitor([
            'texto' => 'a', 'vazio' => ' ', 'numero' => 12, 'decimal' => 1.5, 'inteiroFloat' => 3.0, 'inteiroTexto' => ' 42 ',
            'S' => 'S', 'N' => 'n', 'bool' => true, 'outro' => 'talvez', 'valor' => 'abc', 'lista' => ['x', '', 1, ['y']],
            'textoUnico' => 'só', 'naoLista' => ['a' => 1], 'mista' => [['a' => 1], 'b'],
        ]);

        expect($leitor->texto('texto'))->toBe('a')
            ->and($leitor->texto('vazio'))->toBeNull()
            ->and($leitor->texto('numero'))->toBe('12')
            ->and($leitor->texto('lista'))->toBeNull()
            ->and($leitor->inteiro('inteiroFloat'))->toBe(3)
            ->and($leitor->inteiro('inteiroTexto'))->toBe(42)
            ->and($leitor->inteiro('decimal'))->toBeNull()
            ->and($leitor->numero('decimal'))->toBe(1.5)
            ->and($leitor->numero('texto'))->toBeNull()
            ->and($leitor->booleano('S'))->toBeTrue()
            ->and($leitor->booleano('N'))->toBeFalse()
            ->and($leitor->booleano('bool'))->toBeTrue()
            ->and($leitor->booleano('outro'))->toBeNull()
            ->and($leitor->centavos('valor'))->toBeNull()
            ->and($leitor->centavos('lista'))->toBeNull()
            ->and($leitor->textos('lista'))->toBe(['x', '1'])
            ->and($leitor->textos('textoUnico'))->toBe(['só'])
            ->and($leitor->textos('numero'))->toBe([])
            ->and($leitor->lista('naoLista'))->toHaveCount(1)
            ->and($leitor->lista('mista'))->toHaveCount(1)
            ->and($leitor->lista('texto'))->toBe([])
            ->and($leitor->tem('texto'))->toBeTrue()
            ->and($leitor->tem('ausente'))->toBeFalse()
            ->and(Leitor::de('x')->bruto())->toBe([])
            ->and(new Leitor(['a' => 1])->itensRaiz())->toHaveCount(1)
            ->and(new Leitor([['a' => 1], 'x'])->itensRaiz())->toHaveCount(1);
    });
});

describe('TokenMapeador', function (): void {
    it('usa os ids de API do cartão e a data atual quando faltam campos', function (): void {
        $token = TokenMapeador::deApi(new Leitor([
            'token' => 't',
            'expiraEm' => '2026-10-05T10:00:00',
            'cartaoPostagem' => ['numero' => '1', 'api' => ['27', 34]],
            'contrato' => ['numero' => '9', 'dr' => 10],
            'apis' => [['api' => 'x']],
        ]), Ambiente::Producao);

        expect($token->apis)->toBe([27, 34])
            ->and($token->ambiente)->toBe(Ambiente::Producao)
            ->and($token->contrato)->toBe('9')
            ->and($token->diretoriaRegional)->toBe(10)
            ->and($token->emitidoEm)->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('lê a lista "apis" de objetos', function (): void {
        $token = TokenMapeador::deApi(new Leitor(['token' => 't', 'expiraEm' => '2026-10-05T10:00:00', 'apis' => [['api' => 41], ['api' => '76']]]), Ambiente::Homologacao);

        expect($token->apis)->toBe([41, 76]);
    });
});

describe('Camada HTTP interna', function (): void {
    it('monta query string com listas, booleanos e sem vazios', function (): void {
        $requisicao = new Requisicao('GET', '/x', 'teste', query: ['a' => ['1', '2'], 'b' => true, 'c' => false, 'd' => null, 'e' => '', 'f' => 'ç d']);

        expect($requisicao->queryString())->toBe('a=1&a=2&b=true&c=false&f=%C3%A7%20d');
    });

    it('decodifica a resposta só quando é JSON', function (): void {
        expect(new RespostaHttp(200, '')->json())->toBeNull()
            ->and(new RespostaHttp(200, 'texto')->ehJson())->toBeFalse()
            ->and(new RespostaHttp(200, '"abc"')->jsonArray())->toBe([])
            ->and(new RespostaHttp(200, '{"a":1}')->ehJson())->toBeTrue();
    });

    it('lê mensagens de vários formatos de erro', function (): void {
        expect(TratadorErros::mensagens(['msgs' => ['a', '', 1], 'mensagens' => ['b'], 'mensagem' => 'c', 'txErro' => 'd', 'message' => ' ']))->toBe(['a', '1', 'b', 'c', 'd'])
            ->and(TratadorErros::codigoErro(['sem código', 'SRO-020: não encontrado']))->toBe('SRO-020')
            ->and(TratadorErros::codigoErro(['PPN-145-5: Operação não realizada!']))->toBe('PPN-145-5')
            ->and(TratadorErros::codigoErro(['nada']))->toBeNull();
    });

    it('mascara dados sensíveis para o log', function (): void {
        $mascarado = MascaradorLog::array(['token' => 'abc', 'cpfCnpj' => '52998224725', 'dados' => 'JVBERi0', 'lista' => [['cnpj' => '11222333000181']], 'texto' => 'cpf 52998224725 no texto', 'n' => 5, 'curto' => ['documento' => '1']]);

        expect($mascarado['token'])->toBe('***')
            ->and($mascarado['cpfCnpj'])->toBe('*********25')
            ->and($mascarado['dados'])->toBe('[omitido: 7 caracteres]')
            ->and($mascarado['lista'][0]['cnpj'])->toBe('************81')
            ->and($mascarado['texto'])->toBe('cpf *********25 no texto')
            ->and($mascarado['n'])->toBe(5)
            ->and($mascarado['curto']['documento'])->toBe('**')
            ->and(MascaradorLog::corpo('<html>11222333000181</html>'))->toBe('<html>************81</html>');
    });
});
