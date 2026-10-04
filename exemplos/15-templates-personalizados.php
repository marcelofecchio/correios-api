<?php

declare(strict_types=1);

/*
 * Templates próprios: informe um diretório com arquivos .zpl que usem os mesmos marcadores do pacote.
 * Arquivo ausente no diretório da aplicação cai no template do pacote (ex.: os parciais de logo).
 *
 *   {{nome}}    valor escapado para campos com ^FH
 *   {{{nome}}}  valor cru (códigos de barras e DataMatrix)
 *   {{>nome}}   parcial (some se não estiver configurado)
 */

use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Etiquetas\DadosEtiquetaPostagem;

require __DIR__ . '/../vendor/autoload.php';

$diretorio = sys_get_temp_dir() . '/meus-templates-zpl';
@mkdir($diretorio);
file_put_contents($diretorio . '/postagem.zpl', <<<'ZPL'
    ^XA
    ^CI28
    ^PW800
    ^LL600
    ^FX Etiqueta resumida da loja: logo dos Correios, código do objeto e destinatário
    {{>logo_correios}}
    ^BY3,3,120^FT120,400^BCN,,Y,Y^FD{{{codigo_objeto}}}^FS
    ^FO50,450^A0N,30,30^FH^FD{{destinatario_nome}}^FS
    ^FO50,490^A0N,26,25^FH^FD{{destinatario_cep_hifen}} {{destinatario_cidade_uf}}^FS
    ^XZ
    ZPL);

$correios = new Correios(Configuracao::deArray([
    'ambiente' => 'producao',
    'usuario' => 'login',
    'codigo_acesso' => 'nao-usado-nas-etiquetas',
    'cnpj' => '11222333000181',
    'cartao_postagem' => '0012345678',
    'etiquetas' => ['diretorio_templates' => $diretorio],
]));

echo $correios->etiquetas()->postagem(new DadosEtiquetaPostagem(
    codigoObjeto: 'AB043180865BR',
    codigoServico: '03220',
    remetente: new Pessoa('Loja Exemplo Ltda', new Endereco('01310100', 'Avenida Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP')),
    destinatario: new Pessoa('Maria Aparecida Souza', new Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP')),
    pesoGramas: 800,
));
