import { api, BACKEND_API_URL, getAuthToken } from '@/services/api';
import { Platform } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

// ─── Types ───────────────────────────────────────────────────────────────────

export type DashboardKpis = {
  collected: {
    today: { amount: number; formatted: string };
    week: { amount: number; formatted: string };
    month: { amount: number; formatted: string };
    total: { amount: number; formatted: string };
  };
  expenses: {
    month: { amount: number; formatted: string };
    total: { amount: number; formatted: string };
  };
  net: { amount: number; formatted: string };
  notices_by_status: {
    pending: number;
    paid: number;
    cancelled: number;
  };
  top_taxes: {
    tax_name: string;
    count: number;
    total: number;
    total_formatted: string;
  }[];
  chart_data: {
    date: string;
    label: string;
    total: number;
  }[];
};

export type CitizenPreview = {
  id: number;
  name: string;
  phone: string;
  email: string | null;
  taxpayer_type: string | null;
  commune_id: number | null;
};

export type AdminTaxNotice = {
  id: number;
  tax_id: number;
  user_id: number;
  tax?: { id: number; name: string; periodicity: string };
  citizen?: {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    taxpayer_type: string | null;
  };
  base_amount: number;
  stamp_amount: number;
  total_amount: number;
  base_amount_formatted: string;
  stamp_amount_formatted: string;
  total_amount_formatted: string;
  status: 'pending' | 'paid' | 'cancelled';
  due_date: string;
  paid_at: string | null;
  payment?: {
    id: number;
    transaction_id: string | null;
    operator: string;
    phone: string;
    status: string;
    created_at: string;
  } | null;
  receipt?: {
    id: number;
    number: string | null;
    verification_url: string | null;
  } | null;
  commune_id: number | null;
  created_at: string;
  updated_at: string;
};

export type AuditLogEntry = {
  id: number;
  actor_type: string | null;
  actor_id: number | null;
  action: string;
  subject_type: string | null;
  subject_id: number | null;
  payload: Record<string, any> | null;
  ip: string | null;
  created_at: string;
};

export type Expense = {
  id: number;
  reference: string;
  category: string;
  label: string;
  amount: number;
  amount_formatted: string;
  spent_at: string | null;
  note: string | null;
  created_at: string;
};

export type CreateExpenseInput = {
  category: string;
  label: string;
  amount: number;
  spent_at?: string;
  note?: string;
};

export type PaginatedResponse<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

// ─── API Functions ────────────────────────────────────────────────────────────

const ADMIN_BASE = '/v1/admin';

/** Tableau de bord KPIs */
export async function getDashboardKpis(): Promise<DashboardKpis> {
  const res = await api.get(`${ADMIN_BASE}/dashboard`);
  return res.data as DashboardKpis;
}

/** Liste paginée de tous les avis de taxes */
export async function getAdminTaxNotices(params?: {
  status?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}): Promise<PaginatedResponse<AdminTaxNotice>> {
  const res = await api.get(`${ADMIN_BASE}/tax-notices`, { params });
  return res.data as PaginatedResponse<AdminTaxNotice>;
}

/** Détail d'un avis */
export async function getAdminTaxNotice(id: number): Promise<AdminTaxNotice> {
  const res = await api.get(`${ADMIN_BASE}/tax-notices/${id}`);
  return res.data.data as AdminTaxNotice;
}

/** Créer un avis pour un citoyen (par numéro de téléphone) */
export async function createAdminTaxNotice(data: {
  tax_id: number;
  phone: string;
  due_date?: string;
  base_amount?: number;
  stamp_amount?: number;
}): Promise<AdminTaxNotice> {
  const res = await api.post(`${ADMIN_BASE}/tax-notices`, data);
  return res.data.data as AdminTaxNotice;
}

/** Recherche d'un citoyen par numéro de téléphone */
export async function searchCitizen(phone: string): Promise<CitizenPreview[]> {
  const res = await api.get(`${ADMIN_BASE}/citizens/search`, { params: { phone } });
  return res.data.data as CitizenPreview[];
}

/** Journal d'audit paginé */
export async function getAuditLogs(params?: {
  action?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
}): Promise<PaginatedResponse<AuditLogEntry>> {
  const res = await api.get(`${ADMIN_BASE}/audit-logs`, { params });
  return res.data as PaginatedResponse<AuditLogEntry>;
}

/** Dépenses de la commune (régisseur). */
export async function getExpenses(params?: { category?: string; page?: number }): Promise<PaginatedResponse<Expense>> {
  const res = await api.get(`${ADMIN_BASE}/expenses`, { params });
  return res.data as PaginatedResponse<Expense>;
}

export async function createExpense(data: CreateExpenseInput): Promise<Expense> {
  const res = await api.post(`${ADMIN_BASE}/expenses`, data);
  return res.data.data as Expense;
}

/**
 * Export CSV.
 * - Sur mobile : télécharge le fichier via expo-file-system + ouvre le Share Sheet.
 * - Sur web : ouvre l'URL directement dans le navigateur pour déclencher le download natif.
 */
export async function exportCsv(params?: {
  date_from?: string;
  date_to?: string;
  status?: string;
}): Promise<void> {
  const query = new URLSearchParams();
  if (params?.date_from) query.set('date_from', params.date_from);
  if (params?.date_to) query.set('date_to', params.date_to);
  if (params?.status) query.set('status', params.status);

  // Même base que l'API mais sans le préfixe /v1. L'endpoint /export/csv est
  // protégé par le guard Clerk : il faut donc présenter le Bearer token, y
  // compris sur web (où window.open ne peut PAS porter d'en-tête Authorization).
  const rawUrl = `${BACKEND_API_URL.replace('/v1', '')}/api/v1/admin/export/csv?${query.toString()}`;
  const filename = `recettes_${params?.date_from ?? 'all'}_${params?.date_to ?? 'all'}.csv`;

  const token = await getAuthToken();
  const headers: Record<string, string> = token ? { Authorization: `Bearer ${token}` } : {};

  if (Platform.OS === 'web') {
    // Sur web : requête authentifiée → blob → téléchargement déclenché par un <a>.
    const response = await fetch(rawUrl, { headers });
    if (!response.ok) {
      throw new Error(`Export CSV échoué (HTTP ${response.status}).`);
    }
    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
    return;
  }

  // Sur mobile : download temporaire authentifié + Share Sheet
  const fileUri = `${FileSystem.cacheDirectory}${filename}`;
  const result = await FileSystem.downloadAsync(rawUrl, fileUri, { headers });

  if (result.status === 200) {
    const canShare = await Sharing.isAvailableAsync();
    if (canShare) {
      await Sharing.shareAsync(result.uri, {
        mimeType: 'text/csv',
        dialogTitle: 'Exporter le rapport',
        UTI: 'public.comma-separated-values-text',
      });
    }
  }
}
