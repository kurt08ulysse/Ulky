import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { colors, spacing, typography } from '@/theme';

type Props = {
  label: string;
  value: string;
  subLabel?: string;
  icon: string;
  accentColor?: string;
};

/**
 * KpiCard — carte indicateur clé du tableau de bord régisseur.
 * Design glassmorphism subtil avec icône, valeur principale et sous-libellé.
 */
export function KpiCard({ label, value, subLabel, icon, accentColor }: Props) {
  const accent = accentColor ?? colors.primary[600];

  return (
    <View style={[styles.card, { borderLeftColor: accent, borderLeftWidth: 3 }]}>
      <View style={styles.header}>
        <Text style={styles.icon}>{icon}</Text>
        <Text style={[styles.label, { color: colors.text.secondary }]}>{label}</Text>
      </View>
      <Text style={[styles.value, { color: accent }]}>{value}</Text>
      {subLabel ? (
        <Text style={styles.subLabel}>{subLabel}</Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.background.default,
    borderRadius: 12,
    padding: spacing.md,
    gap: spacing.xs,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 3,
    flex: 1,
    minWidth: 140,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  icon: {
    fontSize: 18,
  },
  label: {
    ...typography.caption,
    flex: 1,
  },
  value: {
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: -0.5,
  },
  subLabel: {
    ...typography.caption,
    color: colors.text.tertiary,
  },
});
