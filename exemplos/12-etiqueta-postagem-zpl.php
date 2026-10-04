<?php

declare(strict_types=1);

/*
 * Etiqueta de postagem em ZPL gerada pelo componente (sem chamada à API). Funciona sem credenciais.
 * Confira o resultado em https://labelary.com/viewer.html (8 dpmm, 4 x 6,2 polegadas).
 */

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Enums\Resolucao;
use MarceloFecchio\Correios\Etiquetas\DadosEtiquetaPostagem;

require __DIR__ . '/../vendor/autoload.php';

$correios = new Correios(Configuracao::deArray([
    'ambiente' => 'homologacao',
    'usuario' => '11222333000181',
    'codigo_acesso' => 'nao-usado-nas-etiquetas',
    'cnpj' => '11222333000181',
    'cartao_postagem' => '0012345678',
    'contrato' => '9912345678',
    'servicos' => ['sedex' => '03220', 'pac' => '03298'],
    'etiquetas' => [
        'logos_por_servico' => ['sedex' => 'logo_servico_sedex', 'pac' => 'logo_servico_pac'],
        'nomes_servicos' => ['sedex' => 'SEDEX', 'pac' => 'PAC'],
    ],
]));

$etiqueta = $correios->etiquetas()->postagem(new DadosEtiquetaPostagem(
    codigoObjeto: 'AB043180865BR',            // vem de PrePostagemCriada::$codigoObjeto
    codigoServico: 'sedex',
    remetente: new Pessoa('Loja Exemplo Ltda', new Endereco('01310100', 'Avenida Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP', 'Sala 101')),
    destinatario: new Pessoa('Maria Aparecida Souza', new Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP', 'Apto 12')),
    pesoGramas: 800,
    numeroNotaFiscal: '21486',
    siglasAdicionais: ['VD'],
    observacoes: 'Pedido 12345',
));

$arquivo = sys_get_temp_dir() . '/etiqueta-postagem.zpl';
$etiqueta->salvar($arquivo);
echo "ZPL gravado em {$arquivo} ({$etiqueta->quantidade} etiqueta, {$etiqueta->resolucao->value} dpi)", PHP_EOL;

// 300 dpi
$correios->etiquetas()->postagem(new DadosEtiquetaPostagem(
    codigoObjeto: 'AB043180865BR',
    codigoServico: 'pac',
    remetente: new Pessoa('Loja Exemplo Ltda', new Endereco('01310100', 'Avenida Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP')),
    destinatario: new Pessoa('Maria Aparecida Souza', new Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP')),
    pesoGramas: 800,
), Resolucao::Dpi300)->salvar(sys_get_temp_dir() . '/etiqueta-postagem-300dpi.zpl');

// Envio direto à impressora pela rede (porta 9100), se houver uma:
// $socket = fsockopen('192.168.0.50', 9100, $erro, $mensagem, 5);
// fwrite($socket, (string) $etiqueta);
// fclose($socket);
