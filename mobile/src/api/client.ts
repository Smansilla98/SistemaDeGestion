import { API_URL } from '../config';
import { clearTokens, getAccessToken, getRefreshToken, setTokens } from '../auth/storage';
import type {
  AuthPayload,
  CashSessionDetail,
  CashSessionRow,
  CatalogCategory,
  CatalogSector,
  ClientRow,
  DiscountTypeRow,
  NotificationRow,
  OrderRow,
  ProductRow,
  ProductsReport,
  SalesReport,
  StaffReport,
  TableDetail,
  TableLayoutPayload,
  TableRow,
} from './types';

type ApiEnvelope<T> = {
  success: boolean;
  data: T;
  message?: string;
  code?: string;
  errors?: Record<string, string[]>;
};

let refreshPromise: Promise<boolean> | null = null;

async function tryRefresh(): Promise<boolean> {
  const refresh = await getRefreshToken();
  if (!refresh) return false;

  const res = await fetch(`${API_URL}/auth/refresh`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ refresh_token: refresh }),
  });

  if (!res.ok) {
    await clearTokens();
    return false;
  }

  const json = (await res.json()) as ApiEnvelope<AuthPayload>;
  if (!json.success || !json.data?.access_token || !json.data?.refresh_token) {
    await clearTokens();
    return false;
  }

  await setTokens(json.data.access_token, json.data.refresh_token);
  return true;
}

export class ApiError extends Error {
  status: number;
  code?: string;

  constructor(message: string, status: number, code?: string) {
    super(message);
    this.status = status;
    this.code = code;
  }
}

export async function apiRequest<T>(
  path: string,
  options: RequestInit = {},
  retry = true,
): Promise<T> {
  const token = await getAccessToken();
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string> | undefined),
  };
  if (token) headers.Authorization = `Bearer ${token}`;

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20000);

  let res: Response;
  try {
    res = await fetch(`${API_URL}${path}`, {
      ...options,
      headers,
      signal: controller.signal,
    });
  } catch (e) {
    clearTimeout(timeout);
    throw new ApiError(
      e instanceof Error && e.name === 'AbortError'
        ? 'Tiempo de espera agotado. Revisá tu conexión.'
        : 'Sin conexión. Reintentá en unos segundos.',
      0,
      'NETWORK',
    );
  } finally {
    clearTimeout(timeout);
  }

  if (res.status === 401 && retry) {
    if (!refreshPromise) {
      refreshPromise = tryRefresh().finally(() => {
        refreshPromise = null;
      });
    }
    const ok = await refreshPromise;
    if (ok) return apiRequest<T>(path, options, false);
  }

  const json = (await res.json().catch(() => ({}))) as ApiEnvelope<T> & { message?: string };

  if (!res.ok || json.success === false) {
    throw new ApiError(json.message ?? `Error ${res.status}`, res.status, json.code);
  }

  return json.data;
}

/** Normaliza listados paginados o planos. */
function asList<T>(data: unknown): T[] {
  if (Array.isArray(data)) return data as T[];
  if (data && typeof data === 'object') {
    const obj = data as Record<string, unknown>;
    if (Array.isArray(obj.data)) return obj.data as T[];
  }
  return [];
}

export const api = {
  login: (username: string, password: string) =>
    apiRequest<AuthPayload>(
      '/auth/login',
      {
        method: 'POST',
        body: JSON.stringify({ username, password }),
      },
      false,
    ),

  me: () => apiRequest<import('./types').ApiUser>('/auth/me'),

  logout: async (refreshToken: string | null) => {
    try {
      await apiRequest(
        '/auth/logout',
        {
          method: 'POST',
          body: JSON.stringify({ refresh_token: refreshToken }),
        },
        false,
      );
    } finally {
      await clearTokens();
    }
  },

  tables: (sectorId?: number) =>
    apiRequest<TableRow[]>(
      sectorId != null ? `/tables?sector_id=${sectorId}` : '/tables',
    ).then((d) => asList<TableRow>(d)),

  tablesLayout: (sectorId?: number) => {
    const q = sectorId != null ? `?sector_id=${sectorId}` : '';
    return apiRequest<TableLayoutPayload>(`/tables/layout${q}`);
  },

  tableDetail: (id: number) => apiRequest<TableDetail>(`/tables/${id}`),

  occupyTable: (id: number) => apiRequest(`/tables/${id}/occupy`, { method: 'POST' }),

  freeTable: (id: number) => apiRequest(`/tables/${id}/free`, { method: 'POST' }),

  transferTable: (id: number, target_table_id: number) =>
    apiRequest(`/tables/${id}/transfer`, {
      method: 'POST',
      body: JSON.stringify({ target_table_id }),
    }),

  createTable: (body: {
    sector_id: number;
    number: string;
    capacity: number;
    position_x?: number;
    position_y?: number;
  }) =>
    apiRequest('/tables', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  updateTable: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/tables/${id}`, {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  deleteTable: (id: number) => apiRequest(`/tables/${id}`, { method: 'DELETE' }),

  reserveTable: (
    id: number,
    body: {
      customer_name: string;
      customer_phone: string;
      reservation_date: string;
      reservation_time: string;
      number_of_guests: number;
    },
  ) =>
    apiRequest(`/tables/${id}/reserve`, {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  saveTablesLayout: (body: {
    sector_id: number;
    tables: { id: number; position_x: number; position_y: number }[];
  }) =>
    apiRequest('/tables/layout', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  permissionModules: () =>
    apiRequest<{
      modules: Array<{ key: string; label: string; actions: string[] }>;
      action_labels: Record<string, string>;
      roles: string[];
      matrix_by_role: Record<string, Record<string, boolean>>;
      all_keys: string[];
    }>('/permissions/modules'),

  userPermissionMatrix: (userId: number) =>
    apiRequest<{
      user: { id: number; name: string; username: string; role: string };
      matrix: Record<string, boolean>;
      overrides: Record<string, boolean>;
      modules: Array<{ key: string; label: string; actions: string[] }>;
      action_labels: Record<string, string>;
    }>(`/permissions/users/${userId}/matrix`),

  updateUserPermissions: (user_id: number, permissions: Record<string, boolean>) =>
    apiRequest('/permissions/user', {
      method: 'POST',
      body: JSON.stringify({ user_id, permissions }),
    }),

  updateRolePermissions: (role: string, permissions: Record<string, boolean>) =>
    apiRequest('/permissions/role', {
      method: 'POST',
      body: JSON.stringify({ role, permissions }),
    }),

  payTable: (id: number, payments: { payment_method: string; amount: number }[], discount_type_id?: number) =>
    apiRequest(`/tables/${id}/pay`, {
      method: 'POST',
      body: JSON.stringify({
        payments,
        ...(discount_type_id != null ? { discount_type_id } : {}),
      }),
    }),

  products: (opts?: { activeOnly?: boolean; perPage?: number }) => {
    const perPage = opts?.perPage ?? 100;
    const active = opts?.activeOnly === false ? '' : '&is_active=1';
    const q = `/products?per_page=${perPage}&type=PRODUCT${active}`;
    return apiRequest<ProductRow[] | { data: ProductRow[] }>(q)
      .then((d) => asList<ProductRow>(d))
      .catch(async () => {
        const token = await getAccessToken();
        const res = await fetch(`${API_URL}${q}`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        const json = await res.json();
        return asList<ProductRow>(json.data ?? json);
      });
  },

  product: (id: number) => apiRequest<ProductRow>(`/products/${id}`),

  createProduct: (body: Record<string, unknown>) =>
    apiRequest('/products', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  updateProduct: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/products/${id}`, {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  deleteProduct: (id: number) => apiRequest(`/products/${id}`, { method: 'DELETE' }),

  createOrder: (body: Record<string, unknown>) =>
    apiRequest<Record<string, unknown>>('/orders', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  orders: (status?: string) =>
    apiRequest<OrderRow[] | { data: OrderRow[] }>(
      status ? `/orders?status=${encodeURIComponent(status)}&per_page=50` : '/orders?per_page=50',
    )
      .then((d) => asList<OrderRow>(d))
      .catch(async () => {
        const token = await getAccessToken();
        const res = await fetch(
          `${API_URL}/orders?per_page=50${status ? `&status=${encodeURIComponent(status)}` : ''}`,
          { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } },
        );
        const json = await res.json();
        return asList<OrderRow>(json.data ?? json);
      }),

  order: async (id: number) => {
    const data = await apiRequest<OrderRow | { order: OrderRow; items?: OrderRow['items'] }>(
      `/orders/${id}`,
    );
    if (data && typeof data === 'object' && 'order' in data && data.order) {
      const o = { ...data.order };
      if (!o.items && Array.isArray(data.items)) o.items = data.items;
      return o;
    }
    return data as OrderRow;
  },

  updateOrder: (id: number, body: Record<string, unknown>) =>
    apiRequest<OrderRow>(`/orders/${id}`, {
      method: 'PUT',
      body: JSON.stringify(body),
    }),

  deleteOrder: (id: number) => apiRequest(`/orders/${id}`, { method: 'DELETE' }),

  sendToKitchen: (id: number) =>
    apiRequest(`/orders/${id}/send-to-kitchen`, { method: 'POST' }),

  closeOrder: (id: number) =>
    apiRequest<OrderRow>(`/orders/${id}/close`, { method: 'POST' }),

  transitionOrder: (id: number, status: string, note?: string) =>
    apiRequest(`/orders/${id}/transition`, {
      method: 'POST',
      body: JSON.stringify({ status, note }),
    }),

  addOrderItem: (id: number, body: { product_id: number; quantity: number; observations?: string }) =>
    apiRequest<{ item: unknown; order: OrderRow }>(`/orders/${id}/items`, {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  removeOrderItems: (id: number, item_ids: number[]) =>
    apiRequest<OrderRow>(`/orders/${id}/items/remove`, {
      method: 'POST',
      body: JSON.stringify({ item_ids }),
    }),

  replaceOrderItem: (
    id: number,
    body: { order_item_id: number; product_id: number; quantity?: number; observations?: string },
  ) =>
    apiRequest<{ item: unknown; order: OrderRow }>(`/orders/${id}/items/replace`, {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  applyDiscount: (id: number, discount_type_id: number | null, reason?: string) =>
    apiRequest<OrderRow>(`/orders/${id}/discount`, {
      method: 'POST',
      body: JSON.stringify({ discount_type_id, reason }),
    }),

  cancelOrder: (id: number, reason?: string) =>
    apiRequest(`/orders/${id}/cancel`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    }),

  kitchenBoard: () =>
    apiRequest<{
      counts: Record<string, number>;
      orders: Array<Record<string, unknown>>;
    }>('/kitchen/board'),

  kitchenItemStatus: (itemId: number, status: string) =>
    apiRequest(`/kitchen/items/${itemId}/status`, {
      method: 'POST',
      body: JSON.stringify({ status }),
    }),

  dashboard: () => apiRequest<import('./types').DashboardPayload>('/dashboard'),

  categories: () =>
    apiRequest<Array<{ id: number; name: string }>>('/categories').then((d) =>
      asList<{ id: number; name: string }>(d),
    ),

  catalogCategories: () =>
    apiRequest<CatalogCategory[]>('/catalog/categories').then((d) => asList<CatalogCategory>(d)),

  createCatalogCategory: (body: {
    name: string;
    description?: string;
    display_order?: number;
    is_active?: boolean;
  }) => apiRequest('/catalog/categories', { method: 'POST', body: JSON.stringify(body) }),

  updateCatalogCategory: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/catalog/categories/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteCatalogCategory: (id: number) =>
    apiRequest(`/catalog/categories/${id}`, { method: 'DELETE' }),

  catalogSectors: () =>
    apiRequest<CatalogSector[]>('/catalog/sectors').then((d) => asList<CatalogSector>(d)),

  createCatalogSector: (body: { name: string; description?: string; is_active?: boolean }) =>
    apiRequest('/catalog/sectors', { method: 'POST', body: JSON.stringify(body) }),

  updateCatalogSector: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/catalog/sectors/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteCatalogSector: (id: number) =>
    apiRequest(`/catalog/sectors/${id}`, { method: 'DELETE' }),

  discountTypes: () =>
    apiRequest<DiscountTypeRow[]>('/catalog/discounts').then((d) => asList<DiscountTypeRow>(d)),

  createDiscountType: (body: {
    name: string;
    percentage: number;
    description?: string;
    is_active?: boolean;
  }) => apiRequest('/catalog/discounts', { method: 'POST', body: JSON.stringify(body) }),

  updateDiscountType: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/catalog/discounts/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteDiscountType: (id: number) =>
    apiRequest(`/catalog/discounts/${id}`, { method: 'DELETE' }),

  clients: () =>
    apiRequest<ClientRow[] | { data: ClientRow[] }>('/clients?per_page=100')
      .then((d) => asList<ClientRow>(d))
      .catch(async () => {
        const token = await getAccessToken();
        const res = await fetch(`${API_URL}/clients?per_page=100`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        const json = await res.json();
        return asList<ClientRow>(json.data ?? json);
      }),

  createClient: (body: { name: string; phone?: string; email?: string; notes?: string }) =>
    apiRequest('/clients', { method: 'POST', body: JSON.stringify(body) }),

  updateClient: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/clients/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteClient: (id: number) => apiRequest(`/clients/${id}`, { method: 'DELETE' }),

  users: () => {
    type UserListRow = {
      id: number;
      name: string;
      username: string;
      role: string;
      is_active: boolean;
    };
    return apiRequest<UserListRow[] | { data: UserListRow[] }>('/users?per_page=100')
      .then((d) => asList<UserListRow>(d))
      .catch(async () => {
        const token = await getAccessToken();
        const res = await fetch(`${API_URL}/users?per_page=100`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        const json = await res.json();
        const data = json.data;
        return asList<UserListRow>(Array.isArray(data) ? data : data?.data ?? []);
      });
  },

  createUser: (body: {
    name: string;
    username: string;
    password: string;
    role: string;
    email?: string;
    is_active?: boolean;
  }) => apiRequest('/users', { method: 'POST', body: JSON.stringify(body) }),

  updateUser: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/users/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteUser: (id: number) => apiRequest(`/users/${id}`, { method: 'DELETE' }),

  getUser: (id: number) =>
    apiRequest<{
      id: number;
      name: string;
      username: string;
      email?: string | null;
      role: string;
      is_active: boolean;
    }>(`/users/${id}`),

  resetUserPassword: (id: number) =>
    apiRequest<{
      user: Record<string, unknown>;
      temporary_password: string;
    }>(`/users/${id}/reset-password`, { method: 'POST' }),

  stock: (search?: string, type?: 'PRODUCT' | 'INSUMO') => {
    const qs = new URLSearchParams();
    if (search) qs.set('search', search);
    if (type) qs.set('type', type);
    const q = qs.toString();
    return apiRequest<import('./types').StockRow[]>(`/stock${q ? `?${q}` : ''}`);
  },

  stockMovements: (opts?: { productId?: number; dateFrom?: string; dateTo?: string }) => {
    const qs = new URLSearchParams();
    if (opts?.productId) qs.set('product_id', String(opts.productId));
    if (opts?.dateFrom) qs.set('date_from', opts.dateFrom);
    if (opts?.dateTo) qs.set('date_to', opts.dateTo);
    const q = qs.toString();
    return apiRequest<import('./types').StockMovementRow[]>(
      `/stock/movements${q ? `?${q}` : ''}`,
    );
  },

  createStockMovement: (body: {
    product_id: number;
    type: 'ENTRADA' | 'SALIDA' | 'AJUSTE';
    quantity: number;
    reason?: string;
    reference?: string;
    supplier_id?: number;
    new_supplier_name?: string;
    unit_cost?: number;
    purchase_date?: string;
    invoice_number?: string;
  }) =>
    apiRequest('/stock/movements', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  mozoInsumos: () =>
    apiRequest<Array<{ id: number; name: string; unit?: string | null; current_stock?: number }>>(
      '/stock/mozo-insumos',
    ).then((d) =>
      asList<{ id: number; name: string; unit?: string | null; current_stock?: number }>(d),
    ),

  createMozoInsumo: (body: { product_id: number; quantity: number; reason?: string }) =>
    apiRequest('/stock/mozo-insumos', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  suppliers: () =>
    apiRequest<Array<{ id: number; name: string }>>('/stock/suppliers').then((d) =>
      asList<{ id: number; name: string }>(d),
    ),

  cashSummary: (sessionId?: number) => {
    const q = sessionId ? `?session_id=${sessionId}` : '';
    return apiRequest<{
      session: Record<string, unknown> | null;
      sales_total: number;
      payments_count: number;
      expected_amount?: number;
      open_sessions?: CashSessionRow[];
    }>(`/cash/summary${q}`);
  },

  cashRegisters: () =>
    apiRequest<Array<{ id: number; name: string; is_active?: boolean }>>('/cash/registers').then(
      (d) => asList<{ id: number; name: string; is_active?: boolean }>(d),
    ),

  createCashRegister: (body: { name: string; is_active?: boolean }) =>
    apiRequest('/cash/registers', { method: 'POST', body: JSON.stringify(body) }),

  updateCashRegister: (id: number, body: { name?: string; is_active?: boolean }) =>
    apiRequest(`/cash/registers/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteCashRegister: (id: number) => apiRequest(`/cash/registers/${id}`, { method: 'DELETE' }),

  openCash: (registerId: number, initial_amount: number) =>
    apiRequest(`/cash/registers/${registerId}/open`, {
      method: 'POST',
      body: JSON.stringify({ initial_amount }),
    }),

  closeCash: (final_amount: number, notes?: string, sessionId?: number) =>
    apiRequest('/cash/session/close', {
      method: 'POST',
      body: JSON.stringify({
        final_amount,
        notes,
        ...(sessionId != null ? { session_id: sessionId } : {}),
      }),
    }),

  cashSessions: () =>
    apiRequest<CashSessionRow[]>('/cash/sessions').then((d) => asList<CashSessionRow>(d)),

  cashSessionDetail: (sessionId: number) =>
    apiRequest<CashSessionDetail>(`/cash/sessions/${sessionId}`),

  cashMovement: (body: {
    type: 'INGRESO' | 'EGRESO';
    amount: number;
    description: string;
    reference?: string;
    session_id?: number;
  }) =>
    apiRequest('/cash/movements', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  deleteCashMovement: (id: number) =>
    apiRequest(`/cash/movements/${id}`, { method: 'DELETE' }),

  reportsSales: (dateFrom?: string, dateTo?: string) => {
    const qs = new URLSearchParams();
    if (dateFrom) qs.set('date_from', dateFrom);
    if (dateTo) qs.set('date_to', dateTo);
    const q = qs.toString();
    return apiRequest<SalesReport>(`/reports/sales${q ? `?${q}` : ''}`);
  },

  reportsProducts: (dateFrom?: string, dateTo?: string) => {
    const qs = new URLSearchParams();
    if (dateFrom) qs.set('date_from', dateFrom);
    if (dateTo) qs.set('date_to', dateTo);
    const q = qs.toString();
    return apiRequest<ProductsReport>(`/reports/products${q ? `?${q}` : ''}`);
  },

  reportsStaff: (dateFrom?: string, dateTo?: string) => {
    const qs = new URLSearchParams();
    if (dateFrom) qs.set('date_from', dateFrom);
    if (dateTo) qs.set('date_to', dateTo);
    const q = qs.toString();
    return apiRequest<StaffReport>(`/reports/staff${q ? `?${q}` : ''}`);
  },

  events: () => apiRequest<Array<Record<string, unknown>>>('/events').then((d) => asList(d)),

  storeEvent: (body: {
    name: string;
    description?: string;
    date: string;
    time?: string;
    expected_attendance?: number;
    status?: string;
  }) => apiRequest('/events', { method: 'POST', body: JSON.stringify(body) }),

  updateEvent: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/events/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteEvent: (id: number) => apiRequest(`/events/${id}`, { method: 'DELETE' }),

  recurring: () =>
    apiRequest<Array<Record<string, unknown>>>('/recurring-activities').then((d) => asList(d)),

  storeRecurring: (body: Record<string, unknown>) =>
    apiRequest('/recurring-activities', { method: 'POST', body: JSON.stringify(body) }),

  updateRecurring: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/recurring-activities/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteRecurring: (id: number) =>
    apiRequest(`/recurring-activities/${id}`, { method: 'DELETE' }),

  fixedExpenses: () =>
    apiRequest<Array<Record<string, unknown>>>('/fixed-expenses').then((d) => asList(d)),

  storeFixedExpense: (body: Record<string, unknown>) =>
    apiRequest('/fixed-expenses', { method: 'POST', body: JSON.stringify(body) }),

  updateFixedExpense: (id: number, body: Record<string, unknown>) =>
    apiRequest(`/fixed-expenses/${id}`, { method: 'PUT', body: JSON.stringify(body) }),

  deleteFixedExpense: (id: number) =>
    apiRequest(`/fixed-expenses/${id}`, { method: 'DELETE' }),

  notifications: () =>
    apiRequest<NotificationRow[]>('/notifications').then((d) => asList<NotificationRow>(d)),

  markNotificationRead: (id: string) =>
    apiRequest(`/notifications/${id}/read`, { method: 'POST' }),

  markAllNotificationsRead: () =>
    apiRequest('/notifications/read-all', { method: 'POST' }),

  registerDevice: (token: string, platform: 'ios' | 'android' | 'web') =>
    apiRequest('/devices', {
      method: 'POST',
      body: JSON.stringify({ token, platform, app_version: '1.0.0' }),
    }),
};
