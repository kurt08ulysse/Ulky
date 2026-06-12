import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  TextInput,
  Modal,
  ActivityIndicator,
  Alert,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useAdminTaxNotices, useCreateAdminTaxNotice, useCitizenSearch } from '@/hooks/useAdmin';
import { useTaxes } from '@/hooks/useTaxes';
import { cancelTaxNotice } from '@/services/taxService';
import { useQueryClient } from '@tanstack/react-query';
import { Badge } from '@/components';
import { colors, spacing, typography } from '@/theme';
import type { AdminTaxNotice } from '@/services/adminService';

type FilterStatus = 'all' | 'pending' | 'paid' | 'cancelled';

/**
 * Liste de TOUS les avis de taxes (tous contribuables) — vue régisseur.
 * Inclut recherche, filtres statut, création d'avis et annulation.
 */
export default function AdminNoticesScreen() {
  const qc = useQueryClient();

  // Filtres
  const [filterStatus, setFilterStatus] = useState<FilterStatus>('all');
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [searchTimeout, setSearchTimeout] = useState<ReturnType<typeof setTimeout> | null>(null);

  // Modal création
  const [showCreate, setShowCreate] = useState(false);
  const [createPhone, setCreatePhone] = useState('');
  const [createTaxId, setCreateTaxId] = useState<number | null>(null);
  const [phoneDebouncedSearch, setPhoneDebouncedSearch] = useState('');
  const [phoneSearchTimeout, setPhoneSearchTimeout] = useState<ReturnType<typeof setTimeout> | null>(null);

  // Données
  const { data: noticesData, isLoading, refetch, isRefetching } = useAdminTaxNotices({
    status: filterStatus === 'all' ? undefined : filterStatus,
    search: debouncedSearch || undefined,
  });

  const { data: taxes } = useTaxes();
  const { data: citizenResults } = useCitizenSearch(phoneDebouncedSearch);
  const createMutation = useCreateAdminTaxNotice();

  // Debounce sur la barre de recherche principale
  const handleSearchChange = (text: string) => {
    setSearch(text);
    if (searchTimeout) clearTimeout(searchTimeout);
    const t = setTimeout(() => setDebouncedSearch(text), 500);
    setSearchTimeout(t);
  };

  // Debounce sur la recherche de citoyen
  const handlePhoneChange = (text: string) => {
    setCreatePhone(text);
    if (phoneSearchTimeout) clearTimeout(phoneSearchTimeout);
    const t = setTimeout(() => setPhoneDebouncedSearch(text), 500);
    setPhoneSearchTimeout(t);
  };

  const handleCancel = useCallback((notice: AdminTaxNotice) => {
    Alert.alert(
      'Annuler l\'avis de taxe',
      `Annuler l'avis "${notice.tax?.name ?? '#' + notice.id}" de ${notice.citizen?.name ?? 'ce contribuable'} ? Cette action est irréversible.`,
      [
        { text: 'Retour', style: 'cancel' },
        {
          text: 'Oui, annuler',
          style: 'destructive',
          onPress: async () => {
            try {
              await cancelTaxNotice(notice.id);
              qc.invalidateQueries({ queryKey: ['admin', 'tax-notices'] });
              qc.invalidateQueries({ queryKey: ['admin', 'dashboard'] });
            } catch {
              Alert.alert('Erreur', 'Impossible d\'annuler cet avis.');
            }
          },
        },
      ]
    );
  }, [qc]);

  const handleCreate = () => {
    if (!createTaxId) {
      Alert.alert('Erreur', 'Veuillez sélectionner une taxe.');
      return;
    }
    if (!createPhone || createPhone.length < 6) {
      Alert.alert('Erreur', 'Veuillez saisir un numéro de téléphone valide.');
      return;
    }
    createMutation.mutate(
      { tax_id: createTaxId, phone: createPhone },
      {
        onSuccess: () => {
          setShowCreate(false);
          setCreatePhone('');
          setCreateTaxId(null);
          Alert.alert('Succès', 'L\'avis de taxe a été créé et assigné au contribuable.');
        },
        onError: (err: any) => {
          const msg = err?.response?.data?.message ?? 'Une erreur est survenue.';
          Alert.alert('Erreur', msg);
        },
      }
    );
  };

  const getStatusBadge = (status: string, dueDate: string) => {
    if (status === 'paid') return { label: '✓ Payé', variant: 'success' as const };
    if (status === 'cancelled') return { label: '✕ Annulé', variant: 'primary' as const };
    const isLate = new Date(dueDate) < new Date();
    return isLate
      ? { label: '⚠️ En retard', variant: 'error' as const }
      : { label: '⏳ En attente', variant: 'warning' as const };
  };

  const notices = noticesData?.data ?? [];

  return (
    <View style={styles.root}>
      {/* ── Barre de recherche ── */}
      <View style={styles.searchRow}>
        <TextInput
          value={search}
          onChangeText={handleSearchChange}
          placeholder="Nom, téléphone, n° quittance…"
          style={styles.searchInput}
          placeholderTextColor={colors.placeholder}
          clearButtonMode="while-editing"
        />
        <Pressable style={styles.createBtn} onPress={() => setShowCreate(true)}>
          <Text style={styles.createBtnText}>+ Avis</Text>
        </Pressable>
      </View>

      {/* ── Filtres statut ── */}
      <View style={styles.filterRow}>
        {(['all', 'pending', 'paid', 'cancelled'] as FilterStatus[]).map((tab) => {
          const isActive = filterStatus === tab;
          const label = { all: 'Tous', pending: 'En attente', paid: 'Payés', cancelled: 'Annulés' }[tab];
          return (
            <Pressable
              key={tab}
              onPress={() => setFilterStatus(tab)}
              style={[styles.filterTab, isActive && styles.filterTabActive]}
            >
              <Text style={[styles.filterTabText, isActive && styles.filterTabTextActive]}>
                {label}
              </Text>
            </Pressable>
          );
        })}
      </View>

      {/* ── Liste ── */}
      {isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary[600]} />
        </View>
      ) : (
        <FlatList
          data={notices}
          keyExtractor={(item) => item.id.toString()}
          onRefresh={refetch}
          refreshing={isRefetching}
          contentContainerStyle={styles.listContent}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={{ fontSize: 40 }}>📄</Text>
              <Text style={styles.emptyText}>Aucun avis trouvé</Text>
            </View>
          }
          renderItem={({ item }) => {
            const { label, variant } = getStatusBadge(item.status, item.due_date);
            return (
              <View style={styles.noticeCard}>
                {/* Ligne 1 : Taxe + Badge */}
                <View style={styles.noticeLine}>
                  <Text style={styles.noticeTaxName} numberOfLines={1}>
                    {item.tax?.name ?? `Avis #${item.id}`}
                  </Text>
                  <Badge label={label} variant={variant} />
                </View>

                {/* Ligne 2 : Contribuable */}
                <View style={styles.citizenRow}>
                  <Text style={styles.citizenIcon}>👤</Text>
                  <Text style={styles.citizenName} numberOfLines={1}>
                    {item.citizen?.name ?? '—'}
                  </Text>
                  <Text style={styles.citizenPhone}>{item.citizen?.phone ?? ''}</Text>
                </View>

                {/* Ligne 3 : Montant + Date */}
                <View style={styles.noticeLine}>
                  <Text style={styles.noticeAmount}>{item.total_amount_formatted}</Text>
                  <Text style={styles.noticeDate}>
                    Échéance : {new Date(item.due_date).toLocaleDateString('fr-FR')}
                  </Text>
                </View>

                {/* Actions */}
                {item.status === 'pending' && (
                  <Pressable
                    style={styles.cancelBtn}
                    onPress={() => handleCancel(item)}
                  >
                    <Text style={styles.cancelBtnText}>Annuler cet avis</Text>
                  </Pressable>
                )}
                {item.status === 'paid' && item.receipt?.verification_url && (
                  <Text style={styles.receiptRef}>
                    🧾 Quittance : {item.receipt.number ?? '—'}
                  </Text>
                )}
              </View>
            );
          }}
        />
      )}

      {/* ── Modal Création ── */}
      <Modal visible={showCreate} transparent animationType="slide" onRequestClose={() => setShowCreate(false)}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalBox}>
            <Text style={styles.modalTitle}>Créer un avis de taxe</Text>

            {/* Téléphone */}
            <Text style={styles.modalLabel}>Numéro de téléphone du contribuable</Text>
            <TextInput
              value={createPhone}
              onChangeText={handlePhoneChange}
              placeholder="+241 06 XX XX XX"
              keyboardType="phone-pad"
              style={styles.modalInput}
              placeholderTextColor={colors.placeholder}
            />

            {/* Résultats recherche citoyen */}
            {citizenResults && citizenResults.length > 0 && (
              <View style={styles.citizenResults}>
                {citizenResults.map((c) => (
                  <Pressable
                    key={c.id}
                    style={styles.citizenResult}
                    onPress={() => setCreatePhone(c.phone)}
                  >
                    <Text style={styles.citizenResultName}>{c.name}</Text>
                    <Text style={styles.citizenResultPhone}>{c.phone}</Text>
                  </Pressable>
                ))}
              </View>
            )}
            {phoneDebouncedSearch.length >= 4 && citizenResults?.length === 0 && (
              <Text style={styles.notFound}>
                ⚠️ Aucun contribuable trouvé — l'avis sera refusé.
              </Text>
            )}

            {/* Sélection de la taxe */}
            <Text style={styles.modalLabel}>Type de taxe</Text>
            <View style={styles.taxGrid}>
              {(taxes ?? []).slice(0, 8).map((tax) => (
                <Pressable
                  key={tax.id}
                  onPress={() => setCreateTaxId(tax.id)}
                  style={[
                    styles.taxChip,
                    createTaxId === tax.id && styles.taxChipActive,
                  ]}
                >
                  <Text
                    style={[
                      styles.taxChipText,
                      createTaxId === tax.id && styles.taxChipTextActive,
                    ]}
                    numberOfLines={2}
                  >
                    {tax.name}
                  </Text>
                  <Text style={styles.taxChipAmount}>{tax.total_amount_formatted}</Text>
                </Pressable>
              ))}
            </View>

            {/* Boutons */}
            <View style={styles.modalBtns}>
              <Pressable style={styles.modalCancelBtn} onPress={() => setShowCreate(false)}>
                <Text style={styles.modalCancelBtnText}>Annuler</Text>
              </Pressable>
              <Pressable
                style={[styles.modalConfirmBtn, createMutation.isPending && { opacity: 0.6 }]}
                onPress={handleCreate}
                disabled={createMutation.isPending}
              >
                {createMutation.isPending ? (
                  <ActivityIndicator size="small" color={colors.text.inverse} />
                ) : (
                  <Text style={styles.modalConfirmBtnText}>Créer l'avis</Text>
                )}
              </Pressable>
            </View>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background.subtle },
  searchRow: {
    flexDirection: 'row',
    padding: spacing.md,
    gap: spacing.md,
    backgroundColor: colors.background.default,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.light,
  },
  searchInput: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: 8,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    fontSize: 14,
    color: colors.text.primary,
    backgroundColor: colors.neutral[50],
  },
  createBtn: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    backgroundColor: colors.primary[600],
    borderRadius: 8,
    justifyContent: 'center',
  },
  createBtnText: { ...typography.caption, fontWeight: '700', color: colors.text.inverse },
  filterRow: {
    flexDirection: 'row',
    backgroundColor: colors.neutral[100],
    padding: 4,
    gap: 4,
  },
  filterTab: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 6,
    alignItems: 'center',
  },
  filterTabActive: { backgroundColor: colors.background.default },
  filterTabText: { fontSize: 11, fontWeight: '500', color: colors.text.secondary },
  filterTabTextActive: { fontWeight: '700', color: colors.primary[600] },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  listContent: { padding: spacing.md, gap: spacing.md, paddingBottom: spacing.xl * 2 },
  empty: { alignItems: 'center', paddingVertical: spacing.xl * 2, gap: spacing.md },
  emptyText: { ...typography.body, color: colors.text.secondary },
  noticeCard: {
    backgroundColor: colors.background.default,
    borderRadius: 12,
    padding: spacing.md,
    gap: spacing.sm,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  noticeLine: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  noticeTaxName: { ...typography.body, fontWeight: '700', color: colors.text.primary, flex: 1, marginRight: 8 },
  citizenRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
  citizenIcon: { fontSize: 14 },
  citizenName: { ...typography.caption, color: colors.text.secondary, flex: 1 },
  citizenPhone: { ...typography.caption, color: colors.text.tertiary },
  noticeAmount: { ...typography.body, fontWeight: '700', color: colors.primary[600] },
  noticeDate: { ...typography.caption, color: colors.text.tertiary },
  cancelBtn: {
    paddingVertical: spacing.sm,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.error[600],
    alignItems: 'center',
  },
  cancelBtnText: { ...typography.caption, fontWeight: '700', color: colors.error[600] },
  receiptRef: { ...typography.caption, color: colors.text.tertiary },
  // Modal
  modalOverlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.4)' },
  modalBox: {
    backgroundColor: colors.background.default,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    padding: spacing.lg,
    gap: spacing.md,
    maxHeight: '90%',
  },
  modalTitle: { fontSize: 18, fontWeight: '800', color: colors.text.primary },
  modalLabel: { ...typography.caption, color: colors.text.secondary, marginTop: 4 },
  modalInput: {
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: 8,
    padding: spacing.md,
    fontSize: 15,
    color: colors.text.primary,
    backgroundColor: colors.neutral[50],
  },
  citizenResults: {
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: 8,
    overflow: 'hidden',
  },
  citizenResult: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    padding: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.light,
  },
  citizenResultName: { ...typography.caption, fontWeight: '600', color: colors.text.primary },
  citizenResultPhone: { ...typography.caption, color: colors.text.secondary },
  notFound: { ...typography.caption, color: colors.warning[600] },
  taxGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  taxChip: {
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: 8,
    borderWidth: 1.5,
    borderColor: colors.border.default,
    maxWidth: '48%',
  },
  taxChipActive: { borderColor: colors.primary[600], backgroundColor: `${colors.primary[600]}12` },
  taxChipText: { fontSize: 12, color: colors.text.secondary },
  taxChipTextActive: { color: colors.primary[600], fontWeight: '700' },
  taxChipAmount: { fontSize: 10, color: colors.text.tertiary, marginTop: 2 },
  modalBtns: { flexDirection: 'row', gap: spacing.md, marginTop: spacing.xs },
  modalCancelBtn: {
    flex: 1,
    padding: spacing.md,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border.default,
    alignItems: 'center',
  },
  modalCancelBtnText: { ...typography.button, color: colors.text.secondary },
  modalConfirmBtn: {
    flex: 2,
    padding: spacing.md,
    borderRadius: 8,
    backgroundColor: colors.primary[600],
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalConfirmBtnText: { ...typography.button, color: colors.text.inverse },
});
