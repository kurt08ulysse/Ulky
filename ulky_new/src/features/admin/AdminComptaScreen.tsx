import { useState } from 'react';
import { ActivityIndicator, Alert, FlatList, Platform, Pressable, Text, View } from 'react-native';
import { Button, Card, Input } from '@/components';
import { useAdminDashboard, useCreateExpense, useExpenses } from '@/hooks/useAdmin';
import type { Expense } from '@/services/adminService';
import { colors, spacing, typography } from '@/theme';

const CATEGORIES: { key: string; label: string }[] = [
  { key: 'fournitures', label: 'Fournitures' },
  { key: 'salaires', label: 'Salaires' },
  { key: 'maintenance', label: 'Maintenance' },
  { key: 'services', label: 'Services' },
  { key: 'investissement', label: 'Investissement' },
  { key: 'autre', label: 'Autre' },
];

function showAlert(title: string, message: string) {
  if (Platform.OS === 'web') {
    window.alert(`${title}\n${message}`);
  } else {
    Alert.alert(title, message);
  }
}

function KpiRow({ label, value, color }: { label: string; value: string; color: string }) {
  return (
    <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
      <Text style={{ ...typography.body, color: colors.text.secondary }}>{label}</Text>
      <Text style={{ ...typography.h3, color }}>{value}</Text>
    </View>
  );
}

export default function AdminComptaScreen() {
  const { data: kpis } = useAdminDashboard();
  const { data: expenses, isLoading, isError } = useExpenses();
  const createExpense = useCreateExpense();

  const [category, setCategory] = useState<string | null>(null);
  const [label, setLabel] = useState('');
  const [amount, setAmount] = useState('');

  function handleCreate() {
    if (!category) {
      showAlert('Catégorie requise', 'Choisissez une catégorie de dépense.');
      return;
    }
    if (!label.trim()) {
      showAlert('Libellé requis', 'Indiquez l\'objet de la dépense.');
      return;
    }
    const fcfa = Number(amount.replace(',', '.'));
    if (!fcfa || fcfa <= 0) {
      showAlert('Montant invalide', 'Saisissez un montant en FCFA.');
      return;
    }

    createExpense.mutate(
      { category, label: label.trim(), amount: Math.round(fcfa * 100) },
      {
        onSuccess: () => {
          setCategory(null);
          setLabel('');
          setAmount('');
          showAlert('Dépense enregistrée', 'La dépense a été ajoutée à la comptabilité.');
        },
        onError: () => showAlert('Échec', 'La dépense n\'a pas pu être enregistrée.'),
      }
    );
  }

  const header = (
    <View style={{ gap: spacing.lg }}>
      <Card variant="elevated">
        <Text style={{ ...typography.h3, color: colors.text.primary }}>Synthèse</Text>
        <KpiRow label="Recettes (total)" value={kpis?.collected.total.formatted ?? '—'} color={colors.success[600]} />
        <KpiRow label="Dépenses (total)" value={kpis?.expenses.total.formatted ?? '—'} color={colors.error[600]} />
        <View style={{ height: 1, backgroundColor: colors.border.light }} />
        <KpiRow label="Solde net" value={kpis?.net.formatted ?? '—'} color={colors.primary[600]} />
      </Card>

      <Card variant="elevated">
        <Text style={{ ...typography.h3, color: colors.text.primary }}>Nouvelle dépense</Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {CATEGORIES.map((cat) => {
            const active = category === cat.key;
            return (
              <Pressable
                key={cat.key}
                onPress={() => setCategory(cat.key)}
                style={{
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.sm,
                  borderRadius: 20,
                  borderWidth: 1,
                  borderColor: active ? colors.primary[600] : colors.border.default,
                  backgroundColor: active ? `${colors.primary[600]}15` : colors.background.default,
                }}
              >
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
          <Input label="Libellé" placeholder="Ex. Carburant groupe électrogène" value={label} onChangeText={setLabel} />
          <Input
            label="Montant (FCFA)"
            placeholder="Ex. 75000"
            value={amount}
            onChangeText={setAmount}
            keyboardType="numeric"
          />
        </View>
        <Button variant="primary" loading={createExpense.isPending} onPress={handleCreate}>
          Enregistrer la dépense
        </Button>
      </Card>

      <Text style={{ ...typography.h3, color: colors.text.primary }}>Dépenses récentes</Text>
    </View>
  );

  function renderItem({ item }: { item: Expense }) {
    return (
      <Card>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
          <Text style={{ ...typography.caption, color: colors.text.tertiary }}>
            {item.category} · {item.spent_at ?? ''}
          </Text>
          <Text style={{ ...typography.bodyLg, color: colors.error[600], fontWeight: '700' }}>
            {item.amount_formatted}
          </Text>
        </View>
        <Text style={{ ...typography.body, color: colors.text.primary }}>{item.label}</Text>
      </Card>
    );
  }

  return (
    <FlatList
      style={{ flex: 1, backgroundColor: colors.background.subtle }}
      contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}
      data={expenses?.data ?? []}
      keyExtractor={(item) => String(item.id)}
      ListHeaderComponent={header}
      ListEmptyComponent={
        isError ? (
          <Text style={{ ...typography.body, color: colors.text.tertiary }}>
            Accès aux dépenses réservé au régisseur.
          </Text>
        ) : isLoading ? (
          <ActivityIndicator color={colors.primary[600]} />
        ) : (
          <Text style={{ ...typography.body, color: colors.text.tertiary }}>
            Aucune dépense enregistrée.
          </Text>
        )
      }
      renderItem={renderItem}
    />
  );
}
