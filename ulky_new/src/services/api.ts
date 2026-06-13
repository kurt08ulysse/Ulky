import Constants from 'expo-constants';
import axios from 'axios';

const fallbackBaseUrl = 'https://cables-operating-sunset-registered.trycloudflare.com/api/v1';

const backendUrl =
  (Constants.expoConfig?.extra as { BACKEND_API_URL?: string })?.BACKEND_API_URL ??
  fallbackBaseUrl;

export const api = axios.create({
  baseURL: backendUrl,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 10000,
});

type ClerkTokenGetter = () => Promise<string | null>;

let clerkTokenGetter: ClerkTokenGetter | null = null;

export function registerClerkTokenGetter(getter: ClerkTokenGetter | null): void {
  clerkTokenGetter = getter;
}

/**
 * Récupère le jeton Clerk courant pour les appels qui ne passent pas par
 * l'instance Axios (ex. téléchargement de fichier authentifié sur web/mobile).
 */
export async function getAuthToken(): Promise<string | null> {
  return clerkTokenGetter ? clerkTokenGetter() : null;
}

api.interceptors.request.use(async (config) => {
  if (clerkTokenGetter) {
    const token = await clerkTokenGetter();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

export const BACKEND_API_URL = backendUrl;
