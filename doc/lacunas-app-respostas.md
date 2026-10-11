# Respostas às lacunas do levantamento do MARCELO ENVIOS

Resposta, item a item, ao `correios-api-gaps.md` (Fase 0 do MARCELO ENVIOS, 11/10/2026). As mudanças estão na seção
"Não lançado" do `CHANGELOG.md` e entram na próxima versão do pacote.

Situação: **resolvido** (o pacote passou a cobrir) · **parcial** (o pacote ajuda, mas falta dado dos Correios) ·
**fora do pacote** (depende de teste real ou de documentação que não existe).

| # | Lacuna | Situação | O que o app faz agora |
|---|---|---|---|
| 1 | Status da pré-postagem síncrona | resolvido | `rotulo()->tentarObter()` no job `AwaitLabelReady` |
| 2 | `observacao` com 50 caracteres | parcial | Limite `NovaPrePostagem::MAXIMO_OBSERVACAO`; impressão no PDF a confirmar |
| 3 | Bateria, inflamável, explosivo | parcial | `cienteObjetoNaoProibido` (já público) + categoria `ArtigosPerigosos` |
| 4 | Código de mão própria | resolvido | `CategoriaServicoAdicional::MaoPropria->localizar()` |
| 5 | Credenciais do banco | resolvido | `app(FabricaCorreios::class)->deArray([...])` |
| 6 | Laravel 13 | resolvido | Nada: suíte verde com Laravel 13.35 |
| 7 | Limites por serviço | fora do pacote | Mantém os limites em `carrier_services.rules` |
| 8 | Classificação do rastreio | resolvido | `$rastreamento->situacao($mapaDoApp)` |
| 9 | DACE e layouts lineares | fora do pacote | Teste de integração 11 para conferir |
| 10 | DataMatrix | fora do pacote | Teste na impressora com o `.zpl` de amostra |
| 11 | Código de valor declarado | resolvido | `CategoriaServicoAdicional::ValorDeclarado->localizar()` |
| 12 | Certificados no Windows | resolvido (já era) | `curl.cainfo` ou um cliente Guzzle com `verify` |

## 1. Status da pré-postagem síncrona

A especificação continua sem consulta por id. O pacote agora tem `rotulo()->tentarObter($token, SolicitacaoRotulo)`.
Ele solicita e baixa o rótulo numa única chamada e devolve `null` em PPN-288, sempre com recibo novo. Outros erros
continuam como exceção. É o mesmo padrão da solução provisória do app, que passa a usar o método:

```php
$arquivo = $correios->rotulo()->tentarObter($token, SolicitacaoRotulo::porIds([$envio->prepostagem_id]));
if ($arquivo === null) {
    $this->release([5, 10, 30, 60, 120][$this->attempts() - 1] ?? 120);
    return;
}
```

Para conhecer o `statusAtual` antes de pedir o rótulo, a única via documentada é a assíncrona
(`criarAssincrona()` + `consultarAssincrona()`). O aviso sobre o cancelamento automático em homologação continua
valendo.

## 2. Limite de `observacao`

O limite de 50 caracteres está confirmado no schema e agora é público (`NovaPrePostagem::MAXIMO_OBSERVACAO`). Não há
documentação sobre a impressão no rótulo PDF. O teste de integração 11 cria uma pré-postagem com observação, pede o
rótulo e, se a pré-postagem não estiver pendente, grava `build/homologacao/rotulo-linear-100x150.pdf` para
conferência. A decisão entre 50 e 100 caracteres no app continua sendo do dono. Com 50, o texto é o mesmo no PDF e no
ZPL.

O schema também tem `destinatario.obs` (100 caracteres, "observação do destinatário preenchida a critério do
cliente"). O pacote não usa esse campo: ele é do destinatário e também não se sabe se sai impresso.

## 3. Bateria, inflamável ou explosivo

Uma correção ao levantamento: `cienteObjetoNaoProibido` **já é parâmetro público** de `NovaPrePostagem`, com padrão
`true`. `false` envia `"0"` ("objeto proibido no fluxo postal"). A documentação também mostra:

- a API Rastro cita um **serviço adicional de transporte de artigos perigosos** (`servico.dgr`). O pacote o reconhece
  pelo nome em `CategoriaServicoAdicional::ArtigosPerigosos`, se o contrato tiver esse adicional;
- a resposta da pré-postagem traz `ehObjetoDgr`, agora em `PrePostagemCriada::$objetoPerigoso`.

Para o app, a proposta de **bloquear a contratação** com o switch ligado continua sendo a mais segura. As regras para
baterias de lítio não estão na documentação. Mandar `"0"` sem saber como os Correios tratam o objeto é arriscado.
Pergunta ao dono mantida.

## 4 e 11. Mão própria e valor declarado

Novo enum `CategoriaServicoAdicional` (`AvisoRecebimento`, `MaoPropria`, `ValorDeclarado`, `ArtigosPerigosos`). A
categoria vem da `sigla` e do `nome` que `preco()->servicosAdicionais()` devolve. Siglas da documentação: `VD`/`VDS`,
`AR` e `MP`.

```php
$adicionais = $correios->preco()->servicosAdicionais($token, $servico);
$mp = CategoriaServicoAdicional::MaoPropria->localizar($adicionais);   // null: o serviço não aceita
$vd = CategoriaServicoAdicional::ValorDeclarado->localizar($adicionais);
$lista = array_filter([$mp ? ServicoAdicional::de($mp) : null, $vd ? ServicoAdicional::de($vd, $valorCentavos) : null]);
```

O app pode descobrir os códigos no cadastro do serviço e gravá-los em `carrier_services`, editáveis pelo ADM, como
já planejado. O teste de integração 04b grava em `build/homologacao/adicionais.json` os adicionais de SEDEX e PAC com
a categoria identificada. Rode-o para confirmar o código de mão própria antes de mostrar o switch.

## 5. Credenciais do banco

Nova classe `FabricaCorreios`, registrada no Laravel como singleton com o logger da aplicação:

```php
$correios = app(FabricaCorreios::class)->deArray([
    'ambiente' => $credencial->ambiente,
    'usuario' => $credencial->usuario,
    'codigo_acesso' => decrypt($credencial->codigo_acesso),
    'cnpj' => $credencial->cnpj,
    'cartao_postagem' => $credencial->cartao_postagem,
]);
```

O singleton do `.env` continua preguiçoso e não precisa de configuração se não for usado.

## 6. Laravel 13

`require-dev` aceita `illuminate/support ^13.0` e `orchestra/testbench ^11.0`, e o CI ganhou a matriz Laravel 11,
12 e 13. A suíte completa passou com Laravel 13.35.0, Testbench 11.3.0, Pest 4.7.8 e PHPUnit 12.5.33.

## 7. Limites por serviço

A documentação das APIs não traz a tabela de limites e o manual V2.4 também não. O pacote não inventa números. A
solução do app (limites vazios em `carrier_services.rules`, editáveis pelo ADM) continua valendo, e a recusa da API
chega em `ErroApi` com a mensagem dos Correios.

## 8. Classificação do rastreio

Novo enum `SituacaoRastreamento` e `situacao()` em `Rastreamento` (evento mais recente) e `EventoRastreamento`. O
arquivo 10 diz que a tabela de eventos não faz parte da especificação. Por isso a tabela padrão tem só os quatro
eventos do exemplo do manual V2.4:

| Evento | Situação |
|---|---|
| `PO-01` | `Postado` |
| `RO-01` | `EmTransito` |
| `OEC-03` | `SaiuParaEntrega` |
| `BDE-01` | `Entregue` |

O tipo importa: `BDE` com outro tipo **não** é entrega. Fora da tabela, a situação é `Desconhecida`, e não "em
trânsito": o app decide como exibir. O mapa do app (`'CODIGO-TIPO'` ou `'CODIGO'` → situação) tem prioridade, então
o mapa em configuração planejado pelo app continua, agora como parâmetro:

```php
$situacao = $rastreamento->situacao(config('envios.eventos_rastreio'));
if ($situacao->finalizada()) { /* entregue, devolvido ou cancelado */ }
```

## 9. DACE e layouts lineares

Não dá para resolver sem uma pré-postagem com status 2 em homologação. O teste de integração 11 pede o rótulo em
`Linear100x150` e grava o PDF quando a pré-postagem não fica pendente. A DACE continua opcional na tela.

## 10. DataMatrix

Sem mudança. Depende de leitura por equipamento dos Correios ou do manual de etiquetas, que não está na
documentação. Os campos fixos continuam configuráveis em `ConfiguracaoEtiquetas`.

## 12. Certificados no Windows

Sem mudança no pacote. Além do `curl.cainfo` no `php.ini`, dá para injetar um cliente Guzzle com o pacote de
autoridades certificadoras, sem mexer no PHP da máquina:

```php
$fabrica = new FabricaCorreios(clienteHttp: new GuzzleHttp\Client(['verify' => '/caminho/cacert.pem', 'timeout' => 30]));
```

Com um cliente injetado, o `timeout` da configuração não é aplicado: configure-o no próprio cliente.

## Como rodar as conferências em homologação

```bash
export CORREIOS_HOM_USUARIO=<CNPJ> CORREIOS_HOM_CODIGO_ACESSO=... CORREIOS_HOM_CNPJ=<CNPJ> \
       CORREIOS_HOM_CARTAO=... CORREIOS_HOM_CONTRATO=...
vendor/bin/pest --group=integracao
# resultados em build/homologacao/ (fora do git): adicionais.json e, se não ficar pendente, rotulo-linear-100x150.pdf
```

No PHP desta máquina, acrescente `-d curl.cainfo=<pacote de certificados>` ao comando, como no relatório de entrega.
