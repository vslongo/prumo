import { createBrowserRouter } from 'react-router'
import { AcceptInvitationPage } from '../features/auth/pages/AcceptInvitationPage'
import { LoginPage } from '../features/auth/pages/LoginPage'
import { SecurityPage } from '../features/auth/pages/SecurityPage'
import { SignUpPage } from '../features/auth/pages/SignUpPage'
import { CustomerDetailPage } from '../features/customers/pages/CustomerDetailPage'
import { CustomersPage } from '../features/customers/pages/CustomersPage'
import { DashboardPage } from '../features/dashboard/pages/DashboardPage'
import { NewQuotePage } from '../features/quotes/pages/NewQuotePage'
import { QuoteDetailPage } from '../features/quotes/pages/QuoteDetailPage'
import { QuotesPage } from '../features/quotes/pages/QuotesPage'
import { NewServiceOrderPage } from '../features/serviceOrders/pages/NewServiceOrderPage'
import { ServiceOrderDetailPage } from '../features/serviceOrders/pages/ServiceOrderDetailPage'
import { ServiceOrdersPage } from '../features/serviceOrders/pages/ServiceOrdersPage'
import { SettingsPage } from '../features/serviceTypes/pages/SettingsPage'
import { AppLayout } from './AppLayout'
import { NotFoundPage } from './NotFoundPage'

export const routes = [
  { path: '/login', element: <LoginPage /> },
  { path: '/cadastro', element: <SignUpPage /> },
  { path: '/convite/:token', element: <AcceptInvitationPage /> },
  // Link que o cliente recebe no WhatsApp: sem login, sem navegação do app.
  // Carregado à parte: o cliente abre no 4G e não precisa do app inteiro.
  { path: '/o/:token', lazy: async () => ({ Component: (await import('../features/publicQuote/pages/PublicQuotePage')).PublicQuotePage }) },
  {
    path: '/',
    element: <AppLayout />,
    children: [
      { index: true, element: <DashboardPage /> },
      { path: 'os', element: <ServiceOrdersPage /> },
      { path: 'os/nova', element: <NewServiceOrderPage /> },
      { path: 'os/:id', element: <ServiceOrderDetailPage /> },
      { path: 'orcamentos', element: <QuotesPage /> },
      { path: 'orcamentos/novo', element: <NewQuotePage /> },
      { path: 'orcamentos/:id', element: <QuoteDetailPage /> },
      { path: 'clientes', element: <CustomersPage /> },
      { path: 'clientes/:id', element: <CustomerDetailPage /> },
      { path: 'configuracoes', element: <SettingsPage /> },
      { path: 'seguranca', element: <SecurityPage /> },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
]

export const router = createBrowserRouter(routes)
