import { API_URL } from '../config';
import { clearTokens, getAccessToken, getRefreshToken, setTokens } from '../auth/storage';
import type { AuthPayload } from './types';

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

export const api = {
  login: (username: string, password: string) =>
    apiRequest<AuthPayload>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    }, false),

  me: () => apiRequest<import('./types').ApiUser>('/auth/me'),

  logout: async (refreshToken: string | null) => {
    try {
      await apiRequest('/auth/logout', {
        method: 'POST',
        body: JSON.stringify({ refresh_token: refreshToken }),
      }, false);
    } finally {
      await clearTokens();
    }
  },

  tables: () => apiRequest<import('./types').TableRow[]>('/tables'),
  occupyTable: (id: number) =>
    apiRequest(`/tables/${id}/occupy`, { method: 'POST' }),
  payTable: (id: number, payments: { payment_method: string; amount: number }[]) =>
    apiRequest(`/tables/${id}/pay`, {
      method: 'POST',
      body: JSON.stringify({ payments }),
    }),

  products: () =>
    apiRequest<import('./types').ProductRow[]>('/products?per_page=100&type=PRODUCT&is_active=1').catch(
      async () => {
        // paginated envelope may nest differently — fallback raw list from data
        const token = await getAccessToken();
        const res = await fetch(`${API_URL}/products?per_page=100&type=PRODUCT&is_active=1`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        const json = await res.json();
        return (json.data ?? []) as import('./types').ProductRow[];
      },
    ),

  createOrder: (body: Record<string, unknown>) =>
    apiRequest<Record<string, unknown>>('/orders', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  orders: (status?: string) =>
    apiRequest<import('./types').OrderRow[]>(
      status ? `/orders?status=${encodeURIComponent(status)}&per_page=50` : '/orders?per_page=50',
    ).catch(async () => {
      const token = await getAccessToken();
      const res = await fetch(
        `${API_URL}/orders?per_page=50${status ? `&status=${encodeURIComponent(status)}` : ''}`,
        { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } },
      );
      const json = await res.json();
      return (json.data ?? []) as import('./types').OrderRow[];
    }),

  sendToKitchen: (id: number) =>
    apiRequest(`/orders/${id}/send-to-kitchen`, { method: 'POST' }),

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

  order: (id: number) => apiRequest<import('./types').OrderRow>(`/orders/${id}`),

  dashboard: () => apiRequest<import('./types').DashboardPayload>('/dashboard'),

  stock: (search?: string) =>
    apiRequest<import('./types').StockRow[]>(
      search ? `/stock?search=${encodeURIComponent(search)}` : '/stock',
    ),

  stockMovements: (productId?: number) =>
    apiRequest<import('./types').StockMovementRow[]>(
      productId ? `/stock/movements?product_id=${productId}` : '/stock/movements',
    ),

  createStockMovement: (body: {
    product_id: number;
    type: 'ENTRADA' | 'SALIDA' | 'AJUSTE';
    quantity: number;
    reason?: string;
  }) =>
    apiRequest('/stock/movements', {
      method: 'POST',
      body: JSON.stringify(body),
    }),

  cashSummary: () =>
    apiRequest<{
      session: Record<string, unknown> | null;
      sales_total: number;
      payments_count: number;
      expected_amount?: number;
    }>('/cash/summary'),

  cashRegisters: () =>
    apiRequest<Array<{ id: number; name: string }>>('/cash/registers'),

  openCash: (registerId: number, initial_amount: number) =>
    apiRequest(`/cash/registers/${registerId}/open`, {
      method: 'POST',
      body: JSON.stringify({ initial_amount }),
    }),

  closeCash: (final_amount: number, notes?: string) =>
    apiRequest('/cash/session/close', {
      method: 'POST',
      body: JSON.stringify({ final_amount, notes }),
    }),

  registerDevice: (token: string, platform: 'ios' | 'android' | 'web') =>
    apiRequest('/devices', {
      method: 'POST',
      body: JSON.stringify({ token, platform, app_version: '1.0.0' }),
    }),
};
