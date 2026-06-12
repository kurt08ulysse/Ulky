import { api } from '@/services/api';

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  phone?: string;
  taxpayer_type?: 'individual' | 'business';
  roles: string[];
  commune_id?: number | null;
};

export async function getMe(): Promise<AuthUser> {
  const response = await api.get('/auth/me');
  return response.data.data as AuthUser;
}

export async function logoutBackend(): Promise<void> {
  await api.post('/auth/logout');
}
