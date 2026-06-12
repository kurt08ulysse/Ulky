import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'auth_token';

export const tokenStorage = Platform.OS === 'web' ? {
  get: async (): Promise<string | null> => {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  },
  save: async (token: string): Promise<void> => {
    try {
      localStorage.setItem(TOKEN_KEY, token);
    } catch {}
  },
  remove: async (): Promise<void> => {
    try {
      localStorage.removeItem(TOKEN_KEY);
    } catch {}
  },
} : {
  get: (): Promise<string | null> => SecureStore.getItemAsync(TOKEN_KEY),
  save: (token: string): Promise<void> => SecureStore.setItemAsync(TOKEN_KEY, token),
  remove: (): Promise<void> => SecureStore.deleteItemAsync(TOKEN_KEY),
};

