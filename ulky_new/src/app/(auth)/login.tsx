import { useState } from 'react';
import { Alert, ActivityIndicator, Image, Pressable, ScrollView, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import * as WebBrowser from 'expo-web-browser';
import * as AuthSession from 'expo-auth-session';
import { useSSO, useAuth } from '@clerk/expo';
import { loginWithClerkToken } from '@/services/auth';
import { useAuthStore } from '@/store/auth';

WebBrowser.maybeCompleteAuthSession();

export default function LoginScreen() {
  const router = useRouter();
  const { setAuthenticated } = useAuthStore();
  const [loading, setLoading] = useState(false);

  const { startSSOFlow } = useSSO();
  const { getToken } = useAuth();

  async function handleGoogleSignIn() {
    setLoading(true);
    try {
      const redirectUrl = AuthSession.makeRedirectUri({ path: 'sso-callback' });
      const { createdSessionId, setActive } = await startSSOFlow({
        strategy: 'oauth_google',
        redirectUrl,
      });

      if (createdSessionId && setActive) {
        await setActive({ session: createdSessionId });
        const clerkToken = await getToken();
        if (clerkToken) {
          await loginWithClerkToken(clerkToken);
          setAuthenticated(true);
          router.replace('/(app)');
        } else {
          Alert.alert('Erreur', 'Impossible de récupérer le jeton de session.');
        }
      }
    } catch (error) {
      const message =
        error instanceof Error ? error.message : 'Échec de la connexion. Réessayez.';
      Alert.alert('Connexion échouée', message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <ScrollView className="flex-1 bg-white" contentContainerClassName="flex-grow">
      <StatusBar style="light" />

      <View className="bg-emerald-700 px-6 pt-16 pb-12 items-center gap-3">
        <Image
          source={require('../../../assets/images/icon.png')}
          style={{ width: 72, height: 72, borderRadius: 16 }}
        />
        <Text className="text-3xl font-bold text-white tracking-wide">ULKY</Text>
        <Text className="text-emerald-100 text-sm text-center">
          Services municipaux numériques
        </Text>
      </View>

      <View className="flex-1 px-6 pt-10 gap-6">
        <View className="gap-1">
          <Text className="text-2xl font-bold text-gray-900">Bienvenue</Text>
          <Text className="text-gray-500 text-base leading-relaxed">
            Connectez-vous pour accéder à vos services municipaux, payer vos taxes
            et suivre vos démarches.
          </Text>
        </View>

        <Pressable
          className="flex-row items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white py-4 px-5"
          style={{ elevation: 2 }}
          disabled={loading}
          onPress={handleGoogleSignIn}
        >
          {loading ? (
            <ActivityIndicator size="small" color="#374151" />
          ) : (
            <>
              <Text className="text-lg font-bold" style={{ color: '#4285F4' }}>G</Text>
              <Text className="text-base font-semibold text-gray-700">
                Continuer avec Google
              </Text>
            </>
          )}
        </Pressable>

        <View className="flex-row items-center gap-3">
          <View className="flex-1 h-px bg-gray-200" />
          <Text className="text-xs text-gray-400">ou bientôt par SMS</Text>
          <View className="flex-1 h-px bg-gray-200" />
        </View>

        <Pressable
          className="flex-row items-center justify-center gap-3 rounded-xl bg-gray-100 py-4 px-5"
          disabled
        >
          <Text className="text-base font-semibold text-gray-400">
            Se connecter par téléphone
          </Text>
        </Pressable>
      </View>

      <View className="px-6 pb-10 pt-4 items-center">
        <Text className="text-xs text-gray-400 text-center">
          En vous connectant, vous acceptez les conditions d'utilisation
          et la politique de confidentialité d'ULKY.
        </Text>
      </View>
    </ScrollView>
  );
}
