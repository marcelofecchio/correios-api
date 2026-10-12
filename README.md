# marcelofecchio/correios-api

[![Packagist](https://img.shields.io/packagist/v/marcelofecchio/correios-api.svg)](https://packagist.org/packages/marcelofecchio/correios-api)
[![CI](https://github.com/marcelofecchio/correios-api/actions/workflows/ci.yml/badge.svg)](https://github.com/marcelofecchio/correios-api/actions/workflows/ci.yml)
[![Licença](https://img.shields.io/badge/licen%C3%A7a-MIT-blue.svg)](LICENSE)

Componente PHP para a **API REST dos Correios (CWS – Correios Web Services)** e para gerar **etiquetas em ZPL**
(etiqueta de postagem 10x15 e DANFE simplificado) a partir de templates.

- **Sem estado:** não guarda token, sessão nem cache. Toda chamada autenticada recebe o token por parâmetro.
- **Configurável pela aplicação:** credenciais, ambiente e códigos de serviço vêm sempre de quem usa o componente.
- **Independente de framework**, com integração **opcional** para Laravel.
- **Tipado e testado:** PHP 8.4, PHPStan no nível máximo, testes Pest.

## Sumário

1. [Requisitos](#requisitos) · 2. [Instalação](#instala%C3%A7%C3%A3o) · 3. [Credenciais e ambientes](#credenciais-e-ambientes) ·
4. [Configuração](#configura%C3%A7%C3%A3o) · 5. [Boas práticas com o token](#boas-pr%C3%A1ticas-com-o-token) ·
6. [Fluxo completo de envio](#fluxo-completo-de-envio) · 7. [Módulos](#m%C3%B3dulos) ·
8. [Pré-postagem assíncrona e rótulo](#pr%C3%A9-postagem-ass%C3%ADncrona-e-r%C3%B3tulo) · 9. [Serviços adicionais](#servi%C3%A7os-adicionais) ·
10. [Etiquetas](#etiquetas) · 11. [Erros](#tratamento-de-erros) · 12. [Limitações conhecidas](#limita%C3%A7%C3%B5es-conhecidas) ·
13. [Testes](#testes) · 14. [Contribuição e versões](#contribui%C3%A7%C3%A3o-versionamento-e-licen%C3%A7a)

## Requisitos

- PHP **8.4** com as extensões `dom`, `json`, `libxml` e `mbstring`.
- Um cliente HTTP **PSR-18** e fábricas **PSR-17** (recomendado: Guzzle 7 ou 8). Sem nenhum instalado, o componente
  avisa com `ErroConfiguracao`.
- Opcional: `ext-gd` e `ext-zlib` só para converter imagens em `^GFA` (`Etiquetas\ImagemZpl`).
- Opcional: Laravel 12 ou 13 para o ServiceProvider e a Facade.

## Instalação

```bash
composer require marcelofecchio/correios-api guzzlehttp/guzzle
```

## Credenciais e ambientes

| Dado | Onde obter |
|---|---|
| **Usuário** | **Homologação: o CNPJ** com 14 dígitos, sem pontuação. **Produção: o login do Meu Correios.** |
| **Código de acesso** | Portal CWS → menu → gestão de acesso a componentes. **Cada ambiente tem o seu**: `cwshom.correios.com.br` (homologação) e `cws.correios.com.br` (produção). É ele que vai no Basic Auth, não a senha do Meu Correios. |
| **Cartão de postagem** | Contrato com os Correios. Guarde como texto (tem zeros à esquerda). |
| **Contrato e DR** | Opcionais: o token traz os dois. |
| **CNPJ** | CNPJ do contrato. |

| Ambiente | Host das APIs | Portal |
|---|---|---|
| Homologação | `https://apihom.correios.com.br` | `https://cwshom.correios.com.br` |
| Produção | `https://api.correios.com.br` | `https://cws.correios.com.br` |

> ⚠ Em **homologação** o usuário é o **CNPJ**. HTTP 401 na geração do token quase sempre significa login no lugar
> do CNPJ, CNPJ com pontuação ou código de acesso do outro ambiente. Credenciais, tokens, ids e códigos de objeto de
> um ambiente **não valem** no outro.

## Configuração

### PHP puro

```php
use MarceloFecchio\Correios\Configuracao;
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Dto\Endereco;
use MarceloFecchio\Correios\Dto\Pessoa;
use MarceloFecchio\Correios\Enums\Ambiente;

$configuracao = new Configuracao(
    ambiente: Ambiente::Homologacao,
    usuario: getenv('CORREIOS_USUARIO'),             // homologação: o CNPJ
    codigoAcesso: getenv('CORREIOS_CODIGO_ACESSO'),
    cnpj: getenv('CORREIOS_CNPJ'),
    cartaoPostagem: getenv('CORREIOS_CARTAO_POSTAGEM'),
    servicos: ['sedex' => '03220', 'pac' => '03298', 'sedex_reverso' => '03247', 'pac_reverso' => '03301'],
    remetentePadrao: new Pessoa(
        nome: 'Minha Loja Ltda',
        endereco: new Endereco('01310-100', 'Avenida Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP', 'Sala 101'),
        documento: '11.222.333/0001-81',
        email: 'contato@minhaloja.com.br',
    ),
);

$correios = new Correios(configuracao: $configuracao);
```

Os códigos de serviço **variam por contrato**: consulte os seus com `$correios->contrato()->listarTodosServicos($token)`.
Os métodos aceitam o apelido (`'sedex'`) ou o código (`'03220'`).

Também é possível montar a configuração a partir de um array (chaves em `snake_case` ou `camelCase`), com mensagens
claras para chaves ausentes ou inválidas:

```php
$configuracao = Configuracao::deArray(require 'config/correios.php');
```

Opções: `ambiente`, `usuario`, `codigo_acesso`, `cnpj`, `cartao_postagem`, `contrato`, `diretoria_regional`,
`servicos`, `remetente_padrao`, `etiquetas` (ver [Etiquetas](#etiquetas)), `timeout` (segundos) e `url_base`
(sobrescreve o host, útil em testes).

Cliente HTTP, fábricas e logger podem ser injetados:

```php
$correios = new Correios(
    configuracao: $configuracao,
    clienteHttp: $clientePsr18,       // opcional: Guzzle (se instalado) ou descoberta automática
    fabricaRequisicao: $fabricaPsr17, // opcional
    fabricaStream: $fabricaStream,    // opcional
    logger: $loggerPsr3,              // opcional: token, credenciais, documentos e Base64 são mascarados
);
```

O `timeout` é aplicado quando o componente cria o cliente Guzzle. Com um cliente injetado, configure o timeout nele.

### Laravel

O ServiceProvider é registrado por auto-discovery. Publique a configuração:

```bash
php artisan vendor:publish --tag=correios-config
```

`.env`:

```dotenv
CORREIOS_AMBIENTE=homologacao
# Em homologação o usuário é o CNPJ (14 dígitos)
CORREIOS_USUARIO=11222333000181
CORREIOS_CODIGO_ACESSO=...
CORREIOS_CNPJ=11222333000181
CORREIOS_CARTAO_POSTAGEM=0012345678
CORREIOS_CONTRATO=
CORREIOS_DR=
CORREIOS_SERVICO_SEDEX=03220
CORREIOS_SERVICO_PAC=03298
```

Use a injeção de dependência (`Correios` é singleton) ou a Facade:

```php
use MarceloFecchio\Correios\Laravel\Facades\Correios;

$rastreamento = Correios::rastro()->rastrear($token, 'AB043180865BR');
```

**Credenciais no banco ou mais de um ambiente:** o singleton `Correios` usa o `.env`. Quando as credenciais vêm do
banco (uma por ambiente, ou cada envio no ambiente em que foi criado), use a `FabricaCorreios`, registrada com o
logger da aplicação. O singleton do `.env` é preguiçoso: sem `.env`, nada falha se ele não for usado.

```php
use MarceloFecchio\Correios\FabricaCorreios;

$correios = app(FabricaCorreios::class)->deArray([
    'ambiente' => $credencial->ambiente,                // "homologacao" ou "producao"
    'usuario' => $credencial->usuario,
    'codigo_acesso' => $credencial->codigo_acesso,
    'cnpj' => $credencial->cnpj,
    'cartao_postagem' => $credencial->cartao_postagem,
]);
```

Fora do Laravel, `new FabricaCorreios(clienteHttp: ..., logger: ...)` faz o mesmo.

## Boas práticas com o token

O componente **não guarda o token**: essa responsabilidade é da aplicação.

- Ao gerar o token, **grave-o na sessão** com `Token::paraArray()`.
- **Antes de gerar outro, verifique se já existe um válido** com `Token::deArray(...)->estaValido()`, e só gere um
  novo se não houver ou se estiver perto de expirar.
- Motivo: a API Token aceita no máximo **3 requisições por segundo** (acima disso, HTTP 429 →
  `ErroLimiteRequisicoes`) e devolve o **mesmo** token até os 30 minutos finais da validade (normalmente 24 h).

```php
use MarceloFecchio\Correios\Correios;
use MarceloFecchio\Correios\Token;

function obterToken(Correios $correios): Token
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($_SESSION['correios_token'])) {
        $token = Token::deArray($_SESSION['correios_token']);
        if ($token->estaValido(margemSegundos: 300)) {   // 5 minutos de margem
            return $token;
        }
    }

    $token = $correios->token()->gerar();
    $_SESSION['correios_token'] = $token->paraArray();

    return $token;
}

$preco = $correios->preco()->calcular(token: obterToken($correios), consulta: $consulta);
```

Processos sem sessão (filas, jobs, CLI) devem usar um **cache compartilhado** (PSR-16 ou o Cache do Laravel), com
**trava** para que vários processos não renovem o token ao mesmo tempo. Veja `exemplos/laravel/EnvioCorreiosService.php`.

O `Token` também expõe `cartaoPostagem`, `contrato`, `diretoriaRegional`, `cnpj`, `apis` (ids das APIs liberadas) e
`permiteApi($id)`. O valor do token não aparece em `var_dump()`.

## Fluxo completo de envio

```
token → serviços do cartão → CEP → preço e prazo → pré-postagem → rótulo → postagem na agência → rastreamento
```

```php
$token = obterToken($correios);

$servicos = $correios->contrato()->listarTodosServicos($token);              // códigos habilitados
$endereco = $correios->cep()->consultar($token, '01001-000')->paraEndereco('100', 'Apto 12');

$volume = new Volume(pesoGramas: 800, formato: FormatoObjeto::CaixaPacote, comprimento: 30, largura: 20, altura: 10);
$preco = $correios->preco()->calcular($token, new ConsultaPreco('sedex', '01310100', $endereco->cep, $volume));
$prazo = $correios->prazo()->calcular($token, new ConsultaPrazo('sedex', '01310100', $endereco->cep));

$nova = new NovaPrePostagem(
    destinatario: new Pessoa('Maria Aparecida Souza', $endereco, '529.982.247-25', 'maria@exemplo.com'),
    servico: 'sedex',
    volume: $volume,
    itens: [Item::comValor('Teclado para notebook', 1, '250,00')],
    chaveNFe: $chaveDaNfe,                                                       // ou numeroNotaFiscal, ou só os itens
);
$criada = $correios->prePostagem()->criar($token, $nova);

// uma tentativa de obter o rótulo; se não vier, tente de novo mais tarde (ver "Pré-postagem assíncrona e rótulo")
$tentativa = $correios->rotulo()->obter($token, SolicitacaoRotulo::porIds([$criada->id]));
$tentativa->arquivo?->salvar('/tmp/rotulo.pdf');
// ou a etiqueta em ZPL gerada pelo componente:
$zpl = $correios->etiquetas()->postagemDePrePostagem($nova, $criada);

// depois da postagem física na agência:
$rastreamento = $correios->rastro()->rastrear($token, $criada->codigoObjeto);
$situacao = $rastreamento->situacao();                                           // SituacaoRastreamento::Entregue...
$dados = $correios->prePostagem()->dadosPostagem($token, $criada->codigoObjeto);  // valor e peso tarifados
```

### Logística reversa

O **remetente é o cliente que devolve** (com e-mail, obrigatório) e o **destinatário é a loja**:

```php
$reversa = $correios->prePostagem()->criar($token, new NovaPrePostagem(
    destinatario: $loja,
    servico: 'sedex_reverso',
    volume: $volume,
    remetente: $cliente,                                // precisa ter e-mail
    itens: [new Item('Teclado para notebook', 1, 25000)],
    logisticaReversa: true,
    dataValidadeLogReversa: new DateTimeImmutable('+10 days'),
));
```

## Módulos

Todos os métodos autenticados recebem o token como primeiro argumento (`Token` ou o texto do token) e devolvem
objetos tipados. Cada resposta expõe `respostaBruta()` com o JSON original. Valores em dinheiro são `int` em
**centavos** (nunca `float`); use `Utilitarios\Dinheiro` para converter.

| Módulo | Métodos |
|---|---|
| `token()` | `gerar(): Token` |
| `contrato()` | `listarServicos($token, $pagina = 0, $tamanho = 50)`, `listarTodosServicos($token)`, `consultarServico($token, 'sedex')` |
| `cep()` | `consultar($token, '01001-000'): EnderecoCep` |
| `preco()` | `calcular($token, ConsultaPreco)`, `calcularLote($token, [ConsultaPreco, ...])` (até 5), `servicosAdicionais($token, 'sedex')` |
| `prazo()` | `calcular($token, ConsultaPrazo)`, `calcularLote($token, [...])`, `dataPrevista($token, ConsultaDataPrevista)` |
| `prePostagem()` | `criar`, `criarAssincrona` (devolve o recibo), `consultarAssincrona`, `cancelar`, `cancelarPorCodigoObjeto`, `cancelarLote`, `consultarCancelamentoLote`, `dadosPostagem` |
| `rotulo()` | `solicitar($token, SolicitacaoRotulo): ReciboRotulo`, `baixar($token, $recibo): ArquivoRotulo`, `obter($token, SolicitacaoRotulo, $recibo = null): TentativaRotulo` |
| `dce()` | `imprimirDace($token, SolicitacaoDace): Dace`, `atualizarDocumentoFiscal($token, $codigoObjeto, $chave, $numeroNota)` |
| `rastro()` | `rastrear($token, $codigo)` (todos os eventos), `rastrearVarios($token, [...])` (até 50, último evento) |
| `etiquetas()` | ZPL sem chamada à API — ver [Etiquetas](#etiquetas) |

Exemplos executáveis de cada módulo em [`exemplos/`](exemplos) (credenciais por variáveis de ambiente).

**Lotes de Preço e Prazo:** HTTP 206 indica que parte dos itens falhou. `ResultadoLote` traz cada item com
`sucesso()`/`erro()`, correlacionado pelo `nuRequisicao` (numerado a partir de 1 quando não informado):

```php
$lote = $correios->preco()->calcularLote($token, [$consultaSedex, $consultaPac]);
foreach ($lote->itens as $item) {
    echo $item->nuRequisicao, ': ', $item->sucesso() ? $item->resultado->precoFinalCentavos : $item->erro();
}
```

**Paginação (Meu Contrato):** `listarTodosServicos()` percorre as páginas (a primeira é 0) e aceita `itens` como
lista ou objeto único.

**Observação da pré-postagem:** no máximo **50 caracteres** (`NovaPrePostagem::MAXIMO_OBSERVACAO`, limite do
schema). Ela **sai impressa no rótulo PDF** dos Correios (confirmado em homologação em 11/10/2026, layout `PADRAO`).
A etiqueta ZPL do componente aceita observações mais longas (até 110).

**Situação do rastreamento:** `$rastreamento->situacao()` e `$evento->situacao()` devolvem `SituacaoRastreamento`
(`AguardandoPostagem`, `Postado`, `EmTransito`, `SaiuParaEntrega`, `AguardandoRetirada`, `Entregue`, `EmDevolucao`,
`Devolvido`, `Problema`, `Cancelado` ou `Desconhecida`). A classificação vem do **catálogo oficial de eventos**
(`Rastreamento\CatalogoEventos`, 376 eventos do webhook de rastreamento), com a tabela comentada em
[`doc/referencia/correios-eventos-rastreio.md`](doc/referencia/correios-eventos-rastreio.md). O tipo muda o
significado do código (`BDE-1` é entrega; `BDE-20`, carteiro não atendido), por isso o catálogo usa os dois.

- `FC-82` ("Etiqueta emitida", visto em homologação) é `AguardandoPostagem`: o objeto ainda não foi à agência.
- `EST` ("Favor desconsiderar a informação anterior") anula o evento anterior: `situacao()` usa
  `ultimoEventoValido()`, que pula o `EST` e o evento que ele anula. `ultimoEvento()` continua devolvendo o mais
  recente, seja qual for.
- Evento fora do catálogo ou sem descrição nele (`FC-66`, `RO-2`...) é `Desconhecida`; a `descricao` da API continua
  disponível. `CatalogoEventos::descricao('BDE', '01')` devolve a descrição oficial.
- O mapa da aplicação tem prioridade sobre o catálogo (`'CODIGO-TIPO'` ou `'CODIGO'`):

```php
$mapa = ['BDE-20' => SituacaoRastreamento::EmTransito];   // ex.: tratar "carteiro não atendido" como andamento
$situacao = $rastreamento->situacao($mapa);
if ($situacao->finalizada()) { /* entregue, devolvido ou cancelado: pare de consultar */ }
```

**DCe e DACE:** sem nota fiscal, crie a pré-postagem com os itens e `emiteDCe: true` e imprima a DACE:
`TipoDace::Completa`/`Resumida` devolvem PDF (`conteudoBinario()`); `TipoDace::Termica` devolve texto para a
impressora (`texto()`). Com NF-e emitida depois, use `atualizarDocumentoFiscal()`.

## Pré-postagem assíncrona e rótulo

| `statusAtual` | Significado |
|:-:|---|
| 1 | PREATENDIDO |
| **2** | **PREPOSTADO — o único que permite imprimir o rótulo** |
| 3 | POSTADO |
| 4 | EXPIRADO (não postado até o `prazoPostagem`; padrão: criação + 14 dias, máximo 90) |
| 5 | CANCELADO |
| 6 | ESTORNADO |
| 7 | PENDENTE (transitório) |

- Na via assíncrona, `situacao = PROCESSADO` **não basta**: a pré-postagem fica alguns minutos no status **7** antes
  do **2**. Em homologação (04/10/2026) isso aconteceu também na criação **síncrona**.
- O download do rótulo pode responder, com **HTTP 200**, um de dois códigos:

| Código | Exceção em `baixar()` | O que significa | Próxima tentativa |
|---|---|---|---|
| **PPN-291** | `ErroRotuloEmGeracao` | O rótulo ainda está sendo gerado (comum logo depois da solicitação) | **Mesmo recibo**, alguns segundos depois |
| **PPN-288** | `ErroPrePostagemPendente` | A pré-postagem estava PENDENTE (status 7) | **Recibo novo**: este nunca volta a funcionar |

- **`rotulo()->obter()`** faz uma tentativa (solicita, se não receber um recibo, e baixa) e devolve uma
  `TentativaRotulo`: `Pronto` (com o PDF), `EmGeracao` (guarde o recibo) ou `Pendente` (descarte o recibo). Serve
  também para a pré-postagem síncrona, que não tem consulta de status por id: o próprio download diz se ela ainda
  está pendente. O código do objeto não muda entre as solicitações.

```php
// job da aplicação (ex.: Laravel), reagendado com intervalo crescente
$tentativa = $correios->rotulo()->obter($token, SolicitacaoRotulo::porIds([$envio->prepostagem_id]), $envio->recibo_rotulo);
if (!$tentativa->pronto()) {
    $envio->update(['recibo_rotulo' => $tentativa->reciboParaProximaTentativa()?->idRecibo]);  // PPN-291 guarda; PPN-288 limpa
    $this->release([5, 10, 30, 60, 120][$this->attempts() - 1] ?? 120);
    return;
}
$tentativa->arquivo->salvar($caminho);
```

- `tentarObter()` (0.2.0) está obsoleto: pede um recibo novo a cada chamada e devolve `null` nos dois casos.
- Para ter o `statusAtual` antes de pedir o rótulo, use a via assíncrona (`consultarAssincrona()`).
- O componente **não faz espera nem repetição**. A rotina abaixo é da aplicação (intervalo crescente e limite):

```php
$recibo = $correios->prePostagem()->criarAssincrona($token, $nova);

foreach ([5, 10, 30, 60, 120] as $segundos) {           // na aplicação real: um job agendado, não sleep()
    sleep($segundos);
    $processamento = $correios->prePostagem()->consultarAssincrona($token, $recibo);
    if ($processamento->situacao === SituacaoProcessamento::Falha) {
        throw new RuntimeException(implode(' ', $processamento->todasAsMensagens()));
    }
    if ($processamento->prontaParaRotulo()) {             // PROCESSADO e status 2
        $id = $processamento->prePostagens[0]->id;
        try {
            $pdf = $correios->rotulo()->baixar($token, $correios->rotulo()->solicitar($token, SolicitacaoRotulo::porIds([$id])));
        } catch (ErroPrePostagemPendente | ErroRotuloEmGeracao) {
            continue;                                     // ver a tabela acima; obter() separa os dois casos
        }
        break;
    }
}
```

- Um recibo com várias pré-postagens devolve **um único PDF** A4 com as etiquetas lado a lado. Repetir a solicitação
  gera novo recibo, mas mantém o código do objeto. O rótulo só pode ser emitido com as **mesmas credenciais** que
  criaram a pré-postagem.
- Para impressora térmica, `LayoutImpressao::Linear100x150` ou `Linear100x80` (não testados em homologação; o
  `PADRAO` e a DACE completa foram obtidos em 11/10/2026).

## Serviços adicionais

- Descubra os adicionais aceitos e os **obrigatórios** com `preco()->servicosAdicionais($token, 'sedex')`:
  `obrigatorio()` quando `sgIndicador = O`, `opcional()` quando `P`. A obrigatoriedade pode mudar com a vigência.
- **Cotação × pré-postagem:** na API Preço, o **SEDEX REVERSO (03247)** exige o adicional **016** (sem ele:
  `ERP-049`); no PAC REVERSO ele é opcional. Na **Pré-postagem o 016 não é exigido** — não replique
  automaticamente na pré-postagem os adicionais exigidos na cotação.
- **AR:** emitir o aviso de recebimento exige o adicional **001** na pré-postagem (`ServicoAdicional::avisoRecebimento()`).
- **Valor declarado:** o código muda por serviço (`019` no SEDEX, `064` no PAC nos testes) — informe-o:
  `ServicoAdicional::valorDeclarado('019', 25000)`.
- **Categoria do adicional:** para não fixar códigos, localize o adicional de cada serviço pela categoria
  (`CategoriaServicoAdicional`: `AvisoRecebimento`, `AvisoRecebimentoEletronico`, `AvisoRecebimentoDigital`,
  `MaoPropria`, `ValorDeclarado`, `ArtigosPerigosos`). A sigla exata devolvida pela API tem prioridade (`AR`, `EAR`,
  `ARDIG`, `MP`, `VD`/`VDS`, `AP`); sem ela, vale o nome. Assim o AR convencional (001) não se confunde com o
  eletrônico (021) e o digital (037):

```php
$adicionais = $correios->preco()->servicosAdicionais($token, 'pac');
$vd = CategoriaServicoAdicional::ValorDeclarado->localizar($adicionais);        // 064 VDS no PAC da homologação
$mp = CategoriaServicoAdicional::MaoPropria->localizar($adicionais);            // 002; null se o serviço não aceitar

$lista = array_filter([$vd ? ServicoAdicional::de($vd, 25000) : null, $mp ? ServicoAdicional::de($mp) : null]);
```

- **Mão própria:** o código não está na documentação; em homologação foi `002` (sigla `MP`) em SEDEX, PAC e nos
  reversos. Use a categoria acima ou `ServicoAdicional::maoPropria($codigo)`.
- **Bateria, inflamável, explosivo:** a API Pré-postagem não tem campo para isso. A indicação é o **adicional de
  artigos perigosos**: em homologação, `095` (sigla `AP`, "ARTIGOS PERIGOSOS ANAC") em SEDEX, PAC e nos reversos
  (`CategoriaServicoAdicional::ArtigosPerigosos`). Inclua-o na cotação e na pré-postagem; serviço sem esse adicional
  não aceita o envio. Além dele, a declaração obrigatória `cienteObjetoNaoProibido` (`NovaPrePostagem`, padrão
  `true` = `"1"`, objeto permitido; `false` envia `"0"`, objeto proibido no fluxo postal) e o campo `ehObjetoDgr` da
  resposta, em `PrePostagemCriada::$objetoPerigoso`. As regras de aceitação de artigos restritos (ex.: baterias de
  lítio) não estão na documentação: confirme com os Correios.

## Etiquetas

Dois caminhos para a etiqueta de postagem; a escolha é da aplicação:

| | Rótulo oficial (PDF) | ZPL do componente |
|---|---|---|
| Quem gera | API dos Correios (`rotulo()`) | `etiquetas()`, sem chamada à API |
| Formato | PDF A4 (ou layouts lineares) | Texto ZPL para impressora térmica |
| Requisitos | Status 2, mesmas credenciais da criação | Código do objeto da pré-postagem |

O ZPL também pode virar PDF pelo Labelary ([ZPL em PDF](#zpl-em-pdf-alternativa-ao-r%C3%B3tulo-oficial)), para quem não tem
impressora térmica e não quer depender do rótulo oficial.

### Documentos em ZPL

```php
// 1. Etiqueta de postagem 10x15 cm, logo após criar a pré-postagem
$etiqueta = $correios->etiquetas()->postagemDePrePostagem($nova, $criada, observacoes: 'Pedido 12345');

// 2. DANFE simplificado a partir do XML autorizado da NF-e, com o código de rastreamento
$danfe = $correios->etiquetas()->danfeSimplificada(DadosDanfe::deXml($xmlNfe), codigoEnvio: $criada->codigoObjeto);

// 3. Um único envio à impressora
$tudo = $correios->etiquetas()->juntar($etiqueta, $danfe);
$tudo->salvar('/tmp/pedido-12345.zpl');   // ou (string) $tudo
```

- **Postagem** (`postagem(DadosEtiquetaPostagem)` ou `postagemDePrePostagem(...)`): logo dos Correios, DataMatrix de
  164 caracteres, logo do serviço (pelo código, via configuração), NF/pedido, contrato, volume, peso, siglas dos
  adicionais, código do objeto e CEP em Code 128, destinatário, remetente, observações e, em homologação, a marca
  "HOMOLOGACAO - IMPRESSAO DE TESTE".
- **DANFE Simplificado - Etiqueta** (`danfeSimplificada`): título, tipo de operação, número, série, emissão,
  emitente (nome, CNPJ, IE, UF), chave de acesso em Code 128 subconjunto C e em texto, protocolo de autorização,
  destinatário (nome, CPF/CNPJ, IE, UF, endereço) e, opcionalmente, o código de envio. **Não lista itens nem valores.**
  `DadosDanfe::deXml()` só aceita nota **autorizada** (`cStat` 100 ou 150) e recusa contingência EPEC. Também pode ser
  montado à mão. `DadosDanfe` expõe `chaveAcesso` e `numero` para reutilizar na pré-postagem.
- **Declaração de conteúdo:** ainda não disponível em ZPL nesta versão. Use a **DCe** com impressão da **DACE**
  (`emiteDCe: true` + `dce()->imprimirDace()`), que é o documento oficial dos Correios para envios sem nota.

Saída: **somente ZPL**, UTF-8 sem BOM, de `^XA` a `^XZ` + quebra de linha. Mesma entrada → mesma saída. Confira no
visualizador **Labelary**: <https://labelary.com/viewer.html> (8 dpmm para 203 dpi, 12 dpmm para 300 dpi; 4 × 6,2 pol
para a postagem e 4 × 6 pol para o DANFE).

### Enviar o ZPL à impressora (fora do componente)

```php
// Impressora de rede (porta 9100)
$socket = fsockopen('192.168.0.50', 9100, $erro, $mensagem, 5);
fwrite($socket, (string) $etiqueta);
fclose($socket);
```

Também funciona por compartilhamento de impressora (`copy /b etiqueta.zpl \\servidor\zebra` no Windows,
`lp -d zebra -o raw etiqueta.zpl` no Linux) ou pelo navegador com **Zebra Browser Print** ou **QZ Tray**.

### ZPL em PDF (alternativa ao rótulo oficial)

`ConversorLabelary` converte qualquer `EtiquetaZpl` (postagem, DANFE ou as duas juntas) em PDF pela API do
[Labelary](https://labelary.com/service.html), o mesmo motor do visualizador. Não precisa de token dos Correios nem
de a pré-postagem estar no status 2.

```php
use MarceloFecchio\Correios\Etiquetas\ConversorLabelary;

$etiqueta = $correios->etiquetas()->juntar($postagem, $danfe);
$pdf = $etiqueta->paraPdf(new ConversorLabelary());     // ou (new ConversorLabelary())->converter($etiqueta)
$pdf->salvar('/tmp/pedido-12345.pdf');                   // conteudoBinario(), conteudoBase64(), tamanhoBytes()
```

- **Tamanho:** densidade da etiqueta (203 dpi → 8 dpmm, 300 dpi → 12 dpmm) e tamanho tirado do `^PW`/`^LL` do ZPL
  (10 × 15,5 cm na postagem). Uma etiqueta por página, no tamanho dela. O PDF é vetorial: textos e códigos de barras
  não perdem definição; só os logos `^GFA` são imagem.
- **Folha comum:** `new ConversorLabelary(pagina: TamanhoPagina::A4, layout: '2x1')` põe duas etiquetas por folha A4,
  com borda tracejada para recortar (`borda: false` tira). O Labelary não reduz a etiqueta: `2x2` não cabe no A4 e
  volta como `ErroApi` (HTTP 400).
- **Mesma interface do rótulo oficial:** `EtiquetaPdf` e `ArquivoRotulo` implementam `ArquivoPdf`. Tipe o código da
  aplicação com ela para trocar a origem do PDF sem mexer no resto.
- **Outro serviço:** implemente `Etiquetas\ConversorPdf` (um método, `converter(EtiquetaZpl): EtiquetaPdf`) e injete-o
  no lugar.
- **Laravel:** `app(ConversorPdf::class)` devolve o `ConversorLabelary` montado a partir de `correios.labelary`
  (`CORREIOS_LABELARY_CHAVE_API`, `CORREIOS_LABELARY_URL`, `CORREIOS_LABELARY_PAGINA`, `CORREIOS_LABELARY_LAYOUT`),
  com o logger da aplicação.
- **Erros:** `ErroValidacao` antes da chamada (ZPL vazio, mais de 50 etiquetas ou mais de 1 MB);
  `ErroLimiteRequisicoes` no HTTP 429; `ErroApi` nos demais (a mensagem do Labelary vai em `mensagens`);
  `ErroConexao` na falha de rede.

| Plano do Labelary | Limite | Chave | Retenção zero de dados |
|---|---|---|---|
| Gratuito (`api.labelary.com`) | 3 req/s e 5.000/dia | não | **não** |
| Plus / Business (pagos) | 6 a 10 req/s, 20.000 a 40.000/dia | `chaveApi` + `url` enviados pelo Labelary | sim |

> **LGPD:** o ZPL leva nome, endereço e documento do remetente e do destinatário a um serviço de terceiros. O plano
> gratuito não tem retenção zero de dados e roda no canal Beta. Em produção, prefira um plano pago, a versão
> instalada localmente (On-Premise) ou o rótulo oficial. O log do conversor registra só método, URL, status e duração.

### Resolução

203 dpi (8 pontos/mm) é o padrão; 300 dpi (12 pontos/mm) pela configuração (`etiquetas.resolucao = 300`) ou por
parâmetro (`Resolucao::Dpi300`).

**Escolha:** os templates são escritos uma vez, em 203 dpi, e o componente os **escala por 1,5** para 300 dpi
(coordenadas, fontes, traços, larguras e módulos dos códigos: `^BY3` → `^BY5`, DataMatrix módulo 5 → 8). Assim um
ajuste de layout é feito em um lugar só. Imagens `^GFA` não escalam: o pacote traz parciais de logo próprios em
`resources/templates/300dpi/parciais/`, e a aplicação pode pôr em `300dpi/` um template inteiro de 300 dpi, que então
é usado como está.

### Configuração das etiquetas

```php
new ConfiguracaoEtiquetas(
    resolucao: Resolucao::Dpi203,
    diretorioTemplates: __DIR__ . '/templates-zpl',      // null = templates do pacote
    logoCorreios: true,
    logosPorServico: ['sedex' => 'logo_servico_sedex', 'pac' => 'logo_servico_pac', '03301' => 'logo_servico_pac'],
    nomesServicos: ['sedex' => 'SEDEX', 'pac' => 'PAC'],
    marcaHomologacao: null,                              // null = automático (só em homologação)
    dataMatrixServicosAdicionais: '250000000000',        // campo fixo do DataMatrix (valor do sistema antigo)
    siglasAdicionais: ['019' => 'VD', '016' => 'LRSV'],  // sem entrada: valor declarado → VD, 001 → AR
);
```

Logos incluídos: `logo_correios`, `logo_servico_sedex`, `logo_servico_pac`, `logo_servico_carta` (203 e 300 dpi).
Para gerar outro logo a partir de um arquivo oficial: `ImagemZpl::deArquivo('logo.png', x: 41, y: 56, larguraMaxima: 224, alturaMaxima: 224)`
(requer GD). Não desenhe nem recrie logotipos.

### Templates próprios

Informe `diretorioTemplates`. Arquivo ausente no diretório da aplicação cai no do pacote. Marcadores:

| Sintaxe | Uso |
|---|---|
| `{{nome}}` | valor escapado para campos com `^FH` (`_` → `_5F`, `^` → `_5E`, `~` → `_7E`, `\` → `_5C`, `{` → `_7B`, `}` → `_7D`) |
| `{{{nome}}}` | valor cru, só para dados validados (códigos de barras, DataMatrix) |
| `{{>nome}}` | parcial `parciais/nome.zpl`; some do resultado se não estiver configurado |

Linhas `^FX` são removidas; marcador sem valor lança `ErroTemplate`. Acentuação: `^CI28` (UTF-8).

**`postagem.zpl`** — `{{{datamatrix}}}`, `{{{codigo_objeto}}}`, `{{{destinatario_cep}}}`, `{{linha_nf}}` (16),
`{{contrato}}` (10), `{{volume}}` (7), `{{peso_gramas}}` (6), `{{nome_servico}}` (22), `{{siglas_adicionais}}` (12),
`{{destinatario_nome}}` (50), `{{destinatario_endereco}}` (90), `{{destinatario_bairro}}` (30),
`{{destinatario_cep_hifen}}`, `{{destinatario_cidade_uf}}` (34), `{{remetente_nome}}` (50),
`{{remetente_endereco}}` (90), `{{remetente_bairro}}` (30), `{{remetente_cep_hifen}}`, `{{remetente_cidade_uf}}` (34),
`{{observacoes}}` (110); parciais `{{>logo_correios}}`, `{{>logo_servico}}`, `{{>marca_homologacao}}`.

**`danfe_simplificada.zpl`** — `{{{chave_acesso}}}`, `{{tipo_operacao}}`, `{{numero_nf}}`, `{{serie}}`,
`{{data_emissao}}`, `{{emitente_nome}}` (50), `{{emitente_cnpj}}`, `{{emitente_ie}}` (15), `{{emitente_uf}}`,
`{{emitente_endereco}}` (60), `{{chave_acesso_formatada}}`, `{{linha_protocolo}}` (68), `{{destinatario_nome}}` (50),
`{{destinatario_documento}}` (45), `{{destinatario_uf}}`, `{{destinatario_endereco_linha1}}` (68),
`{{destinatario_endereco_linha2}}` (68), `{{observacoes}}` (180); parcial `{{>danfe_codigo_envio}}` com
`{{{codigo_envio}}}`.

Entre parênteses, o limite em caracteres: o valor é cortado antes do escape.

## Tratamento de erros

```
CorreiosException (interface)
├── ErroValidacao            dados inválidos, antes de chamar a API (com o nome do campo)
├── ErroConfiguracao         configuração ausente ou inválida
├── ErroTemplate             template ZPL inválido ou marcador sem valor
├── ErroConexao              falha de rede ou timeout (getPrevious() = exceção PSR-18)
├── ErroAutenticacao         HTTP 401/403
├── ErroLimiteRequisicoes    HTTP 429
└── ErroApi                  demais erros, inclusive regras de negócio com HTTP 400, 404 ou 422
    ├── ErroPrePostagemPendente   PPN-288 (rótulo pedido com a pré-postagem pendente: peça outro recibo)
    └── ErroRotuloEmGeracao       PPN-291 (rótulo ainda em geração: baixe de novo com o mesmo recibo)
```

`ErroApi`, `ErroAutenticacao` e `ErroLimiteRequisicoes` expõem `statusHttp`, `mensagens`, `codigoErro`
(ex.: `PPN-288`, `ERP-049`, `PPN-145-5`), `causa` e `respostaBruta` (JSON ou o texto bruto, como a página HTML de um
erro 500). Os dois formatos de erro da API são lidos: `{"msgs": [...], "causa": ...}` e `{"mensagem": "PPN-288: ..."}`.

```php
try {
    $preco = $correios->preco()->calcular($token, $consulta);
} catch (ErroValidacao $e) {
    // corrigir o dado: $e->campo
} catch (ErroAutenticacao $e) {
    // gerar outro token ou rever credenciais
} catch (ErroApi $e) {
    if ($e->codigoErro === 'ERP-049') {
        // falta um adicional obrigatório na cotação
    }
    logger()->warning($e->getMessage(), ['codigo' => $e->codigoErro, 'http' => $e->statusHttp]);
} catch (CorreiosException $e) {
    // qualquer outro erro do componente
}
```

Mensagens não incluem token, código de acesso nem senha.

**Log (PSR-3):** cada chamada gera dois registros em DEBUG (requisição e resposta, com método, URL, status e
duração) e um em ERROR na falha de conexão. O corpo vai mascarado: token, Basic Auth e CPF/CNPJ; nome, endereço
(menos CEP, cidade e UF), telefones e e-mail de remetente, destinatário e recebedor; coordenadas e comentários da
entrega; o Base64 de rótulos e DACE é omitido. Para não gravar corpo nenhum, use `logarCorpo: false` na
`Configuracao` (`'log_corpo' => false` no array, `CORREIOS_LOG_CORPO=false` no Laravel).

## Limitações conhecidas

- **ZPL em PDF:** depende de um serviço de terceiros (Labelary), com os limites do plano. A conversão foi conferida
  em 12/10/2026 com dados fictícios (postagem em 203 e 300 dpi, DANFE e folha A4 `2x1`); a leitura dos códigos do
  PDF impresso em impressora comum pelos equipamentos dos Correios não foi testada.

Pontos não testados, instáveis ou observados de forma diferente da especificação:

- **Status 7 também na criação síncrona:** em homologação (04/10/2026) a pré-postagem síncrona voltou como
  PENDENTE. Confira `podeImprimirRotulo()` antes de pedir o rótulo.
- **PPN-288 com HTTP 200:** o download do rótulo pendente responde HTTP 200 com `{"mensagem": "PPN-288: ..."}`. O
  componente trata esse caso como `ErroPrePostagemPendente`.
- **Cancelamento automático em homologação:** pré-postagens pendentes de teste foram canceladas pelos Correios
  minutos depois ("Etiqueta cancelada pelo sistema de captação").
- **Cancelamento:** a resposta observada traz só `resultadoCancelamento` (texto). Cancelar uma pré-postagem já
  cancelada responde `PPN-145-5`. O resultado do cancelamento em lote vem como lista (`ProcessamentoCancelamento::$itens`).
- **`GET .../postada`** (dados da postagem) não foi testado: só responde depois da postagem física. Datas e medidas
  ficam como texto.
- **PPN-291 com HTTP 200:** observado em 11/10/2026 no download feito logo depois da solicitação. O recibo continua
  válido; o componente lança `ErroRotuloEmGeracao`, e `obter()` devolve `EmGeracao`.
- **DACE:** a completa foi obtida em homologação em 11/10/2026 (pelo MARCELO ENVIOS). Com a pré-postagem pendente, a
  API responde PPN-376. O formato do tipo `T` (texto puro ou Base64) não foi confirmado; `Dace::texto()` aceita os
  dois.
- **Layouts de rótulo:** o `PADRAO` foi obtido em homologação (11/10/2026). `Linear100x150`, os demais lineares e
  `tipoRotulo = R` não foram testados; o teste de integração 11 pede o `Linear100x150` e grava o PDF em
  `build/homologacao/`.
- **Endpoints HTML** de declaração de conteúdo e AR falham no servidor de homologação e não fazem parte do componente.
- **Fora do escopo desta versão:** Webhook, autenticação por contrato ou só por usuário, agências, faturas, faixas de
  etiquetas, Pedido de Informação, serviços internacionais, lote de objetos simples, imagens de entrega e AR Digital.
- **DataMatrix:** os campos `ServicosAdicionais` (`250000000000`), `ValorDeclarado`, telefone e `IDV` seguem o
  sistema antigo, sem o manual de etiquetas dos Correios; são configuráveis. A leitura por equipamento dos Correios
  não foi testada.
- **DANFE simplificado:** os requisitos da Nota Técnica 2020.004 vêm de fonte secundária (posição da chave no canto
  superior direito não aplicada); contingência EPEC não é suportada.
- **Etiqueta de declaração de conteúdo em ZPL:** não implementada (falta o texto legal e o layout aprovados).
- **Limites de peso e medidas por serviço** não são validados localmente: a documentação das APIs não traz a tabela
  e a API recusa o que estiver fora do limite (a mensagem chega em `ErroApi`).
- **Situação do rastreamento:** a classificação dos eventos é do componente, não dos Correios (critérios em
  `doc/referencia/correios-eventos-rastreio.md`). Onze eventos do catálogo não têm descrição e ficam como
  `Desconhecida`.
- **Rastreio fora do contrato:** em homologação (12/10/2026), um código de outro contrato voltou sem eventos e com a
  mensagem "Objeto não pertence ao contrato" (não o SRO-020 do manual). Não se sabe se, em produção, a consulta
  alcança objetos de outros remetentes.
- **Códigos dos adicionais:** não documentados. Em homologação (11/10/2026) vieram `001` AR, `021` EAR, `037` ARDIG,
  `002` MP, `019` VD (SEDEX), `064` VDS (PAC) e `095` AP; use `CategoriaServicoAdicional` para não fixá-los.
- **Certificados no Windows:** se o PHP não tiver um pacote de autoridades certificadoras (`curl.cainfo` vazio), as
  chamadas falham com "self-signed certificate in certificate chain". Configure `curl.cainfo` no `php.ini`.

## Testes

```bash
composer test               # Pest (unitários, HTTP simulado e Laravel)
composer test:cobertura     # com cobertura (PCOV ou Xdebug), mínimo de 90%
composer analise            # PHPStan, nível máximo
composer estilo             # PHP-CS-Fixer (PER-CS 2.0), sem alterar arquivos
composer qualidade          # tudo acima
```

Os snapshots de ZPL ficam em `tests/Fixtures/zpl`. Depois de mudar um template de propósito, regere-os com
`ATUALIZAR_SNAPSHOTS=1 vendor/bin/pest` e confira o resultado no Labelary.

**Integração com a homologação** (grupo `integracao`, pulado por padrão):

```bash
export CORREIOS_HOM_USUARIO=<CNPJ> CORREIOS_HOM_CODIGO_ACESSO=... CORREIOS_HOM_CNPJ=<CNPJ> \
       CORREIOS_HOM_CARTAO=... CORREIOS_HOM_CONTRATO=...
vendor/bin/pest --group=integracao
```

Os testes geram **um** token por execução, criam e cancelam pré-postagens de teste e não chamam `.../postada` nem os
endpoints HTML. Nunca grave credenciais no repositório.

## Contribuição, versionamento e licença

- Abra uma issue ou um pull request. Antes, rode `composer qualidade`.
- Versionamento semântico; mudanças registradas no [CHANGELOG](CHANGELOG.md).
- Licença [MIT](LICENSE).
