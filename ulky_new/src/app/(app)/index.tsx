import React from 'react';
import { ScrollView, Text, View, Pressable, Platform } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useUser } from '@clerk/expo';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { getMe } from '@/services/auth';
import { useTaxNotices } from '@/hooks/useTaxes';
import { colors, spacing, typography, shadows } from '@/theme';
import { Card, Badge } from '@/components';

// Couleurs personnalisées inspirées des couleurs nationales (Vert, Jaune, Bleu) avec des tons premium
const customColors = {
  gabonGreen: '#0C5C36',      // Vert forêt élégant
  gabonYellow: '#F2B705',     // Jaune d'or/or chaud
  gabonBlue: '#0D3C9B',       // Bleu royal profond
  gabonLightBlue: '#E6EEFF',  // Bleu très clair pour fond de cartes
  gabonLightGreen: '#E6F4EA', // Vert très clair pour fond de cartes
  gabonLightYellow: '#FFF8E1' // Jaune très clair pour fond de cartes
};

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

  // Filtrer les avis en attente pour le tableau de bord
  const pendingNotices = notices?.filter(notice => notice.status === 'pending') ?? [];
  const hasOverdue = pendingNotices.some(notice => {
    const dueDate = new Date(notice.due_date);
    const today = new Date();
    today.setHours(0,0,0,0);
    return dueDate < today;
  });

  const isLoading = isLoadingUser || isLoadingNotices;

  // Calcul du prénom/initiale pour l'avatar
  const firstName = currentUser?.name?.split(' ')[0] ?? user?.firstName ?? 'Citoyen';
  const initials = firstName.charAt(0).toUpperCase();

  return (
    <ScrollView
      style={{
        flex: 1,
        backgroundColor: colors.background.subtle,
      }}
      contentContainerStyle={{
        paddingBottom: spacing.xl,
      }}
    >
      {/* ─── EN-TÊTE PREMIUM (HEADER LARGE) ─── */}
      <View
        style={{
          backgroundColor: customColors.gabonBlue,
          borderBottomLeftRadius: 24,
          borderBottomRightRadius: 24,
          paddingHorizontal: spacing.lg,
          paddingTop: Platform.OS === 'ios' ? 64 : 48,
          paddingBottom: spacing.xl,
          ...shadows.medium,
          position: 'relative',
        }}
      >
        {/* Bandeau supérieur décoratif avec les couleurs nationales (fine ligne discrète sous le status bar) */}
        <View style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 4, flexDirection: 'row' }}>
          <View style={{ flex: 1, backgroundColor: customColors.gabonGreen }} />
          <View style={{ flex: 1, backgroundColor: customColors.gabonYellow }} />
          <View style={{ flex: 1, backgroundColor: customColors.gabonBlue }} />
        </View>

        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.lg }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.md }}>
            {/* Avatar circulaire */}
            <View
              style={{
                width: 48,
                height: 48,
                borderRadius: 24,
                backgroundColor: customColors.gabonYellow,
                justifyContent: 'center',
                alignItems: 'center',
                borderWidth: 2,
                borderColor: '#FFFFFF',
              }}
            >
              <Text style={{ ...typography.button, color: customColors.gabonBlue, fontWeight: '700' }}>
                {initials}
              </Text>
            </View>

            <View>
              <Text style={{ ...typography.h3, color: colors.text.inverse }}>
                Bonjour, {firstName}
              </Text>
              <Text style={{ ...typography.caption, color: 'rgba(255,255,255,0.7)', marginTop: 2 }}>
                {dateString}
              </Text>
            </View>
          </View>

          {/* Bouton de Notification interactif */}
          <Pressable
            accessibilityLabel="Notifications"
            accessibilityRole="button"
            style={({ pressed }) => ({
              width: 44,
              height: 44,
              borderRadius: 22,
              backgroundColor: 'rgba(255, 255, 255, 0.15)',
              justifyContent: 'center',
              alignItems: 'center',
              opacity: pressed ? 0.8 : 1,
            })}
          >
            <Ionicons name="notifications-outline" size={22} color="#FFFFFF" />
            {pendingNotices.length > 0 && (
              <View
                style={{
                  position: 'absolute',
                  top: 10,
                  right: 10,
                  width: 10,
                  height: 10,
                  borderRadius: 5,
                  backgroundColor: colors.error[600],
                  borderWidth: 1.5,
                  borderColor: customColors.gabonBlue,
                }}
              />
            )}
          </Pressable>
        </View>

        {/* Carte d'état citoyen rapide à l'intérieur du Header */}
        <View
          style={{
            backgroundColor: '#FFFFFF',
            borderRadius: 16,
            padding: spacing.md,
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginTop: spacing.sm,
            ...shadows.subtle,
          }}
        >
          <View style={{ flex: 1, gap: 4 }}>
            <Text style={{ ...typography.caption, color: colors.text.secondary }}>
              Statut fiscal de votre compte
            </Text>
            <Text style={{ ...typography.bodyLg, fontWeight: '700', color: colors.text.primary }}>
              {pendingNotices.length === 0 ? 'En règle' : `${pendingNotices.length} avis à payer`}
            </Text>
          </View>
          <Badge
            label={pendingNotices.length === 0 ? 'À Jour' : hasOverdue ? 'Retard' : 'À régler'}
            variant={pendingNotices.length === 0 ? 'success' : hasOverdue ? 'error' : 'warning'}
          />
        </View>
      </View>

      {/* ─── CONTENU PRINCIPAL ─── */}
      <View style={{ padding: spacing.lg, gap: spacing.lg }}>
        
        {/* Grille d'actions rapides épurée (Style bento discret) */}
        <View style={{ gap: spacing.md }}>
          <Text style={{ ...typography.h3, color: colors.text.primary, fontWeight: '700' }}>
            Services municipaux
          </Text>
          
          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            <Pressable
              accessibilityLabel="Payer une taxe"
              accessibilityRole="button"
              onPress={() => router.push('/taxes' as any)}
              style={({ pressed }) => [
                {
                  flex: 1,
                  padding: spacing.lg,
                  backgroundColor: '#FFFFFF',
                  borderRadius: 16,
                  alignItems: 'center',
                  gap: spacing.sm,
                  borderWidth: 1,
                  borderColor: pressed ? customColors.gabonBlue : colors.border.light,
                  ...shadows.subtle,
                  transform: [{ scale: pressed ? 0.98 : 1 }],
                }
              ]}
            >
              <View style={{ width: 48, height: 48, borderRadius: 24, backgroundColor: customColors.gabonLightBlue, justifyContent: 'center', alignItems: 'center' }}>
                <Ionicons name="card-outline" size={24} color={customColors.gabonBlue} />
              </View>
              <Text style={{ ...typography.body, fontWeight: '600', color: colors.text.primary, textAlign: 'center' }}>
                Payer une taxe
              </Text>
            </Pressable>

            <Pressable
              accessibilityLabel="Mes paiements et quittances"
              accessibilityRole="button"
              onPress={() => router.push('/taxes' as any)}
              style={({ pressed }) => [
                {
                  flex: 1,
                  padding: spacing.lg,
                  backgroundColor: '#FFFFFF',
                  borderRadius: 16,
                  alignItems: 'center',
                  gap: spacing.sm,
                  borderWidth: 1,
                  borderColor: pressed ? customColors.gabonGreen : colors.border.light,
                  ...shadows.subtle,
                  transform: [{ scale: pressed ? 0.98 : 1 }],
                }
              ]}
            >
              <View style={{ width: 48, height: 48, borderRadius: 24, backgroundColor: customColors.gabonLightGreen, justifyContent: 'center', alignItems: 'center' }}>
                <Ionicons name="receipt-outline" size={24} color={customColors.gabonGreen} />
              </View>
              <Text style={{ ...typography.body, fontWeight: '600', color: colors.text.primary, textAlign: 'center' }}>
                Mes paiements
              </Text>
            </Pressable>
          </View>

          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            <Pressable
              accessibilityLabel="Mes démarches"
              accessibilityRole="button"
              style={({ pressed }) => [
                {
                  flex: 1,
                  padding: spacing.lg,
                  backgroundColor: '#FFFFFF',
                  borderRadius: 16,
                  alignItems: 'center',
                  gap: spacing.sm,
                  borderWidth: 1,
                  borderColor: pressed ? customColors.gabonYellow : colors.border.light,
                  ...shadows.subtle,
                  transform: [{ scale: pressed ? 0.98 : 1 }],
                }
              ]}
            >
              <View style={{ width: 48, height: 48, borderRadius: 24, backgroundColor: customColors.gabonLightYellow, justifyContent: 'center', alignItems: 'center' }}>
                <Ionicons name="document-text-outline" size={24} color={customColors.gabonYellow} />
              </View>
              <Text style={{ ...typography.body, fontWeight: '600', color: colors.text.primary, textAlign: 'center' }}>
                Mes démarches
              </Text>
            </Pressable>

            <Pressable
              accessibilityLabel="Aide et Support"
              accessibilityRole="button"
              style={({ pressed }) => [
                {
                  flex: 1,
                  padding: spacing.lg,
                  backgroundColor: '#FFFFFF',
                  borderRadius: 16,
                  alignItems: 'center',
                  gap: spacing.sm,
                  borderWidth: 1,
                  borderColor: pressed ? customColors.gabonBlue : colors.border.light,
                  ...shadows.subtle,
                  transform: [{ scale: pressed ? 0.98 : 1 }],
                }
              ]}
            >
              <View style={{ width: 48, height: 48, borderRadius: 24, backgroundColor: customColors.gabonLightBlue, justifyContent: 'center', alignItems: 'center' }}>
                <Ionicons name="help-circle-outline" size={24} color={customColors.gabonBlue} />
              </View>
              <Text style={{ ...typography.body, fontWeight: '600', color: colors.text.primary, textAlign: 'center' }}>
                Aide & Support
              </Text>
            </Pressable>
          </View>
        </View>

        {/* ─── SECTION DES TAXES À PAYER ─── */}
        <View style={{ gap: spacing.md, marginTop: spacing.xs }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={{ ...typography.h3, color: colors.text.primary, fontWeight: '700' }}>
              Taxes à payer
            </Text>
            {!isLoading && pendingNotices.length > 0 && (
              <Text style={{ ...typography.caption, color: colors.text.tertiary }}>
                {pendingNotices.length} avis
              </Text>
            )}
          </View>

          {isLoading ? (
            <Card variant="default">
              <Text style={{ ...typography.body, color: colors.text.secondary, textAlign: 'center' }}>
                Chargement de vos avis de taxe...
              </Text>
            </Card>
          ) : pendingNotices.length === 0 ? (
            <Card variant="default" style={{ alignItems: 'center', paddingVertical: spacing.xl, borderStyle: 'dashed' }}>
              <View
                style={{
                  width: 56,
                  height: 56,
                  borderRadius: 28,
                  backgroundColor: customColors.gabonLightGreen,
                  justifyContent: 'center',
                  alignItems: 'center',
                  marginBottom: spacing.md,
                }}
              >
                <Ionicons name="checkmark-circle-outline" size={32} color={customColors.gabonGreen} />
              </View>
              <Text style={{ ...typography.bodyLg, fontWeight: '600', color: colors.text.primary, marginBottom: 4 }}>
                Vous êtes entièrement à jour !
              </Text>
              <Text style={{ ...typography.body, color: colors.text.secondary, textAlign: 'center', paddingHorizontal: spacing.md }}>
                Aucune taxe ni loyer municipal n'est en attente de paiement pour le moment.
              </Text>
            </Card>
          ) : (
            pendingNotices.map((notice) => {
              const { label: statusLabel, variant: statusVariant } = getStatusDetails(notice.status, notice.due_date);
              return (
                <Card key={notice.id} variant="default" style={{ borderColor: colors.border.default }}>
                  <View style={{ gap: spacing.md }}>
                    <View
                      style={{
                        flexDirection: 'row',
                        justifyContent: 'space-between',
                        alignItems: 'flex-start',
                      }}
                    >
                      <View style={{ flex: 1, marginRight: spacing.md }}>
                        <Text
                          style={{
                            ...typography.bodyLg,
                            color: colors.text.primary,
                            fontWeight: '700',
                          }}
                        >
                          {notice.tax?.name ?? 'Avis de taxe'}
                        </Text>
                        <Text style={{ ...typography.caption, color: colors.text.tertiary, marginTop: 2 }}>
                          Réf : {notice.notice_number || `AVIS-${notice.id}`}
                        </Text>
                      </View>
                      <Badge label={statusLabel} variant={statusVariant} />
                    </View>

                    {/* Informations de la taxe avec mise en forme épurée */}
                    <View style={{ gap: spacing.xs, backgroundColor: colors.background.subtle, padding: spacing.md, borderRadius: 8 }}>
                      <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                        <Text style={{ ...typography.body, color: colors.text.secondary }}>Montant :</Text>
                        <Text style={{ ...typography.body, fontWeight: '700', color: colors.text.primary }}>
                          {notice.total_amount_formatted}
                        </Text>
                      </View>
                      <View style={{ flexDirection: 'row', justifyContent: 'space-between', borderTopWidth: 1, borderTopColor: colors.border.light, paddingTop: spacing.xs, marginTop: spacing.xs }}>
                        <Text style={{ ...typography.body, color: colors.text.secondary }}>Échéance :</Text>
                        <Text style={{ ...typography.body, fontWeight: '600', color: hasOverdue ? colors.error[600] : colors.text.primary }}>
                          {new Date(notice.due_date).toLocaleDateString('fr-FR')}
                        </Text>
                      </View>
                    </View>

                    {/* Bouton d'action soigné */}
                    <Pressable
                      accessibilityLabel={`Régler ${notice.tax?.name ?? 'l\'avis'}`}
                      accessibilityRole="button"
                      onPress={() => router.push('/taxes' as any)}
                      style={({ pressed }) => ({
                        padding: spacing.md,
                        borderRadius: 12,
                        backgroundColor: customColors.gabonBlue,
                        alignItems: 'center',
                        justifyContent: 'center',
                        opacity: pressed ? 0.9 : 1,
                        ...shadows.subtle,
                      })}
                    >
                      <Text style={{ ...typography.button, color: '#FFFFFF' }}>
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
