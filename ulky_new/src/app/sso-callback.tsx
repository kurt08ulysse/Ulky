import { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { Redirect, useRouter } from 'expo-router';
import { useAuth } from '@clerk/expo';
import { loginWithClerkToken } from '@/services/auth';
import { useAuthStore } from '@/store/auth';

export default function SSOCallback() {
  const router = useRouter();
  const { isSignedIn, getToken } = useAuth();
  const { isAuthenticated, setAuthenticated } = useAuthStore();
  const exchangedRef = useRef(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function exchange() {
      if (!isSignedIn || exchangedRef.current) return;
      exchangedRef.current = true;
      try {
        const token = await getToken();
        if (!token) throw new Error('No Clerk token available');
        await loginWithClerkToken(token);
        setAuthenticated(true);
        router.replace('/(app)');
      } catch (e) {
        setError(e instanceof Error ? e.message : 'Échec connexion backend');
        exchangedRef.current = false;
      }
    }
    exchange();
  }, [isSignedIn, getToken, setAuthenticated, router]);

  if (isAuthenticated) {
    return <Redirect href="/(app)" />;
  }

  return (
    <View className="flex-1 items-center justify-center bg-white gap-4 px-6">
      <ActivityIndicator size="large" color="#10B981" />
      <Text className="text-gray-500 text-sm text-center">
        Finalisation de la connexion…
      </Text>
      {error && <Text className="text-red-500 text-xs text-center">{error}</Text>}
    </View>
  );
}
