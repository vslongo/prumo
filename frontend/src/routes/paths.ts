type RouteId = string | number

const segment = (value: RouteId): string => encodeURIComponent(String(value))

/**
 * Rotas do backend: routes/{api,web}.php, bootstrap/app.php e routes.php dos módulos.
 * Os caminhos já incluem /api e podem ser passados diretamente ao cliente http.
 * Os comentários indicam o método HTTP; list/create podem compartilhar a mesma URL.
 */
export const ENDPOINTS = () => ({
  Auth: {
    login: '/api/login', // POST
    signup: '/api/signup', // POST
    me: '/api/me', // GET
    logout: '/api/logout', // POST
    csrfCookie: '/sanctum/csrf-cookie', // GET (Laravel Sanctum)
  },
  Clients: {
    list: '/api/customers', // GET
    create: '/api/customers', // POST
    details: (id: RouteId) => `/api/customers/${segment(id)}`, // GET
    vehicles: (id: RouteId) => `/api/customers/${segment(id)}/vehicles`, // GET
  },
  Vehicles: {
    update: (id: RouteId) => `/api/vehicles/${segment(id)}`, // PUT ou PATCH
    list: '/api/vehicles', // GET
    create: '/api/vehicles', // POST
    details: (id: RouteId) => `/api/vehicles/${segment(id)}`, // GET
  },
  Users: {
    list: '/api/users', // GET
    changeRole: (id: RouteId) => `/api/users/${segment(id)}/role`, // PATCH
    deactivate: (id: RouteId) => `/api/users/${segment(id)}/deactivate`, // POST
    reactivate: (id: RouteId) => `/api/users/${segment(id)}/reactivate`, // POST
  },
  Invitations: {
    list: '/api/user-invitations', // GET
    create: '/api/user-invitations', // POST
    delete: (id: RouteId) => `/api/user-invitations/${segment(id)}`, // DELETE
  },
  PublicInvitations: {
    details: (token: string) => `/api/public/invitations/${segment(token)}`, // GET
    accept: (token: string) => `/api/public/invitations/${segment(token)}/accept`, // POST
  },
  Quotes: {
    list: '/api/quotes', // GET
    create: '/api/quotes', // POST
    details: (id: RouteId) => `/api/quotes/${segment(id)}`, // GET
    addItem: (id: RouteId) => `/api/quotes/${segment(id)}/items`, // POST
    removeItem: (id: RouteId, itemId: RouteId) =>
      `/api/quotes/${segment(id)}/items/${segment(itemId)}`, // DELETE
    send: (id: RouteId) => `/api/quotes/${segment(id)}/send`, // POST
  },
  PublicQuotes: {
    details: (token: string) => `/api/public/quotes/${segment(token)}`, // GET
    signatureUploadUrl: (token: string) =>
      `/api/public/quotes/${segment(token)}/signature-upload-url`, // POST
    approve: (token: string) => `/api/public/quotes/${segment(token)}/approve`, // POST
    reject: (token: string) => `/api/public/quotes/${segment(token)}/reject`, // POST
  },
  ServiceOrders: {
    list: '/api/service-orders', // GET
    create: '/api/service-orders', // POST
    details: (id: RouteId) => `/api/service-orders/${segment(id)}`, // GET
    addItem: (id: RouteId) => `/api/service-orders/${segment(id)}/items`, // POST
    markItemDone: (id: RouteId, itemId: RouteId) =>
      `/api/service-orders/${segment(id)}/items/${segment(itemId)}/done`, // POST
    transition: (id: RouteId) => `/api/service-orders/${segment(id)}/transition`, // POST
    close: (id: RouteId) => `/api/service-orders/${segment(id)}/close`, // POST
  },
  ServiceTypes: {
    list: '/api/service-types', // GET
    create: '/api/service-types', // POST
    update: (id: RouteId) => `/api/service-types/${segment(id)}`, // PUT ou PATCH
  },
  Messaging: {
    // Callback interno do worker: exige assinatura VerifyWorkerSignature.
    whatsappStatus: '/api/internal/whatsapp/status', // POST
  },
  Backend: {
    home: '/', // GET
    health: '/up', // GET
  },
  Storage: {
    // GET/PUT: exigem a URL assinada fornecida pelo backend, com sua query string.
    local: (path: string) => `/storage/${path.split('/').map(segment).join('/')}`,
    // Disponível somente quando R2_DRIVER=local.
    r2Local: (path: string) => `/r2-local/${path.split('/').map(segment).join('/')}`,
  },
} as const)
