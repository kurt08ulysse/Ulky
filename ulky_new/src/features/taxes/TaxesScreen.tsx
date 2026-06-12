import React, { useState } from 'react';
import { Alert, FlatList, Pressable, Text, View, ActivityIndicator, Modal, TextInput } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import * as WebBrowser from 'expo-web-browser';
import { useTaxNotices, useCancelTaxNotice, usePayTaxNotice } from '@/hooks/useTaxes';
import { getMe } from '@/services/auth';
import { colors, spacing, typography } from '@/theme';
import { Card, Badge } from '@/components';

type FilterStatus = 'all' | 'pending' | 'paid' | 'cancelled';
type OperatorType = 'airtel_money' | 'moov_money';
type PaymentStep = 'input' | 'ussd_wait';

export default function TaxesScreen() {
  const [filter, setFilter] = useState<FilterStatus>('all');
  const [selectedNotice, setSelectedNotice] = useState<any | null>(null);
  const [operator, setOperator] = useState<OperatorType>('moov_money');
  const [phone, setPhone] = useState('');
  const [paymentStep, setPaymentStep] = useState<PaymentStep>('input');
  const [selfPaying, setSelfPaying] = useState(false);

  const queryClient = useQueryClient();
  const { data: notices, isLoading, error, refetch } = useTaxNotices();
  const cancelMutation = useCancelTaxNotice();
  const payMutation = usePayTaxNotice();

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

  const handleOpenPay = (notice: any) => {
    setSelectedNotice(notice);
    setPhone(currentUser?.phone ?? '');
    setOperator('moov_money');
    setPaymentStep('input');
    setSelfPaying(false);
  };

  const handleInitiatePayment = () => {
    if (!selectedNotice) return;
    if (!phone || phone.trim().length < 8) {
      Alert.alert('Erreur', 'Veuillez saisir un numéro de téléphone valide.');
      return;
    }

    setSelfPaying(true);

    payMutation.mutate({
      id: selectedNotice.id,
      operator,
      phone: phone.trim()
    }, {
      onSuccess: () => {
        setSelfPaying(false);
        setPaymentStep('ussd_wait');
      },
      onError: (err: any) => {
        setSelfPaying(false);
        const message = err?.response?.data?.message ?? 'Une erreur est survenue lors de l\'initialisation.';
        Alert.alert('Erreur', message);
      }
    });
  };

  const handleFinishedUssd = () => {
    setSelectedNotice(null);
    queryClient.invalidateQueries({ queryKey: ['tax-notices'] });
    refetch();
  };

  const handleViewReceipt = async (verificationUrl: string) => {
    try {
      await WebBrowser.openBrowserAsync(verificationUrl);
    } catch {
      Alert.alert('Erreur', 'Impossible d\'ouvrir la page de vérification.');
    }
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
      return { label: '✕ Annulé', variant: 'primary' as const };
    }
    
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
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background.default }}>
        <ActivityIndicator size="large" color={colors.primary[600]} />
        <Text style={{ ...typography.body, color: colors.text.secondary, marginTop: spacing.md }}>
          Chargement de vos avis de taxes...
        </Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background.default, padding: 24 }}>
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
                      onPress={() => handleOpenPay(item)}
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

                {item.status === 'paid' && item.receipt?.verification_url && (
                  <Pressable
                    onPress={() => handleViewReceipt(item.receipt!.verification_url)}
                    style={{
                      marginTop: spacing.xs,
                      paddingVertical: spacing.md,
                      borderRadius: 8,
                      borderWidth: 1,
                      borderColor: colors.primary[600],
                      backgroundColor: `${colors.primary[600]}10`,
                      alignItems: 'center',
                      flexDirection: 'row',
                      justifyContent: 'center',
                      gap: 8,
                    }}
                  >
                    <Text style={{ fontSize: 16 }}>📥</Text>
                    <Text style={{ ...typography.button, color: colors.primary[600] }}>
                      Voir & Télécharger le reçu
                    </Text>
                  </Pressable>
                )}
              </View>
            </Card>
          );
        }}
      />

      {/* Payment Dialog Modal */}
      <Modal
        visible={selectedNotice !== null}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setSelectedNotice(null)}
      >
        <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(0,0,0,0.5)', padding: spacing.lg }}>
          <View style={{ backgroundColor: colors.background.default, width: '100%', borderRadius: 16, padding: spacing.lg, gap: spacing.md }}>
            
            {paymentStep === 'input' ? (
              <>
                <Text style={{ ...typography.h3, color: colors.text.primary }}>Réglage Mobile Money</Text>
                <Text style={{ ...typography.body, color: colors.text.secondary }}>
                  Paiement de <Text style={{ fontWeight: '700' }}>{selectedNotice?.total_amount_formatted}</Text> pour l'acte "{selectedNotice?.tax?.name}".
                </Text>

                {/* Operator Selector */}
                <View style={{ gap: spacing.xs }}>
                  <Text style={{ ...typography.caption, color: colors.text.secondary }}>Opérateur :</Text>
                  <View style={{ flexDirection: 'row', gap: spacing.md }}>
                    <Pressable 
                      onPress={() => setOperator('moov_money')}
                      style={{
                        flex: 1,
                        padding: spacing.md,
                        borderRadius: 8,
                        borderWidth: 2,
                        borderColor: operator === 'moov_money' ? colors.primary[600] : colors.neutral[300],
                        alignItems: 'center',
                        backgroundColor: operator === 'moov_money' ? `${colors.primary[600]}10` : 'transparent',
                      }}
                    >
                      <Text style={{ ...typography.body, fontWeight: '700', color: operator === 'moov_money' ? colors.primary[600] : colors.text.primary }}>
                        Moov Money
                      </Text>
                    </Pressable>

                    <Pressable 
                      onPress={() => setOperator('airtel_money')}
                      style={{
                        flex: 1,
                        padding: spacing.md,
                        borderRadius: 8,
                        borderWidth: 2,
                        borderColor: operator === 'airtel_money' ? colors.primary[600] : colors.neutral[300],
                        alignItems: 'center',
                        backgroundColor: operator === 'airtel_money' ? `${colors.primary[600]}10` : 'transparent',
                      }}
                    >
                      <Text style={{ ...typography.body, fontWeight: '700', color: operator === 'airtel_money' ? colors.primary[600] : colors.text.primary }}>
                        Airtel Money
                      </Text>
                    </Pressable>
                  </View>
                </View>

                {/* Phone Input */}
                <View style={{ gap: spacing.xs }}>
                  <Text style={{ ...typography.caption, color: colors.text.secondary }}>Numéro de téléphone :</Text>
                  <TextInput
                    value={phone}
                    onChangeText={setPhone}
                    placeholder="+241 06 XX XX XX"
                    keyboardType="phone-pad"
                    style={{
                      borderWidth: 1,
                      borderColor: colors.neutral[300],
                      borderRadius: 8,
                      padding: spacing.md,
                      fontSize: 16,
                      color: colors.text.primary,
                      backgroundColor: colors.neutral[50],
                    }}
                  />
                </View>

                {/* Buttons */}
                <View style={{ flexDirection: 'row', gap: spacing.md, marginTop: spacing.sm }}>
                  <Pressable
                    disabled={selfPaying}
                    onPress={() => setSelectedNotice(null)}
                    style={{
                      flex: 1,
                      padding: spacing.md,
                      borderRadius: 8,
                      borderWidth: 1,
                      borderColor: colors.neutral[300],
                      alignItems: 'center',
                    }}
                  >
                    <Text style={{ ...typography.button, color: colors.text.secondary }}>
                      Annuler
                    </Text>
                  </Pressable>

                  <Pressable
                    disabled={selfPaying}
                    onPress={handleInitiatePayment}
                    style={{
                      flex: 2,
                      padding: spacing.md,
                      borderRadius: 8,
                      backgroundColor: colors.primary[600],
                      alignItems: 'center',
                      justifyContent: 'center',
                      flexDirection: 'row',
                      gap: 8,
                    }}
                  >
                    {selfPaying && <ActivityIndicator color={colors.text.inverse} size="small" />}
                    <Text style={{ ...typography.button, color: colors.text.inverse }}>
                      Initier le règlement
                    </Text>
                  </Pressable>
                </View>
              </>
            ) : (
              <>
                <Text style={{ ...typography.h3, color: colors.text.primary, textAlign: 'center' }}>📲 En attente de validation</Text>
                <Text style={{ ...typography.body, color: colors.text.secondary, textAlign: 'center', marginVertical: spacing.md }}>
                  Une demande de confirmation de paiement (Push USSD) a été envoyée au numéro <Text style={{ fontWeight: '700', color: colors.text.primary }}>{phone}</Text>.<br/><br/>
                  Veuillez composer votre code secret sur votre téléphone pour approuver le paiement de <Text style={{ fontWeight: '700', color: colors.text.primary }}>{selectedNotice?.total_amount_formatted}</Text>.
                </Text>

                <Pressable
                  onPress={handleFinishedUssd}
                  style={{
                    padding: spacing.md,
                    borderRadius: 8,
                    backgroundColor: colors.primary[600],
                    alignItems: 'center',
                    marginTop: spacing.sm,
                  }}
                >
                  <Text style={{ ...typography.button, color: colors.text.inverse }}>
                    J'ai validé le code secret
                  </Text>
                </Pressable>
              </>
            )}

          </View>
        </View>
      </Modal>
    </View>
  );
}

