import { api } from '@/services/api';

export type Stall = {
  id: number;
  stall_number: string;
  stall_type: string;
  rent_amount_cents: number;
  rent_amount_formatted: string;
  status: string;
  market: { id: number; name: string; address: string | null; commune_id: number | null } | null;
};

export type RentStatus = 'pending' | 'paid' | 'cancelled' | 'late';

export type StallRent = {
  id: number;
  amount_cents: number;
  amount_formatted: string;
  period: string;
  status: RentStatus;
  due_date: string;
  paid_at: string | null;
  stall: {
    id: number;
    stall_number: string;
    stall_type: string;
    market: { id: number; name: string } | null;
  } | null;
};

export async function getMyStalls(): Promise<Stall[]> {
  const response = await api.get('/my/stalls');
  return response.data.data as Stall[];
}

export async function getMyRents(): Promise<StallRent[]> {
  const response = await api.get('/my/rents');
  return response.data.data as StallRent[];
}

export async function payRent(id: number, operator: string, phone: string): Promise<unknown> {
  const response = await api.post(`/rents/${id}/pay`, { operator, phone });
  return response.data;
}
