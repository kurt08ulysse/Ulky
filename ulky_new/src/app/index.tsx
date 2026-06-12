import { ActivityIndicator, Image, Text, View } from 'react-native';
import { Redirect } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useAuth } from '@clerk/expo';
import { colors, spacing, typography } from '@/theme';

export default function SplashScreen() {
  const { isSignedIn, isLoaded } = useAuth();

  if (isLoaded) {
    return <Redirect href={isSignedIn ? '/(app)' : '/(auth)/login'} />;
  }

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

      <ActivityIndicator size="large" color={colors.primary[600]} />
    </View>
  );
}
