# Correios — Pré-postagem: Pré-Postagem Nacional Assíncrona (Objetos Registrados)

> Documento de referência para desenvolvimento. Gerado a partir da especificação OpenAPI oficial (`https://apihom.correios.com.br/prepostagem/v3/api-docs`), API **Pré-postagem** versão **2.0.32** (homologação), em 27/09/2026. Cobre apenas os 4 endpoints da seção "Pré-Postagem Nacional Assíncrona (Objetos Registrados)". As seções 1 a 6 reproduzem a especificação; a seção 7 traz orientações de implementação derivadas dela.
>
> **Sequência de testes:** etapa 07 de 14 · Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Anterior: `correios-06-prepostagem-criar-api.md` · Próxima: `correios-08-rotulo-solicitar-assincrono-pdf-api.md`

## 0. Resumo rápido

- **Finalidade:** criar e cancelar pré-postagens de objetos **registrados** de forma **assíncrona**: a solicitação devolve um **recibo** e o resultado é consultado depois.
- **Autenticação:** `Authorization: Bearer <token>` — API Token (`/v1/autentica/cartaopostagem`). Ver `correios-01-token-api.md`.
- **Corpo da criação:** o mesmo schema do `POST /v1/prepostagens` síncrono (`RequestPrePostagemExternaDTO`). Ver também `correios-06-prepostagem-criar-api.md`.

| Ambiente | URL base |
|---|---|
| Produção | `https://api.correios.com.br/prepostagem` |
| Homologação | `https://apihom.correios.com.br/prepostagem` |

| # | Método | Caminho | Função | Resposta 200 |
|---|---|---|---|---|
| 1 | POST | `/v1/prepostagens/assincrona` | Permite solicitar a criação de uma Pré-Postagem de forma assíncrona. | `string` (recibo) |
| 2 | GET | `/v1/prepostagens/assincrona/{recibo}` | Permite solicitar o status do processamento de uma Pré-Postagem assincrona. | `PrePostagemAssincronaResponse` |
| 3 | DELETE | `/v1/prepostagens/assincrona` | Cancela uma pré-postagem pelo código do objeto. | `object` |
| 4 | GET | `/v1/prepostagens/canceladas/assincrona/{idRecibo}` | Consulta processamento das requisições de cancelamento assincrona de pré-postagens. | `object` |

### Fluxos

```
CRIAÇÃO
1. POST /v1/prepostagens/assincrona            → 200 com o recibo (string)
2. GET  /v1/prepostagens/assincrona/{recibo}   → situacao: PROCESSADO | FALHA | NAO_LOCALIZADO
   → PROCESSADO: prePostagens[] traz id e codigoObjeto de cada pré-postagem criada
   → FALHA: ver mensagens[] e sequencias[].mensagens[]
3. POST /v1/prepostagens/rotulo/assincrono/pdf → etiqueta (usar os ids retornados)

CANCELAMENTO
1. DELETE /v1/prepostagens/assincrona (corpo com codigosObjetos ou idsPrePostagens) → 200 (recibo esperado)
2. GET    /v1/prepostagens/canceladas/assincrona/{idRecibo}                       → resultado do cancelamento
```

## 1. `POST /v1/prepostagens/assincrona`

**Permite solicitar a criação de uma Pré-Postagem de forma assíncrona.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/assincrona`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/assincrona`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `prepostaAssincrona`

### 1.1 Corpo da requisição — `RequestPrePostagemExternaDTO` (obrigatório)

> A especificação declara o corpo como **um único objeto** `RequestPrePostagemExternaDTO` (não uma lista). A resposta de status, porém, traz listas (`sequencias[].ordem`, `prePostagens[]`), o que sugere processamento de mais de um item. Confirme em homologação se o endpoint aceita também uma lista.

**Campos obrigatórios (checklist):**

| Campo | Regra |
|---|---|
| `remetente` | `nome` (3 a 50 caracteres, nome e sobrenome) e `endereco` (`cep`, `logradouro`, `numero`, `bairro`, `cidade`, `uf`) |
| `destinatario` | `nome`; `endereco` com `cep`, `logradouro`, `numero`, `bairro`, `cidade`, `uf`, `regiao` |
| `codigoServico` | Código de serviço válido e presente no cartão de postagem |
| `pesoInformado` | Gramas; > 0; dentro do limite do serviço; até 6 dígitos |
| `codigoFormatoObjetoInformado` | `1` Envelope · `2` Caixa/Pacote · `3` Cilindro/Rolo |
| `cienteObjetoNaoProibido` | `"1"` = objeto permitido (`"0"` = proibido) |
| `itensDeclaracaoConteudo` | Lista com `conteudo` (2 a 60), `quantidade`, `valor` unitário |

**Regras condicionais:**

| Situação | Regra |
|---|---|
| Formato `2` | `alturaInformada`, `larguraInformada`, `comprimentoInformado` obrigatórios (cm, até 3 dígitos) |
| Formato `3` | `comprimentoInformado` e `diametroInformado` obrigatórios |
| Documento fiscal | `numeroNotaFiscal` (até 12 dígitos) **ou** `chaveNFe` (44 dígitos) **ou** declaração de conteúdo |
| Valor declarado | `listaServicoAdicional[].valorDeclarado` obrigatório; `codigoServicoAdicional` com 3 caracteres |
| Entrega em locker | `destinatario.cpfCnpj` obrigatório |
| Telefones | DDD 2 dígitos; telefone 8 dígitos |
| Uso interno dos Correios | `idCorreios`, `precoServico`, `precoPrePostagem`, `numeroCartaoPostagem`, `idAtendimento` — não enviar |
| Indicadores S/N | `logisticaReversa`, `embalagem`, `emiteDCe`, `solicitarColeta` |

> Inconsistências da especificação a validar: `itensDeclaracaoConteudo` obrigatório × NF/NF-e como alternativa; `regiao` obrigatório no destinatário × descrito como uso internacional.

### 1.2 Todos os campos da requisição (91)

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

### 1.3 Objetos da requisição

#### `RequestPrePostagemExternaDTO`

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

#### `RemetenteDTO`

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

#### `EnderecoRemetenteDTO`

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

#### `DestinatarioDTO`

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

#### `EnderecoDestinatarioDTO`

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

#### `ServicoAdicional`

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

#### `SubitemCheckList`

Subitem para o tipoChecklist: 05-documentos

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigo` | string |  | Código do subitem. |

#### `ItemDeclaracaoConteudo`

Itens da declaração de conteúdo. Obrigatório informar todos os atributos (conteudo, quantidade e valor).

| Campo | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `conteudo` | string | ✔ | Descrição do item. Obrigatório. Tamanho mínimo 2 e tamanho máximo: 60. Não são aceitos apenas caracteres iguais. Caso informados apenas dois caracteres, os dois devem ser letras. |
| `quantidade` | string | ✔ | Quantidade total de itens de um mesmo tipo. Obrigatório. Tamanho máximo: 11 dígitos. |
| `valor` | string | ✔ | Valor Unitário do Item. Obrigatório. Tamanho máximo: 19 dígitos e 2 casas decimais. |

### 1.4 Exemplo de corpo (esqueleto)

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

### 1.5 Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `string` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o cartão/contrato. | — |

A resposta 200 é uma **string**: o número do recibo, a ser usado em `GET /v1/prepostagens/assincrona/{recibo}`. Confirme em homologação se vem como texto puro ou entre aspas (JSON string).

## 2. `GET /v1/prepostagens/assincrona/{recibo}`

**Permite solicitar o status do processamento de uma Pré-Postagem assincrona.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/assincrona/{recibo}`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/assincrona/{recibo}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPrePostagemAssincrona`

### 2.1 Parâmetros

| Nome | Em | Tipo | Obrig. | Descrição |
|---|---|---|:-:|---|
| `recibo` | path | string | ✔ | Recibo da solicitação da pré-postagem assíncrona |

```http
GET https://apihom.correios.com.br/prepostagem/v1/prepostagens/assincrona/{recibo}
Authorization: Bearer <token>
```

### 2.2 Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `PrePostagemAssincronaResponse` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o cartão/contrato. | — |

### 2.3 `PrePostagemAssincronaResponse` — campos principais

| Campo | Tipo | Significado |
|---|---|---|
| `situacao` | string | `PROCESSADO` · `FALHA` · `NAO_LOCALIZADO` |
| `mensagens` | array<string> | Mensagens gerais do processamento |
| `sequencias` | array<`PrePostagemAssincronaDetalheResponse`> | Detalhe por item: `ordem` e `mensagens` |
| `prePostagens` | array<`PrePostagem`> | Pré-postagens criadas (com `id`, `codigoObjeto`, `statusAtual`, `prazoPostagem`...) |

`PrePostagem.statusAtual`: `1` PREATENDIDO · `2` PREPOSTADO · `3` POSTADO · `4` EXPIRADO · `5` CANCELADO · `6` ESTORNADO · `7` PENDENTE.

### 2.4 Todos os campos da resposta 200

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `situacao` | string |  | Valores: `PROCESSADO`, `FALHA`, `NAO_LOCALIZADO` |
| `mensagens` | array<string> |  |  |
| `sequencias` | array<objeto `PrePostagemAssincronaDetalheResponse`> |  |  |
| `sequencias[].ordem` | string |  |  |
| `sequencias[].mensagens` | array<string> |  |  |
| `prePostagens` | array<objeto `PrePostagem`> |  |  |
| `prePostagens[].id` | string |  |  |
| `prePostagens[].idCorreios` | string |  | idCorreios de quem fez a postagem à vista |
| `prePostagens[].numeroCartaoPostagem` | string |  | Cartão de postagem a faturar. |
| `prePostagens[].codigoObjeto` | string |  |  |
| `prePostagens[].codigoServico` | string | ✔ | Código do serviço. — minLength: `0` — maxLength: `8` |
| `prePostagens[].listaServicoAdicional` | array<objeto `ServicoAdicional`> |  |  |
| `prePostagens[].listaServicoAdicional[].codigoServicoAdicional` | string |  | Código do serviço adicional. Não pode ser deixado em branco. Informar com 3 caracteres. |
| `prePostagens[].listaServicoAdicional[].tipoServicoAdicional` | string |  | Tipo de serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `prePostagens[].listaServicoAdicional[].nomeServicoAdicional` | string |  | Nome do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `prePostagens[].listaServicoAdicional[].valorServicoAdicional` | string |  | Valor do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `prePostagens[].listaServicoAdicional[].valorDeclarado` | string |  | Valor declarado do objeto a ser pré-postado. Não pode ser deixado em branco caso o serviço adicional seja do tipo valor declarado. |
| `prePostagens[].listaServicoAdicional[].siglaServicoAdicional` | string |  | Sigla do serviço adicional. O preenchimento deste campo é automático. Não é necessário o envio desta informação no momento da pré-postagem. |
| `prePostagens[].listaServicoAdicional[].objetoIdaSimultanea` | string |  | Código do objeto de ida simultânea para o serviço adicional 016. — minLength: `0` — maxLength: `20` |
| `prePostagens[].listaServicoAdicional[].orientacaoEntregaVizinho` | string |  | Orientação para entrega no vizinho. Obrigatório se o serviço adicional (011) for selecionado. — minLength: `0` — maxLength: `20` |
| `prePostagens[].listaServicoAdicional[].tipoChecklist` | string |  | Indica o tipo de checklist que deverá ser preenchido no momento da postagem. Aceita apenas 01-Celular, 04-Eletrônico, 05-Documento ou 07-Conteúdo. — minLength: `0` — maxLength: `3` |
| `prePostagens[].listaServicoAdicional[].subitensCheckList` | array<objeto `SubitemCheckList`> |  | Lista de códigos dos subitens para o tipoChecklist: 05-documentos. Podem ser listados até 8 códigos de documentos. |
| `prePostagens[].listaServicoAdicional[].subitensCheckList[].codigo` | string |  | Código do subitem. |
| `prePostagens[].modalidadePagamento` | integer (int32) |  | Modalidade de pagamento: 1 - à vista, 2 - a faturar, 3 - à vista e a faturar |
| `prePostagens[].precoServico` | number |  | Preço somente do serviço, sem os adicionais. |
| `prePostagens[].precoPrePostagem` | number |  | Preço total da pré-postagem. |
| `prePostagens[].numeroNotaFiscal` | string |  | Número Nota Fiscal. |
| `prePostagens[].chaveNFe` | string |  | Chave de acesso à NFe. |
| `prePostagens[].itensDeclaracaoConteudo` | array<objeto `ItemDeclaracaoConteudo`> |  |  |
| `prePostagens[].itensDeclaracaoConteudo[].conteudo` | string | ✔ | Descrição do item. Obrigatório. Tamanho mínimo 2 e tamanho máximo: 60. Não são aceitos apenas caracteres iguais. Caso informados apenas dois caracteres, os dois devem ser letras. |
| `prePostagens[].itensDeclaracaoConteudo[].quantidade` | string | ✔ | Quantidade total de itens de um mesmo tipo. Obrigatório. Tamanho máximo: 11 dígitos. |
| `prePostagens[].itensDeclaracaoConteudo[].valor` | string | ✔ | Valor Unitário do Item. Obrigatório. Tamanho máximo: 19 dígitos e 2 casas decimais. |
| `prePostagens[].pesoInformado` | string |  | Peso, em gramas, do objeto. — minLength: `0` — maxLength: `10` |
| `prePostagens[].codigoFormatoObjetoInformado` | string |  | Formato do objeto: 1 - Envelope, 2 - Caixa/Pacote; 3 - Rolo/Cilindro |
| `prePostagens[].alturaInformada` | string |  | Altura, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `prePostagens[].larguraInformada` | string |  | Largura, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `prePostagens[].comprimentoInformado` | string |  | Comprimento, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `prePostagens[].diametroInformado` | string |  | Diâmetro, em centímetros, do objeto. — minLength: `0` — maxLength: `8` |
| `prePostagens[].ncmObjeto` | string |  |  |
| `prePostagens[].rfidObjeto` | string |  |  |
| `prePostagens[].cienteObjetoNaoProibido` | integer (int32) |  | Indicação de que o objeto não é proibido no fluxo postal: 0 - não, 1 - sim |
| `prePostagens[].idAtendimento` | string |  | ID do atendimento. |
| `prePostagens[].solicitarColeta` | string |  | Indica se o objeto vai ter uma coleta gerada, valores possíveis: S=Coleta solicitada pelo cliente e requisitada ao serviço de coleta, E=Coleta solicitada pelo cliente, mas ocorreu erro ao requisitar o serviço à Api coleta e N=Coleta não solicitada pelo cliente. — default: `N` |
| `prePostagens[].nuColeta` | string |  | Número da coleta do objeto. |
| `prePostagens[].dataPrevistaPostagem` | string (date) |  | Data de pretensão da postagem |
| `prePostagens[].eticket` | string |  |  |
| `prePostagens[].dataEticket` | string (date) |  |  |
| `prePostagens[].checkList` | string |  |  |
| `prePostagens[].controleCliente` | string |  |  |
| `prePostagens[].observacao` | string |  |  |
| `prePostagens[].embalagem` | string |  | Valores: `S`, `N` |
| `prePostagens[].logisticaReversa` | string |  | Indica se o objeto é logistica reversa, valores possíveis: S=Sim, N=não. |
| `prePostagens[].dataValidadeLogReversa` | string (date-time) |  | Data e hora de validade da logística reversa. |
| `prePostagens[].servico` | string |  | Descrição do código do serviço |
| `prePostagens[].statusAtual` | integer (int32) |  | Status da Pré-postagem: 1 - PREATENDIDO, 2 - PREPOSTADO, 3 - POSTADO, 4 - EXPIRADO, 5 - CANCELADO , 6 - ESTORNADO, 7 - PENDENTE |
| `prePostagens[].dataHoraStatusAtual` | string (date-time) |  | Data em que foi configurada o status atual |
| `prePostagens[].codigoObjetoIda` | string |  | Objeto de ida associado a logística reversa. |
| `prePostagens[].objetoVoltaSimultanea` | string |  | Código do objeto de volta para o serviço adicional Logistica Reversa Simultanea — minLength: `0` — maxLength: `20` |
| `prePostagens[].emiteDCe` | string |  | Indicador de emissão de DCe. |
| `prePostagens[].descStatusAtual` | string |  |  |
| `prePostagens[].dataHora` | string (date-time) |  |  |
| `prePostagens[].tipoRotulo` | string |  | minLength: `0` — maxLength: `5` |
| `prePostagens[].retornoIntegracao` | string |  | Mensagens retornadas pela integração da Api coleta e geração do código de etiqueta. |
| `prePostagens[].pedidoExternoOrigem` | string |  | Codigo do pedido informado pelo cliente externo. |
| `prePostagens[].canalExternoOrigem` | string |  | Identificaçáo do canal origem informado pelo cliente externo. |
| `prePostagens[].sistemaOrigem` | string |  |  |
| `prePostagens[].valorTotalBens` | number |  |  |
| `prePostagens[].listaIdPrepostagemObjetos` | array<string> |  |  |
| `prePostagens[].quantidade` | integer (int64) |  |  |
| `prePostagens[].reciboSolicitacaoAssincrona` | string |  | IdRecibo da solicitação da geração da pré-postagem asincrona. |
| `prePostagens[].reciboSolicitacaoAssincronaRotulo` | string |  | IdRecibo da solicitação da geração do rótulo assincrono. |
| `prePostagens[].codigoFormatoObjetoPreAfericao` | string |  | Formato do objeto na pré-aferição. |
| `prePostagens[].alturaPreAfericao` | string |  | Altura na pré-aferição. |
| `prePostagens[].larguraPreAfericao` | string |  | Largurana pré-aferição. |
| `prePostagens[].comprimentoPreAfericao` | string |  | Comprimento na pré-aferição. |
| `prePostagens[].diametroPreAfericao` | string |  | Diametro na pré-aferição. |
| `prePostagens[].pesoPreAfericao` | string |  | Peso na pré-aferição. |
| `prePostagens[].dataHoraPreAfericao` | string (date-time) |  | Data e hora da pré aferição |
| `prePostagens[].mcuUnidadePreAfericao` | string |  | Mcu da unidade de pre-aferição. |
| `prePostagens[].idBalancaCubagem` | string |  | Identificador da balança de cubagem de pre-aferição. |
| `prePostagens[].cepDestinoPreAfericao` | string |  | Cep destino na pre-aferição. — minLength: `0` — maxLength: `8` |
| `prePostagens[].ehObjetoDgr` | string |  | Objeto identificado como objeto perigoso (Dangerous Goods Regulations - DGR), valores possíveis: S=Sim, N=não. |
| `prePostagens[].tipoObjeto` | string |  | Valores: `S`, `R` |
| `prePostagens[].erroAssincrono` | string |  |  |
| `prePostagens[].codigoEstampa2D` | string |  | Código de estampa 2D |
| `prePostagens[].historicoStatus` | array<objeto `PrePostagemHistoricoStatus`> |  |  |
| `prePostagens[].historicoStatus[].id` | integer (int64) |  |  |
| `prePostagens[].historicoStatus[].status` | integer (int32) |  | Status da Pré-postagem: 1 - PREATENDIDO, 2 - PREPOSTADO, 3 - POSTADO , 4 - EXPIRADO, 5 - CANCELADO |
| `prePostagens[].historicoStatus[].dataHora` | string (date-time) |  |  |
| `prePostagens[].prazoPostagem` | string (date-time) |  | Prazo máximo de postagem da pré-postagem. |
| `prePostagens[].indicadorMalote` | string |  |  |
| `prePostagens[].codigoGrafica` | integer (int32) |  | Código da gráfica. — maximum: `999` |
| `prePostagens[].codigoRemetente` | string |  | Código do remetente. |
| `prePostagens[].codigoDestinatario` | string |  | Código do destnatário. |
| `prePostagens[].malotePrePostagem` | objeto `MalotePrePostagem` |  | Malote |
| `prePostagens[].malotePrePostagem.numeroMalote` | string |  | Número do malote. |
| `prePostagens[].malotePrePostagem.dataHoraCadastro` | string (date-time) |  | Data e hora do cadastro do malote. |
| `prePostagens[].malotePrePostagem.quantidadeObjetos` | integer (int32) |  | Quantidade de objetos do malote. |
| `prePostagens[].malotePrePostagem.tipo` | string |  | tipo do malote. — Valores: `C`, `M`, `A` |
| `prePostagens[].malotePrePostagem.codigoObjetoMalote` | string |  | Código do objeto malote. |
| `prePostagens[].malotePrePostagem.codigoRemetente` | string |  | codigo do Remetente. |
| `prePostagens[].malotePrePostagem.codigoDestinatario` | string |  | codigo do Destinatario. |
| `prePostagens[].malotePrePostagem.numeroCartaoPostagem` | string |  | Cartão de postagem. |
| `prePostagens[].malotePrePostagem.prePostagem` | objeto `PrePostagem` |  | Microsserviço de PrePostagem. |
| `prePostagens[].malotePrePostagem.listaCodigosObjetosMalote` | array<string> |  |  |
| `prePostagens[].malotePrePostagem.listaObjetos` | array<objeto `PrePostagem`> |  |  |
| `prePostagens[].listaMalotes` | array<objeto `MalotePrePostagem`> |  |  |
| `prePostagens[].listaMalotes[].numeroMalote` | string |  | Número do malote. |
| `prePostagens[].listaMalotes[].dataHoraCadastro` | string (date-time) |  | Data e hora do cadastro do malote. |
| `prePostagens[].listaMalotes[].quantidadeObjetos` | integer (int32) |  | Quantidade de objetos do malote. |
| `prePostagens[].listaMalotes[].tipo` | string |  | tipo do malote. — Valores: `C`, `M`, `A` |
| `prePostagens[].listaMalotes[].codigoObjetoMalote` | string |  | Código do objeto malote. |
| `prePostagens[].listaMalotes[].codigoRemetente` | string |  | codigo do Remetente. |
| `prePostagens[].listaMalotes[].codigoDestinatario` | string |  | codigo do Destinatario. |
| `prePostagens[].listaMalotes[].numeroCartaoPostagem` | string |  | Cartão de postagem. |
| `prePostagens[].listaMalotes[].prePostagem` | objeto `PrePostagem` |  | Microsserviço de PrePostagem. |
| `prePostagens[].listaMalotes[].listaCodigosObjetosMalote` | array<string> |  |  |
| `prePostagens[].listaMalotes[].listaObjetos` | array<objeto `PrePostagem`> |  |  |
| `prePostagens[].emailLogisticaReversaEnviado` | string |  | Email logistica reversa enviado (S/N). |
| `prePostagens[].motivoCancelamento` | string |  | Erro em geração de DCe que causou eventual cancelamento de prepostagem. |
| `prePostagens[].listaTributo` | array<objeto `Tributo`> |  |  |
| `prePostagens[].listaTributo[].codigo` | string |  | Código do tributo. |
| `prePostagens[].listaTributo[].valor` | string |  | Valor do tributo. |

### 2.5 Exemplo de resposta (esqueleto resumido)

```json
{
  "situacao": "PROCESSADO",
  "mensagens": [],
  "sequencias": [
    {
      "ordem": "1",
      "mensagens": []
    }
  ],
  "prePostagens": [
    {
      "id": "<id da pré-postagem>",
      "codigoObjeto": "AA123456789BR",
      "statusAtual": 1,
      "prazoPostagem": "<data>"
    }
  ]
}
```

## 3. `DELETE /v1/prepostagens/assincrona`

**Cancela uma pré-postagem pelo código do objeto.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/assincrona`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/assincrona`
- Header: `Authorization: Bearer <token>`
- Header: `Content-Type: application/json`
- operationId: `cancelaPrePostagemPorObjetoAsinc`

### 3.1 Parâmetros

| Nome | Em | Tipo | Obrig. | Descrição |
|---|---|---|:-:|---|
| `idCorreiosSolicitanteCancelamento` | query | string |  | Id Correios do solicitante do cancelamento da pré-postagem. |

### 3.2 Corpo da requisição — `RequestCancelaPrePostagemDTO` (obrigatório)

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `codigosObjetos` | array<string> |  | Códigos dos identificadores dos objetos a serem cancelados. |
| `idsPrePostagens` | array<string> |  | Códigos dos identificadores das pré-postagens a serem cancelados. |

Informe **`codigosObjetos`** (códigos de 13 caracteres) **ou** **`idsPrePostagens`**.

> Atenção: é um `DELETE` **com corpo JSON**. Algumas bibliotecas HTTP e proxies descartam o corpo em `DELETE` — confirme que sua biblioteca envia o corpo.

```http
DELETE https://apihom.correios.com.br/prepostagem/v1/prepostagens/assincrona
Authorization: Bearer <token>
Content-Type: application/json

{
  "codigosObjetos": [
    "AA123456789BR"
  ]
}
```

### 3.3 Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `object` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o cartão/contrato. | — |

A resposta 200 é declarada apenas como `object`. Pelo endpoint 4, ela deve conter o identificador do recibo do cancelamento — confirme o nome do campo em homologação.

## 4. `GET /v1/prepostagens/canceladas/assincrona/{idRecibo}`

**Consulta processamento das requisições de cancelamento assincrona de pré-postagens.**

- URL (produção): `https://api.correios.com.br/prepostagem/v1/prepostagens/canceladas/assincrona/{idRecibo}`
- URL (homologação): `https://apihom.correios.com.br/prepostagem/v1/prepostagens/canceladas/assincrona/{idRecibo}`
- Header: `Authorization: Bearer <token>`
- operationId: `consultaPrePostagemPorObjetoAsinc`

### 4.1 Parâmetros

| Nome | Em | Tipo | Obrig. | Descrição |
|---|---|---|:-:|---|
| `idRecibo` | path | string | ✔ | Id Recibo da requisição de cancelamento |

```http
GET https://apihom.correios.com.br/prepostagem/v1/prepostagens/canceladas/assincrona/{idRecibo}
Authorization: Bearer <token>
```

### 4.2 Respostas

| Código | Descrição | Schema |
|---|---|---|
| `200` | OK | `object` |
| `400` | Caso ocorra alguma validação necessária por parte do requisitante. | `MessageResponse` |
| `500` | Caso ocorra algum erro no servidor. | `MessageResponse` |
| `401` | Token ausente, inválido ou expirado. | — |
| `403` | Token sem permissão para o cartão/contrato. | — |

A resposta 200 é declarada apenas como `object`, sem campos. Registre uma resposta real em homologação para completar.

## 5. Resposta de erro (400/500) — `MessageResponse`

| Campo (caminho) | Tipo | Obrig. | Descrição / regras |
|---|---|:-:|---|
| `msgs` | array<string> |  | Mensagens |
| `date` | string (date-time) |  | Data e hora de ocorrência do evento. |
| `method` | string |  | Método HTTP que foi requisitado. |
| `path` | string |  | URL que foi requisitada. |
| `causa` | string |  | A causa da mensagem. |
| `stackTrace` | string |  | Detalhe da mensagem. |

## 6. JSON Schemas completos (referências expandidas)

### `RequestPrePostagemExternaDTO` (corpo do endpoint 1)

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

### `RequestCancelaPrePostagemDTO` (corpo do endpoint 3)

```json
{
  "title": "RequestCancelaPrePostagemDTO",
  "type": "object",
  "description": "RequestCancelaPrePostagemDTO",
  "properties": {
    "codigosObjetos": {
      "type": "array",
      "description": "Códigos dos identificadores dos objetos a serem cancelados.",
      "items": {
        "type": "string"
      }
    },
    "idsPrePostagens": {
      "type": "array",
      "description": "Códigos dos identificadores das pré-postagens a serem cancelados.",
      "items": {
        "type": "string"
      }
    }
  }
}
```

### `PrePostagemAssincronaResponse` (resposta do endpoint 2)

```json
{
  "title": "PrePostagemAssincronaResponse",
  "type": "object",
  "properties": {
    "situacao": {
      "type": "string",
      "enum": [
        "PROCESSADO",
        "FALHA",
        "NAO_LOCALIZADO"
      ]
    },
    "mensagens": {
      "type": "array",
      "items": {
        "type": "string"
      }
    },
    "sequencias": {
      "type": "array",
      "items": {
        "title": "PrePostagemAssincronaDetalheResponse",
        "type": "object",
        "properties": {
          "ordem": {
            "type": "string"
          },
          "mensagens": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      }
    },
    "prePostagens": {
      "type": "array",
      "items": {
        "title": "PrePostagem",
        "type": "object",
        "description": "Microsserviço de PrePostagem.",
        "properties": {
          "id": {
            "type": "string"
          },
          "idCorreios": {
            "type": "string",
            "description": "idCorreios de quem fez a postagem à vista"
          },
          "numeroCartaoPostagem": {
            "type": "string",
            "description": "Cartão de postagem a faturar."
          },
          "codigoObjeto": {
            "type": "string"
          },
          "codigoServico": {
            "type": "string",
            "description": "Código do serviço.",
            "maxLength": 8,
            "minLength": 0
          },
          "listaServicoAdicional": {
            "type": "array",
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
            },
            "uniqueItems": true
          },
          "modalidadePagamento": {
            "type": "integer",
            "format": "int32",
            "description": "Modalidade de pagamento: 1 - à vista, 2 - a faturar, 3 - à vista e a faturar"
          },
          "precoServico": {
            "type": "number",
            "description": "Preço somente do serviço, sem os adicionais."
          },
          "precoPrePostagem": {
            "type": "number",
            "description": "Preço total da pré-postagem."
          },
          "numeroNotaFiscal": {
            "type": "string",
            "description": "Número Nota Fiscal."
          },
          "chaveNFe": {
            "type": "string",
            "description": "Chave de acesso à NFe."
          },
          "itensDeclaracaoConteudo": {
            "type": "array",
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
            },
            "uniqueItems": true
          },
          "pesoInformado": {
            "type": "string",
            "description": "Peso, em gramas, do objeto.",
            "maxLength": 10,
            "minLength": 0
          },
          "codigoFormatoObjetoInformado": {
            "type": "string",
            "description": "Formato do objeto: 1 - Envelope, 2 - Caixa/Pacote; 3 - Rolo/Cilindro"
          },
          "alturaInformada": {
            "type": "string",
            "description": "Altura, em centímetros, do objeto.",
            "maxLength": 8,
            "minLength": 0
          },
          "larguraInformada": {
            "type": "string",
            "description": "Largura, em centímetros, do objeto.",
            "maxLength": 8,
            "minLength": 0
          },
          "comprimentoInformado": {
            "type": "string",
            "description": "Comprimento, em centímetros, do objeto.",
            "maxLength": 8,
            "minLength": 0
          },
          "diametroInformado": {
            "type": "string",
            "description": "Diâmetro, em centímetros, do objeto.",
            "maxLength": 8,
            "minLength": 0
          },
          "ncmObjeto": {
            "type": "string"
          },
          "rfidObjeto": {
            "type": "string"
          },
          "cienteObjetoNaoProibido": {
            "type": "integer",
            "format": "int32",
            "description": "Indicação de que o objeto não é proibido no fluxo postal: 0 - não, 1 - sim"
          },
          "idAtendimento": {
            "type": "string",
            "description": "ID do atendimento."
          },
          "solicitarColeta": {
            "type": "string",
            "default": "N",
            "description": "Indica se o objeto vai ter uma coleta gerada, valores possíveis: S=Coleta solicitada pelo cliente e requisitada ao serviço de coleta, E=Coleta solicitada pelo cliente, mas ocorreu erro ao requisitar o serviço à Api coleta e N=Coleta não solicitada pelo cliente."
          },
          "nuColeta": {
            "type": "string",
            "description": "Número da coleta do objeto."
          },
          "dataPrevistaPostagem": {
            "type": "string",
            "format": "date",
            "description": "Data de pretensão da postagem"
          },
          "eticket": {
            "type": "string"
          },
          "dataEticket": {
            "type": "string",
            "format": "date"
          },
          "checkList": {
            "type": "string"
          },
          "controleCliente": {
            "type": "string"
          },
          "observacao": {
            "type": "string"
          },
          "embalagem": {
            "type": "string",
            "enum": [
              "S",
              "N"
            ]
          },
          "logisticaReversa": {
            "type": "string",
            "description": "Indica se o objeto é logistica reversa, valores possíveis: S=Sim, N=não."
          },
          "dataValidadeLogReversa": {
            "type": "string",
            "format": "date-time",
            "description": "Data e hora de validade da logística reversa."
          },
          "servico": {
            "type": "string",
            "description": "Descrição do código do serviço"
          },
          "statusAtual": {
            "type": "integer",
            "format": "int32",
            "description": "Status da Pré-postagem: 1 - PREATENDIDO, 2 - PREPOSTADO, 3 - POSTADO, 4 - EXPIRADO, 5 - CANCELADO , 6 - ESTORNADO, 7 - PENDENTE"
          },
          "dataHoraStatusAtual": {
            "type": "string",
            "format": "date-time",
            "description": "Data em que foi configurada o status atual"
          },
          "codigoObjetoIda": {
            "type": "string",
            "description": "Objeto de ida associado a logística reversa."
          },
          "objetoVoltaSimultanea": {
            "type": "string",
            "description": "Código do objeto de volta para o serviço adicional Logistica Reversa Simultanea",
            "maxLength": 20,
            "minLength": 0
          },
          "emiteDCe": {
            "type": "string",
            "description": "Indicador de emissão de DCe."
          },
          "descStatusAtual": {
            "type": "string"
          },
          "dataHora": {
            "type": "string",
            "format": "date-time"
          },
          "tipoRotulo": {
            "type": "string",
            "maxLength": 5,
            "minLength": 0
          },
          "retornoIntegracao": {
            "type": "string",
            "description": "Mensagens retornadas pela integração da Api coleta e geração do código de etiqueta."
          },
          "pedidoExternoOrigem": {
            "type": "string",
            "description": "Codigo do pedido informado pelo cliente externo."
          },
          "canalExternoOrigem": {
            "type": "string",
            "description": "Identificaçáo do canal origem informado pelo cliente externo."
          },
          "sistemaOrigem": {
            "type": "string"
          },
          "valorTotalBens": {
            "type": "number"
          },
          "listaIdPrepostagemObjetos": {
            "type": "array",
            "items": {
              "type": "string"
            },
            "uniqueItems": true
          },
          "quantidade": {
            "type": "integer",
            "format": "int64"
          },
          "reciboSolicitacaoAssincrona": {
            "type": "string",
            "description": "IdRecibo da solicitação da geração da pré-postagem asincrona."
          },
          "reciboSolicitacaoAssincronaRotulo": {
            "type": "string",
            "description": "IdRecibo da solicitação da geração do rótulo assincrono."
          },
          "codigoFormatoObjetoPreAfericao": {
            "type": "string",
            "description": "Formato do objeto na pré-aferição."
          },
          "alturaPreAfericao": {
            "type": "string",
            "description": "Altura na pré-aferição."
          },
          "larguraPreAfericao": {
            "type": "string",
            "description": "Largurana pré-aferição."
          },
          "comprimentoPreAfericao": {
            "type": "string",
            "description": "Comprimento na pré-aferição."
          },
          "diametroPreAfericao": {
            "type": "string",
            "description": "Diametro na pré-aferição."
          },
          "pesoPreAfericao": {
            "type": "string",
            "description": "Peso na pré-aferição."
          },
          "dataHoraPreAfericao": {
            "type": "string",
            "format": "date-time",
            "description": "Data e hora da pré aferição"
          },
          "mcuUnidadePreAfericao": {
            "type": "string",
            "description": "Mcu da unidade de pre-aferição."
          },
          "idBalancaCubagem": {
            "type": "string",
            "description": "Identificador da balança de cubagem de pre-aferição."
          },
          "cepDestinoPreAfericao": {
            "type": "string",
            "description": "Cep destino na pre-aferição.",
            "maxLength": 8,
            "minLength": 0
          },
          "ehObjetoDgr": {
            "type": "string",
            "description": "Objeto identificado como objeto perigoso (Dangerous Goods Regulations - DGR), valores possíveis: S=Sim, N=não."
          },
          "tipoObjeto": {
            "type": "string",
            "enum": [
              "S",
              "R"
            ]
          },
          "erroAssincrono": {
            "type": "string"
          },
          "codigoEstampa2D": {
            "type": "string",
            "description": "Código de estampa 2D"
          },
          "historicoStatus": {
            "type": "array",
            "items": {
              "title": "PrePostagemHistoricoStatus",
              "type": "object",
              "properties": {
                "id": {
                  "type": "integer",
                  "format": "int64"
                },
                "status": {
                  "type": "integer",
                  "format": "int32",
                  "description": "Status da Pré-postagem: 1 - PREATENDIDO, 2 - PREPOSTADO, 3 - POSTADO , 4 - EXPIRADO, 5 - CANCELADO"
                },
                "dataHora": {
                  "type": "string",
                  "format": "date-time"
                }
              }
            },
            "uniqueItems": true
          },
          "prazoPostagem": {
            "type": "string",
            "format": "date-time",
            "description": "Prazo máximo de postagem da pré-postagem."
          },
          "indicadorMalote": {
            "type": "string"
          },
          "codigoGrafica": {
            "type": "integer",
            "format": "int32",
            "description": "Código da gráfica.",
            "maximum": 999
          },
          "codigoRemetente": {
            "type": "string",
            "description": "Código do remetente."
          },
          "codigoDestinatario": {
            "type": "string",
            "description": "Código do destnatário."
          },
          "malotePrePostagem": {
            "title": "MalotePrePostagem",
            "type": "object",
            "description": "Malote",
            "properties": {
              "numeroMalote": {
                "type": "string",
                "description": "Número do malote."
              },
              "dataHoraCadastro": {
                "type": "string",
                "format": "date-time",
                "description": "Data e hora do cadastro do malote."
              },
              "quantidadeObjetos": {
                "type": "integer",
                "format": "int32",
                "description": "Quantidade de objetos do malote."
              },
              "tipo": {
                "type": "string",
                "description": "tipo do malote.",
                "enum": [
                  "C",
                  "M",
                  "A"
                ]
              },
              "codigoObjetoMalote": {
                "type": "string",
                "description": "Código do objeto malote."
              },
              "codigoRemetente": {
                "type": "string",
                "description": "codigo do Remetente."
              },
              "codigoDestinatario": {
                "type": "string",
                "description": "codigo do Destinatario."
              },
              "numeroCartaoPostagem": {
                "type": "string",
                "description": "Cartão de postagem."
              },
              "prePostagem": {
                "$comment": "ref circular: PrePostagem"
              },
              "listaCodigosObjetosMalote": {
                "type": "array",
                "items": {
                  "type": "string"
                },
                "uniqueItems": true
              },
              "listaObjetos": {
                "type": "array",
                "items": {
                  "$comment": "ref circular: PrePostagem"
                },
                "uniqueItems": true
              }
            }
          },
          "listaMalotes": {
            "type": "array",
            "items": {
              "title": "MalotePrePostagem",
              "type": "object",
              "description": "Malote",
              "properties": {
                "numeroMalote": {
                  "type": "string",
                  "description": "Número do malote."
                },
                "dataHoraCadastro": {
                  "type": "string",
                  "format": "date-time",
                  "description": "Data e hora do cadastro do malote."
                },
                "quantidadeObjetos": {
                  "type": "integer",
                  "format": "int32",
                  "description": "Quantidade de objetos do malote."
                },
                "tipo": {
                  "type": "string",
                  "description": "tipo do malote.",
                  "enum": [
                    "C",
                    "M",
                    "A"
                  ]
                },
                "codigoObjetoMalote": {
                  "type": "string",
                  "description": "Código do objeto malote."
                },
                "codigoRemetente": {
                  "type": "string",
                  "description": "codigo do Remetente."
                },
                "codigoDestinatario": {
                  "type": "string",
                  "description": "codigo do Destinatario."
                },
                "numeroCartaoPostagem": {
                  "type": "string",
                  "description": "Cartão de postagem."
                },
                "prePostagem": {
                  "$comment": "ref circular: PrePostagem"
                },
                "listaCodigosObjetosMalote": {
                  "type": "array",
                  "items": {
                    "type": "string"
                  },
                  "uniqueItems": true
                },
                "listaObjetos": {
                  "type": "array",
                  "items": {
                    "$comment": "ref circular: PrePostagem"
                  },
                  "uniqueItems": true
                }
              }
            },
            "uniqueItems": true
          },
          "emailLogisticaReversaEnviado": {
            "type": "string",
            "description": "Email logistica reversa enviado (S/N)."
          },
          "motivoCancelamento": {
            "type": "string",
            "description": "Erro em geração de DCe que causou eventual cancelamento de prepostagem."
          },
          "listaTributo": {
            "type": "array",
            "items": {
              "title": "Tributo",
              "type": "object",
              "description": "Tributos relacionados a prepostagem.",
              "properties": {
                "codigo": {
                  "type": "string",
                  "description": "Código do tributo."
                },
                "valor": {
                  "type": "string",
                  "description": "Valor do tributo."
                }
              }
            }
          }
        },
        "required": [
          "codigoServico"
        ]
      }
    }
  }
}
```

## 7. Orientações de implementação

1. **Quando usar o assíncrono:** para grandes volumes ou quando não é preciso o código do objeto na hora. Para criar uma pré-postagem e imprimir a etiqueta imediatamente, o `POST /v1/prepostagens` síncrono é mais simples.
2. **Token:** Bearer da API Token, reutilizado até perto de `expiraEm`; em 401, renovar uma vez e repetir.
3. **Validação prévia:** aplique as regras da seção 1.1 antes de enviar — erros de validação só aparecerão depois, na consulta do recibo.
4. **Guardar o recibo:** grave o recibo retornado vinculado ao pedido, com status local "aguardando processamento".
5. **Consulta do status (polling):** consulte `GET /v1/prepostagens/assincrona/{recibo}` com intervalo crescente (ex.: 5 s, 10 s, 30 s, 1 min...) e limite de tentativas. Enquanto não houver resultado final, continue consultando.
6. **Tratamento de `situacao`:**
   - `PROCESSADO` → grave `id`, `codigoObjeto`, `statusAtual` e `prazoPostagem` de cada item de `prePostagens`; siga para a etiqueta somente quando `statusAtual = 2` (com `7` - PENDENTE, continue consultando);
   - `FALHA` → registre `mensagens` e `sequencias[].mensagens` (use `ordem` para saber qual item falhou), mostre ao usuário, corrija e reenvie;
   - `NAO_LOCALIZADO` → recibo inexistente ou ainda não disponível; tente novamente após alguns segundos e, persistindo, trate como erro.
7. **Etiqueta:** a criação assíncrona **não** gera o rótulo. Depois de `situacao = PROCESSADO`, use os `id` de `prePostagens` em `idsPrePostagem` do `POST /v1/prepostagens/rotulo/assincrono/pdf` (arquivo 08) e baixe o PDF com o `idRecibo` retornado (arquivo 09):

   ```
   POST /v1/prepostagens/assincrona                        → recibo (string)
   GET  /v1/prepostagens/assincrona/{recibo}               → PROCESSADO → prePostagens[].id
   POST /v1/prepostagens/rotulo/assincrono/pdf             → { "idRecibo": "..." }
   GET  /v1/prepostagens/rotulo/download/assincrono/{idRecibo} → { "dados": <PDF Base64>, "nome": "..." }
   ```

   ✔ **Observado em homologação:** uma pré-postagem assíncrona com `situacao = PROCESSADO` teve a solicitação do rótulo aceita, mas o download retornou `PPN-288: Não é permitido imprimir rótulo de pré-postagem com status igual a Pendente`. Minutos depois, a mesma consulta retornou `statusAtual = 2` (PREPOSTADO) e o rótulo foi baixado normalmente. Ou seja: `PROCESSADO` indica que a criação terminou, mas a pré-postagem passa por `7` (PENDENTE) antes de ficar `2`. **Só peça o rótulo com `statusAtual = 2`** — faça *polling* do status (intervalo crescente) até isso acontecer. ✔ Confirmado também: um rótulo solicitado enquanto o status era `7` gera um `idRecibo` que **nunca** funciona no download, nem depois da mudança para `2`; nesse caso, solicite o rótulo novamente. Ver detalhes no arquivo 09.

   Atenção: são **dois recibos diferentes** — o da criação (usado só na consulta de status) e o `idRecibo` do rótulo (usado no download). Não envie o recibo da criação ao endpoint de download, nem peça o rótulo antes de `PROCESSADO` ou de uma pré-postagem já cancelada.
8. **Cancelamento:** envie `codigosObjetos` ou `idsPrePostagens` no corpo do `DELETE`, guarde o recibo retornado e confirme o resultado em `GET /v1/prepostagens/canceladas/assincrona/{idRecibo}`. Só marque como cancelado no seu sistema após a confirmação.
9. **Idempotência:** evite reenviar a mesma solicitação enquanto um recibo estiver pendente, para não duplicar pré-postagens.
10. **Respostas não documentadas:** os retornos 200 do cancelamento (endpoints 3 e 4) são `object` sem campos — registre (log) as primeiras respostas reais e trate de forma defensiva.
11. **Ambientes:** mantenha a URL base configurável (homologação × produção).
