# Correios — Etiquetas de postagem em ZPL (guia para gerar o rótulo sem a API)

> Documento de referência para desenvolvimento. A API dos Correios só entrega o rótulo em **PDF** (arquivos 08 e 09); ela **não gera ZPL**. Este guia explica como a aplicação deve montar, por conta própria, a etiqueta de postagem em ZPL para impressoras térmicas (Zebra e compatíveis).
>
> **Origem:** análise dos arquivos da pasta `doc/legado/etiqueta-postagem-zpl/` — código e templates de um **sistema antigo** (`CreateZplLabel.php`, `QrCodeLabel.php`, `sedex_template.zpl`, `pac_template.zpl`, `carta_template.zpl`). Eles servem de **exemplo de funcionamento**, não de código a copiar: a seção 7 lista o que deve mudar. Analisado em 03/10/2026.
>
> **Como foi verificado:** os templates foram preenchidos com dados fictícios e renderizados no Labelary (203 dpi, 4×6,2 pol); os logotipos foram decodificados para imagem; o CRC dos blocos `:Z64:` foi recalculado e confere. O que **não** foi verificado está na seção 10.
>
> Regras gerais (ambientes, autenticação, convenções): `correios-00-instrucoes-gerais.md` · Dados da etiqueta vêm de: `correios-06-prepostagem-criar-api.md` (pré-postagem) e `correios-09-rotulo-download-assincrono-api.md` (elementos do rótulo oficial).

## 0. Resumo rápido

- A etiqueta é um **texto ZPL** montado a partir de um **template** com marcadores, preenchido com os dados da pré-postagem.
- Tamanho: **800 × 1239 pontos a 203 dpi** (8 pontos/mm) ≈ **100 × 155 mm** (etiqueta 10×15 cm).
- Elementos, de cima para baixo: logo da loja · **DataMatrix** · logo do serviço · NF, contrato e serviço · **código do objeto em Code 128** · campos de recebimento · bloco do destinatário com **CEP em Code 128** · remetente · observações.
- O DataMatrix leva uma cadeia fixa de **164 caracteres** (seção 4).
- Os logotipos vão embutidos no ZPL como imagem (`^GFA`), em hexadecimal compactado ou em `:Z64:` (seção 5).
- O **código do objeto** vem da pré-postagem (`codigoObjeto`). A aplicação nunca gera esse código.

### Fluxo

```
1. POST /v1/prepostagens                → id, codigoObjeto, statusAtual = 2      (arquivo 06)
2. Montar os dados da etiqueta          → remetente, destinatário, serviço, contrato, cartão, adicionais
3. Montar a cadeia do DataMatrix        → 164 caracteres                           (seção 4)
4. Escolher o logo do serviço           → pelo código do serviço                   (seção 5)
5. Preencher o template                 → texto ZPL                                (seções 6 e 8)
6. Enviar o ZPL à impressora            → porta 9100, fila de impressão ou navegador
```

## 1. Arquivos analisados (sistema antigo)

| Arquivo | O que é |
|---|---|
| `CreateZplLabel.php` | Classe que recebe remetente, destinatário, contrato, dados do envio, código do objeto e observações; escolhe o template pelo nome do serviço; substitui os marcadores e devolve o ZPL. |
| `QrCodeLabel.php` | Monta a cadeia de dados do código 2D. **Apesar do nome, o código impresso é um DataMatrix, não um QR Code.** |
| `sedex_template.zpl` | Template com logo da loja + símbolo do SEDEX. |
| `pac_template.zpl` | Template com logo da loja + símbolo do PAC. |
| `carta_template.zpl` | Template com logo da loja + chancela "Carta". |
| `etiqueta_exemplo.zpl` | Etiqueta real gerada pelo sistema antigo (SEDEX), usada para validar a cadeia do DataMatrix — ver seção 4. |
| `doc/recursos/logos-png/*.png` | Imagens extraídas dos templates nesta análise (não existiam no sistema antigo) — ver seção 5. |
| `doc/recursos/converter-imagem-para-gfa.php` | Script novo (não é do sistema antigo) que converte uma imagem em parcial `^GFA` — ver seção 5.5. |

Os três templates são **idênticos**, exceto pelo bloco do logo do serviço.

## 2. Como o sistema antigo funcionava

```
CreateZplLabel::getLabel()
 ├─ getQrCode()                                   → QrCodeLabel::create(...) devolve a cadeia de 164 caracteres
 ├─ arquivo = strtolower(nome do serviço) + "_template.zpl"     (ex.: "SEDEX" → sedex_template.zpl)
 ├─ lê o template                                 → exceção 1001 se o arquivo não existir
 └─ str_replace de cada marcador [NOME] pelo valor → devolve o ZPL
```

Marcadores do template antigo (formato `[NOME]`):

| Marcador | Origem no sistema antigo |
|---|---|
| `[CODIGO_MATRIX]` | Cadeia do DataMatrix |
| `[NUMERO_NF]` | Número da nota fiscal (vazio se não houver) |
| `[CONTRATO]` | Número do contrato |
| `[NOME_SERVICO]` | Nome do serviço |
| `[CODIGO_ETIQUETA]` | Código do objeto (rastreio) |
| `[NOME_DESTINATARIO]`, `[ENDERECO_DESTINATARIO]`, `[NUMERO_ENDERECO_DESTINATARIO]`, `[COMPLEMENTO_DESTINATARIO]`, `[BAIRRO_DESTINATARIO]`, `[CIDADE_DESTINATARIO]`, `[ESTADO_DESTINATARIO]` | Dados do destinatário |
| `[CEP_DESTINATARIO]` | CEP com 8 dígitos (código de barras) |
| `[CEP_DESTINATARIO_COM_HIFEN]` | CEP no formato `00000-000` (texto) |
| `[NOME_REMETENTE]`, `[ENDERECO_REMETENTE]`, `[NUMERO_ENDERECO_REMETENTE]`, `[COMPLEMENTO_REMETENTE]`, `[BAIRRO_REMETENTE]`, `[CEP_REMETENTE_COM_HIFEN]`, `[CIDADE_REMETENTE]`, `[ESTADO_REMETENTE]` | Dados do remetente |
| `[OBSERVACOES]` | Texto livre |

## 3. Anatomia da etiqueta (203 dpi)

Coordenadas em pontos, origem no canto superior esquerdo. `^FO` posiciona pelo canto superior esquerdo do campo; `^FT` posiciona pela **linha de base** (parte de baixo do texto ou do código).

| # | Elemento | Comando | Posição | Tamanho / fonte |
|---|---|---|---|---|
| 1 | Moldura externa | `^FO17,45^GB765,1155,2` | 17,45 | 765 × 1155, traço 2 |
| 2 | Logo da loja (no template novo: logo dos Correios) | `^FO25,50^GFA,8000,8000,32,...` | 25,50 | imagem 256 × 250 |
| 3 | DataMatrix | `^FT290,270^BXN,5,200,0,0,1` | base em 290,270 | módulo 5, ECC 200 (≈ 190 × 190 com 164 caracteres) |
| 4 | Logo do serviço | `^FO512,64^GFA,...` (SEDEX e Carta) · `^FO560,100^GFA,...` (PAC) | ver seção 5 | 224 × 224 · 160 × 147 |
| 5 | Nota fiscal | `^FT31,305^A0N,28,28` | 31,305 | fonte 0, 28 |
| 6 | Contrato | `^FT270,305^A0N,28,28` | 270,305 | fonte 0, 28 |
| 7 | Nome do serviço | `^FT270,335^A0N,28,28` | 270,335 | fonte 0, 28 |
| 8 | Código do objeto (Code 128) | `^BY3,3,120^FT120,510,0^BCN,,Y,Y` | base em 120,510 | módulo 3, altura 120, texto legível **acima** |
| 9 | Recebedor / Assinatura / Documento | `^FT40,550` · `^FT40,590` · `^FT380,590` com `^A0N,32,31` | — | linhas com sublinhados |
| 10 | Caixa do destinatário | `^FO17,610^GB765,275,2` | 17,610 | 765 × 275 |
| 11 | Tarja preta "DESTINATARIO" | `^FO17,610^GB195,40,40` + `^FT30,640^FR^A0N,30,29` | 17,610 | texto em negativo (`^FR`) |
| 12 | Nome do destinatário | `^FT50,677^A0N,28,27` | 50,677 | |
| 13 | Logradouro, número, complemento | `^FT50,705^A0N,28,27` | 50,705 | uma linha |
| 14 | Bairro | `^FT50,730^A0N,28,27` | 50,730 | |
| 15 | CEP + cidade/UF | `^FT50,755^A0N,28,27` | 50,755 | CEP reimpresso em 49,755 e 50,754 para simular negrito |
| 16 | CEP de destino (Code 128) | `^BY3,2,110^FT50,875^BCN,,N,N` | base em 50,875 | módulo 3, altura 110, **sem** texto legível |
| 17 | Rótulo "Remetente:" | `^FT30,920^A0N,32,31` | 30,920 | |
| 18 | Nome, endereço, bairro, CEP/cidade do remetente | `^FT50,950` · `980` · `1010` · `1040` com `^A0N,25,24` | — | CEP também simulando negrito |
| 19 | Linha separadora | `^FO37,1055^GB726,0,1` | 37,1055 | |
| 20 | Observações | `^FT50,1090^A0N,25,24` | 50,1090 | uma linha |

Cabeçalho e rodapé: `^XA` (início) · `^CI28` (texto em UTF-8) · `^LL1239` (comprimento da etiqueta) · `^LS0` (deslocamento lateral) · `^XZ` (fim).

### Comandos ZPL usados

| Comando | Função |
|---|---|
| `^XA` / `^XZ` | Início e fim da etiqueta |
| `^CI28` | Interpreta os textos como UTF-8 (acentos) |
| `^PW`, `^LL`, `^LH`, `^LS` | Largura, comprimento, origem e deslocamento da etiqueta |
| `^FO x,y` / `^FT x,y` | Posição pelo canto superior esquerdo / pela linha de base |
| `^A0N,alt,larg` | Fonte 0 (escalável), sem rotação |
| `^FD ... ^FS` | Conteúdo do campo e fim do campo |
| `^FH` | Permite caracteres em hexadecimal dentro do `^FD` (`_XX`) |
| `^FR` | Imprime em negativo (texto branco sobre a tarja preta) |
| `^FB larg,linhas,esp,alin` | Bloco de texto com quebra automática de linha |
| `^GB larg,alt,traço` | Caixa ou linha |
| `^GFA,total,total,bytesPorLinha,dados` | Imagem embutida |
| `^BY módulo,razão,altura` | Parâmetros padrão dos códigos de barras |
| `^BCN,alt,texto,textoAcima` | Code 128 |
| `^BXN,módulo,200` | DataMatrix ECC 200 |
| `^FX` | Comentário |
| `^PQ quantidade` | Quantidade de cópias |

## 4. Cadeia de dados do DataMatrix

`QrCodeLabel::create()` concatena **19 campos de tamanho fixo**, sem separadores, formando **164 caracteres**.

| # | Campo (nome no código antigo) | Tam. | Valor no sistema antigo | De onde tirar na nova aplicação |
|---|---|:-:|---|---|
| 1 | `CEPdestino` | 8 | CEP do destinatário, só dígitos | `destinatario.endereco.cep` |
| 2 | `ComplementoDoCEP` | 5 | fixo `00000` | manter `00000` |
| 3 | `CEPOrigem` | 8 | CEP do remetente, só dígitos | `remetente.endereco.cep` |
| 4 | `ComplementoDoCEPOrigem` | 5 | fixo `00000` | manter `00000` |
| 5 | `ValidadorDoCEPDestino` | 1 | dígito calculado (ver abaixo) | calcular |
| 6 | `IDV` | 2 | fixo `51` | manter `51` |
| 7 | `Etiqueta` | 13 | código do objeto | `codigoObjeto` da pré-postagem |
| 8 | `ServicosAdicionais` | 12 | fixo `250000000000` | ver "Pontos a confirmar" |
| 9 | `CartaoDePostagem` | 10 | cartão, com zeros à esquerda | cartão de postagem da configuração |
| 10 | `CodigoServico` | 5 | código do serviço, com zeros à esquerda | `codigoServico` (ex.: `03220`) |
| 11 | `InformacaoDeAgrupamento` | 2 | fixo `00` | manter `00` |
| 12 | `NumeroDoLogradouro` | 5 | número do endereço do destinatário, com zeros à esquerda | `destinatario.endereco.numero` (só dígitos) |
| 13 | `ComplementoDoLogradouro` | 20 | complemento do destinatário, cortado em 20 e completado com espaços | `destinatario.endereco.complemento` |
| 14 | `ValorDeclarado` | 5 | fixo `00000` | ver "Pontos a confirmar" |
| 15 | `DDDTelefoneDestinatario` | 12 | fixo `000000000000` | ver "Pontos a confirmar" |
| 16 | `Latitude` | 10 | fixo `-00.000000` | manter |
| 17 | `Longitude` | 10 | fixo `-00.000000` | manter |
| 18 | `Pipe` | 1 | `\|` | manter |
| 19 | `ReservaParaCliente` | 30 | 30 zeros | manter (ou uso próprio, ex.: número do pedido) |
| | **Total** | **164** | | |

### Dígito validador do CEP de destino

Some os 8 dígitos do CEP e calcule quanto falta para o próximo múltiplo de 10. Se a soma já for múltiplo de 10, o dígito é `0`.

```
CEP 01001000 → 0+1+0+0+1+0+0+0 = 2 → próximo múltiplo de 10 = 10 → validador = 8
```

```php
$soma = array_sum(str_split($cep));          // $cep com 8 dígitos
$validador = (10 - ($soma % 10)) % 10;
```

### Exemplo completo

Destino `01001000`, origem `01310100`, objeto `AB043180865BR`, cartão `0012345678`, serviço `03220`, número `100`, complemento `APTO 12`:

```
01001000000000131010000000851AB043180865BR2500000000000012345678032200000100APTO 12             00000000000000000-00.000000-00.000000|000000000000000000000000000000
```

Decomposição: `01001000` · `00000` · `01310100` · `00000` · `8` · `51` · `AB043180865BR` · `250000000000` · `0012345678` · `03220` · `00` · `00100` · `APTO 12` + 13 espaços · `00000` · `000000000000` · `-00.000000` · `-00.000000` · `|` · 30 zeros.

### ✔ Validação com uma etiqueta real do sistema antigo

O arquivo `doc/legado/etiqueta-postagem-zpl/etiqueta_exemplo.zpl` é uma etiqueta **gerada pelo sistema antigo** (SEDEX, serviço `03220`, objeto `AA123456785BR`). A cadeia do DataMatrix nela é:

```
01001000000000131010000000851AA123456785BR2500000000000012345678032200000100APTO 12             00000000000000000-00.000000-00.000000|000000000000000000000000000000
```

| Conferência | Resultado |
|---|---|
| Tamanho total | **164 caracteres** ✔ |
| Ordem e tamanho dos 19 campos | Iguais à tabela acima ✔ |
| Dígito validador do CEP | CEP `93220680` → soma 30 → validador `0` ✔ (confirma o caso "soma múltipla de 10") |
| Campos fixos | `00000` · `51` · `250000000000` · `00` · `00000` · `000000000000` · `-00.000000` (2×) · `\|` · 30 zeros — exatamente os valores do código antigo ✔ |
| Cartão e serviço | `0012345678` e `03220`, com zeros à esquerda ✔ |
| Número e complemento | `00432` e `APTO 12` + 5 espaços ✔ |
| Classe `DataMatrixCorreios` (seção 8.5) | Com os mesmos dados de entrada, gera **exatamente** essa cadeia ✔ |
| Dígito verificador do código do objeto | `AA123456785BR` confere com a regra do módulo 11 ✔ |
| Logotipos | Os dois blocos `^GFA` são idênticos aos de `sedex_template.zpl` ✔ |

O que a etiqueta real mostra sobre o uso do sistema antigo:

- O **nome do serviço** impresso era a forma curta (`SEDEX`), não o nome do contrato (`SEDEX CONTRATO AG`).
- O campo de observações recebia o texto `Observações:` mesmo sem conteúdo — na versão nova, deixe a linha vazia quando não houver observação.
- A linha de endereço do destinatário tinha 62 caracteres em uma única linha, perto do limite da largura — confirma a necessidade de quebra com `^FB`.
- O arquivo termina com um caractere invisível (BOM, `U+FEFF`) depois do `^XZ`. A versão nova deve gerar o ZPL em UTF-8 **sem BOM**.

### Pontos a confirmar no manual de etiquetas dos Correios

A estrutura acima é a que o sistema antigo usava. O significado oficial de alguns campos **não está em nenhum arquivo desta documentação**:

- **`ServicosAdicionais` (12):** o sistema antigo enviava sempre `25` seguido de zeros, sem refletir os adicionais contratados (valor declarado, AR, mão própria). Enquanto o manual não for fornecido, **mantenha o valor antigo** e deixe o campo parametrizável. Não invente a codificação a partir dos códigos de 3 dígitos da API (`001`, `019`, `064`).
- **`ValorDeclarado` (5) e `DDDTelefoneDestinatario` (12):** sempre zeros no sistema antigo. Mantenha zeros e deixe parametrizável.
- **`IDV` = `51`:** valor fixo no sistema antigo para todos os serviços.

Peça o manual ao usuário para preencher esses campos corretamente. Se ele não existir, siga o comportamento antigo e registre a limitação no README.

## 5. Logotipos embutidos (`^GFA`)

### 5.1 O que há nos templates

| Imagem | Onde | Posição | Tamanho | Bytes por linha | Total de bytes | Codificação | Arquivo decodificado |
|---|---|---|---|:-:|:-:|---|---|
| Logo da loja ("LOJA") — **substituído pelo logo dos Correios no template novo** | os 3 templates | `^FO25,50` | 256 × 250 | 32 | 8000 | hexadecimal compactado | `logo_loja_loja_256x250.png` |
| Símbolo SEDEX | `sedex_template.zpl` | `^FO512,64` | 224 × 224 | 28 | 6272 | `:Z64:` | `logo_servico_sedex_224x224.png` |
| Símbolo PAC (círculo) | `pac_template.zpl` | `^FO560,100` | 160 × 147 | 20 | 2940 | hexadecimal compactado | `logo_servico_pac_160x147.png` |
| Chancela "Carta" | `carta_template.zpl` | `^FO512,64` | 224 × 224 | 28 | 6272 | `:Z64:` | `logo_servico_carta_224x224.png` |

Os PNG estão em `doc/recursos/logos-png/` (1 bit, preto sobre branco), exceto o logo "LOJA", que fica em `doc/legado/etiqueta-postagem-zpl/` por não fazer parte do componente. Servem para conferir o resultado e para gerar versões em outra resolução.

### 5.2 Formato do comando

```
^GFA,<total de bytes>,<total de bytes>,<bytes por linha>,<dados>^FS
```

- `bytes por linha` = largura em pontos ÷ 8, arredondado para cima;
- `total de bytes` = bytes por linha × altura;
- cada bit é um ponto (1 = preto), da esquerda para a direita, linha a linha.

Os `<dados>` aparecem de duas formas nos templates:

**a) Hexadecimal compactado (ASCII)** — dois caracteres hexadecimais por byte, com atalhos:

| Símbolo | Significado |
|---|---|
| `G` a `Y` | repete o próximo caractere hexadecimal 1 a 19 vezes (`G`=1 … `Y`=19) |
| `g` a `z` | repete 20, 40, … 400 vezes (`g`=20 … `z`=400); soma-se com `G`–`Y` (ex.: `gG` = 21) |
| `,` | completa o resto da linha com zeros (branco) |
| `!` | completa o resto da linha com `F` (preto) |
| `:` | repete a linha anterior |

**b) `:Z64:`** — `:Z64:<dados em Base64>:<CRC>`, em que os dados são os bytes da imagem comprimidos com **zlib** (deflate) e codificados em **Base64**, e o CRC são 4 dígitos hexadecimais do **CRC-16/XMODEM** (polinômio `0x1021`, valor inicial `0x0000`) calculado sobre o **texto Base64**. Conferido nos dois blocos dos templates (`72B8` e `E311`).

### 5.3 Como gerar um `^GFA` a partir de uma imagem (PHP)

```php
function imagemParaGfa(string $caminhoPng, int $limiar = 128): string
{
    $img = imagecreatefrompng($caminhoPng);
    $larg = imagesx($img);
    $alt = imagesy($img);
    $bytesPorLinha = intdiv($larg + 7, 8);
    $bin = '';

    for ($y = 0; $y < $alt; $y++) {
        for ($b = 0; $b < $bytesPorLinha; $b++) {
            $byte = 0;
            for ($bit = 0; $bit < 8; $bit++) {
                $x = $b * 8 + $bit;
                if ($x < $larg) {
                    $c = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                    $cinza = ($c['red'] * 299 + $c['green'] * 587 + $c['blue'] * 114) / 1000;
                    if ($c['alpha'] < 64 && $cinza < $limiar) {   // ponto escuro e opaco = preto
                        $byte |= (0x80 >> $bit);
                    }
                }
            }
            $bin .= chr($byte);
        }
    }

    $total = strlen($bin);
    $base64 = base64_encode(gzcompress($bin, 9));
    $crc = 0;                                           // CRC-16/XMODEM sobre o texto Base64
    foreach (str_split($base64) as $ch) {
        $crc ^= ord($ch) << 8;
        for ($i = 0; $i < 8; $i++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
        }
    }

    return sprintf('^GFA,%d,%d,%d,:Z64:%s:%04X', $total, $total, $bytesPorLinha, $base64, $crc);
}
```

- Gere o `^GFA` **uma vez** (em tempo de build ou por comando de console) e guarde o resultado como arquivo parcial do template. Não converta a imagem a cada etiqueta.
- A extensão GD é necessária só para a conversão; a geração da etiqueta em si não depende dela.
- O formato hexadecimal simples (sem compactação) também é válido e mais fácil de testar: `^GFA,total,total,bytesPorLinha,` + `strtoupper(bin2hex($bin))`.

### 5.4 Parciais prontos

A pasta `doc/recursos/parciais-zpl/` traz os trechos já prontos para copiar para `resources/templates/parciais/` do componente:

| Arquivo | Conteúdo | Posição | Tamanho |
|---|---|---|---|
| `logo_correios.zpl` | Logo dos Correios, 203 dpi (ver 5.5) | `^FO41,56` | 224 × 224 |
| `logo_correios_300dpi.zpl` | Logo dos Correios, 300 dpi | `^FO62,84` | 336 × 336 |
| `logo_servico_sedex.zpl` | Símbolo do SEDEX, extraído de `sedex_template.zpl` | `^FO512,64` | 224 × 224 |
| `logo_servico_pac.zpl` | Símbolo do PAC, extraído de `pac_template.zpl` | `^FO560,100` | 160 × 147 |
| `logo_servico_carta.zpl` | Chancela Carta, extraída de `carta_template.zpl` | `^FO512,64` | 224 × 224 |
| `exemplo_etiqueta_sedex_203dpi.zpl` | Etiqueta completa de exemplo (template base + logos + dados fictícios), para colar no Labelary | — | 800 × 1239 |

Cada parcial está em uma única linha, com `^FO`, `^GFA` e `^FS`.

### 5.5 Logo dos Correios no lugar do logo da loja

No template novo, a posição `^FO25,50` (área de até **256 × 250 pontos**) é do **logo dos Correios** — parcial `logo_correios.zpl` —, e não mais do logo "LOJA" do sistema antigo.

- **Os parciais já estão prontos**, gerados em 03/10/2026 a partir da imagem fornecida pelo usuário (círculo preto com o símbolo e o nome em branco; recortada em `logos-png/logo_correios_original_524x524.png`):

  | Resolução | Parcial | Posição | Tamanho | Bytes por linha / total | Imagem resultante |
  |---|---|---|---|---|---|
  | 203 dpi | `parciais-zpl/logo_correios.zpl` | `^FO41,56` | 224 × 224 | 28 / 6272 | `logo_correios_224x224.png` |
  | 300 dpi | `parciais-zpl/logo_correios_300dpi.zpl` | `^FO62,84` | 336 × 336 | 42 / 14112 | `logo_correios_336x336.png` |

  O logo de 203 dpi termina em y = 280, acima da linha de texto `NF / Contrato / Vol`. Não aumente além de 224 pontos nessa posição: o círculo encostaria no texto da nota fiscal.
- **Não desenhe nem recrie o logo** por conta própria. Para trocar a imagem ou gerar outro tamanho, parta sempre de um arquivo fornecido pelo usuário.
- **Limitação:** o leve sombreado interno do símbolo vira branco sólido na conversão para 1 bit (impressora térmica só imprime preto ou branco).
- **Como gerar:** use o script `doc/recursos/converter-imagem-para-gfa.php` (mesma lógica da seção 5.3, com redimensionamento e fundo transparente tratado como branco):

  ```
  php converter-imagem-para-gfa.php logo_correios_original_524x524.png 41 56 224 224 > logo_correios.zpl
  php converter-imagem-para-gfa.php logo_correios_original_524x524.png 62 84 336 336 > logo_correios_300dpi.zpl
  ```

  A saída já vem pronta para o parcial: `^FO25,50^GFA,...:Z64:...^FS`. Os argumentos são: imagem, posição x e y, largura e altura máximas em pontos e, opcionalmente, o limiar de preto (0 a 255, padrão 128).
- **Qualidade:** use a versão **monocromática (preta)** do logo, em boa resolução. Imagens coloridas ou com degradê perdem definição ao virar 1 bit; ajuste o limiar se o traço sair fino ou falhado e confira no Labelary.
- Se o parcial não estiver configurado, o marcador `{{>logo_correios}}` some do resultado e a etiqueta sai sem o logo (não é erro), mas ela **não deve ir para produção** assim.
- O script foi testado reconvertendo `logos-png/logo_servico_pac_160x147.png`: os bytes saem idênticos aos do template antigo.

## 6. O que a nova aplicação deve reaproveitar

| Do sistema antigo | Reaproveitar? |
|---|---|
| Disposição geral dos elementos e coordenadas (seção 3) | **Sim**, como ponto de partida do template de 203 dpi |
| Estrutura de 164 caracteres do DataMatrix e o dígito validador do CEP | **Sim** |
| Parâmetros dos códigos (`^BXN,5,200`, `^BY3,3,120^BCN,,Y,Y`, `^BY3,2,110^BCN,,N,N`) | **Sim** |
| Símbolos dos serviços (SEDEX, PAC, Carta) | **Sim** — extrair os blocos `^GFA` dos templates para arquivos parciais |
| Logo "LOJA" fixo no template | **Não** — nessa posição entra o **logo dos Correios** (seção 5.5) |
| Classes PHP (`CreateZplLabel`, `QrCodeLabel`) | **Não** — reescrever conforme a seção 8 |

## 7. Problemas do código antigo que a nova versão deve corrigir

| # | Problema | Consequência | O que fazer |
|---|---|---|---|
| 1 | Valores inseridos **sem escape** em campos com `^FH` | Com `^FH`, o caractere `_` inicia um código hexadecimal: `Loja_AB` vira um caractere estranho. `^` e `~` no texto quebram a etiqueta. | Escapar todo valor: `_` → `_5F`, `^` → `_5E`, `~` → `_7E`, `\` → `_5C` (verificado no Labelary). |
| 2 | Template escolhido por `strtolower(nome do serviço)` | Só funciona se o nome for exatamente `SEDEX`, `PAC` ou `CARTA`. Os nomes da API são outros (`SEDEX CONTRATO AG`, `PAC CONTRATO AG`, `SEDEX REVERSO`...). | Mapear o **código do serviço** para o logo, por configuração. |
| 3 | Três templates quase iguais | Qualquer ajuste de layout precisa ser repetido três vezes. | Um template base + parciais de logo. |
| 4 | Logo da loja ("LOJA") embutido no template | O canto superior esquerdo da etiqueta deve trazer o **logo dos Correios**, não o da loja. | Trocar pelo parcial `logo_correios` (seção 5.5). |
| 5 | `QrCodeLabel` usa um array **estático** alterado a cada chamada | Estado compartilhado entre chamadas; valores de uma etiqueta podem vazar para outra. | Objeto imutável, sem estado estático. |
| 6 | `sprintf('%05d', $numero)` no número do logradouro | `S/N` ou `123A` viram `00000` ou `00123` sem aviso; números com mais de 5 dígitos estouram o campo e deslocam os campos seguintes. | Extrair só os dígitos, limitar a 5 e completar com zeros; sem dígitos → `00000`. |
| 7 | `substr()` e `str_pad()` contam **bytes** | Complemento com acento (`Apto 3 – Bl. Ç`) ocupa mais de 20 bytes e desalinha a cadeia. | Remover acentos e caracteres fora de `A–Z`, `0–9` e espaço, converter para maiúsculas, e só então cortar e completar para 20. |
| 8 | Nenhum campo valida o tamanho final | Uma cadeia com tamanho diferente de 164 gera um DataMatrix inválido para os Correios. | Validar o tamanho de cada campo e o total de 164; lançar `ErroValidacao` se divergir. |
| 9 | Textos longos **não quebram nem são cortados** | Endereço comprido sai da etiqueta ou sobrepõe a moldura. | Usar `^FB` nos campos de endereço e observações, e cortar o excedente no código. |
| 10 | Complemento vazio gera `Rua X, 100, ` | Vírgula sobrando. | Montar a linha de endereço no código, juntando só as partes preenchidas. |
| 11 | Sem nota fiscal imprime `NF: ` | Rótulo solto. | Omitir o rótulo ou imprimir o número do pedido. |
| 12 | Negrito simulado reimprimindo o CEP com 1 ponto de deslocamento | Três campos para o mesmo texto. | Usar fonte maior para o CEP (seção 8.2). |
| 13 | Linhas de bairro e CEP com 25 pontos de distância para fonte de 28 | Textos encostam um no outro. | Reespaçar o bloco do destinatário (seção 8.2). |
| 14 | Quebras de linha dentro de `^FD ... ^FS` | Funciona, mas dificulta a leitura e os testes de comparação. | Um campo por linha, sem quebra dentro do `^FD`. |
| 15 | `^FR^AC^A0N` (duas fontes seguidas) e `^PQ1,0,1,Y` solto dentro do bloco do logo da Carta | Comandos redundantes. | Remover. |
| 16 | Coordenadas fixas para 203 dpi | Em impressora de 300 dpi a etiqueta sai reduzida (≈ 68 mm de largura). | Suporte a 300 dpi (seção 8.4). |
| 17 | Faltam dados que o rótulo oficial traz | Sem peso, sem siglas dos adicionais (`VD`, `AR`), sem marca de homologação. | Acrescentar (seção 8.2). |
| 18 | Exceção genérica (`Exception`, código 1001) e nomes misturados | Difícil tratar o erro. | Usar `ErroTemplate` / `ErroValidacao` do componente. |
| 19 | Classe chamada "QrCode" | Induz a gerar o código errado. | Nomear como DataMatrix (`DataMatrixCorreios`). |

## 8. Projeto recomendado para a nova aplicação

### 8.1 Estrutura

```
resources/templates/
├── postagem.zpl                    # template base (203 dpi)
├── parciais/
│   ├── logo_correios.zpl           # pronto em parciais-zpl/ (seção 5.5)
│   ├── logo_servico_sedex.zpl      # pronto em parciais-zpl/ (seção 5.4)
│   ├── logo_servico_pac.zpl        # pronto em parciais-zpl/
│   ├── logo_servico_carta.zpl      # pronto em parciais-zpl/
│   └── marca_homologacao.zpl
```

- **Marcadores de valor:** `{{nome}}` (o formato antigo `[NOME]` não é mantido).
- **Marcadores de parcial:** `{{>nome}}` — o renderizador troca pelo conteúdo do arquivo parcial, ou por nada se o parcial não estiver configurado.
- **Logo dos Correios:** parcial fixo `logo_correios`, igual para todos os serviços.
- **Mapa serviço → logo**, vindo da configuração, pelo **código** do serviço. Exemplo: `03220` e `03247` → `logo_servico_sedex`; `03298` e `03301` → `logo_servico_pac`. Código sem logo configurado → etiqueta sem logo de serviço (não é erro).
- Cada parcial de logo contém o `^FO` e o `^GFA` completos, terminando em `^FS`.

### 8.2 Template base (203 dpi) — verificado no Labelary

```zpl
^XA
^CI28
^PW800
^LL1239
^LH0,0
^LS0
^FX --- Moldura externa ---
^FO17,45^GB765,1155,2^FS
^FX --- Logo dos Correios (parcial; area de 256x250 a partir de 25,50) ---
{{>logo_correios}}
^FX --- DataMatrix (164 caracteres) ---
^FT290,270^BXN,5,200^FD{{datamatrix}}^FS
^FX --- Logo do servico (parcial) ---
{{>logo_servico}}
^FX --- Dados do envio ---
^FT31,305^A0N,28,28^FH^FD{{linha_nf}}^FS
^FT270,305^A0N,28,28^FH^FDContrato: {{contrato}}^FS
^FT600,305^A0N,28,28^FH^FDVol: {{volume}}^FS
^FT31,335^A0N,28,28^FH^FDPeso: {{peso_gramas}} g^FS
^FT270,335^A0N,28,28^FH^FD{{nome_servico}}^FS
^FT600,335^A0N,28,28^FH^FD{{siglas_adicionais}}^FS
^FX --- Codigo do objeto: Code 128 com texto legivel acima ---
^BY3,3,120^FT120,510^BCN,,Y,Y^FD{{codigo_objeto}}^FS
^FX --- Recebimento ---
^FT40,550^A0N,32,31^FDRecebedor:______________________________^FS
^FT40,590^A0N,32,31^FDAssinatura:____________^FS
^FT380,590^A0N,32,31^FDDocumento:______________^FS
^FX --- Bloco do destinatario ---
^FO17,610^GB765,275,2^FS
^FO17,610^GB195,40,40^FS
^FT30,640^FR^A0N,30,29^FDDESTINATARIO^FS
^FO50,657^A0N,28,27^FH^FD{{destinatario_nome}}^FS
^FO50,686^A0N,26,25^FB710,2,0,L^FH^FD{{destinatario_endereco}}^FS
^FO50,740^A0N,26,25^FH^FD{{destinatario_bairro}}^FS
^FO50,768^A0N,32,31^FH^FD{{destinatario_cep_hifen}}^FS
^FO200,771^A0N,28,27^FH^FD{{destinatario_cidade_uf}}^FS
^FX --- CEP de destino em Code 128, sem texto legivel ---
^BY3,2,70^FO50,805^BCN,,N,N^FD{{destinatario_cep}}^FS
^FX --- Bloco do remetente ---
^FT30,920^A0N,32,31^FDRemetente:^FS
^FO50,930^A0N,25,24^FH^FD{{remetente_nome}}^FS
^FO50,958^A0N,25,24^FB710,2,0,L^FH^FD{{remetente_endereco}}^FS
^FO50,1010^A0N,25,24^FH^FD{{remetente_bairro}}^FS
^FO50,1038^A0N,25,24^FH^FD{{remetente_cep_hifen}} {{remetente_cidade_uf}}^FS
^FO37,1070^GB726,0,1^FS
^FX --- Observacoes (ate 2 linhas) ---
^FO50,1082^A0N,25,24^FB710,2,0,L^FH^FD{{observacoes}}^FS
{{>marca_homologacao}}
^XZ
```

Diferenças em relação ao template antigo: `^PW800` explícito; marcadores `{{}}`; logos como parciais; endereços e observações com `^FB` (até 2 linhas); CEP do destinatário em fonte maior no lugar do negrito simulado; código de barras do CEP com altura 70 para caber no bloco reespaçado; novos campos de peso, volume e siglas dos adicionais.

Parcial sugerido para `marca_homologacao.zpl` (incluído só quando o ambiente for homologação):

```zpl
^FO50,1150^A0N,30,30^FDHOMOLOGACAO - IMPRESSAO DE TESTE^FS
```

### 8.3 Marcadores do template base

| Marcador | Conteúdo | Tamanho máximo sugerido | Origem |
|---|---|:-:|---|
| `{{datamatrix}}` | Cadeia de 164 caracteres (seção 4) | 164 exatos | `DataMatrixCorreios` |
| `{{linha_nf}}` | `NF: 1234`, `Pedido: 999` ou vazio | 16 | `numeroNotaFiscal` / pedido |
| `{{contrato}}` | Número do contrato | 10 | configuração ou token |
| `{{volume}}` | `1/1`, `2/3` | 7 | aplicação |
| `{{peso_gramas}}` | Peso informado | 6 | `pesoInformado` |
| `{{nome_servico}}` | Nome do serviço | 22 | nome configurado ou da API Meu Contrato |
| `{{siglas_adicionais}}` | `VD`, `AR`, `MP`... separadas por espaço | 12 | adicionais da pré-postagem |
| `{{codigo_objeto}}` | 13 caracteres, sem espaços | 13 exatos | `codigoObjeto` |
| `{{destinatario_nome}}` | Nome | 50 | pré-postagem |
| `{{destinatario_endereco}}` | `Logradouro, número, complemento` (só as partes preenchidas) | 90 (2 linhas) | pré-postagem |
| `{{destinatario_bairro}}` | Bairro | 30 | pré-postagem |
| `{{destinatario_cep_hifen}}` | `00000-000` | 9 | pré-postagem |
| `{{destinatario_cidade_uf}}` | `Cidade/UF` | 34 | pré-postagem |
| `{{destinatario_cep}}` | 8 dígitos (código de barras) | 8 exatos | pré-postagem |
| `{{remetente_nome}}`, `{{remetente_endereco}}`, `{{remetente_bairro}}`, `{{remetente_cep_hifen}}`, `{{remetente_cidade_uf}}` | Dados do remetente | 50 · 90 · 30 · 9 · 34 | pré-postagem |
| `{{observacoes}}` | Texto livre | 110 (2 linhas) | `observacao` ou aplicação |
| `{{>logo_correios}}`, `{{>logo_servico}}`, `{{>marca_homologacao}}` | Parciais | — | configuração |

Os limites de nome, bairro e cidade são os da própria API (arquivo 06). Os de endereço e observações são estimativas para a largura útil de 710 pontos; ajuste após testar no Labelary.

Para **logística reversa**, os papéis se invertem: o remetente é o cliente que devolve e o destinatário é a loja (arquivo 06).

### 8.4 Regras do renderizador

1. **Escape:** em todo valor inserido em campo com `^FH`, trocar `_` → `_5F`, `^` → `_5E`, `~` → `_7E`, `\` → `_5C`. Fazer o escape **depois** de cortar o texto.
2. **Campos sem `^FH`** (DataMatrix e códigos de barras): não escapar; **validar** o conteúdo. Código do objeto: `^[A-Z]{2}[0-9]{9}[A-Z]{2}$`. CEP: 8 dígitos. DataMatrix: 164 caracteres do conjunto `A–Z`, `0–9`, espaço, `-`, `.` e `|`.
3. **Acentuação:** `^CI28` + valores em UTF-8. Remover caracteres de controle e quebras de linha dos valores.
4. **Corte:** cortar por **caracteres** (`mb_substr`), não por bytes, respeitando os limites da tabela 8.3. Em campo com `^FB`, o texto que passar do número de linhas é sobreposto na última linha — por isso o corte no código é obrigatório.
5. **Linhas vazias:** valor ausente vira texto vazio; o campo continua no ZPL e não imprime nada.
6. **Marcador sem valor:** se sobrar `{{...}}` no resultado, lançar `ErroTemplate`.
7. **Saída:** um bloco `^XA ... ^XZ` por etiqueta. Para várias etiquetas, concatenar os blocos.
8. **Resolução de 300 dpi:** multiplicar por **1,5** todas as coordenadas, tamanhos de fonte, traços, larguras e alturas (`^PW1200`, `^LL1858`); usar módulo 8 no DataMatrix e `^BY4` ou `^BY5` nos códigos de barras (o módulo é inteiro — confira no Labelary se o código do objeto ainda cabe na largura). As imagens `^GFA` **não** escalam sozinhas: gere parciais próprios de 300 dpi a partir dos PNG (redimensionados em 1,5×).

### 8.5 Montagem do DataMatrix

```php
final readonly class DataMatrixCorreios
{
    public function __construct(
        public string $cepDestino,            // 8 dígitos
        public string $cepOrigem,             // 8 dígitos
        public string $codigoObjeto,          // 13 caracteres
        public string $cartaoPostagem,        // até 10 dígitos
        public string $codigoServico,         // até 5 dígitos
        public string $numeroLogradouro = '', // qualquer texto; só os dígitos são usados
        public string $complemento = '',
        public string $servicosAdicionais = '250000000000',   // 12 — valor do sistema antigo
        public string $valorDeclarado = '00000',              // 5
        public string $telefoneDestinatario = '000000000000', // 12
        public string $reservaCliente = '',                   // até 30
    ) {}

    public function gerar(): string
    {
        $numero = substr(preg_replace('/\D/', '', $this->numeroLogradouro) ?? '', 0, 5);
        $complemento = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '',
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $this->complemento) ?: '') ?? '');

        $cadeia =
              $this->cepDestino                                   //  8
            . '00000'                                             //  5
            . $this->cepOrigem                                    //  8
            . '00000'                                             //  5
            . (10 - array_sum(str_split($this->cepDestino)) % 10) % 10   //  1
            . '51'                                                //  2
            . $this->codigoObjeto                                 // 13
            . $this->servicosAdicionais                           // 12
            . str_pad($this->cartaoPostagem, 10, '0', STR_PAD_LEFT)      // 10
            . str_pad($this->codigoServico, 5, '0', STR_PAD_LEFT)        //  5
            . '00'                                                //  2
            . str_pad($numero, 5, '0', STR_PAD_LEFT)              //  5
            . str_pad(substr($complemento, 0, 20), 20)            // 20
            . $this->valorDeclarado                               //  5
            . $this->telefoneDestinatario                         // 12
            . '-00.000000'                                        // 10
            . '-00.000000'                                        // 10
            . '|'                                                 //  1
            . str_pad(substr($this->reservaCliente, 0, 30), 30, '0');    // 30

        if (strlen($cadeia) !== 164) {
            throw new ErroValidacao('A cadeia do DataMatrix deve ter 164 caracteres.');
        }

        return $cadeia;
    }
}
```

O exemplo é um esboço: a versão final valida cada argumento no construtor (CEPs com 8 dígitos, objeto no padrão, cartão e serviço numéricos, campos fixos com o tamanho exato) e lança `ErroValidacao` com o nome do campo. O resultado para os dados da seção 4 deve ser idêntico ao exemplo de lá (conferido com PHP 8.4). A função `imagemParaGfa` da seção 5.3 também foi executada: reconverter o PNG do SEDEX devolve exatamente os bytes do template antigo.

## 9. Testes

| Teste | O que conferir |
|---|---|
| DataMatrix — exemplo da seção 4 | Cadeia idêntica, 164 caracteres, cada campo na posição certa |
| DataMatrix — etiqueta real | Com os dados de `etiqueta_exemplo.zpl`, a cadeia gerada é idêntica à da etiqueta antiga |
| Saída | ZPL em UTF-8 sem BOM, terminando em `^XZ` |
| DataMatrix — validador do CEP | `01001000` → `8`; CEP cuja soma é múltiplo de 10 → `0` |
| DataMatrix — número do logradouro | `S/N` → `00000`; `123A` → `00123`; `1234567` → `12345` |
| DataMatrix — complemento | Com acento, com mais de 20 caracteres, vazio → sempre 20 posições em ASCII |
| Escape | Valores com `_`, `^`, `~`, `\` e acentos aparecem corretos |
| Corte e quebra | Endereço de 150 caracteres não ultrapassa 2 linhas nem a moldura |
| Marcadores | Template com `{{desconhecido}}` → `ErroTemplate`; parcial ausente → some sem erro |
| Logo por serviço | `03220` → SEDEX; `03298` → PAC; código sem mapeamento → sem logo |
| `^GFA` | Converter um PNG pequeno e comparar com o valor esperado; CRC `:Z64:` confere |
| Snapshot | ZPL completo da etiqueta de exemplo, em 203 e 300 dpi |
| Homologação | Marca "HOMOLOGACAO" só aparece no ambiente de homologação |

Conferência visual: cole o ZPL em `labelary.com/viewer.html` (8 dpmm, 4 × 6,2 polegadas). Dados usados na verificação deste guia: destinatário "Maria Aparecida Souza", Praça da Sé, 100, Apto 12, Sé, `01001-000` São Paulo/SP; remetente "Loja Teste Homologacao Ltda", Avenida Paulista, 1000, Sala 101, Bela Vista, `01310-100` São Paulo/SP; objeto `AB043180865BR`; contrato `9912345678`; cartão `0012345678`; serviço `03220`.

## 10. O que não foi verificado

- **Leitura do DataMatrix e dos códigos de barras por equipamento dos Correios.** A renderização foi conferida no Labelary; a aceitação na agência não foi testada com esta versão.
- **Significado oficial dos campos fixos do DataMatrix** (`ServicosAdicionais`, `ValorDeclarado`, telefone, `IDV`) — dependem do manual de etiquetas dos Correios, que não faz parte desta documentação. A etiqueta real (`etiqueta_exemplo.zpl`) confirma que o sistema antigo enviava esses campos com os valores fixos; ela não mostra como codificar valor declarado ou AR.
- **Medidas oficiais** (tamanho mínimo dos códigos, margens, posição obrigatória de cada elemento) — o layout segue o sistema antigo, não uma norma conferida.
- **Template de 300 dpi** — a regra de escala (seção 8.4) não foi renderizada.
- **Impressão física** em impressora térmica.
- **Uso das marcas** SEDEX, PAC e Correios nos logotipos: as imagens vieram do sistema antigo; confirme com os Correios as regras de uso.
- No template base, os textos da linha `NF / Contrato / Vol` (base em y = 305) ficam logo abaixo dos logos, que terminam em y = 300 (loja) e y = 288 (serviço). Com logos que ocupem toda a altura, confira se não encostam.
