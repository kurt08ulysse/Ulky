import React, { useState } from 'react';
import { Alert, ScrollView, Switch, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuth, useUser } from '@clerk/expo';
import { useQueryClient } from '@tanstack/react-query';
import { logout } from '@/services/auth';
import { useAuthStore } from '@/store/auth';
import { colors, spacing, typography } from '@/theme';
import { Button, Card, Badge } from '@/components';

export default function ProfileScreen() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const { setAuthenticated } = useAuthStore();
  const { signOut } = useAuth();
  const { user } = useUser();

  const [notifications, setNotifications] = useState(true);
  const [sms, setSms] = useState(true);
  const [monthlyReport, setMonthlyReport] = useState(false);

  async function handleLogout() {
    Alert.alert('Déconnexion', 'Êtes-vous sûr de vouloir vous déconnecter ?', [
      {
        text: 'Annuler',
        onPress: () => {},
      },
      {
        text: 'Déconnexion',
        onPress: async () => {
          try {
            await logout();
          } catch {
            // proceed to local logout even if server call fails
          }
          try {
            await signOut();
          } catch {
            // best-effort
          }
          queryClient.clear();
          setAuthenticated(false);
          router.replace('/(auth)/login');
        },
        style: 'destructive',
      },
    ]);
  }

  const initials = (user?.firstName || 'U').charAt(0).toUpperCase();

  return (
    <ScrollView
      style={{
        flex: 1,
        backgroundColor: colors.background.default,
      }}
    >
      {/* Header with Avatar */}
      <View
        style={{
          backgroundColor: colors.primary[600],
          paddingHorizontal: spacing.lg,
          paddingTop: spacing.xl,
          paddingBottom: spacing.lg,
          alignItems: 'center',
          gap: spacing.md,
        }}
      >
        <View
          style={{
            width: 80,
            height: 80,
            borderRadius: 40,
            backgroundColor: `${colors.text.inverse}20`,
            justifyContent: 'center',
            alignItems: 'center',
          }}
        >
          <Text
            style={{
              ...typography.h1,
              color: colors.text.inverse,
            }}
          >
            {initials}
          </Text>
        </View>

        <Text
          style={{
            ...typography.h2,
            color: colors.text.inverse,
            textAlign: 'center',
          }}
        >
          {user?.firstName} {user?.lastName}
        </Text>

        {user?.primaryEmailAddress?.emailAddress && (
          <Text
            style={{
              ...typography.body,
              color: `${colors.text.inverse}99`,
              textAlign: 'center',
            }}
          >
            {user.primaryEmailAddress.emailAddress}
          </Text>
        )}
      </View>

      <View style={{ padding: spacing.lg, gap: spacing.lg }}>
        {/* Contact Information */}
        <View style={{ gap: spacing.md }}>
          <Text style={{ ...typography.h3, color: colors.text.primary }}>
            Mes informations
          </Text>

          <Card variant="default">
            <View style={{ gap: spacing.md }}>
              <View>
                <Text style={{ ...typography.caption, color: colors.text.secondary, marginBottom: spacing.xs }}>
                  Téléphone
                </Text>
                <Text style={{ ...typography.bodyLg, color: colors.text.primary }}>
                  {user?.primaryPhoneNumber?.phoneNumber || '+241 06 XX XX XX'}
                </Text>
              </View>

              <View style={{ borderTopWidth: 1, borderTopColor: colors.border.light, paddingTop: spacing.md }}>
                <Text style={{ ...typography.caption, color: colors.text.secondary, marginBottom: spacing.xs }}>
                  Email
                </Text>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                  <Text style={{ ...typography.bodyLg, color: colors.text.primary }}>
                    {user?.primaryEmailAddress?.emailAddress || 'Non renseigné'}
                  </Text>
                  {!user?.primaryEmailAddress?.emailAddress && (
                    <Text style={{ ...typography.caption, color: colors.primary[600] }}>
                      Ajouter
                    </Text>
                  )}
                </View>
              </View>

              <View style={{ borderTopWidth: 1, borderTopColor: colors.border.light, paddingTop: spacing.md }}>
                <Text style={{ ...typography.caption, color: colors.text.secondary, marginBottom: spacing.xs }}>
                  Rôle
                </Text>
                <Badge label="Citoyen" variant="primary" />
              </View>

              <View style={{ borderTopWidth: 1, borderTopColor: colors.border.light, paddingTop: spacing.md }}>
                <Text style={{ ...typography.caption, color: colors.text.secondary, marginBottom: spacing.xs }}>
                  Type de contribuable
                </Text>
                <Text style={{ ...typography.bodyLg, color: colors.text.primary }}>
                  Particulier
                </Text>
              </View>
            </View>
          </Card>
        </View>

        {/* Preferences */}
        <View style={{ gap: spacing.md }}>
          <Text style={{ ...typography.h3, color: colors.text.primary }}>
            Préférences
          </Text>

          <Card variant="default">
            <View style={{ gap: spacing.md }}>
              <View
                style={{
                  flexDirection: 'row',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                }}
              >
                <Text style={{ ...typography.body, color: colors.text.primary }}>
                  Notifications activées
                </Text>
                <Switch
                  value={notifications}
                  onValueChange={setNotifications}
                  trackColor={{ false: colors.neutral[300], true: `${colors.success[600]}80` }}
                  thumbColor={notifications ? colors.success[600] : colors.neutral[500]}
                />
              </View>

              <View
                style={{
                  borderTopWidth: 1,
                  borderTopColor: colors.border.light,
                  paddingTop: spacing.md,
                  flexDirection: 'row',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                }}
              >
                <Text style={{ ...typography.body, color: colors.text.primary }}>
                  SMS de confirmation
                </Text>
                <Switch
                  value={sms}
                  onValueChange={setSms}
                  trackColor={{ false: colors.neutral[300], true: `${colors.success[600]}80` }}
                  thumbColor={sms ? colors.success[600] : colors.neutral[500]}
                />
              </View>

              <View
                style={{
                  borderTopWidth: 1,
                  borderTopColor: colors.border.light,
                  paddingTop: spacing.md,
                  flexDirection: 'row',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                }}
              >
                <Text style={{ ...typography.body, color: colors.text.primary }}>
                  Rapport mensuel par email
                </Text>
                <Switch
                  value={monthlyReport}
                  onValueChange={setMonthlyReport}
                  trackColor={{ false: colors.neutral[300], true: `${colors.success[600]}80` }}
                  thumbColor={monthlyReport ? colors.success[600] : colors.neutral[500]}
                />
              </View>
            </View>
          </Card>
        </View>

        {/* Security */}
        <View style={{ gap: spacing.md }}>
          <Text style={{ ...typography.h3, color: colors.text.primary }}>
            Sécurité
          </Text>

          <Card variant="default">
            <Button variant="secondary" size="md">
              Changer mot de passe
            </Button>
          </Card>
        </View>

        {/* Logout */}
        <Button variant="destructive" size="md" onPress={handleLogout}>
          Déconnexion
        </Button>

        <Text
          style={{
            ...typography.caption,
            color: colors.text.tertiary,
            textAlign: 'center',
            marginTop: spacing.lg,
          }}
        >
          Version 1.0.0
        </Text>
      </View>
    </ScrollView>
  );
}
