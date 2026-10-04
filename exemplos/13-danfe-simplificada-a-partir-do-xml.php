<?php

declare(strict_types=1);

/*
 * DANFE Simplificado - Etiqueta a partir do XML autorizado da NF-e (nfeProc). Não lista itens nem valores.
 * Uso: php exemplos/13-danfe-simplificada-a-partir-do-xml.php caminho/da/nfe.xml [codigoDeRastreamento]
 */

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Etiquetas\DadosDanfe;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;

require __DIR__ . '/../vendor/autoload.php';

$xml = (string) @file_get_contents($argv[1] ?? __DIR__ . '/../tests/Fixtures/xml/nfe-venda-pf.xml');

$correios = new Correios(Configuracao::deArray([
    'ambiente' => 'homologacao',
    'usuario' => '11222333000181',
    'codigo_acesso' => 'nao-usado-nas-etiquetas',
    'cnpj' => '11222333000181',
    'cartao_postagem' => '0012345678',
]));

try {
    $dados = DadosDanfe::deXml($xml);
} catch (ErroValidacao $e) {
    exit('XML recusado: ' . $e->getMessage() . PHP_EOL);
}

echo 'Chave para a pré-postagem (chaveNFe): ', $dados->chaveAcesso, PHP_EOL;
echo 'Número da nota: ', $dados->numero, PHP_EOL;

$danfe = $correios->etiquetas()->danfeSimplificada($dados, codigoEnvio: $argv[2] ?? 'AB043180865BR');
$danfe->salvar(sys_get_temp_dir() . '/danfe-simplificada.zpl');
echo 'ZPL gravado em ', sys_get_temp_dir() . '/danfe-simplificada.zpl', PHP_EOL;
