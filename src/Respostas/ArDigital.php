<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use DateTimeImmutable;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Utilitarios\Arquivo;

/**
 * Imagem do AR Digital de um objeto (`GET /srorastro/v1/ar-digital`, item `ArDigitalTO`).
 *
 * Só existe para objetos postados com o adicional AR Digital (`ARDIG`) e já entregues. Sem imagem, `disponivel()`
 * é false e `codigoErro`/`mensagem` explicam o motivo (ex.: `SRO-009` objeto de outro contrato, `SRO-020` objeto não
 * localizado, observados em homologação em 02/11/2026).
 *
 * O tipo da imagem vem de `contentType`; sem ele, é identificado pelos primeiros bytes (JPEG, PNG, GIF, TIFF ou
 * PDF). `respostaBruta()` não guarda o Base64, só o tamanho.
 */
final readonly class ArDigital extends Resposta
{
    private const array EXTENSOES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/tiff' => 'tif',
        'application/pdf' => 'pdf',
    ];

    /** @param array<mixed> $bruto */
    public function __construct(
        public string $codigoObjeto,
        public bool $sucesso,
        public ?string $codigoErro = null,
        public ?string $mensagem = null,
        public ?DateTimeImmutable $dataBaixa = null,
        public ?string $contentType = null,
        public ?string $formato = null,
        private ?string $base64 = null,
        array $bruto = [],
    ) {
        if (is_string($bruto['imagemBase64'] ?? null)) {
            $bruto['imagemBase64'] = sprintf('[%d caracteres em Base64]', strlen($bruto['imagemBase64']));
        }
        parent::__construct($bruto);
    }

    /** A API devolveu a imagem do AR. */
    public function disponivel(): bool
    {
        return $this->sucesso && $this->base64 !== null && $this->base64 !== '';
    }

    public function conteudoBase64(): string
    {
        return $this->disponivel() ? (string) $this->base64 : throw $this->semImagem();
    }

    /** Imagem decodificada. Lança `ErroApi` se não houver imagem ou se o Base64 for inválido. */
    public function conteudoBinario(): string
    {
        $binario = base64_decode($this->conteudoBase64(), true);
        if ($binario === false || $binario === '') {
            throw new ErroApi(sprintf('A imagem do AR Digital do objeto %s não é um Base64 válido.', $this->codigoObjeto), 200, [], null, null, $this->bruto);
        }

        return $binario;
    }

    public function tamanhoBytes(): int
    {
        return strlen($this->conteudoBinario());
    }

    /** Tipo MIME: `contentType` da API ou, na falta dele, o identificado pelos primeiros bytes. */
    public function tipoMime(): string
    {
        $informado = strtolower(trim(explode(';', (string) $this->contentType)[0]));
        if (preg_match('#^[a-z]+/[a-z0-9.+-]+$#', $informado) === 1) {
            return $informado === 'image/jpg' ? 'image/jpeg' : $informado;
        }

        $inicio = substr($this->conteudoBinario(), 0, 8);

        return match (true) {
            str_starts_with($inicio, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($inicio, "\x89PNG") => 'image/png',
            str_starts_with($inicio, 'GIF8') => 'image/gif',
            str_starts_with($inicio, "II*\x00"), str_starts_with($inicio, "MM\x00*") => 'image/tiff',
            str_starts_with($inicio, '%PDF') => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    /** Extensão de arquivo sugerida pelo tipo (`jpg`, `png`, `gif`, `tif`, `pdf` ou `bin`). */
    public function extensao(): string
    {
        return self::EXTENSOES[$this->tipoMime()] ?? 'bin';
    }

    /** `data:` URI para exibir a imagem direto em HTML (`<img src="...">`). */
    public function dataUri(): string
    {
        return sprintf('data:%s;base64,%s', $this->tipoMime(), $this->conteudoBase64());
    }

    /** Grava a imagem. Lança `ErroApi` sem imagem e `ErroConfiguracao` se não conseguir gravar. */
    public function salvar(string $caminho): void
    {
        Arquivo::gravar($caminho, $this->conteudoBinario(), 'a imagem do AR Digital');
    }

    private function semImagem(): ErroApi
    {
        return new ErroApi(
            sprintf('O objeto %s não tem imagem do AR Digital: %s', $this->codigoObjeto, $this->mensagem ?? 'a API não devolveu a imagem.'),
            200,
            $this->mensagem !== null ? [$this->mensagem] : [],
            $this->codigoErro,
            null,
            $this->bruto,
        );
    }
}
