<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Excecoes;

/**
 * Rejeição 812: "Usuário emitente bloqueado para emissão de DC-e [Contribuinte de ICMS]".
 *
 * A partir de 02/11/2026 a SEFAZ verifica se o CNPJ do remetente é contribuinte do ICMS ao emitir a DC-e
 * (`emiteDCe`). Sendo contribuinte, a DC-e não é emitida: envie a chave da NF-e (`chaveNFe`). Documentos em papel
 * não exigem NF-e. Ver `doc/comunicados/2026-10-dce-contribuinte-icms.md`.
 */
class ErroContribuinteIcms extends ErroApi
{
    /** Código da rejeição da SEFAZ. */
    public const string CODIGO_REJEICAO = '812';

    /** A mensagem é a Rejeição 812 (pelo código ou pelo texto, com ou sem acentos e prefixo `PPN-xxx:`). */
    public static function reconhece(string $mensagem): bool
    {
        return preg_match('/rejei\S*\s*(?:n[º°o.]*\s*)?[:\-–—]?\s*812\b/iu', $mensagem) === 1
            || preg_match('/bloquead[oa]\s+para\s+(?:a\s+)?emiss\S+\s+de\s+(?:a\s+)?DC-?e\b/iu', $mensagem) === 1
            || preg_match('/\[\s*contribuinte\s+(?:de|do)\s+ICMS\s*\]/iu', $mensagem) === 1;
    }

    /** @param iterable<?string> $mensagens */
    public static function reconheceAlguma(iterable $mensagens): bool
    {
        foreach ($mensagens as $mensagem) {
            if ($mensagem !== null && self::reconhece($mensagem)) {
                return true;
            }
        }

        return false;
    }
}
