import { api } from '@/services/api';

export type Tax = {
  id: number;
  name: string;
  description: string | null;
  base_amount: number;
  stamp_amount: number;
  total_amount: number;
  base_amount_formatted: string;
  stamp_amount_formatted: string;
  total_amount_formatted: string;
  periodicity: 'monthly' | 'quarterly' | 'yearly' | 'one_time';
  commune_id: number | null;
  created_at: string;
  updated_at: string;
};

export type Receipt = {
  id: number;
  payment_id: number;
  receipt_number: string;
  qr_code_token: string;
  pdf_path: string | null;
  created_at: string;
  updated_at: string;
  verification_url: string; // Attribut virtuel renvoyé par l'API
};

export type TaxNotice = {
  id: number;
  tax_id: number;
  user_id: number;
  tax?: Tax;
  receipt?: Receipt;
  base_amount: number;
  stamp_amount: number;
  total_amount: number;
  base_amount_formatted: string;
  stamp_amount_formatted: string;
  total_amount_formatted: string;
  status: 'pending' | 'paid' | 'cancelled';
  due_date: string;
  paid_at: string | null;
  commune_id: number | null;
  created_at: string;
  updated_at: string;
};

export async function getTaxes(): Promise<Tax[]> {
  const response = await api.get('/taxes');
  return response.data.data as Tax[];
}

export async function getTaxNotices(): Promise<TaxNotice[]> {
  const response = await api.get('/tax-notices');
  return response.data.data as TaxNotice[];
}

export async function getTaxNotice(id: number): Promise<TaxNotice> {
  const response = await api.get(`/tax-notices/${id}`);
  return response.data.data as TaxNotice;
}

export async function cancelTaxNotice(id: number): Promise<TaxNotice> {
  const response = await api.put(`/tax-notices/${id}/cancel`);
  return response.data.data as TaxNotice;
}

export async function payTaxNotice(id: number, operator: string, phone: string): Promise<any> {
  const response = await api.post(`/tax-notices/${id}/pay`, { operator, phone });
  return response.data;
}

