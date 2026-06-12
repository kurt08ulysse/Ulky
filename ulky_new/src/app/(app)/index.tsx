import { useQuery } from '@tanstack/react-query';
import { ScrollView, Text, View, Pressable } from 'react-native';
import { useAuth } from '@clerk/expo';
import { getMe } from '@/services/auth';
import { colors, spacing, typography } from '@/theme';
import { Card, Badge } from '@/components';

// Mock taxes data
const MOCK_TAXES = [
  {
    id: '1',
    name: 'Impôt sur le Revenu',
    amount: 50000,
    dueDate: '2026-06-30',
    status: 'pending' as const,
  },
  {
    id: '2',
    name: 'Patente Commerciale',
    amount: 25000,
    dueDate: '2026-06-30',
    status: 'pending' as const,
  },
  {
    id: '3',
    name: 'Taxe Foncière',
    amount: 75000,
    dueDate: '2026-07-15',
    status: 'overdue' as const,
  },
];

function getTaxStatusLabel(status: string) {
  switch (status) {
    case 'paid':
      return '✓ Payé';
    case 'pending':
      return '⏳ En attente';
    case 'overdue':
      return '⚠️ En retard';
    default:
      return status;
  }
}

export default function HomeScreen() {
  const { user } = useAuth();
  const { data: currentUser, isLoading } = useQuery({
    queryKey: ['me'],
    queryFn: getMe,
  });

  const today = new Date();
  const dateString = today.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  return (
    <ScrollView
      style={{
        flex: 1,
        backgroundColor: colors.background.default,
      }}
    >
      {/* Header Section */}
      <View
        style={{
          backgroundColor: colors.primary[600],
          paddingHorizontal: spacing.lg,
          paddingTop: spacing.xl,
          paddingBottom: spacing.lg,
        }}
      >
        <Text
          style={{
            ...typography.h2,
            color: colors.text.inverse,
            marginBottom: spacing.sm,
          }}
        >
          Bienvenue, {currentUser?.name ?? user?.firstName ?? 'Utilisateur'}
        </Text>
        <Text
          style={{
            ...typography.body,
            color: `${colors.text.inverse}99`,
          }}
        >
          {dateString}
        </Text>
      </View>

      <View style={{ padding: spacing.lg, gap: spacing.lg }}>
        {/* Quick Actions */}
        <View style={{ gap: spacing.md }}>
          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            <Pressable
              style={{
                flex: 1,
                padding: spacing.lg,
                backgroundColor: colors.neutral[100],
                borderRadius: 12,
                alignItems: 'center',
                gap: spacing.sm,
              }}
            >
              <Text style={{ fontSize: 28 }}>💰</Text>
              <Text style={{ ...typography.caption, color: colors.text.primary, textAlign: 'center' }}>
                Payer une taxe
              </Text>
            </Pressable>

            <Pressable
              style={{
                flex: 1,
                padding: spacing.lg,
                backgroundColor: colors.neutral[100],
                borderRadius: 12,
                alignItems: 'center',
                gap: spacing.sm,
              }}
            >
              <Text style={{ fontSize: 28 }}>📄</Text>
              <Text style={{ ...typography.caption, color: colors.text.primary, textAlign: 'center' }}>
                Mes paiements
              </Text>
            </Pressable>
          </View>

          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            <Pressable
              style={{
                flex: 1,
                padding: spacing.lg,
                backgroundColor: colors.neutral[100],
                borderRadius: 12,
                alignItems: 'center',
                gap: spacing.sm,
              }}
            >
              <Text style={{ fontSize: 28 }}>📋</Text>
              <Text style={{ ...typography.caption, color: colors.text.primary, textAlign: 'center' }}>
                Mes démarches
              </Text>
            </Pressable>

            <Pressable
              style={{
                flex: 1,
                padding: spacing.lg,
                backgroundColor: colors.neutral[100],
                borderRadius: 12,
                alignItems: 'center',
                gap: spacing.sm,
              }}
            >
              <Text style={{ fontSize: 28 }}>❓</Text>
              <Text style={{ ...typography.caption, color: colors.text.primary, textAlign: 'center' }}>
                Aide & Support
              </Text>
            </Pressable>
          </View>
        </View>

        {/* Taxes Section */}
        <View style={{ gap: spacing.md }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={{ ...typography.h3, color: colors.text.primary }}>Taxes à jour</Text>
            {MOCK_TAXES.length === 0 && (
              <Badge label="À jour" variant="success" />
            )}
          </View>

          {isLoading ? (
            <Text style={{ ...typography.body, color: colors.text.secondary }}>
              Chargement...
            </Text>
          ) : MOCK_TAXES.length === 0 ? (
            <View style={{ alignItems: 'center', paddingVertical: spacing.xl }}>
              <Text style={{ fontSize: 32, marginBottom: spacing.md }}>📭</Text>
              <Text style={{ ...typography.body, color: colors.text.secondary, textAlign: 'center' }}>
                Aucune taxe à payer en ce moment
              </Text>
            </View>
          ) : (
            MOCK_TAXES.map((tax) => (
              <Card key={tax.id} variant="default">
                <View style={{ gap: spacing.sm }}>
                  <View
                    style={{
                      flexDirection: 'row',
                      justifyContent: 'space-between',
                      alignItems: 'flex-start',
                    }}
                  >
                    <Text
                      style={{
                        ...typography.bodyLg,
                        color: colors.text.primary,
                        flex: 1,
                        marginRight: spacing.md,
                      }}
                    >
                      {tax.name}
                    </Text>
                    <Badge
                      label={getTaxStatusLabel(tax.status)}
                      variant={
                        tax.status === 'paid'
                          ? 'success'
                          : tax.status === 'overdue'
                            ? 'error'
                            : 'warning'
                      }
                    />
                  </View>

                  <Text style={{ ...typography.body, color: colors.text.secondary }}>
                    Montant : <Text style={{ fontWeight: '600' }}>{tax.amount.toLocaleString()} FCFA</Text>
                  </Text>

                  <Text style={{ ...typography.body, color: colors.text.secondary }}>
                    Échéance :{' '}
                    <Text style={{ fontWeight: '600' }}>
                      {new Date(tax.dueDate).toLocaleDateString('fr-FR')}
                    </Text>
                  </Text>

                  {tax.status !== 'paid' && (
                    <Pressable
                      style={{
                        marginTop: spacing.sm,
                        padding: spacing.md,
                        borderRadius: 8,
                        backgroundColor: `${colors.primary[600]}10`,
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{ ...typography.button, color: colors.primary[600] }}>
                        Payer maintenant
                      </Text>
                    </Pressable>
                  )}
                </View>
              </Card>
            ))
          )}
        </View>
      </View>
    </ScrollView>
  );
}
