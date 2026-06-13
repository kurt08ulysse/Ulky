import { useState } from 'react';
import { ActivityIndicator, Alert, Platform, Pressable, ScrollView, Text, View } from 'react-native';
import { Badge, Button, Card, Input } from '@/components';
import { useMyRents, useMyStalls, usePayRent } from '@/hooks/useMarket';
import type { RentStatus, StallRent } from '@/services/marketService';
import { colors, spacing, typography } from '@/theme';

const RENT_STATUS: Record<
  RentStatus,
  { label: string; variant: 'primary' | 'success' | 'warning' | 'error' }
> = {
  pending: { label: 'À payer', variant: 'warning' },
  late: { label: 'En retard', variant: 'error' },
  paid: { label: 'Payé', variant: 'success' },
  cancelled: { label: 'Annulé', variant: 'error' },
};

const OPERATORS: { key: string; label: string }[] = [
  { key: 'airtel_money', label: 'Airtel Money' },
  { key: 'moov_money', label: 'Moov Money' },
];

function showAlert(title: string, message: string) {
  if (Platform.OS === 'web') {
    window.alert(`${title}\n${message}`);
  } else {
    Alert.alert(title, message);
  }
}

export default function CommercantScreen() {
  const { data: stalls, isLoading: loadingStalls } = useMyStalls();
  const { data: rents, isLoading: loadingRents } = useMyRents();
  const payRent = usePayRent();

  const [payingId, setPayingId] = useState<number | null>(null);
  const [operator, setOperator] = useState<string | null>(null);
  const [phone, setPhone] = useState('');

  function handlePay(rent: StallRent) {
    if (!operator) {
      showAlert('Opérateur requis', 'Choisissez Airtel Money ou Moov Money.');
      return;
    }
    if (!phone.trim()) {
      showAlert('Téléphone requis', 'Saisissez le numéro Mobile Money.');
      return;
    }

    payRent.mutate(
      { id: rent.id, operator, phone: phone.trim() },
      {
        onSuccess: () => {
          setPayingId(null);
          setOperator(null);
          setPhone('');
          showAlert('Paiement initié', 'Validez le push USSD sur votre téléphone.');
        },
        onError: () => showAlert('Échec', 'Le paiement n\'a pas pu être initié. Réessayez.'),
      }
    );
  }

  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: colors.background.subtle }}
      contentContainerStyle={{ padding: spacing.lg, gap: spacing.lg }}
    >
      <View style={{ gap: spacing.xs }}>
        <Text style={{ ...typography.h2, color: colors.text.primary }}>Espace commerçant</Text>
        <Text style={{ ...typography.body, color: colors.text.secondary }}>
          Vos emplacements de marché et vos loyers mensuels.
        </Text>
      </View>

      {/* Mes emplacements */}
      <View style={{ gap: spacing.md }}>
        <Text style={{ ...typography.h3, color: colors.text.primary }}>Mes emplacements</Text>
        {loadingStalls ? (
          <ActivityIndicator color={colors.primary[600]} />
        ) : !stalls || stalls.length === 0 ? (
          <Text style={{ ...typography.body, color: colors.text.tertiary }}>
            Aucun emplacement ne vous est attribué.
          </Text>
        ) : (
          stalls.map((stall) => (
            <Card key={stall.id}>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                <Text style={{ ...typography.bodyLg, color: colors.text.primary, fontWeight: '600' }}>
                  Emplacement {stall.stall_number}
                </Text>
                <Badge label={stall.stall_type} variant="primary" />
              </View>
              <Text style={{ ...typography.body, color: colors.text.secondary }}>
                {stall.market?.name ?? 'Marché'} · Loyer {stall.rent_amount_formatted}/mois
              </Text>
            </Card>
          ))
        )}
      </View>

      {/* Mes loyers */}
      <View style={{ gap: spacing.md }}>
        <Text style={{ ...typography.h3, color: colors.text.primary }}>Mes loyers</Text>
        {loadingRents ? (
          <ActivityIndicator color={colors.primary[600]} />
        ) : !rents || rents.length === 0 ? (
          <Text style={{ ...typography.body, color: colors.text.tertiary }}>
            Aucun loyer en attente.
          </Text>
        ) : (
          rents.map((rent) => {
            const status = RENT_STATUS[rent.status];
            const needsPayment = rent.status === 'pending' || rent.status === 'late';
            const isPaying = payingId === rent.id;
            return (
              <Card key={rent.id}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                  <Text style={{ ...typography.caption, color: colors.text.tertiary }}>
                    Période {rent.period}
                    {rent.stall ? ` · Empl. ${rent.stall.stall_number}` : ''}
                  </Text>
                  <Badge label={status.label} variant={status.variant} />
                </View>
                <Text style={{ ...typography.bodyLg, color: colors.text.primary, fontWeight: '600' }}>
                  {rent.amount_formatted}
                </Text>

                {needsPayment && !isPaying ? (
                  <Button variant="primary" size="sm" onPress={() => setPayingId(rent.id)}>
                    Payer le loyer
                  </Button>
                ) : null}

                {needsPayment && isPaying ? (
                  <View style={{ gap: spacing.sm }}>
                    <View style={{ flexDirection: 'row', gap: spacing.sm }}>
                      {OPERATORS.map((op) => {
                        const active = operator === op.key;
                        return (
                          <Pressable
                            key={op.key}
                            onPress={() => setOperator(op.key)}
                            style={{
                              flex: 1,
                              alignItems: 'center',
                              paddingVertical: spacing.sm,
                              borderRadius: 8,
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
                              {op.label}
                            </Text>
                          </Pressable>
                        );
                      })}
                    </View>
                    <Input
                      placeholder="Numéro Mobile Money"
                      value={phone}
                      onChangeText={setPhone}
                      keyboardType="phone-pad"
                    />
                    <View style={{ flexDirection: 'row', gap: spacing.sm }}>
                      <View style={{ flex: 1 }}>
                        <Button variant="secondary" size="sm" onPress={() => setPayingId(null)}>
                          Annuler
                        </Button>
                      </View>
                      <View style={{ flex: 1 }}>
                        <Button
                          variant="primary"
                          size="sm"
                          loading={payRent.isPending}
                          onPress={() => handlePay(rent)}
                        >
                          Confirmer
                        </Button>
                      </View>
                    </View>
                  </View>
                ) : null}
              </Card>
            );
          })
        )}
      </View>
    </ScrollView>
  );
}
