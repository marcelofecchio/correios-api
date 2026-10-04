# Correios — Pré-postagem: cancelamento (`DELETE /v1/prepostagens/{id}` e `DELETE /v1/prepostagens/objeto/{codigoObjeto}`)

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prepostagem/v3/api-docs`), API **Pré-postagem** versão **2.0.32** (homologação), em 03/10/2026. Cobre os 2 endpoints **síncronos** de cancelamento. As seções 1 a 4 reproduzem a especificação; a seção 5 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 13 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-12-prepostagem-postada-api.md` · Próxima: `correios-14-prepostagem-dce-api.md`
>
> **Ainda não testado em homologação:** os valores reais de `resultadoCancelamento` e `mensagem` devem ser registrados aqui após o primeiro teste.

## 0. Resumo rápido

- **Finalidade:** cancelar **uma** pré-postagem que ainda não foi postada, identificando-a pelo `id` da pré-postagem **ou** pelo código do objeto.
- **Autenticação:** `Authorization: Bearer <token>` — API Token (`/v1/autentica/cartaopostagem`). Ver `correios-01-token-api.md`.
- **Corpo da requisição:** nenhum (diferente do cancelamento assíncrono do arquivo 07, que é um `DELETE` com corpo JSON).
- **Grupo (tag):** Pré-Postagem Nacional (Objetos Registrados).

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/prepostagem` |
| Homologação | `https://apihom.correios.com.br/prepostagem` |

| # | Método | Caminho | Função | operationId | Resposta 200 |
|---|---|---|---|---|---|
| 1 | DELETE | `/v1/prepostagens/{id}` | Cancela uma pré-postagem pelo identificador. | `cancelaPrePostagem` | `ResponseCancelamento` |
| 2 | DELETE | `/v1/prepostagens/objeto/{codigoObjeto}` | Cancela uma pré-postagem pelo código do objeto. | `cancelaPrePostagemPorObjeto` | `ResponseCancelamento` |

### Qual usar

| Situação | Endpoint |
|---|---|
| Tenho o `id` devolvido na criação (ex.: `PRnR3Wm0pDT02bN0kuyAdP0A`) | 1 — por `id` (preferível: é o identificador da própria pré-postagem) |
| Tenho só o código de rastreio (ex.: `AB043180865BR`) | 2 — por `codigoObjeto` |
| Preciso cancelar **várias** de uma vez | Cancelamento assíncrono em lote — `DELETE /v1/prepostagens/assincrona` (arquivo 07) |

### Fluxo

```
1. POST /v1/prepostagens                     → id + codigoObjeto (statusAtual = 2 - PREPOSTADO)
2. DELETE /v1/prepostagens/{id}              → cancela   ◄ este documento
   ou DELETE /v1/prepostagens/objeto/{codigoObjeto}
   → { resultadoCancelamento, mensagem, idRecibo }
3. A pré-postagem passa a statusAtual = 5 (CANCELADO); o rótulo já emitido deixa de valer
```

## 1. `DELETE /v1/prepostagens/{id}`

**Cancela uma pré-postagem pelo identificador.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/{id}`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/{id}`
- Header: `Authorization: Bearer <token>`
- operationId: `cancelaPrePostagem`

### 1.1 Parâmetros

| Nome | Em | Tipo | Obrig. | Descrição (especificação) |
|---|---|---|:-:|---|
| `id` | path | string | ✔ | Identificador da pré-postagem |
| `idCorreiosSolicitanteCancelamento` | query | string |  | Id Correios do solicitante do cancelamento da pré-postagem. |

- `id`: o valor do campo `id` devolvido na criação (24 caracteres, começa com `PR`).
- `idCorreiosSolicitanteCancelamento`: opcional. Em integração de cliente externo normalmente não é enviado (o solicitante é identificado pelo token).

```http
DELETE https://apihom.correios.com.br/prepostagem/v1/prepostagens/PRnR3Wm0pDT02bN0kuyAdP0A
Authorization: Bearer <token>
Accept: application/json
```

## 2. `DELETE /v1/prepostagens/objeto/{codigoObjeto}`

**Cancela uma pré-postagem pelo código do objeto.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/objeto/{codigoObjeto}`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/objeto/{codigoObjeto}`
- Header: `Authorization: Bearer <token>`
- operationId: `cancelaPrePostagemPorObjeto`

### 2.1 Parâmetros

| Nome | Em | Tipo | Obrig. | Descrição (especificação) |
|---|---|---|:-:|---|
| `codigoObjeto` | path | string | ✔ | Código do Objeto |
| `idCorreiosSolicitanteCancelamento` | query | string |  | Id Correios do Solicitante do Cancelamento. |

- `codigoObjeto`: 13 caracteres, maiúsculo e sem espaços (`^[A-Z]{2}[0-9]{9}[A-Z]{2}$`). A etiqueta mostra o código com espaços (`AB 043 180 865 BR`) — remova-os.

```http
DELETE https://apihom.correios.com.br/prepostagem/v1/prepostagens/objeto/AB043180865BR
Authorization: Bearer <token>
Accept: application/json
```

## 3. Respostas (iguais nos dois endpoints)

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `ResponseCancelamento` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o contrato/cartão. | — |

> Os códigos 401 e 403 não constam na especificação destes endpoints; são o comportamento geral das APIs. Outros endpoints desta API também devolveram **422** para regras de negócio e, em um caso, erro no formato `{ "mensagem": "PPN-xxx: ..." }` (ver arquivos 06 e 09) — trate os dois formatos.

### 3.1 Resposta 200 — `ResponseCancelamento`

Nenhum campo tem descrição ou obrigatoriedade na especificação.

| Campo | Tipo | Descrição |
|---|---|---|
| `resultadoCancelamento` | string | (sem descrição) — resultado do cancelamento |
| `mensagem` | string | (sem descrição) — mensagem explicativa |
| `idRecibo` | string | (sem descrição) — identificador do recibo do cancelamento |

```json
{
  "resultadoCancelamento": "string",
  "mensagem": "string",
  "idRecibo": "string"
}
```

### 3.2 Resposta de erro (400/500) — `MessageResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `msgs` | array<string> | Mensagens |
| `date` | string (date-time) | Data e hora de ocorrência do evento. |
| `method` | string | Método HTTP que foi requisitado. |
| `path` | string | URL que foi requisitada. |
| `causa` | string | A causa da mensagem. |
| `stackTrace` | string | Detalhe da mensagem. |

## 4. JSON Schema da resposta 200

```json
{
  "title": "ResponseCancelamento",
  "type": "object",
  "properties": {
    "resultadoCancelamento": { "type": "string" },
    "mensagem": { "type": "string" },
    "idRecibo": { "type": "string" }
  }
}
```

### Endpoints relacionados

| Método | Caminho | Resumo |
|---|---|---|
| DELETE | `/v1/prepostagens/assincrona` | Cancelamento assíncrono (lista de `codigosObjetos` ou `idsPrePostagens` no corpo) — arquivo 07 |
| GET | `/v1/prepostagens/canceladas/assincrona/{idRecibo}` | Consulta o processamento de cancelamentos assíncronos — arquivo 07 |
| POST | `/v1/prepostagens` | Criação da pré-postagem — arquivo 06 |

## 5. Orientações de implementação

1. **Token:** use o Bearer da API Token (`/v1/autentica/cartaopostagem`) e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita. O cancelamento deve ser feito com as credenciais do mesmo contrato/cartão que criou a pré-postagem.
2. **Quando é possível cancelar:** somente pré-postagens ainda **não postadas**. Com `statusAtual = 3` (POSTADO), `4` (EXPIRADO) ou `5` (CANCELADO), espere erro. Para pré-postagens criadas pela via assíncrona, aguarde sair de `7` (PENDENTE) antes de cancelar.
3. **Não confie só no HTTP 200:** leia `resultadoCancelamento` e `mensagem` para confirmar que o cancelamento foi efetivado, e só então marque como cancelada no seu sistema. Como os valores possíveis de `resultadoCancelamento` não são documentados, registre (log) a primeira resposta real antes de fixar a regra.
4. **`idRecibo`:** guarde o valor retornado junto da pré-postagem, como comprovante do cancelamento. A especificação não diz se ele pode ser consultado em `GET /v1/prepostagens/canceladas/assincrona/{idRecibo}` — a confirmar em teste.
5. **Irreversível:** não há endpoint para desfazer o cancelamento. Para enviar o objeto depois, crie uma nova pré-postagem (novo `id` e novo código de objeto). Peça confirmação ao usuário antes de cancelar.
6. **Rótulo já impresso:** após o cancelamento a etiqueta não deve mais ser usada; oriente o descarte e bloqueie a reimpressão no sistema.
7. **Idempotência:** se a chamada falhar por rede/tempo esgotado, consulte o status antes de repetir; um segundo `DELETE` para uma pré-postagem já cancelada tende a retornar erro, que deve ser tratado como "já cancelada" e não como falha.
8. **Codificação do caminho:** por segurança, aplique *URL encoding* no `id` e no código do objeto ao montar a URL.
9. **Logística reversa:** para apenas prorrogar a validade de uma autorização de postagem reversa, não cancele — use `PATCH /v1/prepostagens/{id}/logisticareversa` (não documentado nesta coleção).
10. **Ambientes:** mantenha a URL base configurável (homologação × produção); ids e códigos de um ambiente não valem no outro.
