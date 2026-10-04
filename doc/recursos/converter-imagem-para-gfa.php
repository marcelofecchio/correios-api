<?php

declare(strict_types=1);

/**
 * Converte uma imagem (PNG, JPG ou GIF) em um parcial ZPL com o comando ^GFA (formato :Z64:).
 *
 * Uso:
 *   php converter-imagem-para-gfa.php <imagem> [x] [y] [larguraMax] [alturaMax] [limiar] > parcial.zpl
 *
 * Exemplo (logo no canto superior esquerdo da etiqueta de 203 dpi, cabendo em 256 x 250 pontos):
 *   php converter-imagem-para-gfa.php logo.png 25 50 256 250 > logo_correios.zpl
 *
 * Parâmetros:
 *   x, y         posição do canto superior esquerdo na etiqueta, em pontos (padrão 25 e 50)
 *   larguraMax   largura máxima em pontos (padrão 256); a imagem é reduzida mantendo a proporção
 *   alturaMax    altura máxima em pontos (padrão 250)
 *   limiar       0 a 255; pontos mais escuros que o limiar saem pretos (padrão 128)
 *
 * Requer a extensão GD. Fundo transparente é tratado como branco.
 */

if ($argc < 2) {
    fwrite(STDERR, "Uso: php converter-imagem-para-gfa.php <imagem> [x] [y] [larguraMax] [alturaMax] [limiar]\n");
    exit(1);
}

$caminho = $argv[1];
$x = (int) ($argv[2] ?? 25);
$y = (int) ($argv[3] ?? 50);
$larguraMax = (int) ($argv[4] ?? 256);
$alturaMax = (int) ($argv[5] ?? 250);
$limiar = (int) ($argv[6] ?? 128);

$dados = @file_get_contents($caminho);
$origem = $dados === false ? false : @imagecreatefromstring($dados);
if ($origem === false) {
    fwrite(STDERR, "Não foi possível abrir a imagem: {$caminho}\n");
    exit(1);
}

$larguraOrigem = imagesx($origem);
$alturaOrigem = imagesy($origem);
$escala = min(1.0, $larguraMax / $larguraOrigem, $alturaMax / $alturaOrigem);
$largura = max(1, (int) round($larguraOrigem * $escala));
$altura = max(1, (int) round($alturaOrigem * $escala));

// Fundo branco + redimensionamento (transparência vira branco)
$img = imagecreatetruecolor($largura, $altura);
imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
imagealphablending($img, true);
imagecopyresampled($img, $origem, 0, 0, 0, 0, $largura, $altura, $larguraOrigem, $alturaOrigem);

$bytesPorLinha = intdiv($largura + 7, 8);
$bin = '';
for ($linha = 0; $linha < $altura; $linha++) {
    for ($b = 0; $b < $bytesPorLinha; $b++) {
        $byte = 0;
        for ($bit = 0; $bit < 8; $bit++) {
            $coluna = $b * 8 + $bit;
            if ($coluna >= $largura) {
                continue;
            }
            $rgb = imagecolorat($img, $coluna, $linha);
            $cinza = ((($rgb >> 16) & 0xFF) * 299 + (($rgb >> 8) & 0xFF) * 587 + ($rgb & 0xFF) * 114) / 1000;
            if ($cinza < $limiar) {
                $byte |= (0x80 >> $bit);
            }
        }
        $bin .= chr($byte);
    }
}

$total = strlen($bin);
$base64 = base64_encode(gzcompress($bin, 9));

// CRC-16/XMODEM sobre o texto Base64
$crc = 0;
foreach (str_split($base64) as $caractere) {
    $crc ^= ord($caractere) << 8;
    for ($i = 0; $i < 8; $i++) {
        $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
    }
}

fwrite(STDERR, "Imagem: {$largura} x {$altura} pontos, {$bytesPorLinha} bytes por linha, {$total} bytes\n");
printf("^FO%d,%d^GFA,%d,%d,%d,:Z64:%s:%04X^FS\n", $x, $y, $total, $total, $bytesPorLinha, $base64, $crc);
