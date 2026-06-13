import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createReport, getReports, type CreateReportInput } from '@/services/reportService';

export function useReports() {
  return useQuery({
    queryKey: ['reports'],
    queryFn: getReports,
  });
}

export function useCreateReport() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (input: CreateReportInput) => createReport(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] });
    },
  });
}
