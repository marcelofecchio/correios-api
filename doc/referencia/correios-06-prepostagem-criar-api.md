# Correios — Pré-postagem: `POST /v1/prepostagens`

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prepostagem/v3/api-docs`), API **Pré-postagem** versão **2.0.32** (homologação), em 25/09/2026. As seções 1 a 7 reproduzem a especificação; a seção 8 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 06 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-05-prazo-api.md` · Próxima: `correios-07-prepostagem-assincrona-registrados-api.md`

## 0. Resumo rápido

| | |
|---|---|
| **Operação** | Permite criar uma Pré-Postagem. |
| **Método / caminho** | `POST /v1/prepostagens` |
| **URL produção** | `https://api.correios.com.br/prepostagem/v1/prepostagens` |
| **URL homologação** | `https://apihom.correios.com.br/prepostagem/v1/prepostagens` |
| **Headers** | `Authorization: Bearer <token>` (API Token, endpoint `/v1/autentica/cartaopostagem` — ver `correios-01-token-api.md`) · `Content-Type: application/json` |
| **operationId** | `preposta` |
| **Schema da requisição** | `RequestPrePostagemExternaDTO` (91 campos, 8 objetos) |
| **Schema da resposta 200** | `PrePostagem` |
| **Unidades** | Peso em **gramas**; dimensões em **centímetros**; CEP com 8 dígitos sem hífen |

### Fluxo em que este endpoint se insere

```
1. POST /v1/autentica/cartaopostagem (API Token)          → token Bearer
2. (opcional) API Preço / API Prazo                        → cotação para o cliente
3. POST /v1/prepostagens                                   → cria a pré-postagem   ◄ este documento
   → resposta traz id e codigoObjeto (código de rastreio)
4. POST /v1/prepostagens/rotulo/assincrono/pdf             → solicita a etiqueta (registrados)
5. GET  /v1/prepostagens/rotulo/download/assincrono/{idRecibo} → baixa o PDF
6. Postagem física na agência dentro do prazoPostagem; acompanhamento pela API SRO Rastro / Webhook
```

## 1. Campos obrigatórios (checklist)

**Objeto principal (`RequestPrePostagemExternaDTO`):**

| Campo | Regra |
|---|---|
| `remetente` | Objeto `RemetenteDTO` — obrigatórios `nome` e `endereco` |
| `destinatario` | Objeto `DestinatarioDTO` — obrigatório `nome` (e `endereco`, na prática, para entrega) |
| `codigoServico` | Código de serviço válido **e presente no cartão de postagem** do cliente |
| `pesoInformado` | Gramas; > 0; dentro do limite do serviço; até 6 dígitos |
| `codigoFormatoObjetoInformado` | `1` Envelope · `2` Caixa/Pacote · `3` Cilindro/Rolo |
| `cienteObjetoNaoProibido` | `"1"` = objeto permitido no fluxo postal (`"0"` = proibido) |
| `itensDeclaracaoConteudo` | Lista de `ItemDeclaracaoConteudo` (`conteudo`, `quantidade`, `valor`) — ver regra de documento fiscal na seção 2 |

**Endereço do remetente (`EnderecoRemetenteDTO`):** `cep`, `logradouro`, `numero`, `bairro`, `cidade`, `uf`.

**Endereço do destinatário (`EnderecoDestinatarioDTO`):** `cep`, `logradouro`, `numero`, `bairro`, `cidade`, `uf`, `regiao`.

**Cada item da declaração de conteúdo (`ItemDeclaracaoConteudo`):** `conteudo` (2 a 60 caracteres), `quantidade`, `valor` (unitário).

## 2. Regras condicionais (extraídas das descrições da especificação)

| Situação | Regra |
|---|---|
| Formato `2` (Caixa/Pacote) | `alturaInformada`, `larguraInformada` e `comprimentoInformado` obrigatórios (cm, até 3 dígitos, dentro dos limites do serviço) |
| Formato `3` (Cilindro/Rolo) | `comprimentoInformado` e `diametroInformado` obrigatórios |
| Documento fiscal | `numeroNotaFiscal` (até 12 dígitos) **ou** `chaveNFe` (44 dígitos) **ou** declaração de conteúdo (`itensDeclaracaoConteudo`) |
| Serviço adicional de valor declarado | `listaServicoAdicional[].valorDeclarado` obrigatório |
| `listaServicoAdicional[]` | `codigoServicoAdicional` obrigatório, com 3 caracteres |
| Entrega em locker | `destinatario.cpfCnpj` obrigatório (e válido) |
| Telefones (remetente/destinatário) | Se informados: DDD com 2 dígitos; telefone com 8 dígitos |
| `remetente.nome` | Nome e sobrenome, 3 a 50 caracteres |
| Destino internacional | `destinatario.endereco.cep` com até 17 caracteres; `regiao` utilizada |
| Credenciais INTERNAS apenas | `idCorreios`, `precoServico`, `precoPrePostagem`, `numeroCartaoPostagem`, `idAtendimento` — **não enviar** em integração de cliente externo |
| Indicadores S/N | `logisticaReversa`, `embalagem`, `emiteDCe`, `solicitarColeta` aceitam `S` ou `N` |
| `modalidadePagamento` | `1` à vista · `2` a faturar · `3` à vista e a faturar · `4` prestação de contas recebimento ou pagamento |

> **Inconsistências da especificação (validar em homologação):**
> - `itensDeclaracaoConteudo` consta como obrigatório no schema, mas a descrição de `numeroNotaFiscal`/`chaveNFe` diz que eles substituem a declaração de conteúdo. Ao enviar NF/NF-e, teste se a lista pode ser omitida ou enviada vazia.
> - `regiao` consta como obrigatório no endereço do destinatário, mas a descrição diz que é usado em pré-postagens internacionais. Teste o comportamento em envios nacionais.

### Logística reversa — ✔ observado em homologação

Pré-postagem reversa criada com sucesso para **SEDEX REVERSO (03247)** com os campos abaixo (o PAC REVERSO 03301 usa o mesmo corpo, mas ainda não teve o resultado registrado):

| Campo | Valor usado |
|---|---|
| `logisticaReversa` | `"S"` |
| `dataValidadeLogReversa` | data futura em `dd/MM/yyyy` (no teste, hoje + 10 dias) |
| `remetente` | o **cliente** que devolve (CPF, e-mail preenchido — obrigatório na reversa) |
| `destinatario` | a **loja** que recebe (CNPJ do contrato) |
| `listaServicoAdicional` | **não enviada** — o adicional 016 (LOGISTICA REV SIMULTANEA VOLTA), obrigatório para cotar o SEDEX REVERSO na API Preço (`ERP-049`), **não** foi exigido na criação da pré-postagem |

### Declaração de conteúdo e AR (endpoints do manual) — ✔ observado em homologação

O manual V2.4 cita `v1/prepostagens/declaracaoconteudo/{ids}` e `v1/prepostagens/avisorecebimento/{ids}` (retorno HTML, pré-postagem com status pré-postado), sem informar o método. Teste com `GET` e header `Accept: application/json`, logo após criar um SEDEX pela via síncrona:

```json
{
  "causa": "ApiNegocioRuntimeException: Cannot invoke \"org.springframework.web.servlet.ViewResolver.resolveViewName(String, java.util.Locale)\" because \"this.viewResolver\" is null",
  "date": "2026-09-27T13:23:35",
  "msgs": ["Cannot invoke \"org.springframework.web.servlet.ViewResolver.resolveViewName(String, java.util.Locale)\" because \"this.viewResolver\" is null"],
  "path": "/prepostagem/v1/prepostagens/declaracaoconteudo/PRnR3Wm0pDT02bN0kuyAdP0A"
}
```

- HTTP **400**, mas a mensagem é uma falha interna do servidor ao montar a página HTML (não uma validação dos dados enviados).
- Repetido com `Accept: text/html`: **HTTP 500** com a página genérica do servidor (`HTTP Status 500 – Internal Server Error`), sem detalhes. O mesmo ocorreu no endpoint de AR com esse header.
- Repetido com `Accept: */*`: **HTTP 500** genérico, tanto na declaração de conteúdo quanto no AR (este para uma pré-postagem criada **com** o adicional 001).

| `Accept` | Declaração de conteúdo | AR |
|---|---|---|
| `application/json` | 400 — `this.viewResolver is null` | 422 — sem o adicional 001 (regra de negócio, esperado) |
| `text/html` | 500 genérico | 500 genérico |
| `*/*` | 500 genérico | 500 genérico (pré-postagem com 001) |

- **Conclusão (27/09/2026):** os dois endpoints que devolvem HTML estão falhando **no servidor de homologação** (a mensagem `this.viewResolver is null` indica que o componente que monta a página não está configurado). Não há ajuste possível do lado do cliente. Recomendações:
  1. Use `Accept: application/json` nas chamadas — é o único formato que devolve mensagens de erro legíveis.
  2. Reporte ao assistente comercial / suporte dos Correios com as mensagens acima, o id da pré-postagem e a data.
  3. ✔ Confirmado na especificação (versão 2.0.32, consultada em 03/10/2026): os dois caminhos existem, com método **GET** — `GET /v1/prepostagens/declaracaoconteudo/{ids}` ("Permite emitir a declaração de conteúdo da Pré-Postagem. Formato HTML.") e `GET /v1/prepostagens/avisorecebimento/{ids}` ("Permite emitir o aviso de recebimento da Pré-Postagem que tenha o serviço adicional cadastrado. Formato HTML."). A requisição está correta; a falha é do servidor de homologação.
  4. Não torne a aplicação dependente desses documentos até que funcionem; para a declaração de conteúdo, avalie a alternativa `emiteDCe: "S"` na criação da pré-postagem.
- **AR:** `GET .../avisorecebimento/{id}` para uma pré-postagem criada **sem** o adicional de AR retornou **HTTP 422**:

  ```json
  {"causa":"ApiNegocioRuntimeException: O objeto consultado não possui o serviço AR Convencional 001 associado.","date":"2026-09-27T13:25:03","msgs":["O objeto consultado não possui o serviço AR Convencional 001 associado."],"path":"/prepostagem/v1/prepostagens/avisorecebimento/PRnR3Wm0pDT02bN0kuyAdP0A"}
  ```

  Ou seja: o método `GET` e o caminho estão corretos (a API validou a regra de negócio), e o AR só é emitido para pré-postagens criadas com o adicional **001 - AR Convencional** em `listaServicoAdicional` (ex.: `[{"codigoServicoAdicional":"019","valorDeclarado":"250.00"},{"codigoServicoAdicional":"001"}]`). Emissão com o 001 ainda a confirmar.
- Erros de regra de negócio desta API podem vir com **HTTP 422** (além de 400), no formato `MessageResponse` (`msgs`, `causa`).

## 3. Todos os campos da requisição

91 campos. A coluna "Obrig." indica obrigatoriedade no nível do objeto: um campo filho só é exigido quando o objeto pai é enviado.

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idCorreios` | string |  | Identificador do usuário no IDCorreios. Obrigatório apenas para credenciais INTERNAS. |
| `remetente` | objeto `RemetenteDTO` | ✔ |  |
| `remetente.nome` | string | ✔ | O preenchimento do Nome do remetente é obrigatório. Deve ser composto de nome e sobrenome. Não pode ser deixado em branco, mínimo de 3 caracteres e máximo 50 caracteres |
| `remetente.codigo` | string |  | Código |
| `remetente.indicadorMalote` | string |  | Indicador de malote. Para malotes será sempre S. |
| `remetente.dddTelefone` | string |  | DDD do telefone do remetente. Se informado, deve ter 2 caracteres numéricos. |
| `remetente.telefone` | string |  | Número do telefone do remetente. Se informado, deve ter 8 caracteres numéricos. |
| `remetente.dddCelular` | string |  | DDD do celular do remetente. Se informado, deve ter 2 caracteres numéricos. |
| `remetente.celular` | string |  | Número do celular do remetente. Se informado, deve ter 9 caracteres numéricos. — minLength: `0` — maxLength: `9` |
| `remetente.email` | string |  | E-mail do remetente. É Obrigatório se for logística reversa, deve ter no máximo 255 caracteres e seguir o formato padrão "login@domínio". |
| `remetente.cpfCnpj` | string |  | CPF ou CNPJ do remetente. É obrigatório se não for informado documento estrangeiro, deve ser válido e ter no máximo 14 caracteres numéricos. |
| `remetente.documentoEstrangeiro` | string |  | Passaporte ou RNM do remetente da prepostagem. É obrigatório se não for informado CPF ou CNPJ e deve ter no máximo 30 caracteres. |
| `remetente.obs` | string |  | Observação do remetente preenchida a critério do cliente. Tamanho máximo: 100 caracteres |
| `remetente.endereco` | objeto `EnderecoRemetenteDTO` | ✔ | Endereço do remetente |
| `remetente.endereco.cep` | string | ✔ | CEP do remetente. Campo obrigatório, deve ser informado CEP válido com 8 caracteres numéricos. — minLength: `0` — maxLength: `8` |
| `remetente.endereco.logradouro` | string | ✔ | Logradouro do remetente. Campo obrigatório. Tamanho máximo: 50 caracteres |
| `remetente.endereco.numero` | string | ✔ | Número do logradouro do remetente. Campo obrigatório. Tamanho máximo: 6 caracteres. |
| `remetente.endereco.complemento` | string |  | Complemento do logradouro do remetente. Tamanho máximo: 30 caracteres. |
| `remetente.endereco.bairro` | string | ✔ | Bairro do remetente. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `remetente.endereco.cidade` | string | ✔ | Cidade do remetente. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `remetente.endereco.uf` | string | ✔ | UF do CEP do remetente. Campo obrigatório. Tamanho máximo: 2 caracteres. |
| `remetente.endereco.pais` | string |  | País do remetente. Tamanho máximo: 50 caracteres. Default Brasil |
| `destinatario` | objeto `DestinatarioDTO` | ✔ |  |
| `destinatario.nome` | string | ✔ | O preenchimento do Nome do destinatário é obrigatório. Deve ser composto de nome e sobrenome. Não pode ser deixado em branco, mínimo de 3 caracteres e máximo 50 caracteres. |
| `destinatario.codigo` | string |  | Código |
| `destinatario.indicadorMalote` | string |  | Indicador de malote. Para malotes será sempre S |
| `destinatario.dddTelefone` | string |  | DDD do telefone do destinatário. Se informado, deve ter 2 caracteres numéricos. |
| `destinatario.ddiTelefone` | string |  | DDI do telefone do destinatário. Se informado, deve ter 3 caracteres numéricos. Utilizado em prepostagens internacionais. — minLength: `0` — maxLength: `3` |
| `destinatario.telefone` | string |  | Número do telefone do destinatário. Se informado, deve ter 8 caracteres numéricos. |
| `destinatario.dddCelular` | string |  | DDD do celular do destinatário. Obrigatório se a entrega for em locker. Se informado, deve ter 2 caracteres numéricos. |
| `destinatario.ddiCelular` | string |  | DDI do celular do destinatário. Se informado, deve ter 3 caracteres numéricos. Utilizado em prepostagens internacionais. — minLength: `0` — maxLength: `3` |
| `destinatario.celular` | string |  | Número do celular do destinatário. Obrigatório se a entrega for em locker.Se informado, deve ter 9 caracteres numéricos. — minLength: `0` — maxLength: `9` |
| `destinatario.email` | string |  | E-mail do destinatário. Se informado, deve ter no máximo 255 caracteres e seguir o formato padrão "login@domínio". |
| `destinatario.cpfCnpj` | string |  | CPF ou CNPJ do destinatário. Obrigatório se a entrega for em locker. Se informado, deve ser válido. |
| `destinatario.documentoEstrangeiro` | string |  | Passaporte ou RNM do destinatário da prepostagem. Tamanho máximo: 30 caracteres. |
| `destinatario.obs` | string |  | Observação do destinatário preenchida a critério do cliente. Tamanho máximo: 100 caracteres |
| `destinatario.endereco` | objeto `EnderecoDestinatarioDTO` |  | Endereço do destinatário |
| `destinatario.endereco.cep` | string | ✔ | CEP do destinatário. Informar um CEP válido com 8 caracteres numéricos. Caso o endereço seja internacional, informar até 17 caracteres. — minLength: `0` — maxLength: `17` |
| `destinatario.endereco.logradouro` | string | ✔ | Logradouro do destinatário. Campo obrigatório. Tamanho máximo: 50 caracteres |
| `destinatario.endereco.numero` | string | ✔ | Número do logradouro do destinatário. Campo obrigatório. Tamanho máximo: 6 caracteres. |
| `destinatario.endereco.complemento` | string |  | Complemento do logradouro do destinatário. Tamanho máximo: 30 caracteres. |
| `destinatario.endereco.bairro` | string | ✔ | Bairro do destinatário. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `destinatario.endereco.cidade` | string | ✔ | cidade do destinatário. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `destinatario.endereco.uf` | string | ✔ | UF do CEP do destinatário. Campo obrigatório. Tamanho máximo: 2 caracteres. |
| `destinatario.endereco.regiao` | string | ✔ | Região. Utilizado em prepostagens internacionais. Tamanho máximo: 50 caracteres. — minLength: `0` — maxLength: `50` |
| `destinatario.endereco.pais` | string |  | Sigla do País do destinatário. Utilizado em prepostagens internacionais. Tamanho máximo: 2 caracteres. Exemplo: BR, US, CA, FR — minLength: `0` — maxLength: `2` |
| `codigoServico` | string | ✔ | Código do serviço a ser pré-postado. Não pode ser deixado em branco. Tem que ser um código de serviço válido. O código do serviço deve constar no cartão de postagem do cliente que está fazendo a pré-postagem. — minLength: `0` — maxLength: `8` |
| `precoServico` | string |  | Preço somente do serviço, sem os adicionais. Utilizado apenas em credenciais INTERNAS. |
| `precoPrePostagem` | string |  | Preço total da pré-postagem. Utilizado apenas em credenciais INTERNAS. |
| `numeroNotaFiscal` | string |  | Número da nota fiscal que acompanhará o objeto pré-postado. Obrigatório, na ausência da declaração de conteúdo ou NFe. Tamanho máximo: 12 caracteres numéricos. |
| `numeroCartaoPostagem` | string |  | Número do cartao de postagem - somente para credencial interna. |
| `chaveNFe` | string |  | Chave da Nota Fiscal Eletrônica. Obrigatório, na ausência da declaração de conteúdo ou NF. Se informado, deverá conter 44 caracteres numéricos. |
| `listaServicoAdicional` | array<objeto `ServicoAdicional`> |  | Serviços Adicionais. |
| `listaServicoAdicional[].codigoServicoAdicional` | string |  | Código do serviço adicional. Não pode ser deixado em branco. Informar com 3 caracteres. |
| `listaServicoAdicional[].tipoServicoAdicional` | string |  | Tipo de serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].nomeServicoAdicional` | string |  | Nome do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].valorServicoAdicional` | string |  | Valor do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].valorDeclarado` | string |  | Valor declarado do objeto a ser pré-postado. Não pode ser deixado em branco caso o serviço adicional seja do tipo valor declarado. |
| `listaServicoAdicional[].siglaServicoAdicional` | string |  | Sigla do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].objetoIdaSimultanea` | string |  | Código do objeto de ida simultânea para o serviço adicional 016. — minLength: `0` — maxLength: `20` |
| `listaServicoAdicional[].orientacaoEntregaVizinho` | string |  | Orientação para entrega no vizinho. Obrigatório se o serviço adicional (011) for selecionado. — minLength: `0` — maxLength: `20` |
| `listaServicoAdicional[].tipoChecklist` | string |  | Indica o tipo de checklist que deverá ser preenchido no momento da postagem. Aceita apenas 01-Celular, 04-Eletrônico, 05-Documento ou 07-Conteúdo. — minLength: `0` — maxLength: `3` |
| `listaServicoAdicional[].subitensCheckList` | array<objeto `SubitemCheckList`> |  | Lista de códigos dos subitens para o tipoChecklist: 05-documentos. Podem ser listados até 8 códigos de documentos. |
| `listaServicoAdicional[].subitensCheckList[].codigo` | string |  | Código do subitem. |
| `itensDeclaracaoConteudo` | array<objeto `ItemDeclaracaoConteudo`> | ✔ | Declaração de conteúdo. Obrigatório. |
| `itensDeclaracaoConteudo[].conteudo` | string | ✔ | Descrição do item. Obrigatório. Tamanho mínimo 2 e tamanho máximo: 60. Não são aceitos apenas caracteres iguais. Caso informados apenas dois caracteres, os dois devem ser letras. |
| `itensDeclaracaoConteudo[].quantidade` | string | ✔ | Quantidade total de itens de um mesmo tipo. Obrigatório. Tamanho máximo: 11 dígitos. |
| `itensDeclaracaoConteudo[].valor` | string | ✔ | Valor Unitário do Item. Obrigatório. Tamanho máximo: 19 dígitos e 2 casas decimais. |
| `pesoInformado` | string | ✔ | Peso em gramas do objeto que será pré-postado. Não pode ser deixado em branco. Não pode ser zero. Não pode ser um valor negativo. Não pode exceder o limite máximo definido para o serviço informado. Tamanho máximo: 6 dígitos. — minLength: `0` — maxLength: `6` |
| `codigoFormatoObjetoInformado` | string | ✔ | Código do formato do objeto a ser pré-postado. Não pode ser deixado em branco. Deve ser um dos códigos a seguir: 1-Envelope; 2-Caixa/Pacote; 3-Cilindro/rolo. |
| `alturaInformada` | string |  | Dimensão altura do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informada em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `larguraInformada` | string |  | Dimensão largura do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informada em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `comprimentoInformado` | string |  | Dimensão comprimento do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informado em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `diametroInformado` | string |  | Dimensão diâmetro do objeto a ser pré-postado. Obrigatório para o formato cilindro /rolo. Deve ser informado em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `ncmObjeto` | string |  | Código NCM (Nomenclatura Comum do Mercosul) do produto que está sendo pré-postado. Se informado deve conter até 8 caracteres numéricos. |
| `rfidObjeto` | string |  | Código SSCC (do inglês Serial Shipping Container Code ou traduzindo Código de Séria de Unidade Logística) associado à TAG RFID afixado no objeto que está sendo pré-postado. Se informado deverá conter até 24 caracteres numéricos. |
| `cienteObjetoNaoProibido` | string | ✔ | Atributo que indica que o objeto a ser pré-postado é permitido no fluxo postal. Não pode ser deixada em branco. Deverá ser informado "0" ou "1". Onde  "0" – Indica que o cliente está pré-postando um objeto proibido no fluxo postal e "1" – Indica que o cliente está pré-postando um objeto permitido no fluxo postal |
| `idAtendimento` | string |  | ID do atendimento. Utilizado apenas em credenciais INTERNAS |
| `solicitarColeta` | string |  | Indica se o objeto vai ter uma coleta gerada, valores possíveis: S ou N. — pattern: `^(?:S\|N)$` |
| `codigoObjeto` | string |  | Código identificador do objeto. Tamanho máximo: 13 caracteres. — pattern: `^[A-Z]{2}[0-9]{9}[A-Z]{2}$` |
| `dataPrevistaPostagem` | string |  | Data provável da postagem (obrigatório apenas para o segmento de mensagem) — pattern: `dd/MM/yyyy` — Ex.: `01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)` |
| `observacao` | string |  | Informações complementares. Tamanho máximo: 50 caracteres. — minLength: `0` — maxLength: `50` |
| `modalidadePagamento` | string |  | Modalidade Pagamento: 1-à vista, 2-à faturar, 3- à avista e à afaturar e 4- Prestação de contas recebimento ou pagamento |
| `logisticaReversa` | string |  | Indicador se possui ou não logística reversa: S= Sim, N= Não. — Valores: `S`, `N` |
| `dataValidadeLogReversa` | string |  | Data de validade da logistica reversa — pattern: `^([0-2][0-9]\|\|3[0-1])/(0[0-9]\|\|1[0-2])/([0-9][0-9])?[0-9][0-9]$` — Ex.: `01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)` |
| `prazoPostagem` | string |  | Data de limite para postagem do objeto. Após essa data, caso o objeto não seja postado, o mesmo será expirado. Data default será a data de criação + 14 dias, caso não seja informada. Data máxima de 90 dias a contar da data de criação. — pattern: `^([0-2][0-9]\|\|3[0-1])/(0[0-9]\|\|1[0-2])/([0-9][0-9])?[0-9][0-9]$` — Ex.: `10/05/2025` |
| `codigoObjetoIda` | string |  | Código identificador do objeto associado a logística reversa. Tamanho máximo: 13 caracteres. — pattern: `^[A-Z]{2}[0-9]{9}[A-Z]{2}$` |
| `embalagem` | string |  | Indicador se possui ou não embalagem: S= Sim, N= Não.  — Valores: `S`, `N` |
| `emiteDCe` | string |  | Indicador se a DCe - Declaração de Conteúdo Eletrônica deve ser emitida pelos Correios ou não. Valores possíveis: S ou N. |
| `canalExternoOrigem` | string |  | Identifica o canal de acesso utilizado pelo cliente externo ao acessar serviço. — minLength: `0` — maxLength: `14` |
| `pedidoExternoOrigem` | string |  | Identifica o código de pedido do cliente junto à plataforma externa. — minLength: `0` — maxLength: `25` |

## 4. Objetos da requisição (um a um)

### `RequestPrePostagemExternaDTO`

RequestPrePostagemDTO

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `idCorreios` | string |  | Identificador do usuário no IDCorreios. Obrigatório apenas para credenciais INTERNAS. |
| `remetente` | objeto `RemetenteDTO` | ✔ |  |
| `destinatario` | objeto `DestinatarioDTO` | ✔ |  |
| `codigoServico` | string | ✔ | Código do serviço a ser pré-postado. Não pode ser deixado em branco. Tem que ser um código de serviço válido. O código do serviço deve constar no cartão de postagem do cliente que está fazendo a pré-postagem. — minLength: `0` — maxLength: `8` |
| `precoServico` | string |  | Preço somente do serviço, sem os adicionais. Utilizado apenas em credenciais INTERNAS. |
| `precoPrePostagem` | string |  | Preço total da pré-postagem. Utilizado apenas em credenciais INTERNAS. |
| `numeroNotaFiscal` | string |  | Número da nota fiscal que acompanhará o objeto pré-postado. Obrigatório, na ausência da declaração de conteúdo ou NFe. Tamanho máximo: 12 caracteres numéricos. |
| `numeroCartaoPostagem` | string |  | Número do cartao de postagem - somente para credencial interna. |
| `chaveNFe` | string |  | Chave da Nota Fiscal Eletrônica. Obrigatório, na ausência da declaração de conteúdo ou NF. Se informado, deverá conter 44 caracteres numéricos. |
| `listaServicoAdicional` | array<objeto `ServicoAdicional`> |  | Serviços Adicionais. |
| `itensDeclaracaoConteudo` | array<objeto `ItemDeclaracaoConteudo`> | ✔ | Declaração de conteúdo. Obrigatório. |
| `pesoInformado` | string | ✔ | Peso em gramas do objeto que será pré-postado. Não pode ser deixado em branco. Não pode ser zero. Não pode ser um valor negativo. Não pode exceder o limite máximo definido para o serviço informado. Tamanho máximo: 6 dígitos. — minLength: `0` — maxLength: `6` |
| `codigoFormatoObjetoInformado` | string | ✔ | Código do formato do objeto a ser pré-postado. Não pode ser deixado em branco. Deve ser um dos códigos a seguir: 1-Envelope; 2-Caixa/Pacote; 3-Cilindro/rolo. |
| `alturaInformada` | string |  | Dimensão altura do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informada em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `larguraInformada` | string |  | Dimensão largura do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informada em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `comprimentoInformado` | string |  | Dimensão comprimento do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informado em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `diametroInformado` | string |  | Dimensão diâmetro do objeto a ser pré-postado. Obrigatório para o formato cilindro /rolo. Deve ser informado em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos. |
| `ncmObjeto` | string |  | Código NCM (Nomenclatura Comum do Mercosul) do produto que está sendo pré-postado. Se informado deve conter até 8 caracteres numéricos. |
| `rfidObjeto` | string |  | Código SSCC (do inglês Serial Shipping Container Code ou traduzindo Código de Séria de Unidade Logística) associado à TAG RFID afixado no objeto que está sendo pré-postado. Se informado deverá conter até 24 caracteres numéricos. |
| `cienteObjetoNaoProibido` | string | ✔ | Atributo que indica que o objeto a ser pré-postado é permitido no fluxo postal. Não pode ser deixada em branco. Deverá ser informado "0" ou "1". Onde  "0" – Indica que o cliente está pré-postando um objeto proibido no fluxo postal e "1" – Indica que o cliente está pré-postando um objeto permitido no fluxo postal |
| `idAtendimento` | string |  | ID do atendimento. Utilizado apenas em credenciais INTERNAS |
| `solicitarColeta` | string |  | Indica se o objeto vai ter uma coleta gerada, valores possíveis: S ou N. — pattern: `^(?:S\|N)$` |
| `codigoObjeto` | string |  | Código identificador do objeto. Tamanho máximo: 13 caracteres. — pattern: `^[A-Z]{2}[0-9]{9}[A-Z]{2}$` |
| `dataPrevistaPostagem` | string |  | Data provável da postagem (obrigatório apenas para o segmento de mensagem) — pattern: `dd/MM/yyyy` — Ex.: `01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)` |
| `observacao` | string |  | Informações complementares. Tamanho máximo: 50 caracteres. — minLength: `0` — maxLength: `50` |
| `modalidadePagamento` | string |  | Modalidade Pagamento: 1-à vista, 2-à faturar, 3- à avista e à afaturar e 4- Prestação de contas recebimento ou pagamento |
| `logisticaReversa` | string |  | Indicador se possui ou não logística reversa: S= Sim, N= Não. — Valores: `S`, `N` |
| `dataValidadeLogReversa` | string |  | Data de validade da logistica reversa — pattern: `^([0-2][0-9]\|\|3[0-1])/(0[0-9]\|\|1[0-2])/([0-9][0-9])?[0-9][0-9]$` — Ex.: `01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)` |
| `prazoPostagem` | string |  | Data de limite para postagem do objeto. Após essa data, caso o objeto não seja postado, o mesmo será expirado. Data default será a data de criação + 14 dias, caso não seja informada. Data máxima de 90 dias a contar da data de criação. — pattern: `^([0-2][0-9]\|\|3[0-1])/(0[0-9]\|\|1[0-2])/([0-9][0-9])?[0-9][0-9]$` — Ex.: `10/05/2025` |
| `codigoObjetoIda` | string |  | Código identificador do objeto associado a logística reversa. Tamanho máximo: 13 caracteres. — pattern: `^[A-Z]{2}[0-9]{9}[A-Z]{2}$` |
| `embalagem` | string |  | Indicador se possui ou não embalagem: S= Sim, N= Não.  — Valores: `S`, `N` |
| `emiteDCe` | string |  | Indicador se a DCe - Declaração de Conteúdo Eletrônica deve ser emitida pelos Correios ou não. Valores possíveis: S ou N. |
| `canalExternoOrigem` | string |  | Identifica o canal de acesso utilizado pelo cliente externo ao acessar serviço. — minLength: `0` — maxLength: `14` |
| `pedidoExternoOrigem` | string |  | Identifica o código de pedido do cliente junto à plataforma externa. — minLength: `0` — maxLength: `25` |

### `RemetenteDTO`

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string | ✔ | O preenchimento do Nome do remetente é obrigatório. Deve ser composto de nome e sobrenome. Não pode ser deixado em branco, mínimo de 3 caracteres e máximo 50 caracteres |
| `codigo` | string |  | Código |
| `indicadorMalote` | string |  | Indicador de malote. Para malotes será sempre S. |
| `dddTelefone` | string |  | DDD do telefone do remetente. Se informado, deve ter 2 caracteres numéricos. |
| `telefone` | string |  | Número do telefone do remetente. Se informado, deve ter 8 caracteres numéricos. |
| `dddCelular` | string |  | DDD do celular do remetente. Se informado, deve ter 2 caracteres numéricos. |
| `celular` | string |  | Número do celular do remetente. Se informado, deve ter 9 caracteres numéricos. — minLength: `0` — maxLength: `9` |
| `email` | string |  | E-mail do remetente. É Obrigatório se for logística reversa, deve ter no máximo 255 caracteres e seguir o formato padrão "login@domínio". |
| `cpfCnpj` | string |  | CPF ou CNPJ do remetente. É obrigatório se não for informado documento estrangeiro, deve ser válido e ter no máximo 14 caracteres numéricos. |
| `documentoEstrangeiro` | string |  | Passaporte ou RNM do remetente da prepostagem. É obrigatório se não for informado CPF ou CNPJ e deve ter no máximo 30 caracteres. |
| `obs` | string |  | Observação do remetente preenchida a critério do cliente. Tamanho máximo: 100 caracteres |
| `endereco` | objeto `EnderecoRemetenteDTO` | ✔ | Endereço do remetente |

### `EnderecoRemetenteDTO`

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `cep` | string | ✔ | CEP do remetente. Campo obrigatório, deve ser informado CEP válido com 8 caracteres numéricos. — minLength: `0` — maxLength: `8` |
| `logradouro` | string | ✔ | Logradouro do remetente. Campo obrigatório. Tamanho máximo: 50 caracteres |
| `numero` | string | ✔ | Número do logradouro do remetente. Campo obrigatório. Tamanho máximo: 6 caracteres. |
| `complemento` | string |  | Complemento do logradouro do remetente. Tamanho máximo: 30 caracteres. |
| `bairro` | string | ✔ | Bairro do remetente. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `cidade` | string | ✔ | Cidade do remetente. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `uf` | string | ✔ | UF do CEP do remetente. Campo obrigatório. Tamanho máximo: 2 caracteres. |
| `pais` | string |  | País do remetente. Tamanho máximo: 50 caracteres. Default Brasil |

### `DestinatarioDTO`

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `nome` | string | ✔ | O preenchimento do Nome do destinatário é obrigatório. Deve ser composto de nome e sobrenome. Não pode ser deixado em branco, mínimo de 3 caracteres e máximo 50 caracteres. |
| `codigo` | string |  | Código |
| `indicadorMalote` | string |  | Indicador de malote. Para malotes será sempre S |
| `dddTelefone` | string |  | DDD do telefone do destinatário. Se informado, deve ter 2 caracteres numéricos. |
| `ddiTelefone` | string |  | DDI do telefone do destinatário. Se informado, deve ter 3 caracteres numéricos. Utilizado em prepostagens internacionais. — minLength: `0` — maxLength: `3` |
| `telefone` | string |  | Número do telefone do destinatário. Se informado, deve ter 8 caracteres numéricos. |
| `dddCelular` | string |  | DDD do celular do destinatário. Obrigatório se a entrega for em locker. Se informado, deve ter 2 caracteres numéricos. |
| `ddiCelular` | string |  | DDI do celular do destinatário. Se informado, deve ter 3 caracteres numéricos. Utilizado em prepostagens internacionais. — minLength: `0` — maxLength: `3` |
| `celular` | string |  | Número do celular do destinatário. Obrigatório se a entrega for em locker.Se informado, deve ter 9 caracteres numéricos. — minLength: `0` — maxLength: `9` |
| `email` | string |  | E-mail do destinatário. Se informado, deve ter no máximo 255 caracteres e seguir o formato padrão "login@domínio". |
| `cpfCnpj` | string |  | CPF ou CNPJ do destinatário. Obrigatório se a entrega for em locker. Se informado, deve ser válido. |
| `documentoEstrangeiro` | string |  | Passaporte ou RNM do destinatário da prepostagem. Tamanho máximo: 30 caracteres. |
| `obs` | string |  | Observação do destinatário preenchida a critério do cliente. Tamanho máximo: 100 caracteres |
| `endereco` | objeto `EnderecoDestinatarioDTO` |  | Endereço do destinatário |

### `EnderecoDestinatarioDTO`

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `cep` | string | ✔ | CEP do destinatário. Informar um CEP válido com 8 caracteres numéricos. Caso o endereço seja internacional, informar até 17 caracteres. — minLength: `0` — maxLength: `17` |
| `logradouro` | string | ✔ | Logradouro do destinatário. Campo obrigatório. Tamanho máximo: 50 caracteres |
| `numero` | string | ✔ | Número do logradouro do destinatário. Campo obrigatório. Tamanho máximo: 6 caracteres. |
| `complemento` | string |  | Complemento do logradouro do destinatário. Tamanho máximo: 30 caracteres. |
| `bairro` | string | ✔ | Bairro do destinatário. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `cidade` | string | ✔ | cidade do destinatário. Campo obrigatório. Tamanho máximo: 30 caracteres. |
| `uf` | string | ✔ | UF do CEP do destinatário. Campo obrigatório. Tamanho máximo: 2 caracteres. |
| `regiao` | string | ✔ | Região. Utilizado em prepostagens internacionais. Tamanho máximo: 50 caracteres. — minLength: `0` — maxLength: `50` |
| `pais` | string |  | Sigla do País do destinatário. Utilizado em prepostagens internacionais. Tamanho máximo: 2 caracteres. Exemplo: BR, US, CA, FR — minLength: `0` — maxLength: `2` |

### `ServicoAdicional`

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigoServicoAdicional` | string |  | Código do serviço adicional. Não pode ser deixado em branco. Informar com 3 caracteres. |
| `tipoServicoAdicional` | string |  | Tipo de serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `nomeServicoAdicional` | string |  | Nome do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `valorServicoAdicional` | string |  | Valor do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `valorDeclarado` | string |  | Valor declarado do objeto a ser pré-postado. Não pode ser deixado em branco caso o serviço adicional seja do tipo valor declarado. |
| `siglaServicoAdicional` | string |  | Sigla do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `objetoIdaSimultanea` | string |  | Código do objeto de ida simultânea para o serviço adicional 016. — minLength: `0` — maxLength: `20` |
| `orientacaoEntregaVizinho` | string |  | Orientação para entrega no vizinho. Obrigatório se o serviço adicional (011) for selecionado. — minLength: `0` — maxLength: `20` |
| `tipoChecklist` | string |  | Indica o tipo de checklist que deverá ser preenchido no momento da postagem. Aceita apenas 01-Celular, 04-Eletrônico, 05-Documento ou 07-Conteúdo. — minLength: `0` — maxLength: `3` |
| `subitensCheckList` | array<objeto `SubitemCheckList`> |  | Lista de códigos dos subitens para o tipoChecklist: 05-documentos. Podem ser listados até 8 códigos de documentos. |

### `SubitemCheckList`

Subitem para o tipoChecklist: 05-documentos

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigo` | string |  | Código do subitem. |

### `ItemDeclaracaoConteudo`

Itens da declaração de conteúdo. Obrigatório informar todos os atributos (conteudo, quantidade e valor).

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `conteudo` | string | ✔ | Descrição do item. Obrigatório. Tamanho mínimo 2 e tamanho máximo: 60. Não são aceitos apenas caracteres iguais. Caso informados apenas dois caracteres, os dois devem ser letras. |
| `quantidade` | string | ✔ | Quantidade total de itens de um mesmo tipo. Obrigatório. Tamanho máximo: 11 dígitos. |
| `valor` | string | ✔ | Valor Unitário do Item. Obrigatório. Tamanho máximo: 19 dígitos e 2 casas decimais. |

## 5. Exemplos de corpo

### 5.1 Exemplo nacional — pacote com NF-e e valor declarado

Valores ilustrativos (dados fictícios). Os códigos de serviço e de serviço adicional dependem do seu contrato/cartão.

```json
{
  "remetente": {
    "nome": "Loja Exemplo Ltda",
    "dddCelular": "11",
    "celular": "999999999",
    "email": "contato@exemplo.com.br",
    "cpfCnpj": "00000000000000",
    "endereco": {
      "cep": "06700000",
      "logradouro": "Rua Exemplo",
      "numero": "100",
      "complemento": "Sala 1",
      "bairro": "Centro",
      "cidade": "Cotia",
      "uf": "SP"
    }
  },
  "destinatario": {
    "nome": "Maria da Silva",
    "dddCelular": "21",
    "celular": "988888888",
    "email": "maria@exemplo.com",
    "cpfCnpj": "00000000000",
    "endereco": {
      "cep": "20040000",
      "logradouro": "Avenida Exemplo",
      "numero": "200",
      "complemento": "Apto 10",
      "bairro": "Centro",
      "cidade": "Rio de Janeiro",
      "uf": "RJ",
      "regiao": ""
    }
  },
  "codigoServico": "<código do serviço do seu cartão>",
  "pesoInformado": "500",
  "codigoFormatoObjetoInformado": "2",
  "alturaInformada": "10",
  "larguraInformada": "15",
  "comprimentoInformado": "20",
  "cienteObjetoNaoProibido": "1",
  "chaveNFe": "<44 dígitos da NF-e>",
  "itensDeclaracaoConteudo": [
    {
      "conteudo": "Teclado para notebook",
      "quantidade": "1",
      "valor": "150.00"
    }
  ],
  "listaServicoAdicional": [
    {
      "codigoServicoAdicional": "<3 caracteres>",
      "valorDeclarado": "150.00"
    }
  ],
  "observacao": "Pedido 12345"
}
```

### 5.2 Esqueleto com todos os campos

Preenchido com os exemplos da especificação quando existem; caso contrário, valores genéricos.

```json
{
  "idCorreios": "string",
  "remetente": {
    "nome": "string",
    "codigo": "string",
    "indicadorMalote": "string",
    "dddTelefone": "string",
    "telefone": "string",
    "dddCelular": "string",
    "celular": "string",
    "email": "string",
    "cpfCnpj": "string",
    "documentoEstrangeiro": "string",
    "obs": "string",
    "endereco": {
      "cep": "string",
      "logradouro": "string",
      "numero": "string",
      "complemento": "string",
      "bairro": "string",
      "cidade": "string",
      "uf": "string",
      "pais": "string"
    }
  },
  "destinatario": {
    "nome": "string",
    "codigo": "string",
    "indicadorMalote": "string",
    "dddTelefone": "string",
    "ddiTelefone": "string",
    "telefone": "string",
    "dddCelular": "string",
    "ddiCelular": "string",
    "celular": "string",
    "email": "string",
    "cpfCnpj": "string",
    "documentoEstrangeiro": "string",
    "obs": "string",
    "endereco": {
      "cep": "string",
      "logradouro": "string",
      "numero": "string",
      "complemento": "string",
      "bairro": "string",
      "cidade": "string",
      "uf": "string",
      "regiao": "string",
      "pais": "string"
    }
  },
  "codigoServico": "string",
  "precoServico": "string",
  "precoPrePostagem": "string",
  "numeroNotaFiscal": "string",
  "numeroCartaoPostagem": "string",
  "chaveNFe": "string",
  "listaServicoAdicional": [
    {
      "codigoServicoAdicional": "string",
      "tipoServicoAdicional": "string",
      "nomeServicoAdicional": "string",
      "valorServicoAdicional": "string",
      "valorDeclarado": "string",
      "siglaServicoAdicional": "string",
      "objetoIdaSimultanea": "string",
      "orientacaoEntregaVizinho": "string",
      "tipoChecklist": "string",
      "subitensCheckList": [
        {
          "codigo": "string"
        }
      ]
    }
  ],
  "itensDeclaracaoConteudo": [
    {
      "conteudo": "string",
      "quantidade": "string",
      "valor": "string"
    }
  ],
  "pesoInformado": "string",
  "codigoFormatoObjetoInformado": "string",
  "alturaInformada": "string",
  "larguraInformada": "string",
  "comprimentoInformado": "string",
  "diametroInformado": "string",
  "ncmObjeto": "string",
  "rfidObjeto": "string",
  "cienteObjetoNaoProibido": "string",
  "idAtendimento": "string",
  "solicitarColeta": "string",
  "codigoObjeto": "string",
  "dataPrevistaPostagem": "01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)",
  "observacao": "string",
  "modalidadePagamento": "string",
  "logisticaReversa": "S",
  "dataValidadeLogReversa": "01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)",
  "prazoPostagem": "10/05/2025",
  "codigoObjetoIda": "string",
  "embalagem": "S",
  "emiteDCe": "string",
  "canalExternoOrigem": "string",
  "pedidoExternoOrigem": "string"
}
```

## 6. Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PrePostagem` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o cartão/contrato. | — |

### 6.1 Campos principais da resposta 200

| Campo | Uso |
|---|---|
| `id` | Identificador da pré-postagem — guardar; usado em `idsPrePostagem` para gerar o rótulo e em consultas/cancelamento |
| `codigoObjeto` | Código de rastreio (13 caracteres) — guardar; usado nas APIs SRO Rastro e Webhook |
| `statusAtual` | `1` PREATENDIDO · `2` PREPOSTADO · `3` POSTADO · `4` EXPIRADO · `5` CANCELADO · `6` ESTORNADO · `7` PENDENTE |
| `prazoPostagem` | Prazo máximo para postar o objeto na agência |

### 6.2 Resposta 200 — `PrePostagem` (todos os campos)

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `id` | string |  |  |
| `idCorreios` | string |  | idCorreios de quem fez a postagem à vista |
| `numeroCartaoPostagem` | string |  | Cartão de postagem a faturar. |
| `codigoObjeto` | string |  |  |
| `codigoServico` | string | ✔ | Código do serviço. — minLength: `0` — maxLength: `8` |
| `listaServicoAdicional` | array<objeto `ServicoAdicional`> |  |  |
| `listaServicoAdicional[].codigoServicoAdicional` | string |  | Código do serviço adicional. Não pode ser deixado em branco. Informar com 3 caracteres. |
| `listaServicoAdicional[].tipoServicoAdicional` | string |  | Tipo de serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].nomeServicoAdicional` | string |  | Nome do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].valorServicoAdicional` | string |  | Valor do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].valorDeclarado` | string |  | Valor declarado do objeto a ser pré-postado. Não pode ser deixado em branco caso o serviço adicional seja do tipo valor declarado. |
| `listaServicoAdicional[].siglaServicoAdicional` | string |  | Sigla do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `listaServicoAdicional[].objetoIdaSimultanea` | string |  | Código do objeto de ida simultânea para o serviço adicional 016. — minLength: `0` — maxLength: `20` |
| `listaServicoAdicional[].orientacaoEntregaVizinho` | string |  | Orientação para entrega no vizinho. Obrigatório se o serviço adicional (011) for selecionado. — minLength: `0` — maxLength: `20` |
| `listaServicoAdicional[].tipoChecklist` | string |  | Indica o tipo de checklist que deverá ser preenchido no momento da postagem. Aceita apenas 01-Celular, 04-Eletrônico, 05-Documento ou 07-Conteúdo. — minLength: `0` — maxLength: `3` |
| `listaServicoAdicional[].subitensCheckList` | array<objeto `SubitemCheckList`> |  | Lista de códigos dos subitens para o tipoChecklist: 05-documentos. Podem ser listados até 8 códigos de documentos. |
| `listaServicoAdicional[].subitensCheckList[].codigo` | string |  | Código do subitem. |
| `modalidadePagamento` | integer (int32) |  | Modalidade de pagamento: 1 - à vista, 2 - a faturar, 3 - à vista e a faturar |
| `precoServico` | number |  | Preço somente do serviço, sem os adicionais. |
| `precoPrePostagem` | number |  | Preço total da pré-postagem. |
| `numeroNotaFiscal` | string |  | Número Nota Fiscal. |
| `chaveNFe` | string |  | Chave de acesso à NFe. |
| `itensDeclaracaoConteudo` | array<objeto `ItemDeclaracaoConteudo`> |  |  |
| `itensDeclaracaoConteudo[].conteudo` | string | ✔ | Descrição do item. Obrigatório. Tamanho mínimo 2 e tamanho máximo: 60. Não são aceitos apenas caracteres iguais. Caso informados apenas dois caracteres, os dois devem ser letras. |
| `itensDeclaracaoConteudo[].quantidade` | string | ✔ | Quantidade total de itens de um mesmo tipo. Obrigatório. Tamanho máximo: 11 dígitos. |
| `itensDeclaracaoConteudo[].valor` | string | ✔ | Valor Unitário do Item. Obrigatório. Tamanho máximo: 19 dígitos e 2 casas decimais. |
| `pesoInformado` | string |  | Peso, em gramas, do objeto. — minLength: `0` — maxLength: `10` |
| `codigoFormatoObjetoInformado` | string |  | Formato do objeto: 1 - Envelope, 2 - Caixa/Pacote; 3 - Rolo/Cilindro |
| `alturaInformada` | string |  | Altura, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `larguraInformada` | string |  | Largura, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `comprimentoInformado` | string |  | Comprimento, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `diametroInformado` | string |  | Diâmetro, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `ncmObjeto` | string |  |  |
| `rfidObjeto` | string |  |  |
| `cienteObjetoNaoProibido` | integer (int32) |  | Indicação de que o objeto não é proibido no fluxo postal: 0 - não, 1 - sim |
| `idAtendimento` | string |  | ID do atendimento. |
| `solicitarColeta` | string |  | Indica se o objeto vai ter uma coleta gerada, valores possíveis: S=Coleta solicitada pelo cliente e requisitada ao serviço de coleta, E=Coleta solicitada pelo cliente, mas ocorreu erro ao requisitar o serviço à Api coleta e N=Coleta não solicitada pelo cliente. — default: `N` |
| `nuColeta` | string |  | Número da coleta do objeto. |
| `dataPrevistaPostagem` | string (date) |  | Data de pretensão da postagem |
| `eticket` | string |  |  |
| `dataEticket` | string (date) |  |  |
| `checkList` | string |  |  |
| `controleCliente` | string |  |  |
| `observacao` | string |  |  |
| `embalagem` | string |  | Valores: `S`, `N` |
| `logisticaReversa` | string |  | Indica se o objeto é logistica reversa, valores possíveis: S=Sim, N=não. |
| `dataValidadeLogReversa` | string (date-time) |  | Data e hora de validade da logística reversa. |
| `servico` | string |  | Descrição do código do serviço |
| `statusAtual` | integer (int32) |  | Status da Pré-postagem: 1 - PREATENDIDO, 2 - PREPOSTADO, 3 - POSTADO, 4 - EXPIRADO, 5 - CANCELADO , 6 - ESTORNADO, 7 - PENDENTE |
| `dataHoraStatusAtual` | string (date-time) |  | Data em que foi configurada o status atual |
| `codigoObjetoIda` | string |  | Objeto de ida associado a logística reversa. |
| `objetoVoltaSimultanea` | string |  | Código do objeto de volta para o serviço adicional Logistica Reversa Simultanea — minLength: `0` — maxLength: `20` |
| `emiteDCe` | string |  | Indicador de emissão de DCe. |
| `descStatusAtual` | string |  |  |
| `dataHora` | string (date-time) |  |  |
| `tipoRotulo` | string |  | minLength: `0` — maxLength: `5` |
| `retornoIntegracao` | string |  | Mensagens retornadas pela integração da Api coleta e geração do código de etiqueta. |
| `pedidoExternoOrigem` | string |  | Codigo do pedido informado pelo cliente externo. |
| `canalExternoOrigem` | string |  | Identificaçáo do canal origem informado pelo cliente externo. |
| `sistemaOrigem` | string |  |  |
| `valorTotalBens` | number |  |  |
| `listaIdPrepostagemObjetos` | array<string> |  |  |
| `quantidade` | integer (int64) |  |  |
| `reciboSolicitacaoAssincrona` | string |  | IdRecibo da solicitação da geração da pré-postagem asincrona. |
| `reciboSolicitacaoAssincronaRotulo` | string |  | IdRecibo da solicitação da geração do rótulo assincrono. |
| `codigoFormatoObjetoPreAfericao` | string |  | Formato do objeto na pré-aferição. |
| `alturaPreAfericao` | string |  | Altura na pré-aferição. |
| `larguraPreAfericao` | string |  | Largurana pré-aferição. |
| `comprimentoPreAfericao` | string |  | Comprimento na pré-aferição. |
| `diametroPreAfericao` | string |  | Diametro na pré-aferição. |
| `pesoPreAfericao` | string |  | Peso na pré-aferição. |
| `dataHoraPreAfericao` | string (date-time) |  | Data e hora da pré aferição |
| `mcuUnidadePreAfericao` | string |  | Mcu da unidade de pre-aferição. |
| `idBalancaCubagem` | string |  | Identificador da balança de cubagem de pre-aferição. |
| `cepDestinoPreAfericao` | string |  | Cep destino na pre-aferição. — minLength: `0` — maxLength: `8` |
| `ehObjetoDgr` | string |  | Objeto identificado como objeto perigoso (Dangerous Goods Regulations - DGR), valores possíveis: S=Sim, N=não. |
| `tipoObjeto` | string |  | Valores: `S`, `R` |
| `erroAssincrono` | string |  |  |
| `codigoEstampa2D` | string |  | Código de estampa 2D |
| `historicoStatus` | array<objeto `PrePostagemHistoricoStatus`> |  |  |
| `historicoStatus[].id` | integer (int64) |  |  |
| `historicoStatus[].status` | integer (int32) |  | Status da Pré-postagem: 1 - PREATENDIDO, 2 - PREPOSTADO, 3 - POSTADO , 4 - EXPIRADO, 5 - CANCELADO |
| `historicoStatus[].dataHora` | string (date-time) |  |  |
| `prazoPostagem` | string (date-time) |  | Prazo máximo de postagem da pré-postagem. |
| `indicadorMalote` | string |  |  |
| `codigoGrafica` | integer (int32) |  | Código da gráfica. — maximum: `999` |
| `codigoRemetente` | string |  | Código do remetente. |
| `codigoDestinatario` | string |  | Código do destnatário. |
| `malotePrePostagem` | objeto `MalotePrePostagem` |  | Malote |
| `malotePrePostagem.numeroMalote` | string |  | Número do malote. |
| `malotePrePostagem.dataHoraCadastro` | string (date-time) |  | Data e hora do cadastro do malote. |
| `malotePrePostagem.quantidadeObjetos` | integer (int32) |  | Quantidade de objetos do malote. |
| `malotePrePostagem.tipo` | string |  | tipo do malote. — Valores: `C`, `M`, `A` |
| `malotePrePostagem.codigoObjetoMalote` | string |  | Código do objeto malote. |
| `malotePrePostagem.codigoRemetente` | string |  | codigo do Remetente. |
| `malotePrePostagem.codigoDestinatario` | string |  | codigo do Destinatario. |
| `malotePrePostagem.numeroCartaoPostagem` | string |  | Cartão de postagem. |
| `malotePrePostagem.prePostagem` | objeto `PrePostagem` |  | Microsserviço de PrePostagem. |
| `malotePrePostagem.listaCodigosObjetosMalote` | array<string> |  |  |
| `malotePrePostagem.listaObjetos` | array<objeto `PrePostagem`> |  |  |
| `listaMalotes` | array<objeto `MalotePrePostagem`> |  |  |
| `listaMalotes[].numeroMalote` | string |  | Número do malote. |
| `listaMalotes[].dataHoraCadastro` | string (date-time) |  | Data e hora do cadastro do malote. |
| `listaMalotes[].quantidadeObjetos` | integer (int32) |  | Quantidade de objetos do malote. |
| `listaMalotes[].tipo` | string |  | tipo do malote. — Valores: `C`, `M`, `A` |
| `listaMalotes[].codigoObjetoMalote` | string |  | Código do objeto malote. |
| `listaMalotes[].codigoRemetente` | string |  | codigo do Remetente. |
| `listaMalotes[].codigoDestinatario` | string |  | codigo do Destinatario. |
| `listaMalotes[].numeroCartaoPostagem` | string |  | Cartão de postagem. |
| `listaMalotes[].prePostagem` | objeto `PrePostagem` |  | Microsserviço de PrePostagem. |
| `listaMalotes[].listaCodigosObjetosMalote` | array<string> |  |  |
| `listaMalotes[].listaObjetos` | array<objeto `PrePostagem`> |  |  |
| `emailLogisticaReversaEnviado` | string |  | Email logistica reversa enviado (S/N). |
| `motivoCancelamento` | string |  | Erro em geração de DCe que causou eventual cancelamento de prepostagem. |
| `listaTributo` | array<objeto `Tributo`> |  |  |
| `listaTributo[].codigo` | string |  | Código do tributo. |
| `listaTributo[].valor` | string |  | Valor do tributo. |

### 6.3 Resposta de erro (400/500) — `MessageResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `msgs` | array<string> |  | Mensagens |
| `date` | string (date-time) |  | Data e hora de ocorrência do evento. |
| `method` | string |  | Método HTTP que foi requisitado. |
| `path` | string |  | URL que foi requisitada. |
| `causa` | string |  | A causa da mensagem. |
| `stackTrace` | string |  | Detalhe da mensagem. |

## 7. JSON Schema completo da requisição (referências expandidas)

```json
{
  "title": "RequestPrePostagemExternaDTO",
  "type": "object",
  "description": "RequestPrePostagemDTO",
  "properties": {
    "idCorreios": {
      "type": "string",
      "description": "Identificador do usuário no IDCorreios. Obrigatório apenas para credenciais INTERNAS."
    },
    "remetente": {
      "title": "RemetenteDTO",
      "type": "object",
      "properties": {
        "nome": {
          "type": "string",
          "description": "O preenchimento do Nome do remetente é obrigatório. Deve ser composto de nome e sobrenome. Não pode ser deixado em branco, mínimo de 3 caracteres e máximo 50 caracteres"
        },
        "codigo": {
          "type": "string",
          "description": "Código"
        },
        "indicadorMalote": {
          "type": "string",
          "description": "Indicador de malote. Para malotes será sempre S."
        },
        "dddTelefone": {
          "type": "string",
          "description": "DDD do telefone do remetente. Se informado, deve ter 2 caracteres numéricos."
        },
        "telefone": {
          "type": "string",
          "description": "Número do telefone do remetente. Se informado, deve ter 8 caracteres numéricos."
        },
        "dddCelular": {
          "type": "string",
          "description": "DDD do celular do remetente. Se informado, deve ter 2 caracteres numéricos."
        },
        "celular": {
          "type": "string",
          "description": "Número do celular do remetente. Se informado, deve ter 9 caracteres numéricos.",
          "maxLength": 9,
          "minLength": 0
        },
        "email": {
          "type": "string",
          "description": "E-mail do remetente. É Obrigatório se for logística reversa, deve ter no máximo 255 caracteres e seguir o formato padrão \"login@domínio\"."
        },
        "cpfCnpj": {
          "type": "string",
          "description": "CPF ou CNPJ do remetente. É obrigatório se não for informado documento estrangeiro, deve ser válido e ter no máximo 14 caracteres numéricos."
        },
        "documentoEstrangeiro": {
          "type": "string",
          "description": "Passaporte ou RNM do remetente da prepostagem. É obrigatório se não for informado CPF ou CNPJ e deve ter no máximo 30 caracteres."
        },
        "obs": {
          "type": "string",
          "description": "Observação do remetente preenchida a critério do cliente. Tamanho máximo: 100 caracteres"
        },
        "endereco": {
          "title": "EnderecoRemetenteDTO",
          "type": "object",
          "properties": {
            "cep": {
              "type": "string",
              "description": "CEP do remetente. Campo obrigatório, deve ser informado CEP válido com 8 caracteres numéricos.",
              "maxLength": 8,
              "minLength": 0
            },
            "logradouro": {
              "type": "string",
              "description": "Logradouro do remetente. Campo obrigatório. Tamanho máximo: 50 caracteres"
            },
            "numero": {
              "type": "string",
              "description": "Número do logradouro do remetente. Campo obrigatório. Tamanho máximo: 6 caracteres."
            },
            "complemento": {
              "type": "string",
              "description": "Complemento do logradouro do remetente. Tamanho máximo: 30 caracteres."
            },
            "bairro": {
              "type": "string",
              "description": "Bairro do remetente. Campo obrigatório. Tamanho máximo: 30 caracteres."
            },
            "cidade": {
              "type": "string",
              "description": "Cidade do remetente. Campo obrigatório. Tamanho máximo: 30 caracteres."
            },
            "uf": {
              "type": "string",
              "description": "UF do CEP do remetente. Campo obrigatório. Tamanho máximo: 2 caracteres."
            },
            "pais": {
              "type": "string",
              "description": "País do remetente. Tamanho máximo: 50 caracteres. Default Brasil"
            }
          },
          "required": [
            "bairro",
            "cep",
            "cidade",
            "logradouro",
            "numero",
            "uf"
          ],
          "description": "Endereço do remetente"
        }
      },
      "required": [
        "endereco",
        "nome"
      ]
    },
    "destinatario": {
      "title": "DestinatarioDTO",
      "type": "object",
      "properties": {
        "nome": {
          "type": "string",
          "description": "O preenchimento do Nome do destinatário é obrigatório. Deve ser composto de nome e sobrenome. Não pode ser deixado em branco, mínimo de 3 caracteres e máximo 50 caracteres."
        },
        "codigo": {
          "type": "string",
          "description": "Código"
        },
        "indicadorMalote": {
          "type": "string",
          "description": "Indicador de malote. Para malotes será sempre S"
        },
        "dddTelefone": {
          "type": "string",
          "description": "DDD do telefone do destinatário. Se informado, deve ter 2 caracteres numéricos."
        },
        "ddiTelefone": {
          "type": "string",
          "description": "DDI do telefone do destinatário. Se informado, deve ter 3 caracteres numéricos. Utilizado em prepostagens internacionais.",
          "maxLength": 3,
          "minLength": 0
        },
        "telefone": {
          "type": "string",
          "description": "Número do telefone do destinatário. Se informado, deve ter 8 caracteres numéricos."
        },
        "dddCelular": {
          "type": "string",
          "description": "DDD do celular do destinatário. Obrigatório se a entrega for em locker. Se informado, deve ter 2 caracteres numéricos."
        },
        "ddiCelular": {
          "type": "string",
          "description": "DDI do celular do destinatário. Se informado, deve ter 3 caracteres numéricos. Utilizado em prepostagens internacionais.",
          "maxLength": 3,
          "minLength": 0
        },
        "celular": {
          "type": "string",
          "description": "Número do celular do destinatário. Obrigatório se a entrega for em locker.Se informado, deve ter 9 caracteres numéricos.",
          "maxLength": 9,
          "minLength": 0
        },
        "email": {
          "type": "string",
          "description": "E-mail do destinatário. Se informado, deve ter no máximo 255 caracteres e seguir o formato padrão \"login@domínio\"."
        },
        "cpfCnpj": {
          "type": "string",
          "description": "CPF ou CNPJ do destinatário. Obrigatório se a entrega for em locker. Se informado, deve ser válido."
        },
        "documentoEstrangeiro": {
          "type": "string",
          "description": "Passaporte ou RNM do destinatário da prepostagem. Tamanho máximo: 30 caracteres."
        },
        "obs": {
          "type": "string",
          "description": "Observação do destinatário preenchida a critério do cliente. Tamanho máximo: 100 caracteres"
        },
        "endereco": {
          "title": "EnderecoDestinatarioDTO",
          "type": "object",
          "properties": {
            "cep": {
              "type": "string",
              "description": "CEP do destinatário. Informar um CEP válido com 8 caracteres numéricos. Caso o endereço seja internacional, informar até 17 caracteres.",
              "maxLength": 17,
              "minLength": 0
            },
            "logradouro": {
              "type": "string",
              "description": "Logradouro do destinatário. Campo obrigatório. Tamanho máximo: 50 caracteres"
            },
            "numero": {
              "type": "string",
              "description": "Número do logradouro do destinatário. Campo obrigatório. Tamanho máximo: 6 caracteres."
            },
            "complemento": {
              "type": "string",
              "description": "Complemento do logradouro do destinatário. Tamanho máximo: 30 caracteres."
            },
            "bairro": {
              "type": "string",
              "description": "Bairro do destinatário. Campo obrigatório. Tamanho máximo: 30 caracteres."
            },
            "cidade": {
              "type": "string",
              "description": "cidade do destinatário. Campo obrigatório. Tamanho máximo: 30 caracteres."
            },
            "uf": {
              "type": "string",
              "description": "UF do CEP do destinatário. Campo obrigatório. Tamanho máximo: 2 caracteres."
            },
            "regiao": {
              "type": "string",
              "description": "Região. Utilizado em prepostagens internacionais. Tamanho máximo: 50 caracteres.",
              "maxLength": 50,
              "minLength": 0
            },
            "pais": {
              "type": "string",
              "description": "Sigla do País do destinatário. Utilizado em prepostagens internacionais. Tamanho máximo: 2 caracteres. Exemplo: BR, US, CA, FR",
              "maxLength": 2,
              "minLength": 0
            }
          },
          "required": [
            "bairro",
            "cep",
            "cidade",
            "logradouro",
            "numero",
            "regiao",
            "uf"
          ],
          "description": "Endereço do destinatário"
        }
      },
      "required": [
        "nome"
      ]
    },
    "codigoServico": {
      "type": "string",
      "description": "Código do serviço a ser pré-postado. Não pode ser deixado em branco. Tem que ser um código de serviço válido. O código do serviço deve constar no cartão de postagem do cliente que está fazendo a pré-postagem.",
      "maxLength": 8,
      "minLength": 0
    },
    "precoServico": {
      "type": "string",
      "description": "Preço somente do serviço, sem os adicionais. Utilizado apenas em credenciais INTERNAS."
    },
    "precoPrePostagem": {
      "type": "string",
      "description": "Preço total da pré-postagem. Utilizado apenas em credenciais INTERNAS."
    },
    "numeroNotaFiscal": {
      "type": "string",
      "description": "Número da nota fiscal que acompanhará o objeto pré-postado. Obrigatório, na ausência da declaração de conteúdo ou NFe. Tamanho máximo: 12 caracteres numéricos."
    },
    "numeroCartaoPostagem": {
      "type": "string",
      "description": "Número do cartao de postagem - somente para credencial interna."
    },
    "chaveNFe": {
      "type": "string",
      "description": "Chave da Nota Fiscal Eletrônica. Obrigatório, na ausência da declaração de conteúdo ou NF. Se informado, deverá conter 44 caracteres numéricos."
    },
    "listaServicoAdicional": {
      "type": "array",
      "description": "Serviços Adicionais.",
      "items": {
        "title": "ServicoAdicional",
        "type": "object",
        "properties": {
          "codigoServicoAdicional": {
            "type": "string",
            "description": "Código do serviço adicional. Não pode ser deixado em branco. Informar com 3 caracteres."
          },
          "tipoServicoAdicional": {
            "type": "string",
            "description": "Tipo de serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem."
          },
          "nomeServicoAdicional": {
            "type": "string",
            "description": "Nome do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem."
          },
          "valorServicoAdicional": {
            "type": "string",
            "description": "Valor do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem."
          },
          "valorDeclarado": {
            "type": "string",
            "description": "Valor declarado do objeto a ser pré-postado. Não pode ser deixado em branco caso o serviço adicional seja do tipo valor declarado."
          },
          "siglaServicoAdicional": {
            "type": "string",
            "description": "Sigla do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem."
          },
          "objetoIdaSimultanea": {
            "type": "string",
            "description": "Código do objeto de ida simultânea para o serviço adicional 016.",
            "maxLength": 20,
            "minLength": 0
          },
          "orientacaoEntregaVizinho": {
            "type": "string",
            "description": "Orientação para entrega no vizinho. Obrigatório se o serviço adicional (011) for selecionado.",
            "maxLength": 20,
            "minLength": 0
          },
          "tipoChecklist": {
            "type": "string",
            "description": "Indica o tipo de checklist que deverá ser preenchido no momento da postagem. Aceita apenas 01-Celular, 04-Eletrônico, 05-Documento ou 07-Conteúdo.",
            "maxLength": 3,
            "minLength": 0
          },
          "subitensCheckList": {
            "type": "array",
            "description": "Lista de códigos dos subitens para o tipoChecklist: 05-documentos. Podem ser listados até 8 códigos de documentos.",
            "items": {
              "title": "SubitemCheckList",
              "type": "object",
              "description": "Subitem para o tipoChecklist: 05-documentos",
              "properties": {
                "codigo": {
                  "type": "string",
                  "description": "Código do subitem."
                }
              }
            }
          }
        }
      }
    },
    "itensDeclaracaoConteudo": {
      "type": "array",
      "description": "Declaração de conteúdo. Obrigatório.",
      "items": {
        "title": "ItemDeclaracaoConteudo",
        "type": "object",
        "description": "Itens da declaração de conteúdo. Obrigatório informar todos os atributos (conteudo, quantidade e valor).",
        "properties": {
          "conteudo": {
            "type": "string",
            "description": "Descrição do item. Obrigatório. Tamanho mínimo 2 e tamanho máximo: 60. Não são aceitos apenas caracteres iguais. Caso informados apenas dois caracteres, os dois devem ser letras."
          },
          "quantidade": {
            "type": "string",
            "description": "Quantidade total de itens de um mesmo tipo. Obrigatório. Tamanho máximo: 11 dígitos."
          },
          "valor": {
            "type": "string",
            "description": "Valor Unitário do Item. Obrigatório. Tamanho máximo: 19 dígitos e 2 casas decimais."
          }
        },
        "required": [
          "conteudo",
          "quantidade",
          "valor"
        ]
      }
    },
    "pesoInformado": {
      "type": "string",
      "description": "Peso em gramas do objeto que será pré-postado. Não pode ser deixado em branco. Não pode ser zero. Não pode ser um valor negativo. Não pode exceder o limite máximo definido para o serviço informado. Tamanho máximo: 6 dígitos.",
      "maxLength": 6,
      "minLength": 0
    },
    "codigoFormatoObjetoInformado": {
      "type": "string",
      "description": "Código do formato do objeto a ser pré-postado. Não pode ser deixado em branco. Deve ser um dos códigos a seguir: 1-Envelope; 2-Caixa/Pacote; 3-Cilindro/rolo."
    },
    "alturaInformada": {
      "type": "string",
      "description": "Dimensão altura do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informada em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos."
    },
    "larguraInformada": {
      "type": "string",
      "description": "Dimensão largura do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informada em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos."
    },
    "comprimentoInformado": {
      "type": "string",
      "description": "Dimensão comprimento do objeto a ser pré-postado. Obrigatório para o formato caixa/pacote. Deve ser informado em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos."
    },
    "diametroInformado": {
      "type": "string",
      "description": "Dimensão diâmetro do objeto a ser pré-postado. Obrigatório para o formato cilindro /rolo. Deve ser informado em centímetros. Não deve ser menor que o limite mínimo nem maior do que o limite máximo definido para o serviço informado. Tamanho máximo: 3 dígitos."
    },
    "ncmObjeto": {
      "type": "string",
      "description": "Código NCM (Nomenclatura Comum do Mercosul) do produto que está sendo pré-postado. Se informado deve conter até 8 caracteres numéricos."
    },
    "rfidObjeto": {
      "type": "string",
      "description": "Código SSCC (do inglês Serial Shipping Container Code ou traduzindo Código de Séria de Unidade Logística) associado à TAG RFID afixado no objeto que está sendo pré-postado. Se informado deverá conter até 24 caracteres numéricos."
    },
    "cienteObjetoNaoProibido": {
      "type": "string",
      "description": "Atributo que indica que o objeto a ser pré-postado é permitido no fluxo postal. Não pode ser deixada em branco. Deverá ser informado \"0\" ou \"1\". Onde  \"0\" – Indica que o cliente está pré-postando um objeto proibido no fluxo postal e \"1\" – Indica que o cliente está pré-postando um objeto permitido no fluxo postal"
    },
    "idAtendimento": {
      "type": "string",
      "description": "ID do atendimento. Utilizado apenas em credenciais INTERNAS"
    },
    "solicitarColeta": {
      "type": "string",
      "description": "Indica se o objeto vai ter uma coleta gerada, valores possíveis: S ou N.",
      "pattern": "^(?:S|N)$"
    },
    "codigoObjeto": {
      "type": "string",
      "description": "Código identificador do objeto. Tamanho máximo: 13 caracteres.",
      "pattern": "^[A-Z]{2}[0-9]{9}[A-Z]{2}$"
    },
    "dataPrevistaPostagem": {
      "type": "string",
      "description": "Data provável da postagem (obrigatório apenas para o segmento de mensagem)",
      "example": "01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)",
      "pattern": "dd/MM/yyyy"
    },
    "observacao": {
      "type": "string",
      "description": "Informações complementares. Tamanho máximo: 50 caracteres.",
      "maxLength": 50,
      "minLength": 0,
      "title": "Observação"
    },
    "modalidadePagamento": {
      "type": "string",
      "description": "Modalidade Pagamento: 1-à vista, 2-à faturar, 3- à avista e à afaturar e 4- Prestação de contas recebimento ou pagamento",
      "title": "Modalidade Pagamento"
    },
    "logisticaReversa": {
      "type": "string",
      "description": "Indicador se possui ou não logística reversa: S= Sim, N= Não.",
      "enum": [
        "S",
        "N"
      ],
      "title": "Logistica reversa"
    },
    "dataValidadeLogReversa": {
      "type": "string",
      "description": "Data de validade da logistica reversa",
      "example": "01/01/2021 para primeiro de janeiro de 2021 (dd/MM/yyyy)",
      "pattern": "^([0-2][0-9]||3[0-1])/(0[0-9]||1[0-2])/([0-9][0-9])?[0-9][0-9]$"
    },
    "prazoPostagem": {
      "type": "string",
      "description": "Data de limite para postagem do objeto. Após essa data, caso o objeto não seja postado, o mesmo será expirado. Data default será a data de criação + 14 dias, caso não seja informada. Data máxima de 90 dias a contar da data de criação.",
      "example": "10/05/2025",
      "pattern": "^([0-2][0-9]||3[0-1])/(0[0-9]||1[0-2])/([0-9][0-9])?[0-9][0-9]$"
    },
    "codigoObjetoIda": {
      "type": "string",
      "description": "Código identificador do objeto associado a logística reversa. Tamanho máximo: 13 caracteres.",
      "pattern": "^[A-Z]{2}[0-9]{9}[A-Z]{2}$"
    },
    "embalagem": {
      "type": "string",
      "description": "Indicador se possui ou não embalagem: S= Sim, N= Não. ",
      "enum": [
        "S",
        "N"
      ],
      "title": "Embalagem"
    },
    "emiteDCe": {
      "type": "string",
      "description": "Indicador se a DCe - Declaração de Conteúdo Eletrônica deve ser emitida pelos Correios ou não. Valores possíveis: S ou N.",
      "title": "Indicador de emissão de DCe"
    },
    "canalExternoOrigem": {
      "type": "string",
      "description": "Identifica o canal de acesso utilizado pelo cliente externo ao acessar serviço.",
      "maxLength": 14,
      "minLength": 0,
      "title": "Canal Externo Origem"
    },
    "pedidoExternoOrigem": {
      "type": "string",
      "description": "Identifica o código de pedido do cliente junto à plataforma externa.",
      "maxLength": 25,
      "minLength": 0,
      "title": "Pedido Externo Origem"
    }
  },
  "required": [
    "cienteObjetoNaoProibido",
    "codigoFormatoObjetoInformado",
    "codigoServico",
    "destinatario",
    "itensDeclaracaoConteudo",
    "pesoInformado",
    "remetente"
  ]
}
```

## 8. Orientações de implementação

1. **Token:** use o Bearer da API Token (`/v1/autentica/cartaopostagem`) e reutilize-o até perto de `expiraEm`; em 401, renove uma vez e repita.
2. **Validação antes de enviar:** implemente no seu sistema as regras das seções 1 e 2 (obrigatórios, formato × dimensões, documento fiscal, tamanhos máximos, S/N). Isso evita erros 400 e dá mensagens claras ao usuário.
3. **Tipos:** vários campos numéricos (peso, medidas, quantidade, valor) são `string` na especificação — envie como texto (`"500"`). Valores monetários com ponto decimal e 2 casas (`"150.00"`).
4. **Normalização:** CEP, CPF/CNPJ, DDD e telefone somente com dígitos; UF com 2 letras maiúsculas; respeite os tamanhos máximos (truncar ou alertar o usuário).
5. **Serviço:** `codigoServico` deve constar no seu cartão de postagem — mantenha no sistema a lista dos serviços contratados (obtida em `correios-02-meucontrato-servicos-cartao-api.md`) e use o mesmo código nas APIs Preço e Prazo.
6. **Documento fiscal:** para venda com NF-e, envie `chaveNFe`; sem nota, envie a declaração de conteúdo detalhada (`itensDeclaracaoConteudo`) e, se desejado, `emiteDCe: "S"`. A impressão da DACE e a inclusão da chave depois da criação estão em `correios-14-prepostagem-dce-api.md`.
7. **Credenciais internas:** não envie `idCorreios`, `precoServico`, `precoPrePostagem`, `numeroCartaoPostagem` e `idAtendimento` — são de uso interno dos Correios.
8. **Persistência:** grave `id`, `codigoObjeto`, `statusAtual` e `prazoPostagem` vinculados ao pedido; eles alimentam a etiqueta, o rastreamento e o controle de prazo.
9. **Erros 400:** exiba `msgs` ao usuário e registre o corpo enviado para diagnóstico. Não reenvie automaticamente sem corrigir os dados.
10. **Duplicidade:** evite criar duas pré-postagens para o mesmo pedido (ex.: bloqueio por pedido enquanto a chamada está em andamento; use `pedidoExternoOrigem`/`observacao` para referenciar o pedido).
11. **Prazo de postagem:** monitore `prazoPostagem`; pré-postagens não postadas até a data expiram (`statusAtual = 4`). Pela especificação, o padrão é criação + 14 dias (máximo 90); o manual V2.4 cita 7 dias — prevalece o valor retornado.
12. **Ambientes:** mantenha a URL base configurável (homologação × produção); pré-postagens de homologação não geram objetos reais.
