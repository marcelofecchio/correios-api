# Changelog

Todas as mudanças relevantes deste projeto. O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o projeto usa [versionamento semântico](https://semver.org/lang/pt-BR/).

## [Não lançado]

## [0.4.0] - 2026-10-12

ZPL em PDF pelo Labelary, como alternativa ao rótulo oficial dos Correios.

### Adicionado

- ZPL em PDF, alternativa ao rótulo oficial: interface `Etiquetas\ConversorPdf`, implementação `ConversorLabelary`
  (API do Labelary, plano gratuito ou pago com `chaveApi`), resultado `EtiquetaPdf` e atalho
  `EtiquetaZpl::paraPdf()`. Tamanho tirado do `^PW`/`^LL`, densidade da resolução da etiqueta e, opcionalmente,
  várias etiquetas por folha (`TamanhoPagina`, `layout`).
- Interface `ArquivoPdf`, implementada por `EtiquetaPdf` e `Respostas\ArquivoRotulo`.
- Laravel: `ConversorPdf` registrado a partir da seção `labelary` do `config/correios.php`.

## [0.3.0] - 2026-10-12

Segunda rodada de lacunas do MARCELO ENVIOS (12/10/2026), com os resultados da homologação do app.

### Adicionado

- Catálogo oficial dos eventos de rastreamento (`Rastreamento\CatalogoEventos`, 376 eventos do webhook `wh-rastro`),
  com descrição e situação de cada um. Fonte em `doc/referencia/correios-eventos-rastreio.json` e tabela comentada em
  `doc/referencia/correios-eventos-rastreio.md`.
- `SituacaoRastreamento::AguardandoPostagem` (`FC-82` "Etiqueta emitida", coleta não feita), `EmDevolucao` e
  `emPosseDosCorreios()`.
- Correção `EST` ("Favor desconsiderar a informação anterior"): `Rastreamento::ultimoEventoValido()`,
  `eventosRecentesPrimeiro()` e `EventoRastreamento::anulaAnterior()`.
- PPN-291 (rótulo ainda em geração): `ErroRotuloEmGeracao`, com `idRecibo()`.
- `rotulo()->obter($token, $solicitacao, $recibo = null): TentativaRotulo`, com `SituacaoRotulo` (`Pronto`,
  `EmGeracao`, `Pendente`): reaproveita o recibo no PPN-291 e descarta no PPN-288.
- `CategoriaServicoAdicional::AvisoRecebimentoEletronico` (`EAR`) e `AvisoRecebimentoDigital` (`ARDIG`).
- Opção `logarCorpo` na `Configuracao` (`log_corpo`, `CORREIOS_LOG_CORPO`): sem corpo no log.
- Guzzle 8 no `require-dev` e na matriz do CI (Laravel 13 com Guzzle 7 e 8).

### Alterado

- `SituacaoRastreamento::doEvento()` usa o catálogo oficial no lugar da tabela de 4 eventos do manual. Eventos que
  antes eram `Desconhecida` agora têm situação (ex.: `BDE-20` é `Problema`).
- `CategoriaServicoAdicional::de()` dá prioridade à sigla exata; o AR convencional (001) não casa mais com o
  eletrônico (021) e o digital (037). A sigla `AP` (095) passou a ser reconhecida, e o valor do caso
  `ArtigosPerigosos` mudou de `DGR` para `AP`.
- O log mascara nome, endereço (menos CEP, cidade e UF), telefones e e-mail de remetente, destinatário e recebedor,
  além de coordenadas, comentários e `obs`.
- `tentarObter()` devolve `null` também no PPN-291, em vez de lançar `ErroApi`.
- README e documentos de referência 09 e 10 registram o observado em homologação: PPN-291, `observacao` impressa no
  rótulo PDF, rótulo `PADRAO` e DACE completa obtidos, adicionais `002` MP e `095` AP, `FC-82` e "Objeto não
  pertence ao contrato".

### Obsoleto

- `rotulo()->tentarObter()`: use `obter()`.

### Removido

- Laravel 11 da matriz do CI e do `require-dev` (`illuminate/support ^12.0 || ^13.0`, `orchestra/testbench ^10.0 ||
  ^11.0`). O Composer recusa instalar qualquer versão do Laravel 11 por alertas de segurança sem correção, e o
  suporte de segurança dessa versão terminou. O `require` de produção continua sem restringir o Laravel.

## [0.2.0] - 2026-10-11

Respostas às lacunas levantadas na construção do primeiro app que usa o componente (11/10/2026).

### Adicionado

- `rotulo()->tentarObter()`: solicita e baixa o rótulo numa tentativa e devolve `null` em PPN-288 (pré-postagem
  pendente). Substitui a consulta de status que não existe para a pré-postagem síncrona. Sem espera nem repetição.
- `FabricaCorreios`: cria um `Correios` por configuração com o mesmo cliente HTTP e logger, para credenciais vindas
  do banco ou de mais de um ambiente. No Laravel é registrada como singleton com o logger da aplicação.
- `CategoriaServicoAdicional` (AR, mão própria, valor declarado, artigos perigosos), identificada pela sigla e pelo
  nome que a API Preço devolve; `ServicoAdicionalDisponivel::categoria()`, `CategoriaServicoAdicional::localizar()`,
  `ServicoAdicional::de()` e `ServicoAdicional::maoPropria()`.
- `SituacaoRastreamento` e `situacao()` em `Rastreamento` e `EventoRastreamento`, com a tabela padrão restrita aos
  eventos do manual (`PO-01`, `RO-01`, `OEC-03`, `BDE-01`) e um mapa da aplicação com prioridade.
- `PrePostagemCriada::$objetoPerigoso` (campo `ehObjetoDgr` da resposta).
- `NovaPrePostagem::MAXIMO_OBSERVACAO` (50) e `MAXIMO_PEDIDO_EXTERNO` (25).
- Testes de integração 04b (categorias dos adicionais de SEDEX e PAC) e 11 (rótulo `Linear100x150` com observação),
  que gravam o resultado em `build/homologacao/`.

### Alterado

- Compatível com Laravel 13: `illuminate/support ^13.0` e `orchestra/testbench ^11.0` no `require-dev` e matriz
  Laravel 11, 12 e 13 no CI. A suíte passou com Laravel 13.35 e Testbench 11.3.
- O ServiceProvider cria o singleton `Correios` pela `FabricaCorreios`.

## [0.1.0] - 2026-10-04

Primeira versão.

### Adicionado

- Configuração (`Configuracao`, `Configuracao::deArray()`), enums, hierarquia de exceções e camada HTTP sobre PSR-18,
  com leitura dos dois formatos de erro da API e log PSR-3 com dados sensíveis mascarados.
- Serviços: Token (por cartão de postagem), Meu Contrato, CEP, Preço (unitário, lote e adicionais), Prazo (unitário,
  lote e data prevista), Pré-postagem (síncrona, assíncrona, logística reversa, cancelamentos e dados da postagem),
  Rótulo oficial em PDF, DCe/DACE e Rastro.
- Objeto de valor `Token` com `estaValido()`, `paraArray()` e `deArray()`.
- Etiquetas em ZPL: etiqueta de postagem 10x15 e DANFE simplificado, em 203 e 300 dpi; `DataMatrixCorreios`,
  `ImagemZpl`, `RenderizadorZpl` e `juntar()`.
- Integração opcional com Laravel (ServiceProvider com auto-discovery, config publicável e Facade).
- Exemplos em `exemplos/` e testes Pest (unitários, HTTP simulado, Laravel e integração opcional com a homologação).

### Divergências registradas (a documentação/observação vence o prompt)

- **Status da pré-postagem síncrona:** o prompt e o arquivo 06 indicam que a criação síncrona devolve o status 2.
  Em homologação (04/10/2026) ela devolveu o status **7 (PENDENTE)**. O componente não presume o status: use
  `podeImprimirRotulo()`.
- **PPN-288:** a documentação não informa o código HTTP. Em homologação ele veio com **HTTP 200** e corpo
  `{"mensagem": "PPN-288: ..."}`, sem `dados`. O componente converte em `ErroPrePostagemPendente`.
- **Resultado do cancelamento em lote:** declarado como `object`; em homologação veio uma **lista** de itens
  (`IdPrePostagem`, `data`, `idRecibo`, `mensagem`).
- **Cancelamento síncrono:** a resposta observada trouxe só `resultadoCancelamento`, sem `mensagem` e `idRecibo`.
- **Código de erro:** além de `LETRAS-NÚMEROS`, a API usa códigos como `PPN-145-5`; o componente reconhece os dois.
- **Etiqueta de declaração de conteúdo em ZPL (prompt 4.7.7):** adiada por decisão do usuário (sem texto legal e
  layout aprovados). A alternativa oficial (DCe + DACE) está disponível.
- **Template de postagem:** o campo cidade/UF do destinatário foi movido de x=200 para x=212 para não encostar no CEP
  em 300 dpi (conferido no Labelary).

[Não lançado]: https://github.com/marcelofecchio/correios-api/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/marcelofecchio/correios-api/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/marcelofecchio/correios-api/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/marcelofecchio/correios-api/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/marcelofecchio/correios-api/releases/tag/v0.1.0
