import { createTheme } from '@mui/material/styles'
import { LinkBehavior } from '../shared/ui/LinkBehavior'

/**
 * Tema MUI da marca. As cores apontam para os tokens de docs/brand/tokens.css (fonte única),
 * então claro/escuro continua sendo só o atributo data-theme no <html> (ver useTheme).
 * `nativeColor` faz o MUI derivar hover/opacidade com CSS relative color em vez de calcular em JS.
 */
const v = (token: string) => `var(--color-${token})`

export const theme = createTheme({
  cssVariables: { nativeColor: true },
  // Mesmos pontos de quebra do layout original (sm 640, lg 1024).
  breakpoints: { values: { xs: 0, sm: 640, md: 768, lg: 1024, xl: 1280 } },
  palette: {
    primary: { main: v('brand'), light: v('brand'), dark: v('brand-strong'), contrastText: v('on-brand') },
    secondary: { main: v('accent'), light: v('accent'), dark: v('accent'), contrastText: v('on-accent') },
    error: { main: v('danger'), light: v('danger-soft'), dark: v('danger'), contrastText: v('surface') },
    warning: { main: v('warning'), light: v('warning-soft'), dark: v('warning'), contrastText: v('surface') },
    success: { main: v('success'), light: v('success-soft'), dark: v('success'), contrastText: v('surface') },
    info: { main: v('info'), light: v('info-soft'), dark: v('info'), contrastText: v('surface') },
    background: { default: v('bg'), paper: v('surface') },
    text: { primary: v('text'), secondary: v('text-muted'), disabled: v('text-muted') },
    divider: v('border'),
    action: { hover: v('surface-2'), selected: v('surface-2'), active: v('text-muted'), disabled: v('text-muted'), disabledBackground: v('surface-2') },
  },
  shape: { borderRadius: 8 },
  typography: {
    fontFamily: 'var(--font-sans)',
    button: { textTransform: 'none', fontWeight: 600, fontSize: 15 },
    h1: { fontFamily: 'var(--font-display)', fontSize: 24, fontWeight: 700, lineHeight: 1.2, '@media (min-width:1024px)': { fontSize: 28 } },
    h2: { fontFamily: 'var(--font-display)', fontSize: 18, fontWeight: 600, lineHeight: 1.3 },
    h3: { fontFamily: 'var(--font-display)', fontSize: 20, fontWeight: 700, lineHeight: 1.3 },
    body1: { fontSize: 16 },
    body2: { fontSize: 14 },
  },
  components: {
    MuiCssBaseline: {
      styleOverrides: {
        html: { WebkitTapHighlightColor: 'transparent' },
        // ButtonBase vira <button>, que não herda a fonte por padrão.
        button: { font: 'inherit' },
        ':focus-visible': { outline: `2px solid ${v('focus')}`, outlineOffset: 2 },
        '.num': { fontVariantNumeric: 'tabular-nums' },
      },
    },
    MuiButtonBase: { defaultProps: { disableRipple: true, LinkComponent: LinkBehavior } },
    MuiLink: { defaultProps: { component: LinkBehavior } },
    MuiButton: {
      defaultProps: { disableElevation: true },
      styleOverrides: {
        root: { minHeight: 48, paddingInline: 16, whiteSpace: 'nowrap', '@media (min-width:1024px)': { minHeight: 40, fontSize: 16 } },
        sizeSmall: { minHeight: 36, '@media (min-width:1024px)': { minHeight: 36 } },
        text: { paddingInline: 8, '&:hover': { background: 'transparent', textDecoration: 'underline', textUnderlineOffset: 4 } },
      },
    },
    MuiPaper: { defaultProps: { variant: 'outlined' }, styleOverrides: { rounded: { borderRadius: 12 } } },
    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          background: v('surface'),
          '& .MuiOutlinedInput-notchedOutline': { borderColor: v('border-strong') },
          '&.Mui-focused .MuiOutlinedInput-notchedOutline': { borderColor: v('accent'), borderWidth: 2 },
          '&.Mui-error .MuiOutlinedInput-notchedOutline': { borderColor: v('danger') },
        },
        input: { minHeight: 24, paddingBlock: 12 },
      },
    },
    MuiFormLabel: { styleOverrides: { root: { fontSize: 14, fontWeight: 500, color: v('text'), marginBottom: 6, '&.Mui-focused, &.Mui-error': { color: v('text') } } } },
    MuiFormHelperText: { styleOverrides: { root: { marginInline: 0, fontSize: 14 } } },
    MuiSkeleton: { defaultProps: { variant: 'rounded', animation: 'pulse' }, styleOverrides: { root: { background: v('surface-2') } } },
    MuiDialog: { styleOverrides: { paper: { borderRadius: 12, width: 'min(92vw, 440px)', margin: 16, padding: 20 } } },
    MuiTableCell: {
      styleOverrides: {
        root: { borderColor: v('border'), paddingBlock: 12, paddingInline: 16 },
        head: { background: v('surface-2'), color: v('text-muted'), fontSize: 14, fontWeight: 500, paddingBlock: 10 },
      },
    },
  },
})
