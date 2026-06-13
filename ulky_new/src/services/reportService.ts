import { api } from '@/services/api';

export type ReportStatus =
  | 'new'
  | 'acknowledged'
  | 'in_progress'
  | 'resolved'
  | 'rejected'
  | 'closed';

export type ReportCategory = 'voirie' | 'eclairage' | 'dechets' | 'eau' | 'securite' | 'autre';

export type CitizenReport = {
  id: number;
  reference: string;
  category: ReportCategory;
  title: string;
  description: string | null;
  status: ReportStatus;
  latitude: number | null;
  longitude: number | null;
  address: string | null;
  created_at: string;
};

export type CreateReportInput = {
  category: ReportCategory;
  title: string;
  description?: string;
  address?: string;
  latitude?: number;
  longitude?: number;
};

export async function getReports(): Promise<CitizenReport[]> {
  const response = await api.get('/reports');
  return response.data.data as CitizenReport[];
}

export async function createReport(input: CreateReportInput): Promise<CitizenReport> {
  const response = await api.post('/reports', input);
  return response.data.data as CitizenReport;
}
