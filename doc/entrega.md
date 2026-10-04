# Relatório de entrega — `marcelofecchio/correios-api`

Execução do `doc/prompt-inicial.md` concluída em 04/10/2026, até a etapa 13 da seção 8. O componente está implementado
e testado, com uma exceção: a etiqueta de **declaração de conteúdo em ZPL** foi adiada por decisão do usuário (ver
parte 2).

## 1. Resumo do que foi implementado

O componente PHP 8.4 integra as APIs dos Correios previstas na tabela 4.1 e gera em ZPL a etiqueta de postagem e o
DANFE simplificado, em 203 e 300 dpi. Ele não guarda estado, recebe toda a configuração da aplicação e não depende de
framework, com integração opcional para Laravel. Os testes com HTTP simulado e os testes contra a homologação real
dos Correios passaram. Durante esses testes apareceram quatro comportamentos da API que a documentação não registrava
(parte 4); o componente foi ajustado para todos eles.

| Módulo / método | Situação | Principais arquivos |
|---|---|---|
| Token (cartão de postagem) e objeto `Token` | completo | `src/Servicos/Token.php`, `src/Token.php`, `src/Mapeadores/TokenMapeador.php` |
| Meu Contrato (lista paginada, todas as páginas, um serviço) | completo | `src/Servicos/Contrato.php`, `src/Mapeadores/ContratoMapeador.php` |
| CEP | completo | `src/Servicos/Cep.php`, `src/Respostas/EnderecoCep.php` |
| Preço (unitário, lote com HTTP 206, serviços adicionais) | completo | `src/Servicos/Preco.php`, `src/Mapeadores/PrecoMapeador.php` |
| Prazo (unitário, lote, data prevista) | completo | `src/Servicos/Prazo.php`, `src/Mapeadores/PrazoMapeador.php` |
| Pré-postagem síncrona, incluindo logística reversa | completo | `src/Servicos/PrePostagem.php`, `src/Mapeadores/PrePostagemMapeador.php`, `src/Dto/NovaPrePostagem.php` |
| Pré-postagem assíncrona (criar e consultar) | completo | `src/Servicos/PrePostagem.php`, `src/Respostas/ProcessamentoAssincrono.php` |
| Cancelamento síncrono (por id e por código) | completo | `src/Servicos/PrePostagem.php` |
| Cancelamento em lote assíncrono e consulta do resultado | completo | `src/Servicos/PrePostagem.php`, `src/Respostas/ProcessamentoCancelamento.php` |
| Dados da postagem (`.../postada`) | completo (não testado em homologação, ver parte 5) | `src/Servicos/PrePostagem.php`, `src/Respostas/DadosPostagem.php` |
| Rótulo oficial (solicitar e baixar PDF) | completo | `src/Servicos/Rotulo.php`, `src/Respostas/ArquivoRotulo.php` |
| DCe (impressão da DACE e atualização da chave fiscal) | completo (DACE não obtida em homologação) | `src/Servicos/Dce.php`, `src/Respostas/Dace.php` |
| Rastro (um objeto e até 50) | completo | `src/Servicos/Rastro.php`, `src/Mapeadores/RastroMapeador.php` |
| `GeradorEtiquetas::postagem()` | completo | `src/Etiquetas/GeradorEtiquetas.php`, `resources/templates/postagem.zpl` |
| `GeradorEtiquetas::postagemDePrePostagem()` | completo | `src/Etiquetas/GeradorEtiquetas.php` |
| `GeradorEtiquetas::danfeSimplificada()` e `DadosDanfe::deXml()` | completo | `src/Etiquetas/DadosDanfe.php`, `resources/templates/danfe_simplificada.zpl` |
| `GeradorEtiquetas::declaracaoConteudo()` | **não feito** (adiado pelo usuário) | — |
| `GeradorEtiquetas::juntar()` | completo | `src/Etiquetas/GeradorEtiquetas.php` |
| `RenderizadorZpl`, `DataMatrixCorreios`, `ImagemZpl`, `EtiquetaZpl` | completo | `src/Etiquetas/` |
| Utilitários (`CodigoRastreamento`, CEP, CPF/CNPJ, `ChaveDocumentoFiscal`, `Dinheiro`, `Datas`) | completo | `src/Utilitarios/` |
| Integração com Laravel (ServiceProvider, config publicável, Facade) | completo | `src/Laravel/`, `config/correios.php` |
| README, CHANGELOG, LICENSE, exemplos 01 a 15 e `exemplos/laravel/` | completo | `README.md`, `CHANGELOG.md`, `exemplos/` |
| CI (GitHub Actions) | completo (ainda não executado: o repositório não foi enviado depois desta etapa) | `.github/workflows/ci.yml` |

## 2. Principais decisões técnicas

| Decisão | Escolha | Alternativas | Motivo |
|---|---|---|---|
| 300 dpi (4.7) | **Escala em código** (×1,5) dos templates de 203 dpi, com parciais de logo próprios em `resources/templates/300dpi/parciais/`. Um template inteiro em `300dpi/` também é aceito e usado como está. | Um conjunto de templates por resolução | Um layout só para manter; os logos `^GFA`, que não escalam, têm versão própria. Conferido no Labelary. |
| Acentuação (4.7) | **`^CI28`** (UTF-8) | Converter para outra codificação | Sem perda de caracteres; os templates do guia já usavam `^CI28`. |
| `CorreiosException` (4.6) | **Interface**, implementada por classes que estendem `RuntimeException` ou `InvalidArgumentException`. Os dados da resposta de erro ficam num trait (`DetalhesRespostaErro`) comum a `ErroApi`, `ErroAutenticacao` e `ErroLimiteRequisicoes`. | Classe base abstrata | Mantém a hierarquia do prompt e deixa cada erro estender a exceção SPL adequada. |
| `Volume` ou `Objeto` (4.4) | **`Volume`** | `Objeto` | Evita confusão com o "objeto postal" e com o código do objeto. |
| Dinheiro (4.4) | **`int` em centavos** nos DTOs e nas respostas; `Dinheiro` converte de/para `"19,92"` e `"150.00"` | `string` decimal | Somas exatas (totais do lote, declaração) sem `float`. |
| Declaração de conteúdo (4.7.7) | **Adiada** por decisão do usuário; o README e o exemplo 14 orientam a usar DCe + DACE | Usar o texto do modelo oficial | Falta o texto legal e o layout aprovados. |
| DANFE (4.7.6) | Aceita `cStat` 100 e 150; recusa EPEC (`tpEmis = 4`); chave no meio da etiqueta, como no guia | Só 100; seguir a Nota Técnica | Decisão do usuário (a Nota Técnica não está na documentação). |
| Datas enviadas às APIs | Formatadas **como informadas**, sem converter o fuso | Converter para `America/Sao_Paulo` | Um teste mostrou que a conversão fazia `18/10` virar `17/10` em servidores em UTC. |
| Cliente HTTP padrão | Guzzle com `timeout`, se instalado; senão descoberta PSR-18. Plugin do `php-http/discovery` desligado | Só descoberta | A descoberta não permite configurar o timeout. |
| Siglas dos adicionais na etiqueta | Mapa configurável; sem entrada, valor declarado → `VD` e `001` → `AR` | Mapa fixo de códigos | Não fixa os códigos do valor declarado, que mudam por serviço. |
| Escape no `^FH` | Além de `_ ^ ~ \`, também `{` e `}` | Só os quatro do guia | Um valor com `{{` gerava `ErroTemplate` indevido; agora sai impresso corretamente. |
| Endereços longos no DANFE | Corta primeiro o logradouro ou o bairro, preservando cidade/UF e CEP | Corte simples no fim | O corte simples partia o CEP (`90020-0`). |
| Remoção de acentos (DataMatrix) | Tabela própria | `iconv` TRANSLIT | O resultado do `iconv` varia entre sistemas. |
| Recibo da pré-postagem assíncrona | `criarAssincrona()` devolve `string`; aceita texto puro, string JSON ou objeto | Classe de resposta | O formato não era documentado; em homologação veio como texto. |
| `Token` (objeto de valor) | `MarceloFecchio\Correios\Token`; o serviço é `Servicos\Token` | — | Nome curto para a aplicação (`Token::deArray()`). |

## 3. Testes executados e resultados

Resultados da última execução de cada comando, em 04/10/2026, com PHP 8.4.15 (ZTS, x64) no Windows e PCOV 1.0.12
(instalado nesta execução com autorização do usuário):

| Comando | Resultado |
|---|---|
| `composer validate --strict` | `./composer.json is valid` |
| `vendor/bin/php-cs-fixer fix --dry-run --diff` (PER-CS 2.0) | `Found 0 of 138 files that can be fixed` |
| `vendor/bin/phpstan analyse` (nível `max`, sem baseline) | `[OK] No errors` |
| `vendor/bin/pest --coverage --min=90` | **313 aprovados, 0 falhos, 7 pulados** (1068 asserções); os 7 pulados são os de integração, sem as variáveis de ambiente |
| Cobertura de `src/` (exceto `src/Laravel/`) | **98,2 %** |
| `vendor/bin/pest --group=integracao` contra a **homologação real** | **7 aprovados, 0 falhos** (19 asserções): token, serviços do cartão, CEP, preço e adicionais, prazo, criação de pré-postagem + rótulo (PPN-288 confirmado) + cancelamento, e rastreamento |

- **Integração:** rodou com as credenciais de `doc/credentials.txt`, passadas por variáveis de ambiente e nunca
  gravadas no repositório. O PHP desta máquina não tem pacote de autoridades certificadoras (`curl.cainfo` vazio),
  por isso os testes rodaram com `php -d curl.cainfo=<pacote do Git>`, sem alterar o `php.ini`.
- **Labelary:** as quatro etiquetas dos snapshots (postagem e DANFE, em 203 e 300 dpi, com dados fictícios) foram
  renderizadas pela API do Labelary (HTTP 200) e conferidas visualmente. Uma sobreposição entre CEP e cidade em
  300 dpi foi corrigida. A etiqueta de declaração de conteúdo não existe, então não foi conferida.
- **Logotipos:** o `ImagemZpl` reproduz exatamente os bytes dos logos de SEDEX, PAC e Carta do sistema antigo.
- **DataMatrix:** reproduz exatamente a cadeia da etiqueta gerada pelo sistema antigo (`doc/legado/etiqueta-postagem-zpl/etiqueta_exemplo.zpl`). A conferência foi feita com a etiqueta real; em 04/10/2026 os dados dela foram trocados por fictícios, com a cadeia regenerada pelo mesmo algoritmo.
  O teste lê o arquivo de `doc/` na hora e é pulado se a pasta não existir; nenhum dado real foi para as fixtures.
- **Exemplos:** 01 a 06 e 08 a 11 rodaram contra a homologação. O 07 (assíncrono) rodou, mas a pré-postagem ficou
  pendente durante as tentativas (cerca de 2 minutos). O 10 mostrou a DACE indisponível com a pré-postagem pendente
  (PPN-376), e o exemplo tratou o erro. Os exemplos 12 a 15 rodaram sem credenciais.
- **Instalação em projeto limpo:** `composer require` do pacote (repositório local) com Guzzle, sem Laravel, seguido
  da geração de uma etiqueta: funcionou.
- **CI do GitHub Actions:** configurado, mas **não executado**, porque esta versão ainda não foi enviada ao GitHub.

## 4. Premissas adotadas

| Premissa | Fonte na documentação | Onde no código |
|---|---|---|
| Só com NF-e ou número da nota, `itensDeclaracaoConteudo` vai como lista vazia | Arquivo 06, seção 2 (inconsistência apontada) | `src/Mapeadores/PrePostagemMapeador.php` (`paraApi`) |
| `destinatario.endereco.regiao` recebe a UF | Prompt 4.4 e arquivo 06 | `PrePostagemMapeador::pessoa()` |
| `logisticaReversa` só é enviado como `S` (omitido quando falso) | Arquivo 06 | `PrePostagemMapeador::paraApi()` |
| Nome da pessoa: só o tamanho (3 a 50) é validado; a exigência de nome e sobrenome fica com a API | Arquivo 06 (`remetente.nome`) | `src/Dto/Pessoa.php` |
| Valor com só ponto (`"1.234"`) é lido como decimal (formato da Pré-postagem), não como milhar | Arquivo 00, seção 5 | `src/Utilitarios/Dinheiro.php` |
| Prazo em lote aceita HTTP 206, como o Preço | Prompt 4.5 (o arquivo 05 só cita 200) | `src/Servicos/Prazo.php` |
| `GET .../servicos/{coServico}` pode responder com o serviço ou com `itens` | Arquivo 02 (resposta não documentada) | `src/Servicos/Contrato.php` |
| O código do objeto devolvido pela API é aceito só pelo formato; o código informado pela aplicação valida também o dígito | Prompt 4.8 | `PrePostagemMapeador::prePostagem()`, `DadosEtiquetaPostagem` |
| A DACE térmica pode vir como texto puro ou Base64 | Arquivo 14, seção 5 | `src/Respostas/Dace.php` (`texto()`) |
| Datas não confirmadas de `.../postada` ficam como texto | Arquivo 12 e prompt 4.5 | `src/Respostas/DadosPostagem.php` |
| Erro com mensagem em resposta HTTP 200 (sem o campo esperado) é tratado como erro da API | Observado em homologação (PPN-288) | `src/Servicos/Servico.php` (`respostaSemCampo`) |

Divergências entre o prompt, a documentação e o comportamento observado, registradas no `CHANGELOG.md`:

- a pré-postagem **síncrona** voltou com status **7 (PENDENTE)**;
- o **PPN-288** veio com **HTTP 200**;
- o resultado do cancelamento em lote é uma **lista**;
- o cancelamento síncrono responde só `resultadoCancelamento`;
- há códigos de erro no formato `PPN-145-5`;
- a declaração de conteúdo em ZPL foi adiada;
- o campo cidade/UF do template de postagem foi movido de x=200 para x=212.

## 5. Limitações e critérios não atendidos

### Critérios de aceite (seção 9)

✔ atendido e verificado · ◐ atendido sem verificação completa · ✘ não atendido

| Critério | Situação | Observação |
|---|:-:|---|
| `composer validate --strict`; instala em projeto limpo com PHP 8.4 | ✔ | Instalação conferida com repositório local (`path`); ainda não publicado no Packagist |
| PHPStan nível máximo, sem baseline | ✔ | |
| PHP-CS-Fixer sem pendências | ✔ | |
| Testes Pest passam; cobertura ≥ 90% | ✔ | 98,2 % |
| Nenhum endpoint autenticado sem token por parâmetro; nenhum estado global | ✔ | Revisão do código, busca por `$_SESSION`/`static $`/`$GLOBALS` e teste de token vazio |
| Trocar o ambiente muda o host; nenhuma URL fora do enum `Ambiente` | ✔ | Teste de hosts e busca no código |
| Nenhum código de serviço ou adicional fixo na lógica | ✔ | Só a constante `001` do AR, exigida pelo prompt para `avisoRecebimento()` |
| Endpoints da tabela 4.1 com caminhos e campos da documentação | ✔ | |
| Comportamentos da tabela 2.4 cobertos por testes | ◐ | 12 de 14 com teste direto. Item 4 (vários rótulos num PDF): só a leitura de um PDF é testada. Item 13 (`prazoPostagem` padrão): não há lógica no componente, só a leitura da data |
| Sem espera, repetição ou polling | ✔ | |
| Métodos de 4.7.3 com as assinaturas descritas | ✘ | Falta `declaracaoConteudo()` (adiado) |
| DataMatrix igual ao da etiqueta antiga | ✔ | |
| DANFE com a chave correta, sem itens nem valores | ✔ | |
| Os três ZPL abrem no Labelary em 203 e 300 dpi | ◐ | Dois de três (a declaração não existe) |
| Núcleo funciona sem Laravel | ✔ | Projeto limpo sem Laravel |
| Exemplos de `exemplos/` rodam com credenciais de homologação | ✔ | Ver parte 3 |
| README com todas as seções da etapa 7 | ✔ | |
| Este relatório com as cinco partes | ✔ | |

### Lacunas da documentação que afetam o componente

- `GET .../postada` não foi testado: só responde depois da postagem física.
- DACE (tipos T, R e C) não foi obtida em homologação.
- Os layouts de rótulo diferentes de `PADRAO` e o tipo `R` não foram testados.
- O resultado de `.../servicos/{coServico}` não foi documentado; o tratamento é defensivo.
- O significado oficial dos campos fixos do DataMatrix depende do manual de etiquetas dos Correios, que não está na
  documentação.
- Os requisitos legais do DANFE vêm de fonte secundária (Nota Técnica 2020.004 não lida).
- A leitura dos códigos de barras e do DataMatrix por equipamento e a impressão física não foram testadas.
- Em homologação, pré-postagens pendentes foram canceladas automaticamente pelos Correios em poucos minutos. Por isso
  não foi possível obter um rótulo em PDF nem a DACE nesta execução.

### Pendências e perguntas ao usuário

1. **Declaração de conteúdo em ZPL:** precisa do texto legal e do layout para ser implementada.
2. **Nota Técnica 2020.004:** confirmar a posição da chave de acesso e o tratamento de contingência (EPEC).
3. **Manual de etiquetas dos Correios:** para codificar corretamente os campos fixos do DataMatrix.
4. **Publicação:** enviar ao GitHub (o CI roda no push) e publicar no Packagist dependem do seu ok.
5. **Certificados no PHP local:** configure `curl.cainfo` no `php.ini` para as chamadas reais funcionarem sem o
   `-d curl.cainfo=...`.
