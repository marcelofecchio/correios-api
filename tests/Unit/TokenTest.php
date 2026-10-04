<?php

declare(strict_types=1);

use MarceloFecchio\Correios\Enums\Ambiente;
use MarceloFecchio\Correios\Excecoes\ErroValidacao;
use MarceloFecchio\Correios\Token;

describe('Token', function (): void {
    $expira = new DateTimeImmutable('2026-10-05T10:00:00-03:00');

    it('considera a margem de segurança antes da expiração', function () use ($expira): void {
        $token = tokenTeste($expira);

        expect($token->estaValido(300, new DateTimeImmutable('2026-10-05T09:54:00-03:00')))->toBeTrue()
            ->and($token->estaValido(300, new DateTimeImmutable('2026-10-05T09:56:00-03:00')))->toBeFalse()
            ->and($token->estaValido(0, new DateTimeImmutable('2026-10-05T10:00:01-03:00')))->toBeFalse()
            ->and($token->segundosRestantes(new DateTimeImmutable('2026-10-05T09:59:00-03:00')))->toBe(60)
            ->and(tokenTeste()->estaValido())->toBeTrue()
            ->and(tokenTeste()->segundosRestantes())->toBeGreaterThan(0);
    });

    it('faz ida e volta com paraArray e deArray', function (): void {
        $token = tokenTeste();
        $copia = Token::deArray(json_decode((string) json_encode($token->paraArray()), true));

        expect($copia->valor)->toBe($token->valor)
            ->and($copia->expiraEm->getTimestamp())->toBe($token->expiraEm->getTimestamp())
            ->and($copia->emitidoEm->getTimestamp())->toBe($token->emitidoEm->getTimestamp())
            ->and($copia->ambiente)->toBe(Ambiente::Homologacao)
            ->and($copia->contrato)->toBe(CONTRATO_TESTE)
            ->and($copia->cartaoPostagem)->toBe(CARTAO_TESTE)
            ->and($copia->diretoriaRegional)->toBe(20)
            ->and($copia->apis)->toBe($token->apis)
            ->and($copia->permiteApi(587))->toBeTrue()
            ->and($copia->permiteApi(1))->toBeFalse();
    });

    it('recusa dados incompletos', function (): void {
        Token::deArray(['valor' => 'x']);
    })->throws(ErroValidacao::class, 'incompletos');

    it('recusa valor vazio', function (): void {
        new Token(' ', new DateTimeImmutable(), new DateTimeImmutable(), Ambiente::Producao);
    })->throws(ErroValidacao::class);

    it('vira texto e não aparece no var_dump', function (): void {
        $token = tokenTeste();
        ob_start();
        var_dump($token);
        $saida = (string) ob_get_clean();

        expect((string) $token)->toBe($token->valor)
            ->and($saida)->not->toContain($token->valor);
    });

    it('ignora ids de API inválidos ao recuperar', function (): void {
        $dados = tokenTeste()->paraArray();
        $dados['apis'] = [1, '2', 'x', 3.5];

        expect(Token::deArray($dados)->apis)->toBe([1, 2]);
    });
});
