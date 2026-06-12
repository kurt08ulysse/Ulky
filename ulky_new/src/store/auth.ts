import { create } from 'zustand';
import { tokenStorage } from '@/services/tokenStorage';

type AuthStore = {
  isAuthenticated: boolean;
  isHydrating: boolean;
  setAuthenticated: (value: boolean) => void;
  hydrate: () => Promise<void>;
};

export const useAuthStore = create<AuthStore>((set) => ({
  isAuthenticated: false,
  isHydrating: true,
  setAuthenticated: (value) => set({ isAuthenticated: value }),
  hydrate: async () => {
    try {
      const token = await tokenStorage.get();
      set({ isAuthenticated: !!token, isHydrating: false });
    } catch {
      set({ isAuthenticated: false, isHydrating: false });
    }
  },
}));
