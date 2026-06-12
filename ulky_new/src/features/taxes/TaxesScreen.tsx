import React, { useState } from 'react';
import { Alert, ScrollView, Pressable, Text, View, ActivityIndicator, Modal, TextInput, Platform } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import * as WebBrowser from 'expo-web-browser';
import { useTaxNotices, useCancelTaxNotice, usePayTaxNotice } from '@/hooks/useTaxes';
import { getMe } from '@/services/auth';
import { colors, spacing, typography, shadows } from '@/theme';
import { Card, Badge } from '@/components';

type OperatorType = 'airtel_money' | 'moov_money';
type PaymentStep = 'input' | 'ussd_wait';

// Couleurs de la charte de Franceville et du Gabon
const customColors = {
  gabonGreen: '#009E60',
  gabonYellow: '#FCD116',
  gabonBlue: '#3A75C4',
  primary: '#001e40',          // Bleu très foncé identitaire
  secondary: '#496177',        // Bleu-gris
  background: '#f9f9f9',       // Gris très clair
  surface: '#ffffff',          // Blanc
  border: '#c3c6d1',           // Gris-bleu bordures
  textPrimary: '#1a1c1c',
  textSecondary: '#43474f',
  errorContainer: '#ffdad6',
  onErrorContainer: '#93000a',
  successContainer: '#E6F4EA',
  warningContainer: '#FFF8E1'
};

export default function TaxesScreen() {
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

  // Filtrage des avis
  const pendingNotices = notices?.filter(notice => notice.status === 'pending') ?? [];
  const paidNotices = notices?.filter(notice => notice.status === 'paid') ?? [];

  // Calcul du montant total en attente
  const totalPending = pendingNotices.reduce((sum, notice) => sum + notice.total_amount, 0);
  const totalPendingFormatted = (totalPending / 100).toLocaleString('fr-FR') + ' FCFA';

  // Calcul du montant réglé cette année (réel)
  const totalPaidReal = paidNotices.reduce((sum, notice) => sum + notice.total_amount, 0);
  const totalPaidFormatted = totalPaidReal > 0 
    ? (totalPaidReal / 100).toLocaleString('fr-FR') + ' FCFA'
    : '0,00 €'; // Valeur par défaut de la maquette si 0

  // Liste fictive issue de la maquette pour compléter l'historique
  const mockPaidNotices = [
    {
      id: 'mock-1',
      tax: { name: 'Taxe Foncière 2022' },
      paid_at: '2022-10-12T12:00:00Z',
      total_amount_formatted: '1 180,00 €',
      isMock: true,
    },
    {
      id: 'mock-2',
      tax: { name: 'Taxe Habitation 2022' },
      paid_at: '2022-11-10T12:00:00Z',
      total_amount_formatted: '850,00 €',
      isMock: true,
    },
    {
      id: 'mock-3',
      tax: { name: 'Taxe Foncière 2021' },
      paid_at: '2021-10-15T12:00:00Z',
      total_amount_formatted: '1 150,00 €',
      isMock: true,
    }
  ];

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

  const getStatusDetails = (dueDateStr: string) => {
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
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: customColors.background }}>
        <ActivityIndicator size="large" color={customColors.primary} />
        <Text style={{ ...typography.body, color: customColors.textSecondary, marginTop: spacing.md }}>
          Chargement de vos avis de taxes...
        </Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: customColors.background, padding: 24 }}>
        <Text style={{ fontSize: 48, marginBottom: spacing.md }}>⚠️</Text>
        <Text style={{ ...typography.h3, color: customColors.textPrimary, textAlign: 'center', marginBottom: spacing.sm }}>
          Erreur de connexion
        </Text>
        <Text style={{ ...typography.body, color: customColors.textSecondary, textAlign: 'center' }}>
          Impossible de récupérer les avis de taxes. Veuillez réessayer plus tard.
        </Text>
      </View>
    );
  }

  return (
    <ScrollView 
      style={{ flex: 1, backgroundColor: customColors.background }}
      contentContainerStyle={{ paddingBottom: spacing.xl * 2 }}
      showsVerticalScrollIndicator={false}
    >
      {/* Drapeau du Gabon en bordure supérieure */}
      <View style={{ height: 4, flexDirection: 'row' }}>
        <View style={{ flex: 1, backgroundColor: customColors.gabonGreen }} />
        <View style={{ flex: 1, backgroundColor: customColors.gabonYellow }} />
        <View style={{ flex: 1, backgroundColor: customColors.gabonBlue }} />
      </View>

      {/* ─── EN-TÊTE DE LA PAGE ─── */}
      <View 
        style={{ 
          backgroundColor: customColors.surface, 
          paddingHorizontal: spacing.lg, 
          paddingVertical: spacing.xl, 
          borderBottomWidth: 1, 
          borderBottomColor: `${customColors.border}50`,
          flexDirection: 'row',
          justifyContent: 'space-between',
          alignItems: 'center',
          ...shadows.subtle
        }}
      >
        <View style={{ flex: 1, marginRight: spacing.md }}>
          <Text style={{ fontSize: 24, fontWeight: '700', color: customColors.primary, letterSpacing: -0.5 }}>
            Espace Fiscalité
          </Text>
          <Text style={{ fontSize: 13, color: customColors.textSecondary, marginTop: 4 }}>
            Gérez vos avis d'imposition et paiements locaux.
          </Text>
        </View>
        
        {pendingNotices.length > 0 && (
          <Pressable
            onPress={() => handleOpenPay(pendingNotices[0])}
            style={({ pressed }) => ({
              backgroundColor: customColors.primary,
              paddingHorizontal: 16,
              paddingVertical: 10,
              borderRadius: 8,
              opacity: pressed ? 0.9 : 1,
              ...shadows.subtle
            })}
          >
            <Text style={{ color: '#ffffff', fontWeight: '700', fontSize: 13 }}>
              Payer mes taxes
            </Text>
          </Pressable>
        )}
      </View>

      <View style={{ padding: spacing.lg, gap: spacing.lg }}>
        
        {/* ─── AVIS EN ATTENTE (SECTION DYNAMIQUE) ─── */}
        <View style={{ gap: spacing.md }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={{ fontSize: 18, fontWeight: '700', color: customColors.primary }}>
              Avis en attente
            </Text>
            <Badge 
              label={`${pendingNotices.length} à régler`} 
              variant={pendingNotices.length > 0 ? 'error' : 'success'} 
            />
          </View>

          {pendingNotices.length > 0 ? (
            pendingNotices.map((item) => {
              const { label: statusLabel, variant: statusVariant } = getStatusDetails(item.due_date);
              const noticeName = item.tax?.name ?? 'Avis de Taxe';
              
              return (
                <Card key={item.id} variant="default" style={{ borderColor: `${customColors.border}80` }}>
                  <View style={{ gap: spacing.md }}>
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm, flex: 1 }}>
                        <View style={{ width: 36, height: 36, borderRadius: 18, backgroundColor: '#cde5ff', justifyContent: 'center', alignItems: 'center' }}>
                          <Ionicons name="business-outline" size={20} color={customColors.primary} />
                        </View>
                        <Text style={{ fontSize: 16, fontWeight: '700', color: customColors.primary, flex: 1 }} numberOfLines={1}>
                          {noticeName}
                        </Text>
                      </View>
                      <Badge label={statusLabel} variant={statusVariant} />
                    </View>

                    <View style={{ backgroundColor: '#f3f3f3', padding: spacing.md, borderRadius: 8, gap: spacing.xs }}>
                      <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                        <Text style={{ fontSize: 12, color: customColors.textSecondary }}>Coût de base :</Text>
                        <Text style={{ fontSize: 12, fontWeight: '600', color: customColors.textPrimary }}>
                          {item.base_amount_formatted}
                        </Text>
                      </View>
                      {item.stamp_amount > 0 && (
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                          <Text style={{ fontSize: 12, color: customColors.textSecondary }}>Timbre fiscal :</Text>
                          <Text style={{ fontSize: 12, fontWeight: '600', color: customColors.textPrimary }}>
                            {item.stamp_amount_formatted}
                          </Text>
                        </View>
                      )}
                      <View style={{ borderTopWidth: 1, borderTopColor: `${customColors.border}30`, marginTop: spacing.xs, paddingTop: spacing.xs, flexDirection: 'row', justifyContent: 'space-between' }}>
                        <Text style={{ fontSize: 14, fontWeight: '700', color: customColors.textPrimary }}>Total :</Text>
                        <Text style={{ fontSize: 15, fontWeight: '700', color: customColors.primary }}>
                          {item.total_amount_formatted}
                        </Text>
                      </View>
                    </View>

                    <Text style={{ fontSize: 11, color: customColors.textSecondary }}>
                      Date limite : <Text style={{ fontWeight: '600', color: customColors.textPrimary }}>
                        {new Date(item.due_date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
                      </Text>
                    </Text>

                    <View style={{ flexDirection: 'row', gap: spacing.md }}>
                      <Pressable
                        onPress={() => handleOpenPay(item)}
                        style={({ pressed }) => ({
                          flex: 2,
                          backgroundColor: customColors.primary,
                          paddingVertical: 10,
                          borderRadius: 8,
                          alignItems: 'center',
                          opacity: pressed ? 0.9 : 1,
                        })}
                      >
                        <Text style={{ color: '#ffffff', fontWeight: '700', fontSize: 14 }}>
                          Payer maintenant
                        </Text>
                      </Pressable>

                      {isAgentOrAdmin && (
                        <Pressable
                          onPress={() => handleCancel(item.id, noticeName)}
                          style={({ pressed }) => ({
                            flex: 1,
                            borderWidth: 1,
                            borderColor: colors.error[600],
                            backgroundColor: `${colors.error[600]}10`,
                            paddingVertical: 10,
                            borderRadius: 8,
                            alignItems: 'center',
                            opacity: pressed ? 0.9 : 1,
                          })}
                        >
                          <Text style={{ color: colors.error[600], fontWeight: '700', fontSize: 14 }}>
                            Annuler
                          </Text>
                        </Pressable>
                      )}
                    </View>
                  </View>
                </Card>
              );
            })
          ) : (
            <Card variant="default" style={{ padding: spacing.xl, alignItems: 'center', borderColor: `${customColors.border}80` }}>
              <Ionicons name="checkmark-circle-outline" size={48} color={customColors.gabonGreen} />
              <Text style={{ fontSize: 15, fontWeight: '700', color: customColors.primary, marginTop: spacing.sm, textAlign: 'center' }}>
                Aucun avis de taxe en attente
              </Text>
              <Text style={{ fontSize: 13, color: customColors.textSecondary, marginTop: 4, textAlign: 'center' }}>
                Vous êtes parfaitement à jour de vos contributions locales.
              </Text>
            </Card>
          )}
        </View>

        {/* ─── QUICK STATS BENTO GRID ─── */}
        <View style={{ flexDirection: 'row', gap: spacing.md }}>
          <View 
            style={{ 
              flex: 1, 
              backgroundColor: customColors.primary, 
              borderRadius: 12, 
              padding: spacing.lg,
              ...shadows.subtle,
              overflow: 'hidden'
            }}
          >
            <View style={{ position: 'absolute', right: -15, top: -15, opacity: 0.1 }}>
              <Ionicons name="card" size={80} color="#ffffff" />
            </View>
            <Text style={{ fontSize: 12, color: 'rgba(255,255,255,0.7)', fontWeight: '600' }}>
              Réglé cette année
            </Text>
            <Text style={{ fontSize: 20, fontWeight: '700', color: '#ffffff', marginTop: 8 }}>
              {totalPaidFormatted}
            </Text>
          </View>

          <View 
            style={{ 
              flex: 1, 
              backgroundColor: customColors.surface, 
              borderRadius: 12, 
              padding: spacing.lg, 
              borderWidth: 1, 
              borderColor: `${customColors.border}80`,
              ...shadows.subtle,
              justifyContent: 'space-between'
            }}
          >
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
              <Text style={{ fontSize: 12, color: customColors.textSecondary, fontWeight: '600' }}>
                Prélèvement
              </Text>
              <Ionicons name="chevron-forward" size={16} color={customColors.secondary} />
            </View>
            <Text style={{ fontSize: 18, fontWeight: '700', color: customColors.primary, marginTop: 8 }}>
              Non activé
            </Text>
          </View>
        </View>

        {/* ─── GRAPHIQUE VERTICAL (RÉPARTITION DES IMPÔTS) ─── */}
        <View 
          style={{ 
            backgroundColor: customColors.surface, 
            borderRadius: 12, 
            padding: spacing.lg, 
            borderWidth: 1, 
            borderColor: `${customColors.border}80`,
            ...shadows.subtle
          }}
        >
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: spacing.md }}>
            <Ionicons name="pie-chart-outline" size={20} color={customColors.secondary} />
            <Text style={{ fontSize: 16, fontWeight: '700', color: customColors.primary }}>
              Répartition des impôts locaux
            </Text>
          </View>

          <View 
            style={{ 
              height: 160, 
              width: '100%', 
              backgroundColor: '#f3f3f3', 
              borderRadius: 8, 
              borderWidth: 1, 
              borderColor: `${customColors.border}50`, 
              borderStyle: 'dashed', 
              justifyContent: 'flex-end', 
              paddingHorizontal: spacing.lg, 
              paddingBottom: spacing.sm 
            }}
          >
            {/* Grid lines background */}
            <View style={{ position: 'absolute', top: 30, left: 0, right: 0, height: 1, backgroundColor: `${customColors.border}20` }} />
            <View style={{ position: 'absolute', top: 70, left: 0, right: 0, height: 1, backgroundColor: `${customColors.border}20` }} />
            <View style={{ position: 'absolute', top: 110, left: 0, right: 0, height: 1, backgroundColor: `${customColors.border}20` }} />

            {/* Bars container */}
            <View style={{ flexDirection: 'row', justifyContent: 'space-around', alignItems: 'flex-end', height: 120 }}>
              {/* Bar 1: Taxe Foncière (65%) */}
              <View style={{ alignItems: 'center', width: '30%' }}>
                <View style={{ height: 120 * 0.65, width: 28, backgroundColor: customColors.primary, borderRadius: 4 }} />
              </View>

              {/* Bar 2: Taxe Habitation (25%) */}
              <View style={{ alignItems: 'center', width: '30%' }}>
                <View style={{ height: 120 * 0.25, width: 28, backgroundColor: customColors.gabonYellow, borderRadius: 4 }} />
              </View>

              {/* Bar 3: TEOM (10%) */}
              <View style={{ alignItems: 'center', width: '30%' }}>
                <View style={{ height: 120 * 0.1, width: 28, backgroundColor: customColors.gabonGreen, borderRadius: 4 }} />
              </View>
            </View>
          </View>

          {/* Labels row */}
          <View style={{ flexDirection: 'row', justifyContent: 'space-around', marginTop: spacing.sm }}>
            <View style={{ alignItems: 'center', width: '30%' }}>
              <Text style={{ fontSize: 11, fontWeight: '700', color: customColors.textPrimary }}>Foncier</Text>
              <Text style={{ fontSize: 10, color: customColors.textSecondary }}>65%</Text>
            </View>
            <View style={{ alignItems: 'center', width: '30%' }}>
              <Text style={{ fontSize: 11, fontWeight: '700', color: customColors.textPrimary }}>Habitation</Text>
              <Text style={{ fontSize: 10, color: customColors.textSecondary }}>25%</Text>
            </View>
            <View style={{ alignItems: 'center', width: '30%' }}>
              <Text style={{ fontSize: 11, fontWeight: '700', color: customColors.textPrimary }}>TEOM</Text>
              <Text style={{ fontSize: 10, color: customColors.textSecondary }}>10%</Text>
            </View>
          </View>
        </View>

        {/* ─── HISTORIQUE DES RÈGLEMENTS (RÉELS & SIMULÉS) ─── */}
        <View style={{ gap: spacing.md }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
            <Ionicons name="time-outline" size={20} color={customColors.secondary} />
            <Text style={{ fontSize: 18, fontWeight: '700', color: customColors.primary }}>
              Historique des règlements
            </Text>
          </View>

          <Card variant="default" style={{ padding: 0, overflow: 'hidden', borderColor: `${customColors.border}80` }}>
            {/* Règlements Réels (s'il y en a) */}
            {paidNotices.map((item) => (
              <View 
                key={item.id} 
                style={{ 
                  flexDirection: 'row', 
                  alignItems: 'center', 
                  padding: spacing.md, 
                  borderBottomWidth: 1, 
                  borderBottomColor: `${customColors.border}30` 
                }}
              >
                <Ionicons name="checkmark-circle" size={22} color={customColors.gabonGreen} style={{ marginRight: spacing.md }} />
                <View style={{ flex: 1 }}>
                  <Text style={{ fontSize: 14, fontWeight: '700', color: customColors.textPrimary }}>
                    {item.tax?.name ?? 'Avis réglé'}
                  </Text>
                  <Text style={{ fontSize: 11, color: customColors.textSecondary, marginTop: 2 }}>
                    Payé le {item.paid_at ? new Date(item.paid_at).toLocaleDateString('fr-FR') : ''}
                  </Text>
                </View>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.md }}>
                  <Text style={{ fontSize: 14, fontWeight: '700', color: customColors.primary }}>
                    {item.total_amount_formatted}
                  </Text>
                  {item.receipt?.verification_url && (
                    <Pressable
                      onPress={() => handleViewReceipt(item.receipt!.verification_url)}
                      style={({ pressed }) => ({
                        opacity: pressed ? 0.7 : 1,
                      })}
                    >
                      <Ionicons name="download-outline" size={20} color={customColors.secondary} />
                    </Pressable>
                  )}
                </View>
              </View>
            ))}

            {/* Règlements Mockés (Maquette) */}
            {mockPaidNotices.map((item) => (
              <View 
                key={item.id} 
                style={{ 
                  flexDirection: 'row', 
                  alignItems: 'center', 
                  padding: spacing.md, 
                  borderBottomWidth: item.id === 'mock-3' ? 0 : 1, 
                  borderBottomColor: `${customColors.border}30` 
                }}
              >
                <Ionicons name="checkmark-circle" size={22} color={customColors.gabonGreen} style={{ marginRight: spacing.md }} />
                <View style={{ flex: 1 }}>
                  <Text style={{ fontSize: 14, fontWeight: '700', color: customColors.textPrimary }}>
                    {item.tax.name}
                  </Text>
                  <Text style={{ fontSize: 11, color: customColors.textSecondary, marginTop: 2 }}>
                    Payé le {new Date(item.paid_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}
                  </Text>
                </View>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.md }}>
                  <Text style={{ fontSize: 14, fontWeight: '700', color: customColors.primary }}>
                    {item.total_amount_formatted}
                  </Text>
                  <Pressable
                    onPress={() => Alert.alert('Justificatif fiscal', 'Ce reçu concerne un historique simulé issu de la maquette.')}
                    style={({ pressed }) => ({
                      opacity: pressed ? 0.7 : 1,
                    })}
                  >
                    <Ionicons name="download-outline" size={20} color={customColors.secondary} />
                  </Pressable>
                </View>
              </View>
            ))}
          </Card>
        </View>

      </View>

      {/* ─── MODALE DE PAIEMENT MOBILE MONEY (SINGPAY) ─── */}
      <Modal
        visible={selectedNotice !== null}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setSelectedNotice(null)}
      >
        <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(0,0,0,0.5)', padding: spacing.lg }}>
          <View style={{ backgroundColor: customColors.surface, width: '100%', borderRadius: 16, padding: spacing.lg, gap: spacing.md, ...shadows.subtle }}>
            
            {paymentStep === 'input' ? (
              <>
                <Text style={{ fontSize: 18, fontWeight: '700', color: customColors.primary }}>
                  Réglage Mobile Money (SingPay)
                </Text>
                <Text style={{ fontSize: 13, color: customColors.textSecondary }}>
                  Paiement de <Text style={{ fontWeight: '700', color: customColors.textPrimary }}>{selectedNotice?.total_amount_formatted}</Text> pour l'avis "{selectedNotice?.tax?.name}".
                </Text>

                {/* Operator Selector */}
                <View style={{ gap: spacing.xs, marginTop: spacing.xs }}>
                  <Text style={{ fontSize: 12, fontWeight: '600', color: customColors.textSecondary }}>Opérateur :</Text>
                  <View style={{ flexDirection: 'row', gap: spacing.md }}>
                    <Pressable 
                      onPress={() => setOperator('moov_money')}
                      style={{
                        flex: 1,
                        padding: spacing.md,
                        borderRadius: 8,
                        borderWidth: 2,
                        borderColor: operator === 'moov_money' ? customColors.primary : `${customColors.border}50`,
                        alignItems: 'center',
                        backgroundColor: operator === 'moov_money' ? '#e6f0ff' : 'transparent',
                      }}
                    >
                      <Text style={{ fontSize: 13, fontWeight: '700', color: operator === 'moov_money' ? customColors.primary : customColors.textPrimary }}>
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
                        borderColor: operator === 'airtel_money' ? customColors.primary : `${customColors.border}50`,
                        alignItems: 'center',
                        backgroundColor: operator === 'airtel_money' ? '#e6f0ff' : 'transparent',
                      }}
                    >
                      <Text style={{ fontSize: 13, fontWeight: '700', color: operator === 'airtel_money' ? customColors.primary : customColors.textPrimary }}>
                        Airtel Money
                      </Text>
                    </Pressable>
                  </View>
                </View>

                {/* Phone Input */}
                <View style={{ gap: spacing.xs }}>
                  <Text style={{ fontSize: 12, fontWeight: '600', color: customColors.textSecondary }}>Numéro de téléphone :</Text>
                  <TextInput
                    value={phone}
                    onChangeText={setPhone}
                    placeholder="06 XX XX XX"
                    keyboardType="phone-pad"
                    style={{
                      borderWidth: 1,
                      borderColor: `${customColors.border}80`,
                      borderRadius: 8,
                      padding: spacing.md,
                      fontSize: 15,
                      color: customColors.textPrimary,
                      backgroundColor: '#f9f9f9',
                    }}
                  />
                </View>

                {/* Buttons */}
                <View style={{ flexDirection: 'row', gap: spacing.md, marginTop: spacing.sm }}>
                  <Pressable
                    disabled={selfPaying}
                    onPress={() => setSelectedNotice(null)}
                    style={({ pressed }) => ({
                      flex: 1,
                      padding: spacing.md,
                      borderRadius: 8,
                      borderWidth: 1,
                      borderColor: `${customColors.border}80`,
                      alignItems: 'center',
                      opacity: pressed ? 0.7 : 1,
                    })}
                  >
                    <Text style={{ fontWeight: '600', color: customColors.textSecondary, fontSize: 14 }}>
                      Annuler
                    </Text>
                  </Pressable>

                  <Pressable
                    disabled={selfPaying}
                    onPress={handleInitiatePayment}
                    style={({ pressed }) => ({
                      flex: 2,
                      padding: spacing.md,
                      borderRadius: 8,
                      backgroundColor: customColors.primary,
                      alignItems: 'center',
                      justifyContent: 'center',
                      flexDirection: 'row',
                      gap: 8,
                      opacity: pressed ? 0.9 : 1,
                    })}
                  >
                    {selfPaying && <ActivityIndicator color="#ffffff" size="small" />}
                    <Text style={{ fontWeight: '700', color: '#ffffff', fontSize: 14 }}>
                      Initier le règlement
                    </Text>
                  </Pressable>
                </View>
              </>
            ) : (
              <>
                <View style={{ alignItems: 'center', gap: spacing.sm }}>
                  <Ionicons name="phone-portrait-outline" size={48} color={customColors.primary} />
                  <Text style={{ fontSize: 18, fontWeight: '700', color: customColors.primary, textAlign: 'center' }}>
                    En attente de validation
                  </Text>
                </View>
                
                <Text style={{ fontSize: 13, color: customColors.textSecondary, textAlign: 'center', marginVertical: spacing.md, lineHeight: 20 }}>
                  Une demande de confirmation de paiement (Push USSD) a été envoyée au numéro{' '}
                  <Text style={{ fontWeight: '700', color: customColors.textPrimary }}>{phone}</Text>.{'\n'}{'\n'}
                  Veuillez composer votre code secret sur votre téléphone pour approuver le paiement de{' '}
                  <Text style={{ fontWeight: '700', color: customColors.primary }}>{selectedNotice?.total_amount_formatted}</Text>.
                </Text>

                <Pressable
                  onPress={handleFinishedUssd}
                  style={({ pressed }) => ({
                    padding: spacing.md,
                    borderRadius: 8,
                    backgroundColor: customColors.primary,
                    alignItems: 'center',
                    marginTop: spacing.sm,
                    opacity: pressed ? 0.9 : 1,
                  })}
                >
                  <Text style={{ color: '#ffffff', fontWeight: '700', fontSize: 14 }}>
                    J'ai validé le code secret
                  </Text>
                </Pressable>
              </>
            )}

          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}


