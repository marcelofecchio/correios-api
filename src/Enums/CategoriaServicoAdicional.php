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
 * Siglas da documentação: `VD` (resposta da API Preço) e `VDS` (rótulo do PAC) para valor declarado; `AR` e `MP` nos
 * campos `servico.ar` e `servico.mp` da API Rastro. A categoria de artigos perigosos é reconhecida só pelo nome
 * ("perigoso"), pois a documentação não traz a sigla.
 */
enum CategoriaServicoAdicional: string
{
    case AvisoRecebimento = 'AR';
    case MaoPropria = 'MP';
    case ValorDeclarado = 'VD';
    case ArtigosPerigosos = 'DGR';

    /** Categoria de um adicional devolvido pela API, ou null se não for nenhuma destas. */
    public static function de(ServicoAdicionalDisponivel $adicional): ?self
    {
        $sigla = strtoupper(trim((string) $adicional->sigla));
        $nome = strtoupper(Texto::semAcentos((string) $adicional->nome));

        return match (true) {
            str_starts_with($sigla, 'VD'), str_contains($nome, 'VALOR DECLARADO') => self::ValorDeclarado,
            $sigla === 'MP', str_contains($nome, 'MAO PROPRIA') => self::MaoPropria,
            $sigla === 'AR', str_contains($nome, 'AVISO DE RECEBIMENTO') => self::AvisoRecebimento,
            $sigla === 'DGR', str_contains($nome, 'PERIGOS') => self::ArtigosPerigosos,
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
