import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { colors } from '@/theme';

type BarDataPoint = {
  date: string;
  label: string;
  total: number;
};

type Props = {
  data: BarDataPoint[];
  height?: number;
};

/**
 * MiniBarChart — graphique en barres minimaliste (natif, pas de SVG).
 * Affiche les encaissements des 7 derniers jours.
 */
export function MiniBarChart({ data, height = 80 }: Props) {
  const max = Math.max(...data.map((d) => d.total), 1);

  return (
    <View style={styles.container}>
      <View style={[styles.barsRow, { height }]}>
        {data.map((point, i) => {
          const ratio = point.total / max;
          const barHeight = Math.max(ratio * height, 3);
          const isToday = i === data.length - 1;

          return (
            <View key={point.date} style={styles.barWrapper}>
              {/* Valeur au-dessus de la barre */}
              {point.total > 0 && (
                <Text style={styles.barValue}>
                  {point.total >= 100_000
                    ? `${Math.round(point.total / 100_000)}k`
                    : `${Math.round(point.total / 100)}`}
                </Text>
              )}
              {/* La barre */}
              <View style={styles.barBg}>
                <View
                  style={[
                    styles.bar,
                    {
                      height: barHeight,
                      backgroundColor: isToday
                        ? colors.primary[600]
                        : `${colors.primary[600]}60`,
                    },
                  ]}
                />
              </View>
              {/* Libellé du jour */}
              <Text style={[styles.dayLabel, isToday && { color: colors.primary[600], fontWeight: '700' }]}>
                {point.label}
              </Text>
            </View>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: '100%',
  },
  barsRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 4,
  },
  barWrapper: {
    flex: 1,
    alignItems: 'center',
    gap: 2,
  },
  barValue: {
    fontSize: 9,
    color: colors.text.secondary,
    fontWeight: '600',
  },
  barBg: {
    width: '100%',
    justifyContent: 'flex-end',
  },
  bar: {
    width: '100%',
    borderRadius: 4,
    minHeight: 3,
  },
  dayLabel: {
    fontSize: 9,
    color: colors.text.tertiary,
    marginTop: 2,
  },
});
