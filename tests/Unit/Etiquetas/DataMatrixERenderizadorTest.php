<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Etiquetas\DataMatrixCorreios;
use MarceloFecchio\Correios\Etiquetas\EscalaZpl;
use MarceloFecchio\Correios\Etiquetas\ImagemZpl;
use MarceloFecchio\Correios\Etiquetas\RenderizadorZpl;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroTemplate;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;

describe('DataMatrixCorreios', function (): void {
    it('monta a cadeia de 164 caracteres campo a campo (exemplo da seção 4 do guia, cartão fictício)', function (): void {
        $cadeia = new DataMatrixCorreios('01001000', '01310100', 'AB043180865BR', CARTAO_TESTE, '03220', '100', 'APTO 12')->gerar();

        $campos = [
            [0, 8, '01001000'],          // CEP destino
            [8, 5, '00000'],             // complemento do CEP
            [13, 8, '01310100'],         // CEP origem
            [21, 5, '00000'],            // complemento do CEP de origem
            [26, 1, '8'],                // validador do CEP de destino
            [27, 2, '51'],               // IDV
            [29, 13, 'AB043180865BR'],   // etiqueta
            [42, 12, '250000000000'],    // serviços adicionais (valor antigo)
            [54, 10, CARTAO_TESTE],      // cartão
            [64, 5, '03220'],            // serviço
            [69, 2, '00'],               // agrupamento
            [71, 5, '00100'],            // número do logradouro
            [76, 20, 'APTO 12' . str_repeat(' ', 13)],
            [96, 5, '00000'],            // valor declarado
            [101, 12, '000000000000'],   // telefone
            [113, 10, '-00.000000'],     // latitude
            [123, 10, '-00.000000'],     // longitude
            [133, 1, '|'],
            [134, 30, str_repeat('0', 30)],
        ];

        expect(strlen($cadeia))->toBe(164);
        foreach ($campos as [$inicio, $tamanho, $esperado]) {
            expect(substr($cadeia, $inicio, $tamanho))->toBe($esperado);
        }
    });

    it('reproduz a cadeia da etiqueta gerada pelo sistema antigo', function (): void {
        $arquivo = arquivoDoc('legado/etiqueta-postagem-zpl/etiqueta_exemplo.zpl');
        if (!is_file($arquivo)) {
            $this->markTestSkipped('A pasta doc/legado não está disponível (ela não vai no pacote).');
        }
        $zpl = (string) file_get_contents($arquivo);
        preg_match('/\^BXN[^\^]*\^FD([^\^]{164})\^FS/', $zpl, $m);
        $antiga = $m[1] ?? '';

        // Campos impressos na etiqueta que conferem com a cadeia
        preg_match('/\^BCN,,Y,Y\s*\^FD([A-Z]{2}\d{9}[A-Z]{2})/', $zpl, $objeto);
        preg_match('/\^BCN,,N,N\s*\^FD\s*(\d{8})/', $zpl, $cepImpresso);

        $gerada = new DataMatrixCorreios(
            cepDestino: substr($antiga, 0, 8),
            cepOrigem: substr($antiga, 13, 8),
            codigoObjeto: $objeto[1],
            cartaoPostagem: substr($antiga, 54, 10),
            codigoServico: substr($antiga, 64, 5),
            numeroLogradouro: ltrim(substr($antiga, 71, 5), '0'),
            complemento: rtrim(substr($antiga, 76, 20)),
        )->gerar();

        expect($antiga)->toHaveLength(164)
            ->and($cepImpresso[1])->toBe(substr($antiga, 0, 8))
            ->and($objeto[1])->toBe(substr($antiga, 29, 13))
            ->and($gerada)->toBe($antiga);
    });

    it('calcula o validador do CEP', function (): void {
        expect(DataMatrixCorreios::validadorCep('01001000'))->toBe(8)
            ->and(DataMatrixCorreios::validadorCep('93220680'))->toBe(0);
    });

    it('usa só os dígitos do número, com até 5', function (string $numero, string $esperado): void {
        $cadeia = new DataMatrixCorreios('01001000', '01310100', 'AB043180865BR', '1', '3220', $numero)->gerar();

        expect(substr($cadeia, 71, 5))->toBe($esperado)
            ->and(substr($cadeia, 54, 10))->toBe('0000000001')
            ->and(substr($cadeia, 64, 5))->toBe('03220');
    })->with([['S/N', '00000'], ['123A', '00123'], ['1234567', '12345'], ['', '00000']]);

    it('normaliza o complemento para 20 posições em ASCII maiúsculo', function (string $complemento, string $esperado): void {
        $cadeia = new DataMatrixCorreios('01001000', '01310100', 'AB043180865BR', '1', '03220', '1', $complemento)->gerar();

        expect(substr($cadeia, 76, 20))->toBe($esperado)->and(strlen($cadeia))->toBe(164);
    })->with([
        ['Apto 3 – Bl. Ç', str_pad('APTO 3  BL C', 20)],
        ['Bloco Residencial Jardim das Flores', 'BLOCO RESIDENCIAL JA'],
        ['', str_repeat(' ', 20)],
    ]);

    it('aceita campos fixos parametrizáveis e reserva do cliente', function (): void {
        $cadeia = new DataMatrixCorreios('01001000', '01310100', 'AB043180865BR', '1', '03220', '1', '', '120000000000', '25000', '011987654321', 'PED123')->gerar();

        expect(substr($cadeia, 42, 12))->toBe('120000000000')
            ->and(substr($cadeia, 96, 5))->toBe('25000')
            ->and(substr($cadeia, 101, 12))->toBe('011987654321')
            ->and(substr($cadeia, 134))->toBe('PED123' . str_repeat('0', 24));
    });

    it('valida cada argumento com o nome do campo', function (array $argumentos, string $campo): void {
        $padrao = ['cepDestino' => '01001000', 'cepOrigem' => '01310100', 'codigoObjeto' => 'AB043180865BR', 'cartaoPostagem' => '1', 'codigoServico' => '03220'];
        expect(fn() => new DataMatrixCorreios(...array_merge($padrao, $argumentos)))->toThrow(ErroValidacao::class, $campo);
    })->with([
        [['cepDestino' => '123'], 'cepDestino'],
        [['codigoObjeto' => 'AB043180866BR'], 'codigoObjeto'],
        [['cartaoPostagem' => '12345678901'], 'cartaoPostagem'],
        [['codigoServico' => '032200'], 'codigoServico'],
        [['servicosAdicionais' => '25'], 'servicosAdicionais'],
        [['valorDeclarado' => 'ABCDE'], 'valorDeclarado'],
        [['telefoneDestinatario' => '1'], 'telefoneDestinatario'],
        [['reservaCliente' => 'pedido-1'], 'reservaCliente'],
    ]);
});

describe('RenderizadorZpl', function (): void {
    beforeEach(function (): void {
        $this->dir = sys_get_temp_dir() . '/zpl-' . uniqid();
        mkdir($this->dir . '/parciais', 0777, true);
        mkdir($this->dir . '/300dpi/parciais', 0777, true);
        file_put_contents($this->dir . '/teste.zpl', "\u{FEFF}^XA\r\n^FX comentario\n^FO10,20^A0N,28,28^FH^FD{{texto}}^FS\n^FO10,60^BCN,,Y,N^FD{{{codigo}}}^FS\n{{>logo}}\n{{>ausente}}\n\n^XZ");
        file_put_contents($this->dir . '/parciais/logo.zpl', "^FX parcial\n^FO100,100^GB50,50,4^FS\n");
        file_put_contents($this->dir . '/300dpi/parciais/logo.zpl', "^FO1,1^GB9,9,9^FS\n");
    });

    afterEach(function (): void {
        foreach (['300dpi/parciais/logo.zpl', 'parciais/logo.zpl', 'teste.zpl'] as $arquivo) {
            @unlink($this->dir . '/' . $arquivo);
        }
        @rmdir($this->dir . '/300dpi/parciais');
        @rmdir($this->dir . '/300dpi');
        @rmdir($this->dir . '/parciais');
        @rmdir($this->dir);
    });

    it('escapa os valores, mantém os crus, inclui parciais e remove comentários e BOM', function (): void {
        $zpl = new RenderizadorZpl($this->dir)->renderizar('teste', ['texto' => "Loja_A^B~C\\D Ação\nfim", 'codigo' => 'AB043180865BR'], ['logo' => 'logo', 'ausente' => null]);

        expect($zpl)->toBe("^XA\n^FO10,20^A0N,28,28^FH^FDLoja_5FA_5EB_7EC_5CD Ação fim^FS\n^FO10,60^BCN,,Y,N^FDAB043180865BR^FS\n^FO100,100^GB50,50,4^FS\n^XZ\n");
    });

    it('não reinterpreta um valor que contém marcador (passada única) e escapa as chaves', function (): void {
        $zpl = new RenderizadorZpl($this->dir)->renderizar('teste', ['texto' => '{{codigo}}', 'codigo' => 'X1']);

        expect($zpl)->toContain('^FH^FD_7B_7Bcodigo_7D_7D^FS')
            ->and($zpl)->toContain('^FDX1^FS');
    });

    it('lança ErroTemplate se o template tiver marcador malformado sobrando', function (): void {
        file_put_contents($this->dir . '/teste.zpl', "^XA\n^FD{{ nome com espaço }}^FS\n^XZ");
        new RenderizadorZpl($this->dir)->renderizar('teste', []);
    })->throws(ErroTemplate::class, 'Sobrou');

    it('lança ErroTemplate para marcador sem valor', function (): void {
        new RenderizadorZpl($this->dir)->renderizar('teste', ['texto' => 'x']);
    })->throws(ErroTemplate::class, '"codigo"');

    it('recusa ^ e ~ em valor cru', function (): void {
        new RenderizadorZpl($this->dir)->renderizar('teste', ['texto' => 'x', 'codigo' => 'A^B']);
    })->throws(ErroTemplate::class, 'cru');

    it('usa o parcial de 300 dpi sem escalar e escala o template', function (): void {
        $zpl = new RenderizadorZpl($this->dir)->renderizar('teste', ['texto' => 'x', 'codigo' => 'Y'], ['logo' => 'logo'], Resolucao::Dpi300);

        expect($zpl)->toContain('^FO15,30^A0N,42,42')
            ->and($zpl)->toContain('^FO1,1^GB9,9,9^FS');
    });

    it('aceita caminho de arquivo e cai no template do pacote', function (): void {
        $renderizador = new RenderizadorZpl($this->dir);
        $porCaminho = $renderizador->renderizar($this->dir . '/teste.zpl', ['texto' => 'x', 'codigo' => 'Y']);

        expect($porCaminho)->toStartWith('^XA')
            ->and(fn() => $renderizador->renderizar('nao_existe', []))->toThrow(ErroTemplate::class, 'não encontrado')
            ->and(fn() => $renderizador->renderizar('../fora', []))->toThrow(ErroTemplate::class, 'inválido');
    });

    it('exige ^XA no início e ^XZ no fim', function (): void {
        file_put_contents($this->dir . '/teste.zpl', '^FO1,1^FDx^FS');
        new RenderizadorZpl($this->dir)->renderizar('teste', []);
    })->throws(ErroTemplate::class, '^XA');
});

describe('EscalaZpl', function (): void {
    it('multiplica coordenadas, fontes, traços e módulos por 1,5', function (): void {
        $original = "^PW800\n^LL1239\n^LH0,0\n^LS0\n^FO17,45^GB765,1155,2^FS\n^FT290,270^BXN,5,200^FDx^FS\n^BY3,3,120^FT120,510^BCN,,Y,Y^FDx^FS\n^BY2,3,110^FO123,303^BCN,110,N,N^FDx^FS\n^FO50,686^A0N,26,25^FB710,2,0,L^FH^FDx^FS\n^FO1,2^GFA,10,10,2,abc^FS";

        expect(EscalaZpl::aplicar($original, 1.5))->toBe("^PW1200\n^LL1859\n^LH0,0\n^LS0\n^FO26,68^GB1148,1733,3^FS\n^FT435,405^BXN,8,200^FDx^FS\n^BY5,3,180^FT180,765^BCN,,Y,Y^FDx^FS\n^BY3,3,165^FO185,455^BCN,165,N,N^FDx^FS\n^FO75,1029^A0N,39,38^FB1065,2,0,L^FH^FDx^FS\n^FO2,3^GFA,10,10,2,abc^FS")
            ->and(EscalaZpl::aplicar('^FO1,1', 1.0))->toBe('^FO1,1');
    });
});

describe('ImagemZpl', function (): void {
    it('calcula o CRC-16/XMODEM', function (): void {
        expect(ImagemZpl::crc16('123456789'))->toBe('31C3');
    });

    it('reconverte o PNG do SEDEX nos mesmos bytes do template antigo', function (): void {
        $antigo = ImagemZpl::bytesDoGfa((string) file_get_contents(dirname(__DIR__, 3) . '/resources/templates/parciais/logo_servico_sedex.zpl'));
        $parcial = ImagemZpl::deArquivo(__DIR__ . '/../../Fixtures/imagens/logo_servico_sedex_224x224.png', 512, 64);

        expect($parcial)->toStartWith('^FO512,64^GFA,6272,6272,28,:Z64:')
            ->and($parcial)->toEndWith('^FS')
            ->and(ImagemZpl::bytesDoGfa($parcial))->toBe($antigo);
    });

    it('lê o hexadecimal compactado do PAC antigo e reproduz os bytes', function (): void {
        $antigo = ImagemZpl::bytesDoGfa((string) file_get_contents(dirname(__DIR__, 3) . '/resources/templates/parciais/logo_servico_pac.zpl'));

        expect(strlen($antigo))->toBe(2940)
            ->and(ImagemZpl::bytesDoGfa(ImagemZpl::deArquivo(__DIR__ . '/../../Fixtures/imagens/logo_servico_pac_160x147.png')))->toBe($antigo);
    });

    it('gera ^GFA a partir de uma imagem GD e redimensiona mantendo a proporção', function (): void {
        $imagem = imagecreatetruecolor(16, 2);
        imagefill($imagem, 0, 0, (int) imagecolorallocate($imagem, 255, 255, 255));
        imagefilledrectangle($imagem, 0, 0, 7, 1, (int) imagecolorallocate($imagem, 0, 0, 0));
        $gfa = ImagemZpl::gfa($imagem);
        $arquivo = sys_get_temp_dir() . '/img-' . uniqid() . '.png';
        imagepng($imagem, $arquivo);
        $reduzida = ImagemZpl::deArquivo($arquivo, 0, 0, 8, 8);
        $ampliada = ImagemZpl::deArquivo($arquivo, 0, 0, 32, null, 128, true);
        $semAmpliar = ImagemZpl::deArquivo($arquivo, 0, 0, 32);
        unlink($arquivo);

        expect(ImagemZpl::bytesDoGfa($gfa))->toBe("\xFF\x00\xFF\x00")
            ->and($reduzida)->toContain('^GFA,1,1,1,')
            ->and($ampliada)->toContain('^GFA,16,16,4,')
            ->and($semAmpliar)->toContain('^GFA,4,4,2,');
    });

    it('lê imagem com paleta e hexadecimal simples', function (): void {
        $imagem = imagecreate(8, 1);
        imagecolorallocate($imagem, 0, 0, 0);

        expect(ImagemZpl::bytesDoGfa(ImagemZpl::gfa($imagem)))->toBe("\xFF")
            ->and(ImagemZpl::bytesDoGfa('^GFA,2,2,1,F00F^FS'))->toBe("\xF0\x0F");
    });

    it('valida entradas', function (): void {
        $imagem = imagecreatetruecolor(1, 1);

        expect(fn() => ImagemZpl::gfa($imagem, 300))->toThrow(ErroValidacao::class, 'limiar')
            ->and(fn() => ImagemZpl::deArquivo('/nao/existe.png'))->toThrow(ErroConfiguracao::class)
            ->and(fn() => ImagemZpl::bytesDoGfa('^FO1,1^FS'))->toThrow(ErroValidacao::class)
            ->and(fn() => ImagemZpl::bytesDoGfa('^GFA,1,1,1,:Z64:eJwDAAAAAAE=:0000'))->toThrow(ErroValidacao::class, 'CRC')
            ->and(fn() => ImagemZpl::bytesDoGfa('^GFA,1,1,1,:Z64:!!!!:' . ImagemZpl::crc16('!!!!')))->toThrow(ErroValidacao::class, 'inválidos');
    });
});
