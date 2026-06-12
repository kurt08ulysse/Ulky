import React, { useState } from 'react';
import {
  View,
  Text,
  FlatList,
  Pressable,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { useAuditLogs } from '@/hooks/useAdmin';
import { colors, spacing, typography } from '@/theme';

const ACTION_LABELS: Record<string, { label: string; icon: string; color: string }> = {
  'webhook.received':       { label: 'Webhook reçu',       icon: '📡', color: colors.primary[600] },
  'payment.confirmed':      { label: 'Paiement confirmé',  icon: '✅', color: colors.success[600] },
  'payment.failed':         { label: 'Paiement échoué',    icon: '❌', color: colors.error[600] },
  'receipt.generated':      { label: 'Quittance générée',  icon: '🧾', color: colors.success[600] },
  'signature_failed':       { label: 'Signature invalide', icon: '⛔', color: colors.error[600] },
  'webhook.status_mismatch':{ label: 'Statut incohérent',  icon: '⚠️', color: colors.warning[600] },
  'unknown_payment':        { label: 'Paiement inconnu',   icon: '❓', color: colors.warning[600] },
};

const ALL_ACTIONS = ['', ...Object.keys(ACTION_LABELS)];

/**
 * Journal d'audit append-only — vue régisseur.
 * Filtrable par type d'événement, paginé.
 */
export default function AdminAuditScreen() {
  const [filterAction, setFilterAction] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading, refetch, isRefetching } = useAuditLogs({
    action: filterAction || undefined,
    page,
  });

  const logs = data?.data ?? [];
  const lastPage = data?.last_page ?? 1;

  return (
    <View style={styles.root}>
      {/* ── Filtre actions ── */}
      <View style={styles.filterScroll}>
        <FlatList
          data={ALL_ACTIONS}
          horizontal
          showsHorizontalScrollIndicator={false}
          keyExtractor={(a) => a}
          contentContainerStyle={styles.filterList}
          renderItem={({ item: action }) => {
            const isActive = filterAction === action;
            const meta = action ? ACTION_LABELS[action] : null;
            return (
              <Pressable
                onPress={() => { setFilterAction(action); setPage(1); }}
                style={[styles.chip, isActive && styles.chipActive]}
              >
                {meta && <Text>{meta.icon} </Text>}
                <Text style={[styles.chipText, isActive && styles.chipTextActive]}>
                  {meta ? meta.label : 'Tous'}
                </Text>
              </Pressable>
            );
          }}
        />
      </View>

      {/* ── Liste ── */}
      {isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary[600]} />
        </View>
      ) : (
        <FlatList
          data={logs}
          keyExtractor={(item) => item.id.toString()}
          onRefresh={refetch}
          refreshing={isRefetching}
          contentContainerStyle={styles.listContent}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={{ fontSize: 36 }}>📋</Text>
              <Text style={styles.emptyText}>Aucune entrée d'audit</Text>
            </View>
          }
          ListFooterComponent={
            lastPage > 1 ? (
              <View style={styles.pagination}>
                <Pressable
                  style={[styles.pageBtn, page <= 1 && styles.pageBtnDisabled]}
                  onPress={() => setPage((p) => Math.max(1, p - 1))}
                  disabled={page <= 1}
                >
                  <Text style={styles.pageBtnText}>← Précédent</Text>
                </Pressable>
                <Text style={styles.pageInfo}>{page} / {lastPage}</Text>
                <Pressable
                  style={[styles.pageBtn, page >= lastPage && styles.pageBtnDisabled]}
                  onPress={() => setPage((p) => Math.min(lastPage, p + 1))}
                  disabled={page >= lastPage}
                >
                  <Text style={styles.pageBtnText}>Suivant →</Text>
                </Pressable>
              </View>
            ) : null
          }
          renderItem={({ item }) => {
            const meta = ACTION_LABELS[item.action];
            const amount = item.payload?.amount
              ? ` — ${(item.payload.amount / 100).toLocaleString('fr-FR')} FCFA`
              : '';
            const ref = item.payload?.singpay_reference ?? item.payload?.transaction_id ?? null;

            return (
              <View style={styles.logEntry}>
                <View style={styles.logHeader}>
                  <View style={[styles.actionBadge, { borderColor: meta?.color ?? colors.neutral[300] }]}>
                    <Text style={styles.actionIcon}>{meta?.icon ?? '📝'}</Text>
                    <Text style={[styles.actionLabel, { color: meta?.color ?? colors.text.secondary }]}>
                      {meta?.label ?? item.action}
                    </Text>
                  </View>
                  <Text style={styles.logDate}>
                    {new Date(item.created_at).toLocaleString('fr-FR', {
                      day: '2-digit',
                      month: '2-digit',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </Text>
                </View>

                <View style={styles.logMeta}>
                  {amount ? (
                    <Text style={styles.logMetaText}>💰{amount}</Text>
                  ) : null}
                  {ref ? (
                    <Text style={styles.logMetaText} numberOfLines={1}>🔗 {ref}</Text>
                  ) : null}
                  {item.ip ? (
                    <Text style={styles.logMetaText}>🌐 {item.ip}</Text>
                  ) : null}
                </View>
              </View>
            );
          }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background.subtle },
  filterScroll: {
    backgroundColor: colors.background.default,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.light,
  },
  filterList: { padding: spacing.sm, gap: 8, flexDirection: 'row' },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: 20,
    borderWidth: 1.5,
    borderColor: colors.border.default,
    backgroundColor: colors.background.default,
  },
  chipActive: {
    borderColor: colors.primary[600],
    backgroundColor: `${colors.primary[600]}12`,
  },
  chipText: { fontSize: 12, color: colors.text.secondary },
  chipTextActive: { color: colors.primary[600], fontWeight: '700' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  listContent: { padding: spacing.md, gap: spacing.sm, paddingBottom: spacing.xl * 2 },
  empty: { alignItems: 'center', paddingVertical: spacing.xl * 2, gap: spacing.md },
  emptyText: { ...typography.body, color: colors.text.secondary },
  logEntry: {
    backgroundColor: colors.background.default,
    borderRadius: 10,
    padding: spacing.md,
    gap: spacing.xs,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  logHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  actionBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    borderRadius: 12,
    borderWidth: 1.5,
  },
  actionIcon: { fontSize: 12 },
  actionLabel: { fontSize: 12, fontWeight: '600' },
  logDate: { fontSize: 11, color: colors.text.tertiary },
  logMeta: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  logMetaText: { fontSize: 11, color: colors.text.secondary },
  pagination: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: spacing.lg,
    paddingVertical: spacing.lg,
  },
  pageBtn: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: 8,
    backgroundColor: colors.primary[600],
  },
  pageBtnDisabled: { opacity: 0.3 },
  pageBtnText: { ...typography.caption, color: colors.text.inverse, fontWeight: '700' },
  pageInfo: { ...typography.caption, color: colors.text.secondary },
});
