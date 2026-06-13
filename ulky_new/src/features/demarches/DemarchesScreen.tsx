import { useState } from 'react';
import { ActivityIndicator, Alert, Platform, Pressable, ScrollView, Text, View } from 'react-native';
import { Badge, Button, Card, Input } from '@/components';
import { useCreateDemarche, useDemarches, usePayDemarche } from '@/hooks/useDemarches';
import type { Demarche, DemarcheStatus } from '@/services/demarcheService';
import { colors, spacing, typography } from '@/theme';

const TYPES: { key: string; label: string }[] = [
  { key: 'acte_naissance', label: 'Acte de naissance' },
  { key: 'certificat_residence', label: 'Certificat de résidence' },
  { key: 'certificat_vie', label: 'Certificat de vie' },
  { key: 'legalisation', label: 'Légalisation' },
  { key: 'autre', label: 'Autre' },
];

const STATUS_MAP: Record<
  DemarcheStatus,
  { label: string; variant: 'primary' | 'success' | 'warning' | 'error' }
> = {
  submitted: { label: 'Soumise', variant: 'primary' },
  in_review: { label: 'En instruction', variant: 'warning' },
  additional_info: { label: 'Infos requises', variant: 'warning' },
  approved: { label: 'Approuvée', variant: 'success' },
  rejected: { label: 'Rejetée', variant: 'error' },
  closed: { label: 'Clôturée', variant: 'success' },
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

export default function DemarchesScreen() {
  const { data: demarches, isLoading } = useDemarches();
  const createDemarche = useCreateDemarche();
  const payDemarche = usePayDemarche();

  const [type, setType] = useState<string | null>(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');

  // Paiement inline d'une démarche
  const [payingId, setPayingId] = useState<number | null>(null);
  const [operator, setOperator] = useState<string | null>(null);
  const [phone, setPhone] = useState('');

  function handleCreate() {
    if (!type) {
      showAlert('Type requis', 'Veuillez choisir le type de démarche.');
      return;
    }
    if (!title.trim()) {
      showAlert('Objet requis', 'Veuillez préciser l\'objet de votre démarche.');
      return;
    }

    createDemarche.mutate(
      { type, title: title.trim(), description: description.trim() || undefined },
      {
        onSuccess: () => {
          setType(null);
          setTitle('');
          setDescription('');
          showAlert('Démarche déposée', 'Votre demande a été transmise à la mairie.');
        },
        onError: () => showAlert('Échec', 'La démarche n\'a pas pu être déposée. Réessayez.'),
      }
    );
  }

  function handlePay(demarche: Demarche) {
    if (!operator) {
      showAlert('Opérateur requis', 'Choisissez Airtel Money ou Moov Money.');
      return;
    }
    if (!phone.trim()) {
      showAlert('Téléphone requis', 'Saisissez le numéro Mobile Money.');
      return;
    }

    payDemarche.mutate(
      { id: demarche.id, operator, phone: phone.trim() },
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
        <Text style={{ ...typography.h2, color: colors.text.primary }}>Mes démarches</Text>
        <Text style={{ ...typography.body, color: colors.text.secondary }}>
          Déposez vos demandes administratives et suivez leur traitement.
        </Text>
      </View>

      {/* Formulaire de dépôt */}
      <Card variant="elevated">
        <Text style={{ ...typography.h3, color: colors.text.primary }}>Nouvelle démarche</Text>

        <Text style={{ ...typography.caption, color: colors.text.secondary, fontWeight: '600' }}>
          Type de démarche
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {TYPES.map((t) => {
            const active = type === t.key;
            return (
              <Pressable
                key={t.key}
                onPress={() => setType(t.key)}
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
                  {t.label}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <View style={{ marginTop: spacing.md }}>
          <Input
            label="Objet"
            placeholder="Ex. Copie intégrale d'acte de naissance"
            value={title}
            onChangeText={setTitle}
          />
          <Input
            label="Précisions (facultatif)"
            placeholder="Informations utiles à la mairie"
            value={description}
            onChangeText={setDescription}
            multiline
          />
        </View>

        <Button variant="primary" loading={createDemarche.isPending} onPress={handleCreate}>
          Déposer la démarche
        </Button>
      </Card>

      {/* Liste de mes démarches */}
      <View style={{ gap: spacing.md }}>
        <Text style={{ ...typography.h3, color: colors.text.primary }}>Suivi</Text>

        {isLoading ? (
          <ActivityIndicator color={colors.primary[600]} />
        ) : !demarches || demarches.length === 0 ? (
          <Text style={{ ...typography.body, color: colors.text.tertiary }}>
            Vous n'avez encore déposé aucune démarche.
          </Text>
        ) : (
          demarches.map((demarche) => {
            const status = STATUS_MAP[demarche.status];
            const needsPayment = demarche.fee_amount > 0 && demarche.payment_status !== 'paid';
            const isPaying = payingId === demarche.id;
            return (
              <Card key={demarche.id}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                  <Text style={{ ...typography.caption, color: colors.text.tertiary }}>
                    {demarche.reference}
                  </Text>
                  <Badge label={status.label} variant={status.variant} />
                </View>
                <Text style={{ ...typography.bodyLg, color: colors.text.primary, fontWeight: '600' }}>
                  {demarche.title}
                </Text>

                {demarche.fee_amount > 0 ? (
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Text style={{ ...typography.body, color: colors.text.secondary }}>
                      Frais : {demarche.fee_amount_formatted}
                    </Text>
                    {demarche.payment_status === 'paid' ? (
                      <Badge label="Payé" variant="success" />
                    ) : null}
                  </View>
                ) : null}

                {needsPayment && !isPaying ? (
                  <Button variant="primary" size="sm" onPress={() => setPayingId(demarche.id)}>
                    Payer les frais
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
                          loading={payDemarche.isPending}
                          onPress={() => handlePay(demarche)}
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
