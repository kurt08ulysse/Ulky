import { useState } from 'react';
import {
  Alert,
  ActivityIndicator,
  Image,
  Pressable,
  ScrollView,
  Text,
  View,
  TextInput,
  useWindowDimensions,
  Platform
} from 'react-native';
import { useRouter } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useSignIn, useSignUp } from '@clerk/expo';
import { Ionicons } from '@expo/vector-icons';

// Couleurs de la maquette Mairie de Franceville
const colors = {
  primary: '#001e40',       // Bleu très foncé (identitaire)
  secondary: '#003366',     // Bleu institutionnel
  background: '#f9f9f9',    // Fond gris très clair
  cardBg: '#ffffff',        // Fond de carte blanc
  border: '#c3c6d1',        // Bordures gris-bleu
  borderFocus: '#003366',   // Focus
  textPrimary: '#1a1c1c',   // Texte principal
  textMuted: '#43474f',     // Texte secondaire
  textInverse: '#ffffff',   // Texte blanc
  error: '#ba1a1a',         // Rouge erreur
};

// Composant Drapeau du Gabon en CSS natif React Native
const GabonFlag = () => (
  <View
    style={{
      width: 20,
      height: 14,
      borderRadius: 2,
      overflow: 'hidden',
      marginRight: 8,
      borderWidth: 0.5,
      borderColor: 'rgba(0,0,0,0.1)',
    }}
  >
    <View style={{ flex: 1, backgroundColor: '#009E60' }} />
    <View style={{ flex: 1, backgroundColor: '#FCD116' }} />
    <View style={{ flex: 1, backgroundColor: '#3A75C4' }} />
  </View>
);

export default function LoginScreen() {
  const router = useRouter();
  const { width } = useWindowDimensions();
  const isLargeScreen = width >= 768; // Mode ordinateur / tablette

  const [loading, setLoading] = useState(false);
  const [isSignUp, setIsSignUp] = useState(false);
  const [pendingVerification, setPendingVerification] = useState(false);

  // Clerk hooks
  const { signIn, setActive: setSignInActive, isLoaded: isSignInLoaded } = useSignIn();
  const { signUp, setActive: setSignUpActive, isLoaded: isSignUpLoaded } = useSignUp();

  // Form states
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  
  // Sign Up states
  const [signUpFirstName, setSignUpFirstName] = useState('');
  const [signUpLastName, setSignUpLastName] = useState('');
  const [signUpEmail, setSignUpEmail] = useState('');
  const [signUpPassword, setSignUpPassword] = useState('');
  const [verificationCode, setVerificationCode] = useState('');

  // Input Focus states
  const [focusEmail, setFocusEmail] = useState(false);
  const [focusPassword, setFocusPassword] = useState(false);
  const [focusFirstName, setFocusFirstName] = useState(false);
  const [focusLastName, setFocusLastName] = useState(false);
  const [focusSignUpEmail, setFocusSignUpEmail] = useState(false);
  const [focusSignUpPassword, setFocusSignUpPassword] = useState(false);
  const [focusCode, setFocusCode] = useState(false);

  // Connexion
  async function handleSignIn() {
    if (!isSignInLoaded) return;
    if (!email || !password) {
      Alert.alert('Champs requis', 'Veuillez saisir votre email et votre mot de passe.');
      return;
    }
    setLoading(true);
    try {
      const completeSignIn = await signIn.create({
        identifier: email,
        password,
      });
      if (completeSignIn.status === 'complete') {
        await setSignInActive({ session: completeSignIn.createdSessionId });
        router.replace('/(app)');
      }
    } catch (error: any) {
      const message = error.errors?.[0]?.message || 'Identifiants incorrects. Veuillez réessayer.';
      Alert.alert('Échec de la connexion', message);
    } finally {
      setLoading(false);
    }
  }

  // Inscription
  async function handleSignUp() {
    if (!isSignUpLoaded) return;
    if (!signUpFirstName || !signUpLastName || !signUpEmail || !signUpPassword) {
      Alert.alert('Champs requis', 'Veuillez remplir tous les champs obligatoires.');
      return;
    }
    setLoading(true);
    try {
      await signUp.create({
        emailAddress: signUpEmail,
        password: signUpPassword,
        firstName: signUpFirstName,
        lastName: signUpLastName,
      });
      await signUp.prepareEmailAddressVerification({ strategy: 'email_code' });
      setPendingVerification(true);
    } catch (error: any) {
      const message = error.errors?.[0]?.message || "L'inscription a échoué. Veuillez réessayer.";
      Alert.alert("Échec de l'inscription", message);
    } finally {
      setLoading(false);
    }
  }

  // Vérification de l'email
  async function handleVerifyCode() {
    if (!isSignUpLoaded) return;
    if (!verificationCode) {
      Alert.alert('Champs requis', 'Veuillez saisir le code de vérification reçu par e-mail.');
      return;
    }
    setLoading(true);
    try {
      const completeSignUp = await signUp.attemptEmailAddressVerification({
        code: verificationCode,
      });
      if (completeSignUp.status === 'complete') {
        await setSignUpActive({ session: completeSignUp.createdSessionId });
        router.replace('/(app)');
      }
    } catch (error: any) {
      const message = error.errors?.[0]?.message || 'Code incorrect. Veuillez réessayer.';
      Alert.alert('Erreur de validation', message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: colors.background }}
      contentContainerStyle={{ flexGrow: 1 }}
      keyboardShouldPersistTaps="handled"
    >
      <StatusBar style="dark" />

      {/* ─── 1. BARRE DE NAVIGATION (HEADER) ─── */}
      <View
        style={{
          backgroundColor: '#FFFFFF',
          borderBottomWidth: 1,
          borderBottomColor: colors.border,
          paddingHorizontal: isLargeScreen ? 40 : 16,
          paddingVertical: 16,
          zIndex: 50,
        }}
      >
        <View style={{ maxWidth: 1200, alignSelf: 'center', width: '100%', flexDirection: 'row', alignItems: 'center', gap: 10 }}>
          <Ionicons name="business" size={24} color={colors.secondary} />
          <Text style={{ fontSize: 20, fontWeight: '700', color: colors.secondary }}>
            Mairie Centrale de Franceville
          </Text>
        </View>
      </View>

      {/* ─── 2. CONTENU PRINCIPAL (GRID OR COLUMN) ─── */}
      <View
        style={{
          flex: 1,
          maxWidth: 1200,
          alignSelf: 'center',
          width: '100%',
          paddingHorizontal: isLargeScreen ? 40 : 16,
          paddingVertical: isLargeScreen ? 60 : 32,
          flexDirection: isLargeScreen ? 'row' : 'column',
          alignItems: 'center',
          justifyContent: 'center',
          gap: isLargeScreen ? 60 : 40,
        }}
      >
        {/* SECTION GAUCHE (Catchphrase & Photo) */}
        <View style={{ flex: isLargeScreen ? 1.2 : undefined, width: '100%', gap: 24, alignItems: isLargeScreen ? 'flex-start' : 'center' }}>
          <Text
            style={{
              fontSize: isLargeScreen ? 48 : 32,
              lineHeight: isLargeScreen ? 56 : 40,
              fontWeight: '700',
              color: colors.secondary,
              textAlign: isLargeScreen ? 'left' : 'center',
            }}
          >
            Simplifiez vos démarches citoyennes
          </Text>
          <Text
            style={{
              fontSize: 18,
              lineHeight: 28,
              color: colors.textMuted,
              textAlign: isLargeScreen ? 'left' : 'center',
            }}
          >
            Connectez-vous pour accéder à tous vos services publics en ligne, suivre vos demandes et gérer votre profil en toute sécurité.
          </Text>
          
          <Image
            source={{ uri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAu3QW67jDQTfPXpw8XsM99eywuZBUUBmiVtKt5RL15O0TFwlbDmn_DeuHOjrPNZ1_3fqpBlXjVAtW0IBOh5CuEtu8_Ey9G-jAYgV69lMaGEnsTpp4X9S79uurcmbq6zA_V2P0uDOPe3fwiY7AAymuN8PgGoyx6XeUbQrKE3agxx0YbarGJL3Jwd7QEM_i0xWyZBuIt_z3aiPSKgXQtSNU4XfFPHrrCWs-hituZ_qaYP2QvE7kKz4jm3F95p2rzXclke4llXfKFF9FnIQ' }}
            style={{
              width: '100%',
              height: isLargeScreen ? 320 : 200,
              borderRadius: 16,
              resizeMode: 'cover',
            }}
          />
        </View>

        {/* SECTION DROITE (CARTE DE FORMULAIRE) */}
        <View
          style={{
            flex: 1,
            width: '100%',
            maxWidth: 420,
            backgroundColor: colors.cardBg,
            borderWidth: 1,
            borderColor: `${colors.border}80`,
            borderRadius: 16,
            padding: 24,
            shadowColor: '#000000',
            shadowOffset: { width: 0, height: 4 },
            shadowOpacity: 0.08,
            shadowRadius: 16,
            elevation: 4,
          }}
        >
          {loading && (
            <View style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(255,255,255,0.7)', justifyContent: 'center', alignItems: 'center', zIndex: 10, borderRadius: 16 }}>
              <ActivityIndicator size="large" color={colors.secondary} />
            </View>
          )}

          {/* Verification Flow */}
          {pendingVerification ? (
            <View style={{ gap: 20 }}>
              <View style={{ gap: 4 }}>
                <Text style={{ fontSize: 22, fontWeight: '700', color: colors.secondary }}>Vérifiez votre e-mail</Text>
                <Text style={{ fontSize: 14, color: colors.textMuted }}>Saisissez le code de vérification envoyé sur votre adresse e-mail.</Text>
              </View>

              <TextInput
                style={{
                  width: '100%',
                  paddingHorizontal: 16,
                  paddingVertical: 14,
                  backgroundColor: '#FFFFFF',
                  borderWidth: 1,
                  borderColor: focusCode ? colors.borderFocus : colors.border,
                  borderRadius: 8,
                  fontSize: 16,
                  color: colors.textPrimary,
                }}
                placeholder="Code de validation"
                value={verificationCode}
                onChangeText={setVerificationCode}
                onFocus={() => setFocusCode(true)}
                onBlur={() => setFocusCode(false)}
                keyboardType="number-pad"
              />

              <Pressable
                onPress={handleVerifyCode}
                style={({ pressed }) => ({
                  width: '100%',
                  backgroundColor: colors.secondary,
                  borderRadius: 8,
                  paddingVertical: 14,
                  alignItems: 'center',
                  opacity: pressed ? 0.9 : 1,
                })}
              >
                <Text style={{ color: '#FFFFFF', fontWeight: '700', fontSize: 16 }}>
                  Valider mon compte
                </Text>
              </Pressable>
            </View>
          ) : isSignUp ? (
            /* Sign Up Flow */
            <View style={{ gap: 16 }}>
              <View style={{ gap: 4 }}>
                <Text style={{ fontSize: 22, fontWeight: '700', color: colors.secondary }}>Inscription Citoyen</Text>
                <Text style={{ fontSize: 14, color: colors.textMuted }}>Créez un compte pour effectuer vos règlements et démarches en ligne.</Text>
              </View>

              <TextInput
                style={{
                  width: '100%',
                  paddingHorizontal: 16,
                  paddingVertical: 12,
                  backgroundColor: '#FFFFFF',
                  borderWidth: 1,
                  borderColor: focusFirstName ? colors.borderFocus : colors.border,
                  borderRadius: 8,
                  fontSize: 16,
                  color: colors.textPrimary,
                }}
                placeholder="Prénom"
                value={signUpFirstName}
                onChangeText={setSignUpFirstName}
                onFocus={() => setFocusFirstName(true)}
                onBlur={() => setFocusFirstName(false)}
              />

              <TextInput
                style={{
                  width: '100%',
                  paddingHorizontal: 16,
                  paddingVertical: 12,
                  backgroundColor: '#FFFFFF',
                  borderWidth: 1,
                  borderColor: focusLastName ? colors.borderFocus : colors.border,
                  borderRadius: 8,
                  fontSize: 16,
                  color: colors.textPrimary,
                }}
                placeholder="Nom"
                value={signUpLastName}
                onChangeText={setSignUpLastName}
                onFocus={() => setFocusLastName(true)}
                onBlur={() => setFocusLastName(false)}
              />

              <TextInput
                style={{
                  width: '100%',
                  paddingHorizontal: 16,
                  paddingVertical: 12,
                  backgroundColor: '#FFFFFF',
                  borderWidth: 1,
                  borderColor: focusSignUpEmail ? colors.borderFocus : colors.border,
                  borderRadius: 8,
                  fontSize: 16,
                  color: colors.textPrimary,
                }}
                placeholder="Adresse e-mail"
                value={signUpEmail}
                onChangeText={setSignUpEmail}
                onFocus={() => setFocusSignUpEmail(true)}
                onBlur={() => setFocusSignUpEmail(false)}
                autoCapitalize="none"
                keyboardType="email-address"
              />

              <TextInput
                style={{
                  width: '100%',
                  paddingHorizontal: 16,
                  paddingVertical: 12,
                  backgroundColor: '#FFFFFF',
                  borderWidth: 1,
                  borderColor: focusSignUpPassword ? colors.borderFocus : colors.border,
                  borderRadius: 8,
                  fontSize: 16,
                  color: colors.textPrimary,
                }}
                placeholder="Mot de passe"
                value={signUpPassword}
                onChangeText={setSignUpPassword}
                onFocus={() => setFocusSignUpPassword(true)}
                onBlur={() => setFocusSignUpPassword(false)}
                secureTextEntry
              />

              <Pressable
                onPress={handleSignUp}
                style={({ pressed }) => ({
                  width: '100%',
                  backgroundColor: colors.secondary,
                  borderRadius: 8,
                  paddingVertical: 14,
                  alignItems: 'center',
                  marginTop: 8,
                  opacity: pressed ? 0.9 : 1,
                })}
              >
                <Text style={{ color: '#FFFFFF', fontWeight: '700', fontSize: 16 }}>
                  Créer mon compte
                </Text>
              </Pressable>

              <View style={{ borderTopWidth: 1, borderTopColor: `${colors.border}60`, marginTop: 8, paddingTop: 16 }}>
                <Pressable
                  onPress={() => setIsSignUp(false)}
                  style={({ pressed }) => ({
                    width: '100%',
                    borderWidth: 1,
                    borderColor: colors.secondary,
                    borderRadius: 8,
                    paddingVertical: 14,
                    alignItems: 'center',
                    backgroundColor: 'transparent',
                    opacity: pressed ? 0.8 : 1,
                  })}
                >
                  <Text style={{ color: colors.secondary, fontWeight: '700', fontSize: 16 }}>
                    Déjà inscrit ? Se connecter
                  </Text>
                </Pressable>
              </View>
            </View>
          ) : (
            /* Sign In Flow */
            <View style={{ gap: 20 }}>
              <TextInput
                style={{
                  width: '100%',
                  paddingHorizontal: 16,
                  paddingVertical: 14,
                  backgroundColor: '#FFFFFF',
                  borderWidth: 1,
                  borderColor: focusEmail ? colors.borderFocus : colors.border,
                  borderRadius: 8,
                  fontSize: 16,
                  color: colors.textPrimary,
                }}
                placeholder="E-mail ou identifiant"
                value={email}
                onChangeText={setEmail}
                onFocus={() => setFocusEmail(true)}
                onBlur={() => setFocusEmail(false)}
                autoCapitalize="none"
                keyboardType="email-address"
              />

              <TextInput
                style={{
                  width: '100%',
                  paddingHorizontal: 16,
                  paddingVertical: 14,
                  backgroundColor: '#FFFFFF',
                  borderWidth: 1,
                  borderColor: focusPassword ? colors.borderFocus : colors.border,
                  borderRadius: 8,
                  fontSize: 16,
                  color: colors.textPrimary,
                }}
                placeholder="Mot de passe"
                value={password}
                onChangeText={setPassword}
                onFocus={() => setFocusPassword(true)}
                onBlur={() => setFocusPassword(false)}
                secureTextEntry
              />

              <Pressable
                onPress={handleSignIn}
                style={({ pressed }) => ({
                  width: '100%',
                  backgroundColor: colors.secondary,
                  borderRadius: 8,
                  paddingVertical: 14,
                  alignItems: 'center',
                  opacity: pressed ? 0.9 : 1,
                  marginTop: 8,
                })}
              >
                <Text style={{ color: '#FFFFFF', fontWeight: '700', fontSize: 18 }}>
                  Se connecter
                </Text>
              </Pressable>

              <Pressable style={{ alignSelf: 'center', marginTop: 4 }}>
                <Text style={{ color: colors.secondary, fontSize: 14, fontWeight: '500' }}>
                  Mot de passe oublié ?
                </Text>
              </Pressable>

              <View style={{ borderTopWidth: 1, borderTopColor: `${colors.border}60`, marginVertical: 8 }} />

              <Pressable
                onPress={() => setIsSignUp(true)}
                style={({ pressed }) => ({
                  width: '100%',
                  backgroundColor: colors.primary,
                  borderRadius: 8,
                  paddingVertical: 14,
                  alignItems: 'center',
                  opacity: pressed ? 0.9 : 1,
                })}
              >
                <Text style={{ color: '#FFFFFF', fontWeight: '700', fontSize: 16 }}>
                  Créer un nouveau compte
                </Text>
              </Pressable>
            </View>
          )}
        </View>
      </View>

      {/* ─── 3. PIED DE PAGE (FOOTER) ─── */}
      <View
        style={{
          backgroundColor: '#FFFFFF',
          borderTopWidth: 1,
          borderTopColor: colors.border,
          paddingHorizontal: isLargeScreen ? 40 : 16,
          paddingVertical: 24,
          marginTop: 'auto',
        }}
      >
        <View
          style={{
            maxWidth: 1200,
            alignSelf: 'center',
            width: '100%',
            flexDirection: isLargeScreen ? 'row' : 'column',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: 16,
          }}
        >
          {/* Logo Gabon & Copyright */}
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <GabonFlag />
            <Text style={{ fontSize: 12, color: colors.textMuted }}>
              © 2026 République Gabonaise - Mairie de Franceville
            </Text>
          </View>

          {/* Liens légaux */}
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'center', gap: 16 }}>
            <Pressable>
              <Text style={{ fontSize: 12, color: colors.textMuted }}>Mentions Légales</Text>
            </Pressable>
            <Pressable>
              <Text style={{ fontSize: 12, color: colors.textMuted }}>Données Personnelles</Text>
            </Pressable>
            <Pressable>
              <Text style={{ fontSize: 12, color: colors.textMuted }}>Accessibilité</Text>
            </Pressable>
            <Pressable>
              <Text style={{ fontSize: 12, color: colors.textMuted }}>Contact</Text>
            </Pressable>
          </View>
        </View>
      </View>
    </ScrollView>
  );
}
