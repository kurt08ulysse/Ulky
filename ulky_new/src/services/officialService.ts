import { api } from '@/services/api';

export type ElectedOfficial = {
  id: number;
  name: string;
  title: string;
  description: string | null;
  photo_url: string | null;
  display_order: number;
};

export async function getOfficials(): Promise<ElectedOfficial[]> {
  const response = await api.get('/officials');
  return response.data.data as ElectedOfficial[];
}
