# Prumo — marca

> Arquivos: `tokens.css` (tokens claro/escuro), `logo.svg` (símbolo + nome), `logo-mark.svg` (só símbolo), `favicon.svg`.

## 1. Nome

**Prumo**: o fio com peso que o profissional usa para conferir se algo está reto. "Estar no prumo" é estar em ordem, certo, confiável. É o que o dono de oficina quer da própria oficina: OS em dia, orçamento claro, cliente que confia.

- Curto (5 letras), fácil de falar no balcão e de digitar no celular, sem anglicismo.
- Palavra de ofício, não de startup. O símbolo (o peso do prumo) é geométrico e funciona em 16 px.

**Checagem de colisão (WebSearch, out/2026):** buscas por "Prumo" + sistema/software para oficina mecânica não retornaram produto com esse nome no segmento. Existe a **Prumo Logística** (portos, setor muito diferente) e o nome é comum em empresas de construção. Antes de lançar: buscar no INPI (classes 9 e 42), checar domínio (`prumo.app`, `useprumo.com.br`, `prumooficina.com.br`) e @ nas redes. "Bancada" foi a alternativa avaliada (também sem colisão encontrada), descartada por ser genérica demais e ter sentido político ("bancada ruralista").

## 2. Tagline e posicionamento

- **Tagline:** "Sua oficina no prumo."
- **Linha de apoio:** "Orçamento no celular. Aprovação com assinatura no WhatsApp. OS na hora."
- **Posicionamento:** o sistema de OS e orçamento feito para a oficina pequena, que o técnico usa na bancada e o cliente aprova pelo WhatsApp, sem treinamento.

## 3. Personalidade e voz

Personalidade: **de ofício, direta, confiável, calma.** Um bom chefe de oficina: fala pouco, fala certo.

| Faça | Não faça |
|---|---|
| "OS 1042 aberta." | "Sua ordem de serviço foi criada com sucesso! 🎉" |
| "Cliente assinou o orçamento." | "Uhuul, mais um orçamento aprovado!" |
| "Enviar pelo WhatsApp" | "Compartilhar experiência com seu cliente" |
| "Sem conexão. Salvamos aqui e enviamos quando voltar." | "Ops! Algo deu errado :(" |
| "Placa", "Km", "Peça", "Mão de obra" | "Ativo", "Odômetro", "Insumo", "Recurso humano" |
| Verbo no infinitivo nos botões: "Salvar", "Enviar" | "Clique aqui", "Vamos lá!" |

Regras: português do Brasil, frases curtas, sem exclamação (no máximo uma, em sucesso raro), sem emoji, sem "potencialize/transforme/jornada/revolucione". Trate o usuário por "você". Números sempre com unidade: "R$ 1.250,00", "84.300 km".

## 4. Logo

- **Símbolo:** o peso do prumo (fio, tampa e corpo em V) em branco sobre quadrado verde-petróleo com cantos de 7/32; ponta em laranja-sinalização.
- **Logotipo:** "prumo" em minúsculas, desenhado em traço monolinear (sem fonte), cor `#1b1f1d`.
- **Arquivos:** `logo.svg` (horizontal, 150×40), `logo-mark.svg` (32×32, legível em 16 px), `favicon.svg`.
- **Área de proteção:** 1/4 da altura do símbolo em volta de tudo. **Tamanho mínimo:** logotipo com 96 px de largura; abaixo disso, só o símbolo.
- **Fundo escuro:** use o símbolo sozinho, ou o logo com o traço do nome em `#ebece8` (inline o SVG e troque o `stroke`).
- **Proibido:** esticar, girar, aplicar sombra/brilho/gradiente, trocar as cores do símbolo, colocar o símbolo sobre foto sem a caixa verde, recriar o nome com uma fonte.

## 5. Cores

Verde-petróleo de marca (lembra pintura industrial, macacão, bancada), neutros quentes de papel/aço, e um laranja-sinalização usado com parcimônia.

| Token | Claro | Escuro | Uso |
|---|---|---|---|
| `--color-bg` | `#f4f3ef` | `#111413` | fundo da página |
| `--color-surface` | `#ffffff` | `#191d1c` | cards, campos, folhas |
| `--color-surface-2` | `#ebe9e3` | `#222826` | cabeçalho de tabela, hover, faixas |
| `--color-border` | `#d4d1c8` | `#323a37` | divisores, borda de card |
| `--color-border-strong` | `#8f938e` | `#6b736f` | borda de campo (3:1) |
| `--color-text` | `#1b1f1d` | `#ebece8` | texto principal |
| `--color-text-muted` | `#565c59` | `#a2a9a5` | texto secundário, legendas |
| `--color-brand` | `#0e5a4f` | `#3fae99` | botão primário, link, item ativo |
| `--color-brand-strong` | `#0a433b` | `#63c4b1` | hover/pressionado |
| `--color-on-brand` | `#ffffff` | `#0b1715` | texto sobre marca |
| `--color-accent` | `#e8690c` | `#f08a3a` | anel de foco, marcador, fio de destaque; **nunca texto sobre fundo claro** |
| `--color-success` / `-soft` | `#17673a` / `#e0f0e5` | `#5cc285` / `#173323` | confirmação |
| `--color-warning` / `-soft` | `#7d5000` / `#fbefd5` | `#e8b54d` / `#352a12` | atenção |
| `--color-danger` / `-soft` | `#b0241a` / `#fae4e1` | `#f0776c` / `#3b1a17` | erro, ação destrutiva |
| `--color-info` / `-soft` | `#1d5a92` / `#e2ecf7` | `#74aee6` / `#172a3d` | informação |

Proporção na tela: ~85% neutros, ~10% verde, ≤5% laranja e cores de status.

### Mapa de status (sempre cor + texto, nunca só cor)

| Status | Rótulo na UI | Família | Claro fg/bg | Escuro fg/bg |
|---|---|---|---|---|
| OS `open` | Aberta | azul | `#164a7a` / `#e2ecf7` | `#8fbdec` / `#172a3d` |
| OS `in_progress` | Em execução | âmbar | `#664200` / `#fbefd5` | `#ecc062` / `#352a12` |
| OS `ready` | Pronta | verde | `#115430` / `#e0f0e5` | `#74cf98` / `#173323` |
| OS `delivered` | Entregue | petróleo | `#0a433b` / `#dcece8` | `#70ccb9` / `#13302b` |
| OS `closed` | Fechada | cinza | `#434946` / `#e6e4de` | `#b9bfbb` / `#2a302d` |
| OS `canceled` | Cancelada | vermelho | `#84190f` / `#fae4e1` | `#f7a49d` / `#3b1a17` |
| Orçamento `draft` | Rascunho | cinza | `#434946` / `#e6e4de` | `#b9bfbb` / `#2a302d` |
| Orçamento `sent` | Enviado | azul | `#164a7a` / `#e2ecf7` | `#8fbdec` / `#172a3d` |
| Orçamento `viewed` | Visualizado | ciano | `#0b5566` / `#dbeff3` | `#72c6d8` / `#12303a` |
| Orçamento `approved` | Aprovado | verde | `#115430` / `#e0f0e5` | `#74cf98` / `#173323` |
| Orçamento `rejected` | Recusado | vermelho | `#84190f` / `#fae4e1` | `#f7a49d` / `#3b1a17` |
| Orçamento `expired` | Expirado | ferrugem | `#7a3a06` / `#f6e5d6` | `#eda36a` / `#38230f` |

### Contraste (WCAG 2.1, calculado com a fórmula de luminância relativa)

| Par | Claro | Escuro |
|---|---|---|
| text / bg · surface · surface-2 | 15,01 · 16,66 · 13,72 | 15,61 · 14,34 · 12,64 |
| text-muted / bg · surface · surface-2 | 6,16 · 6,84 · 5,63 | 7,73 · 7,10 · 6,26 |
| on-brand / brand · brand-strong | 8,10 · 11,18 | 6,73 · 8,77 |
| brand (como texto/link) / surface · bg | 8,10 · 7,29 | 6,26 · 6,82 |
| on-accent / accent | 5,12 | 7,41 |
| success · warning · danger · info / seu `-soft` | 5,85 · 6,10 · 5,54 · 6,00 | 6,20 · 7,48 · 5,62 · 6,23 |
| success · warning · danger · info / surface | 6,91 · 6,95 · 6,75 · 7,17 | 7,71 · 9,04 · 6,13 · 7,25 |
| border-strong / surface (UI, mín. 3:1) | 3,12 | 3,49 |
| accent / surface (anel de foco, mín. 3:1) | 3,25 | 6,81 |
| Todos os 12 pares de status fg/bg | 7,02 a 9,16 (AAA) | 7,10 a 8,24 (AAA) |

Todos os pares de texto passam AA (≥ 4,5); todos os status passam AAA (≥ 7), como pede a regra de "texto de valor e status".

## 6. Tipografia

Uma superfamília, duas famílias do Google Fonts: **IBM Plex Sans** (texto e números) e **IBM Plex Sans Condensed** (títulos e números grandes). Plex tem origem em engenharia, desenho de ferramenta, ótima leitura em tela pequena e **algarismos tabulares** (`font-variant-numeric: tabular-nums`).

| Papel | Família | Peso | Tamanho / altura de linha (mobile → desktop) |
|---|---|---|---|
| Título de página | Condensed | 700 | 24/28 → 30/36 |
| Título de seção/card | Condensed | 600 | 18/24 → 20/28 |
| Corpo | Sans | 400 | 16/24 (nunca < 16 px em campo, evita zoom no iOS) |
| Rótulo, botão | Sans | 600 | 15/20 → 16/20 |
| Legenda, meta | Sans | 400/500 | 13/18 (mínimo absoluto) |
| Valor em destaque (total da OS) | Condensed | 700 | 28/32, tabular |
| Placa | Sans | 600 | caixa alta, `letter-spacing: 0.04em`, tabular |

Dinheiro, km, quantidade, número da OS e placa: sempre `tabular-nums`, alinhados à direita em tabelas.

## 7. Espaçamento, raio, sombra, ícones

- **Espaçamento:** escala de 4 px (4, 8, 12, 16, 24, 32, 48; no `sx` do MUI, `0.5` a `6`). Gutter lateral mobile 16 px; entre cards 12 px; dentro de card 16 px.
- **Alvo de toque:** mínimo 44×44 px; botões principais no mobile com 48 px de altura.
- **Raios:** `--radius-sm` 4 px (badge), `--radius-md` 8 px (botão, campo), `--radius-lg` 12 px (card, folha, modal). Nada de pílula em botão; pílula só em badge se preferir, mas padrão é 4 px.
- **Sombras:** `--shadow-sm` em card (opcional, a borda já separa), `--shadow-md` só em elementos flutuantes (menu, folha inferior, toast). Sem sombra colorida.
- **Ícones:** [Lucide](https://lucide.dev) (`lucide-react`), traço 2 px, 20 px na UI e 24 px na navegação. Ícone sempre acompanhado de texto, exceto em botão de ícone com `aria-label`. Proibido: ícones de brilho/estrela/varinha mágica, ícones preenchidos coloridos, ilustrações 3D.

## 8. Proibido (marca)

Gradiente (principalmente roxo/azul), glassmorphism/blur de fundo, blobs e formas orgânicas decorativas, emoji, ícones de "IA" (brilho, estrela, varinha), ilustrações 3D genéricas ou de banco de imagem, texto em degradê, sombras coloridas/neon, animações chamativas (bounce, confete), fundo preto puro `#000`, cinza frio azulado, fonte diferente de Plex, status só com cor, laranja como cor de texto no tema claro, frases de marketing vazias.

---

## Para o desenvolvedor de frontend

### 1. Fontes

No `index.html`, dentro de `<head>`:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Sans+Condensed:wght@600;700&display=swap" rel="stylesheet">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<meta name="theme-color" content="#0e5a4f">
```

Copie `docs/brand/favicon.svg` para `frontend/public/favicon.svg`.

### 2. Tokens com MUI

O CSS global importa `docs/brand/tokens.css` (sem copiar). O tema MUI fica em `frontend/src/app/theme.ts` e aponta a paleta para os tokens, então nenhum hex vai para o código:

```ts
const v = (token: string) => `var(--color-${token})`

createTheme({
  cssVariables: { nativeColor: true }, // hover/opacidade calculados em CSS a partir da var
  palette: {
    primary: { main: v('brand'), dark: v('brand-strong'), contrastText: v('on-brand') },
    error: { main: v('danger'), light: v('danger-soft') }, // idem success, warning, info
    background: { default: v('bg'), paper: v('surface') },
    text: { primary: v('text'), secondary: v('text-muted') },
    divider: v('border'),
  },
  typography: { fontFamily: 'var(--font-sans)' }, // títulos com var(--font-display)
})
```

Nos componentes, use as chaves da paleta no `sx` (`'primary.main'`, `'text.secondary'`, `'error.light'`, `'divider'`) ou `var(--color-…)` para o que a paleta não tem (`--color-border-strong`). `.num` (tabular-nums) é global. Raios no `sx`: `0.5` = 4 px, `1` = 8 px, `1.5` = 12 px.

- **Tema:** `document.documentElement.dataset.theme = "dark"`. Padrão: seguir `prefers-color-scheme` na primeira visita e salvar a escolha do usuário. Use só tokens; nunca hex no componente. Assim o escuro sai de graça.
- **Status:** use as variáveis direto, mapeando o enum: `style={{ color: \`var(--status-os-${status}-fg)\`, background: \`var(--status-os-${status}-bg)\` }}` (ou `--status-quote-…`). Os nomes batem com os enums do backend.

### 3. Componentes

**Botões** (altura 48 px no mobile, 40 px no desktop; `rounded-md`, `font-semibold`, padding horizontal 16 px; largura total no mobile quando for a ação principal da tela):

| Tipo | Classes base | Uso |
|---|---|---|
| Primário | `bg-brand text-on-brand hover:bg-brand-strong` | uma por tela: "Salvar", "Enviar pelo WhatsApp" |
| Secundário | `bg-surface text-text border border-border-strong hover:bg-surface-2` | "Cancelar", "Adicionar item" |
| Perigo | `bg-danger text-white hover:opacity-90` (escuro: `text-bg`) | só dentro de confirmação: "Cancelar OS" |
| Fantasma/link | `text-brand underline-offset-4 hover:underline` | ações terciárias |

Desabilitado: `opacity-50 cursor-not-allowed`. Carregando: spinner de 16 px à esquerda + texto no gerúndio ("Salvando…"), botão mantém a largura.

**Campo:** rótulo sempre visível acima (`text-sm font-medium`), input `h-12 rounded-md bg-surface border border-border-strong px-3 text-base`, foco com anel `accent`. Ajuda abaixo em `text-sm text-muted`; erro abaixo em `text-sm text-danger` com ícone, e borda `border-danger`. Use `inputmode` certo: `numeric` para km, `decimal` para valor, `tel` para telefone; placa em caixa alta automática. Nunca use placeholder como rótulo.

**Card:** `bg-surface border border-border rounded-lg p-4`. Título em `font-display font-semibold text-lg`. Sem sombra por padrão.

**Lista de OS no mobile:** uma linha-card por OS, toque abre a OS (alvo inteiro clicável, mín. 72 px):
```
[ OS 1042 · ABC1D23                [Em execução] ]
[ Gol 1.0 2015 · João Silva                      ]
[ Hoje, 14:20                       R$ 1.250,00  ]
```
Número e placa em `font-semibold num`; valor à direita em `num font-semibold`; meta em `text-sm text-muted`. No desktop (≥ 1024 px) vira tabela: cabeçalho `bg-surface-2 text-sm text-muted`, linhas com `border-b border-border`, hover `bg-surface-2`, números alinhados à direita. Filtro por status em chips roláveis acima da lista.

**Badge de status:** `inline-flex items-center gap-1 rounded-sm px-2 py-0.5 text-[13px] font-semibold` com as cores do status + rótulo da tabela da seção 5. Opcional: ponto de 6 px na cor `fg`. Nunca só o ponto.

**Navegação:** mobile: barra inferior fixa, `bg-surface border-t border-border`, 4 itens com ícone 24 px + rótulo 12 px: **Início, OS, Orçamentos, Clientes**; ativo em `text-brand` com fio superior de 2 px `accent`; respeite `env(safe-area-inset-bottom)`. Ação "Nova OS/Novo orçamento" em botão primário no topo da lista (não use FAB flutuante redondo). Desktop (≥ 1024 px): barra lateral de 240 px, `bg-surface border-r border-border`, logo no topo, mesmos itens + **Configurações** embaixo; item ativo `bg-surface-2 text-brand font-semibold` com fio esquerdo de 3 px `accent`.

**Estado vazio:** ícone Lucide 32 px em `text-muted`, título curto, uma frase, um botão primário. Sem ilustração.

**Carregando:** skeleton (`bg-surface-2 animate-pulse rounded-md`) no formato do conteúdo; spinner só dentro de botão. Nada de tela inteira bloqueada.

**Erro:** inline perto de onde aconteceu, caixa `bg-danger-soft text-danger rounded-md p-3` com ícone, o que houve e o que fazer + botão "Tentar de novo". Toasts (canto inferior no mobile, acima da barra; superior direito no desktop) só para confirmação rápida, somem em 4 s.

**Página pública do orçamento (cliente):** sem navegação do app, logo da oficina no topo (o Prumo aparece só no rodapé: "Enviado com Prumo"), itens em lista clara com total `font-display` grande, botões "Aprovar e assinar" (primário) e "Recusar" (secundário). Área de assinatura com borda tracejada `border-border-strong` e botão "Limpar".

### 4. Microcopy padrão

| Situação | Texto |
|---|---|
| Botões | Salvar · Cancelar · Voltar · Editar · Excluir · Nova OS · Novo orçamento · Adicionar item · Enviar pelo WhatsApp · Reenviar link · Copiar link · Aprovar e assinar · Recusar · Limpar assinatura · Iniciar serviço · Marcar como pronta · Registrar entrega · Fechar OS · Cancelar OS · Tentar de novo |
| Sucesso | "OS 1042 aberta." · "Orçamento enviado para João pelo WhatsApp." · "Cliente assinou o orçamento. OS 1043 criada." · "Alterações salvas." · "Link copiado." |
| Vazio (OS) | "Nenhuma OS aberta." / "Abra a primeira OS ou envie um orçamento." / [Nova OS] |
| Vazio (orçamentos) | "Nenhum orçamento ainda." / "Monte o orçamento no celular e mande pelo WhatsApp." / [Novo orçamento] |
| Vazio (busca) | "Nada encontrado para “ABC1D23”." / "Confira a placa ou o nome." |
| Erro genérico | "Não deu para salvar. Tente de novo." |
| Sem conexão | "Sem conexão. Salvamos aqui e enviamos quando voltar." |
| Foto subindo | "Enviando 2 fotos…" (não bloqueia nada) |
| Campo obrigatório | "Informe a placa." · "Informe o km." (sempre "Informe o/a …") |
| Confirmação destrutiva | Título "Cancelar a OS 1042?" / "Essa ação não pode ser desfeita." / [Cancelar OS] [Voltar] |
| Link expirado (cliente) | "Este orçamento expirou. Peça um novo link para a oficina." |
| Orçamento aprovado (cliente) | "Orçamento aprovado. A oficina já foi avisada." |

Datas: "Hoje, 14:20", "Ontem", "12/10/2026". Dinheiro: `Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`. Km: `84.300 km`.

### 5. Proibido no frontend

- Hex, `rgb()` ou cor padrão do MUI (`blue[500]`, `grey[600]`) em componente: só tokens.
- Gradiente, `backdrop-blur`, sombra colorida, emoji, ícones de brilho/IA, ilustrações.
- Status só com cor; laranja (`accent`) como cor de texto no tema claro.
- Texto < 13 px; input < 16 px; alvo de toque < 44 px.
- Placeholder no lugar de rótulo; mais de um botão primário por tela.
- Spinner de tela cheia, modal para mensagem de sucesso, bloquear o salvamento esperando foto.
- Terceira família de fonte ou recriar o logotipo com fonte.
- Microcopy fora da seção 4 sem passar pelo tom da seção 3.
