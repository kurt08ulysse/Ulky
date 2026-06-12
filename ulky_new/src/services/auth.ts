import { api } from '@/services/api';
import { tokenStorage } from '@/services/tokenStorage';

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  phone?: string;
  taxpayer_type?: 'individual' | 'business';
  roles: string[];
  commune_id?: number | null;
};

export async function loginWithClerkToken(sessionToken: string): Promise<AuthUser> {
  const response = await api.post('/auth/clerk/token', { session_token: sessionToken });
  const { data } = response.data;
  await tokenStorage.save(data.token);
  return data.user as AuthUser;
}

export async function logout(): Promise<void> {
  await api.post('/auth/logout');
  await tokenStorage.remove();
}

export async function getMe(): Promise<AuthUser> {
  const response = await api.get('/auth/me');
  return response.data.data as AuthUser;
}
