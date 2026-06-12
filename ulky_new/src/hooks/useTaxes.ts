import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getTaxes, getTaxNotices, getTaxNotice, cancelTaxNotice, payTaxNotice } from '@/services/taxService';

export function useTaxes() {
  return useQuery({
    queryKey: ['taxes'],
    queryFn: getTaxes,
  });
}

export function useTaxNotices() {
  return useQuery({
    queryKey: ['tax-notices'],
    queryFn: getTaxNotices,
  });
}

export function useTaxNotice(id: number) {
  return useQuery({
    queryKey: ['tax-notices', id],
    queryFn: () => getTaxNotice(id),
    enabled: !!id,
  });
}

export function useCancelTaxNotice() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: cancelTaxNotice,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['tax-notices'] });
      queryClient.invalidateQueries({ queryKey: ['tax-notices', data.id] });
    },
  });
}

export function usePayTaxNotice() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, operator, phone }: { id: number; operator: string; phone: string }) =>
      payTaxNotice(id, operator, phone),
    onSuccess: (data, variables) => {
      queryClient.invalidateQueries({ queryKey: ['tax-notices'] });
      queryClient.invalidateQueries({ queryKey: ['tax-notices', variables.id] });
    },
  });
}
