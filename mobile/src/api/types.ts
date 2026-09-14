export type ApiUser = {
  id: number;
  name: string;
  username: string;
  email?: string;
  role: string;
  restaurant_id: number | null;
  is_active: boolean;
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
};
