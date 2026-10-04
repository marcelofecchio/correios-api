<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Http;

use JsonException;

/**
 * Resposta HTTP já lida (status e corpo).
 *
 * @internal
 */
final readonly class RespostaHttp
{
    public function __construct(
        public int $status,
        public string $corpo,
        public string $tipoConteudo = '',
    ) {}

    /** JSON decodificado, ou null se o corpo não for JSON válido. */
    public function json(): mixed
    {
        if (trim($this->corpo) === '') {
            return null;
        }

        try {
            return json_decode($this->corpo, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * JSON como array, ou array vazio se não for objeto/lista.
     *
     * @return array<mixed>
     */
    public function jsonArray(): array
    {
        $json = $this->json();

        return is_array($json) ? $json : [];
    }

    public function ehJson(): bool
    {
        return $this->json() !== null;
    }
}
