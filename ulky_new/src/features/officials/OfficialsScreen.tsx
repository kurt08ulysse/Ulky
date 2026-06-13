import { ActivityIndicator, Image, ScrollView, Text, View } from 'react-native';
import { Card } from '@/components';
import { useOfficials } from '@/hooks/useOfficials';
import { colors, spacing, typography } from '@/theme';

export default function OfficialsScreen() {
  const { data: officials, isLoading } = useOfficials();

  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: colors.background.subtle }}
      contentContainerStyle={{ padding: spacing.lg, gap: spacing.lg }}
    >
      <View style={{ gap: spacing.xs }}>
        <Text style={{ ...typography.h2, color: colors.text.primary }}>Our elected officials</Text>
        <Text style={{ ...typography.body, color: colors.text.secondary }}>
          Meet the mayor and the municipal council.
        </Text>
      </View>

      {isLoading ? (
        <ActivityIndicator color={colors.primary[600]} />
      ) : !officials || officials.length === 0 ? (
        <Text style={{ ...typography.body, color: colors.text.tertiary }}>
          No information available yet.
        </Text>
      ) : (
        officials.map((official) => (
          <Card key={official.id} variant="elevated">
            <View style={{ flexDirection: 'row', gap: spacing.md, alignItems: 'center' }}>
              {official.photo_url ? (
                <Image
                  source={{ uri: official.photo_url }}
                  style={{ width: 72, height: 72, borderRadius: 36, backgroundColor: colors.neutral[200] }}
                />
              ) : (
                <View
                  style={{
                    width: 72,
                    height: 72,
                    borderRadius: 36,
                    backgroundColor: colors.neutral[200],
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  <Text style={{ fontSize: 28 }}>🏛️</Text>
                </View>
              )}
              <View style={{ flex: 1, gap: spacing.xs }}>
                <Text style={{ ...typography.h3, color: colors.text.primary }}>{official.name}</Text>
                <Text style={{ ...typography.body, color: colors.primary[600], fontWeight: '600' }}>
                  {official.title}
                </Text>
              </View>
            </View>
            {official.description ? (
              <Text style={{ ...typography.body, color: colors.text.secondary }}>
                {official.description}
              </Text>
            ) : null}
          </Card>
        ))
      )}
    </ScrollView>
  );
}
