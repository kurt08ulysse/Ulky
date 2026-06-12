import { useEffect } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { Redirect, useRouter } from 'expo-router';
import { useAuth } from '@clerk/expo';

export default function SSOCallback() {
  const router = useRouter();
  const { isLoaded, isSignedIn } = useAuth();

  useEffect(() => {
    if (isLoaded && isSignedIn) {
      router.replace('/(app)');
    }
  }, [isLoaded, isSignedIn, router]);

  if (isLoaded && isSignedIn) {
    return <Redirect href="/(app)" />;
  }

  return (
    <View className="flex-1 items-center justify-center bg-white gap-4 px-6">
      <ActivityIndicator size="large" color="#10B981" />
      <Text className="text-gray-500 text-sm text-center">
        Finalisation de la connexion…
      </Text>
    </View>
  );
}
