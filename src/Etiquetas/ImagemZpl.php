<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

use GdImage;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;

/**
 * Converte imagens (PNG, JPG, GIF) em `^GFA` no formato `:Z64:` (zlib + Base64 + CRC-16/XMODEM), para gerar
 * parciais de logotipo. É a única classe que usa GD; faça a conversão uma vez e guarde o resultado como parcial.
 *
 * Não desenhe nem recrie logotipos: parta sempre do arquivo oficial.
 */
final class ImagemZpl
{
    private function __construct() {}

    /**
     * Parcial pronto: `^FOx,y^GFA,...^FS`.
     *
     * A imagem é redimensionada para caber em `larguraMaxima` × `alturaMaxima` pontos, mantendo a proporção.
     * Sem `permitirAmpliar`, imagens menores não são ampliadas. Transparência vira branco.
     *
     * @param int $limiar 0 a 255: pontos mais escuros que o limiar saem pretos
     */
    public static function deArquivo(
        string $caminho,
        int $x = 0,
        int $y = 0,
        ?int $larguraMaxima = null,
        ?int $alturaMaxima = null,
        int $limiar = 128,
        bool $permitirAmpliar = false,
    ): string {
        self::exigirGd();
        $dados = is_file($caminho) ? @file_get_contents($caminho) : false;
        $imagem = $dados === false || $dados === '' ? false : @imagecreatefromstring($dados);
        if (!$imagem instanceof GdImage) {
            throw new ErroConfiguracao(sprintf('Não foi possível abrir a imagem "%s".', $caminho));
        }

        $redimensionada = self::redimensionar($imagem, $larguraMaxima, $alturaMaxima, $permitirAmpliar);

        return sprintf('^FO%d,%d%s^FS', $x, $y, self::gfa($redimensionada, $limiar));
    }

    /** Comando `^GFA,total,total,bytesPorLinha,:Z64:dados:CRC` a partir de uma imagem GD. */
    public static function gfa(GdImage $imagem, int $limiar = 128): string
    {
        self::exigirGd();
        if ($limiar < 0 || $limiar > 255) {
            throw ErroValidacao::campo('limiar', 'deve estar entre 0 e 255.');
        }

        $largura = imagesx($imagem);
        $altura = imagesy($imagem);
        $bytesPorLinha = intdiv($largura + 7, 8);
        $binario = '';
        for ($linha = 0; $linha < $altura; $linha++) {
            for ($b = 0; $b < $bytesPorLinha; $b++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $coluna = $b * 8 + $bit;
                    if ($coluna >= $largura) {
                        continue;
                    }
                    $rgb = (int) imagecolorat($imagem, $coluna, $linha);
                    if (!imageistruecolor($imagem)) {
                        $cor = imagecolorsforindex($imagem, $rgb);
                        $rgb = ($cor['red'] << 16) | ($cor['green'] << 8) | $cor['blue'];
                    }
                    $cinza = ((($rgb >> 16) & 0xFF) * 299 + (($rgb >> 8) & 0xFF) * 587 + ($rgb & 0xFF) * 114) / 1000;
                    if ($cinza < $limiar) {
                        $byte |= (0x80 >> $bit);
                    }
                }
                $binario .= chr($byte);
            }
        }

        return self::gfaDeBytes($binario, $bytesPorLinha);
    }

    /** Monta o `^GFA` `:Z64:` a partir dos bytes da imagem (1 bit por ponto, 1 = preto). */
    public static function gfaDeBytes(string $binario, int $bytesPorLinha): string
    {
        if (!function_exists('gzcompress')) {
            throw new ErroConfiguracao('A extensão zlib é necessária para gerar ^GFA no formato :Z64:.');
        }
        $total = strlen($binario);
        $base64 = base64_encode((string) gzcompress($binario, 9));

        return sprintf('^GFA,%d,%d,%d,:Z64:%s:%s', $total, $total, $bytesPorLinha, $base64, self::crc16($base64));
    }

    /** CRC-16/XMODEM (polinômio 0x1021, valor inicial 0) do texto, em 4 dígitos hexadecimais maiúsculos. */
    public static function crc16(string $texto): string
    {
        $crc = 0;
        $tamanho = strlen($texto);
        for ($i = 0; $i < $tamanho; $i++) {
            $crc ^= ord($texto[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) !== 0 ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }

        return sprintf('%04X', $crc);
    }

    /**
     * Bytes da imagem de um comando `^GFA` (`:Z64:`, hexadecimal simples ou hexadecimal compactado).
     * Útil para comparar logotipos. Confere o CRC do formato `:Z64:`.
     */
    public static function bytesDoGfa(string $comando): string
    {
        if (preg_match('/\^GFA,(\d+),(\d+),(\d+),(.*?)(?:\^FS|$)/s', $comando, $m) !== 1) {
            throw ErroValidacao::campo('gfa', 'comando ^GFA não encontrado.');
        }
        $total = (int) $m[1];
        $bytesPorLinha = (int) $m[3];
        $dados = trim($m[4]);

        if (str_starts_with($dados, ':Z64:')) {
            [$base64, $crc] = array_pad(explode(':', substr($dados, 5), 2), 2, '');
            if (strtoupper($crc) !== self::crc16($base64)) {
                throw ErroValidacao::campo('gfa', 'CRC do bloco :Z64: não confere.');
            }
            $comprimido = base64_decode($base64, true);
            $binario = $comprimido === false ? false : @gzuncompress($comprimido);
            if ($binario === false) {
                throw ErroValidacao::campo('gfa', 'dados :Z64: inválidos.');
            }

            return $binario;
        }

        return self::descompactarHex($dados, $bytesPorLinha, $total);
    }

    /** Descompacta o hexadecimal ASCII do ZPL (`G`–`Y`, `g`–`z`, `,`, `!`, `:`). */
    private static function descompactarHex(string $dados, int $bytesPorLinha, int $total): string
    {
        $caracteresPorLinha = $bytesPorLinha * 2;
        $hex = '';
        $linha = '';
        $anterior = str_repeat('0', $caracteresPorLinha);
        $repeticao = 0;
        $tamanho = strlen($dados);

        $fecharLinha = static function (string $preenchimento) use (&$linha, &$hex, &$anterior, $caracteresPorLinha): void {
            $linha = str_pad($linha, $caracteresPorLinha, $preenchimento);
            $hex .= $linha;
            $anterior = $linha;
            $linha = '';
        };

        for ($i = 0; $i < $tamanho; $i++) {
            $c = $dados[$i];
            if ($c >= 'G' && $c <= 'Y') {
                $repeticao += ord($c) - ord('G') + 1;
            } elseif ($c >= 'g' && $c <= 'z') {
                $repeticao += (ord($c) - ord('g') + 1) * 20;
            } elseif ($c === ',') {
                $fecharLinha('0');
            } elseif ($c === '!') {
                $fecharLinha('F');
            } elseif ($c === ':') {
                $hex .= $anterior;
            } elseif (ctype_xdigit($c)) {
                $linha .= str_repeat(strtoupper($c), max(1, $repeticao));
                $repeticao = 0;
                if (strlen($linha) >= $caracteresPorLinha) {
                    $fecharLinha('0');
                }
            }
        }
        if ($linha !== '') {
            $fecharLinha('0');
        }

        $binario = (string) hex2bin(substr($hex, 0, $total * 2));

        return str_pad($binario, $total, "\0");
    }

    private static function redimensionar(GdImage $origem, ?int $larguraMaxima, ?int $alturaMaxima, bool $permitirAmpliar): GdImage
    {
        $larguraOrigem = imagesx($origem);
        $alturaOrigem = imagesy($origem);
        $escala = min(
            $larguraMaxima !== null ? $larguraMaxima / $larguraOrigem : PHP_FLOAT_MAX,
            $alturaMaxima !== null ? $alturaMaxima / $alturaOrigem : PHP_FLOAT_MAX,
        );
        if ($escala === PHP_FLOAT_MAX || (!$permitirAmpliar && $escala > 1.0)) {
            $escala = 1.0;
        }
        $largura = max(1, (int) round($larguraOrigem * $escala));
        $altura = max(1, (int) round($alturaOrigem * $escala));

        $destino = imagecreatetruecolor($largura, $altura);
        if ($destino === false) {
            throw new ErroConfiguracao('Não foi possível criar a imagem redimensionada.');
        }
        imagefill($destino, 0, 0, (int) imagecolorallocate($destino, 255, 255, 255));
        imagealphablending($destino, true);
        imagecopyresampled($destino, $origem, 0, 0, 0, 0, $largura, $altura, $larguraOrigem, $alturaOrigem);

        return $destino;
    }

    private static function exigirGd(): void
    {
        if (!extension_loaded('gd')) {
            throw new ErroConfiguracao('A extensão GD é necessária para converter imagens em ^GFA (composer suggest: ext-gd).');
        }
    }
}
