import { useQuery } from '@tanstack/react-query';
import { getOfficials } from '@/services/officialService';

export function useOfficials() {
  return useQuery({
    queryKey: ['officials'],
    queryFn: getOfficials,
    staleTime: 10 * 60_000,
  });
}
