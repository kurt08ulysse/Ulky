import { useQuery } from '@tanstack/react-query';
import { ScrollView, Text, View, Pressable } from 'react-native';
import { useUser } from '@clerk/expo';
import { useRouter } from 'expo-router';
import { getMe } from '@/services/auth';
import { useTaxNotices } from '@/hooks/useTaxes';
import { colors, spacing, typography } from '@/theme';
import { Card, Badge } from '@/components';

function getStatusDetails(status: string, dueDateStr: string) {
  if (status === 'paid') {
    return { label: '✓ Payé', variant: 'success' as const };
  }
  if (status === 'cancelled') {
    return { label: '✕ Annulé', variant: 'primary' as const };
  }
  
  const dueDate = new Date(dueDateStr);
  const today = new Date();
  today.setHours(0,0,0,0);
  
  if (dueDate < today) {
    return { label: '⚠️ En retard', variant: 'error' as const };
  }
  
  return { label: '⏳ En attente', variant: 'warning' as const };
}

export default function HomeScreen() {
  const { user } = useUser();
  const router = useRouter();
  
  const { data: currentUser, isLoading: isLoadingUser } = useQuery({
    queryKey: ['me'],
    queryFn: getMe,
  });

  const { data: notices, isLoading: isLoadingNotices } = useTaxNotices();

  const today = new Date();
  const dateString = today.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  // Filter only pending/overdue notices for home screen dashboard
  const pendingNotices = notices?.filter(notice => notice.status === 'pending') ?? [];
  const hasOverdue = pendingNotices.some(notice => {
    const dueDate = new Date(notice.due_date);
    const today = new Date();
    today.setHours(0,0,0,0);
    return dueDate < today;
  });

  const isLoading = isLoadingUser || isLoadingNotices;

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
              onPress={() => router.push('/taxes' as any)}
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
              onPress={() => router.push('/taxes' as any)}
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
            <Text style={{ ...typography.h3, color: colors.text.primary }}>Taxes à payer</Text>
            {!isLoading && pendingNotices.length === 0 && (
              <Badge label="À jour" variant="success" />
            )}
            {!isLoading && hasOverdue && (
              <Badge label="Attention" variant="error" />
            )}
          </View>

          {isLoading ? (
            <Text style={{ ...typography.body, color: colors.text.secondary }}>
              Chargement...
            </Text>
          ) : pendingNotices.length === 0 ? (
            <View style={{ alignItems: 'center', paddingVertical: spacing.xl }}>
              <Text style={{ fontSize: 32, marginBottom: spacing.md }}>📭</Text>
              <Text style={{ ...typography.body, color: colors.text.secondary, textAlign: 'center' }}>
                Aucune taxe à payer en ce moment
              </Text>
            </View>
          ) : (
            pendingNotices.map((notice) => {
              const { label: statusLabel, variant: statusVariant } = getStatusDetails(notice.status, notice.due_date);
              return (
                <Card key={notice.id} variant="default">
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
                          fontWeight: '700',
                        }}
                      >
                        {notice.tax?.name ?? 'Avis de taxe'}
                      </Text>
                      <Badge label={statusLabel} variant={statusVariant} />
                    </View>

                    <Text style={{ ...typography.body, color: colors.text.secondary }}>
                      Montant : <Text style={{ fontWeight: '600', color: colors.text.primary }}>{notice.total_amount_formatted}</Text>
                    </Text>

                    <Text style={{ ...typography.body, color: colors.text.secondary }}>
                      Échéance :{' '}
                      <Text style={{ fontWeight: '600', color: colors.text.primary }}>
                        {new Date(notice.due_date).toLocaleDateString('fr-FR')}
                      </Text>
                    </Text>

                    <Pressable
                      onPress={() => router.push('/taxes' as any)}
                      style={{
                        marginTop: spacing.sm,
                        padding: spacing.md,
                        borderRadius: 8,
                        backgroundColor: `${colors.primary[600]}10`,
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{ ...typography.button, color: colors.primary[600] }}>
                        Détails & Règlement
                      </Text>
                    </Pressable>
                  </View>
                </Card>
              );
            })
          )}
        </View>
      </View>
    </ScrollView>
  );
}
