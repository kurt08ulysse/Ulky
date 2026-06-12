import React from 'react';
import { Text, View } from 'react-native';
import { Redirect, Tabs } from 'expo-router';
import { useAuth } from '@clerk/expo';
import { useQuery } from '@tanstack/react-query';
import { getMe } from '@/services/auth';
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

function AdminIcon({ color }: { color: any }) {
  return <Text style={{ fontSize: 20 }}>⚙️</Text>;
}

export default function AppLayout() {
  const { isLoaded, isSignedIn } = useAuth();

  // Récupère le profil pour déterminer si l'onglet Admin doit être affiché
  const { data: me } = useQuery({
    queryKey: ['me'],
    queryFn: getMe,
    enabled: isLoaded && !!isSignedIn,
    staleTime: 5 * 60_000,
  });

  const isAdmin = me?.roles?.some((r) =>
    ['municipal_agent', 'cashier', 'commune_admin', 'super_admin'].includes(r)
  ) ?? false;

  if (!isLoaded) return null;
  if (!isSignedIn) {
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

      {/* Onglet Admin — visible uniquement pour les agents/admins */}
      <Tabs.Screen
        name="admin"
        options={{
          title: 'Admin',
          tabBarLabel: 'Admin',
          tabBarIcon: ({ color }) => <AdminIcon color={color} />,
          // Caché pour les citoyens — href={null} supprime l'onglet sans erreur de route
          href: isAdmin ? undefined : null,
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
