import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import {
  getDashboardKpis,
  getAdminTaxNotices,
  getAdminTaxNotice,
  createAdminTaxNotice,
  searchCitizen,
  getAuditLogs,
  exportCsv,
} from '@/services/adminService';

// ─── Dashboard KPIs ──────────────────────────────────────────────────────────

export function useAdminDashboard() {
  return useQuery({
    queryKey: ['admin', 'dashboard'],
    queryFn: getDashboardKpis,
    staleTime: 60_000, // 1 minute
    refetchInterval: 120_000, // rafraîchi toutes les 2 min
  });
}

// ─── Tax Notices ─────────────────────────────────────────────────────────────

export function useAdminTaxNotices(filters?: {
  status?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
}) {
  return useQuery({
    queryKey: ['admin', 'tax-notices', filters],
    queryFn: () => getAdminTaxNotices({ ...filters, per_page: 20 }),
    placeholderData: keepPreviousData,
  });
}

export function useAdminTaxNotice(id: number | null) {
  return useQuery({
    queryKey: ['admin', 'tax-notices', id],
    queryFn: () => getAdminTaxNotice(id!),
    enabled: id !== null,
  });
}

export function useCreateAdminTaxNotice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: createAdminTaxNotice,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'tax-notices'] });
      qc.invalidateQueries({ queryKey: ['admin', 'dashboard'] });
    },
  });
}

// ─── Citizen Search ───────────────────────────────────────────────────────────

export function useCitizenSearch(phone: string) {
  return useQuery({
    queryKey: ['admin', 'citizens', phone],
    queryFn: () => searchCitizen(phone),
    enabled: phone.length >= 4,
    staleTime: 30_000,
  });
}

// ─── Audit Logs ───────────────────────────────────────────────────────────────

export function useAuditLogs(filters?: {
  action?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
}) {
  return useQuery({
    queryKey: ['admin', 'audit-logs', filters],
    queryFn: () => getAuditLogs(filters),
    placeholderData: keepPreviousData,
  });
}

// ─── Export CSV ───────────────────────────────────────────────────────────────

export function useExportCsv() {
  return useMutation({
    mutationFn: exportCsv,
  });
}
