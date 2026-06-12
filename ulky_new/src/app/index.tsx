import { useEffect } from 'react';
import { ActivityIndicator, Image, Text, View } from 'react-native';
import { useRouter, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useAuth } from '@clerk/expo';
import { colors, spacing, typography } from '@/theme';

export default function SplashScreen() {
  const router = useRouter();
  const segments = useSegments();
  const { isSignedIn, isLoaded } = useAuth();

  useEffect(() => {
    if (!isLoaded) return;

    const inAuthGroup = segments[0] === '(auth)';

    if (isSignedIn && inAuthGroup) {
      router.replace('/(app)');
    } else if (!isSignedIn && !inAuthGroup) {
      router.replace('/(auth)/login');
    }
  }, [isSignedIn, isLoaded, segments]);

  return (
    <View
      style={{
        flex: 1,
        backgroundColor: colors.background.default,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: spacing.lg,
      }}
    >
      <StatusBar style="dark" />

      {/* Logo */}
      <View style={{ marginBottom: spacing.lg, alignItems: 'center' }}>
        <Image
          source={require('../../assets/images/icon.png')}
          style={{ width: 80, height: 80, borderRadius: 16, marginBottom: spacing.md }}
        />
        <Text style={{ ...typography.h2, color: colors.text.primary, textAlign: 'center' }}>
          ULKY
        </Text>
        <Text
          style={{
            ...typography.body,
            color: colors.text.secondary,
            marginTop: spacing.sm,
            textAlign: 'center',
          }}
        >
          Digitalisation des services municipaux
        </Text>
      </View>

      {/* Spinner */}
      <ActivityIndicator size="large" color={colors.primary[600]} />
    </View>
  );
}
