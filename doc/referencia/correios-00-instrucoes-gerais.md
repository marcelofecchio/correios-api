# Correios API — Instruções Gerais para Construção de Aplicações

> Documento de contexto geral para agentes de IA / LLMs que vão desenvolver aplicações integradas às APIs REST dos Correios (plataforma **Correios API / Correios Web Services – CWS**). Baseado no *Manual de Integração Correios API* — Revisão 04/2025, **V2.4** (DINEG/SUCAN), complementado por observações práticas do ambiente de homologação (marcadas como **⚠ Observação prática**). Compilado em 27/09/2026.
>
> **Leia este arquivo antes dos demais.** Os arquivos específicos de cada API (listados na seção 13, na ordem de teste) detalham endpoints, schemas e códigos de erro; este documento explica as regras que valem para todas elas.
>
> **Hierarquia das fontes:** quando o manual e a especificação OpenAPI divergirem, **prevalece a especificação** (reproduzida nos arquivos 01 a 14, mais recente). As observações práticas (⚠) prevalecem sobre ambos no ambiente em que foram verificadas.
>
> **Sequência de testes:** etapa 00 de 14 · Próxima: `correios-01-token-api.md`

---

## 0. Resumo rápido (para o agente)

1. Toda chamada às APIs exige um **token JWT** obtido na **API Token** (`POST /v1/autentica/cartaopostagem`, na maioria dos casos).
2. O token é obtido com **HTTP Basic**: `Authorization: Basic base64(usuario:codigoDeAcesso)`.
   - **Produção:** `usuario` = login do **Meu Correios**.
   - **Homologação:** `usuario` = **CNPJ** da empresa (⚠ observação prática — ver seção 4.2).
   - `codigoDeAcesso` = senha de componente / código de acesso gerado no portal CWS **do mesmo ambiente**.
3. Nas demais APIs, envie `Authorization: Bearer <token>`.
4. Reutilize o token até perto de `expiraEm` (tipicamente 24 h). A API Token aceita no máximo **3 requisições/s** (acima disso: HTTP 429).
5. Homologação usa o host `apihom.correios.com.br`; produção usa `api.correios.com.br`. Nunca misture token, credenciais ou IDs entre ambientes.
6. APIs **restritas** só funcionam se o serviço estiver cadastrado no **cartão de postagem** / contrato. Um 403 geralmente significa falta de permissão, não erro de código.
7. Antes de montar qualquer corpo JSON, consulte a seção **Schemas** da documentação da API (obrigatoriedade, tipo e tamanho de cada campo).

---

## 1. O que é o Correios API

- Catálogo de APIs **REST** sobre **HTTPS**, em arquitetura de **microsserviços** (cada API tem sua própria URL base e versão).
- Substitui o antigo Web Services SOAP/XML dos Correios.
- Independente de linguagem (Java, .NET, PHP, Python, Node, Ruby etc.); não exige instalação de software proprietário.
- A documentação é dinâmica (OpenAPI/Swagger, com botão *Try it out*) e pode mudar — sempre confira a versão publicada no catálogo antes de implementar.

### Funcionalidades cobertas

| Área | API | Visibilidade |
|---|---|---|
| Autenticação | Token | Pública (sem restrição) |
| Endereçamento | CEP (Busca CEP) | Restrita |
| Unidades de atendimento | Agência (Busca Agência) | Restrita |
| Prazo de entrega | Prazo | Restrita |
| Preço do serviço | Preço | Restrita |
| Rastreamento | Rastro (SRO) | Restrita |
| Postagem | Pré-postagem (inclui rótulos, AR, declaração de conteúdo, lotes, faixas de etiquetas) | Restrita |
| Atendimento | Pedido de Informação (Fale Conosco) | Restrita |
| Contrato | Meu Contrato (serviços do cartão) | Restrita |

---

## 2. Ambientes

| Ambiente | Portal (catálogo CWS) | Host das APIs |
|---|---|---|
| **Homologação** | `https://cwshom.correios.com.br/` | `https://apihom.correios.com.br` |
| **Produção** | `https://cws.correios.com.br/` | `https://api.correios.com.br` |

**Formação da URL de uma requisição:** `host` + `/<nome-da-api>` + `/<versão>/<recurso>`.
Exemplo: `https://apihom.correios.com.br` + `/token` + `/v1/autentica/cartaopostagem`.

Regras:

- Credenciais, códigos de acesso, tokens, IDs de pré-postagem e números de objeto de um ambiente **não valem no outro**.
- O campo `ambiente` no retorno do token (`HOMOLOGACAO` / `PRODUCAO`) confirma em qual ambiente o token foi emitido — use-o como verificação de segurança.
- Parametrize o host em configuração/variável de ambiente (ex.: `CORREIOS_BASE_URL`); nunca o deixe fixo no código.

### Tela do portal CWS (referência)

1. Nome do cliente e menu (Meu Correios, sair, configurações).
2. Menu lateral (hambúrguer): documentação e **gestão de acesso a componentes** (onde se gera o código de acesso).
3. Barra de pesquisa (lista as APIs às quais o usuário tem acesso) e botão **Credenciais** (gera token para testes no navegador).
4. Aba de cada componente: nome, versão, link `api-docs` e os verbos HTTP; ao abrir um verbo, *Try it out* permite testar.

---

## 3. Pré-requisitos

- Cadastro válido no **Meu Correios** (idCorreios).
- **Contrato ativo** com os Correios (encomendas ou mensagens) e **cartão de postagem** vinculado. Sem contrato, apenas APIs públicas ficam disponíveis.
- **Código de acesso (senha de componente)** gerado no portal CWS — é essa senha que vai no Basic Auth, **não** a senha do Meu Correios.
- APIs restritas precisam estar cadastradas no cartão de postagem/contrato. Para torná-las visíveis no catálogo, informe os dados do contrato/cartão no portal; enquanto não houver o cadastro, a API (ex.: CEP) não aparece na lista.
- O acesso também é disponível a empresas que fornecem soluções de TI no segmento de logística.

---

## 4. Autenticação e autorização

### 4.1 Fluxo geral

```
┌──────────────┐  Basic (usuario:codigoDeAcesso)   ┌────────────┐
│  Aplicação   │ ───────────────────────────────▶ │ API Token  │
│              │ ◀─────────────────────────────── │            │
│              │   JSON { token, expiraEm, ... }   └────────────┘
│              │
│              │  Bearer <token>                   ┌────────────────────────┐
│              │ ───────────────────────────────▶ │ CEP / Preço / Prazo /  │
│              │ ◀─────────────────────────────── │ Rastro / Pré-postagem… │
└──────────────┘                                   └────────────────────────┘
```

Endpoints de geração de token (URL base `/token`):

| Método | Caminho | Quando usar |
|---|---|---|
| POST | `/v1/autentica` | Autorização concedida diretamente ao usuário idCorreios |
| POST | `/v1/autentica/contrato` | Autorização pelo número do contrato |
| POST | `/v1/autentica/cartaopostagem` | Autorização pelo cartão de postagem (**caso mais comum**) |

Exemplo (homologação, por cartão de postagem):

```bash
curl -X POST 'https://apihom.correios.com.br/token/v1/autentica/cartaopostagem' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -H 'Authorization: Basic <base64(USUARIO:CODIGO_DE_ACESSO)>' \
  -d '{ "numero": "<NUMERO_DO_CARTAO_DE_POSTAGEM>" }'
```

Resposta esperada: **HTTP 201 Created**, com `ambiente`, `id`, `perfil`, `cnpj`, `cartaoPostagem` (`numero`, `contrato`, `dr`, `api`), `api` (lista de IDs de APIs liberadas), `emissao`, `expiraEm` e `token`.

```json
{
  "ambiente": "HOMOLOGACAO",
  "id": "xxxxxxxxxx",
  "perfil": "PJ",
  "cnpj": "00000000000101",
  "cartaoPostagem": { "numero": "00xxxxxxx7", "contrato": "99xxxxxxxx", "dr": 10, "api": [93, 36, 83] },
  "api": [93, 36, 54, 87],
  "emissao": "20XX-03-18T10:26:51",
  "expiraEm": "20XX-03-19T10:26:51",
  "token": "eyJhbGciOiJSUzUxMiJ9..."
}
```

### 4.2 ⚠ Usuário no ambiente de HOMOLOGAÇÃO = CNPJ

> **⚠ Observação prática (não consta no Manual V2.4):** no ambiente de **homologação**, o **nome de usuário** usado no HTTP Basic da API Token é o **CNPJ** da empresa — e não o login textual do Meu Correios, como descrevem o manual e a especificação OpenAPI.

Regras para o agente:

- **Homologação:** `Authorization: Basic base64("<CNPJ>:<codigoDeAcessoHomologacao>")`.
  - Use o CNPJ **somente com dígitos** (14 caracteres, sem pontos, barra ou hífen) — mesmo formato em que ele aparece no campo `cnpj` do retorno do token (ex.: `00000000000101`).
  - O código de acesso deve ser o gerado no portal de **homologação** (`cwshom`).
- **Produção:** `Authorization: Basic base64("<loginMeuCorreios>:<codigoDeAcessoProducao>")`, conforme a documentação oficial.
- Mantenha **usuário e senha configuráveis por ambiente** (ex.: `CORREIOS_USUARIO` e `CORREIOS_CODIGO_ACESSO` em arquivos `.env` distintos). Nunca derive o usuário de produção a partir do de homologação ou vice-versa.
- HTTP **401** na API Token em homologação costuma indicar usuário errado (login em vez de CNPJ, ou CNPJ formatado com pontuação) ou código de acesso de outro ambiente.

### 4.3 Uso e ciclo de vida do token

- Enviar em todas as APIs: `Authorization: Bearer <token>`.
- Validade no campo `expiraEm` (no exemplo do manual: 24 h após `emissao`). **Guarde o token em cache** e reutilize-o.
- Renove alguns minutos antes de expirar. Regras da API Token:
  - um **novo** token é devolvido se o atual expirou, se houve mudança de permissões ou se a solicitação ocorre dentro de **30 minutos** antes do vencimento;
  - fora disso, a API devolve o **mesmo** token já emitido.
- Limite de **3 solicitações por segundo** na API Token → HTTP **429 Too Many Requests**. Nunca gere um token por requisição.
- Um token serve para **todas** as APIs liberadas no cartão (lista `api` do retorno). Se uma API retornar 403, verifique se o ID dela está nessa lista.
- Recursos de impressão de rótulo só funcionam com as **mesmas credenciais** usadas para criar a pré-postagem.

### 4.4 Segurança

- Credenciais e token são sensíveis: não registrar em log, não versionar no Git, não expor no front-end. Chamadas às APIs devem partir do **back-end**.
- Tráfego exclusivamente por HTTPS.

---

## 5. Convenções comuns das requisições

| Item | Regra |
|---|---|
| Protocolo | HTTPS, REST, verbos `GET`, `POST`, `PUT`, `DELETE` |
| Formato | JSON — `Content-Type: application/json` e `Accept: application/json` |
| Exceções de formato | Upload de lote de pré-postagens usa `multipart/form-data` (campo `lote`); declaração de conteúdo e AR retornam **HTML**; rótulos retornam **JSON** com o PDF em Base64 no campo `dados` e o nome do arquivo em `nome` (✔ observado em homologação — ver arquivo 09) |
| Parâmetros repetidos | Listas em query string são enviadas repetindo o parâmetro: `status=2&status=5`, `servicosAdicionais=019&servicosAdicionais=001` |
| Paginação | Parâmetros `page` (inicia em **0**) e `size`; a resposta traz `page.size`, `page.totalElements`, `page.totalPages`, `page.number`. Para percorrer tudo, itere `page` de `0` a `totalPages - 1` |
| Datas | Variam por API e por campo — **sempre confira o formato no arquivo da API**. Respostas de Prazo/Rastro usam ISO (`2022-03-22T23:58:00`); `dtEvento` em Preço/Prazo usa `DD-MM-YYYY` (a especificação prevalece sobre o `18/03/2022` dos exemplos antigos do manual); `/v1/servicos-adicionais` usa `YYYY-MM-DD`; `prazoPostagem` da Pré-postagem vem como `dd/MM/yyyy` |
| Valores monetários | **Respostas da API Preço:** string com **vírgula** decimal (ex.: `"19,92"`) — converter antes de calcular. **Requisições da Pré-postagem:** string com **ponto** decimal e 2 casas (ex.: `"150.00"`) |
| Números como texto | Muitos campos numéricos (peso em **gramas**, medidas em **centímetros**, quantidades, valores) são `string` nas especificações — envie como texto (`"300"`) |
| CEP | Sempre **8 dígitos numéricos, sem hífen**, preservando zeros à esquerda (`01001001`). Tratar CEP como **string**, nunca como inteiro. `01001-001` gera HTTP 400 |
| Código do objeto | Formato `XX999999999XX` (ex.: `DG049186226BR`) |
| Códigos de serviço | Strings com zeros à esquerda (`"03220"`, `"04162"`). Use os códigos habilitados no seu cartão (API Meu Contrato) |

---

## 6. Requisições síncronas e assíncronas

Como o volume pode variar de uma a centenas de requisições, algumas APIs (principalmente Pré-postagem e rótulos) oferecem duas formas:

- **Síncrona:** o cliente aguarda o fim do processamento e só então recebe a resposta. Adequada para operações unitárias.
- **Assíncrona:** o cliente envia a requisição e recebe um **identificador** (ex.: id do lote ou do recibo). Depois consulta o status/resultado com esse identificador. Permite enviar várias requisições sem aguardar as anteriores.

Padrão de implementação assíncrona: `POST` (recebe id) → consulta periódica (`GET .../{id}`) com intervalo crescente → download do resultado quando concluído. Nunca faça *polling* agressivo.

---

## 7. Tratamento de erros (orientação geral)

| HTTP | Significado provável | Ação |
|---|---|---|
| 200 / 201 | Sucesso (201 na criação de token/recursos) | — |
| 400 | Dados inválidos (formato, tamanho, campo obrigatório). A mensagem indica o problema — ex.: CEP inexistente ou fora do padrão | Validar entrada contra o Schema; não repetir sem corrigir |
| 401 | Token ausente, expirado ou inválido; na API Token, usuário/código de acesso incorretos | Renovar token; em homologação conferir o CNPJ como usuário (4.2) |
| 403 | Sem permissão para a API/contrato/cartão | Verificar cadastro da API no cartão e a lista `api` do token |
| 429 | Limite de requisições excedido | Aguardar e aplicar *backoff*; usar cache do token |
| 5xx | Falha no servidor dos Correios | Repetir com *backoff* exponencial |

Algumas APIs (ex.: Pedido de Informação) retornam HTTP de sucesso com um **código de negócio** no corpo (`codigoRetorno` / `responseCode`) — sempre inspecione esses campos, item a item, em operações em lote.

✔ **Observado em homologação (04/10/2026) na API Pré-postagem:**

| Situação | HTTP | Corpo |
|---|:-:|---|
| Download do rótulo de pré-postagem pendente | **200** | `{"mensagem": "PPN-288: Não é permitido imprimir rótulo de pré-postagem com status igual a Pendente. ..."}` — sem o campo `dados` |
| Cancelar pré-postagem que não está pré-postada (ex.: já cancelada) | 400 | `MessageResponse` com `PPN-145-5: Operação não realizada! A pré-postagem está com o status Cancelado. ...` |
| Imprimir DACE de pré-postagem pendente | **404** | `PPN-376: Não é possível emitir DACE para NF-e, objetos que não estejam prepostados ou sem chave D...` |
| AR sem o adicional 001 | 422 | `MessageResponse` (arquivo 06) |

Consequências para a aplicação:

- **Não confie só no código HTTP:** uma resposta 200 pode trazer um erro. Confira se o campo esperado veio (ex.: `dados` no download do rótulo) e, se não veio, leia `mensagem`/`msgs`.
- Regras de negócio aparecem com HTTP **400, 404 ou 422**.
- Os códigos de erro seguem o padrão `LETRAS-NÚMEROS`, às vezes com um sufixo (`PPN-288`, `ERP-049`, `SRO-020`, `PPN-145-5`), sempre no início da mensagem e seguidos de `:`.

> ⚠ **Observação prática (PHP no Windows):** se o PHP não tiver um pacote de autoridades certificadoras configurado (`curl.cainfo` vazio no `php.ini`), as chamadas falham com `cURL error 60: SSL certificate problem: self-signed certificate in certificate chain`, embora a cadeia dos Correios seja válida (DigiCert). Configure `curl.cainfo` (ou `openssl.cafile`) apontando para um pacote de CAs atualizado.

---

## 8. URLs base das APIs (homologação)

Para produção, troque `apihom` por `api`.

| API | URL base (homologação) | Exemplos de recursos |
|---|---|---|
| Token | `https://apihom.correios.com.br/token` | `POST /v1/autentica/cartaopostagem` |
| CEP | `https://apihom.correios.com.br/cep` | `GET /v2/enderecos/{cep}` (o manual cita `/v1`; use a versão da especificação) |
| Agência | `https://apihom.correios.com.br/agencia` | `GET /v1/unidades?municipio=&bairro=&uf=&status=&codigoTipoUnidade=&page=&size=` |
| Prazo | `https://apihom.correios.com.br/prazo` | `GET /v1/nacional/{coProduto}?cepOrigem=&cepDestino=`; `POST /v1/nacional` (lote) |
| Preço | `https://apihom.correios.com.br/preco` | `GET /v1/nacional/{coProduto}?cepOrigem=&cepDestino=&psObjeto=&...`; `POST /v1/nacional` (até 5 simulações) |
| Rastro | `https://apihom.correios.com.br/srorastro` | `GET /v1/objetos/{codigoObjeto}?resultado=T` |
| Pré-postagem | `https://apihom.correios.com.br/prepostagem` | `POST /v1/prepostagens`, `POST /v1/prepostagens/assincrona`, `POST /v1/prepostagens/rotulo/assincrono/pdf`, `GET /v1/prepostagens/rotulo/download/assincrono/{idRecibo}`, `/v1/prepostagens/lote`, `GET /v2/prepostagens` |
| Webhook | `https://apihom.correios.com.br/webhook` | `GET /v1/servicos`, `POST /v1/servicos/{idServicoWebhook}/assinaturas` |
| Pedido de Informação | `https://apihom.correios.com.br/pedido-informacao` | `/v1/externo/pedidos/...` (nacional), `/v1/international/requests/...` (Packet) |
| Meu Contrato | `https://apihom.correios.com.br/meucontrato` | `GET /v1/empresas/{cnpj}/contratos/{nuContrato}/cartoes/{nuCartaoPostagem}/servicos` |

> **Inconsistências do manual:** o texto da API Rastro cita `apphom.correios.com.br/rastro`, mas o exemplo cURL usa `apihom.correios.com.br/srorastro` — considere o segundo. A API Pedido de Informação aparece ora com host de produção, ora de homologação — siga o padrão `apihom`/`api`. A URL oficial de cada especificação fica em `/<api>/v3/api-docs`. Em caso de dúvida, prevalece o catálogo CWS.

A **versão** no caminho (`/v1`, `/v2`, `/v3`) é por recurso; confira no arquivo específico de cada API.

---

## 9. Regras de negócio transversais

### 9.1 Serviços e códigos

- O código de serviço (`coProduto` em Preço/Prazo, `codigoServico` na Pré-postagem) deve constar no contrato/cartão. Obtenha a lista pela API **Meu Contrato**.
- Serviços adicionais são informados por código (ex.: `019` = valor declarado, `001` = aviso de recebimento, nos exemplos do manual).

### 9.2 Pré-postagem

- Cada pré-postagem corresponde a **um objeto postal**. Todos os campos (contrato, remetente, destinatário, objeto, serviços adicionais) são validados campo a campo; inconsistências retornam mensagem com o motivo.
- Sucesso retorna o **id da pré-postagem** (ex.: `PRgddyJTC0QMyNqlCX7w0H9A`, 24 caracteres) e, segundo a especificação, também o `codigoObjeto` (código de rastreio) e o `statusAtual`. Grave os três.
- Não é preciso informar o número do objeto: o sistema o atribui. Se o `codigoObjeto` vier vazio na criação, ele é obtido na emissão do rótulo (comportamento descrito no manual). Uso antecipado de números exige **faixa de etiquetas**, liberada com base na média dos últimos 3 meses de postagem — consulte o saldo antes de pedir nova faixa.
- **Expiração:** o objeto não postado até `prazoPostagem` passa a `statusAtual = 4` (EXPIRADO). Pela especificação, o padrão é **criação + 14 dias** (máximo 90 dias) quando o campo não é informado; o manual V2.4 cita 7 dias corridos — use o `prazoPostagem` retornado como referência.
- **Síncrona × assíncrona:** `POST /v1/prepostagens` (síncrona, arquivo 06) para operações unitárias; `POST /v1/prepostagens/assincrona` (arquivo 07) para volume, com consulta do recibo. ✔ Observado em homologação: após `situacao = PROCESSADO`, a pré-postagem assíncrona fica alguns minutos em `statusAtual = 7` (PENDENTE) antes de passar a `2` (PREPOSTADO); o rótulo deve ser solicitado **só depois** do status `2` — um `idRecibo` de rótulo pedido com status `7` falha no download com `PPN-288` e não passa a funcionar depois; é preciso solicitar o rótulo de novo (ver arquivos 07, 08 e 09).
- ✔ **Observado em homologação (04/10/2026):** a criação **síncrona** também devolveu a pré-postagem com `statusAtual = 7` (PENDENTE) — não presuma o status 2 em nenhuma das vias; confira `statusAtual` antes de pedir o rótulo. Nesse dia, as pré-postagens de teste ficaram pendentes por mais de 2 minutos e foram **canceladas automaticamente** pelos Correios (status 5; evento de rastreamento "Etiqueta cancelada pelo sistema de captação"). Em 27/09/2026 a pré-postagem assíncrona tinha passado de 7 para 2 em alguns minutos. O tempo e o desfecho do status 7 não são documentados.
- **Rótulo (objetos registrados):** `POST /v1/prepostagens/rotulo/assincrono/pdf` (arquivo 08) com `idsPrePostagem` (ou `codigosObjeto`, em reimpressão) e `tipoRotulo` `P` (padrão) ou `R` (reduzido) → recibo → `GET /v1/prepostagens/rotulo/download/assincrono/{idRecibo}` (arquivo 09). Só pode ser emitido com as **mesmas credenciais** da criação. Objetos simples em lote usam `POST /v1/prepostagens/rotulo/lote/assincrono/pdf` (até **6 ids** por requisição para carta simples, segundo o manual).
- **Declaração de conteúdo** (`v1/prepostagens/declaracaoconteudo/{ids}`) e **AR** (`v1/prepostagens/avisorecebimento/{ids}`): exigem pré-postagem com status **pré-postado**; retornam HTML.
- **Consulta:** `GET /v2/prepostagens` é paginada e exige ao menos um parâmetro de identificação (id/código/eTicket) ou combinação de parâmetros.
- **Documento fiscal:** com NF-e, informe a chave (`chaveNFe`) na criação ou depois, por `PATCH /v1/prepostagens/dce`; sem nota, use a DCe — emitida pelos Correios (`emiteDCe: "S"`, com impressão da DACE em `POST /v1/prepostagens/dce/dace/impressao`) ou pelo cliente, informando a chave (arquivo 14).
- **Cancelamento:** síncrono, um objeto por chamada — `DELETE /v1/prepostagens/{id}` ou `DELETE /v1/prepostagens/objeto/{codigoObjeto}` (arquivo 13); assíncrono em lote — `DELETE /v1/prepostagens/assincrona` (arquivo 07). Só para objetos ainda não postados; irreversível.
- **Lote (cartas simples/sem rastreamento):** `POST /v1/prepostagens/lote` com `multipart/form-data`, campo `lote`; processamento assíncrono; consulta em `GET /v1/prepostagens/lote/{idLote}`, que devolve os ids das pré-postagens geradas para emissão dos rótulos.

### 9.3 Preço e Prazo

- São APIs separadas (microsserviços): calcule preço e prazo em chamadas distintas.
- `GET` para uma consulta; `POST /v1/nacional` para lote (`idLote` + lista de parâmetros com `nuRequisicao` para correlacionar as respostas). Preço: até 5 simulações por POST.

### 9.4 Pedido de Informação (Fale Conosco)

- Métodos: cadastrar, reativar e acompanhar manifestações — nacionais (campos em português) e internacionais Correios Packet (campos em inglês).
- Até **50** pedidos/reativações por requisição; cada item retorna seu próprio código de resultado.
- Acompanhamento: parâmetro de canal sempre `W`.
- Prazos: em geral 90 dias da entrega/previsão de entrega; 30 dias para avaria/violação.
- APIs internacionais aceitam o header `Accept-Language` para respostas em português ou inglês.
- Esta API ainda **não tem arquivo próprio** nesta documentação; a fonte é o manual V2.4 (seção 16).

---

## 10. Boas práticas para a aplicação

- **Camada de cliente única** para as APIs dos Correios: centraliza URL base, cabeçalhos, obtenção/renovação do token, *retry* e log.
- **Cache do token** com renovação preventiva (antes de `expiraEm`) e proteção contra chamadas concorrentes (evita 429).
- **Validação local** antes do envio (CEP com 8 dígitos, código de objeto, tamanhos dos campos conforme Schema).
- **Idempotência** em operações de criação: guarde o id retornado antes de tentar novamente, para não gerar pré-postagens duplicadas.
- **Separação de ambientes** por configuração; flag explícita para produção.
- **Logs** sem dados sensíveis (mascarar token, CNPJ quando necessário, dados pessoais de destinatários).
- Teste primeiro no portal (*Try it out*) ou em Postman/Insomnia (use *environments* com a variável `baseUrl` para alternar homologação/produção e defina o Bearer token na pasta da coleção com *Inherit auth from parent* nas requisições).

---

## 11. Sequência recomendada para um fluxo completo de envio

```
01. POST /token/v1/autentica/cartaopostagem                       → token (cache até expiraEm)
02. GET  /meucontrato/v1/empresas/{cnpj}/.../servicos             → códigos de serviço permitidos
03. GET  /cep/v2/enderecos/{cep}                                  → validar/completar endereço
04. GET  /preco/v1/nacional/{coProduto}                           → preço
05. GET  /prazo/v1/nacional/{coProduto}                           → prazo
06. POST /prepostagem/v1/prepostagens                             → id + codigoObjeto
07. (alternativa em volume) POST /prepostagem/v1/prepostagens/assincrona → recibo → GET .../assincrona/{recibo}
08. POST /prepostagem/v1/prepostagens/rotulo/assincrono/pdf       → recibo do rótulo
09. GET  /prepostagem/v1/prepostagens/rotulo/download/assincrono/{idRecibo} → PDF
    (opcional) declaração de conteúdo / AR                        → HTML
    Postagem física na agência até o prazoPostagem
10. GET  /srorastro/v1/objetos/{codigoObjeto}                     → acompanhamento (consulta)
11. Webhook (assinatura wh-rastro)                                → acompanhamento (push)
12. GET  /prepostagem/v1/prepostagens/postada?codigoObjeto=…      → valor e peso tarifados (após a postagem)
    (se necessário) Pedido de Informação                          → manifestação no Fale Conosco
```

Os números à esquerda correspondem aos arquivos da seção 13.

---

## 12. Glossário

| Termo | Definição |
|---|---|
| API | *Application Programming Interface* |
| Autenticação | Verificação da identidade digital do usuário |
| Autorização | Verificação dos privilégios concedidos para usar uma aplicação |
| Aviso de Recebimento (AR) | Serviço adicional que comprova ao remetente a entrega do objeto |
| Cartão de postagem | Identifica o usuário como cliente com contrato com os Correios |
| Código do serviço | Código dos serviços contratados, constante no contrato comercial |
| Código de acesso / Senha de componente | Senha definida no portal CWS para usar os componentes (APIs); é a senha do Basic Auth |
| Contrato comercial | Instrumento firmado com os Correios para prestação de serviços |
| Declaração de conteúdo | Documento que acompanha objetos não sujeitos a tributação |
| DNE | Diretório Nacional de Endereços — base de endereçamento dos Correios |
| Expiração | Status automático (`statusAtual = 4`) da pré-postagem não postada até o `prazoPostagem` |
| Meu Correios / idCorreios | Mecanismo de autenticação e autorização dos serviços dos Correios na internet |
| Pré-postagem | Envio eletrônico prévio dos dados de um objeto para postagem |
| Rótulo de endereçamento | Etiqueta de identificação e endereçamento do objeto |
| Token (API Manager) | String codificada (JWT) que autoriza o acesso às APIs |

---

## 13. Arquivos desta documentação (ordem de testes)

Os arquivos são numerados na ordem em que devem ser **lidos e testados em homologação**: cada etapa usa dados produzidos pelas anteriores (token → serviços → endereço → cotação → pré-postagem → rótulo → rastreamento).

| # | Arquivo | Conteúdo | Depende de |
|:-:|---|---|---|
| 00 | `correios-00-instrucoes-gerais.md` | Este documento — regras gerais | — |
| 01 | `correios-01-token-api.md` | API Token (geração do JWT; usuário = CNPJ em homologação) | Credenciais do portal CWS |
| 02 | `correios-02-meucontrato-servicos-cartao-api.md` | Serviços habilitados no cartão de postagem | 01 |
| 03 | `correios-03-cep-enderecos-api.md` | Consulta de endereço por CEP | 01 |
| 04 | `correios-04-preco-api.md` | API Preço | 01, 02 (código do serviço), 03 (CEPs) |
| 05 | `correios-05-prazo-api.md` | API Prazo | 01, 02, 03 |
| 06 | `correios-06-prepostagem-criar-api.md` | Criação síncrona de pré-postagem (`POST /v1/prepostagens`) | 01, 02, 03 |
| 07 | `correios-07-prepostagem-assincrona-registrados-api.md` | Pré-postagem assíncrona de objetos registrados (criação e cancelamento) | 01, 06 (mesmo corpo) |
| 08 | `correios-08-rotulo-solicitar-assincrono-pdf-api.md` | Solicitação assíncrona do rótulo em PDF | 06 ou 07 (ids) |
| 09 | `correios-09-rotulo-download-assincrono-api.md` | Download do rótulo (PDF) | 08 (recibo) |
| 10 | `correios-10-rastro-sro-api.md` | API Rastro (SRO) | 06/07 (`codigoObjeto`) |
| 11 | `correios-11-webhook-api.md` | API Webhook (notificações push) | 01; objeto postado para eventos reais |
| 12 | `correios-12-prepostagem-postada-api.md` | Dados reais da postagem (`GET /v1/prepostagens/postada`): valor, peso e dimensões tarifados | 01; objeto **postado** (06/07 + postagem física) |
| 13 | `correios-13-prepostagem-cancelar-api.md` | Cancelamento síncrono de pré-postagem (`DELETE /v1/prepostagens/{id}` e `DELETE /v1/prepostagens/objeto/{codigoObjeto}`) | 01; 06 (id ou código do objeto, ainda não postado) |
| 14 | `correios-14-prepostagem-dce-api.md` | Emissão de DCe: impressão da DACE (`POST /v1/prepostagens/dce/dace/impressao`) e inclusão da chave de documento fiscal (`PATCH /v1/prepostagens/dce`) | 01; 06 (pré-postagem com `emiteDCe: "S"` ou código do objeto) |
| — | `correios-etiquetas-zpl.md` | Guia complementar (não é etapa de teste da API): como gerar a etiqueta de postagem em **ZPL** — layout, DataMatrix de 164 caracteres, logotipos `^GFA`, template base. Baseado nos exemplos de `doc/legado/etiqueta-postagem-zpl/`. | 06 (dados da pré-postagem) |
| — | `correios-danfe-simplificada-zpl.md` | Guia complementar (não é etapa de teste da API): como gerar o **DANFE Simplificado (etiqueta)** em ZPL — chave de acesso em Code 128, dados exigidos, template base. Baseado nos exemplos de `doc/legado/danfe-simplificada-zpl/`. | NF-e autorizada; 06 (código do objeto, opcional) |

---

## 14. Suporte

Dúvidas sobre as APIs: contatar o **Assistente Comercial** da região ou do contrato. Central de atendimento: `https://www.correios.com.br/falecomoscorreios/central-de-atendimento`.
