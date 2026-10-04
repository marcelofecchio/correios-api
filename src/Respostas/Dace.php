<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Respostas;

use MarceloFecchio\Correios\Enums\TipoDace;
use MarceloFecchio\Correios\Excecoes\ErroApi;
use MarceloFecchio\Correios\Excecoes\ErroConfiguracao;
use MarceloFecchio\Correios\Utilitarios\Arquivo;

/**
 * DACE (documento auxiliar da DCe).
 *
 * - Tipos `R` e `C`: PDF em Base64 → use `conteudoBinario()` (valida `%PDF`).
 * - Tipo `T`: texto para impressora térmica → use `texto()`. Não é PDF. Ainda não foi testado se vem puro
 *   ou em Base64: `texto()` devolve o texto decodificado quando o conteúdo é Base64 válido de um texto.
 *
 * `respostaBruta()` não guarda o conteúdo (`dados`), só o tamanho.
 */
final readonly class Dace extends Resposta
{
    /**
     * @param list<string> $objetos códigos dos objetos incluídos na impressão
     * @param array<mixed> $bruto
     */
    public function __construct(
        public TipoDace $tipo,
        public array $objetos,
        private string $dados,
        array $bruto = [],
    ) {
        if (isset($bruto['dados'])) {
            $bruto['dados'] = sprintf('[%d caracteres]', strlen($dados));
        }
        parent::__construct($bruto);
    }

    /** Conteúdo exatamente como veio no campo `dados`. */
    public function conteudoOriginal(): string
    {
        return $this->dados;
    }

    /** PDF decodificado (tipos `R` e `C`). */
    public function conteudoBinario(): string
    {
        if (!$this->tipo->ehPdf()) {
            throw new ErroConfiguracao('A DACE térmica (tipo T) é texto para impressora, não PDF: use texto().');
        }
        $binario = base64_decode($this->dados, true);
        if ($binario === false || !str_starts_with($binario, '%PDF')) {
            throw new ErroApi('O conteúdo da DACE não é um PDF válido (o Base64 decodificado não começa com %PDF).', 200, [], null, null, $this->bruto);
        }

        return $binario;
    }

    /** Texto para a impressora térmica (tipo `T`). */
    public function texto(): string
    {
        if ($this->tipo->ehPdf()) {
            throw new ErroConfiguracao('A DACE dos tipos R e C é PDF: use conteudoBinario().');
        }
        $decodificado = base64_decode($this->dados, true);
        if ($decodificado !== false && $decodificado !== '' && mb_check_encoding($decodificado, 'UTF-8') && preg_match('/^[\P{C}\r\n\t]+$/u', $decodificado) === 1 && preg_match('/[^A-Za-z0-9+\/=\r\n]/', $this->dados) !== 1) {
            return $decodificado;
        }

        return $this->dados;
    }

    /** Grava o conteúdo (PDF decodificado ou texto térmico). */
    public function salvar(string $caminho): void
    {
        $conteudo = $this->tipo->ehPdf() ? $this->conteudoBinario() : $this->texto();
        Arquivo::gravar($caminho, $conteudo, 'a DACE');
    }
}
