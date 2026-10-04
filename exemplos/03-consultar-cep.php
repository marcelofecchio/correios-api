<?php

declare(strict_types=1);

require __DIR__ . '/_inicio.php';

$endereco = correios()->cep()->consultar(token: token(), cep: '01001-000');

mostrar('Logradouro', $endereco->logradouro);
mostrar('Bairro', $endereco->bairro);
mostrar('Cidade/UF', $endereco->cidade . '/' . $endereco->uf);
mostrar('Tipo de CEP', $endereco->tipoCep);

// Monta o Endereco da pré-postagem com o número e o complemento informados pelo usuário
$paraPrePostagem = $endereco->paraEndereco(numero: '100', complemento: 'Apto 12');
mostrar('Endereço para a pré-postagem', $paraPrePostagem->cidadeUf());
