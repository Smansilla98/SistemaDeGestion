export type ApiUser = {
  id: number;
  name: string;
  username: string;
  email?: string;
  role: string;
  restaurant_id: number | null;
  is_active: boolean;
  permissions?: string[];
};

export type AuthPayload = {
  access_token: string;
  token_type: string;
  expires_in: number;
  refresh_token?: string;
  refresh_expires_in?: number;
  user: ApiUser;
};

export type TableRow = {
  id: number;
  number: string;
  status: string;
  capacity?: number;
  sector?: string | null;
  current_session_id?: number | null;
};

export type ProductRow = {
  id: number;
  name: string;
  price: number | string;
  type?: string;
  is_active?: boolean;
};

export type OrderRow = {
  id: number;
  number: string;
  status: string;
  total?: number | string;
  table_id?: number | null;
  table?: { number?: string } | string | null;
  items?: Array<{
    id: number;
    name?: string;
    product_name?: string;
    quantity: number;
    status?: string;
    unit_price?: number | string;
  }>;
};

export type StockRow = {
  id: number;
  name: string;
  type?: string;
  current_stock: number;
  stock_minimum: number;
  is_low_stock: boolean;
  unit?: string | null;
};

export type StockMovementRow = {
  id: number;
  type: string;
  quantity: number;
  previous_stock?: number;
  new_stock?: number;
  reason?: string | null;
  product?: string;
  product_id?: number;
  user?: string;
  created_at?: string;
};

export type DashboardPayload = {
  role: string;
  operational: {
    mesas_libres: number;
    mesas_ocupadas: number;
    total_tables: number;
    pedidos_pendientes: number;
    ventas_sesion: number;
    tiene_sesion_abierta: boolean;
    low_stock_products: number;
  };
  management: {
    low_stock_products: number;
    stock_ok_products: number;
    open_cash_sessions: number;
    ventas_hoy: number;
    ventas_sesion: number;
    pedidos_pendientes: number;
    recent_stock_movements?: StockMovementRow[];
  } | null;
};
