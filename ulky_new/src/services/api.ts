import Constants from 'expo-constants';
import axios from 'axios';
import { tokenStorage } from '@/services/tokenStorage';
import { useAuthStore } from '@/store/auth';

const fallbackBaseUrl = 'http://192.168.1.89:8001/api/v1';

const backendUrl =
  (Constants.expoConfig?.extra as { BACKEND_API_URL?: string })?.BACKEND_API_URL ??
  fallbackBaseUrl;

export const api = axios.create({
  baseURL: backendUrl,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(async (config) => {
  const token = await tokenStorage.get();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await tokenStorage.remove();
      useAuthStore.getState().setAuthenticated(false);
    }
    return Promise.reject(error);
  }
);

export const BACKEND_API_URL = backendUrl;
