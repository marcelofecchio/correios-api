<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Utilitarios\Cep;
use MarceloFecchio\Correios\Utilitarios\ChaveDocumentoFiscal;
use MarceloFecchio\Correios\Utilitarios\CodigoRastreamento;
use MarceloFecchio\Correios\Utilitarios\Datas;
use MarceloFecchio\Correios\Utilitarios\Dinheiro;
use MarceloFecchio\Correios\Utilitarios\Documento;
use MarceloFecchio\Correios\Utilitarios\Texto;

describe('Cep', function (): void {
    it('normaliza com ou sem hífen, preservando zeros à esquerda', function (string $entrada): void {
        expect(Cep::normalizar($entrada))->toBe('01001000');
    })->with(['01001-000', '01001000', ' 01.001-000 ']);

    it('recusa CEP incompleto ou com letras', function (string $cep): void {
        Cep::normalizar($cep);
    })->with(['0100100', '010010000', '01001-00A', ''])->throws(ErroValidacao::class);

    it('formata com hífen', function (): void {
        expect(Cep::formatar('01310100'))->toBe('01310-100');
    });
});

describe('Documento', function (): void {
    it('valida CPF e CNPJ pelo dígito verificador', function (): void {
        expect(Documento::cpfValido('529.982.247-25'))->toBeTrue()
            ->and(Documento::cpfValido('529.982.247-24'))->toBeFalse()
            ->and(Documento::cpfValido('111.111.111-11'))->toBeFalse()
            ->and(Documento::cnpjValido('11.222.333/0001-81'))->toBeTrue()
            ->and(Documento::cnpjValido('11.222.333/0001-80'))->toBeFalse()
            ->and(Documento::cnpjValido('00000000000000'))->toBeFalse()
            ->and(Documento::valido('123'))->toBeFalse();
    });

    it('normaliza removendo a máscara', function (): void {
        expect(Documento::normalizar('529.982.247-25'))->toBe('52998224725')
            ->and(Documento::normalizar('11.222.333/0001-81'))->toBe('11222333000181');
    });

    it('recusa documento inválido com o nome do campo', function (): void {
        Documento::normalizar('123.456.789-00', 'destinatario.cpf');
    })->throws(ErroValidacao::class, 'destinatario.cpf');

    it('formata CPF e CNPJ', function (): void {
        expect(Documento::formatar('52998224725'))->toBe('529.982.247-25')
            ->and(Documento::formatar('11222333000181'))->toBe('11.222.333/0001-81')
            ->and(Documento::formatar('123'))->toBe('123')
            ->and(Documento::ehCpf('52998224725'))->toBeTrue();
    });
});

describe('CodigoRastreamento', function (): void {
    it('confere o dígito com os códigos reais da documentação', function (string $codigo): void {
        expect(CodigoRastreamento::digitoValido($codigo))->toBeTrue()
            ->and(new CodigoRastreamento($codigo)->valor)->toBe($codigo);
    })->with(['AB043180865BR', 'AK039775832BR', 'DG049186226BR', 'AA123456785BR']);

    it('remove espaços e converte para maiúsculas', function (): void {
        $codigo = new CodigoRastreamento('ab 043 180 865 br');

        expect($codigo->valor)->toBe('AB043180865BR')
            ->and($codigo->formatado())->toBe('AB 043 180 865 BR')
            ->and((string) $codigo)->toBe('AB043180865BR');
    });

    it('recusa dígito errado, mas aceita só o formato quando pedido', function (): void {
        expect(fn() => new CodigoRastreamento('AB043180866BR'))->toThrow(ErroValidacao::class, 'Dígito')
            ->and(new CodigoRastreamento('AB043180866BR', validarDigito: false)->valor)->toBe('AB043180866BR')
            ->and(CodigoRastreamento::digitoValido('XX'))->toBeFalse();
    });

    it('recusa formato inválido', function (): void {
        new CodigoRastreamento('A1043180865BR');
    })->throws(ErroValidacao::class, 'formato');

    it('aplica as regras de resto 0 e 1', function (): void {
        // soma ≡ 0 (mod 11) → 5 ; soma = 7 × 8 = 56 ≡ 1 (mod 11) → 0
        expect(CodigoRastreamento::calcularDigito('00000000'))->toBe(5)
            ->and(CodigoRastreamento::calcularDigito('70000000'))->toBe(0);
    });

    it('exige 8 dígitos para calcular', function (): void {
        CodigoRastreamento::calcularDigito('123');
    })->throws(ErroValidacao::class);
});

describe('ChaveDocumentoFiscal', function (): void {
    $chave = '35261011222333000181550020000214861123456788';

    it('valida e decompõe a chave', function () use ($chave): void {
        $c = new ChaveDocumentoFiscal($chave);

        expect($c->codigoUf())->toBe('35')
            ->and($c->anoMes())->toBe('2610')
            ->and($c->cnpjEmitente())->toBe('11222333000181')
            ->and($c->modelo())->toBe('55')
            ->and($c->serie())->toBe('002')
            ->and($c->numero())->toBe('000021486')
            ->and($c->tipoEmissao())->toBe('1')
            ->and($c->codigoNumerico())->toBe('12345678')
            ->and($c->digito())->toBe(8)
            ->and($c->formatada())->toBe('3526 1011 2223 3300 0181 5500 2000 0214 8611 2345 6788')
            ->and((string) $c)->toBe($chave)
            ->and(ChaveDocumentoFiscal::valida($chave))->toBeTrue();
    });

    it('recusa dígito trocado e tamanho errado', function () use ($chave): void {
        expect(fn() => new ChaveDocumentoFiscal(substr($chave, 0, 43) . '1'))->toThrow(ErroValidacao::class, 'Dígito')
            ->and(fn() => new ChaveDocumentoFiscal('123'))->toThrow(ErroValidacao::class, '44 dígitos')
            ->and(ChaveDocumentoFiscal::valida('123'))->toBeFalse()
            ->and(fn() => ChaveDocumentoFiscal::calcularDigito('1'))->toThrow(ErroValidacao::class);
    });

    it('aceita espaços e devolve dígito 0 quando o resto é menor que 2', function (): void {
        $base = '3526101122233300018155002000021486112345670';
        $digito = ChaveDocumentoFiscal::calcularDigito(substr($base, 0, 43));

        expect(new ChaveDocumentoFiscal(implode(' ', str_split(substr($base, 0, 43) . $digito, 4)))->valor)->toHaveLength(44);
    });
});

describe('Dinheiro', function (): void {
    it('lê os formatos das APIs em centavos', function (string $texto, int $centavos): void {
        expect(Dinheiro::paraCentavos($texto))->toBe($centavos);
    })->with([
        'preço com vírgula' => ['19,92', 1992],
        'milhar e vírgula' => ['1.234,56', 123456],
        'ponto decimal' => ['150.00', 15000],
        'milhar americano' => ['1,234.56', 123456],
        'inteiro' => ['25', 2500],
        'quatro casas arredonda para cima' => ['0,1050', 11],
        'quatro casas arredonda para baixo' => ['0,1040', 10],
        'com símbolo' => ['R$ 10,00', 1000],
        'negativo' => ['-5,50', -550],
        'só fração' => [',5', 50],
    ]);

    it('recusa texto que não é valor', function (string $texto): void {
        Dinheiro::paraCentavos($texto);
    })->with(['', 'abc', '10,00x'])->throws(ErroValidacao::class);

    it('formata para a API e para exibição', function (): void {
        expect(Dinheiro::formatarApi(15000))->toBe('150.00')
            ->and(Dinheiro::formatarApi(5))->toBe('0.05')
            ->and(Dinheiro::formatarApi(-550))->toBe('-5.50')
            ->and(Dinheiro::formatarBr(123456))->toBe('1.234,56')
            ->and(Dinheiro::formatarBr(-5))->toBe('-0,05')
            ->and(Dinheiro::numeroParaCentavos(21.5))->toBe(2150)
            ->and(Dinheiro::numeroParaCentavos(0.1 + 0.2))->toBe(30);
    });
});

describe('Datas', function (): void {
    it('interpreta data sem fuso no horário de Brasília', function (): void {
        $data = Datas::interpretar('2026-10-05T10:26:51');

        expect($data?->format('Y-m-d H:i:s'))->toBe('2026-10-05 10:26:51')
            ->and($data?->getTimezone()->getName())->toBe('America/Sao_Paulo');
    });

    it('mantém o fuso informado', function (): void {
        expect(Datas::interpretar('2026-09-29T14:55:29-03:00')?->format(DATE_ATOM))->toBe('2026-09-29T14:55:29-03:00');
    });

    it('aceita os formatos de cada campo', function (string $texto, string $esperado): void {
        expect(Datas::interpretar($texto)?->format('Y-m-d H:i:s'))->toBe($esperado);
    })->with([
        ['18/10/2026', '2026-10-18 00:00:00'],
        ['18-10-2026', '2026-10-18 00:00:00'],
        ['2026-10-18', '2026-10-18 00:00:00'],
        ['18/10/2026 13:45:10', '2026-10-18 13:45:10'],
        ['2026-10-18T13:45:10.123', '2026-10-18 13:45:10'],
    ]);

    it('devolve null para vazio ou formato desconhecido', function (mixed $valor): void {
        expect(Datas::interpretar($valor))->toBeNull();
    })->with([null, '', 'amanhã', 123, '2026-13-45']);

    it('formata a data de calendário como informada, sem converter o fuso', function (): void {
        expect(Datas::formatar(new DateTimeImmutable('2026-10-18', new DateTimeZone('UTC')), 'd/m/Y'))->toBe('18/10/2026')
            ->and(Datas::formatar(new DateTimeImmutable('2026-10-18', new DateTimeZone('Asia/Tokyo')), 'Y-m-d'))->toBe('2026-10-18');
    });
});

describe('Texto', function (): void {
    it('remove acentos com tabela própria', function (): void {
        expect(Texto::semAcentos('Ação É Ç ñ º'))->toBe('Acao E C n o');
    });

    it('limpa controle e espaços e corta por caracteres', function (): void {
        expect(Texto::limpar("  A\tB\nC   D "))->toBe('A B C D')
            ->and(Texto::cortar('Ação', 2))->toBe('Aç')
            ->and(Texto::tamanho('Ação'))->toBe(4)
            ->and(Texto::digitos('(11) 98765-4321'))->toBe('11987654321')
            ->and(Texto::juntar(', ', 'Rua X', '100', null, ' '))->toBe('Rua X, 100')
            ->and(Texto::semControle("CNPJ: 1  IE: 2\nfim"))->toBe('CNPJ: 1  IE: 2 fim');
    });

    it('encurta só a primeira parte para caber no limite', function (): void {
        expect(Texto::juntarCortandoPrimeira(' - ', 30, 'Rua Muito Comprida Demais, 1000', 'Cidade/UF', '01001-000'))->toBe('Rua Mu - Cidade/UF - 01001-000')
            ->and(Texto::juntarCortandoPrimeira(' - ', 60, 'Rua A, 1', 'Cidade/UF', null))->toBe('Rua A, 1 - Cidade/UF')
            ->and(Texto::juntarCortandoPrimeira(' - ', 10, 'Rua Comprida', null))->toBe('Rua Compri')
            ->and(Texto::juntarCortandoPrimeira(' - ', 12, 'Rua', 'Cidade Grande/UF'))->toBe('Cidade Grand');
    });
});
