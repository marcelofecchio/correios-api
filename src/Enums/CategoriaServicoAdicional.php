<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Enums;

use MarceloFecchio\Correios\Respostas\ServicoAdicionalDisponivel;
use MarceloFecchio\Correios\Utilitarios\Texto;

/**
 * Categoria de um serviço adicional, para achar o código certo de cada serviço sem fixá-lo na aplicação.
 *
 * Os códigos mudam por serviço (o valor declarado é `019` no SEDEX e `064` no PAC, nos testes); a categoria vem da
 * `sigla` e do `nome` devolvidos por `preco()->servicosAdicionais()`:
 *
 * ```php
 * $adicionais = $correios->preco()->servicosAdicionais($token, 'sedex');
 * $maoPropria = CategoriaServicoAdicional::MaoPropria->localizar($adicionais);   // null se o serviço não aceitar
 * ```
 *
 * Confirmado em homologação (11/10/2026, SEDEX 03220, PAC 03298, SEDEX REVERSO 03247 e PAC REVERSO 03301):
 * `001` AR, `021` EAR, `037` ARDIG, `002` MP, `019` VD (SEDEX), `064` VDS (PAC) e `095` AP ("ARTIGOS PERIGOSOS
 * ANAC"). A sigla exata tem prioridade; sem ela, a categoria vem do nome.
 */
enum CategoriaServicoAdicional: string
{
    /** AR convencional (`001`). */
    case AvisoRecebimento = 'AR';
    case AvisoRecebimentoEletronico = 'EAR';
    case AvisoRecebimentoDigital = 'ARDIG';
    case MaoPropria = 'MP';
    case ValorDeclarado = 'VD';
    case ArtigosPerigosos = 'AP';

    /** Categoria de um adicional devolvido pela API, ou null se não for nenhuma destas. */
    public static function de(ServicoAdicionalDisponivel $adicional): ?self
    {
        $sigla = strtoupper(trim((string) $adicional->sigla));
        $porSigla = match ($sigla) {
            'AR' => self::AvisoRecebimento,
            'EAR' => self::AvisoRecebimentoEletronico,
            'ARDIG' => self::AvisoRecebimentoDigital,
            'MP' => self::MaoPropria,
            'VD', 'VDS' => self::ValorDeclarado,
            'AP', 'DGR' => self::ArtigosPerigosos,
            default => null,
        };
        if ($porSigla !== null) {
            return $porSigla;
        }

        $nome = strtoupper(Texto::semAcentos((string) $adicional->nome));

        return match (true) {
            str_contains($nome, 'VALOR DECLARADO') => self::ValorDeclarado,
            str_contains($nome, 'MAO PROPRIA') => self::MaoPropria,
            str_contains($nome, 'AVISO DE RECEBIMENTO') && str_contains($nome, 'ELETRONICO') => self::AvisoRecebimentoEletronico,
            str_contains($nome, 'AVISO DE RECEBIMENTO') && str_contains($nome, 'DIGITAL') => self::AvisoRecebimentoDigital,
            str_contains($nome, 'AVISO DE RECEBIMENTO') => self::AvisoRecebimento,
            str_contains($nome, 'PERIGOS') => self::ArtigosPerigosos,
            default => null,
        };
    }

    /**
     * Primeiro adicional desta categoria na lista, ou null se o serviço não o aceitar.
     *
     * @param iterable<ServicoAdicionalDisponivel> $adicionais resultado de `preco()->servicosAdicionais()`
     */
    public function localizar(iterable $adicionais): ?ServicoAdicionalDisponivel
    {
        foreach ($adicionais as $adicional) {
            if (self::de($adicional) === $this) {
                return $adicional;
            }
        }

        return null;
    }
}
