# Prompt: criar o componente PHP `marcelofecchio/correios-api`

> **Como usar este prompt:** ele é lido por um agente de IA **junto com a documentação do projeto** — a pasta `doc/` (ver 2.1). Este prompt diz **o que construir**; a documentação diz **como a API dos Correios funciona**. Leia os dois antes de escrever código.

## 1. Papel e objetivo

Você é um desenvolvedor PHP sênior, especialista em criar bibliotecas reutilizáveis distribuídas via Composer/Packagist, em integrações com APIs REST e na linguagem ZPL (Zebra Programming Language).

Crie, do zero, um **componente PHP 8.4** chamado `marcelofecchio/correios-api`, publicado no Packagist, que integre com a **API REST dos Correios (CWS – Correios Web Services)** e gere **etiquetas em ZPL** a partir de templates.

O componente será usado por várias aplicações diferentes (PHP puro e Laravel). Por isso ele precisa ser:

- **sem estado (stateless)**: não guarda token, sessão nem cache por conta própria;
- **configurável pela aplicação**: credenciais, ambiente e códigos de serviço vêm sempre da aplicação que usa o componente;
- **independente de framework**, com uma integração **opcional** para Laravel;
- **bem testado, tipado e documentado**.

---

## 2. Fonte da verdade: a documentação do projeto

### 2.1 O que está disponível

Os caminhos são relativos à raiz do projeto. A pasta `doc/` está organizada assim:

| Pasta | Conteúdo |
|---|---|
| `doc/referencia/` | Documentação da API (arquivos `correios-00` a `correios-14`), guias de ZPL e coleção do Postman. Os arquivos citados só pelo nome neste prompt estão aqui. |
| `doc/legado/` | Código, templates e etiquetas reais do **sistema antigo**. Servem de exemplo de funcionamento: **não copie**. |
| `doc/recursos/` | Material novo, **pronto para usar** no componente: parciais `^GFA` dos logotipos, logotipos em PNG e o conversor de imagem. |
| `doc/oficial/` | Manual oficial de integração dos Correios (PDF, v2.4). |

| Arquivo | Conteúdo | Usado em |
|---|---|---|
| `correios-00-instrucoes-gerais.md` | Ambientes, autenticação, convenções, erros, regras de negócio, fluxo completo. **Leia primeiro.** | Tudo |
| `correios-01-token-api.md` | API Token | Módulo Token |
| `correios-02-meucontrato-servicos-cartao-api.md` | Serviços habilitados no cartão de postagem | Módulo Contrato |
| `correios-03-cep-enderecos-api.md` | Consulta de CEP (`/v2`) | Módulo CEP |
| `correios-04-preco-api.md` | API Preço e serviços adicionais | Módulo Preço |
| `correios-05-prazo-api.md` | API Prazo | Módulo Prazo |
| `correios-06-prepostagem-criar-api.md` | Criação de pré-postagem, logística reversa, declaração de conteúdo/AR em HTML | Módulo Pré-postagem |
| `correios-07-prepostagem-assincrona-registrados-api.md` | Criação e cancelamento assíncronos | Módulo Pré-postagem |
| `correios-08-rotulo-solicitar-assincrono-pdf-api.md` | Solicitação do rótulo oficial (PDF) | Módulo Rótulo |
| `correios-09-rotulo-download-assincrono-api.md` | Download do rótulo oficial | Módulo Rótulo |
| `correios-10-rastro-sro-api.md` | Rastreamento (SRO) | Módulo Rastro |
| `correios-11-webhook-api.md` | Webhook | Fora do escopo desta versão (ver 4.1) |
| `correios-12-prepostagem-postada-api.md` | Dados reais após a postagem | Módulo Pré-postagem |
| `correios-13-prepostagem-cancelar-api.md` | Cancelamento síncrono | Módulo Pré-postagem |
| `correios-14-prepostagem-dce-api.md` | DCe e impressão da DACE | Módulo DCe |
| `correios-etiquetas-zpl.md` | Como gerar a etiqueta de postagem em ZPL: layout, DataMatrix de 164 caracteres, logotipos `^GFA`, template base, regras do renderizador | Etiquetas (4.7) |
| `correios-danfe-simplificada-zpl.md` | Como gerar o DANFE Simplificado (etiqueta) em ZPL: chave de acesso, dados exigidos, template base, origem dos campos no XML da NF-e | Etiquetas (4.7) |
| `correios-api-homologacao.postman_collection.json` | Requisições prontas (URLs, cabeçalhos, corpos, ordem de execução) | Fixtures e testes de integração |
| `doc/legado/etiqueta-postagem-zpl/` | Código, templates e etiqueta real do **sistema antigo** (exemplo de funcionamento, não copiar) | Etiquetas (4.7) |
| `doc/legado/danfe-simplificada-zpl/` | Código, template, etiqueta real e XML de NF-e do **sistema antigo** (exemplo de funcionamento, não copiar) | Etiquetas (4.7) |
| `doc/recursos/parciais-zpl/` | Parciais `^GFA` prontos: logo dos Correios (203 e 300 dpi) e símbolos de SEDEX, PAC e Carta | Etiquetas (4.7.2) |
| `doc/recursos/logos-png/` | Logotipos decodificados em PNG, para conferência e para gerar outras resoluções | Etiquetas (4.7.2) e testes |
| `doc/recursos/converter-imagem-para-gfa.php` | Script que converte uma imagem em parcial `^GFA` | Etiquetas (4.7) |
| `doc/oficial/manual-integracao-correios-api-v2.4.pdf` | Manual oficial dos Correios | Consulta (ver 2.2, item 1) |

### 2.2 Como interpretar a documentação

1. **Hierarquia das fontes:** trechos marcados como **"✔ Observado em homologação"** (testes reais) vencem a especificação OpenAPI reproduzida nos arquivos, que vence o manual dos Correios, que vence este prompt. Se este prompt divergir da documentação, **a documentação vence** — registre a divergência no `CHANGELOG.md`.
2. **Use os nomes exatos** de campos, tipos, tamanhos, obrigatoriedades e padrões que estão nos arquivos. **Não invente campos nem endpoints.**
3. Trechos marcados como **"ainda não testado"**, **"a confirmar"** ou **"não observado"** são lacunas conhecidas: implemente de forma defensiva (aceite as variações razoáveis, valide a resposta antes de usar) e liste essas lacunas no README, na seção "Limitações conhecidas".
4. **Não é preciso acessar a internet** para consultar a especificação: tudo o que o componente precisa está nos arquivos. Se faltar alguma informação que não esteja neles, **pare e pergunte** ao usuário em vez de supor.
5. A coleção do Postman traz as variáveis de credenciais **vazias ou fictícias** (preencha-as localmente, sem versionar). Use-a só como referência de requisições; **nunca copie credenciais** para código, testes, fixtures, exemplos ou README.

### 2.3 Ambientes

| Ambiente | Host das APIs | Portal |
|---|---|---|
| Homologação | `https://apihom.correios.com.br` | `https://cwshom.correios.com.br` |
| Produção | `https://api.correios.com.br` | `https://cws.correios.com.br` |

Cada API tem seu próprio prefixo depois do host (`/token`, `/cep`, `/preco`, `/prazo`, `/srorastro`, `/prepostagem`, `/meucontrato`). Credenciais, tokens, ids, códigos de objeto e recibos de um ambiente **não valem no outro**.

### 2.4 Comportamentos confirmados em teste que o componente precisa respeitar

Estes pontos **não estão na especificação oficial** e foram descobertos nos testes. Todos estão detalhados na documentação; a lista serve de checklist.

| # | Comportamento | Onde está |
|---|---|---|
| 1 | Em **homologação**, o usuário do Basic Auth é o **CNPJ** (14 dígitos); em produção, o login do Meu Correios. | 00 (4.2), 01 |
| 2 | A API Token responde **HTTP 201**, limita **3 requisições/segundo** (HTTP 429) e só emite token novo se o atual expirou, se as permissões mudaram ou nos 30 minutos finais. | 01 |
| 3 | O rótulo oficial é assíncrono em dois passos: `POST .../rotulo/assincrono/pdf` → `{"idRecibo": "..."}`; `GET .../rotulo/download/assincrono/{idRecibo}` → `{"dados": "<PDF em Base64>", "nome": "idRecibo_<id>.pdf"}`. | 08, 09 |
| 4 | Um recibo com várias pré-postagens devolve **um único PDF** com as etiquetas lado a lado em A4. Repetir a solicitação gera novo recibo, mas mantém o código do objeto. | 09 |
| 5 | Pré-postagem **assíncrona**: `situacao = PROCESSADO` não basta. Ela fica alguns minutos em `statusAtual = 7` (PENDENTE) antes de `2` (PREPOSTADO). | 07 |
| 6 | Rótulo solicitado com status `7` é aceito, mas o download falha com `PPN-288` e **aquele recibo nunca passa a funcionar**. É preciso solicitar o rótulo de novo com status `2`. | 07, 08, 09 |
| 7 | Há **dois formatos de erro**: `MessageResponse` (`msgs`, `causa`, `date`, `path`...) e `{"mensagem": "PPN-288: ..."}`. Regras de negócio podem vir com HTTP **400 ou 422**. | 06, 09 |
| 8 | Na API Preço, o **SEDEX REVERSO (03247)** exige o adicional **016** (`ERP-049`); no PAC REVERSO (03301) ele é opcional. Na Pré-postagem o 016 **não** é exigido. A obrigatoriedade vem de `GET /preco/v1/servicos-adicionais/{coProduto}` (`sgIndicador`: `O` obrigatório, `P` opcional). | 04, 06 |
| 9 | Emitir o AR exige o adicional **001** na pré-postagem (senão HTTP 422). | 06 |
| 10 | Os endpoints HTML de declaração de conteúdo e AR falharam no servidor de homologação (`viewResolver is null` / HTTP 500). | 06 |
| 11 | Formatos de valor diferem por API: **Preço responde** com vírgula decimal em texto (`"19,92"`); **Pré-postagem recebe** texto com ponto e 2 casas (`"150.00"`). Peso em gramas e medidas em cm vão como **string**. | 00 (5), 04, 06 |
| 12 | Formatos de data diferem por campo (`DD-MM-YYYY`, `dd/MM/yyyy`, `YYYY-MM-DD`, ISO). Confira o formato de cada campo no arquivo da API. | 00 (5), 04, 05 |
| 13 | `prazoPostagem` padrão = criação + 14 dias (máximo 90). O manual fala em 7 dias; vale o valor retornado. | 00, 06 |
| 14 | A solicitação de rótulo aceitou um id vazio na lista. **Não dependa disso**: remova ids vazios antes de enviar. | 08 |

---

## 3. Decisões técnicas já tomadas

| Tema | Decisão |
|---|---|
| Linguagem | PHP **8.4**, `declare(strict_types=1);` em todos os arquivos |
| Pacote | `marcelofecchio/correios-api` |
| Namespace raiz | `MarceloFecchio\Correios` (PSR-4, pasta `src/`) |
| Idioma | **Tudo em português**: classes, métodos, propriedades, mensagens de exceção, README e exemplos. Use português sem acentos nos identificadores (ex.: `Endereco`, `Configuracao`, `calcularPreco`). Os **campos JSON da API** mantêm o nome original dos Correios (dentro dos mapeadores). |
| Cliente HTTP | **PSR-18** (`psr/http-client`) + **PSR-17** (`psr/http-factory`), com `php-http/discovery` para descobrir automaticamente a implementação. **Guzzle 7** como `suggest` no `composer.json`. A aplicação pode injetar qualquer cliente PSR-18. |
| Framework | Independente de framework + **ServiceProvider opcional para Laravel** (com auto-discovery) |
| Testes | **Pest** |
| Qualidade | **PHPStan** no nível máximo, **PHP-CS-Fixer** (regra PER-CS 2.0), **GitHub Actions** para CI |
| Licença | MIT |

### Recursos do PHP 8.4 a usar quando fizer sentido

- `readonly class` para DTOs e objetos de valor;
- `enum` com valor (backed enum) para ambientes, formatos, tipos de objeto, status etc.;
- property hooks e visibilidade assimétrica (`public private(set)`) onde deixarem o código mais claro;
- `new Classe()->metodo()` sem parênteses extras;
- argumentos nomeados nos exemplos.

---

## 4. Escopo funcional

### 4.1 Módulos da API que entram no escopo

Os caminhos abaixo são relativos ao host do ambiente. Os detalhes de parâmetros, corpos e respostas estão no arquivo indicado.

| Módulo | O que fazer | Endpoints | Arquivo |
|---|---|---|---|
| **Token** | Gerar token por **cartão de postagem** | `POST /token/v1/autentica/cartaopostagem` (Basic `usuario:codigoAcesso` + corpo `{"numero": "<cartão>"}`) → HTTP 201 | 01 |
| **Contrato** | Listar e consultar os serviços habilitados no cartão | `GET /meucontrato/v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos` e `.../servicos/{coServico}` (paginado) | 02 |
| **CEP** | Consultar endereço por CEP | `GET /cep/v2/enderecos/{cep}` | 03 |
| **Preço** | Preço nacional (um serviço e lote) e adicionais aceitos/obrigatórios de um serviço | `GET /preco/v1/nacional/{coProduto}`, `POST /preco/v1/nacional`, `GET /preco/v1/servicos-adicionais/{coProduto}` | 04 |
| **Prazo** | Prazo nacional (um serviço e lote) e data prevista | `GET /prazo/v1/nacional/{coProduto}`, `POST /prazo/v1/nacional`, `GET /prazo/v1/data-prevista` | 05 |
| **Pré-postagem** | Criar (síncrona), incluindo **logística reversa** | `POST /prepostagem/v1/prepostagens` | 06 |
| | Criar e consultar pela via assíncrona | `POST /prepostagem/v1/prepostagens/assincrona`, `GET .../assincrona/{recibo}` | 07 |
| | Cancelar (um objeto) | `DELETE /prepostagem/v1/prepostagens/{id}`, `DELETE .../objeto/{codigoObjeto}` | 13 |
| | Cancelar em lote (assíncrono) e consultar o resultado | `DELETE /prepostagem/v1/prepostagens/assincrona` (com corpo JSON), `GET .../canceladas/assincrona/{idRecibo}` | 07 |
| | Consultar dados reais após a postagem | `GET /prepostagem/v1/prepostagens/postada?codigoObjeto=` | 12 |
| **Rótulo oficial** | Solicitar e baixar o rótulo em PDF gerado pelos Correios | `POST /prepostagem/v1/prepostagens/rotulo/assincrono/pdf`, `GET .../rotulo/download/assincrono/{idRecibo}` | 08, 09 |
| **DCe** | Imprimir a DACE e informar a chave do documento fiscal | `POST /prepostagem/v1/prepostagens/dce/dace/impressao`, `PATCH /prepostagem/v1/prepostagens/dce` | 14 |
| **Rastro** | Rastrear um ou vários objetos (todos os eventos, primeiro ou último) | `GET /srorastro/v1/objetos/{codigoObjeto}`, `GET /srorastro/v1/objetos?codigosObjetos=` (até 50) | 10 |

**Fora do escopo desta versão** (deixe a arquitetura preparada para adicionar depois sem quebrar a API pública): Webhook (arquivo 11), autenticação por contrato ou só por usuário, agências, faturas, faixas de etiquetas, Pedido de Informação (Fale Conosco), serviços internacionais, lote de objetos simples, imagens de entrega e AR Digital do Rastro, e os endpoints HTML de declaração de conteúdo e AR (instáveis em homologação — ver 2.4, item 10).

**Não há endpoint documentado de consulta de uma pré-postagem síncrona por id.** Não invente um. O status de uma pré-postagem criada pela via síncrona vem da resposta da criação; o da via assíncrona, de `GET .../assincrona/{recibo}`.

### 4.2 Token de autorização: regras obrigatórias

1. O componente **gera** o token (módulo Token), mas **não o armazena**.
2. **Toda chamada a um endpoint autenticado recebe o token como parâmetro explícito**, sempre como primeiro argumento:
   ```php
   $correios->preco()->calcular(token: $token, consulta: $consulta);
   ```
3. O parâmetro aceita `Token|string`: o objeto de valor `Token` ou a string pura do token.
4. Crie o objeto de valor `Token` (readonly) com:
   - `valor` (string), `emitidoEm` e `expiraEm` (`DateTimeImmutable`; a API devolve data-hora **sem fuso** — interprete como `America/Sao_Paulo`), `ambiente`;
   - os dados do cartão devolvidos pela API: `cartaoPostagem`, `contrato`, `diretoriaRegional` (campo `dr`), `cnpj` e a lista `apis` (ids das APIs liberadas);
   - `estaValido(int $margemSegundos = 300): bool`, que considera uma margem de segurança antes da expiração;
   - `paraArray()` e `static deArray(array $dados)`, para a aplicação gravar o token na sessão ou no cache e recuperá-lo depois.
5. O token deve ser enviado no cabeçalho `Authorization: Bearer <token>`.
6. A geração do token considera sucesso os HTTP **200 e 201**. HTTP **429** vira `ErroLimiteRequisicoes` (ver 4.6) com mensagem orientando a reutilizar o token em vez de gerar outro.
7. O serviço Token confere que o campo `ambiente` da resposta corresponde ao ambiente configurado e lança `ErroConfiguracao` se não corresponder.

### 4.3 Configuração vinda da aplicação

O componente não pode ler `.env`, arquivos nem variáveis globais. Tudo chega pelo construtor.

Crie a classe `Configuracao` (readonly), montada de duas formas:

- pelo construtor com argumentos nomeados;
- por `Configuracao::deArray(array $config)`, com validação e mensagens de erro claras para chaves ausentes ou inválidas.

Ela deve conter, no mínimo:

| Campo | Descrição |
|---|---|
| `ambiente` | enum `Ambiente::Homologacao` / `Ambiente::Producao` (define o host) |
| `usuario` | usuário do Basic Auth. **Em homologação é o CNPJ (14 dígitos); em produção é o login do Meu Correios.** O componente envia exatamente o que a aplicação informar — não deduz um a partir do outro. |
| `codigoAcesso` | código de acesso à API, gerado no portal CWS **do mesmo ambiente** |
| `cnpj` | CNPJ do contrato (14 dígitos). Usado no módulo Contrato e como documento do remetente padrão. |
| `cartaoPostagem` | número do cartão de postagem, como **string** (tem zeros à esquerda) |
| `contrato` | número do contrato (opcional; usado em Preço e Contrato). Se ausente, use o que vier no `Token`. |
| `diretoriaRegional` | DR do contrato (opcional). A API Preço exige `nuDR` quando `nuContrato` é enviado. Se ausente, use o `dr` do `Token`. |
| `servicos` | mapa de apelidos para códigos de serviço, ex.: `['sedex' => '03220', 'pac' => '03298', 'sedex_reverso' => '03247', 'pac_reverso' => '03301']`. Os códigos variam por contrato, por isso **não** fixe códigos na lógica. Os métodos aceitam o apelido ou o código direto. Códigos são **strings** (zeros à esquerda). |
| `remetentePadrao` | `Pessoa` opcional, usada como remetente quando nenhum for informado |
| `etiquetas` | `ConfiguracaoEtiquetas` opcional — resolução, templates próprios, logotipos por serviço (ver 4.7.2) |
| `timeout` | timeout das requisições em segundos |
| `urlBase` | opcional, para sobrescrever o host do ambiente (útil em testes) |

O ponto de entrada é a classe `Correios` (fachada do componente):

```php
$correios = new Correios(
    configuracao: $configuracao,
    clienteHttp: $clientePsr18,      // opcional: usa discovery se for nulo
    fabricaRequisicao: $fabricaPsr17, // opcional
    fabricaStream: $fabricaStream,    // opcional
);

$correios->token();        // Servicos\Token
$correios->contrato();     // Servicos\Contrato
$correios->cep();          // Servicos\Cep
$correios->preco();        // Servicos\Preco
$correios->prazo();        // Servicos\Prazo
$correios->prePostagem();  // Servicos\PrePostagem
$correios->rotulo();       // Servicos\Rotulo   (rótulo oficial em PDF)
$correios->dce();          // Servicos\Dce
$correios->rastro();       // Servicos\Rastro
$correios->etiquetas();    // Etiquetas\GeradorEtiquetas (ZPL, sem chamada à API)
```

### 4.4 Classes de entrada de dados (DTOs)

Crie DTOs `readonly`, validados no construtor, que lançam `ErroValidacao` com mensagem clara e o nome do campo. Os limites de tamanho de cada campo estão no arquivo 06 (seções 1 a 4) — valide-os localmente.

- **`Pessoa`**: nome (nome e sobrenome, 3 a 50 caracteres), documento (CPF ou CNPJ, validado pelo dígito verificador, aceito com ou sem máscara e guardado só com números), e-mail, telefone (DDD com 2 dígitos + 8 dígitos), celular (DDD + 9 dígitos), `Endereco`.
- **`Endereco`**: CEP (8 dígitos, normalizado, sempre string), logradouro (até 50), número (até 6; aceitar "S/N"), complemento (até 30), bairro (até 30), cidade (até 30), UF (enum `Uf` com as 27 unidades), país (padrão BR).
- **`Item`**: descrição (2 a 60 caracteres), quantidade, valor unitário, peso (opcional), NCM (opcional). Usado na declaração de conteúdo e na pré-postagem (`itensDeclaracaoConteudo`).
- **`Volume`** (ou `Objeto`): peso em gramas, formato (enum `FormatoObjeto`: `1` envelope, `2` caixa/pacote, `3` cilindro/rolo), comprimento, largura, altura e diâmetro em cm. Regras: pacote exige altura, largura e comprimento; rolo exige comprimento e diâmetro. Os limites mínimos e máximos por serviço **não estão na documentação** — valide só o que está documentado (positivo, até 3 dígitos; peso até 6 dígitos) e deixe a API recusar o resto.
- **`ServicoAdicional`**: código de 3 caracteres e, quando for valor declarado, o valor. Ofereça construtores nomeados para os casos conhecidos (`avisoRecebimento()` = `001`, `valorDeclarado(...)`), recebendo o código do valor declarado como parâmetro, porque ele **muda por serviço** (`019` no SEDEX, `064` no PAC nos testes). Não fixe essa relação na lógica.
- **`NovaPrePostagem`**: remetente, destinatário, serviço, volume, itens, adicionais, documento fiscal (`chaveNFe`, `numeroNotaFiscal` ou declaração de conteúdo), `emiteDCe`, observação (até 50), pedido externo (até 25), `prazoPostagem`. Para **logística reversa**: `logisticaReversa`, `dataValidadeLogReversa` e e-mail do remetente obrigatório; o remetente é o cliente que devolve e o destinatário é a loja (arquivo 06, seção "Logística reversa").
- DTOs de consulta: `ConsultaPreco`, `ConsultaPrazo`, `SolicitacaoRotulo` (ids **ou** códigos de objeto, `tipoRotulo` `P`/`R`, `formatoRotulo` `ET`/`EV`, `layoutImpressao`, `imprimeRemetente`), `SolicitacaoDace` (`tipoDace` `T`/`R`/`C`).

Regras de mapeamento:

- Faça a conversão de/para o formato JSON da API em **mapeadores** separados (ex.: `Mapeadores\PrePostagemMapeador`), para não misturar regra de domínio com o formato da API.
- **Não envie** os campos reservados a credenciais internas dos Correios (`idCorreios`, `precoServico`, `precoPrePostagem`, `numeroCartaoPostagem`, `idAtendimento` — arquivo 06).
- O campo `destinatario.endereco.regiao` é obrigatório no schema, mas descrito como internacional; nos testes nacionais foi enviado com a UF e a criação funcionou. Envie a UF nesse campo em envios nacionais.
- Atenção aos nomes parecidos entre endpoints: o rótulo usa `idsPrePostagem`/`codigosObjeto`; a DACE e o cancelamento assíncrono usam `idsPrePostagens`/`codigosObjetos`.
- Remova ids e códigos vazios das listas antes de enviar.

Valores monetários: use `int` em centavos internamente, ou `string` decimal. **Nunca `float`** para dinheiro. Converta nos mapeadores para o formato de cada API (ver 2.4, item 11): ao **ler** preços, aceite vírgula decimal e separador de milhar; ao **enviar** para a Pré-postagem, use ponto e 2 casas.

### 4.5 Respostas

- Todos os métodos retornam **objetos de resposta tipados**, como `ResultadoPreco`, `ResultadoPrazo`, `EnderecoCep`, `ServicoContrato`, `ServicoAdicionalDisponivel` (com `obrigatorio(): bool` a partir de `sgIndicador`), `Rastreamento` com uma lista de `EventoRastreamento`, `PrePostagemCriada`, `ProcessamentoAssincrono`, `ReciboRotulo`, `ArquivoRotulo`, `ResultadoCancelamento`, `DadosPostagem` e `Dace`. Não retorne arrays soltos.
- Cada resposta expõe `respostaBruta(): array` com o JSON original, para depuração.
- Datas viram `DateTimeImmutable` com o fuso correto (`America/Sao_Paulo` ou o offset retornado pela API). Trate cada formato de data conforme o campo (2.4, item 12).
- **Status da pré-postagem:** enum `StatusPrePostagem` com os valores `1` PREATENDIDO, `2` PREPOSTADO, `3` POSTADO, `4` EXPIRADO, `5` CANCELADO, `6` ESTORNADO, `7` PENDENTE. `PrePostagemCriada` expõe `podeImprimirRotulo(): bool` (verdadeiro só com status `2`).
- **Processamento assíncrono:** enum `SituacaoProcessamento` (`PROCESSADO`, `FALHA`, `NAO_LOCALIZADO`). `ProcessamentoAssincrono` expõe a situação, as pré-postagens criadas e as mensagens de falha.
- **Arquivos (rótulo e DACE):** `ArquivoRotulo` expõe `nome`, `conteudoBase64()` e `conteudoBinario()`, e valida que o conteúdo decodificado começa com `%PDF`. Na DACE tipo `T` o conteúdo é texto para impressora térmica — não trate como PDF.
- **Lotes (Preço e Prazo):** o HTTP **206** significa que parte dos itens falhou. Retorne o resultado item a item, cada um com sucesso ou erro, correlacionados por `nuRequisicao`.
- **Paginação (Contrato):** ofereça um método que percorre todas as páginas (`page` começa em 0) e trate `itens` de forma defensiva (lista ou objeto único).
- Campos que a documentação marca como não confirmados (por exemplo, valores de `resultadoCancelamento`, formato de datas de `GET .../postada`) ficam como `string` no objeto de resposta, sem enum.

### 4.6 Erros e exceções

Crie esta hierarquia em `MarceloFecchio\Correios\Excecoes`:

```
CorreiosException (interface, ou classe base abstrata)
├── ErroValidacao           // dados de entrada inválidos (antes de chamar a API)
├── ErroConfiguracao        // configuração ausente ou inválida
├── ErroTemplate            // template ZPL inválido ou placeholder sem valor
├── ErroConexao             // falha de rede/timeout (envolve a ClientExceptionInterface do PSR-18)
├── ErroAutenticacao        // 401/403, token inválido/expirado ou sem permissão para a API
├── ErroLimiteRequisicoes   // 429 (API Token: 3 requisições por segundo)
└── ErroApi                 // demais 4xx/5xx (inclui 400 e 422 de regra de negócio)
       ├── statusHttp
       ├── mensagens        // array de mensagens
       ├── codigoErro       // ex.: "PPN-288", "ERP-049", quando presente no início da mensagem
       ├── causa
       └── respostaBruta
```

- O tratamento de erros lê **os dois formatos** de corpo: `msgs` (lista) e `mensagem` (texto). Se o corpo não for JSON (por exemplo, a página HTML "HTTP Status 500"), guarde o texto bruto e gere uma mensagem genérica.
- Extraia `codigoErro` do prefixo da mensagem no padrão `LETRAS-NÚMEROS:` (ex.: `PPN-288: ...`, `ERP-049: ...`).
- Para o erro **PPN-288** (rótulo de pré-postagem pendente), lance uma subclasse `ErroPrePostagemPendente`, com mensagem explicando que é preciso aguardar o status `2` e **solicitar o rótulo de novo** (o recibo atual fica inválido).
- As mensagens de exceção devem estar em português e ser úteis (diga o que falhou e o que fazer). Em 401 no módulo Token em homologação, a mensagem lembra que o usuário deve ser o CNPJ.
- **Nunca** coloque o token, a senha ou o código de acesso em mensagens de exceção ou logs.
- Aceite um `Psr\Log\LoggerInterface` opcional para registrar requisições e respostas, mascarando dados sensíveis (token, Basic Auth, documentos) e **omitindo o conteúdo Base64** de rótulos e DACE.

### 4.7 Rótulo oficial e etiquetas em ZPL

O componente oferece **dois caminhos** para a etiqueta de postagem. Documente os dois no README e deixe a escolha para a aplicação.

**A) Rótulo oficial dos Correios (PDF) — módulo `Rotulo`**

- `solicitar(token, SolicitacaoRotulo)` → `ReciboRotulo` (`idRecibo`);
- `baixar(token, idRecibo)` → `ArquivoRotulo`.
- **O componente não faz espera nem repetição automática** (é stateless e não deve bloquear a aplicação). A aplicação decide quando chamar de novo. Documente no README o roteiro: confirmar `statusAtual = 2` → solicitar → baixar; se o download falhar com `ErroPrePostagemPendente`, aguardar e **solicitar de novo**.
- O PDF vem em página A4 no layout `PADRAO` e pode conter várias etiquetas. Para impressora térmica, a solicitação aceita `layoutImpressao` `LINEAR_100_150` ou `LINEAR_100_80` (resultado ainda não testado — arquivo 09).
- O rótulo só pode ser emitido com as mesmas credenciais que criaram a pré-postagem.

**B) Etiquetas em ZPL geradas pelo componente — `Etiquetas\GeradorEtiquetas`**

Gere **três documentos** em ZPL, cada um a partir de um template:

1. **Etiqueta de postagem 10x15 cm** — siga `correios-etiquetas-zpl.md`, que documenta o layout, os comandos e os dados a partir de um sistema antigo que já imprimia essa etiqueta:
   - use o **template base** da seção 8.2 do guia como ponto de partida (800 × 1239 pontos a 203 dpi) e a lista de marcadores da seção 8.3;
   - código do objeto legível e em **Code 128** (o código vem do `codigoObjeto` da pré-postagem — a aplicação não gera códigos); CEP de destino em Code 128;
   - **DataMatrix** com a cadeia de **164 caracteres** descrita na seção 4 do guia, montada em uma classe própria (`Etiquetas\DataMatrixCorreios`), com teste unitário que confere cada campo, o dígito validador do CEP e o tamanho total. Os campos que o sistema antigo deixava fixos (`ServicosAdicionais`, `ValorDeclarado`, telefone) **continuam com os valores antigos e parametrizáveis**; não invente outra codificação. Se o usuário fornecer o manual oficial de etiquetas dos Correios, ele prevalece;
   - **logotipos** como arquivos parciais com o comando `^GFA` (seção 5 do guia): extraia os símbolos de SEDEX, PAC e Carta dos templates antigos. No canto superior esquerdo vai o **logo dos Correios** (parcial `logo_correios`), no lugar do logo "LOJA" do sistema antigo: use o parcial pronto `doc/recursos/parciais-zpl/logo_correios.zpl`, gerado a partir da imagem fornecida pelo usuário (guia, seções 5.4 e 5.5). Os símbolos de SEDEX, PAC e Carta também estão prontos nessa pasta. **Não desenhe nem recrie o logo**; para outro tamanho ou resolução, converta um arquivo fornecido pelo usuário com `doc/recursos/converter-imagem-para-gfa.php`. Ofereça um utilitário para converter PNG em `^GFA` (`Etiquetas\ImagemZpl`), usando GD só nessa conversão (`ext-gd` em `suggest`);
   - o logo do serviço é escolhido pelo **código do serviço**, por um mapa vindo da configuração;
   - inclua peso, volume, siglas dos adicionais e, em homologação, a marca "HOMOLOGACAO - IMPRESSAO DE TESTE";
   - corrija os problemas do código antigo listados na seção 7 do guia (escape de `_`, `^`, `~`; estado estático; corte por bytes; textos sem quebra; template por nome de serviço). **Não copie as classes antigas** (`CreateZplLabel`, `QrCodeLabel`) nem o formato de marcador `[NOME]`.
2. **DANFE simplificada (etiqueta)** — siga `correios-danfe-simplificada-zpl.md`:
   - use o **template base** e o parcial do código de envio da seção 7.2 do guia, com os marcadores da seção 7.3;
   - título "DANFe Simplificado - Etiqueta"; tipo de operação (entrada/saída), número, série e data de emissão vindos da nota;
   - chave de acesso em **Code 128 subconjunto C** (prefixo `>;`, módulo 2 em 203 dpi) e também em texto, em grupos de 4 dígitos; valide os 44 dígitos e o dígito verificador (guia, seção 4);
   - protocolo de autorização com data e hora;
   - emitente (nome, CNPJ, IE, UF) e destinatário (nome, CPF/CNPJ, IE se houver, UF, endereço);
   - bloco **opcional** com o código de rastreamento dos Correios em Code 128 (parcial incluído só quando houver código);
   - **não liste os itens nem imprima valores** (nem o valor total) — decisão do usuário; há apenas um campo livre e opcional de observações;
   - só gere a etiqueta para nota **autorizada**; corrija os problemas do código antigo listados na seção 6 do guia;
   - os requisitos legais (Nota Técnica 2020.004) estão resumidos na seção 5 do guia a partir de fonte secundária: **peça a Nota Técnica ao usuário ou confirme os pontos em aberto** (posição da chave, contingência EPEC) antes de dar a etiqueta como final.
   - **Entrada de dados com duas opções:**
     - `DadosDanfe::deXml(string $xml)`: lê o **XML autorizado da NF-e** (`nfeProc`) com `DOMDocument`/`SimpleXML`, sem dependências pesadas. Lança `ErroValidacao` se o XML não estiver autorizado ou se faltarem tags;
     - construção manual do DTO `DadosDanfe` pela aplicação.
   - `DadosDanfe` expõe a chave de 44 dígitos e o número da nota, para a aplicação reutilizar em `NovaPrePostagem` (`chaveNFe`, `numeroNotaFiscal`) ou em `dce()->atualizarDocumentoFiscal()`.
3. **Declaração de conteúdo**, para envios sem nota fiscal:
   - remetente, destinatário, lista de `Item` (descrição, quantidade, valor), totais (quantidade, valor e peso), texto legal da declaração, campos de data e assinatura;
   - se os itens não couberem em uma etiqueta, gerar **várias etiquetas** com numeração "1/2", "2/2".
   - Alternativa oficial: criar a pré-postagem com `emiteDCe: "S"` e imprimir a **DACE** pelo módulo `Dce` (arquivo 14). No tipo `T`, os Correios devolvem texto pronto para impressora térmica. Explique no README quando usar cada opção.

**Arquitetura dos templates:**

- Os templates são **arquivos `.zpl` de texto puro**, em `resources/templates/`, com placeholders no formato `{{nome_variavel}}` (valor escapado) e `{{{nome_variavel}}}` (valor cru, para códigos de barras e DataMatrix — ver 4.7.9).
- Para blocos repetidos (como as linhas de itens), use um template parcial (ex.: `item_declaracao.zpl`) que o renderizador repete e concatena. Não crie uma linguagem de template própria complexa.
- `RenderizadorZpl`:
  - substitui os placeholders e **escapa os valores** nos campos com `^FH`: `_` → `_5F`, `^` → `_5E`, `~` → `_7E`, `\` → `_5C` (guia, seção 8.4). Campos de código de barras e DataMatrix não são escapados: são validados;
  - trata **acentuação**: use `^CI28` (UTF-8) ou converta para uma codificação compatível, e documente qual foi escolhida;
  - **trunca e quebra linhas** de textos longos conforme a largura do campo, sem cortar a etiqueta;
  - lança `ErroTemplate` se sobrar algum placeholder sem valor.
- **Resolução:** 203 dpi (8 dpmm) é o padrão, com **300 dpi (12 dpmm) opcional** pela configuração ou por parâmetro. Escreva as coordenadas dos templates em uma unidade base e converta (escala de 1,5×) para a resolução escolhida, ou mantenha um conjunto de templates por resolução. Escolha uma das duas opções e justifique no README. As imagens `^GFA` não escalam sozinhas: gere parciais de logo próprios para 300 dpi (guia, seção 8.4).
- Parciais são incluídos com `{{>nome}}`; um parcial não configurado some do resultado sem erro (guia, seção 8.1).
- A aplicação pode **usar seus próprios templates**, informando outro diretório ou caminho de arquivo, desde que use os mesmos placeholders. Documente a lista completa de placeholders de cada template no README.
- Saída: **somente ZPL** (string). O README deve indicar o visualizador online Labelary (labelary.com) para conferir o resultado. Não é preciso gerar PDF ou PNG.
- A especificação dos métodos está nas subseções 4.7.1 a 4.7.10, a seguir.

#### 4.7.1 Classes e responsabilidades (namespace `MarceloFecchio\Correios\Etiquetas`)

Nenhuma destas classes chama a API dos Correios nem recebe token: elas só transformam dados em texto ZPL.

| Classe | Responsabilidade |
|---|---|
| `GeradorEtiquetas` | Fachada devolvida por `$correios->etiquetas()`. Tem um método público por tipo de etiqueta. |
| `ConfiguracaoEtiquetas` (readonly) | Opções de geração (ver 4.7.2). Faz parte de `Configuracao` (campo `etiquetas`), com valores padrão. |
| `RenderizadorZpl` | Carrega o template, inclui os parciais, substitui os marcadores, escapa, corta e valida o resultado. |
| `DataMatrixCorreios` (readonly) | Monta e valida a cadeia de 164 caracteres do DataMatrix. |
| `DadosEtiquetaPostagem` (readonly) | Dados de entrada da etiqueta de postagem. |
| `DadosDanfe` (readonly) | Dados de entrada do DANFE simplificado, com `deXml()`. |
| `DadosDeclaracaoConteudo` (readonly) | Dados de entrada da declaração de conteúdo. |
| `ImagemZpl` | Converte PNG/JPG/GIF em parcial `^GFA` (formato `:Z64:`). Única classe que usa GD. |
| `EtiquetaZpl` (readonly) | Resultado: texto ZPL + metadados (ver 4.7.4). |

#### 4.7.2 `ConfiguracaoEtiquetas`

| Campo | Tipo | Padrão | Descrição |
|---|---|---|---|
| `resolucao` | `Resolucao` | `Dpi203` | `Dpi203` (8 pontos/mm) ou `Dpi300` (12 pontos/mm) |
| `diretorioTemplates` | `?string` | `null` | Diretório com templates próprios da aplicação; `null` usa `resources/templates/` do pacote. Arquivo ausente no diretório da aplicação cai no do pacote. |
| `logoCorreios` | `bool` | `true` | Inclui o parcial `logo_correios` na etiqueta de postagem |
| `logosPorServico` | `array<string, string>` | `[]` | Código do serviço → nome do parcial. Ex.: `['03220' => 'logo_servico_sedex', '03298' => 'logo_servico_pac']`. Aceita também os apelidos de `Configuracao::servicos`. Serviço sem entrada → etiqueta sem logo de serviço. |
| `nomesServicos` | `array<string, string>` | `[]` | Código do serviço → nome impresso (ex.: `'03220' => 'SEDEX'`). Sem entrada, imprime o nome informado nos dados ou o próprio código. |
| `marcaHomologacao` | `?bool` | `null` | `null` = automático (imprime a marca quando `Configuracao::ambiente` é homologação) |
| `dataMatrixServicosAdicionais` | `string` | `'250000000000'` | Campo fixo de 12 caracteres do DataMatrix (valor do sistema antigo) |

Os parciais de logo que acompanham o pacote ficam em `resources/templates/parciais/` (203 dpi) e `resources/templates/300dpi/parciais/`. Copie-os de `doc/recursos/parciais-zpl/` (guia `correios-etiquetas-zpl.md`, seção 5.4). Para 300 dpi só existe o logo dos Correios; os símbolos dos serviços em 300 dpi devem ser gerados com `ImagemZpl` a partir dos PNG de `doc/recursos/logos-png/` ampliados em 1,5×.

#### 4.7.3 Métodos públicos de `GeradorEtiquetas`

```php
final class GeradorEtiquetas
{
    /** Etiqueta de postagem 10x15 cm. */
    public function postagem(
        DadosEtiquetaPostagem $dados,
        ?Resolucao $resolucao = null,          // null = a da configuração
    ): EtiquetaZpl;

    /** Atalho: monta DadosEtiquetaPostagem a partir do que a aplicação enviou e do que a API devolveu. */
    public function postagemDePrePostagem(
        NovaPrePostagem $enviada,
        PrePostagemCriada $criada,
        ?string $observacoes = null,
        string $volume = '1/1',
        ?Resolucao $resolucao = null,
    ): EtiquetaZpl;

    /** DANFE Simplificado - Etiqueta. $codigoEnvio é o código de rastreamento dos Correios (opcional). */
    public function danfeSimplificada(
        DadosDanfe $dados,
        ?string $codigoEnvio = null,
        ?string $observacoes = null,
        ?Resolucao $resolucao = null,
    ): EtiquetaZpl;

    /** Declaração de conteúdo. Pode gerar mais de uma etiqueta ("1/2", "2/2"). */
    public function declaracaoConteudo(
        DadosDeclaracaoConteudo $dados,
        ?Resolucao $resolucao = null,
    ): EtiquetaZpl;

    /** Junta várias etiquetas em um único envio à impressora, na ordem recebida. */
    public function juntar(EtiquetaZpl|string ...$etiquetas): EtiquetaZpl;
}
```

Regras comuns a todos os métodos:

- **Sem efeitos colaterais:** não gravam arquivo, não imprimem, não chamam a rede. Devolvem o resultado.
- **Validam antes de renderizar** e lançam `ErroValidacao` com o nome do campo; problemas de template lançam `ErroTemplate`.
- **Mesma entrada → mesma saída**, byte a byte (necessário para os testes de comparação). Nada de data/hora atual dentro do ZPL.
- Saída em **UTF-8 sem BOM**, começando em `^XA` e terminando em `^XZ` seguido de uma quebra de linha.

#### 4.7.4 `EtiquetaZpl` (resultado)

| Membro | Descrição |
|---|---|
| `zpl` (string) | O texto ZPL completo |
| `quantidade` (int) | Número de blocos `^XA … ^XZ` |
| `resolucao` (`Resolucao`) | Resolução usada |
| `__toString()` | Devolve `zpl`, para uso direto como string |
| `salvar(string $caminho): void` | Grava o ZPL em arquivo (conveniência; lança `ErroConfiguracao` se não conseguir gravar) |

O envio à impressora **não** faz parte do componente. O README mostra como a aplicação envia o texto (socket na porta 9100, fila de impressão, Zebra Browser Print/QZ Tray).

#### 4.7.5 `postagem()` — passo a passo

Entrada `DadosEtiquetaPostagem`:

| Campo | Obrig. | Observação |
|---|:-:|---|
| `codigoObjeto` (`CodigoRastreamento`) | ✔ | Vem de `PrePostagemCriada`; nunca é gerado pela aplicação |
| `codigoServico` (string) | ✔ | Código ou apelido configurado |
| `remetente`, `destinatario` (`Pessoa`) | ✔ | Na logística reversa os papéis já chegam invertidos (arquivo 06) |
| `cartaoPostagem`, `contrato` | | Padrão: os de `Configuracao` |
| `pesoGramas` (int) | ✔ | |
| `volume` (string) | | Padrão `1/1` |
| `numeroNotaFiscal`, `numeroPedido` | | Linha `NF: …` ou `Pedido: …`; vazia se nenhum |
| `siglasAdicionais` (list<string>) | | Ex.: `['VD', 'AR']` |
| `observacoes` (?string) | | Até 2 linhas |
| `nomeServico` (?string) | | Sobrepõe `ConfiguracaoEtiquetas::nomesServicos` |

Algoritmo:

1. Validar os dados (CEPs com 8 dígitos, código do objeto, serviço resolvido para código de até 5 dígitos).
2. Montar o DataMatrix com `DataMatrixCorreios` (guia `correios-etiquetas-zpl.md`, seções 4 e 8.5) e conferir os 164 caracteres.
3. Escolher os parciais: `logo_correios` (se habilitado), o logo do serviço pelo mapa, `marca_homologacao` (se aplicável).
4. Montar os valores dos marcadores da seção 8.3 do guia: linha de endereço só com as partes preenchidas, CEP com e sem hífen, `Cidade/UF`, linha da nota fiscal.
5. Cortar cada valor no limite da tabela 8.3 (por caracteres) e **depois** escapar.
6. Renderizar `postagem.zpl` na resolução pedida.
7. Devolver `EtiquetaZpl` com `quantidade = 1`.

`postagemDePrePostagem()` só monta `DadosEtiquetaPostagem` (remetente, destinatário, serviço, peso, nota fiscal e adicionais vêm de `NovaPrePostagem`; código do objeto vem de `PrePostagemCriada`) e chama `postagem()`. Se `PrePostagemCriada` não trouxer código do objeto, lança `ErroValidacao`. As siglas dos adicionais vêm de um mapa código → sigla **configurável** (`001` → `AR`; valor declarado → `VD`); código sem sigla conhecida é omitido.

#### 4.7.6 `danfeSimplificada()` — passo a passo

Siga `correios-danfe-simplificada-zpl.md` (template na seção 7.2, marcadores na 7.3, regras na 7.4, leitura do XML na 7.5).

1. `DadosDanfe` já chega validado: nota autorizada, chave com 44 dígitos e dígito verificador correto. `DadosDanfe::deXml()` faz essas verificações ao ler o XML; o construtor manual faz as mesmas, exceto a do protocolo, que passa a ser obrigatório como argumento.
2. Formatar: número com pontos, CNPJ/CPF, CEP, datas (`dd/mm/aaaa` e `dd/mm/aaaa hh:mm:ss`), chave em grupos de 4.
3. Se `$codigoEnvio` foi informado, validar o padrão e incluir o parcial `danfe_codigo_envio`; se não, omitir o parcial.
4. **Não incluir itens nem valores.** `$observacoes` é texto livre opcional (até 3 linhas).
5. Cortar, escapar e renderizar `danfe_simplificada.zpl`. A chave vai para o código de barras com o prefixo `>;` e o código de envio com `>:`, ambos sem escape.
6. Devolver `EtiquetaZpl` com `quantidade = 1`.

#### 4.7.7 `declaracaoConteudo()` — passo a passo

Não há exemplo do sistema antigo nem guia para esta etiqueta: o layout é desenhado pelo agente, com o mesmo renderizador e as mesmas regras de escape, corte e resolução.

1. Entrada `DadosDeclaracaoConteudo`: remetente e destinatário (`Pessoa`), lista de `Item` (descrição, quantidade, valor unitário), peso total opcional, local e data opcionais (informados pela aplicação).
2. Conteúdo: título "DECLARAÇÃO DE CONTEÚDO", blocos de remetente e destinatário (nome, CPF/CNPJ, endereço completo), tabela de itens (nº, conteúdo, quantidade, valor), totais (quantidade, valor e peso), texto legal da declaração, campos de local, data e assinatura do declarante.
3. Itens em parcial repetido (`item_declaracao.zpl`), uma linha por item, com a posição vertical calculada pelo gerador.
4. Se os itens não couberem, gerar **várias etiquetas** numeradas ("1/2", "2/2"), repetindo cabeçalho e partes; totais, texto legal e assinatura só na última.
5. Valores em reais com vírgula decimal; totais calculados com inteiros (centavos).
6. **Antes de implementar, peça ao usuário o texto legal exato e confirme o layout** — a documentação do projeto não traz esse modelo. Se o usuário preferir, esta etiqueta pode ficar para depois: a alternativa oficial é a DCe com impressão da DACE (arquivo 14).

#### 4.7.8 `juntar()`

Concatena os blocos na ordem recebida, sem alterar o conteúdo. Exige que todas as etiquetas tenham a mesma resolução (`ErroValidacao` se diferirem). Strings puras são aceitas se começarem com `^XA` e terminarem com `^XZ`.

#### 4.7.9 `RenderizadorZpl` — contrato

```php
final class RenderizadorZpl
{
    /**
     * @param array<string, string>  $valores   marcador => valor ainda sem escape
     * @param array<string, ?string> $parciais  marcador de parcial => nome do arquivo parcial (null = omitir)
     */
    public function renderizar(string $template, array $valores, array $parciais = [], ?Resolucao $resolucao = null): string;
}
```

Ordem das operações: (1) carregar o template da resolução; (2) trocar cada `{{>parcial}}` pelo conteúdo do arquivo, ou por nada; (3) trocar cada `{{marcador}}` pelo valor — **em uma única passada**, para que um valor que contenha `{{...}}` não seja reinterpretado; (4) remover as linhas de comentário `^FX`; (5) lançar `ErroTemplate` se sobrar `{{`; (6) normalizar as quebras de linha para `\n`.

O escape depende do campo: valores de marcadores que ficam em campos com `^FH` são escapados; os de códigos de barras e do DataMatrix não. Para o renderizador saber a diferença sem analisar o ZPL, use uma convenção explícita no template: `{{nome}}` (escapado) e `{{{nome}}}` (cru, só para dados já validados). Ajuste os templates dos guias para essa convenção nos marcadores `datamatrix`, `codigo_objeto`, `destinatario_cep`, `chave_acesso` e `codigo_envio`.

#### 4.7.10 Exemplo de uso esperado

```php
// 1. Etiqueta de postagem logo após criar a pré-postagem
$criada = $correios->prePostagem()->criar(token: $token, prePostagem: $nova);
$etiqueta = $correios->etiquetas()->postagemDePrePostagem($nova, $criada, observacoes: 'Pedido 12345');

// 2. DANFE simplificado a partir do XML autorizado, com o código de rastreamento
$danfe = $correios->etiquetas()->danfeSimplificada(
    DadosDanfe::deXml($xmlNfe),
    codigoEnvio: (string) $criada->codigoObjeto,
);

// 3. Um único envio à impressora
$tudo = $correios->etiquetas()->juntar($etiqueta, $danfe);
$tudo->salvar('/tmp/pedido-12345.zpl');   // ou: enviar (string) $tudo para a impressora
```

### 4.8 Utilitários

- `CodigoRastreamento`: objeto de valor que valida o formato `^[A-Z]{2}[0-9]{9}[A-Z]{2}$` (remove espaços e converte para maiúsculas — a etiqueta imprime `AB 043 180 865 BR`) e o **dígito verificador** (módulo 11, pesos 8, 6, 4, 2, 3, 5, 9, 7; resto 0 vira 5, resto 1 vira 0, nos demais casos o dígito é 11 − resto). Essa regra **não está na documentação do projeto**: confira-a com os códigos reais que aparecem nos arquivos 09 e 10 (ex.: `AB043180865BR`, `AK039775832BR`, `DG049186226BR`) em um teste unitário. Se algum não bater, **pare e pergunte**. Ofereça também validação só de formato, sem dígito.
- Normalização de CEP, CPF e CNPJ (remove máscara), com validação. CEP é sempre string de 8 dígitos.
- `ChaveDocumentoFiscal`: valida os 44 dígitos e o dígito verificador da chave de NF-e/DCe.

### 4.9 Integração opcional com Laravel

- `MarceloFecchio\Correios\Laravel\CorreiosServiceProvider`, com auto-discovery em `composer.json` (`extra.laravel.providers`).
- Arquivo publicável `config/correios.php`, lendo as variáveis de ambiente `CORREIOS_AMBIENTE`, `CORREIOS_USUARIO`, `CORREIOS_CODIGO_ACESSO`, `CORREIOS_CNPJ`, `CORREIOS_CARTAO_POSTAGEM`, `CORREIOS_CONTRATO`, `CORREIOS_DR` e o mapa de serviços. Comente no arquivo que, em homologação, `CORREIOS_USUARIO` é o CNPJ.
- Registra `Correios` como singleton no container. Facade `Correios` opcional.
- `illuminate/support` vai em **`suggest`/`require-dev`**, nunca em `require`. O núcleo não pode depender do Laravel.
- Testes do provider com `orchestra/testbench`.

---

## 5. Estrutura de diretórios esperada

```
correios-api/
├── .github/workflows/ci.yml        # PHP 8.4: composer validate, cs-fixer --dry-run, phpstan, pest
├── config/correios.php             # config publicável (Laravel)
├── doc/                            # documentação de referência (já existe; fora do pacote: export-ignore)
│   └── entrega.md                  # relatório de entrega, escrito ao final (seção 11)
├── exemplos/
│   ├── 01-gerar-token-e-guardar-na-sessao.php
│   ├── 02-listar-servicos-do-cartao.php
│   ├── 03-consultar-cep.php
│   ├── 04-calcular-preco-e-prazo.php
│   ├── 05-criar-pre-postagem.php
│   ├── 06-criar-pre-postagem-reversa.php
│   ├── 07-pre-postagem-assincrona.php
│   ├── 08-rotulo-oficial-pdf.php
│   ├── 09-cancelar-pre-postagem.php
│   ├── 10-dce-e-dace.php
│   ├── 11-rastrear-objetos.php
│   ├── 12-etiqueta-postagem-zpl.php
│   ├── 13-danfe-simplificada-a-partir-do-xml.php
│   ├── 14-declaracao-de-conteudo.php
│   ├── 15-templates-personalizados.php
│   └── laravel/                    # exemplos de uso no Laravel (controller/service)
├── resources/templates/
│   ├── postagem.zpl                # etiqueta de postagem, 203 dpi
│   ├── danfe_simplificada.zpl
│   ├── declaracao_conteudo.zpl
│   ├── parciais/                   # logo_correios, logo_servico_sedex|pac|carta, marca_homologacao,
│   │                               # danfe_codigo_envio, item_declaracao
│   └── 300dpi/                     # mesmos arquivos para 300 dpi (ou escala em código — ver 4.7)
├── src/
│   ├── Correios.php
│   ├── Configuracao.php
│   ├── Enums/                      # Ambiente, Uf, FormatoObjeto, Resolucao, StatusPrePostagem, SituacaoProcessamento, TipoRotulo, TipoDace, ...
│   ├── Dto/                        # Pessoa, Endereco, Item, Volume, ServicoAdicional, NovaPrePostagem, ...
│   ├── Respostas/
│   ├── Servicos/                   # Token, Contrato, Cep, Preco, Prazo, PrePostagem, Rotulo, Dce, Rastro
│   ├── Http/                       # cliente interno sobre PSR-18, montagem de requisições, tratamento de erros
│   ├── Mapeadores/
│   ├── Etiquetas/                  # GeradorEtiquetas, ConfiguracaoEtiquetas, RenderizadorZpl, DataMatrixCorreios, ImagemZpl,
│   │                               # DadosEtiquetaPostagem, DadosDanfe, DadosDeclaracaoConteudo, EtiquetaZpl
│   ├── Utilitarios/                # CodigoRastreamento, Documento, Cep, ChaveDocumentoFiscal
│   ├── Excecoes/
│   └── Laravel/
├── tests/
│   ├── Unit/
│   ├── Feature/                    # serviços com HTTP simulado
│   ├── Integracao/                 # homologação real, desativado por padrão
│   ├── Laravel/
│   └── Fixtures/                   # JSONs de resposta, XMLs de NF-e, ZPL esperados (snapshots)
├── CHANGELOG.md
├── LICENSE
├── README.md
├── composer.json
├── phpstan.neon
├── .php-cs-fixer.dist.php
└── phpunit.xml / Pest.php
```

---

## 6. Testes

- **Pest**, organizado por `Unit`, `Feature`, `Integracao` e `Laravel`.
- **HTTP simulado:** use `php-http/mock-client` (ou o `MockHandler` do Guzzle) com **fixtures JSON**. Monte as fixtures a partir da documentação, nesta ordem de preferência: (1) respostas **reais** transcritas nos trechos "✔ Observado em homologação"; (2) exemplos e schemas da especificação nos arquivos. Substitua dados reais (CNPJ, contrato, cartão, token) por valores fictícios.
- Confira nos testes de Feature que cada requisição sai com o **método, a URL do ambiente correto (host + prefixo da API), os cabeçalhos** (`Authorization: Bearer`, `Content-Type`, `Accept`) **e o corpo JSON esperado**. A coleção do Postman é a referência dos corpos.
- **Cenários obrigatórios vindos dos testes reais:**
  - Token: resposta HTTP 201; HTTP 429 → `ErroLimiteRequisicoes`; `ambiente` da resposta diferente do configurado → `ErroConfiguracao`;
  - erros nos dois formatos: `{"msgs": [...], "causa": ...}` e `{"mensagem": "PPN-288: ..."}`; HTTP 422 de regra de negócio; corpo HTML em HTTP 500;
  - rótulo: `{"idRecibo": ...}` → download com `{"dados", "nome"}`; `dados` que não começa com `%PDF` → erro; PPN-288 → `ErroPrePostagemPendente`; ids vazios removidos do corpo;
  - assíncrona: `PROCESSADO` com `statusAtual` 7 e depois 2; `FALHA` com mensagens; `NAO_LOCALIZADO`;
  - preço: leitura de `"19,92"`; ERP-049 quando falta adicional obrigatório; lote com HTTP 206 e itens mistos; adicionais com `sgIndicador` `O` e `P`;
  - pré-postagem: corpo comum e corpo de logística reversa; valores com ponto e 2 casas; números enviados como string; campos internos nunca enviados;
  - cancelamento: `DELETE` por id e por objeto (sem corpo) e `DELETE` assíncrono **com corpo JSON**;
  - contrato: paginação em várias páginas; `itens` como lista e como objeto único.
- **Ambientes:** teste que homologação e produção apontam para os hosts corretos e que `urlBase` sobrescreve os dois.
- **DTOs:** casos válidos e inválidos (CPF/CNPJ, CEP, UF, tamanhos máximos, dimensões por formato, valores).
- **Token:** `estaValido()` com margem, ida e volta com `paraArray()`/`deArray()`, aceitação de `Token|string`, leitura de `dr`, `contrato` e `apis`.
- **Métodos de etiqueta (4.7.3):** `postagem()` e `postagemDePrePostagem()` geram o mesmo ZPL para os mesmos dados; `danfeSimplificada()` com e sem código de envio; ZPL do DANFE sem itens nem valores; `juntar()` preserva a ordem e recusa resoluções diferentes; `EtiquetaZpl::quantidade` correto; saída sem BOM, começando em `^XA` e terminando em `^XZ`; mesma entrada → mesma saída.
- **ZPL:** testes de **snapshot** comparando com arquivos `.zpl` esperados em `tests/Fixtures`; acentuação; escape de `^` e `~`; truncamento; placeholder sem valor; paginação da declaração de conteúdo; 203 e 300 dpi.
- **DANFE:** parse de pelo menos dois XMLs de exemplo (PJ→PF e PJ→PJ) e de um XML inválido ou não autorizado; dígito verificador da chave; etiqueta sem código de envio. Use a tabela de testes da seção 8 de `correios-danfe-simplificada-zpl.md`. A pasta `doc/legado/danfe-simplificada-zpl/` traz um XML autorizado com dados **fictícios** — `nfe-venda-pf.xml` (venda para pessoa física, o caso típico) — e o guia traz uma função de leitura já testada com ele e com uma venda para pessoa jurídica (seção 7.5). Use-o como base das fixtures e monte a de pessoa jurídica (destinatário com CNPJ e IE) com dados fictícios; nunca versione XML de notas reais.
- **DataMatrix e código de rastreamento:** testes campo a campo e do dígito verificador (com os códigos reais da documentação). Use a tabela de testes da seção 9 de `correios-etiquetas-zpl.md`, incluindo o exemplo completo de 164 caracteres da seção 4.
- **Logotipos:** converter um PNG em `^GFA` e conferir os bytes e o CRC do formato `:Z64:`; reconverter `doc/recursos/logos-png/logo_servico_sedex_224x224.png` deve reproduzir os bytes do template antigo.
- **Integração real (opcional):** grupo `integracao`, **pulado por padrão**, que só roda se existirem as variáveis `CORREIOS_HOM_USUARIO` (o CNPJ), `CORREIOS_HOM_CODIGO_ACESSO`, `CORREIOS_HOM_CNPJ`, `CORREIOS_HOM_CARTAO` e `CORREIOS_HOM_CONTRATO`. Siga a ordem das pastas da coleção do Postman. Gere **um** token por execução e reutilize-o. Não inclua nos testes de integração os endpoints que só funcionam com objeto postado (`.../postada`) nem os endpoints HTML instáveis. Nunca grave credenciais no repositório.
- Meta: **cobertura de pelo menos 90%** em `src/` (exceto `Laravel/`).

---

## 7. README.md (em português)

O README deve conter:

1. Descrição, selos (Packagist, CI, licença) e requisitos (PHP 8.4, cliente PSR-18).
2. Instalação: `composer require marcelofecchio/correios-api guzzlehttp/guzzle`.
3. Como obter as credenciais no portal CWS (usuário, código de acesso, cartão de postagem, contrato, CNPJ) e a diferença entre homologação e produção — **incluindo o aviso de que em homologação o usuário é o CNPJ** e que cada ambiente tem seu próprio código de acesso.
4. Configuração em PHP puro e no Laravel (publicar a config, variáveis do `.env`).
5. **Seção "Boas práticas com o token":**
   - O componente **não guarda o token**. Essa responsabilidade é da aplicação.
   - Recomende que a aplicação, ao gerar o token, **o grave na sessão** (`$_SESSION` ou a sessão do framework) usando `Token::paraArray()`.
   - **Antes de gerar um novo token, sempre verificar se já existe um válido** na sessão (`Token::deArray(...)->estaValido()`), e só gerar outro se não houver ou se estiver perto de expirar.
   - Explique o motivo: a API Token aceita no máximo **3 requisições por segundo** (HTTP 429) e devolve o mesmo token até os 30 minutos finais da validade.
   - Inclua um exemplo completo dessa lógica, como uma função `obterToken()`.
   - Mencione como alternativa o uso de cache compartilhado (PSR-16 ou o Cache do Laravel) para processos sem sessão (filas, jobs, CLI), com trava contra renovações simultâneas.
6. **Fluxo completo de envio**, na ordem: token → serviços do cartão → CEP → preço e prazo → pré-postagem → rótulo → postagem → rastreamento. Inclua o fluxo de **logística reversa**.
7. Exemplos de uso de cada módulo: token, contrato, CEP, preço, prazo, pré-postagem (síncrona, assíncrona, reversa, cancelamento, dados da postagem), rótulo oficial, DCe e rastro.
8. **Seção "Pré-postagem assíncrona e rótulo":** explique os status, a passagem `7 → 2`, o erro PPN-288 e a necessidade de solicitar o rótulo de novo, com um exemplo de rotina de espera feita **pela aplicação** (intervalo crescente e limite de tentativas).
9. **Seção "Serviços adicionais":** como descobrir os obrigatórios (`sgIndicador`), a diferença entre cotação e pré-postagem no adicional 016 e a exigência do 001 para o AR.
10. Etiquetas: rótulo oficial (PDF) × ZPL próprio; os três tipos de ZPL, a resolução, a lista de placeholders de cada template, como usar templates próprios, como enviar o ZPL para a impressora (socket na porta 9100, compartilhamento de rede, impressão pelo navegador com Zebra Browser Print/QZ Tray) e como pré-visualizar no Labelary.
11. Tratamento de erros (hierarquia de exceções com exemplo de `try/catch`, incluindo `codigoErro`).
12. **Limitações conhecidas:** os pontos que a documentação marca como não testados ou instáveis (endpoints HTML de declaração de conteúdo e AR, `GET .../postada`, respostas de cancelamento, DACE, layouts de rótulo diferentes de `PADRAO`).
13. Como rodar os testes, inclusive os de integração com homologação.
14. Contribuição, versionamento semântico, CHANGELOG e licença.

---

## 8. Ordem de execução sugerida

1. Ler `correios-00-instrucoes-gerais.md`, depois os arquivos 01 a 14 dos módulos em escopo e os guias `correios-etiquetas-zpl.md` e `correios-danfe-simplificada-zpl.md`. **Listar e perguntar** ao usuário o que estiver ambíguo ou faltando antes de codificar.
2. Criar o esqueleto do pacote: `composer.json`, ferramentas de qualidade, CI, estrutura de pastas.
3. `Configuracao`, enums, exceções e a camada HTTP sobre PSR-18 (com o tratamento dos dois formatos de erro).
4. Módulo Token e objeto de valor `Token`.
5. DTOs e utilitários (Pessoa, Endereco, Item, Volume, ServicoAdicional, CodigoRastreamento, documentos).
6. Serviços Contrato, CEP, Preço, Prazo e Rastro, com seus testes.
7. Pré-postagem: síncrona e reversa → assíncrona → cancelamentos → dados da postagem, com seus testes.
8. Rótulo oficial e DCe, com seus testes.
9. Etiquetas ZPL, nesta ordem: `RenderizadorZpl` e `EtiquetaZpl` → `DataMatrixCorreios` → `postagem()` e `postagemDePrePostagem()` → `DadosDanfe` (com `deXml()`) e `danfeSimplificada()` → `juntar()` → `ImagemZpl` → `declaracaoConteudo()` (depois de confirmar o layout com o usuário). Testes de snapshot a cada passo.
10. Integração com Laravel.
11. README, exemplos e CHANGELOG.
12. Rodar `composer validate`, PHP-CS-Fixer, PHPStan (nível máximo, sem baseline) e Pest. Tudo precisa passar.
13. Escrever o relatório de entrega `doc/entrega.md` (seção 11).

Ao terminar cada etapa, rode os testes e a análise estática antes de seguir para a próxima.

---

## 9. Critérios de aceite

- [ ] `composer validate --strict` sem erros. O pacote instala em um projeto limpo com PHP 8.4.
- [ ] PHPStan no nível máximo sem erros e **sem baseline**.
- [ ] PHP-CS-Fixer sem pendências.
- [ ] Todos os testes Pest passam. Cobertura ≥ 90% em `src/` (exceto `Laravel/`).
- [ ] Nenhum endpoint autenticado é chamado sem o token recebido por parâmetro. Nenhum estado global, sessão ou cache dentro do componente.
- [ ] Trocar o ambiente na configuração muda o host. Nenhuma URL fixa fora do enum `Ambiente`.
- [ ] Nenhum código de serviço ou de serviço adicional fixo na lógica (só em exemplos, testes e documentação).
- [ ] Todos os endpoints da tabela 4.1 estão implementados com os caminhos e nomes de campos da documentação. Nenhum endpoint ou campo fora dela.
- [ ] Os comportamentos da tabela 2.4 estão cobertos por testes.
- [ ] O componente não faz espera, repetição automática nem *polling*.
- [ ] Os métodos de 4.7.3 existem com as assinaturas descritas, não têm efeitos colaterais e devolvem `EtiquetaZpl`.
- [ ] A etiqueta de postagem gerada com os dados de `doc/legado/etiqueta-postagem-zpl/etiqueta_exemplo.zpl` tem a **mesma cadeia de DataMatrix** da etiqueta antiga.
- [ ] O DANFE gerado a partir dos XML de exemplo traz a chave correta e não contém itens nem valores.
- [ ] Os três ZPL gerados abrem corretamente no Labelary em 203 e 300 dpi (os snapshots dos testes servem de referência).
- [ ] O núcleo funciona sem Laravel instalado.
- [ ] Os exemplos em `exemplos/` rodam (com credenciais de homologação configuradas por variáveis de ambiente).
- [ ] O README tem todas as seções da etapa 7, incluindo as boas práticas do token e as limitações conhecidas.
- [ ] O relatório de entrega `doc/entrega.md` existe, tem as cinco partes da seção 11 e os números dele vêm da última execução das ferramentas.

---

## 10. O que NÃO fazer

- Não guardar token, credenciais ou respostas em sessão, arquivo ou cache dentro do componente.
- Não usar `float` para dinheiro.
- Não tratar CEP, cartão de postagem, contrato ou código de serviço como número (perdem os zeros à esquerda).
- Não colocar `illuminate/*` nem `guzzlehttp/guzzle` em `require`.
- Não colocar credenciais reais em código, testes, fixtures ou exemplos — inclusive as que aparecem na coleção do Postman e na documentação.
- Não inventar campos ou endpoints que não estão na documentação do projeto. Na dúvida, perguntar.
- Não deduzir o usuário de homologação a partir do de produção, nem o contrário.
- Não replicar na pré-postagem os serviços adicionais exigidos na cotação de preço.
- Não reutilizar um `idRecibo` de rótulo que falhou com PPN-288.
- Não mudar a estrutura de 164 caracteres do DataMatrix nem inventar valores para os campos que o sistema antigo deixava fixos.
- Não copiar as classes do sistema antigo nem embutir no pacote o logo da loja do sistema antigo (já removido dos templates de exemplo).
- Não desenhar o logo dos Correios: ele vem do arquivo oficial fornecido pelo usuário.
- Não usar bibliotecas pesadas para ler o XML da NF-e ou gerar o ZPL quando o PHP nativo resolve.

---

## 11. Relatório de entrega

Ao final da execução, escreva o arquivo **`doc/entrega.md`**, em português e em Markdown. Ele é lido pelo dono do projeto para revisar o trabalho sem precisar refazer a análise, por isso precisa ser **fiel ao que aconteceu**, não ao que foi planejado.

Escreva o relatório **mesmo que a execução termine antes do previsto** (etapa bloqueada, pergunta sem resposta, critério impossível de atender). Nesse caso, diga em que etapa da seção 8 o trabalho parou e por quê.

O arquivo tem cinco partes, nesta ordem:

1. **Resumo do que foi implementado**
   - Um parágrafo curto com o estado geral da entrega.
   - Uma tabela com cada módulo da seção 4.1 e cada método de etiqueta de 4.7.3, com a situação (**completo**, **parcial** ou **não feito**) e as principais classes ou arquivos. Na situação "parcial", diga o que falta.
2. **Principais decisões técnicas**
   - Cada escolha que este prompt deixou em aberto, com a opção escolhida, as alternativas consideradas e o motivo em uma ou duas frases. No mínimo: resolução de 300 dpi (escala em código × templates por resolução, 4.7), acentuação (`^CI28` × conversão, 4.7), `CorreiosException` como interface ou classe abstrata (4.6), `Volume` ou `Objeto` (4.4), dinheiro em centavos `int` ou `string` decimal (4.4), e o layout e o texto legal da declaração de conteúdo (4.7.7).
   - Qualquer outra decisão que mude a API pública ou o comportamento do componente.
3. **Testes executados e resultados**
   - Os comandos rodados e o resultado da **última execução** de cada um: `composer validate --strict`, PHP-CS-Fixer, PHPStan (nível e número de erros), Pest (testes aprovados, falhos e pulados) e a cobertura de `src/`.
   - Copie os números da saída das ferramentas. Não arredonde, não estime e não informe como aprovado um teste que não foi executado.
   - Diga se os testes de integração com homologação rodaram. Se não rodaram, diga por quê (por exemplo, variáveis de ambiente ausentes).
   - Diga se os ZPL foram conferidos no Labelary e em quais resoluções. Se não foram, diga que a conferência ficou pendente.
4. **Premissas adotadas**
   - O que foi assumido sem confirmação do usuário porque a documentação era omissa ou ambígua, com o arquivo e a seção da documentação e o lugar no código onde a premissa aparece.
   - As divergências entre este prompt e a documentação registradas no `CHANGELOG.md` (seção 2.2, item 1).
5. **Limitações e critérios não atendidos**
   - A lista da seção 9 com a situação de cada item: ✔ atendido e verificado, ◐ atendido sem verificação, ✘ não atendido. Para ◐ e ✘, explique o motivo e o que falta.
   - As lacunas conhecidas da documentação (trechos "ainda não testado" e "a confirmar") que afetam o componente.
   - As perguntas feitas ao usuário que ainda não têm resposta.

Regras do relatório:

- Separe o que foi **verificado** (um comando rodou e passou) do que foi apenas **implementado**.
- Cite arquivos pelo caminho relativo à raiz do projeto (ex.: `src/Servicos/Preco.php`).
- **Não inclua** credenciais, tokens, CNPJ, cartão de postagem ou outros dados reais, nem nas saídas de comandos copiadas.
- Seja direto: frases curtas e tabelas onde couberem. Não repita o conteúdo do README.
