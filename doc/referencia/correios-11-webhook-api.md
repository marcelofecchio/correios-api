# Correios — API Webhook

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/webhook/v3/api-docs`), versão **1.6.46** (homologação), em 25/09/2026. As seções 1 a 5 reproduzem a especificação; a seção 6 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 11 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-10-rastro-sro-api.md` · Próxima: `correios-12-prepostagem-postada-api.md`

## 0. Resumo rápido

- **Finalidade:** receber notificações automáticas (push) dos Correios no **seu** endpoint HTTPS sempre que ocorrer um evento (ex.: eventos de rastreamento no serviço `wh-rastro`), em vez de consultar as APIs periodicamente.
- **Duas partes na integração:**
  1. **Gerenciar a assinatura** (esta API): consultar serviços, criar assinatura, cadastrar endereços (URLs) e tipos de evento, testar, ativar/desativar, consultar e reenviar eventos. Autenticação `Authorization: Bearer <token>` (API Token — ver `correios-01-token-api.md`).
  2. **Receber os eventos** (no seu sistema): endpoint `POST` HTTPS na porta 443, certificado de AC reconhecida, resposta `200`/`201` com corpo vazio em até **2 segundos**, validação da assinatura HMAC-SHA256 no header `x-correios-signature`.
- **Empacotamento:** eventos do mesmo tipo chegam agrupados em listas de até 100 itens (tamanho pode mudar).
- **Retentativas:** a cada 5 min na 1ª hora; depois 1 por hora por até 3 dias. Eventos descartados após 5 dias.
- **Cuidado:** responder **410** cancela imediatamente o endereço; respostas 3xx/4xx não geram novas tentativas.

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/webhook` |
| Homologação | `https://apihom.correios.com.br/webhook` |

| Método | Caminho | Grupo | Resumo |
|---|---|---|---|
| GET | `/v1/servicos` | Serviços | Lista os serviços webhooks |
| GET | `/v1/servicos/{idServicoWebhook}` | Serviços | Consulta um serviço de webhook |
| GET | `/v1/servicos/assinaturas` | Assinaturas | Lista todas as assinaturas do assinante. |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas` | Assinaturas | Lista todas as assinaturas de um serviço do assinante. |
| POST | `/v1/servicos/{idServicoWebhook}/assinaturas` | Assinaturas | Cria um nova assinatura |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}` | Assinaturas | Consulta uma assinatura |
| PATCH | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}` | Assinaturas | Altera descrição, email e língua de uma assinatura |
| POST | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/teste` | Assinaturas | Envia uma mensagem de teste, para os endereços cadastrados. |
| PATCH | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/ativa` | Assinaturas | Ativa uma assinatura |
| PATCH | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/desativa` | Assinaturas | Desativa uma assinatura |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos` | Assinaturas |  |
| POST | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos` | Assinaturas |  |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos` | Assinaturas - endereços | Lista os endereços de uma assinatura |
| POST | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos` | Assinaturas - endereços | Adiciona um novo endereço para uma assinatura |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}` | Assinaturas - endereços | Consulta um endereço de uma assinatura |
| PATCH | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}` | Assinaturas - endereços | Altera nome, url e o secret de um endereço de uma assinatura |
| PATCH | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/ativa` | Assinaturas - endereços | Ativa um endereço existente da assinatura |
| PATCH | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/desativa` | Assinaturas - endereços | Desativa um endereço existente da assinatura |
| PUT | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos` | Assinaturas - endereços | Essa operação substitui os tipos de eventos informados pelos existentes do endereço |
| POST | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}` | Assinaturas - endereços | Adiciona um novo tipo de evento para um endereço. |
| DELETE | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}` | Assinaturas - endereços | Exclui um tipo de evento de um endereço |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos` | Eventos | Lista os eventos recebidos |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/{idEvento}` | Eventos | Consulta um evento recebido pelo identificador |
| POST | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/redelivery` | Eventos | Reenvia mensagens de eventos de uma assinatura |
| POST | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/eventos/redelivery` | Eventos | Reenvia mensagens de eventos a um endereço de uma assinatura |
| GET | `/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/sequences` | Sequencias | Lista as sequências de eventos gerados para uma assinatura |

## 1. Instruções oficiais da API

**O que é Webhook?**

- **Webhook:** mecanismo que envia notificações para o seu sistema sempre que um evento relevante ocorre. Em vez de buscar ativamente os dados, você os recebe assim que estiverem disponíveis.
- **Endpoint:** é o endereço (URL) fornecido por você que receberá as notificações (requisições HTTP) enviadas pela plataforma. Ele deve estar preparado para processar os dados recebidos via requisições POST com payload em formato JSON.

**Pré-requisitos à adesão aos serviços de webhooks**

- Código do **serviço webhook desejado**, vinculado a um contrato ou a um cartão de postagem ativo do cliente.
- Os endpoints do assinante devem oferecer suporte a **HTTPS**, sendo que **somente a porta 443** é permitida.
- Os endpoints devem obrigatoriamente utilizar um certificado digital emitido por uma Autoridade Certificadora reconhecida.

**Sequência para a criação de uma assinatura**


1. **Consulte os serviços disponíveis**
Utilize este passo para identificar os serviços e eventos que podem ser assinados, executando:

`GET /v1/servicos`

2. **Defina os dados da assinatura**
Após identificar os serviços disponíveis, prepare os dados necessários para a criação da assinatura, incluindo os seus endpoints e os eventos desejados, executando:

`POST /v1/servicos/{idServicoWebhook}/assinaturas`

**Exemplo de payload:**

```json
{
  "descricao": "Descrição da sua assinatura",
  "enderecos": [
    {
      "nome": "Descrição do seu endpoint",
      "tipoEventos": [
        { "tipo": "*" }
      ],
      "url": "https://seudominio.com/seu-endpoint",
      "secret": "seu-secret"
    }
  ],
  "email": "seu-email@seu-dominio.com"
}
```

_(`"tipo": "*"` = todos os eventos)_

A definição da palavra secreta **secret** é altamente recomendada. Esse valor tem como finalidade **garantir a autenticidade dos eventos recebidos** pelo assinante, permitindo validar se a mensagem foi realmente enviada pela plataforma.
3. Com a assinatura e o endpoint devidamente criados, execute o envio de um evento de teste a fim de validar o correto funcionamento da integração, execute:

`POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/teste`

**Envio dos eventos pela plataforma**

Os eventos são enviados ao seu endpoint via requisições HTTP **POST**, com o payload em formato JSON. Os seguintes **headers** são incluídos na requisição:

| Header | Descrição | Exemplo de Valor |
|---|---|---|
| content-type | Tipo do conteúdo recebido na resposta | application/json |
| accept-charset | Conjunto de caracteres aceitos | UTF-8 |
| x-correios-hook-id | Identificador do serviço Webhook | wh-rastro |
| x-correios-hook-event-id | Identificador do evento do serviço Webhook | wh-rastro-40-4425 |
| x-correios-hook-assinatura | Identificador da assinatura | 40 |
| x-correios-hook-event | Identificador do tipo de evento | BDE-1 |
| x-correios-hook-event-sq | Número sequencial do evento | 4425 |
| x-correios-hook-event-tp-sq | Número sequencial do tipo de evento | 915 |
| x-correios-hook-nu-contrato | Número do contrato | 9999999999 |
| x-correios-signature (*) | Resumo HMAC do payload | sha256=63268c3fb98094e5d0567683afa4e741d6bf5a7bc... |

**(*)** Quando uma palavra secreta é definida (atributo **secret**), o header ****x-correios-signature**** terá um **resumo HMAC** (_Hash-based Message Authentication Code_ ) gerado a partir do corpo da requisição (_payload_ ).

Esse resumo HMAC é calculado com o algoritmo **SHA-256** e representado em formato **hexadecimal**. A chave utilizada no processo de criptografia HMAC é o valor definido no atributo **secret**.

O objetivo é garantir a **integridade** e a **autenticidade** da mensagem, permitindo que o destinatário valide se a requisição realmente foi enviada pela plataforma.

**Respostas do assinante**

- A resposta sempre com o status HTTP **200** ou **201** para indicar o recebimento bem-sucedido do evento.
- A resposta deve sempre ter o **(body)** vazio.
- A requisição deve ser respondida imediatamente em até **2 segundos**.
- Serão consideradas **falhas** respostas com:
  - Carga útil (**payload**) de resposta **maior que 32KB**;
  - Códigos **HTTP 5xx**: acarretará em novas tentativas de entrega;
  - Códigos **HTTP 4xx**: considerados falhas permanentes. **Não haverá novas tentativas de entrega**;
  - Códigos **HTTP 3xx**: redirecionamentos **não são seguidos** e **não haverá novas tentativas de entrega**.
- Resposta com código HTTP **410** (gone), resultará no **cancelamento imediato** do endereço da assinatura.

**Fluxo de raspagem dos eventos**

- **Periodicidade**: Os eventos são verificados (raspados) a cada minuto.
- **Empacotamento de eventos**: Eventos do mesmo tipo (**tpEvento**) são agrupados em uma ou mais listas com no máximo **100** itens cada. O número máximo de itens pode ser alterado no futuro, e o cliente deve tratar a quantidade recebida de forma dinâmica, independentemente do tamanho dos pacotes.
- Após 5 dias os eventos serão **descartados**.

**Retentativa de envios**

- **Critério de retentativa**: Se a requisição é rejeitada por erro do servidor/cliente (ex.: HTTP 5xx), o mesmo payload será enviado novamente seguindo a lógica abaixo.
- **Primeira fase (primeira hora)**: Uma tentativa a cada 5 minutos durante 1 hora (totalizando 12 tentativas).
- **Segunda fase (até 3 dias)**: Após a primeira hora, será feita 1 nova tentativa por hora durante 3 dias.
- **Envio do payload**: O mesmo payload será enviado em cada tentativa, contendo todos os objetos inicialmente empacotados.
- **Ordenação**: Eventos podem ser entregues aos endpoints fora de ordem ou mais de uma vez, devido às tentativas de reenvio.

## 2. Endpoints de gerenciamento

### Serviços

Serviços de webhook disponíveis para assinatura

#### 2.1 `GET /v1/servicos`

**Lista os serviços webhooks**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos`
- Header: `Authorization: Bearer <token>`
- operationId: `listaServicosWebhook`

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | array<objeto `ServicoWebhookResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].id` | string |  | Identificador do serviço de webhook — minLength: `5` — maxLength: `40` |
| `[].nome` | string |  | Nome do serviço de webhook — minLength: `5` — maxLength: `60` |
| `[].descricao` | string |  | Descrição detalhada do serviço de webhook — minLength: `5` — maxLength: `100` |
| `[].disponibilizacao` | string |  | Valores: `I`, `E`, `A` |
| `[].nuApi` | integer (int32) |  | Número da API que está associado ao serviço de webhook. É necessário que o token a ser utilizado tenha essa API autorizada — maxLength: `5` |
| `[].tipoEventos` | array<objeto `TipoEventoResponse`> |  | Lista dos tipoEventos que estão disponíveis para a assinatura — minItems: `1` — maxItems: `2147483647` |
| `[].tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `[].tipoEventos[].descricao` | string |  | Descrição do tipo de evento — minLength: `3` — maxLength: `100` |
| `[].tipoEventos[].desativadoEm` | string (date-time) |  |  |
| `[].status` | string |  | Status do serviço de webhook — Valores: `C`, `A`, `D`, `P` |
| `[].exigeContrato` | string |  | Valores: `S`, `N` |

#### 2.2 `GET /v1/servicos/{idServicoWebhook}`

**Consulta um serviço de webhook**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaServicoWebhook`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `ServicoWebhookResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | string |  | Identificador do serviço de webhook — minLength: `5` — maxLength: `40` |
| `nome` | string |  | Nome do serviço de webhook — minLength: `5` — maxLength: `60` |
| `descricao` | string |  | Descrição detalhada do serviço de webhook — minLength: `5` — maxLength: `100` |
| `disponibilizacao` | string |  | Valores: `I`, `E`, `A` |
| `nuApi` | integer (int32) |  | Número da API que está associado ao serviço de webhook. É necessário que o token a ser utilizado tenha essa API autorizada — maxLength: `5` |
| `tipoEventos` | array<objeto `TipoEventoResponse`> |  | Lista dos tipoEventos que estão disponíveis para a assinatura — minItems: `1` — maxItems: `2147483647` |
| `tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `tipoEventos[].descricao` | string |  | Descrição do tipo de evento — minLength: `3` — maxLength: `100` |
| `tipoEventos[].desativadoEm` | string (date-time) |  |  |
| `status` | string |  | Status do serviço de webhook — Valores: `C`, `A`, `D`, `P` |
| `exigeContrato` | string |  | Valores: `S`, `N` |

### Assinaturas

#### 2.3 `GET /v1/servicos/assinaturas`

**Lista todas as assinaturas do assinante.**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/assinaturas`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/assinaturas`
- Header: `Authorization: Bearer <token>`
- operationId: `listaAssinaturas_1`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | query | string | ✔ | Identificação do serviço de webhook assinado — minLength: `0` — maxLength: `40` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/assinaturas?idServicoWebhook=wh-rastro
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | array<objeto `AssinaturaResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].id` | integer (int64) |  | Identificador — maxLength: `8` |
| `[].descricao` | string |  | Descrição da assinatura — minLength: `3` — maxLength: `100` |
| `[].idServicoWebhook` | string |  | Identificação do serviço de webhook — minLength: `3` — maxLength: `40` |
| `[].email` | string |  | Email do responsavel pela assinatura — minLength: `5` — maxLength: `100` |
| `[].status` | string |  | Status da assinatura. C: Criada; A: Ativada; D: Desativado pelo assinante; X: Cancelado pelo Correios — Valores: `C`, `A`, `D`, `X` |
| `[].language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `[].enderecos` | array<objeto `EnderecoResponse`> |  | Lista de endereços da urls para envio dos hooks — minItems: `1` — maxItems: `5` |
| `[].enderecos[].id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `[].enderecos[].nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `[].enderecos[].tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `[].enderecos[].tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `[].enderecos[].tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `[].enderecos[].url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `[].enderecos[].status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `[].enderecos[].motivo` | string |  |  |
| `[].enderecos[].secret` | string |  |  |
| `[].enderecos[].token` | string |  |  |
| `[].enderecos[].certificadoDigital` | string |  |  |
| `[].enderecos[].criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `[].enderecos[].atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |
| `[].contratos` | array<objeto `ContratoResponse`> |  | Lista de contratos — minItems: `1` — maxItems: `20` |
| `[].contratos[].numero` | string | ✔ | minLength: `1` |
| `[].contratos[].nuDr` | integer (int64) | ✔ |  |
| `[].contratos[].status` | string |  | Status do contrato. A: Ativo — Valores: `A` |
| `[].contratos[].criadoEm` | string (date-time) | ✔ |  |
| `[].contratos[].atualizadoEm` | string (date-time) |  |  |
| `[].assinante` | objeto `AssinanteResponse` |  |  |
| `[].assinante.user` | string |  |  |
| `[].assinante.perfil` | string |  |  |
| `[].assinante.cnpj` | string |  |  |
| `[].assinante.pjInternacional` | integer (int64) |  |  |
| `[].assinante.cpf` | string |  |  |
| `[].assinante.cie` | string |  |  |
| `[].assinante.nuContrato` | string |  |  |
| `[].criadaEm` | string (date-time) |  | Data e hora de criação da assinatura |
| `[].atualizadaEm` | string (date-time) |  | Data e hora de atualização da assinatura |

#### 2.4 `GET /v1/servicos/{idServicoWebhook}/assinaturas`

**Lista todas as assinaturas de um serviço do assinante.**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas`
- Header: `Authorization: Bearer <token>`
- operationId: `listaAssinaturas`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | array<objeto `AssinaturaResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].id` | integer (int64) |  | Identificador — maxLength: `8` |
| `[].descricao` | string |  | Descrição da assinatura — minLength: `3` — maxLength: `100` |
| `[].idServicoWebhook` | string |  | Identificação do serviço de webhook — minLength: `3` — maxLength: `40` |
| `[].email` | string |  | Email do responsavel pela assinatura — minLength: `5` — maxLength: `100` |
| `[].status` | string |  | Status da assinatura. C: Criada; A: Ativada; D: Desativado pelo assinante; X: Cancelado pelo Correios — Valores: `C`, `A`, `D`, `X` |
| `[].language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `[].enderecos` | array<objeto `EnderecoResponse`> |  | Lista de endereços da urls para envio dos hooks — minItems: `1` — maxItems: `5` |
| `[].enderecos[].id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `[].enderecos[].nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `[].enderecos[].tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `[].enderecos[].tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `[].enderecos[].tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `[].enderecos[].url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `[].enderecos[].status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `[].enderecos[].motivo` | string |  |  |
| `[].enderecos[].secret` | string |  |  |
| `[].enderecos[].token` | string |  |  |
| `[].enderecos[].certificadoDigital` | string |  |  |
| `[].enderecos[].criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `[].enderecos[].atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |
| `[].contratos` | array<objeto `ContratoResponse`> |  | Lista de contratos — minItems: `1` — maxItems: `20` |
| `[].contratos[].numero` | string | ✔ | minLength: `1` |
| `[].contratos[].nuDr` | integer (int64) | ✔ |  |
| `[].contratos[].status` | string |  | Status do contrato. A: Ativo — Valores: `A` |
| `[].contratos[].criadoEm` | string (date-time) | ✔ |  |
| `[].contratos[].atualizadoEm` | string (date-time) |  |  |
| `[].assinante` | objeto `AssinanteResponse` |  |  |
| `[].assinante.user` | string |  |  |
| `[].assinante.perfil` | string |  |  |
| `[].assinante.cnpj` | string |  |  |
| `[].assinante.pjInternacional` | integer (int64) |  |  |
| `[].assinante.cpf` | string |  |  |
| `[].assinante.cie` | string |  |  |
| `[].assinante.nuContrato` | string |  |  |
| `[].criadaEm` | string (date-time) |  | Data e hora de criação da assinatura |
| `[].atualizadaEm` | string (date-time) |  | Data e hora de atualização da assinatura |

#### 2.5 `POST /v1/servicos/{idServicoWebhook}/assinaturas`

**Cria um nova assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `criaAssinatura`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas
Authorization: Bearer <token>
```

**Corpo da requisição** (obrigatório) — objeto `AssinaturaInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `descricao` | string | ✔ | Descrição da assinatura — minLength: `3` — maxLength: `100` |
| `enderecos` | array<objeto `EnderecoInput`> | ✔ | minItems: `1` — maxItems: `5` |
| `enderecos[].nome` | string | ✔ | Nome/label de identificação do endereço — minLength: `3` — maxLength: `100` |
| `enderecos[].tipoEventos` | array<objeto `TipoEventoInput`> | ✔ | minItems: `1` — maxItems: `2147483647` |
| `enderecos[].tipoEventos[].tipo` | string | ✔ | Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado. — minLength: `3` — maxLength: `30` |
| `enderecos[].url` | string | ✔ | URL do endereço. Prefira que seu endpoint suporte HTTPS, apesar de a plataforma suportar HTTP. Apenas as portas 80 (HTTP) e 443 (HTTPS) são passíveis de utilização. Utilize validação da autenticidade no seu endpoint através de um usuário e senha (basic auth) ou token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Optando por receber os webhook via HTTPS, certifique-se de usar um certificado digital emitido por Autoridade Certificadora reconhecida, em seus servidores, pois o uso dos autoassinados é vedado. — minLength: `5` — maxLength: `255` |
| `enderecos[].secret` | string |  | Palavra secreta para o endereço, para garantir que o POST sejam dos Correios. Definindo a palavra secreta, será encaminhado no header o atributo x-correios-signature, um resumo HMAC — minLength: `8` — maxLength: `40` |
| `enderecos[].token` | string |  | Caso o seu endereço exija um token, Authorization: Bearer <token> — minLength: `8` — maxLength: `4000` |
| `enderecos[].certificadoDigital` | string |  | Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N — minLength: `1` — maxLength: `1` |
| `language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `email` | string (email) | ✔ | Email do responsável — minLength: `5` — maxLength: `100` |

```json
{
  "descricao": "string",
  "enderecos": [
    {
      "nome": "string",
      "tipoEventos": [
        {
          "tipo": "string"
        }
      ],
      "url": "string",
      "secret": "string",
      "token": "string",
      "certificadoDigital": "string"
    }
  ],
  "language": "PT",
  "email": "string"
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Quando criado uma nova assinatura. | `AssinaturaResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 201:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | integer (int64) |  | Identificador — maxLength: `8` |
| `descricao` | string |  | Descrição da assinatura — minLength: `3` — maxLength: `100` |
| `idServicoWebhook` | string |  | Identificação do serviço de webhook — minLength: `3` — maxLength: `40` |
| `email` | string |  | Email do responsavel pela assinatura — minLength: `5` — maxLength: `100` |
| `status` | string |  | Status da assinatura. C: Criada; A: Ativada; D: Desativado pelo assinante; X: Cancelado pelo Correios — Valores: `C`, `A`, `D`, `X` |
| `language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `enderecos` | array<objeto `EnderecoResponse`> |  | Lista de endereços da urls para envio dos hooks — minItems: `1` — maxItems: `5` |
| `enderecos[].id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `enderecos[].nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `enderecos[].tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `enderecos[].tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `enderecos[].tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `enderecos[].url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `enderecos[].status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `enderecos[].motivo` | string |  |  |
| `enderecos[].secret` | string |  |  |
| `enderecos[].token` | string |  |  |
| `enderecos[].certificadoDigital` | string |  |  |
| `enderecos[].criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `enderecos[].atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |
| `contratos` | array<objeto `ContratoResponse`> |  | Lista de contratos — minItems: `1` — maxItems: `20` |
| `contratos[].numero` | string | ✔ | minLength: `1` |
| `contratos[].nuDr` | integer (int64) | ✔ |  |
| `contratos[].status` | string |  | Status do contrato. A: Ativo — Valores: `A` |
| `contratos[].criadoEm` | string (date-time) | ✔ |  |
| `contratos[].atualizadoEm` | string (date-time) |  |  |
| `assinante` | objeto `AssinanteResponse` |  |  |
| `assinante.user` | string |  |  |
| `assinante.perfil` | string |  |  |
| `assinante.cnpj` | string |  |  |
| `assinante.pjInternacional` | integer (int64) |  |  |
| `assinante.cpf` | string |  |  |
| `assinante.cie` | string |  |  |
| `assinante.nuContrato` | string |  |  |
| `criadaEm` | string (date-time) |  | Data e hora de criação da assinatura |
| `atualizadaEm` | string (date-time) |  | Data e hora de atualização da assinatura |

#### 2.6 `GET /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}`

**Consulta uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaAssinatura`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | Identificador da assinatura |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `AssinaturaResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | integer (int64) |  | Identificador — maxLength: `8` |
| `descricao` | string |  | Descrição da assinatura — minLength: `3` — maxLength: `100` |
| `idServicoWebhook` | string |  | Identificação do serviço de webhook — minLength: `3` — maxLength: `40` |
| `email` | string |  | Email do responsavel pela assinatura — minLength: `5` — maxLength: `100` |
| `status` | string |  | Status da assinatura. C: Criada; A: Ativada; D: Desativado pelo assinante; X: Cancelado pelo Correios — Valores: `C`, `A`, `D`, `X` |
| `language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `enderecos` | array<objeto `EnderecoResponse`> |  | Lista de endereços da urls para envio dos hooks — minItems: `1` — maxItems: `5` |
| `enderecos[].id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `enderecos[].nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `enderecos[].tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `enderecos[].tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `enderecos[].tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `enderecos[].url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `enderecos[].status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `enderecos[].motivo` | string |  |  |
| `enderecos[].secret` | string |  |  |
| `enderecos[].token` | string |  |  |
| `enderecos[].certificadoDigital` | string |  |  |
| `enderecos[].criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `enderecos[].atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |
| `contratos` | array<objeto `ContratoResponse`> |  | Lista de contratos — minItems: `1` — maxItems: `20` |
| `contratos[].numero` | string | ✔ | minLength: `1` |
| `contratos[].nuDr` | integer (int64) | ✔ |  |
| `contratos[].status` | string |  | Status do contrato. A: Ativo — Valores: `A` |
| `contratos[].criadoEm` | string (date-time) | ✔ |  |
| `contratos[].atualizadoEm` | string (date-time) |  |  |
| `assinante` | objeto `AssinanteResponse` |  |  |
| `assinante.user` | string |  |  |
| `assinante.perfil` | string |  |  |
| `assinante.cnpj` | string |  |  |
| `assinante.pjInternacional` | integer (int64) |  |  |
| `assinante.cpf` | string |  |  |
| `assinante.cie` | string |  |  |
| `assinante.nuContrato` | string |  |  |
| `criadaEm` | string (date-time) |  | Data e hora de criação da assinatura |
| `atualizadaEm` | string (date-time) |  | Data e hora de atualização da assinatura |

#### 2.7 `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}`

**Altera descrição, email e língua de uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `alteraAssinatura`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |

Exemplo de chamada:

```http
PATCH https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40
Authorization: Bearer <token>
```

**Corpo da requisição** (obrigatório) — objeto `AssinaturaPatch`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `descricao` | string |  | Descricão da assinatura — minLength: `3` — maxLength: `100` |
| `language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `email` | string (email) |  | E-mail do responsável — minLength: `5` — maxLength: `100` |

```json
{
  "descricao": "string",
  "language": "PT",
  "email": "string"
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.8 `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/teste`

**Envia uma mensagem de teste, para os endereços cadastrados.**

Antes de apontar o webhook para seu ambiente de produção, recomendamos testá-lo com o RequestBin. Esta ferramenta gera uma URL temporária onde você pode inspecionar as requisições realizadas.

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/teste`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/teste`
- Header: `Authorization: Bearer <token>`
- operationId: `enviaEventoTeste`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/teste
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Quando recebido com sucesso. |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.9 `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/ativa`

**Ativa uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/ativa`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/ativa`
- Header: `Authorization: Bearer <token>`
- operationId: `ativaAssinatura`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |

Exemplo de chamada:

```http
PATCH https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/ativa
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Quando ativado com sucesso. |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.10 `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/desativa`

**Desativa uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/desativa`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/desativa`
- Header: `Authorization: Bearer <token>`
- operationId: `desativaAssinatura`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |

Exemplo de chamada:

```http
PATCH https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/desativa
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | Quando desativado com sucesso. |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.11 `GET /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos`

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos`
- Header: `Authorization: Bearer <token>`
- operationId: `listaContratos`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ |  |
| `idAssinatura` | path | integer (int64) | ✔ |  |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/contratos
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | array<objeto `ContratoResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].numero` | string | ✔ | minLength: `1` |
| `[].nuDr` | integer (int64) | ✔ |  |
| `[].status` | string |  | Status do contrato. A: Ativo — Valores: `A` |
| `[].criadoEm` | string (date-time) | ✔ |  |
| `[].atualizadoEm` | string (date-time) |  |  |

#### 2.12 `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos`

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `adicionaContrato`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ |  |
| `idAssinatura` | path | integer (int64) | ✔ |  |

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/contratos
Authorization: Bearer <token>
```

**Corpo da requisição** (obrigatório) — objeto `ContratoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string | ✔ | Número do contrato — minLength: `10` — maxLength: `12` |

```json
{
  "numero": "string"
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Created |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

### Assinaturas - endereços

Endereços de uma assinatura

#### 2.13 `GET /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos`

**Lista os endereços de uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos`
- Header: `Authorization: Bearer <token>`
- operationId: `listaEnderecos`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | array<objeto `EnderecoResponse`> |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `[].nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `[].tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `[].tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `[].tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `[].url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `[].status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `[].motivo` | string |  |  |
| `[].secret` | string |  |  |
| `[].token` | string |  |  |
| `[].certificadoDigital` | string |  |  |
| `[].criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `[].atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |

#### 2.14 `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos`

**Adiciona um novo endereço para uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `adicionaEndereco`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos
Authorization: Bearer <token>
```

**Corpo da requisição** (obrigatório) — objeto `EnderecoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string | ✔ | Nome/label de identificação do endereço — minLength: `3` — maxLength: `100` |
| `tipoEventos` | array<objeto `TipoEventoInput`> | ✔ | minItems: `1` — maxItems: `2147483647` |
| `tipoEventos[].tipo` | string | ✔ | Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado. — minLength: `3` — maxLength: `30` |
| `url` | string | ✔ | URL do endereço. Prefira que seu endpoint suporte HTTPS, apesar de a plataforma suportar HTTP. Apenas as portas 80 (HTTP) e 443 (HTTPS) são passíveis de utilização. Utilize validação da autenticidade no seu endpoint através de um usuário e senha (basic auth) ou token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Optando por receber os webhook via HTTPS, certifique-se de usar um certificado digital emitido por Autoridade Certificadora reconhecida, em seus servidores, pois o uso dos autoassinados é vedado. — minLength: `5` — maxLength: `255` |
| `secret` | string |  | Palavra secreta para o endereço, para garantir que o POST sejam dos Correios. Definindo a palavra secreta, será encaminhado no header o atributo x-correios-signature, um resumo HMAC — minLength: `8` — maxLength: `40` |
| `token` | string |  | Caso o seu endereço exija um token, Authorization: Bearer <token> — minLength: `8` — maxLength: `4000` |
| `certificadoDigital` | string |  | Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N — minLength: `1` — maxLength: `1` |

```json
{
  "nome": "string",
  "tipoEventos": [
    {
      "tipo": "string"
    }
  ],
  "url": "string",
  "secret": "string",
  "token": "string",
  "certificadoDigital": "string"
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Quando adicionado com sucesso. |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.15 `GET /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}`

**Consulta um endereço de uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaEndereco`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEndereco` | path | integer (int32) | ✔ | identificador do endereço |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `EnderecoResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `motivo` | string |  |  |
| `secret` | string |  |  |
| `token` | string |  |  |
| `certificadoDigital` | string |  |  |
| `criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |

#### 2.16 `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}`

**Altera nome, url e o secret de um endereço de uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `alteraEndereco`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEndereco` | path | integer (int32) | ✔ | identificador do endereço |

Exemplo de chamada:

```http
PATCH https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1
Authorization: Bearer <token>
```

**Corpo da requisição** (obrigatório) — objeto `EnderecoPatch`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string |  | Nome/label para identificação do endereço — minLength: `3` — maxLength: `100` |
| `url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endpoint suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). Validação da autenticidade da requisição através de um usuário e senha (basic auth) OU token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `secret` | string |  | Defina uma palavra secreta para o endereco do webhook, para garantir que o POST sejam dos Correios. — minLength: `8` — maxLength: `40` |
| `token` | string |  | Authorization: Bearer <token> — minLength: `8` — maxLength: `4000` |
| `certificadoDigital` | string |  | Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N — minLength: `1` — maxLength: `1` |

```json
{
  "nome": "string",
  "url": "string",
  "secret": "string",
  "token": "string",
  "certificadoDigital": "string"
}
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.17 `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/ativa`

**Ativa um endereço existente da assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/ativa`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/ativa`
- Header: `Authorization: Bearer <token>`
- operationId: `ativaEndereco`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEndereco` | path | integer (int32) | ✔ | identificador do endereço |

Exemplo de chamada:

```http
PATCH https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1/ativa
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.18 `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/desativa`

**Desativa um endereço existente da assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/desativa`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/desativa`
- Header: `Authorization: Bearer <token>`
- operationId: `desativaEndereco`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEndereco` | path | integer (int32) | ✔ | identificador do endereço |

Exemplo de chamada:

```http
PATCH https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1/desativa
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.19 `PUT /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos`

**Essa operação substitui os tipos de eventos informados pelos existentes do endereço**

Os tipos de eventos existentes que não forem informados na requisição serão removidos do endereço.

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `modificaTipoEvento`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ |  |
| `idAssinatura` | path | integer (int64) | ✔ |  |
| `idEndereco` | path | integer (int32) | ✔ |  |

Exemplo de chamada:

```http
PUT https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1/tipo-eventos
Authorization: Bearer <token>
```

**Corpo da requisição** (obrigatório) — array<objeto `TipoEventoOperacaoInput`>

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `[].tipo` | string | ✔ | Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado. — minLength: `1` — maxLength: `30` |

```json
[
  {
    "tipo": "string"
  }
]
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.20 `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}`

**Adiciona um novo tipo de evento para um endereço.**

Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado.

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}`
- Header: `Authorization: Bearer <token>`
- operationId: `adicionaTipoEvento`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEndereco` | path | integer (int32) | ✔ | identificador do endereco |
| `tpEvento` | path | string | ✔ | Tipo de Evento. Informando * (asterisco) é valido para todos os tipos de eventos. |

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1/tipo-eventos/BDE-1
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `201` | Quando adicionado com sucesso. |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.21 `DELETE /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}`

**Exclui um tipo de evento de um endereço**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos/{tpEvento}`
- Header: `Authorization: Bearer <token>`
- operationId: `deleteTipoEvento`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEndereco` | path | integer (int32) | ✔ | identificador do endereço |
| `tpEvento` | path | string | ✔ | Tipo de Evento |

Exemplo de chamada:

```http
DELETE https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1/tipo-eventos/BDE-1
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `202` | Accepted |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

### Eventos

#### 2.22 `GET /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos`

**Lista os eventos recebidos**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos`
- Header: `Authorization: Bearer <token>`
- operationId: `listaEventos`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `criadoEmInicial` | query | string (date-time) | ✔ | Data e hora inicial que o evento foi criado em |
| `criadoEmFinal` | query | string (date-time) | ✔ | Data e hora final que o evento foi criado em |
| `id` | query | string |  | Identificação do evento |
| `tpEvento` | query | string |  | Identificação do tipo de evento — minLength: `0` — maxLength: `30` |
| `idEndereco` | query | integer (int32) |  | Identificação do endereço |
| `sqEventoInicial` | query | integer (int64) |  | Sequencial do evento inicial |
| `sqEventoFinal` | query | integer (int64) |  | Sequencial do evento final |
| `sqTpEventoInicial` | query | integer (int64) |  | Sequencial do tipo de evento inicial |
| `sqTpEventoFinal` | query | integer (int64) |  | Sequencial do tipo de evento final |
| `key` | query | string |  | Identificador do item de um payload. Exemplo: código do objeto |
| `status` | query | array<string> |  | Status do evento — Valores: `CRI`, `END`, `NED`, `EXP`, `FIN`, `PEN`, `RED` |
| `page` | query | integer (int32) |  | default: `0` |
| `size` | query | integer (int32) |  | default: `50` |
| `sort` | query | array<string> |  | default: `criadoEm,desc` |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/eventos?criadoEmInicial=2026-09-01T00:00:00&criadoEmFinal=2026-09-25T23:59:59
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PagedModelEventoResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `itens` | objeto `EventoResponse` |  |  |
| `itens.id` | string |  | Identificação do evento. Identificado no header 'x-correios-hook-event-id'  — minLength: `5` — maxLength: `100` |
| `itens.idServicoWebhook` | string |  | Identificação do serviço de webhook. Identificado no header 'x-correios-hook-id'  — minLength: `5` — maxLength: `40` |
| `itens.tpEvento` | string |  | Identificação do tipo de evento. Identificado no header 'x-correios-hook-event'  — minLength: `3` — maxLength: `30` |
| `itens.idAssinatura` | integer (int64) |  | Identificação da assinatura. Identificado no header 'x-correios-hook-assinatura'  — maxLength: `8` |
| `itens.sqEvento` | integer (int64) |  | Número sequencial do evento. Identificado no header 'x-correios-hook-event'  — maxLength: `12` |
| `itens.sqTpEvento` | integer (int64) |  | Núméro sequencial do tipo de evento. Identificado no header 'x-correios-hook-event-sq'  — maxLength: `12` |
| `itens.criadoEm` | string (date-time) |  | Data e hora que foi criado evento |
| `itens.atualizadoEm` | string (date-time) |  | Data e hora que foi atualizado o evento |
| `itens.status` | string |  | Valores: `CRI`, `END`, `NED`, `EXP`, `FIN`, `PEN`, `RED` |
| `itens.keys` | array<string> |  |  |
| `itens.payload` | string |  | Conteúdo do evento — minLength: `1` — maxLength: `1000000` |
| `itens.envios` | array<objeto `EnvioResponse`> |  |  |
| `itens.envios[].idEndereco` | integer (int32) |  | Identificação do endereço — maxLength: `3` |
| `itens.envios[].status` | string |  | Status de envio do evento. ENV: Evento enviado com sucesso; REJ: Evento rejeitado pelo servidor do assinante, com retorno de status HTTP 4xx; PEN: Evento pendente de envio; EXP: Evento expirado; CRD: Evento cancelado por um redelivery; NED: Evento não enviado, não há endereços ativos; — Valores: `NED`, `ENV`, `REJ`, `PEN`, `EXP`, `CAN`, `CRD` |
| `itens.envios[].criadoEm` | string (date-time) |  |  |
| `itens.envios[].atualizadoEm` | string (date-time) |  |  |
| `itens.envios[].redelivery` | boolean |  |  |
| `itens.envios[].dhProximaTentativa` | string (date-time) |  |  |
| `itens.envios[].tentativas` | array<objeto `TentativaResponse`> |  | Tentativas de envio |
| `itens.envios[].tentativas[].realizadaEm` | string (date-time) |  | Data e hora da tentativa de envio |
| `itens.envios[].tentativas[].criadaEm` | string (date-time) |  | Data e hora de crição da tentativa de envio |
| `itens.envios[].tentativas[].status` | integer (int32) |  | Status HTTP recebida do servidor do assinante — maxLength: `3` |
| `itens.envios[].tentativas[].mensagem` | string |  | Mensagem recebida do servidor do assinante — minLength: `0` — maxLength: `4000` |
| `itens.envios[].tentativas[].tr` | integer (int64) |  | Tempo de resposta do servidor do assinante. Em millissegundos — maxLength: `8` |
| `links` | array<objeto `Link`> |  | Links das páginas |
| `links[].href` | string |  |  |
| `links[].hreflang` | string |  |  |
| `links[].title` | string |  |  |
| `links[].type` | string |  |  |
| `links[].deprecation` | string |  |  |
| `links[].profile` | string |  |  |
| `links[].name` | string |  |  |
| `links[].templated` | boolean |  |  |
| `page` | objeto `PagedModelInfo` |  |  |
| `page.size` | integer (int64) |  | Tamanho máximo de cada página |
| `page.totalElements` | integer (int64) |  | Numero total de itens |
| `page.totalPages` | integer (int64) |  | Numero total de páginas |
| `page.number` | integer (int64) |  | Numero da página atual |

#### 2.23 `GET /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/{idEvento}`

**Consulta um evento recebido pelo identificador**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/{idEvento}`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/{idEvento}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaEvento`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEvento` | path | string | ✔ | identificador do evento |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/eventos/wh-rastro-40-4425
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `EventoResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | string |  | Identificação do evento. Identificado no header 'x-correios-hook-event-id'  — minLength: `5` — maxLength: `100` |
| `idServicoWebhook` | string |  | Identificação do serviço de webhook. Identificado no header 'x-correios-hook-id'  — minLength: `5` — maxLength: `40` |
| `tpEvento` | string |  | Identificação do tipo de evento. Identificado no header 'x-correios-hook-event'  — minLength: `3` — maxLength: `30` |
| `idAssinatura` | integer (int64) |  | Identificação da assinatura. Identificado no header 'x-correios-hook-assinatura'  — maxLength: `8` |
| `sqEvento` | integer (int64) |  | Número sequencial do evento. Identificado no header 'x-correios-hook-event'  — maxLength: `12` |
| `sqTpEvento` | integer (int64) |  | Núméro sequencial do tipo de evento. Identificado no header 'x-correios-hook-event-sq'  — maxLength: `12` |
| `criadoEm` | string (date-time) |  | Data e hora que foi criado evento |
| `atualizadoEm` | string (date-time) |  | Data e hora que foi atualizado o evento |
| `status` | string |  | Valores: `CRI`, `END`, `NED`, `EXP`, `FIN`, `PEN`, `RED` |
| `keys` | array<string> |  |  |
| `payload` | string |  | Conteúdo do evento — minLength: `1` — maxLength: `1000000` |
| `envios` | array<objeto `EnvioResponse`> |  |  |
| `envios[].idEndereco` | integer (int32) |  | Identificação do endereço — maxLength: `3` |
| `envios[].status` | string |  | Status de envio do evento. ENV: Evento enviado com sucesso; REJ: Evento rejeitado pelo servidor do assinante, com retorno de status HTTP 4xx; PEN: Evento pendente de envio; EXP: Evento expirado; CRD: Evento cancelado por um redelivery; NED: Evento não enviado, não há endereços ativos; — Valores: `NED`, `ENV`, `REJ`, `PEN`, `EXP`, `CAN`, `CRD` |
| `envios[].criadoEm` | string (date-time) |  |  |
| `envios[].atualizadoEm` | string (date-time) |  |  |
| `envios[].redelivery` | boolean |  |  |
| `envios[].dhProximaTentativa` | string (date-time) |  |  |
| `envios[].tentativas` | array<objeto `TentativaResponse`> |  | Tentativas de envio |
| `envios[].tentativas[].realizadaEm` | string (date-time) |  | Data e hora da tentativa de envio |
| `envios[].tentativas[].criadaEm` | string (date-time) |  | Data e hora de crição da tentativa de envio |
| `envios[].tentativas[].status` | integer (int32) |  | Status HTTP recebida do servidor do assinante — maxLength: `3` |
| `envios[].tentativas[].mensagem` | string |  | Mensagem recebida do servidor do assinante — minLength: `0` — maxLength: `4000` |
| `envios[].tentativas[].tr` | integer (int64) |  | Tempo de resposta do servidor do assinante. Em millissegundos — maxLength: `8` |

#### 2.24 `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/redelivery`

**Reenvia mensagens de eventos de uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/redelivery`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/eventos/redelivery`
- Header: `Authorization: Bearer <token>`
- operationId: `redelivery`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `id` | query | string |  | Identificação do evento |
| `tpEvento` | query | string |  | Identificação do tipo de evento — minLength: `0` — maxLength: `30` |
| `idEndereco` | query | integer (int32) |  | Identificação do endereço |
| `sqEventoInicial` | query | integer (int64) |  | Sequencial do evento inicial |
| `sqEventoFinal` | query | integer (int64) |  | Sequencial do evento final |
| `sqTpEventoInicial` | query | integer (int64) |  | Sequencial do tipo de evento inicial |
| `sqTpEventoFinal` | query | integer (int64) |  | Sequencial do tipo de evento final |
| `criadoEmInicial` | query | string (date-time) |  |  |
| `criadoEmFinal` | query | string (date-time) |  |  |
| `key` | query | string |  | Identificador do item de um payload. Exemplo: código do objeto |
| `status` | query | array<string> |  | Status do evento — Valores: `CRI`, `END`, `NED`, `EXP`, `FIN`, `PEN`, `RED` |

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/eventos/redelivery
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

#### 2.25 `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/eventos/redelivery`

**Reenvia mensagens de eventos a um endereço de uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/eventos/redelivery`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/eventos/redelivery`
- Header: `Authorization: Bearer <token>`
- operationId: `redeliveryPorEndereco`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |
| `idEndereco` | path | integer (int32) | ✔ | identificador do endereço |
| `id` | query | string |  | Identificação do evento |
| `tpEvento` | query | string |  | Identificação do tipo de evento — minLength: `0` — maxLength: `30` |
| `sqEventoInicial` | query | integer (int64) |  | Sequencial do evento inicial |
| `sqEventoFinal` | query | integer (int64) |  | Sequencial do evento final |
| `sqTpEventoInicial` | query | integer (int64) |  | Sequencial do tipo de evento inicial |
| `sqTpEventoFinal` | query | integer (int64) |  | Sequencial do tipo de evento final |
| `criadoEmInicial` | query | string (date-time) |  |  |
| `criadoEmFinal` | query | string (date-time) |  |  |
| `key` | query | string |  | Identificador do item de um payload. Exemplo: código do objeto |
| `status` | query | array<string> |  | Status do evento — Valores: `CRI`, `END`, `NED`, `EXP`, `FIN`, `PEN`, `RED` |

Exemplo de chamada:

```http
POST https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/enderecos/1/eventos/redelivery
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK |  |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

### Sequencias

Sequencias dos eventos gerados

#### 2.26 `GET /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/sequences`

**Lista as sequências de eventos gerados para uma assinatura**

- URL (produção): `https://api.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/sequences`
- URL (homologação): `https://apihom.correios.com.br/webhook/v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/sequences`
- Header: `Authorization: Bearer <token>`
- operationId: `listaSequencias`

**Parâmetros**

| Nome | Em | Tipo | Obrig. | Descrição / regras |
|---|---|---|:-:|---|
| `idServicoWebhook` | path | string | ✔ | Identificador do serviço de webhook — minLength: `0` — maxLength: `40` |
| `idAssinatura` | path | integer (int64) | ✔ | identificador da assinatura |

Exemplo de chamada:

```http
GET https://apihom.correios.com.br/webhook/v1/servicos/wh-rastro/assinaturas/40/sequences
Authorization: Bearer <token>
```

**Respostas**

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `SequenceResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |

Campos da resposta 200:

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idServicoWebhook` | string |  | Identificador do serviço de webhook — minLength: `5` — maxLength: `40` |
| `idAssinatura` | integer (int64) |  | Identificador da assinatura — maxLength: `8` |
| `sqEvento` | integer (int64) |  | Último sequencial gerado — maxLength: `12` |
| `dh` | string (date-time) |  | Data e hora da último evento gerado |
| `tipoEventos` | array<objeto `SequenceTipoEventoResponse`> |  |  |
| `tipoEventos[].tpEvento` | string |  | Identificador do tipo de evento — minLength: `3` — maxLength: `30` |
| `tipoEventos[].sqTpEvento` | integer (int64) |  | Último sequencial gerado dotipo de evento — maxLength: `12` |
| `tipoEventos[].dh` | string (date-time) |  | Data e hora da último evento gerado |

## 3. Schemas (todos os campos expandidos)

### `TipoEventoOperacaoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tipo` | string | ✔ | Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado. — minLength: `1` — maxLength: `30` |

### `AssinaturaInput`

Assinatura

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `descricao` | string | ✔ | Descrição da assinatura — minLength: `3` — maxLength: `100` |
| `enderecos` | array<objeto `EnderecoInput`> | ✔ | minItems: `1` — maxItems: `5` |
| `enderecos[].nome` | string | ✔ | Nome/label de identificação do endereço — minLength: `3` — maxLength: `100` |
| `enderecos[].tipoEventos` | array<objeto `TipoEventoInput`> | ✔ | minItems: `1` — maxItems: `2147483647` |
| `enderecos[].tipoEventos[].tipo` | string | ✔ | Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado. — minLength: `3` — maxLength: `30` |
| `enderecos[].url` | string | ✔ | URL do endereço. Prefira que seu endpoint suporte HTTPS, apesar de a plataforma suportar HTTP. Apenas as portas 80 (HTTP) e 443 (HTTPS) são passíveis de utilização. Utilize validação da autenticidade no seu endpoint através de um usuário e senha (basic auth) ou token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Optando por receber os webhook via HTTPS, certifique-se de usar um certificado digital emitido por Autoridade Certificadora reconhecida, em seus servidores, pois o uso dos autoassinados é vedado. — minLength: `5` — maxLength: `255` |
| `enderecos[].secret` | string |  | Palavra secreta para o endereço, para garantir que o POST sejam dos Correios. Definindo a palavra secreta, será encaminhado no header o atributo x-correios-signature, um resumo HMAC — minLength: `8` — maxLength: `40` |
| `enderecos[].token` | string |  | Caso o seu endereço exija um token, Authorization: Bearer <token> — minLength: `8` — maxLength: `4000` |
| `enderecos[].certificadoDigital` | string |  | Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N — minLength: `1` — maxLength: `1` |
| `language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `email` | string (email) | ✔ | Email do responsável — minLength: `5` — maxLength: `100` |

### `EnderecoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string | ✔ | Nome/label de identificação do endereço — minLength: `3` — maxLength: `100` |
| `tipoEventos` | array<objeto `TipoEventoInput`> | ✔ | minItems: `1` — maxItems: `2147483647` |
| `tipoEventos[].tipo` | string | ✔ | Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado. — minLength: `3` — maxLength: `30` |
| `url` | string | ✔ | URL do endereço. Prefira que seu endpoint suporte HTTPS, apesar de a plataforma suportar HTTP. Apenas as portas 80 (HTTP) e 443 (HTTPS) são passíveis de utilização. Utilize validação da autenticidade no seu endpoint através de um usuário e senha (basic auth) ou token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Optando por receber os webhook via HTTPS, certifique-se de usar um certificado digital emitido por Autoridade Certificadora reconhecida, em seus servidores, pois o uso dos autoassinados é vedado. — minLength: `5` — maxLength: `255` |
| `secret` | string |  | Palavra secreta para o endereço, para garantir que o POST sejam dos Correios. Definindo a palavra secreta, será encaminhado no header o atributo x-correios-signature, um resumo HMAC — minLength: `8` — maxLength: `40` |
| `token` | string |  | Caso o seu endereço exija um token, Authorization: Bearer <token> — minLength: `8` — maxLength: `4000` |
| `certificadoDigital` | string |  | Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N — minLength: `1` — maxLength: `1` |

### `TipoEventoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tipo` | string | ✔ | Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado. — minLength: `3` — maxLength: `30` |

### `AssinanteResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `user` | string |  |  |
| `perfil` | string |  |  |
| `cnpj` | string |  |  |
| `pjInternacional` | integer (int64) |  |  |
| `cpf` | string |  |  |
| `cie` | string |  |  |
| `nuContrato` | string |  |  |

### `AssinaturaResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | integer (int64) |  | Identificador — maxLength: `8` |
| `descricao` | string |  | Descrição da assinatura — minLength: `3` — maxLength: `100` |
| `idServicoWebhook` | string |  | Identificação do serviço de webhook — minLength: `3` — maxLength: `40` |
| `email` | string |  | Email do responsavel pela assinatura — minLength: `5` — maxLength: `100` |
| `status` | string |  | Status da assinatura. C: Criada; A: Ativada; D: Desativado pelo assinante; X: Cancelado pelo Correios — Valores: `C`, `A`, `D`, `X` |
| `language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `enderecos` | array<objeto `EnderecoResponse`> |  | Lista de endereços da urls para envio dos hooks — minItems: `1` — maxItems: `5` |
| `enderecos[].id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `enderecos[].nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `enderecos[].tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `enderecos[].tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `enderecos[].tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `enderecos[].url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `enderecos[].status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `enderecos[].motivo` | string |  |  |
| `enderecos[].secret` | string |  |  |
| `enderecos[].token` | string |  |  |
| `enderecos[].certificadoDigital` | string |  |  |
| `enderecos[].criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `enderecos[].atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |
| `contratos` | array<objeto `ContratoResponse`> |  | Lista de contratos — minItems: `1` — maxItems: `20` |
| `contratos[].numero` | string | ✔ | minLength: `1` |
| `contratos[].nuDr` | integer (int64) | ✔ |  |
| `contratos[].status` | string |  | Status do contrato. A: Ativo — Valores: `A` |
| `contratos[].criadoEm` | string (date-time) | ✔ |  |
| `contratos[].atualizadoEm` | string (date-time) |  |  |
| `assinante` | objeto `AssinanteResponse` |  |  |
| `assinante.user` | string |  |  |
| `assinante.perfil` | string |  |  |
| `assinante.cnpj` | string |  |  |
| `assinante.pjInternacional` | integer (int64) |  |  |
| `assinante.cpf` | string |  |  |
| `assinante.cie` | string |  |  |
| `assinante.nuContrato` | string |  |  |
| `criadaEm` | string (date-time) |  | Data e hora de criação da assinatura |
| `atualizadaEm` | string (date-time) |  | Data e hora de atualização da assinatura |

### `ContratoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string | ✔ | minLength: `1` |
| `nuDr` | integer (int64) | ✔ |  |
| `status` | string |  | Status do contrato. A: Ativo — Valores: `A` |
| `criadoEm` | string (date-time) | ✔ |  |
| `atualizadoEm` | string (date-time) |  |  |

### `EnderecoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | integer (int32) |  | Identificador do endereço — maxLength: `3` |
| `nome` | string |  | Um nome/apelido para o endereço — minLength: `3` — maxLength: `100` |
| `tipoEventos` | array<objeto `TipoEventoAssinadoResponse`> |  | Os tipos de eventos do webhook assinados — minItems: `1` — maxItems: `4000` |
| `tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `tipoEventos[].criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |
| `url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endereço suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). E que tenha validação da autenticidade da requisição através de um usuário e senha (basic auth). Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `status` | string |  | Status do Endereço. C: Criado; A: Ativado; D: Desativado pelo assinante; X: Cancelado pelo Correios; S: Suspenso por inatividade, baixa disponibilidade — Valores: `C`, `A`, `D`, `X`, `S` |
| `motivo` | string |  |  |
| `secret` | string |  |  |
| `token` | string |  |  |
| `certificadoDigital` | string |  |  |
| `criadoEm` | string (date-time) |  | Data e hora de criação do endereço |
| `atualizadoEm` | string (date-time) |  | Data e hora de atualização do endereço |

### `TipoEventoAssinadoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `criadoEm` | string (date-time) |  | Data e hora que foi adicionando o tipo de evento |

### `ContratoInput`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `numero` | string | ✔ | Número do contrato — minLength: `10` — maxLength: `12` |

### `AssinaturaPatch`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `descricao` | string |  | Descricão da assinatura — minLength: `3` — maxLength: `100` |
| `language` | string |  | Língua. PT: Português; ES: Espanhol; EN: Inglês — Valores: `PT`, `ES`, `EN` |
| `email` | string (email) |  | E-mail do responsável — minLength: `5` — maxLength: `100` |

### `EnderecoPatch`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string |  | Nome/label para identificação do endereço — minLength: `3` — maxLength: `100` |
| `url` | string |  | URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endpoint suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). Validação da autenticidade da requisição através de um usuário e senha (basic auth) OU token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado. — minLength: `5` — maxLength: `255` |
| `secret` | string |  | Defina uma palavra secreta para o endereco do webhook, para garantir que o POST sejam dos Correios. — minLength: `8` — maxLength: `40` |
| `token` | string |  | Authorization: Bearer <token> — minLength: `8` — maxLength: `4000` |
| `certificadoDigital` | string |  | Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N — minLength: `1` — maxLength: `1` |

### `ServicoWebhookResponse`

Serviços disponíveis de webhook.

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | string |  | Identificador do serviço de webhook — minLength: `5` — maxLength: `40` |
| `nome` | string |  | Nome do serviço de webhook — minLength: `5` — maxLength: `60` |
| `descricao` | string |  | Descrição detalhada do serviço de webhook — minLength: `5` — maxLength: `100` |
| `disponibilizacao` | string |  | Valores: `I`, `E`, `A` |
| `nuApi` | integer (int32) |  | Número da API que está associado ao serviço de webhook. É necessário que o token a ser utilizado tenha essa API autorizada — maxLength: `5` |
| `tipoEventos` | array<objeto `TipoEventoResponse`> |  | Lista dos tipoEventos que estão disponíveis para a assinatura — minItems: `1` — maxItems: `2147483647` |
| `tipoEventos[].tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `tipoEventos[].descricao` | string |  | Descrição do tipo de evento — minLength: `3` — maxLength: `100` |
| `tipoEventos[].desativadoEm` | string (date-time) |  |  |
| `status` | string |  | Status do serviço de webhook — Valores: `C`, `A`, `D`, `P` |
| `exigeContrato` | string |  | Valores: `S`, `N` |

### `TipoEventoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tipo` | string |  | Código do tipo de evento — minLength: `3` — maxLength: `30` |
| `descricao` | string |  | Descrição do tipo de evento — minLength: `3` — maxLength: `100` |
| `desativadoEm` | string (date-time) |  |  |

### `SequenceResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idServicoWebhook` | string |  | Identificador do serviço de webhook — minLength: `5` — maxLength: `40` |
| `idAssinatura` | integer (int64) |  | Identificador da assinatura — maxLength: `8` |
| `sqEvento` | integer (int64) |  | Último sequencial gerado — maxLength: `12` |
| `dh` | string (date-time) |  | Data e hora da último evento gerado |
| `tipoEventos` | array<objeto `SequenceTipoEventoResponse`> |  |  |
| `tipoEventos[].tpEvento` | string |  | Identificador do tipo de evento — minLength: `3` — maxLength: `30` |
| `tipoEventos[].sqTpEvento` | integer (int64) |  | Último sequencial gerado dotipo de evento — maxLength: `12` |
| `tipoEventos[].dh` | string (date-time) |  | Data e hora da último evento gerado |

### `SequenceTipoEventoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `tpEvento` | string |  | Identificador do tipo de evento — minLength: `3` — maxLength: `30` |
| `sqTpEvento` | integer (int64) |  | Último sequencial gerado dotipo de evento — maxLength: `12` |
| `dh` | string (date-time) |  | Data e hora da último evento gerado |

### `EnvioResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idEndereco` | integer (int32) |  | Identificação do endereço — maxLength: `3` |
| `status` | string |  | Status de envio do evento. ENV: Evento enviado com sucesso; REJ: Evento rejeitado pelo servidor do assinante, com retorno de status HTTP 4xx; PEN: Evento pendente de envio; EXP: Evento expirado; CRD: Evento cancelado por um redelivery; NED: Evento não enviado, não há endereços ativos; — Valores: `NED`, `ENV`, `REJ`, `PEN`, `EXP`, `CAN`, `CRD` |
| `criadoEm` | string (date-time) |  |  |
| `atualizadoEm` | string (date-time) |  |  |
| `redelivery` | boolean |  |  |
| `dhProximaTentativa` | string (date-time) |  |  |
| `tentativas` | array<objeto `TentativaResponse`> |  | Tentativas de envio |
| `tentativas[].realizadaEm` | string (date-time) |  | Data e hora da tentativa de envio |
| `tentativas[].criadaEm` | string (date-time) |  | Data e hora de crição da tentativa de envio |
| `tentativas[].status` | integer (int32) |  | Status HTTP recebida do servidor do assinante — maxLength: `3` |
| `tentativas[].mensagem` | string |  | Mensagem recebida do servidor do assinante — minLength: `0` — maxLength: `4000` |
| `tentativas[].tr` | integer (int64) |  | Tempo de resposta do servidor do assinante. Em millissegundos — maxLength: `8` |

### `EventoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | string |  | Identificação do evento. Identificado no header 'x-correios-hook-event-id'  — minLength: `5` — maxLength: `100` |
| `idServicoWebhook` | string |  | Identificação do serviço de webhook. Identificado no header 'x-correios-hook-id'  — minLength: `5` — maxLength: `40` |
| `tpEvento` | string |  | Identificação do tipo de evento. Identificado no header 'x-correios-hook-event'  — minLength: `3` — maxLength: `30` |
| `idAssinatura` | integer (int64) |  | Identificação da assinatura. Identificado no header 'x-correios-hook-assinatura'  — maxLength: `8` |
| `sqEvento` | integer (int64) |  | Número sequencial do evento. Identificado no header 'x-correios-hook-event'  — maxLength: `12` |
| `sqTpEvento` | integer (int64) |  | Núméro sequencial do tipo de evento. Identificado no header 'x-correios-hook-event-sq'  — maxLength: `12` |
| `criadoEm` | string (date-time) |  | Data e hora que foi criado evento |
| `atualizadoEm` | string (date-time) |  | Data e hora que foi atualizado o evento |
| `status` | string |  | Valores: `CRI`, `END`, `NED`, `EXP`, `FIN`, `PEN`, `RED` |
| `keys` | array<string> |  |  |
| `payload` | string |  | Conteúdo do evento — minLength: `1` — maxLength: `1000000` |
| `envios` | array<objeto `EnvioResponse`> |  |  |
| `envios[].idEndereco` | integer (int32) |  | Identificação do endereço — maxLength: `3` |
| `envios[].status` | string |  | Status de envio do evento. ENV: Evento enviado com sucesso; REJ: Evento rejeitado pelo servidor do assinante, com retorno de status HTTP 4xx; PEN: Evento pendente de envio; EXP: Evento expirado; CRD: Evento cancelado por um redelivery; NED: Evento não enviado, não há endereços ativos; — Valores: `NED`, `ENV`, `REJ`, `PEN`, `EXP`, `CAN`, `CRD` |
| `envios[].criadoEm` | string (date-time) |  |  |
| `envios[].atualizadoEm` | string (date-time) |  |  |
| `envios[].redelivery` | boolean |  |  |
| `envios[].dhProximaTentativa` | string (date-time) |  |  |
| `envios[].tentativas` | array<objeto `TentativaResponse`> |  | Tentativas de envio |
| `envios[].tentativas[].realizadaEm` | string (date-time) |  | Data e hora da tentativa de envio |
| `envios[].tentativas[].criadaEm` | string (date-time) |  | Data e hora de crição da tentativa de envio |
| `envios[].tentativas[].status` | integer (int32) |  | Status HTTP recebida do servidor do assinante — maxLength: `3` |
| `envios[].tentativas[].mensagem` | string |  | Mensagem recebida do servidor do assinante — minLength: `0` — maxLength: `4000` |
| `envios[].tentativas[].tr` | integer (int64) |  | Tempo de resposta do servidor do assinante. Em millissegundos — maxLength: `8` |

### `TentativaResponse`

Tentativas de envio

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `realizadaEm` | string (date-time) |  | Data e hora da tentativa de envio |
| `criadaEm` | string (date-time) |  | Data e hora de crição da tentativa de envio |
| `status` | integer (int32) |  | Status HTTP recebida do servidor do assinante — maxLength: `3` |
| `mensagem` | string |  | Mensagem recebida do servidor do assinante — minLength: `0` — maxLength: `4000` |
| `tr` | integer (int64) |  | Tempo de resposta do servidor do assinante. Em millissegundos — maxLength: `8` |

### `PagedModelEventoResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `itens` | objeto `EventoResponse` |  |  |
| `itens.id` | string |  | Identificação do evento. Identificado no header 'x-correios-hook-event-id'  — minLength: `5` — maxLength: `100` |
| `itens.idServicoWebhook` | string |  | Identificação do serviço de webhook. Identificado no header 'x-correios-hook-id'  — minLength: `5` — maxLength: `40` |
| `itens.tpEvento` | string |  | Identificação do tipo de evento. Identificado no header 'x-correios-hook-event'  — minLength: `3` — maxLength: `30` |
| `itens.idAssinatura` | integer (int64) |  | Identificação da assinatura. Identificado no header 'x-correios-hook-assinatura'  — maxLength: `8` |
| `itens.sqEvento` | integer (int64) |  | Número sequencial do evento. Identificado no header 'x-correios-hook-event'  — maxLength: `12` |
| `itens.sqTpEvento` | integer (int64) |  | Núméro sequencial do tipo de evento. Identificado no header 'x-correios-hook-event-sq'  — maxLength: `12` |
| `itens.criadoEm` | string (date-time) |  | Data e hora que foi criado evento |
| `itens.atualizadoEm` | string (date-time) |  | Data e hora que foi atualizado o evento |
| `itens.status` | string |  | Valores: `CRI`, `END`, `NED`, `EXP`, `FIN`, `PEN`, `RED` |
| `itens.keys` | array<string> |  |  |
| `itens.payload` | string |  | Conteúdo do evento — minLength: `1` — maxLength: `1000000` |
| `itens.envios` | array<objeto `EnvioResponse`> |  |  |
| `itens.envios[].idEndereco` | integer (int32) |  | Identificação do endereço — maxLength: `3` |
| `itens.envios[].status` | string |  | Status de envio do evento. ENV: Evento enviado com sucesso; REJ: Evento rejeitado pelo servidor do assinante, com retorno de status HTTP 4xx; PEN: Evento pendente de envio; EXP: Evento expirado; CRD: Evento cancelado por um redelivery; NED: Evento não enviado, não há endereços ativos; — Valores: `NED`, `ENV`, `REJ`, `PEN`, `EXP`, `CAN`, `CRD` |
| `itens.envios[].criadoEm` | string (date-time) |  |  |
| `itens.envios[].atualizadoEm` | string (date-time) |  |  |
| `itens.envios[].redelivery` | boolean |  |  |
| `itens.envios[].dhProximaTentativa` | string (date-time) |  |  |
| `itens.envios[].tentativas` | array<objeto `TentativaResponse`> |  | Tentativas de envio |
| `itens.envios[].tentativas[].realizadaEm` | string (date-time) |  | Data e hora da tentativa de envio |
| `itens.envios[].tentativas[].criadaEm` | string (date-time) |  | Data e hora de crição da tentativa de envio |
| `itens.envios[].tentativas[].status` | integer (int32) |  | Status HTTP recebida do servidor do assinante — maxLength: `3` |
| `itens.envios[].tentativas[].mensagem` | string |  | Mensagem recebida do servidor do assinante — minLength: `0` — maxLength: `4000` |
| `itens.envios[].tentativas[].tr` | integer (int64) |  | Tempo de resposta do servidor do assinante. Em millissegundos — maxLength: `8` |
| `links` | array<objeto `Link`> |  | Links das páginas |
| `links[].href` | string |  |  |
| `links[].hreflang` | string |  |  |
| `links[].title` | string |  |  |
| `links[].type` | string |  |  |
| `links[].deprecation` | string |  |  |
| `links[].profile` | string |  |  |
| `links[].name` | string |  |  |
| `links[].templated` | boolean |  |  |
| `page` | objeto `PagedModelInfo` |  |  |
| `page.size` | integer (int64) |  | Tamanho máximo de cada página |
| `page.totalElements` | integer (int64) |  | Numero total de itens |
| `page.totalPages` | integer (int64) |  | Numero total de páginas |
| `page.number` | integer (int64) |  | Numero da página atual |

### `MessageResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `msgs` | array<string> |  | Mensagens |
| `date` | string (date-time) |  | Data e hora de ocorrência do evento. |
| `method` | string |  | Método HTTP que foi requisitado. |
| `path` | string |  | URL que foi requisitada. |
| `causa` | string |  | A causa da mensagem. |
| `stackTrace` | string |  | Detalhe da mensagem. |

### `PagedModelInfo`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `size` | integer (int64) |  | Tamanho máximo de cada página |
| `totalElements` | integer (int64) |  | Numero total de itens |
| `totalPages` | integer (int64) |  | Numero total de páginas |
| `number` | integer (int64) |  | Numero da página atual |

### `Link`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `href` | string |  |  |
| `hreflang` | string |  |  |
| `title` | string |  |  |
| `type` | string |  |  |
| `deprecation` | string |  |  |
| `profile` | string |  |  |
| `name` | string |  |  |
| `templated` | boolean |  |  |

## 4. Códigos HTTP (API de gerenciamento)

| Código | Significado |
|---|---|
| `200` | Consulta/alteração realizada. |
| `201` | Recurso criado (assinatura, endereço, tipo de evento, teste enviado). |
| `202` | Exclusão aceita (tipo de evento). |
| `400` | Requisição inválida. Ver `MessageResponse.msgs`. |
| `401` | Token ausente, inválido ou expirado. |
| `403` | Sem permissão para o serviço/contrato. |
| `500` | Erro interno dos Correios. |

## 5. JSON Schema completo (referências expandidas) — corpos de requisição

### `PUT /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}/tipo-eventos`

```json
{
  "type": "array",
  "items": {
    "title": "TipoEventoOperacaoInput",
    "type": "object",
    "properties": {
      "tipo": {
        "type": "string",
        "description": "Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado.",
        "maxLength": 30,
        "minLength": 1
      }
    },
    "required": [
      "tipo"
    ]
  }
}
```

### `POST /v1/servicos/{idServicoWebhook}/assinaturas`

```json
{
  "title": "AssinaturaInput",
  "type": "object",
  "description": "Assinatura",
  "properties": {
    "descricao": {
      "type": "string",
      "description": "Descrição da assinatura",
      "maxLength": 100,
      "minLength": 3
    },
    "enderecos": {
      "type": "array",
      "items": {
        "title": "EnderecoInput",
        "type": "object",
        "properties": {
          "nome": {
            "type": "string",
            "description": "Nome/label de identificação do endereço",
            "maxLength": 100,
            "minLength": 3
          },
          "tipoEventos": {
            "type": "array",
            "items": {
              "title": "TipoEventoInput",
              "type": "object",
              "properties": {
                "tipo": {
                  "type": "string",
                  "description": "Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado.",
                  "maxLength": 30,
                  "minLength": 3
                }
              },
              "required": [
                "tipo"
              ]
            },
            "maxItems": 2147483647,
            "minItems": 1
          },
          "url": {
            "type": "string",
            "description": "URL do endereço. Prefira que seu endpoint suporte HTTPS, apesar de a plataforma suportar HTTP. Apenas as portas 80 (HTTP) e 443 (HTTPS) são passíveis de utilização. Utilize validação da autenticidade no seu endpoint através de um usuário e senha (basic auth) ou token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Optando por receber os webhook via HTTPS, certifique-se de usar um certificado digital emitido por Autoridade Certificadora reconhecida, em seus servidores, pois o uso dos autoassinados é vedado.",
            "maxLength": 255,
            "minLength": 5
          },
          "secret": {
            "type": "string",
            "description": "Palavra secreta para o endereço, para garantir que o POST sejam dos Correios. Definindo a palavra secreta, será encaminhado no header o atributo x-correios-signature, um resumo HMAC",
            "maxLength": 40,
            "minLength": 8
          },
          "token": {
            "type": "string",
            "description": "Caso o seu endereço exija um token, Authorization: Bearer <token>",
            "maxLength": 4000,
            "minLength": 8
          },
          "certificadoDigital": {
            "type": "string",
            "description": "Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N",
            "maxLength": 1,
            "minLength": 1
          }
        },
        "required": [
          "nome",
          "tipoEventos",
          "url"
        ]
      },
      "maxItems": 5,
      "minItems": 1
    },
    "language": {
      "type": "string",
      "description": "Língua. PT: Português; ES: Espanhol; EN: Inglês",
      "enum": [
        "PT",
        "ES",
        "EN"
      ]
    },
    "email": {
      "type": "string",
      "format": "email",
      "description": "Email do responsável",
      "maxLength": 100,
      "minLength": 5
    }
  },
  "required": [
    "descricao",
    "email",
    "enderecos"
  ]
}
```

### `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos`

```json
{
  "title": "EnderecoInput",
  "type": "object",
  "properties": {
    "nome": {
      "type": "string",
      "description": "Nome/label de identificação do endereço",
      "maxLength": 100,
      "minLength": 3
    },
    "tipoEventos": {
      "type": "array",
      "items": {
        "title": "TipoEventoInput",
        "type": "object",
        "properties": {
          "tipo": {
            "type": "string",
            "description": "Código do tipo do evento a ser assinado. Use o caractere curinga * (asterico) para assinar todos os tipos de evento. Ao adicionar o caractere curinga, substituiremos todos os tipos de eventos existentes que já tenha configurado.",
            "maxLength": 30,
            "minLength": 3
          }
        },
        "required": [
          "tipo"
        ]
      },
      "maxItems": 2147483647,
      "minItems": 1
    },
    "url": {
      "type": "string",
      "description": "URL do endereço. Prefira que seu endpoint suporte HTTPS, apesar de a plataforma suportar HTTP. Apenas as portas 80 (HTTP) e 443 (HTTPS) são passíveis de utilização. Utilize validação da autenticidade no seu endpoint através de um usuário e senha (basic auth) ou token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Optando por receber os webhook via HTTPS, certifique-se de usar um certificado digital emitido por Autoridade Certificadora reconhecida, em seus servidores, pois o uso dos autoassinados é vedado.",
      "maxLength": 255,
      "minLength": 5
    },
    "secret": {
      "type": "string",
      "description": "Palavra secreta para o endereço, para garantir que o POST sejam dos Correios. Definindo a palavra secreta, será encaminhado no header o atributo x-correios-signature, um resumo HMAC",
      "maxLength": 40,
      "minLength": 8
    },
    "token": {
      "type": "string",
      "description": "Caso o seu endereço exija um token, Authorization: Bearer <token>",
      "maxLength": 4000,
      "minLength": 8
    },
    "certificadoDigital": {
      "type": "string",
      "description": "Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N",
      "maxLength": 1,
      "minLength": 1
    }
  },
  "required": [
    "nome",
    "tipoEventos",
    "url"
  ]
}
```

### `POST /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/contratos`

```json
{
  "title": "ContratoInput",
  "type": "object",
  "properties": {
    "numero": {
      "type": "string",
      "description": "Número do contrato",
      "maxLength": 12,
      "minLength": 10
    }
  },
  "required": [
    "numero"
  ]
}
```

### `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}`

```json
{
  "title": "AssinaturaPatch",
  "type": "object",
  "properties": {
    "descricao": {
      "type": "string",
      "description": "Descricão da assinatura",
      "maxLength": 100,
      "minLength": 3
    },
    "language": {
      "type": "string",
      "description": "Língua. PT: Português; ES: Espanhol; EN: Inglês",
      "enum": [
        "PT",
        "ES",
        "EN"
      ]
    },
    "email": {
      "type": "string",
      "format": "email",
      "description": "E-mail do responsável",
      "maxLength": 100,
      "minLength": 5
    }
  }
}
```

### `PATCH /v1/servicos/{idServicoWebhook}/assinaturas/{idAssinatura}/enderecos/{idEndereco}`

```json
{
  "title": "EnderecoPatch",
  "type": "object",
  "properties": {
    "nome": {
      "type": "string",
      "description": "Nome/label para identificação do endereço",
      "maxLength": 100,
      "minLength": 3
    },
    "url": {
      "type": "string",
      "description": "URL de destino. Apesar da plataforma suportar o envio para endereços HTTP, recomendamos fortemente que seu endpoint suporte HTTPS. Não é possível configurar portas diferentes de 80 (HTTP) ou 443 (HTTPS). Validação da autenticidade da requisição através de um usuário e senha (basic auth) OU token (auth bearer). Exemplo de url com basic auth: https://username:password@example.com.Caso opte por receber os webhooks via HTTPS, você deverá usar um certificado emitido por uma autoridade certificadora reconhecida, ou seja, o certificado do seu servidor não deverá ser autoassinado.",
      "maxLength": 255,
      "minLength": 5
    },
    "secret": {
      "type": "string",
      "description": "Defina uma palavra secreta para o endereco do webhook, para garantir que o POST sejam dos Correios.",
      "maxLength": 40,
      "minLength": 8
    },
    "token": {
      "type": "string",
      "description": "Authorization: Bearer <token>",
      "maxLength": 4000,
      "minLength": 8
    },
    "certificadoDigital": {
      "type": "string",
      "description": "Habilite, se o seu endpoint exigir autenticação com certificado digital de cliente. Valor: S ou N",
      "maxLength": 1,
      "minLength": 1
    }
  }
}
```

## 6. Orientações de implementação

### 6.1 Passo a passo da adesão

1. `GET /v1/servicos` → identifique o `idServicoWebhook` (ex.: `wh-rastro`) e os tipos de evento disponíveis.
2. `POST /v1/servicos/{idServicoWebhook}/assinaturas` → crie a assinatura com `descricao`, `email` e `enderecos` (nome, `url`, `secret` e `tipoEventos`; use `"tipo": "*"` para todos). Guarde o `idAssinatura` retornado.
3. Se a autorização for por contrato, vincule-o com `POST .../assinaturas/{idAssinatura}/contratos`.
4. `POST .../assinaturas/{idAssinatura}/teste` → dispare um evento de teste e confirme o recebimento no seu endpoint.
5. Ajustes posteriores: `PATCH` na assinatura (descrição/e-mail/idioma) ou no endereço (nome/url/secret); `PUT .../tipo-eventos` substitui **todos** os tipos do endereço (os não enviados são removidos); `POST`/`DELETE .../tipo-eventos/{tpEvento}` adiciona/remove um tipo. Adicionar `*` substitui todos os tipos existentes.
6. Ativar/desativar: `PATCH .../ativa` e `.../desativa` (assinatura ou endereço).

### 6.2 Endpoint receptor (seu sistema)

1. **HTTPS na porta 443** com certificado de AC reconhecida (não usar certificado autoassinado).
2. **Validar a assinatura:** calcule `HMAC-SHA256(secret, corpo_bruto)` em hexadecimal e compare com o valor de `x-correios-signature` (formato `sha256=<hex>`), usando comparação de tempo constante. Use o **corpo bruto** exatamente como recebido — não re-serialize o JSON antes de calcular.
3. **Responder rápido:** grave o payload (fila/tabela) e responda `200` com **corpo vazio** em menos de 2 s; processe depois, de forma assíncrona.
4. **Idempotência:** o mesmo payload pode chegar mais de uma vez (retentativas e reenvios). Use `x-correios-hook-event-id` como chave única e ignore duplicados.
5. **Pacotes de tamanho variável:** trate a lista recebida dinamicamente (hoje até 100 itens, pode mudar).
6. **Códigos de resposta:** use `5xx` apenas para falhas temporárias (gera retentativa). **Nunca responda `410`**, a não ser que queira cancelar o endereço. Não redirecione (3xx) nem retorne 4xx para eventos válidos — não haverá nova tentativa.
7. **Ordem:** use `x-correios-hook-event-sq` / `x-correios-hook-event-tp-sq` para ordenar e detectar lacunas; se faltar algo, consulte `GET .../eventos` e use `.../eventos/redelivery` para pedir reenvio (há 5 dias antes do descarte).
8. **Testes:** antes de produção, aponte para uma URL de inspeção (ex.: RequestBin, como sugerido pelos Correios) e use o endpoint `/teste`.

### Exemplo (Node.js / Express)

```js
import express from 'express';
import crypto from 'crypto';

const app = express();
const SECRET = process.env.CORREIOS_WEBHOOK_SECRET;

// Importante: corpo bruto para validar o HMAC
app.post('/webhooks/correios', express.raw({ type: 'application/json', limit: '5mb' }), async (req, res) => {
  const recebido = req.get('x-correios-signature') || '';
  const esperado = 'sha256=' + crypto.createHmac('sha256', SECRET).update(req.body).digest('hex');
  const ok = recebido.length === esperado.length &&
             crypto.timingSafeEqual(Buffer.from(recebido), Buffer.from(esperado));
  if (!ok) return res.status(401).end(); // 4xx: não haverá retentativa

  const eventId = req.get('x-correios-hook-event-id');
  // Grava para processamento assíncrono; ignora se eventId já existir (idempotência)
  await salvarNaFila({ eventId, tipo: req.get('x-correios-hook-event'), payload: req.body.toString('utf8') });

  res.status(200).end(); // corpo vazio, em menos de 2 s
});
```
