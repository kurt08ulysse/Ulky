import { useEffect } from 'react';
import { ActivityIndicator, View } from 'react-native';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ClerkProvider, useAuth } from '@clerk/expo';
import { tokenCache } from '@clerk/expo/token-cache';
import { registerClerkTokenGetter } from '@/services/api';
import '../global.css';

const queryClient = new QueryClient();

const CLERK_PUBLISHABLE_KEY = 'pk_test_bWVycnktZXdlLTk5LmNsZXJrLmFjY291bnRzLmRldiQ';

function TokenBridge({ children }: { children: React.ReactNode }) {
  const { isLoaded, getToken } = useAuth();

  useEffect(() => {
    registerClerkTokenGetter(() => getToken());
    return () => registerClerkTokenGetter(null);
  }, [getToken]);

  if (!isLoaded) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#ffffff' }}>
        <ActivityIndicator size="large" color="#10B981" />
      </View>
    );
  }

  return <>{children}</>;
}

export default function RootLayout() {
  return (
    <ClerkProvider publishableKey={CLERK_PUBLISHABLE_KEY} tokenCache={tokenCache}>
      <QueryClientProvider client={queryClient}>
        <TokenBridge>
          <Stack screenOptions={{ headerShown: false }}>
            <Stack.Screen name="index" />
            <Stack.Screen name="(auth)" />
            <Stack.Screen name="(app)" />
            <Stack.Screen name="sso-callback" />
          </Stack>
          <StatusBar style="dark" />
        </TokenBridge>
      </QueryClientProvider>
    </ClerkProvider>
  );
}