import React, { useState } from 'react';
import { View, Text, Pressable, StyleSheet } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { getMe } from '@/services/auth';
import AdminDashboardScreen from '@/features/admin/AdminDashboardScreen';
import AdminNoticesScreen from '@/features/admin/AdminNoticesScreen';
import AdminAuditScreen from '@/features/admin/AdminAuditScreen';
import AdminComptaScreen from '@/features/admin/AdminComptaScreen';
import { colors, spacing, typography } from '@/theme';

type AdminTab = 'dashboard' | 'notices' | 'compta' | 'audit';

const TABS: { key: AdminTab; label: string; icon: string }[] = [
  { key: 'dashboard', label: 'Tableau de bord', icon: '📊' },
  { key: 'notices',   label: 'Avis de taxes',   icon: '📋' },
  { key: 'compta',    label: 'Comptabilité',    icon: '💰' },
  { key: 'audit',     label: 'Audit',            icon: '🔐' },
];

/**
 * Écran container Admin.
 * Navigation horizontale interne entre Dashboard / Avis / Audit.
 */
export default function AdminScreen() {
  const [activeTab, setActiveTab] = useState<AdminTab>('dashboard');

  const { data: me } = useQuery({ queryKey: ['me'], queryFn: getMe });
  const isAdmin = me?.roles?.some((r) =>
    ['municipal_agent', 'cashier', 'commune_admin', 'super_admin'].includes(r)
  ) ?? false;

  if (!isAdmin) {
    return (
      <View style={styles.denied}>
        <Text style={{ fontSize: 56 }}>🚫</Text>
        <Text style={styles.deniedTitle}>Accès refusé</Text>
        <Text style={styles.deniedSub}>
          Cette section est réservée aux agents de la mairie.
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.root}>
      {/* ── Tab bar interne ── */}
      <View style={styles.tabBar}>
        {TABS.map((tab) => {
          const isActive = activeTab === tab.key;
          return (
            <Pressable
              key={tab.key}
              onPress={() => setActiveTab(tab.key)}
              style={[styles.tab, isActive && styles.tabActive]}
            >
              <Text style={styles.tabIcon}>{tab.icon}</Text>
              <Text style={[styles.tabLabel, isActive && styles.tabLabelActive]}>
                {tab.label}
              </Text>
            </Pressable>
          );
        })}
      </View>

      {/* ── Contenu ── */}
      <View style={styles.content}>
        {activeTab === 'dashboard' && <AdminDashboardScreen />}
        {activeTab === 'notices'   && <AdminNoticesScreen />}
        {activeTab === 'compta'    && <AdminComptaScreen />}
        {activeTab === 'audit'     && <AdminAuditScreen />}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.background.subtle,
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: colors.background.default,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.light,
    paddingHorizontal: spacing.sm,
  },
  tab: {
    flex: 1,
    paddingVertical: spacing.sm,
    alignItems: 'center',
    gap: 2,
    borderBottomWidth: 2.5,
    borderBottomColor: 'transparent',
  },
  tabActive: {
    borderBottomColor: colors.primary[600],
  },
  tabIcon: { fontSize: 16 },
  tabLabel: {
    fontSize: 10,
    fontWeight: '500',
    color: colors.text.tertiary,
    textAlign: 'center',
  },
  tabLabelActive: {
    color: colors.primary[600],
    fontWeight: '700',
  },
  content: {
    flex: 1,
  },
  denied: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.background.subtle,
    padding: spacing.xl,
  },
  deniedTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.text.primary,
  },
  deniedSub: {
    ...typography.body,
    color: colors.text.secondary,
    textAlign: 'center',
  },
});
