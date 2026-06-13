import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { getMyRents, getMyStalls, payRent } from '@/services/marketService';

export function useMyStalls() {
  return useQuery({
    queryKey: ['my-stalls'],
    queryFn: getMyStalls,
  });
}

export function useMyRents() {
  return useQuery({
    queryKey: ['my-rents'],
    queryFn: getMyRents,
  });
}

export function usePayRent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, operator, phone }: { id: number; operator: string; phone: string }) =>
      payRent(id, operator, phone),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['my-rents'] });
    },
  });
}
