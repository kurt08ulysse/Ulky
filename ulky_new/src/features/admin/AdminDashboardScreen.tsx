import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  RefreshControl,
  ActivityIndicator,
  StyleSheet,
  Alert,
} from 'react-native';
import { useAdminDashboard, useExportCsv } from '@/hooks/useAdmin';
import { KpiCard } from './components/KpiCard';
import { MiniBarChart } from './components/MiniBarChart';
import { colors, spacing, typography } from '@/theme';

/**
 * Tableau de bord principal du régisseur.
 * Affiche les KPIs encaissements + graphique 7 jours + top taxes.
 */
export default function AdminDashboardScreen() {
  const { data, isLoading, error, refetch, isRefetching } = useAdminDashboard();
  const exportMutation = useExportCsv();
  const [exporting, setExporting] = useState(false);

  const handleExport = async () => {
    setExporting(true);
    try {
      await exportMutation.mutateAsync({});
    } catch {
      Alert.alert('Erreur', "Impossible de générer l'export CSV.");
    } finally {
      setExporting(false);
    }
  };

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary[600]} />
        <Text style={styles.loadingText}>Chargement du tableau de bord…</Text>
      </View>
    );
  }

  if (error || !data) {
    return (
      <View style={styles.centered}>
        <Text style={{ fontSize: 40 }}>⚠️</Text>
        <Text style={styles.errorText}>Impossible de charger les données.</Text>
        <Pressable style={styles.retryBtn} onPress={() => refetch()}>
          <Text style={styles.retryBtnText}>Réessayer</Text>
        </Pressable>
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.root}
      contentContainerStyle={styles.content}
      refreshControl={
        <RefreshControl
          refreshing={isRefetching}
          onRefresh={refetch}
          colors={[colors.primary[600]]}
          tintColor={colors.primary[600]}
        />
      }
    >
      {/* ── Header ── */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>Tableau de bord</Text>
          <Text style={styles.headerSub}>Recettes de la mairie</Text>
        </View>
        <Pressable
          style={[styles.exportBtn, exporting && styles.exportBtnDisabled]}
          onPress={handleExport}
          disabled={exporting}
        >
          {exporting ? (
            <ActivityIndicator size="small" color={colors.text.inverse} />
          ) : (
            <Text style={styles.exportBtnText}>📤 Export CSV</Text>
          )}
        </Pressable>
      </View>

      {/* ── KPI Cards — Encaissements ── */}
      <Text style={styles.sectionTitle}>Encaissements</Text>
      <View style={styles.kpiRow}>
        <KpiCard
          icon="📅"
          label="Aujourd'hui"
          value={data.collected.today.formatted}
          accentColor={colors.primary[600]}
        />
        <KpiCard
          icon="📆"
          label="Ce mois"
          value={data.collected.month.formatted}
          accentColor={colors.success[600]}
        />
      </View>
      <View style={styles.kpiRow}>
        <KpiCard
          icon="🗓️"
          label="Cette semaine"
          value={data.collected.week.formatted}
          accentColor={colors.warning[600]}
        />
        <KpiCard
          icon="💰"
          label="Total cumulé"
          value={data.collected.total.formatted}
          accentColor={colors.neutral[700]}
        />
      </View>

      {/* ── KPI Cards — Statuts des avis ── */}
      <Text style={styles.sectionTitle}>Avis de taxes</Text>
      <View style={styles.kpiRow}>
        <KpiCard
          icon="⏳"
          label="En attente"
          value={`${data.notices_by_status.pending}`}
          accentColor={colors.warning[600]}
        />
        <KpiCard
          icon="✅"
          label="Payés"
          value={`${data.notices_by_status.paid}`}
          accentColor={colors.success[600]}
        />
        <KpiCard
          icon="❌"
          label="Annulés"
          value={`${data.notices_by_status.cancelled}`}
          accentColor={colors.error[600]}
        />
      </View>

      {/* ── Graphique 7 jours ── */}
      <Text style={styles.sectionTitle}>Encaissements — 7 derniers jours</Text>
      <View style={styles.card}>
        {data.chart_data.length > 0 ? (
          <MiniBarChart data={data.chart_data} height={90} />
        ) : (
          <Text style={styles.emptyText}>Aucune donnée disponible.</Text>
        )}
      </View>

      {/* ── Top 5 Taxes ── */}
      <Text style={styles.sectionTitle}>Top taxes ce mois</Text>
      <View style={styles.card}>
        {data.top_taxes.length === 0 ? (
          <Text style={styles.emptyText}>Aucun paiement ce mois.</Text>
        ) : (
          data.top_taxes.map((tax, i) => (
            <View key={i} style={[styles.topTaxRow, i < data.top_taxes.length - 1 && styles.topTaxRowBorder]}>
              <View style={styles.topTaxRank}>
                <Text style={styles.topTaxRankText}>#{i + 1}</Text>
              </View>
              <Text style={styles.topTaxName} numberOfLines={1}>{tax.tax_name}</Text>
              <View style={styles.topTaxRight}>
                <Text style={styles.topTaxAmount}>{tax.total_formatted}</Text>
                <Text style={styles.topTaxCount}>{tax.count} avis</Text>
              </View>
            </View>
          ))
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.background.subtle,
  },
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xl * 2,
    gap: spacing.md,
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.background.subtle,
    padding: spacing.lg,
  },
  loadingText: {
    ...typography.body,
    color: colors.text.secondary,
  },
  errorText: {
    ...typography.body,
    color: colors.text.secondary,
    textAlign: 'center',
  },
  retryBtn: {
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    backgroundColor: colors.primary[600],
    borderRadius: 8,
  },
  retryBtnText: {
    ...typography.button,
    color: colors.text.inverse,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.sm,
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.text.primary,
    letterSpacing: -0.5,
  },
  headerSub: {
    ...typography.caption,
    color: colors.text.tertiary,
  },
  exportBtn: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    backgroundColor: colors.primary[600],
    borderRadius: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    minWidth: 110,
    justifyContent: 'center',
  },
  exportBtnDisabled: {
    opacity: 0.6,
  },
  exportBtnText: {
    ...typography.caption,
    color: colors.text.inverse,
    fontWeight: '700',
  },
  sectionTitle: {
    ...typography.bodyLg,
    fontWeight: '700',
    color: colors.text.primary,
    marginTop: spacing.xs,
  },
  kpiRow: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  card: {
    backgroundColor: colors.background.default,
    borderRadius: 12,
    padding: spacing.md,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 2,
  },
  emptyText: {
    ...typography.caption,
    color: colors.text.tertiary,
    textAlign: 'center',
    paddingVertical: spacing.md,
  },
  topTaxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.sm,
    gap: spacing.md,
  },
  topTaxRowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: colors.border.light,
  },
  topTaxRank: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: colors.neutral[100],
    alignItems: 'center',
    justifyContent: 'center',
  },
  topTaxRankText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.text.secondary,
  },
  topTaxName: {
    ...typography.body,
    color: colors.text.primary,
    flex: 1,
  },
  topTaxRight: {
    alignItems: 'flex-end',
  },
  topTaxAmount: {
    ...typography.caption,
    fontWeight: '700',
    color: colors.primary[600],
  },
  topTaxCount: {
    fontSize: 10,
    color: colors.text.tertiary,
  },
});
