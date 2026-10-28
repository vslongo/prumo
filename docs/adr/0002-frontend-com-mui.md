# 0002 — Frontend com MUI no lugar do Tailwind

- Status: aceito
- Data: 2026-10-27

## Contexto
O frontend usava Tailwind v4, com os tokens da marca (`docs/brand/tokens.css`) registrados como utilitários. A decisão do produto foi passar a usar uma biblioteca de componentes pronta, com diálogos, snackbar, tabelas e campos acessíveis, sem manter tudo à mão.

## Decisão
- Usar **MUI (`@mui/material`)** com Emotion. O Tailwind sai do frontend. O `backend/` continua com o Tailwind da página padrão do Laravel, que não faz parte do app.
- **`docs/brand/tokens.css` continua sendo a fonte única da marca.** O tema (`frontend/src/app/theme.ts`) aponta a paleta para `var(--color-*)`, com `cssVariables: { nativeColor: true }`, para o MUI derivar hover e opacidade em CSS. Nenhum hex é copiado para o tema.
- O claro/escuro continua sendo o atributo `data-theme` no `<html>` (script no `index.html` + `useTheme`). Não usamos o gerenciador de esquema de cores do MUI.
- Os breakpoints do tema são os mesmos do layout anterior: `sm` 640 e `lg` 1024.
- O `href` de `Button`, `Link` e `ListItemButton` passa pelo react-router (`shared/ui/LinkBehavior.tsx`, ligado no tema).
- Os selects são nativos (`NativeSelect`), por serem melhores no celular. Os ícones continuam no `lucide-react`.
- Os componentes de `shared/ui` mantiveram a mesma API. As features estilizam só com `sx`.

## Consequências
- O MUI 9 não aceita "system props" (`mt`, `gap`, `px`… direto no componente); tudo vai em `sx`.
- No `sx`, `borderRadius: n` multiplica o raio do tema (8 px): 4 px = `0.5`, 8 px = `1`, 12 px = `1.5`. O espaçamento usa a escala de 8 px (`1` = 8 px, `2` = 16 px).
- Em grid, use `minmax(0, 1fr)` (inclusive no `xs`), senão textos que não quebram linha alargam a coluna.
- O `Checkbox` do MUI guarda o próprio estado: com `register` do react-hook-form, o valor inicial precisa vir também em `defaultChecked`.
- O bundle principal ficou em ~800 KB (~250 KB gzip). Se pesar no 4G, separar as rotas com `lazy`.
