import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  createDemarche,
  getDemarches,
  payDemarche,
  type CreateDemarcheInput,
} from '@/services/demarcheService';

export function useDemarches() {
  return useQuery({
    queryKey: ['demarches'],
    queryFn: getDemarches,
  });
}

export function useCreateDemarche() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (input: CreateDemarcheInput) => createDemarche(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['demarches'] });
    },
  });
}

export function usePayDemarche() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, operator, phone }: { id: number; operator: string; phone: string }) =>
      payDemarche(id, operator, phone),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['demarches'] });
    },
  });
}
