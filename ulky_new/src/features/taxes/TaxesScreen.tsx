import React, { useState } from 'react';
import { Alert, FlatList, Pressable, Text, View, ActivityIndicator } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useTaxNotices, useCancelTaxNotice } from '@/hooks/useTaxes';
import { getMe } from '@/services/auth';
import { colors, spacing, typography } from '@/theme';
import { Card, Badge, Button } from '@/components';

type FilterStatus = 'all' | 'pending' | 'paid' | 'cancelled';

export default function TaxesScreen() {
  const [filter, setFilter] = useState<FilterStatus>('all');
  const { data: notices, isLoading, error } = useTaxNotices();
  const cancelMutation = useCancelTaxNotice();

  const { data: currentUser } = useQuery({
    queryKey: ['me'],
    queryFn: getMe,
  });

  const isAgentOrAdmin = currentUser?.roles?.some(role => 
    ['municipal_agent', 'commune_admin', 'super_admin'].includes(role)
  ) ?? false;

  // Calcul du montant total en attente
  const totalPending = notices
    ?.filter(notice => notice.status === 'pending')
    ?.reduce((sum, notice) => sum + notice.total_amount, 0) ?? 0;

  const totalPendingFormatted = (totalPending / 100).toLocaleString('fr-FR') + ' FCFA';

  // Filtrage des avis
  const filteredNotices = notices?.filter(notice => {
    if (filter === 'all') return true;
    return notice.status === filter;
  }) ?? [];

  const handlePay = (noticeName: string, amount: string) => {
    Alert.alert(
      'Paiement Mobile Money',
      `Le module de paiement (Airtel Money / Moov Money) pour un montant de ${amount} (${noticeName}) sera disponible lors de la Phase 3.`,
      [{ text: 'Compris' }]
    );
  };

  const handleCancel = (noticeId: number, noticeName: string) => {
    Alert.alert(
      'Annuler l\'avis de taxe',
      `Êtes-vous sûr de vouloir annuler l'avis de taxe "${noticeName}" ? Cette action est irréversible.`,
      [
        { text: 'Retour', style: 'cancel' },
        { 
          text: 'Oui, annuler', 
          style: 'destructive',
          onPress: () => {
            cancelMutation.mutate(noticeId, {
              onSuccess: () => {
                Alert.alert('Succès', 'L\'avis de taxe a bien été annulé.');
              },
              onError: (err: any) => {
                const message = err?.response?.data?.message ?? 'Une erreur est survenue.';
                Alert.alert('Erreur', message);
              }
            });
          }
        }
      ]
    );
  };

  const getStatusDetails = (status: string, dueDateStr: string) => {
    if (status === 'paid') {
      return { label: '✓ Payé', variant: 'success' as const };
    }
    if (status === 'cancelled') {
      return { label: '✕ Annulé', variant: 'primary' as const }; // Neutral
    }
    
    // Check if overdue
    const dueDate = new Date(dueDateStr);
    const today = new Date();
    today.setHours(0,0,0,0);
    
    if (dueDate < today) {
      return { label: '⚠️ En retard', variant: 'error' as const };
    }
    
    return { label: '⏳ En attente', variant: 'warning' as const };
  };

  if (isLoading) {
    return (
      <View className="flex-1 justify-center items-center bg-white" style={{ backgroundColor: colors.background.default }}>
        <ActivityIndicator size="large" color={colors.primary[600]} />
        <Text style={{ ...typography.body, color: colors.text.secondary, marginTop: spacing.md }}>
          Chargement de vos avis de taxes...
        </Text>
      </View>
    );
  }

  if (error) {
    return (
      <View className="flex-1 justify-center items-center bg-white p-6" style={{ backgroundColor: colors.background.default }}>
        <Text style={{ fontSize: 48, marginBottom: spacing.md }}>⚠️</Text>
        <Text style={{ ...typography.h3, color: colors.text.primary, textAlign: 'center', marginBottom: spacing.sm }}>
          Erreur de connexion
        </Text>
        <Text style={{ ...typography.body, color: colors.text.secondary, textAlign: 'center' }}>
          Impossible de récupérer les avis de taxes. Veuillez réessayer plus tard.
        </Text>
      </View>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: colors.background.default }}>
      {/* Header section with total due */}
      <View style={{ backgroundColor: colors.primary[600], padding: spacing.lg, paddingTop: spacing.xl * 1.5 }}>
        <Text style={{ ...typography.body, color: `${colors.text.inverse}cc` }}>Total des avis en attente</Text>
        <Text style={{ ...typography.h1, color: colors.text.inverse, fontSize: 32, marginTop: spacing.xs }}>
          {totalPendingFormatted}
        </Text>
      </View>

      {/* Filter Tabs */}
      <View style={{ flexDirection: 'row', backgroundColor: colors.neutral[100], padding: spacing.xs }}>
        {(['all', 'pending', 'paid', 'cancelled'] as FilterStatus[]).map((tab) => {
          const isActive = filter === tab;
          const label = tab === 'all' ? 'Tous' : tab === 'pending' ? 'En attente' : tab === 'paid' ? 'Payés' : 'Annulés';
          return (
            <Pressable
              key={tab}
              onPress={() => setFilter(tab)}
              style={{
                flex: 1,
                paddingVertical: spacing.md,
                borderRadius: 8,
                backgroundColor: isActive ? colors.background.default : 'transparent',
                alignItems: 'center',
              }}
            >
              <Text style={{ 
                ...typography.caption, 
                fontWeight: isActive ? '700' : '500',
                color: isActive ? colors.primary[600] : colors.text.secondary 
              }}>
                {label}
              </Text>
            </Pressable>
          );
        })}
      </View>

      {/* Notices List */}
      <FlatList
        data={filteredNotices}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={{ padding: spacing.lg, gap: spacing.md, paddingBottom: spacing.xl * 2 }}
        ListEmptyComponent={
          <View style={{ alignItems: 'center', paddingVertical: spacing.xl }}>
            <Text style={{ fontSize: 48, marginBottom: spacing.md }}>📄</Text>
            <Text style={{ ...typography.bodyLg, color: colors.text.secondary, textAlign: 'center' }}>
              Aucun avis de taxe trouvé
            </Text>
          </View>
        }
        renderItem={({ item }) => {
          const { label: statusLabel, variant: statusVariant } = getStatusDetails(item.status, item.due_date);
          const noticeName = item.tax?.name ?? 'Avis de Taxe';
          
          return (
            <Card variant="default">
              <View style={{ gap: spacing.md }}>
                {/* Line 1: Name and Badge */}
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <Text style={{ ...typography.bodyLg, fontWeight: '700', color: colors.text.primary, flex: 1, marginRight: spacing.sm }}>
                    {noticeName}
                  </Text>
                  <Badge label={statusLabel} variant={statusVariant} />
                </View>

                {/* Line 2: Details Base + Stamp */}
                <View style={{ backgroundColor: colors.neutral[50], padding: spacing.md, borderRadius: 8, gap: spacing.xs }}>
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                    <Text style={{ ...typography.caption, color: colors.text.secondary }}>Coût de base :</Text>
                    <Text style={{ ...typography.caption, color: colors.text.primary, fontWeight: '600' }}>
                      {item.base_amount_formatted}
                    </Text>
                  </View>
                  {item.stamp_amount > 0 && (
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                      <Text style={{ ...typography.caption, color: colors.text.secondary }}>Timbre fiscal :</Text>
                      <Text style={{ ...typography.caption, color: colors.text.primary, fontWeight: '600' }}>
                        {item.stamp_amount_formatted}
                      </Text>
                    </View>
                  )}
                  <View style={{ borderTopWidth: 1, borderTopColor: colors.border.light, marginTop: spacing.xs, paddingTop: spacing.xs, flexDirection: 'row', justifyContent: 'space-between' }}>
                    <Text style={{ ...typography.body, color: colors.text.primary, fontWeight: '700' }}>Total :</Text>
                    <Text style={{ ...typography.body, color: colors.primary[600], fontWeight: '700' }}>
                      {item.total_amount_formatted}
                    </Text>
                  </View>
                </View>

                {/* Line 3: Dates */}
                <View style={{ gap: spacing.xs }}>
                  <Text style={{ ...typography.caption, color: colors.text.secondary }}>
                    Échéance : <Text style={{ fontWeight: '600', color: colors.text.primary }}>
                      {new Date(item.due_date).toLocaleDateString('fr-FR')}
                    </Text>
                  </Text>
                  {item.paid_at && (
                    <Text style={{ ...typography.caption, color: colors.text.secondary }}>
                      Payé le : <Text style={{ fontWeight: '600', color: colors.text.primary }}>
                        {new Date(item.paid_at).toLocaleDateString('fr-FR')}
                      </Text>
                    </Text>
                  )}
                </View>

                {/* Line 4: Actions */}
                {item.status === 'pending' && (
                  <View style={{ flexDirection: 'row', gap: spacing.md, marginTop: spacing.xs }}>
                    <Pressable
                      onPress={() => handlePay(noticeName, item.total_amount_formatted)}
                      style={{
                        flex: 2,
                        paddingVertical: spacing.md,
                        borderRadius: 8,
                        backgroundColor: colors.primary[600],
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{ ...typography.button, color: colors.text.inverse }}>
                        Payer maintenant
                      </Text>
                    </Pressable>

                    {isAgentOrAdmin && (
                      <Pressable
                        onPress={() => handleCancel(item.id, noticeName)}
                        style={{
                          flex: 1,
                          paddingVertical: spacing.md,
                          borderRadius: 8,
                          borderWidth: 1,
                          borderColor: colors.error[600],
                          backgroundColor: `${colors.error[600]}10`,
                          alignItems: 'center',
                        }}
                      >
                        <Text style={{ ...typography.button, color: colors.error[600] }}>
                          Annuler
                        </Text>
                      </Pressable>
                    )}
                  </View>
                )}
              </View>
            </Card>
          );
        }}
      />
    </View>
  );
}
