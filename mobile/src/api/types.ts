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
  sector_id?: number | null;
  current_session_id?: number | null;
  current_order_id?: number | null;
  waiter?: string | null;
};

export type TableDetail = {
  table: TableRow;
  orders: OrderRow[];
  receipt?: {
    subtotal?: number;
    discount?: number;
    total?: number;
    items_count?: number;
    [key: string]: unknown;
  } | null;
};

export type ProductRow = {
  id: number;
  name: string;
  price: number | string;
  type?: string;
  is_active?: boolean;
  category_id?: number | null;
  description?: string | null;
  has_stock?: boolean;
  stock_minimum?: number;
  current_stock?: number;
  unit?: string | null;
  cost_price?: number | string | null;
  supplier_id?: number | null;
};

export type OrderItem = {
  id: number;
  name?: string;
  product_name?: string;
  product?: { name?: string; id?: number };
  quantity: number;
  status?: string;
  unit_price?: number | string;
  observations?: string | null;
};

export type OrderRow = {
  id: number;
  number: string;
  status: string;
  total?: number | string;
  subtotal?: number | string;
  discount?: number | string;
  observations?: string | null;
  table_id?: number | null;
  table?: { number?: string; id?: number } | string | null;
  items?: OrderItem[];
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
  reference?: string | null;
  product?: string;
  product_id?: number;
  user?: string;
  created_at?: string;
  purchase?: {
    supplier_id?: number | null;
    supplier?: string | null;
    unit_cost?: number | null;
    total_cost?: number | null;
    purchase_date?: string | null;
    invoice_number?: string | null;
  } | null;
};

export type DiscountTypeRow = {
  id: number;
  name: string;
  percentage: number | string;
  description?: string | null;
  is_active?: boolean;
};

export type CatalogCategory = {
  id: number;
  name: string;
  description?: string | null;
  display_order?: number | null;
  is_active?: boolean;
};

export type CatalogSector = {
  id: number;
  name: string;
  description?: string | null;
  is_active?: boolean;
};

export type ClientRow = {
  id: number;
  name: string;
  phone?: string | null;
  email?: string | null;
  notes?: string | null;
};

export type CashSessionRow = {
  id: number;
  status: string;
  register?: string | null;
  user?: string | null;
  initial_amount: number;
  final_amount?: number | null;
  opened_at?: string | null;
  closed_at?: string | null;
};

export type CashSessionDetail = {
  session: Record<string, unknown>;
  sales_total: number;
  ingresos: number;
  egresos: number;
  expected_amount: number;
  payments: Array<{
    id: number;
    amount: number;
    payment_method: string;
    order_number?: string | null;
    table?: string | null;
    created_at?: string | null;
  }>;
  movements: Array<{
    id: number;
    type: string;
    amount: number;
    description?: string | null;
    reference?: string | null;
    user_id?: number | null;
    can_delete?: boolean;
    created_at?: string | null;
  }>;
  sales_detail?: Array<{
    id: number;
    number?: string | null;
    total: number;
    customer_name?: string | null;
    table?: string | number | null;
    user?: string | null;
    created_at?: string | null;
    items: Array<{
      id: number;
      product?: string | null;
      product_id?: number;
      quantity: number;
      unit_price: number;
      subtotal: number;
      has_stock?: boolean;
    }>;
  }>;
};

export type SalesReport = {
  date_from: string;
  date_to: string;
  total_sales: number;
  total_orders: number;
  sales_by_day: Array<{ day: string; total: number; count: number }>;
  sales_by_method: Array<{ payment_method: string; total: number }>;
};

export type ProductsReport = {
  date_from: string;
  date_to: string;
  top_products: Array<{
    id: number;
    name: string;
    total_quantity: number;
    total_revenue: number;
  }>;
};

export type StaffReport = {
  date_from: string;
  date_to: string;
  sales_by_staff: Array<{
    id: number;
    name: string;
    total_orders: number;
    total_sales: number;
  }>;
};

export type LayoutTable = {
  id: number;
  number: string;
  status: string;
  capacity?: number;
  position_x: number;
  position_y: number;
  waiter?: string | null;
  current_order_id?: number | null;
};

export type TableLayoutPayload = {
  sector_id: number | null;
  sectors: Array<{ id: number; name: string }>;
  tables: LayoutTable[];
  fixtures?: unknown[];
  canvas: { width: number; height: number };
};

export type EventRow = {
  id: number;
  name: string;
  date?: string;
  time?: string | null;
  status?: string;
  description?: string | null;
  expected_attendance?: number | null;
};

export type RecurringRow = {
  id: number;
  name: string;
  description?: string | null;
  day_of_week: string;
  start_time: string;
  end_time?: string | null;
  expected_attendance?: number | null;
  start_date?: string | null;
  end_date?: string | null;
  is_active?: boolean;
};

export type ExpenseRow = {
  id: number;
  name: string;
  type?: string;
  category?: string;
  amount?: number | string;
  frequency?: string;
  is_active?: boolean;
  description?: string | null;
  due_day?: number | null;
  start_date?: string;
  end_date?: string | null;
};

export type NotificationRow = {
  id: string;
  type: string;
  data?: Record<string, unknown>;
  read_at?: string | null;
  created_at?: string | null;
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
    open_cash_session_labels?: string[];
    ventas_hoy: number;
    ventas_sesion: number;
    tiene_sesion_abierta?: boolean;
    pedidos_pendientes: number;
    recent_stock_movements?: StockMovementRow[];
  } | null;
  insights?: {
    recent_orders: Array<{
      id: number;
      number: string;
      status: string;
      total: number;
      table?: string;
      waiter?: string;
    }>;
    top_products: Array<{ name: string; total_quantity: number }>;
    low_stock_list: Array<{
      id: number;
      name: string;
      current_stock: number;
      stock_minimum: number;
    }>;
    out_of_stock_list: Array<{ id: number; name: string }>;
    sales_by_waiter: Array<{ name: string; total_sales: number; payment_count: number }>;
    income_by_method: Array<{ payment_method: string; total: number }>;
    active_tables: Array<{ id: number; number: string; sector?: string; waiter?: string }>;
    today_orders: number;
  } | null;
};
