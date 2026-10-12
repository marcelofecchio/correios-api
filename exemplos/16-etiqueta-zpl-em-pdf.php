<?php

declare(strict_types=1);

/*
 * Etiqueta de postagem gerada em ZPL pelo componente e convertida em PDF pelo Labelary, como alternativa ao rótulo
 * oficial dos Correios. Funciona sem credenciais dos Correios, mas chama a API pública do Labelary.
 *
 * ATENÇÃO (LGPD): o ZPL leva nome, endereço e documento das pessoas. O plano gratuito do Labelary não tem retenção
 * zero de dados: use dados fictícios em testes e avalie um plano pago (chaveApi + url) em produção.
 */

use MarceloFecchio\Correios\ArquivoPdf;
use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Enums\TamanhoPagina;
use MarceloFecchio\Correios\Etiquetas\ConversorLabelary;
use MarceloFecchio\Correios\Etiquetas\DadosEtiquetaPostagem;

require __DIR__ . '/../vendor/autoload.php';

$correios = new Correios(Configuracao::deArray([
    'ambiente' => 'homologacao',
    'usuario' => '11222333000181',
    'codigo_acesso' => 'nao-usado-nas-etiquetas',
    'cnpj' => '11222333000181',
    'cartao_postagem' => '0012345678',
    'contrato' => '9912345678',
    'servicos' => ['sedex' => '03220'],
    'etiquetas' => [
        'logos_por_servico' => ['sedex' => 'logo_servico_sedex'],
        'nomes_servicos' => ['sedex' => 'SEDEX'],
    ],
]));

$etiqueta = $correios->etiquetas()->postagem(new DadosEtiquetaPostagem(
    codigoObjeto: 'AB043180865BR',
    codigoServico: 'sedex',
    remetente: new Pessoa('Loja Exemplo Ltda', new Endereco('01310100', 'Avenida Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP', 'Sala 101')),
    destinatario: new Pessoa('Maria Aparecida Souza', new Endereco('01001000', 'Praça da Sé', '100', 'Sé', 'São Paulo', 'SP', 'Apto 12')),
    pesoGramas: 800,
    observacoes: 'Pedido 12345',
));

// 1. Uma etiqueta por página, no tamanho dela (10 x 15,5 cm)
$pdf = $etiqueta->paraPdf(new ConversorLabelary());
$arquivo = sys_get_temp_dir() . '/etiqueta-postagem.pdf';
$pdf->salvar($arquivo);
echo "PDF gravado em {$arquivo} ({$pdf->quantidade} etiqueta, {$pdf->tamanhoBytes()} bytes)", PHP_EOL;

// 2. Duas etiquetas por folha A4, com borda para recortar. Plano pago: new ConversorLabelary(chaveApi: '...', url: '...')
usleep(400_000);   // plano gratuito: até 3 requisições por segundo
$folha = (new ConversorLabelary(pagina: TamanhoPagina::A4, layout: '2x1'))
    ->converter($correios->etiquetas()->juntar($etiqueta, $etiqueta));
$folha->salvar(sys_get_temp_dir() . '/etiquetas-a4.pdf');

// A aplicação pode tratar este PDF e o do rótulo oficial (ArquivoRotulo) da mesma forma:
function guardarPdf(ArquivoPdf $pdf, string $caminho): void
{
    $pdf->salvar($caminho);
}
