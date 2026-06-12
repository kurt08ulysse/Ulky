import React from 'react';
import { Text, View } from 'react-native';
import { Redirect, Tabs } from 'expo-router';
import { useAuthStore } from '@/store/auth';
import { colors, spacing } from '@/theme';

// Icon components (emoji-based for simplicity)
function HomeIcon({ color }: { color: any }) {
  return <Text style={{ fontSize: 20 }}>🏠</Text>;
}

function ProfileIcon({ color }: { color: any }) {
  return <Text style={{ fontSize: 20 }}>👤</Text>;
}

function TaxesIcon({ color }: { color: any }) {
  return <Text style={{ fontSize: 20 }}>💰</Text>;
}

export default function AppLayout() {
  const { isAuthenticated } = useAuthStore();

  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary[600],
        tabBarInactiveTintColor: colors.text.tertiary,
        tabBarStyle: {
          backgroundColor: colors.background.default,
          borderTopWidth: 1,
          borderTopColor: colors.border.light,
          paddingBottom: spacing.sm,
          height: 56 + spacing.lg,
        },
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '500',
          marginTop: 4,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Accueil',
          tabBarLabel: 'Accueil',
          tabBarIcon: ({ color }) => <HomeIcon color={color} />,
        }}
      />

      <Tabs.Screen
        name="taxes"
        options={{
          title: 'Taxes',
          tabBarLabel: 'Taxes',
          tabBarIcon: ({ color }) => <TaxesIcon color={color} />,
        }}
      />

      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profil',
          tabBarLabel: 'Profil',
          tabBarIcon: ({ color }) => <ProfileIcon color={color} />,
        }}
      />
    </Tabs>
  );
}
