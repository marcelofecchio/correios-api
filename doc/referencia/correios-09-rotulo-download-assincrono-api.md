# Correios — Pré-postagem: `GET /v1/prepostagens/rotulo/download/assincrono/{idRecibo}`

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prepostagem/v3/api-docs`), API **Pré-postagem** versão **2.0.32** (homologação), em 25/09/2026. As seções 1 a 4 reproduzem a especificação; a seção 5 traz orientações de implementação derivadas dela.
>
> **Atualizado em 27/09/2026** com a resposta real obtida em homologação (seções 0, 2 e 3 — marcadas como **✔ Observado em homologação**). A especificação declara a resposta 200 apenas como `object`; a estrutura documentada aqui vem do teste.
>
> **Sequência de testes:** etapa 09 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-08-rotulo-solicitar-assincrono-pdf-api.md` · Próxima: `correios-10-rastro-sro-api.md`

## 0. Resumo rápido

| | |
|---|---|
| **Operação** | Consulta pelo id do recibo os rótulos solicitados assincronamente pelos métodos POST de geração de objetos simples ou registrados. |
| **Método / caminho** | `GET /v1/prepostagens/rotulo/download/assincrono/{idRecibo}` |
| **URL produção** | `https://api.correios.com.br/prepostagem/v1/prepostagens/rotulo/download/assincrono/{idRecibo}` |
| **URL homologação** | `https://apihom.correios.com.br/prepostagem/v1/prepostagens/rotulo/download/assincrono/{idRecibo}` |
| **Headers** | `Authorization: Bearer <token>` (API Token — ver `correios-01-token-api.md`) |
| **Corpo da requisição** | Nenhum |
| **operationId** | `getRotuloPorRecibo` |
| **Schema da resposta 200** | Especificação: `object`. ✔ Observado: JSON `{ "dados": "<PDF em Base64>", "nome": "idRecibo_<idRecibo>.pdf" }` |
| **Usado após** | `POST /v1/prepostagens/rotulo/assincrono/pdf` (objetos **registrados**) ou `POST /v1/prepostagens/rotulo/lote/assincrono/pdf` (objetos **simples** em lote) |

### Fluxo

```
1. POST /v1/prepostagens                                  → cria a pré-postagem
2. POST /v1/prepostagens/rotulo/assincrono/pdf            → solicita o PDF (registrados)
   ou POST /v1/prepostagens/rotulo/lote/assincrono/pdf    → solicita o PDF (simples, lote)
   → resposta contém o identificador do recibo
3. GET  /v1/prepostagens/rotulo/download/assincrono/{idRecibo} → obtém os rótulos gerados   ◄ este documento
   → resposta JSON: "dados" (PDF em Base64) + "nome" (nome sugerido do arquivo)
4. Decodificar "dados" (Base64) e salvar como .pdf                → etiqueta pronta para impressão
```

## 1. Parâmetros

| Nome | Em | Tipo | Obrig. | Descrição (especificação) |
|---|---|---|:-:|---|
| `idRecibo` | path | string | ✔ | Id recibo |

- `idRecibo`: valor do campo `idRecibo` devolvido pela chamada POST de solicitação do rótulo (✔ observado: `{"idRecibo":"0bf495ce83074e1ab495ce83073e1a261002"}`). Envie exatamente o valor recebido (string), codificado para URL se necessário.

### Exemplo de chamada

```http
GET https://apihom.correios.com.br/prepostagem/v1/prepostagens/rotulo/download/assincrono/{idRecibo}
Authorization: Bearer <token>
Accept: application/json
```

## 2. Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `object` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o contrato/cartão. | — |

### Resposta 200 — ✔ Observado em homologação

A especificação declara apenas `object`. Na chamada real (homologação, 27/09/2026) a resposta foi um **JSON com dois campos**:

| Campo | Tipo | Descrição (observada) |
|---|---|---|
| `dados` | string | Conteúdo do arquivo **PDF codificado em Base64** (padrão, sem quebras de linha). Começa com `JVBERi0` — que é `%PDF-` em Base64. |
| `nome` | string | Nome sugerido para o arquivo, no formato `idRecibo_<idRecibo>.pdf` (ex.: `idRecibo_0bf495ce83074e1ab495ce83073e1a261002.pdf`). O `<idRecibo>` é o mesmo valor devolvido no campo `idRecibo` da solicitação (arquivo 08). |

Exemplo (conteúdo de `dados` abreviado):

```json
{
  "dados": "JVBERi0xLjQKJfbk/N8KMSAwIG9iago8PAovVHlwZSAvQ2F0YWxvZwovVmVyc2lvbiAvMS43Ci9QYWdlcyAyIDAgUgo+Pg...JSVFT0YK",
  "nome": "idRecibo_0bf495ce83074e1ab495ce83073e1a261002.pdf"
}
```

#### O arquivo decodificado

Características do PDF obtido no teste — rótulo solicitado em `POST /v1/prepostagens/rotulo/assincrono/pdf` (resposta `{"idRecibo":"0bf495ce83074e1ab495ce83073e1a261002"}`) com `tipoRotulo: "P"` e `layoutImpressao: "PADRAO"`, para **uma** pré-postagem SEDEX 03220 com valor declarado:

| Item | Valor observado |
|---|---|
| Tamanho | ~43 KB de JSON → ~32 KB de PDF (o Base64 aumenta cerca de 33%) |
| Formato | PDF válido (`%PDF-1.4` … `%%EOF`), gerado por *openhtmltopdf* |
| Página | Folha **A4** (595 × 842 pt), 1 página, com a etiqueta no canto superior esquerdo |
| Conteúdo da etiqueta | Logotipo, DataMatrix, número do contrato, nome do serviço (`SEDEX CONTRATO AG`), **código do objeto** (ex.: `AB 043 180 865 BR`) com código de barras, sigla do serviço adicional (`VD` = valor declarado), campos Recebedor/Assinatura/Documento, bloco **DESTINATÁRIO** (nome, endereço, complemento, bairro, CEP, cidade/UF e código de barras do CEP), observação da pré-postagem e bloco **REMETENTE** |
| Marca de homologação | Faixa vertical **"HOMOLOGAÇÃO — Impressão de teste. Não aceitar"**. Etiquetas de homologação **não** podem ser usadas em postagens reais. |

#### Várias etiquetas no mesmo recibo — ✔ Observado em homologação

Segundo teste: rótulo solicitado para **duas** pré-postagens (SEDEX 03220 e PAC 03298), mesmas opções (`P` / `PADRAO`). Resposta com a mesma estrutura `{ "dados", "nome" }` (nome `idRecibo_6dbebb7bf0554651bebb7bf0559651261002.pdf`, ~53 KB de JSON → ~39 KB de PDF).

| Item | Observado |
|---|---|
| Arquivo | **Um único PDF** com todas as etiquetas do recibo |
| Disposição | Etiquetas **lado a lado** na mesma folha A4 (grade de 2 colunas a partir do canto superior esquerdo); com 2 etiquetas, 1 página |
| Ordem | A mesma de `idsPrePostagem` na solicitação (SEDEX à esquerda, PAC à direita) |
| Reemissão | A etiqueta do SEDEX, já emitida no teste anterior, saiu com o **mesmo código do objeto** (`AB 043 180 865 BR`) — solicitar o rótulo de novo não gera novo código |
| Serviço adicional | A sigla impressa ao lado do código de barras varia por serviço: `VD` no SEDEX e `VDS` no PAC (ambos com valor declarado) |

Consequência para a aplicação: um PDF pode conter etiquetas de vários objetos; se precisar de um arquivo por objeto, solicite o rótulo de cada pré-postagem separadamente (um recibo por objeto) em vez de tentar dividir o PDF. Quantas etiquetas cabem por folha e como fica a quebra de página ainda não foram testados.

#### Erro PPN-288 — pré-postagem com status Pendente — ✔ Observado em homologação

Pré-postagem criada pela via **assíncrona** (`POST /v1/prepostagens/assincrona`, arquivo 07), com `situacao = PROCESSADO`. A solicitação do rótulo foi **aceita** (`{"idRecibo":"f479b6af0af84f12b9b6af0af86f12261002"}`), mas o download retornou:

```json
{"mensagem":"PPN-288: Não é permitido imprimir rótulo de pré-postagem com status igual a Pendente. Pré-postagem ID = PRVIuNOqSCR0CFd8iKK0y3Rg"}
```

- A validação do status acontece **no download**, não na solicitação: receber `idRecibo` não garante que o PDF será gerado.
- `Pendente` corresponde a `statusAtual = 7` (PENDENTE). O rótulo só é emitido para pré-postagens fora desse status. Em 27/09/2026 as pré-postagens criadas pela via síncrona vieram com status pré-postado e imprimiram normalmente; ✔ em 04/10/2026 uma criada pela via **síncrona** veio com status 7 e o download também respondeu PPN-288.
- O corpo de erro **não** segue o `MessageResponse` (`msgs`): vem como `{ "mensagem": "..." }`, com o código `PPN-xxx` no início. Trate os dois formatos.
- ✔ **HTTP 200 (observado em 04/10/2026):** o PPN-288 veio com **HTTP 200**, e não com um código de erro. Não confie só no status HTTP: se `dados` não vier, leia `mensagem` e trate como erro.
- ✔ **Confirmado:** o status Pendente é **transitório**. Alguns minutos depois, nova consulta a `GET /v1/prepostagens/assincrona/{recibo}` mostrou `statusAtual = 2` (PREPOSTADO), e o download do rótulo passou a funcionar.
- ✔ **Confirmado:** o `idRecibo` obtido enquanto a pré-postagem estava em `7` **fica inutilizado** — mesmo depois de o status mudar para `2`, o download com esse recibo não funciona. É preciso fazer **nova** solicitação (`POST /v1/prepostagens/rotulo/assincrono/pdf`) já com `statusAtual = 2`, e baixar com o **novo** `idRecibo`.
- Regra prática: só solicite o rótulo quando `statusAtual` for `2`. Se o download retornar PPN-288, descarte aquele `idRecibo`, aguarde o status `2` e solicite o rótulo de novo. O tempo de transição não é documentado (no teste de 27/09/2026, alguns minutos). ✔ Em 04/10/2026 as pré-postagens pendentes de teste não chegaram ao status 2: foram canceladas automaticamente pelos Correios minutos depois (ver arquivo 07, seção 7, item 11).

#### Pontos ainda não observados

- Resposta quando o arquivo **ainda está em processamento** (recibo válido, PDF não pronto) — trate como na seção 5, item 3.
- Variações de página com `tipoRotulo = R` e `layoutImpressao` diferente de `PADRAO`.

### Resposta de erro (400/500) — `MessageResponse`

| Campo | Tipo | Descrição |
|---|---|---|
| `msgs` | array<string> | Mensagens |
| `date` | string (date-time) | Data e hora de ocorrência do evento. |
| `method` | string | Método HTTP que foi requisitado. |
| `path` | string | URL que foi requisitada. |
| `causa` | string | A causa da mensagem. |
| `stackTrace` | string | Detalhe da mensagem. |

```json
{
  "msgs": [
    "Mensagem de validação"
  ],
  "date": "2026-01-01T00:00:00",
  "method": "GET",
  "path": "/v1/prepostagens/rotulo/download/assincrono/{idRecibo}",
  "causa": "string",
  "stackTrace": "string"
}
```

## 3. JSON Schema da resposta (conforme a especificação)

Resposta 200 (especificação):

```json
{
  "type": "object"
}
```

Resposta 200 — ✔ schema observado em homologação (não publicado pelos Correios):

```json
{
  "title": "RotuloDownloadResponse (observado)",
  "type": "object",
  "properties": {
    "dados": {
      "type": "string",
      "contentEncoding": "base64",
      "contentMediaType": "application/pdf",
      "description": "Arquivo PDF com os rótulos, codificado em Base64."
    },
    "nome": {
      "type": "string",
      "pattern": "^idRecibo_.+\\.pdf$",
      "description": "Nome sugerido do arquivo: idRecibo_<idRecibo>.pdf"
    }
  }
}
```

Resposta 400/500:

```json
{
  "title": "MessageResponse",
  "properties": {
    "msgs": {
      "type": "array",
      "description": "Mensagens",
      "items": {
        "type": "string",
        "description": "Mensagens"
      }
    },
    "date": {
      "type": "string",
      "format": "date-time",
      "description": "Data e hora de ocorrência do evento."
    },
    "method": {
      "type": "string",
      "description": "Método HTTP que foi requisitado."
    },
    "path": {
      "type": "string",
      "description": "URL que foi requisitada."
    },
    "causa": {
      "type": "string",
      "description": "A causa da mensagem."
    },
    "stackTrace": {
      "type": "string",
      "description": "Detalhe da mensagem."
    }
  }
}
```

## 4. Endpoints relacionados (referência)

| Método | Caminho | Resumo |
|---|---|---|
| POST | `/v1/prepostagens/rotulo/lote/assincrono/pdf` | Para objetos Simples, solicita de maneira assíncrona a geração do rotulo. Para LOTES de pré-postagem Simples. (somente para geração de rótulos de objetos SIMPLES). |
| POST | `/v1/prepostagens/rotulo/assincrono/pdf` | Para objetos Registrados, solicita de maneira assíncrona a geração do rótulo pelos ids das pré-postagens. (somente para geração de rótulos de objetos REGISTRADOS). |
| GET | `/v1/prepostagens/rotulo/lote/{idLote}/assincrono/pdf` | Permite recuperar arquivo de rótulos de objetos SIMPLES que foram gerados anteriormente de forma assíncrona. (Retorna todos os rótulos de um determinado lote). |
| GET | `/v1/prepostagens/rotulo/download/assincrono/{idRecibo}` | Consulta pelo id do recibo os rótulos solicitados assincronamente pelos métodos POST de geração de objetos simples ou registrados. |

## 5. Orientações de implementação

1. **Token:** use o Bearer da API Token (`/v1/autentica/cartaopostagem`) e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita.
2. **Guarde o recibo:** salve o `idRecibo` retornado pelo POST junto com as pré-postagens correspondentes, para poder baixar (ou baixar de novo) o PDF depois.
3. **Consulta com espera:** como a geração é assíncrona, o arquivo pode não estar pronto na primeira chamada. Consulte com intervalo entre as tentativas (ex.: alguns segundos, aumentando progressivamente) e com limite máximo de tentativas; registre o erro se esgotar.
4. **Decodificação do PDF:** leia `dados`, decodifique de Base64 para bytes e grave como `.pdf` (use `nome` como nome do arquivo ou gere o seu, ex.: `<codigoObjeto>.pdf`). Antes de gravar, valide que os bytes começam com `%PDF` — se não começarem, trate como resposta inesperada e registre o corpo. Não trate `dados` como texto após decodificar (é binário).
5. **Erros:** exiba ao usuário/log as mensagens de `msgs` (formato `MessageResponse`) **ou** de `mensagem` (formato observado, ex.: `PPN-288` para pré-postagem Pendente). O PPN-288 pode vir com **HTTP 200**: considere erro toda resposta sem o campo `dados`. Podem indicar recibo inválido, expirado, ainda não processado ou pré-postagem em status que não permite impressão.
6. **Armazenamento:** salve o PDF baixado (ou o caminho do arquivo) vinculado à pré-postagem, evitando novas chamadas para reimprimir a mesma etiqueta.
7. **Ambientes:** mantenha a URL base configurável (homologação × produção); recibos de um ambiente não valem no outro. Etiquetas de homologação trazem a marca "Impressão de teste. Não aceitar".
8. **Exibição/impressão:** para abrir no navegador sem gravar em disco, use `data:application/pdf;base64,<dados>` ou devolva os bytes com `Content-Type: application/pdf`. A etiqueta vem em página A4; para impressora térmica 10×15 cm, solicite o rótulo com `layoutImpressao: LINEAR_100_150` (ver arquivo 08).

### Exemplo (Node.js)

```js
import { writeFile } from 'node:fs/promises';

async function baixarRotulo(idRecibo, token) {
  const url = `${process.env.CORREIOS_BASE_URL}/prepostagem/v1/prepostagens/rotulo/download/assincrono/${encodeURIComponent(idRecibo)}`;
  const res = await fetch(url, { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } });
  if (!res.ok) throw new Error(`Download rótulo: HTTP ${res.status} ${await res.text()}`);
  const { dados, nome, mensagem } = await res.json();
  // O PPN-288 (pré-postagem pendente) pode vir com HTTP 200 e sem "dados"
  if (!dados) throw new Error(`Download rótulo: ${mensagem || 'resposta sem o campo dados'}`);
  const pdf = Buffer.from(dados, 'base64');
  if (pdf.subarray(0, 4).toString() !== '%PDF') throw new Error('Resposta não contém um PDF válido');
  await writeFile(nome || `rotulo_${idRecibo}.pdf`, pdf);
  return nome;
}
```

### Exemplo (Python)

```python
import base64, requests

def baixar_rotulo(base_url: str, id_recibo: str, token: str) -> str:
    r = requests.get(f"{base_url}/prepostagem/v1/prepostagens/rotulo/download/assincrono/{id_recibo}",
                     headers={"Authorization": f"Bearer {token}", "Accept": "application/json"}, timeout=30)
    r.raise_for_status()
    corpo = r.json()
    # O PPN-288 (pré-postagem pendente) pode vir com HTTP 200 e sem "dados"
    if not corpo.get("dados"):
        raise ValueError(corpo.get("mensagem") or "Resposta sem o campo dados")
    pdf = base64.b64decode(corpo["dados"])
    if not pdf.startswith(b"%PDF"):
        raise ValueError("Resposta não contém um PDF válido")
    nome = corpo.get("nome") or f"rotulo_{id_recibo}.pdf"
    with open(nome, "wb") as f:
        f.write(pdf)
    return nome
```
