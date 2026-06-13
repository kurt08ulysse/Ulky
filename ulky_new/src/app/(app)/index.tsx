import React from 'react';
import { ScrollView, Text, View, Pressable } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useUser } from '@clerk/expo';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { getMe } from '@/services/auth';
import { useTaxNotices } from '@/hooks/useTaxes';
import { spacing, shadows } from '@/theme';
import { Card, Badge } from '@/components';

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

export default function HomeScreen() {
  const { user } = useUser();
  const router = useRouter();
  
  const { data: currentUser } = useQuery({
    queryKey: ['me'],
    queryFn: getMe,
  });

  const { data: notices } = useTaxNotices();

  // Filtrer les avis en attente pour le bandeau d'alerte
  const pendingNotices = notices?.filter(notice => notice.status === 'pending') ?? [];
  const firstName = currentUser?.name?.split(' ')[0] ?? user?.firstName ?? 'Citoyen';

  return (
    <ScrollView
      style={{
        flex: 1,
        backgroundColor: customColors.background,
      }}
      contentContainerStyle={{
        paddingBottom: spacing.xl * 2,
      }}
      showsVerticalScrollIndicator={false}
    >
      {/* Drapeau du Gabon en bordure supérieure */}
      <View style={{ height: 4, flexDirection: 'row' }}>
        <View style={{ flex: 1, backgroundColor: customColors.gabonGreen }} />
        <View style={{ flex: 1, backgroundColor: customColors.gabonYellow }} />
        <View style={{ flex: 1, backgroundColor: customColors.gabonBlue }} />
      </View>

      {/* ─── BARRE DE NAVIGATION SUPÉRIEURE (HEADER) ─── */}
      <View
        style={{
          backgroundColor: customColors.surface,
          borderBottomWidth: 1,
          borderBottomColor: `${customColors.border}80`,
          paddingHorizontal: spacing.lg,
          paddingVertical: spacing.md,
          flexDirection: 'row',
          justifyContent: 'space-between',
          alignItems: 'center',
          ...shadows.subtle,
        }}
      >
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
          <View
            style={{
              width: 36,
              height: 36,
              borderRadius: 18,
              backgroundColor: '#d5e3ff',
              justifyContent: 'center',
              alignItems: 'center',
            }}
          >
            <Ionicons name="business" size={18} color={customColors.primary} />
          </View>
          <View>
            <Text style={{ fontSize: 16, fontWeight: '700', color: customColors.primary }}>
              Mairie de Franceville
            </Text>
            <Text style={{ fontSize: 10, color: customColors.secondary }}>
              Portail Officiel
            </Text>
          </View>
        </View>

        <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
          <Pressable
            accessibilityLabel="Notifications"
            accessibilityRole="button"
            style={({ pressed }) => ({
              width: 36,
              height: 36,
              borderRadius: 18,
              backgroundColor: customColors.background,
              justifyContent: 'center',
              alignItems: 'center',
              opacity: pressed ? 0.8 : 1,
            })}
          >
            <Ionicons name="notifications" size={20} color={customColors.secondary} />
            {pendingNotices.length > 0 && (
              <View
                style={{
                  position: 'absolute',
                  top: 8,
                  right: 8,
                  width: 8,
                  height: 8,
                  borderRadius: 4,
                  backgroundColor: '#ba1a1a',
                }}
              />
            )}
          </Pressable>

          <Pressable
            accessibilityLabel="Mon Profil"
            accessibilityRole="button"
            onPress={() => router.push('/profile' as any)}
            style={({ pressed }) => ({
              width: 36,
              height: 36,
              borderRadius: 18,
              backgroundColor: customColors.background,
              justifyContent: 'center',
              alignItems: 'center',
              opacity: pressed ? 0.8 : 1,
            })}
          >
            <Ionicons name="person-circle" size={24} color={customColors.secondary} />
          </Pressable>
        </View>
      </View>

      {/* ─── CONTENU PRINCIPAL ─── */}
      <View style={{ gap: spacing.lg, paddingTop: spacing.lg }}>
        
        {/* Welcome Section */}
        <View style={{ paddingHorizontal: spacing.lg, gap: 4 }}>
          <View style={{ flexDirection: 'row', gap: 6, marginBottom: 4 }}>
            <View style={{ height: 2, width: 24, backgroundColor: customColors.gabonGreen, borderRadius: 1 }} />
            <View style={{ height: 2, width: 24, backgroundColor: customColors.gabonYellow, borderRadius: 1 }} />
            <View style={{ height: 2, width: 24, backgroundColor: customColors.gabonBlue, borderRadius: 1 }} />
          </View>
          <Text style={{ fontSize: 26, fontWeight: '700', color: customColors.primary, letterSpacing: -0.5 }}>
            Bienvenue sur votre espace citoyen, {firstName}
          </Text>
          <Text style={{ fontSize: 14, color: customColors.textSecondary, lineHeight: 22, marginTop: 4 }}>
            Gérez vos démarches administratives, suivez vos dossiers et interagissez avec les services de la mairie en toute simplicité.
          </Text>
        </View>

        {/* Alerte Taxes Fiscale en attente (Backend link) */}
        {pendingNotices.length > 0 && (
          <Pressable
            onPress={() => router.push('/taxes' as any)}
            style={({ pressed }) => ({
              marginHorizontal: spacing.lg,
              backgroundColor: customColors.errorContainer,
              borderRadius: 12,
              borderWidth: 1,
              borderColor: `${customColors.border}80`,
              padding: spacing.md,
              flexDirection: 'row',
              alignItems: 'center',
              gap: spacing.sm,
              opacity: pressed ? 0.95 : 1,
            })}
          >
            <Ionicons name="alert-circle-outline" size={24} color="#ba1a1a" />
            <View style={{ flex: 1 }}>
              <Text style={{ fontSize: 14, fontWeight: '700', color: customColors.onErrorContainer }}>
                Avis de taxe en attente
              </Text>
              <Text style={{ fontSize: 12, color: customColors.onErrorContainer, marginTop: 2 }}>
                Vous avez {pendingNotices.length} {pendingNotices.length > 1 ? 'avis fiscaux' : 'avis fiscal'} à régler sur votre espace.
              </Text>
            </View>
            <View style={{ backgroundColor: '#ba1a1a', borderRadius: 8, paddingVertical: 6, paddingHorizontal: 12 }}>
              <Text style={{ color: '#ffffff', fontSize: 12, fontWeight: '700' }}>Payer</Text>
            </View>
          </Pressable>
        )}

        {/* Bento Grid Metrics */}
        <View style={{ paddingHorizontal: spacing.lg, gap: spacing.md }}>
          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            {/* Demandes en cours */}
            <View
              style={{
                flex: 1,
                backgroundColor: customColors.surface,
                borderRadius: 12,
                padding: spacing.md,
                borderWidth: 1,
                borderColor: `${customColors.border}50`,
                position: 'relative',
                overflow: 'hidden',
                ...shadows.subtle,
              }}
            >
              <View style={{ position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, backgroundColor: '#b1c9e2' }} />
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8, paddingLeft: 6 }}>
                <View style={{ width: 32, height: 32, borderRadius: 6, backgroundColor: '#cde5ff', justifyContent: 'center', alignItems: 'center' }}>
                  <Ionicons name="hourglass-outline" size={18} color={customColors.primary} />
                </View>
                <Text style={{ fontSize: 22, fontWeight: '700', color: customColors.primary }}>3</Text>
              </View>
              <Text style={{ fontSize: 11, fontWeight: '600', color: customColors.textSecondary, paddingLeft: 6, letterSpacing: 0.2 }}>
                Demandes en cours
              </Text>
            </View>

            {/* Documents prêts */}
            <View
              style={{
                flex: 1,
                backgroundColor: customColors.surface,
                borderRadius: 12,
                padding: spacing.md,
                borderWidth: 1,
                borderColor: `${customColors.border}50`,
                position: 'relative',
                overflow: 'hidden',
                ...shadows.subtle,
              }}
            >
              <View style={{ position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, backgroundColor: customColors.gabonGreen }} />
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8, paddingLeft: 6 }}>
                <View style={{ width: 32, height: 32, borderRadius: 6, backgroundColor: customColors.successContainer, justifyContent: 'center', alignItems: 'center' }}>
                  <Ionicons name="checkmark-done-circle-outline" size={18} color={customColors.gabonGreen} />
                </View>
                <Text style={{ fontSize: 22, fontWeight: '700', color: customColors.primary }}>1</Text>
              </View>
              <Text style={{ fontSize: 11, fontWeight: '600', color: customColors.textSecondary, paddingLeft: 6, letterSpacing: 0.2 }}>
                Documents prêts
              </Text>
            </View>
          </View>

          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            {/* Prochains RDV */}
            <View
              style={{
                flex: 1,
                backgroundColor: customColors.surface,
                borderRadius: 12,
                padding: spacing.md,
                borderWidth: 1,
                borderColor: `${customColors.border}50`,
                position: 'relative',
                overflow: 'hidden',
                ...shadows.subtle,
              }}
            >
              <View style={{ position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, backgroundColor: customColors.gabonBlue }} />
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8, paddingLeft: 6 }}>
                <View style={{ width: 32, height: 32, borderRadius: 6, backgroundColor: '#c9e2fc', justifyContent: 'center', alignItems: 'center' }}>
                  <Ionicons name="calendar-outline" size={18} color={customColors.gabonBlue} />
                </View>
                <Text style={{ fontSize: 22, fontWeight: '700', color: customColors.primary }}>2</Text>
              </View>
              <Text style={{ fontSize: 11, fontWeight: '600', color: customColors.textSecondary, paddingLeft: 6, letterSpacing: 0.2 }}>
                Prochains RDV
              </Text>
            </View>

            {/* Messages non lus */}
            <View
              style={{
                flex: 1,
                backgroundColor: customColors.surface,
                borderRadius: 12,
                padding: spacing.md,
                borderWidth: 1,
                borderColor: `${customColors.border}50`,
                position: 'relative',
                overflow: 'hidden',
                ...shadows.subtle,
              }}
            >
              <View style={{ position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, backgroundColor: '#ba1a1a' }} />
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8, paddingLeft: 6 }}>
                <View style={{ width: 32, height: 32, borderRadius: 6, backgroundColor: customColors.errorContainer, justifyContent: 'center', alignItems: 'center' }}>
                  <Ionicons name="mail-unread-outline" size={18} color="#ba1a1a" />
                </View>
                <Text style={{ fontSize: 22, fontWeight: '700', color: customColors.primary }}>5</Text>
              </View>
              <Text style={{ fontSize: 11, fontWeight: '600', color: customColors.textSecondary, paddingLeft: 6, letterSpacing: 0.2 }}>
                Messages non lus
              </Text>
            </View>
          </View>
        </View>

        {/* ─── SECTION DÉMARCHES (STATUT) ─── */}
        <View style={{ paddingHorizontal: spacing.lg, gap: spacing.md }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={{ fontSize: 18, fontWeight: '700', color: customColors.primary }}>
              Statut de mes démarches
            </Text>
            <Pressable>
              <Text style={{ fontSize: 13, color: '#3a5f94', fontWeight: '600' }}>
                Voir tout
              </Text>
            </Pressable>
          </View>
          
          <Card variant="default" style={{ padding: 0, overflow: 'hidden', borderColor: `${customColors.border}80` }}>
            {/* CNI */}
            <View style={{ flexDirection: 'row', alignItems: 'center', padding: spacing.md, borderBottomWidth: 1, borderBottomColor: `${customColors.border}30` }}>
              <View style={{ width: 36, height: 36, borderRadius: 6, backgroundColor: customColors.background, justifyContent: 'center', alignItems: 'center', marginRight: spacing.md }}>
                <Ionicons name="card-outline" size={20} color={customColors.secondary} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, fontWeight: '600', color: customColors.textPrimary }}>Renouvellement CNI</Text>
                <Text style={{ fontSize: 12, color: customColors.textSecondary, marginTop: 2 }}>12 Mars 2024</Text>
              </View>
              <Badge label="En cours" variant="warning" />
            </View>

            {/* Birth cert */}
            <View style={{ flexDirection: 'row', alignItems: 'center', padding: spacing.md, borderBottomWidth: 1, borderBottomColor: `${customColors.border}30` }}>
              <View style={{ width: 36, height: 36, borderRadius: 6, backgroundColor: customColors.background, justifyContent: 'center', alignItems: 'center', marginRight: spacing.md }}>
                <Ionicons name="document-text-outline" size={20} color={customColors.secondary} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, fontWeight: '600', color: customColors.textPrimary }}>Acte de naissance</Text>
                <Text style={{ fontSize: 12, color: customColors.textSecondary, marginTop: 2 }}>05 Mars 2024</Text>
              </View>
              <Badge label="Terminé" variant="success" />
            </View>

            {/* Permis de construire */}
            <View style={{ flexDirection: 'row', alignItems: 'center', padding: spacing.md }}>
              <View style={{ width: 36, height: 36, borderRadius: 6, backgroundColor: customColors.background, justifyContent: 'center', alignItems: 'center', marginRight: spacing.md }}>
                <Ionicons name="home-outline" size={20} color={customColors.secondary} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, fontWeight: '600', color: customColors.textPrimary }}>Permis de construire</Text>
                <Text style={{ fontSize: 12, color: customColors.textSecondary, marginTop: 2 }}>28 Fév 2024</Text>
              </View>
              <Badge label="Action requise" variant="error" />
            </View>
          </Card>
        </View>

        {/* ─── ACTIONS RAPIDES ─── */}
        <View style={{ paddingHorizontal: spacing.lg, gap: spacing.md }}>
          <Text style={{ fontSize: 18, fontWeight: '700', color: customColors.primary }}>
            Actions rapides
          </Text>
          
          <View style={{ gap: spacing.sm }}>
            {/* Nouvelle Demande (Primary button) */}
            <Pressable
              style={({ pressed }) => ({
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: customColors.primary,
                borderRadius: 12,
                padding: spacing.md,
                opacity: pressed ? 0.9 : 1,
              })}
            >
              <View style={{ width: 36, height: 36, borderRadius: 18, backgroundColor: 'rgba(255,255,255,0.15)', justifyContent: 'center', alignItems: 'center', marginRight: spacing.md }}>
                <Ionicons name="add-circle" size={22} color="#ffffff" />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, fontWeight: '700', color: '#ffffff' }}>Nouvelle demande</Text>
                <Text style={{ fontSize: 11, color: 'rgba(255,255,255,0.8)', marginTop: 2 }}>Lancer une démarche</Text>
              </View>
              <Ionicons name="chevron-forward" size={18} color="rgba(255,255,255,0.8)" />
            </Pressable>

            {/* Prendre rdv */}
            <Pressable
              style={({ pressed }) => ({
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: customColors.surface,
                borderRadius: 12,
                padding: spacing.md,
                borderWidth: 1,
                borderColor: `${customColors.border}80`,
                opacity: pressed ? 0.9 : 1,
              })}
            >
              <View style={{ width: 36, height: 36, borderRadius: 18, backgroundColor: '#cde5ff', justifyContent: 'center', alignItems: 'center', marginRight: spacing.md }}>
                <Ionicons name="time-outline" size={20} color={customColors.primary} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, fontWeight: '700', color: customColors.textPrimary }}>Prendre rendez-vous</Text>
                <Text style={{ fontSize: 11, color: customColors.textSecondary, marginTop: 2 }}>État civil, urbanisme...</Text>
              </View>
              <Ionicons name="chevron-forward" size={18} color={customColors.secondary} />
            </Pressable>

            {/* Payer mes taxes (Redirect to Taxes) */}
            <Pressable
              onPress={() => router.push('/taxes' as any)}
              style={({ pressed }) => ({
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: customColors.surface,
                borderRadius: 12,
                padding: spacing.md,
                borderWidth: 1,
                borderColor: `${customColors.border}80`,
                opacity: pressed ? 0.9 : 1,
              })}
            >
              <View style={{ width: 36, height: 36, borderRadius: 18, backgroundColor: customColors.successContainer, justifyContent: 'center', alignItems: 'center', marginRight: spacing.md }}>
                <Ionicons name="wallet-outline" size={20} color={customColors.gabonGreen} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, fontWeight: '700', color: customColors.textPrimary }}>Payer mes taxes</Text>
                <Text style={{ fontSize: 11, color: customColors.textSecondary, marginTop: 2 }}>Accès au portail fiscal</Text>
              </View>
              <Ionicons name="chevron-forward" size={18} color={customColors.secondary} />
            </Pressable>

            {/* Contacter la mairie */}
            <Pressable
              style={({ pressed }) => ({
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: customColors.surface,
                borderRadius: 12,
                padding: spacing.md,
                borderWidth: 1,
                borderColor: `${customColors.border}80`,
                opacity: pressed ? 0.9 : 1,
              })}
            >
              <View style={{ width: 36, height: 36, borderRadius: 18, backgroundColor: customColors.errorContainer, justifyContent: 'center', alignItems: 'center', marginRight: spacing.md }}>
                <Ionicons name="chatbubble-ellipses-outline" size={20} color="#ba1a1a" />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, fontWeight: '700', color: customColors.textPrimary }}>Contacter la mairie</Text>
                <Text style={{ fontSize: 11, color: customColors.textSecondary, marginTop: 2 }}>Assistance citoyenne</Text>
              </View>
              <Ionicons name="chevron-forward" size={18} color={customColors.secondary} />
            </Pressable>
          </View>
        </View>

      </View>
    </ScrollView>
  );
}
