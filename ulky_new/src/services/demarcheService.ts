import { api } from '@/services/api';

export type DemarcheStatus =
  | 'submitted'
  | 'in_review'
  | 'additional_info'
  | 'approved'
  | 'rejected'
  | 'closed';

export type Demarche = {
  id: number;
  reference: string;
  type: string;
  title: string;
  description: string | null;
  status: DemarcheStatus;
  fee_amount: number;
  fee_amount_formatted: string;
  payment_status: 'unpaid' | 'paid';
  paid_at: string | null;
  created_at: string;
};

export type CreateDemarcheInput = {
  type: string;
  title: string;
  description?: string;
};

export async function getDemarches(): Promise<Demarche[]> {
  const response = await api.get('/requests');
  return response.data.data as Demarche[];
}

export async function createDemarche(input: CreateDemarcheInput): Promise<Demarche> {
  const response = await api.post('/requests', input);
  return response.data.data as Demarche;
}

export async function payDemarche(id: number, operator: string, phone: string): Promise<unknown> {
  const response = await api.post(`/requests/${id}/pay`, { operator, phone });
  return response.data;
}
