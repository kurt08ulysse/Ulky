import { useState } from 'react';
import { ActivityIndicator, Alert, FlatList, Platform, Pressable, Text, View } from 'react-native';
import { Badge, Button, Card, Input } from '@/components';
import { useCreateReport, useReports } from '@/hooks/useReports';
import type { CitizenReport, ReportCategory, ReportStatus } from '@/services/reportService';
import { colors, spacing, typography } from '@/theme';

const CATEGORIES: { key: ReportCategory; label: string; icon: string }[] = [
  { key: 'voirie', label: 'Voirie', icon: '🛣️' },
  { key: 'eclairage', label: 'Éclairage', icon: '💡' },
  { key: 'dechets', label: 'Déchets', icon: '🗑️' },
  { key: 'eau', label: 'Eau', icon: '💧' },
  { key: 'securite', label: 'Sécurité', icon: '🛡️' },
  { key: 'autre', label: 'Autre', icon: '📌' },
];

const STATUS_MAP: Record<
  ReportStatus,
  { label: string; variant: 'primary' | 'success' | 'warning' | 'error' }
> = {
  new: { label: 'Nouveau', variant: 'primary' },
  acknowledged: { label: 'Pris en compte', variant: 'primary' },
  in_progress: { label: 'En cours', variant: 'warning' },
  resolved: { label: 'Résolu', variant: 'success' },
  rejected: { label: 'Rejeté', variant: 'error' },
  closed: { label: 'Clôturé', variant: 'success' },
};

function showAlert(title: string, message: string) {
  if (Platform.OS === 'web') {
    window.alert(`${title}\n${message}`);
  } else {
    Alert.alert(title, message);
  }
}

export default function ReportsScreen() {
  const { data: reports, isLoading } = useReports();
  const createReport = useCreateReport();

  const [category, setCategory] = useState<ReportCategory | null>(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [address, setAddress] = useState('');

  function handleSubmit() {
    if (!category) {
      showAlert('Catégorie requise', 'Veuillez choisir une catégorie de problème.');
      return;
    }
    if (!title.trim()) {
      showAlert('Titre requis', 'Veuillez décrire le problème en quelques mots.');
      return;
    }

    createReport.mutate(
      {
        category,
        title: title.trim(),
        description: description.trim() || undefined,
        address: address.trim() || undefined,
      },
      {
        onSuccess: () => {
          setCategory(null);
          setTitle('');
          setDescription('');
          setAddress('');
          showAlert('Merci !', 'Votre signalement a bien été transmis à la mairie.');
        },
        onError: () => {
          showAlert('Échec', 'Le signalement n\'a pas pu être envoyé. Réessayez.');
        },
      }
    );
  }

  const header = (
    <View style={{ gap: spacing.lg }}>
      <View style={{ gap: spacing.xs }}>
        <Text style={{ ...typography.h2, color: colors.text.primary }}>Signaler un problème</Text>
        <Text style={{ ...typography.body, color: colors.text.secondary }}>
          Voirie, éclairage, déchets… Aidez la mairie à intervenir.
        </Text>
      </View>

      <Card variant="elevated">
        <Text style={{ ...typography.h3, color: colors.text.primary }}>Nouveau signalement</Text>
        <Text style={{ ...typography.caption, color: colors.text.secondary, fontWeight: '600' }}>
          Catégorie
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {CATEGORIES.map((cat) => {
            const active = category === cat.key;
            return (
              <Pressable
                key={cat.key}
                onPress={() => setCategory(cat.key)}
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  gap: spacing.xs,
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.sm,
                  borderRadius: 20,
                  borderWidth: 1,
                  borderColor: active ? colors.primary[600] : colors.border.default,
                  backgroundColor: active ? `${colors.primary[600]}15` : colors.background.default,
                }}
              >
                <Text>{cat.icon}</Text>
                <Text
                  style={{
                    ...typography.caption,
                    color: active ? colors.primary[600] : colors.text.secondary,
                    fontWeight: active ? '700' : '500',
                  }}
                >
                  {cat.label}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <View style={{ marginTop: spacing.md }}>
          <Input
            label="Titre"
            placeholder="Ex. Lampadaire éteint depuis 3 jours"
            value={title}
            onChangeText={setTitle}
          />
          <Input
            label="Description (facultatif)"
            placeholder="Détails utiles pour la mairie"
            value={description}
            onChangeText={setDescription}
            multiline
          />
          <Input
            label="Lieu / adresse (facultatif)"
            placeholder="Ex. Quartier Louis, rue de la Paix"
            value={address}
            onChangeText={setAddress}
          />
        </View>

        <Button variant="primary" loading={createReport.isPending} onPress={handleSubmit}>
          Envoyer le signalement
        </Button>
      </Card>

      <Text style={{ ...typography.h3, color: colors.text.primary }}>Mes signalements</Text>
    </View>
  );

  function renderItem({ item }: { item: CitizenReport }) {
    const status = STATUS_MAP[item.status];
    const cat = CATEGORIES.find((c) => c.key === item.category);
    return (
      <Card>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
          <Text style={{ ...typography.caption, color: colors.text.tertiary }}>
            {cat ? `${cat.icon} ${cat.label}` : item.category} · {item.reference}
          </Text>
          <Badge label={status.label} variant={status.variant} />
        </View>
        <Text style={{ ...typography.bodyLg, color: colors.text.primary, fontWeight: '600' }}>
          {item.title}
        </Text>
        {item.address ? (
          <Text style={{ ...typography.caption, color: colors.text.secondary }}>📍 {item.address}</Text>
        ) : null}
      </Card>
    );
  }

  return (
    <FlatList
      style={{ flex: 1, backgroundColor: colors.background.subtle }}
      contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}
      data={reports ?? []}
      keyExtractor={(item) => String(item.id)}
      ListHeaderComponent={header}
      ListEmptyComponent={
        isLoading ? (
          <ActivityIndicator color={colors.primary[600]} />
        ) : (
          <Text style={{ ...typography.body, color: colors.text.tertiary }}>
            Vous n'avez encore signalé aucun problème.
          </Text>
        )
      }
      renderItem={renderItem}
    />
  );
}
