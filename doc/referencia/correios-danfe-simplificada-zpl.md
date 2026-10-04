# DANFE Simplificado (etiqueta) em ZPL — guia para gerar a etiqueta da nota fiscal

> Documento de referência para desenvolvimento. O DANFE Simplificado não faz parte da API dos Correios: é a versão resumida, em etiqueta, do documento auxiliar da **NF-e**, impressa pela própria aplicação e colada no volume junto com a etiqueta de postagem (guia `correios-etiquetas-zpl.md`).
>
> **Origem:** análise dos arquivos da pasta `doc/legado/danfe-simplificada-zpl/` — código e template de um **sistema antigo** (`DanfeSimplificado.php`, `template_danfe_simplificado.zpl`) e uma etiqueta real gerada por ele (`exemplo_etiqueta_danfe.zpl`). Servem de **exemplo de funcionamento**, não de código a copiar: a seção 6 lista o que deve mudar. Analisado em 03/10/2026.
>
> **Como foi verificado:** o template antigo e o template novo (seção 7.2) foram preenchidos com dados fictícios e renderizados no Labelary (203 dpi, 4 × 6 pol); o cálculo do dígito da chave de acesso foi executado em PHP 8.4 e confere com a chave da etiqueta real. As regras legais da seção 5 vêm de uma **fonte secundária** e precisam ser conferidas na Nota Técnica oficial. O que não foi verificado está na seção 9.
>
> Guias relacionados: `correios-etiquetas-zpl.md` (regras do renderizador ZPL, escape, logotipos) · `correios-06-prepostagem-criar-api.md` e `correios-14-prepostagem-dce-api.md` (uso da chave da NF-e na pré-postagem).

## 0. Resumo rápido

- A etiqueta é um **texto ZPL** montado a partir de um template, com os dados da **NF-e autorizada**.
- Tamanho usado: **800 × 1199 pontos a 203 dpi** (≈ 100 × 150 mm).
- Elemento central: a **chave de acesso de 44 dígitos** em código de barras **Code 128 subconjunto C**.
- O sistema antigo acrescentava o **código de envio** (rastreamento dos Correios) em um segundo código de barras e um **resumo dos produtos**. Na versão nova, o código de envio continua (opcional) e **a etiqueta não lista itens nem valores**.
- Só se gera a etiqueta para nota **autorizada**. O sistema antigo já fazia essa verificação.
- A mesma chave de 44 dígitos é a que vai para a pré-postagem dos Correios (`chaveNFe`).

### Fluxo

```
1. NF-e autorizada                       → XML com protocolo (nfeProc) ou dados equivalentes do pedido
2. Extrair os dados                       → chave, número, série, emissão, emitente, destinatário, protocolo
3. (opcional) código de rastreamento      → codigoObjeto da pré-postagem dos Correios
4. Preencher o template                   → texto ZPL
5. Imprimir junto com a etiqueta de postagem
```

## 1. Arquivos analisados (sistema antigo)

| Arquivo | O que é |
|---|---|
| `DanfeSimplificado.php` | Classe estática: recebe os dados do pedido, monta o mapa de substituições e devolve o ZPL. |
| `template_danfe_simplificado.zpl` | Template com os marcadores (palavras soltas, sem delimitador). Começa com BOM (`EF BB BF`). |
| `exemplo_etiqueta_danfe.zpl` | Etiqueta real gerada pelo sistema antigo. |
| `nfe-venda-pf.xml` | XML de uma NF-e autorizada de **venda** para pessoa física — a mesma nota da etiqueta de exemplo. Os dados, inclusive o produto, foram trocados por **fictícios** em 04/10/2026 (ver seção 7.5). |

## 2. Como o sistema antigo funcionava

```
DanfeSimplificado::gerarDanfe($dados_pedido)
 ├─ se status_nota_fiscal != 'emissao_concluida'  → devolve false (não gera)
 ├─ Pedidos::pegaResumoProdutos(codigo_pedido)     → texto com o resumo dos produtos
 ├─ dados do emitente                              → 3 linhas fixas de configuração, escolhidas pela conta do pedido
 └─ pegaTextoZpl($texto_substituir)                → lê o template e faz str_replace de cada marcador
       (marcador com valor vazio NÃO é substituído)
```

| Marcador | Origem no sistema antigo |
|---|---|
| `NUMERO_NOTA` | Número da nota fiscal |
| `DATA_EMISSAO` | Data formatada (vinha do campo "data de envio" do pedido) |
| `CHAVE_NOTA_FISCAL` | Chave de acesso, 44 dígitos |
| `NOME_DESTINATARIO` | Nome do destinatário |
| `ENDERECO_DESTINATARIO_LINHA_1` | `logradouro, número` |
| `ENDERECO_DESTINATARIO_LINHA_2` | `bairro, cidade/UF CEP` |
| `CODIGO_DE_ENVIO` | Código de rastreamento (vazio se ainda não houver) |
| `EMITENTE_LINHA1` | Razão social |
| `EMITENTE_LINHA2` | `CNPJ: ... IE: ...` |
| `EMITENTE_LINHA3` | Endereço resumido |
| `SKU_PRODUTOS` | Resumo dos produtos do pedido |

Textos fixos no template: `DANFE Simplificado da NFe`, `1 - Saida`, `Numero: NUMERO_NOTA / 2`, `Emitente:`, `CHAVE DE ACESSO`, `CÓDIGO DE ENVIO`, `DESTINATÁRIO`.

## 3. Anatomia da etiqueta antiga (203 dpi)

| # | Elemento | Comando | Posição | Observação |
|---|---|---|---|---|
| 1 | Título | `^FT36,58^A0N,23,23` | 36,58 | "DANFE Simplificado da NFe" |
| 2 | Tipo de operação | `^FPH,1^FT367,49^ADN,18,10` | 367,49 | fixo "1 - Saida", fonte D (sem acentos) |
| 3 | Número | `^FPH,1^FT367,67^ADN,18,10` | 367,67 | "Numero: … / 2" |
| 4 | Emissão | `^FPH,1^FT367,85^ADN,18,10` | 367,85 | |
| 5 | Rótulo "Emitente:" | `^FT36,99^A0N,23,23` | 36,99 | |
| 6 | Emitente (3 linhas) | `^FT36,128` · `157` · `186` com `^A0N,23,23` | — | |
| 7 | Título "CHAVE DE ACESSO" | `^FT284,280^A0N,25,25` | 284,280 | |
| 8 | Chave em Code 128 | `^BY2,3,117^FT126,408^BCN,,Y,N` + `^FD>;…` | base em 126,408 | módulo 2, altura 117, texto legível abaixo, `>;` = subconjunto C |
| 9 | Título "CÓDIGO DE ENVIO" | `^FT36,471^A0N,25,25` | 36,471 | |
| 10 | Código de envio em Code 128 | `^BY2,3,87^FT19,564^BCN,,Y,N` + `^FD>:…` | base em 19,564 | altura 87, `>:` = subconjunto B |
| 11 | Linha | `^FO14,589^GB768,0,4` | 14,589 | |
| 12 | Tarja "DESTINATÁRIO" | `^FT62,670^A0N,25,25` + `^LRY^FO19,636^GB247,0,49^FS^LRN` | 19,636 | faixa preta impressa em modo reverso sobre o texto |
| 13 | Destinatário (3 linhas) | `^FT21,720` · `749` · `778` com `^A0N,23,23` | — | nome, endereço 1, endereço 2 |
| 14 | Linha | `^FO14,848^GB768,0,4` | 14,848 | |
| 15 | Resumo dos produtos | `^FT28,892^A0N,34,34` | 28,892 | uma linha, fonte grande |

Cabeçalho e rodapé: `^XA` · `^MMT` (modo destacar) · `^PW799` · `^LL1199` · `^LS0` · `^PQ1,0,1,Y` (1 cópia) · `^XZ`.

Particularidades do template antigo:

- Cada campo de texto liga o UTF-8 e depois desliga: `^CI28^FD...^FS^CI27`.
- `^FH\` define a **barra invertida** como indicador de hexadecimal (no guia de etiquetas de postagem o indicador é `_`).
- `^FPH,1` (direção horizontal, espaçamento 1) nos campos com fonte D.

### Códigos de barras

| Código | Conteúdo | Subconjunto | Largura estimada |
|---|---|---|---|
| Chave de acesso | 44 dígitos | **C** (`>;`): cada símbolo leva 2 dígitos | 22 pares + início, verificador e fim = 277 módulos × 2 = **554 pontos** (≈ 69 mm) |
| Código de envio | 13 caracteres (`AA123456785BR`) | **B** (`>:`): letras e dígitos | 178 módulos × 2 = **356 pontos** |

Com módulo 2, a chave cabe na etiqueta de 800 pontos com margem. **Com módulo 3 não cabe** (831 pontos).

## 4. Chave de acesso da NF-e

A chave tem 44 dígitos. A da etiqueta de exemplo (`35261011222333000181550010000214861123456781`; dados fictícios desde 04/10/2026, com o dígito verificador recalculado) se decompõe assim:

| Posições | Tam. | Campo | Exemplo |
|---|:-:|---|---|
| 1–2 | 2 | Código da UF do emitente | `35` (SP) |
| 3–6 | 4 | Ano e mês de emissão (AAMM) | `2610` |
| 7–20 | 14 | CNPJ do emitente | `11222333000181` |
| 21–22 | 2 | Modelo | `55` (NF-e) |
| 23–25 | 3 | Série | `001` |
| 26–34 | 9 | Número da nota | `000021486` |
| 35 | 1 | Tipo de emissão | `1` |
| 36–43 | 8 | Código numérico | `12345678` |
| 44 | 1 | Dígito verificador | `1` |

### Dígito verificador (módulo 11) — conferido com a chave real

Multiplique os 43 primeiros dígitos, **da direita para a esquerda**, pelos pesos 2, 3, 4, 5, 6, 7, 8, 9 (repetindo). Some, calcule o resto da divisão por 11. Resto 0 ou 1 → dígito `0`; caso contrário → `11 − resto`.

```php
function digitoChave(string $chave43): int
{
    $soma = 0;
    $peso = 2;
    for ($i = 42; $i >= 0; $i--) {
        $soma += (int) $chave43[$i] * $peso;
        $peso = $peso === 9 ? 2 : $peso + 1;
    }
    $resto = $soma % 11;

    return $resto < 2 ? 0 : 11 - $resto;
}

function formatarChave(string $chave): string      // "3526 1029 9438 ..."
{
    return implode(' ', str_split($chave, 4));
}
```

Uso na aplicação: validar a chave antes de imprimir (44 dígitos + dígito verificador + modelo `55`) e extrair série e número quando eles não vierem de outra fonte.

## 5. O que a legislação exige (conferir na Nota Técnica oficial)

O "DANFE Simplificado – Etiqueta" é definido pela **Nota Técnica 2020.004** da NF-e (versão 1.10, de agosto de 2024, segundo a fonte consultada). Resumo dos requisitos, a partir de fonte secundária ([Focus NFe](https://focusnfe.com.br/notas-tecnicas/nfe/2020-004/)):

| Requisito | Situação na etiqueta antiga |
|---|---|
| Descrição "DANFe Simplificado - Etiqueta" | ✖ imprime "DANFE Simplificado da NFe" |
| Emitente: nome/razão social, **sigla da UF**, CNPJ, inscrição estadual | Parcial — a UF só aparece dentro do endereço |
| Tipo de operação (entrada ou saída), **série** e número | Parcial — "1 - Saida" e a série são fixos no template |
| Data de emissão | ✔ (mas vinha da data de envio do pedido) |
| Destinatário: nome/razão social, **sigla da UF**, **CNPJ/CPF** | ✖ sem CNPJ/CPF |
| Chave de acesso e código de barras | ✔ |
| **Protocolo de autorização de uso** | ✖ ausente |
| Protocolo do evento EPEC, quando emitida em contingência | ✖ ausente |
| Valor total da NF-e | ausente — **deixou de ser obrigatório na versão 1.10**, segundo a fonte. O template novo também **não** imprime (decisão do usuário) |
| Largura mínima de 55 mm; textos com no mínimo 6 pt; títulos dos campos em negrito | ✔ largura de 100 mm |
| Chave e código de barras no canto superior direito | ✖ ficam no meio da etiqueta (a confirmar) |

A mesma fonte informa que a chave e o código de barras podem ser impressos em qualquer orientação, **desde que fiquem no canto superior direito do papel**. Nem a etiqueta antiga nem o template da seção 7.2 seguem essa posição (a chave fica no meio da etiqueta, abaixo do emitente). Confirme a regra na Nota Técnica; se ela valer, reposicione o bloco da chave.

> **Antes de implementar, confira esses requisitos na Nota Técnica 2020.004 publicada no Portal Nacional da NF-e** (ou peça o documento ao usuário). Em caso de divergência, a Nota Técnica oficial vence este guia.

## 6. Problemas do código antigo que a nova versão deve corrigir

| # | Problema | Consequência | O que fazer |
|---|---|---|---|
| 1 | Marcadores são **palavras soltas** (`NUMERO_NOTA`), sem delimitador | Um valor que contenha o nome de outro marcador é substituído em cascata; não dá para saber se sobrou marcador. | Marcadores `{{nome}}`, como nos outros templates. |
| 2 | Marcador com valor vazio **não é substituído** | Sem código de rastreamento, a etiqueta imprime um código de barras com o texto `CODIGO_DE_ENVIO`. | Bloco do código de envio como parcial opcional; campos vazios viram texto vazio. |
| 3 | Série fixa no template (`/ 2`) | Na etiqueta de exemplo a chave indica série `001`, mas o texto impresso é "/ 2". | Série vinda da nota (XML ou chave). |
| 4 | "1 - Saida" fixo | Nota de entrada ou devolução sai com o tipo errado. | Tipo de operação vindo da nota (`0` entrada, `1` saída). |
| 5 | Faltam dados exigidos (seção 5) | Etiqueta fora do padrão. | Incluir título correto, protocolo, UF e CNPJ/CPF. |
| 6 | Data de emissão tirada da data de envio do pedido | Pode divergir da data real da nota. | Usar a data de emissão da NF-e. |
| 7 | Valores inseridos **sem escape**, com `^FH\` | Uma barra invertida ou `^`/`~` no texto quebra o campo. | Escape do guia de etiquetas (seção 7.3). |
| 8 | Template com **BOM** no início | Caracteres estranhos antes do `^XA` podem ser impressos ou ignorar o primeiro comando. | Arquivos em UTF-8 sem BOM. |
| 9 | `^CI28 … ^CI27` em cada campo e fonte D sem acento | Texto repetitivo; "Saida", "Emissao", "Numero" sem acento. | `^CI28` uma vez no início; fonte 0 em tudo. |
| 10 | Textos sem quebra nem corte | Razão social ou endereço longos saem da etiqueta. | `^FB` e corte no código. |
| 11 | Resumo dos produtos impresso na etiqueta | Mais de ~40 caracteres não cabem; o usuário não quer itens na etiqueta. | Remover: a etiqueta nova não lista itens nem valores. |
| 12 | Emitente em 3 linhas pré-formatadas na configuração | Dados fiscais duplicados fora da nota. | Montar a partir dos dados da NF-e. |
| 13 | Devolve `false` quando a nota não está autorizada | Erro silencioso. | Lançar `ErroValidacao` com mensagem clara. |
| 14 | Classe estática acoplada a `Pedidos` e `settings()` | Não reutilizável. | DTO `DadosDanfe` + gerador sem dependências externas. |
| 15 | Chave não é validada | Chave errada gera código de barras ilegível para o fisco. | Validar 44 dígitos e o dígito verificador. |

## 7. Projeto recomendado para a nova aplicação

### 7.1 Estrutura

```
resources/templates/
├── danfe_simplificada.zpl              # template base (203 dpi)
└── parciais/
    └── danfe_codigo_envio.zpl          # bloco opcional do código de rastreamento
```

- Marcadores de valor `{{nome}}` e de parcial `{{>nome}}`, iguais aos do guia `correios-etiquetas-zpl.md`.
- O parcial `danfe_codigo_envio` só é incluído quando houver código de rastreamento. Sem ele, o espaço fica em branco.

### 7.2 Template base (203 dpi) — verificado no Labelary

`danfe_simplificada.zpl`:

```zpl
^XA
^CI28
^PW800
^LL1199
^LH0,0
^LS0
^FX --- Cabecalho ---
^FT30,62^A0N,34,34^FH^FDDANFe Simplificado - Etiqueta^FS
^FT30,98^A0N,24,24^FH^FD{{tipo_operacao}}^FS
^FT520,50^A0N,24,24^FH^FDNº {{numero_nf}}^FS
^FT520,80^A0N,24,24^FH^FDSérie: {{serie}}^FS
^FT520,110^A0N,24,24^FH^FDEmissão: {{data_emissao}}^FS
^FO14,125^GB772,0,3^FS
^FX --- Emitente ---
^FT30,155^A0N,22,22^FDEMITENTE^FS
^FO30,163^A0N,27,27^FH^FD{{emitente_nome}}^FS
^FO30,196^A0N,23,23^FH^FDCNPJ: {{emitente_cnpj}}  IE: {{emitente_ie}}  UF: {{emitente_uf}}^FS
^FO30,224^A0N,23,23^FH^FD{{emitente_endereco}}^FS
^FO14,258^GB772,0,3^FS
^FX --- Chave de acesso: Code 128 subconjunto C ---
^FO0,270^A0N,25,25^FB800,1,0,C^FDCHAVE DE ACESSO^FS
^BY2,3,110^FO123,303^BCN,110,N,N^FD>;{{chave_acesso}}^FS
^FO0,423^A0N,24,24^FB800,1,0,C^FD{{chave_acesso_formatada}}^FS
^FO0,457^A0N,23,23^FB800,1,0,C^FH^FD{{linha_protocolo}}^FS
^FO14,492^GB772,0,3^FS
{{>danfe_codigo_envio}}
^FX --- Destinatario ---
^FO14,665^GB250,45,45^FS
^FT30,699^FR^A0N,28,28^FDDESTINATÁRIO^FS
^FO30,722^A0N,27,27^FH^FD{{destinatario_nome}}^FS
^FO30,755^A0N,23,23^FH^FD{{destinatario_documento}}  UF: {{destinatario_uf}}^FS
^FO30,783^A0N,23,23^FH^FD{{destinatario_endereco_linha1}}^FS
^FO30,811^A0N,23,23^FH^FD{{destinatario_endereco_linha2}}^FS
^FO14,848^GB772,0,3^FS
^FX --- Observacoes (opcional, texto livre da aplicacao) ---
^FO30,864^A0N,24,24^FB740,3,3,L^FH^FD{{observacoes}}^FS
^XZ
```

`parciais/danfe_codigo_envio.zpl`:

```zpl
^FX --- Codigo de envio (rastreamento): Code 128 subconjunto B ---
^FT30,522^A0N,25,25^FDCÓDIGO DE ENVIO^FS
^BY2,3,80^FO30,535^BCN,80,Y,N^FD>:{{codigo_envio}}^FS
^FO14,655^GB772,0,3^FS
```

Diferenças em relação ao template antigo: título conforme a Nota Técnica; série e tipo de operação variáveis; UF e CNPJ/CPF do destinatário; protocolo de autorização; chave também em texto, em grupos de 4 dígitos (o texto automático do código de barras foi desligado); **sem lista de itens e sem valores** (decisão do usuário — ver 7.4); campo livre de observações em até 3 linhas; `^CI28` uma única vez; indicador de hexadecimal padrão (`_`); sem `^MMT`, `^PQ` e `^LRY` (a tarja usa `^GB` preenchido + `^FR`); bloco do código de envio opcional.

### 7.3 Marcadores

| Marcador | Conteúdo | Tam. máx. sugerido | Origem na NF-e (XML `nfeProc`) |
|---|---|:-:|---|
| `{{tipo_operacao}}` | `0 - ENTRADA` ou `1 - SAÍDA` | 12 | `ide/tpNF` |
| `{{numero_nf}}` | Número, com pontos (`000.021.486`) | 11 | `ide/nNF` |
| `{{serie}}` | Série | 3 | `ide/serie` |
| `{{data_emissao}}` | `dd/mm/aaaa` | 10 | `ide/dhEmi` |
| `{{emitente_nome}}` | Razão social | 50 | `emit/xNome` |
| `{{emitente_cnpj}}` | CNPJ formatado | 18 | `emit/CNPJ` |
| `{{emitente_ie}}` | Inscrição estadual | 15 | `emit/IE` |
| `{{emitente_uf}}` | Sigla da UF | 2 | `emit/enderEmit/UF` |
| `{{emitente_endereco}}` | Endereço resumido, sem o bairro (opcional) | 60 | `emit/enderEmit/xLgr`, `nro`, `xCpl`, `xMun`, `UF`, `CEP` |
| `{{chave_acesso}}` | 44 dígitos (código de barras) | 44 exatos | `protNFe/infProt/chNFe` ou `infNFe/@Id` sem o prefixo `NFe` |
| `{{chave_acesso_formatada}}` | 11 grupos de 4 dígitos | 54 | calculado |
| `{{linha_protocolo}}` | `Protocolo de autorização: <número> - <data e hora>` | 68 | `protNFe/infProt/nProt` e `dhRecbto` |
| `{{codigo_envio}}` | Código de rastreamento, 13 caracteres | 13 | `codigoObjeto` da pré-postagem |
| `{{destinatario_nome}}` | Nome ou razão social | 50 | `dest/xNome` |
| `{{destinatario_documento}}` | `CPF: …` ou `CNPJ: …` (e `IE: …`, se houver) | 45 | `dest/CPF` ou `dest/CNPJ`, `dest/IE` |
| `{{destinatario_uf}}` | Sigla da UF | 2 | `dest/enderDest/UF` |
| `{{destinatario_endereco_linha1}}` | `logradouro, número, complemento` | 68 | `dest/enderDest/xLgr`, `nro`, `xCpl` |
| `{{destinatario_endereco_linha2}}` | `bairro - cidade/UF - CEP` | 68 | `dest/enderDest/xBairro`, `xMun`, `UF`, `CEP` |
| `{{observacoes}}` | Texto livre opcional (ex.: número do pedido). **Não usar para itens nem valores.** | 180 (3 linhas) | aplicação |

Os limites são estimativas para a largura útil de 740 pontos; ajuste após testar no Labelary.

✔ **Lições da implementação (04/10/2026):**

- **Não corte o fim do endereço:** com o limite de 60 caracteres, um corte simples de `{{emitente_endereco}}` partia o CEP (`... - 90020-0`). Corte primeiro o logradouro e preserve `cidade/UF - CEP`. O mesmo vale para `{{destinatario_endereco_linha2}}`: encurte o bairro e preserve cidade/UF e CEP.
- **Não junte espaços duplos dos valores já formatados:** o separador de dois espaços em `CNPJ: …  IE: …  UF: …` precisa ser mantido. Limpe só caracteres de controle e quebras de linha (seção 7.4, regra 5).

### 7.4 Regras

1. **Só para nota autorizada:** com o XML, exigir `protNFe/infProt/cStat = 100` (autorizado o uso) e protocolo preenchido. Nota cancelada, denegada ou sem protocolo → `ErroValidacao`. Confirme com o usuário se outros status (por exemplo, autorização fora de prazo) devem ser aceitos.
2. **Chave:** validar 44 dígitos, modelo `55` e o dígito verificador (seção 4). No código de barras, enviar com o prefixo `>;` (subconjunto C), **sem escape** e sem espaços.
3. **Módulo do código de barras:** manter `^BY2`. Com `^BY3` a chave passa da largura da etiqueta de 100 mm. Em 300 dpi, usar `^BY3` (≈ 831 pontos em uma etiqueta de 1200).
4. **Código de envio:** validar no padrão `^[A-Z]{2}[0-9]{9}[A-Z]{2}$`; prefixo `>:` (subconjunto B).
5. **Escape, acentuação, corte e marcadores sem valor:** as mesmas regras da seção 8.4 de `correios-etiquetas-zpl.md` (`_` → `_5F`, `^` → `_5E`, `~` → `_7E`, `\` → `_5C`; `^CI28`; corte por caracteres; `ErroTemplate` se sobrar marcador).
6. **Datas:** data de emissão em `dd/mm/aaaa`; data e hora do protocolo em `dd/mm/aaaa hh:mm:ss`, no fuso da nota.
7. **Sem itens e sem valores:** por decisão do usuário, a etiqueta **não lista os produtos nem imprime valores** (nem o valor total, que deixou de ser obrigatório na versão 1.10 da Nota Técnica, segundo a fonte da seção 5). Não leia `det/prod` nem `total` do XML para a etiqueta. `{{emitente_endereco}}` e `{{observacoes}}` são opcionais e podem ficar vazios.
8. **Contingência (EPEC):** a fonte consultada cita a impressão do protocolo do evento EPEC. O template não tem esse campo; se a aplicação emitir em contingência, trate o caso antes de liberar a etiqueta (pergunte ao usuário).
9. **Entrada dos dados — duas formas** (como define o prompt do componente):
   - `DadosDanfe::deXml(string $xml)` — lê o XML autorizado (`nfeProc`) com `DOMDocument`/`SimpleXML`, respeitando o namespace `http://www.portalfiscal.inf.br/nfe`;
   - construção manual do DTO `DadosDanfe`, para aplicações que só têm os dados do pedido (caso do sistema antigo).
10. **Reaproveitamento:** `DadosDanfe` expõe a chave e o número da nota para a pré-postagem (`chaveNFe`, `numeroNotaFiscal` — arquivo 06) ou para `PATCH /v1/prepostagens/dce` (arquivo 14).
11. **Impressão conjunta:** as duas etiquetas (postagem e DANFE) podem ir no mesmo envio à impressora — concatene os blocos `^XA … ^XZ`.

### 7.5 Leitura do XML da NF-e — ✔ testado com dois XML

A função abaixo foi testada com dois XML **autorizados** (`nfeProc` versão 4.00), em uma única linha, com namespace padrão e assinatura. Os dois são notas reais com os **dados trocados por fictícios** (emitente, destinatário, transportadora, responsável técnico, produtos, valores, número, chave, protocolo e assinatura — a assinatura deixa de ser válida, mas a chave e o dígito verificador foram recalculados). Só o de pessoa física está na pasta de exemplos; o de pessoa jurídica foi **removido** em 04/10/2026, antes de o repositório ser tornado público, e o componente usa como fixture (`tests/Fixtures/xml/nfe-venda-pj.xml`) uma versão sintética dele, com 2 itens fictícios:

| Arquivo | Caso | Destinatário | Itens | Particularidades |
|---|---|---|:-:|---|
| `nfe-venda-pf.xml` | **Venda** a consumidor final de outro estado — o caso típico | Pessoa **física** (CPF, sem IE, `indIEDest = 9`) | 1 | É a nota da etiqueta antiga `exemplo_etiqueta_danfe.zpl` (mesma chave) |
| `nfe-venda-pj.xml` | **Venda** entre empresas de estados diferentes (RS → SP) | Pessoa **jurídica** com IE (`indIEDest = 1`) | 10 (2 na fixture) | Fora da pasta de exemplos; tem transportadora, duplicata e informações adicionais |

A função abaixo foi executada em PHP 8.4 com os dois arquivos e preencheu os 17 marcadores de dados do template. Ela usa `digitoChave()` da seção 4 e só as extensões `dom` e `libxml`. **Não lê itens nem valores** (regra 7 da seção 7.4):

```php
/** Lê um XML autorizado de NF-e (nfeProc) e devolve os valores dos marcadores do template. */
function lerNfeParaDanfe(string $xml): array
{
    $dom = new DOMDocument();
    if (!@$dom->loadXML($xml, LIBXML_NONET)) {
        throw new InvalidArgumentException('XML da NF-e inválido.');
    }
    $xp = new DOMXPath($dom);
    $xp->registerNamespace('n', 'http://www.portalfiscal.inf.br/nfe');
    $v = static fn (string $caminho): string => trim($xp->evaluate("string({$caminho})"));

    // 1. Autorização
    if ($v('/n:nfeProc/n:protNFe/n:infProt/n:cStat') !== '100' || $v('/n:nfeProc/n:protNFe/n:infProt/n:nProt') === '') {
        throw new InvalidArgumentException('A NF-e não está autorizada (protNFe ausente ou cStat diferente de 100).');
    }

    // 2. Chave: a do protocolo deve ser igual à do atributo Id
    $inf = '/n:nfeProc/n:NFe/n:infNFe';
    $chave = $v('/n:nfeProc/n:protNFe/n:infProt/n:chNFe');
    if (!preg_match('/^\d{44}$/', $chave) || 'NFe' . $chave !== $v("{$inf}/@Id")) {
        throw new InvalidArgumentException('Chave de acesso ausente ou divergente entre a nota e o protocolo.');
    }
    if (digitoChave(substr($chave, 0, 43)) !== (int) $chave[43] || substr($chave, 20, 2) !== '55') {
        throw new InvalidArgumentException('Chave de acesso inválida.');
    }

    $data = static fn (string $iso, string $formato): string => (new DateTimeImmutable($iso))->format($formato);
    $cnpj = static fn (string $d): string => preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $d);
    $cpf = static fn (string $d): string => preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $d);
    $cep = static fn (string $d): string => preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $d);
    $juntar = static fn (array $partes, string $sep): string => implode($sep, array_filter(array_map('trim', $partes), 'strlen'));

    $docDest = $v("{$inf}/n:dest/n:CNPJ") !== ''
        ? 'CNPJ: ' . $cnpj($v("{$inf}/n:dest/n:CNPJ"))
        : 'CPF: ' . $cpf($v("{$inf}/n:dest/n:CPF"));
    if ($v("{$inf}/n:dest/n:IE") !== '') {
        $docDest .= '  IE: ' . $v("{$inf}/n:dest/n:IE");
    }

    $ende = "{$inf}/n:emit/n:enderEmit";
    $endd = "{$inf}/n:dest/n:enderDest";

    return [
        'tipo_operacao' => $v("{$inf}/n:ide/n:tpNF") === '0' ? '0 - ENTRADA' : '1 - SAÍDA',
        'numero_nf' => implode('.', str_split(str_pad($v("{$inf}/n:ide/n:nNF"), 9, '0', STR_PAD_LEFT), 3)),
        'serie' => $v("{$inf}/n:ide/n:serie"),
        'data_emissao' => $data($v("{$inf}/n:ide/n:dhEmi"), 'd/m/Y'),
        'emitente_nome' => $v("{$inf}/n:emit/n:xNome"),
        'emitente_cnpj' => $cnpj($v("{$inf}/n:emit/n:CNPJ")),
        'emitente_ie' => $v("{$inf}/n:emit/n:IE"),
        'emitente_uf' => $v("{$ende}/n:UF"),
        'emitente_endereco' => $juntar([$juntar([$v("{$ende}/n:xLgr"), $v("{$ende}/n:nro"), $v("{$ende}/n:xCpl")], ', '),
            $v("{$ende}/n:xMun") . '/' . $v("{$ende}/n:UF"), $cep($v("{$ende}/n:CEP"))], ' - '),   // sem bairro, para caber em uma linha
        'chave_acesso' => $chave,
        'chave_acesso_formatada' => implode(' ', str_split($chave, 4)),
        'linha_protocolo' => 'Protocolo de autorização: ' . $v('/n:nfeProc/n:protNFe/n:infProt/n:nProt')
            . ' - ' . $data($v('/n:nfeProc/n:protNFe/n:infProt/n:dhRecbto'), 'd/m/Y H:i:s'),
        'destinatario_nome' => $v("{$inf}/n:dest/n:xNome"),
        'destinatario_documento' => $docDest,
        'destinatario_uf' => $v("{$endd}/n:UF"),
        'destinatario_endereco_linha1' => $juntar([$v("{$endd}/n:xLgr"), $v("{$endd}/n:nro"), $v("{$endd}/n:xCpl")], ', '),
        'destinatario_endereco_linha2' => $juntar([$v("{$endd}/n:xBairro"), $v("{$endd}/n:xMun") . '/' . $v("{$endd}/n:UF"), $cep($v("{$endd}/n:CEP"))], ' - '),
    ];
}
```

| Verificação | Resultado |
|---|---|
| Autorização | `cStat = 100` e protocolo presentes nos dois ✔ |
| Chave | `protNFe/infProt/chNFe` igual a `infNFe/@Id` sem o prefixo `NFe`; dígito verificador confere ✔ |
| Chave da venda × etiqueta antiga | A chave lida de `nfe-venda-pf.xml` é a mesma impressa em `exemplo_etiqueta_danfe.zpl` ✔ |
| Número e série | `nNF` vira `000.021.486` e `000.012.345`; série `1` nos dois — iguais às posições 23–34 da chave ✔ |
| Série da etiqueta antiga | O XML da venda confirma série **1**; o "/ 2" impresso pelo sistema antigo estava errado (problema 3 da seção 6) ✔ |
| Datas | `dhEmi` e `dhRecbto` vêm com fuso (`-03:00`) ✔ |
| Pessoa física | Linha do documento sai como `CPF: 000.000.000-00`, sem IE ✔ |
| Pessoa jurídica | Linha do documento sai como `CNPJ: … IE: …` ✔ |
| Tamanho dos textos | Maior linha de endereço do destinatário: 63 caracteres (limite 68); razão social do emitente: 44 (limite 50) ✔ |
| Nota sem protocolo, com `cStat` diferente de 100 ou com chave divergente | A função lança erro nos três casos ✔ |

O que os XML (reais, na análise original) mostraram e mudaram neste guia:

- **Endereço do emitente:** com o bairro, a linha chegou a 75 caracteres e não caberia. A função monta o endereço **sem o bairro** (`logradouro, número, complemento - cidade/UF - CEP`). Textos em maiúsculas ocupam mais largura: use 60 como limite prático.
- **Tipo de operação ≠ finalidade:** na análise original, uma nota de devolução (`finNFe = 4`) tinha `tpNF = 1` (o XML dela não está no projeto). O tipo impresso vem de `tpNF`, não de `finNFe` nem da natureza da operação.
- **Documento do destinatário:** `dest/CPF` **ou** `dest/CNPJ`; a inscrição estadual só existe em parte das notas (`indIEDest = 1`).
- **Data de emissão:** a etiqueta antiga mostrava `02/10/2026`, igual ao `dhEmi` desta nota — mas tirava o valor do pedido; a versão nova lê da nota.

Na versão final do componente, troque `InvalidArgumentException` por `ErroValidacao` e devolva o DTO `DadosDanfe` em vez de um array.

## 8. Testes

| Teste | O que conferir |
|---|---|
| Dígito da chave | Chave real da etiqueta de exemplo → dígito `1`; chave com dígito trocado → `ErroValidacao` |
| Decomposição da chave | Série `001` e número `000021486` extraídos das posições corretas |
| Chave formatada | 11 grupos de 4 dígitos separados por espaço |
| XML autorizado | `deXml()` preenche todos os campos. Venda para pessoa física: `nfe-venda-pf.xml` (já fictício; copie para as fixtures); venda para pessoa jurídica: monte uma fixture fictícia com destinatário CNPJ e IE (o XML de exemplo foi removido) |
| Sem itens e valores | O ZPL gerado não contém descrição de produto, quantidade nem valor |
| XML não autorizado ou sem `protNFe` | `ErroValidacao` |
| Sem código de envio | O parcial não é incluído; o ZPL não contém código de barras de rastreamento nem marcador sobrando |
| Tipo de operação | `tpNF = 0` → `0 - ENTRADA`; `tpNF = 1` → `1 - SAÍDA` |
| Destinatário | CPF e CNPJ formatados; com e sem inscrição estadual |
| Textos longos | Razão social de 60 caracteres e endereço de 90 são cortados sem ultrapassar as áreas |
| Escape | Valores com `_`, `^`, `~`, `\` e acentos |
| Snapshot | ZPL completo da etiqueta de exemplo, em 203 e 300 dpi |
| Saída | UTF-8 sem BOM, iniciando em `^XA` e terminando em `^XZ` |

Dados fictícios usados na verificação: emitente "Loja Teste Homologação Comércio Ltda", CNPJ `11.222.333/0001-81`, IE `123.456.789.110`, SP; nota `000.021.486`, série `2`, emissão `02/10/2026`; chave `35261011222333000181550020000214861123456788`; protocolo `135260000012345`; destinatária "Maria Aparecida Souza", CPF `529.982.247-25`, Praça da Sé, 100, Apto 12, Sé, São Paulo/SP, `01001-000`; código de envio `AB043180865BR`.

## 9. O que não foi verificado

- **Posição da chave de acesso:** a fonte consultada cita o canto superior direito; o template novo mantém a posição do sistema antigo.
- **Conformidade legal:** os requisitos da seção 5 vêm de fonte secundária; a Nota Técnica 2020.004 oficial não foi lida. Posição obrigatória dos campos, tamanhos mínimos e regras para contingência devem ser conferidos nela.
- **Leitura dos códigos de barras** por leitor físico e **impressão** em impressora térmica.
- ~~Template de 300 dpi~~ — ✔ renderizado no Labelary em 04/10/2026 (12 dpmm, 4 × 6 pol), com escala 1,5 (`^BY2` → `^BY3`). A chave de acesso em Code 128 coube na largura de 1200 pontos. Continua sem teste a impressão física.
- **Leitura do XML da NF-e** — testada com a venda para pessoa física (`nfe-venda-pf.xml`, hoje com dados fictícios) e a venda para pessoa jurídica (dados fictícios, em 04/10/2026; o XML saiu da pasta de exemplos e a fixture do componente é uma versão sintética com 2 itens), ambas em emissão normal. Na análise original também foi testada uma devolução para pessoa jurídica, cujo XML não está no projeto. Não foram testados: nota de entrada (`tpNF = 0`), emissão em contingência e destinatário do exterior.
- ~~Renderização do template sem itens e valores~~ — ✔ renderizado no Labelary em 04/10/2026, em 203 e 300 dpi, com o XML de venda para pessoa física **anonimizado** (dados fictícios) e com o código de envio. Todos os blocos ficaram dentro da etiqueta, sem sobreposição.
- **Aceitação em fiscalização ou pelos Correios** de uma etiqueta gerada por este template.

Fontes:
- [Focus NFe — Nota Técnica 2020.004 da NF-e (DANFE Simplificado - Etiqueta)](https://focusnfe.com.br/notas-tecnicas/nfe/2020-004/)
