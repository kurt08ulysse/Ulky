Compagnon d'AGENTS.md. AGENTS.md dit comment coder ; ce fichier dit quoi construire et dans quel ordre. Règle de construction : chaque phase a un critère de sortie démontrable. Une phase sans critère de sortie n'est pas une phase, c'est une liste de courses.



Vision

ULKY est une plateforme web et mobile de digitalisation des services municipaux au Gabon. Elle permet aux citoyens, commerçants et administrations municipales de gérer leurs démarches de manière simple, sécurisée et transparente.

Non-objectifs jusqu'à livraison du MVP : démarches administratives, signalements citoyens, paiement par carte bancaire. Ils restent au plan (phases 5–6) mais aucune ligne de code avant la fin de la phase 4.

Décisions à fermer avant la phase 1 (bloquantes)

Ces décisions conditionnent le modèle de données ou le calendrier. Les ouvrir plus tard coûte une réécriture.


Mono ou multi-communes (multi-tenancy) — la plus structurante. Si plusieurs communes sont visées, commune_id traverse tout le schéma, le scoping des permissions et les rapports, dès la première migration. Se décide maintenant, pas en phase 7.
Identité primaire des citoyens : téléphone, pas email (à confirmer) — la pénétration email au Gabon rend la vérification par email excluante. Hypothèse de travail : inscription par numéro de téléphone + OTP SMS, email optionnel. L'OTP d'authentification est désormais porté par Clerk ; vérifier avant la phase 1 la délivrabilité SMS de Clerk vers le +241 et son coût (SMS + tarification au MAU à l'échelle d'une commune). Un fournisseur SMS local ne reste nécessaire que pour les notifications (phase 5).
Rail de paiement — Airtel Money et Moov Money sont les deux opérateurs. Choix : agrégateur (SingPay, eBilling, PVit…) vs intégration directe opérateurs. Les démarches contractuelles (KYB, conventions) sont le délai externe le plus long du projet : les lancer dès maintenant, en parallèle de la phase 0.
Reprise de l'existant — les registres actuels de contribuables et de taxes (papier, Excel) sont-ils importés ou repart-on de zéro ? Si import : c'est un chantier à part entière, à chiffrer en phase 2.
Souveraineté des données — des données fiscales de citoyens gabonais sur Neon (infrastructure UE/US) posent une question juridique et politique. Le choix de Clerk élargit cette question : l'identité des contribuables (téléphones, noms, sessions) est hébergée chez un SaaS américain, et contrairement à Neon → PostgreSQL, Clerk n'a aucune trajectoire d'auto-hébergement — le miroir local par webhooks (AGENTS.md) garantit l'exportabilité des données, pas la réversibilité du service. Vérifier la conformité avec la réglementation gabonaise sur les données personnelles (loi n°001/2011 et textes en vigueur, CNPDCP) avec un juriste, pour la base et pour Clerk. Cette analyse peut imposer d'avancer le PostgreSQL auto-hébergé de « plus tard » à « dès la mise en production ».


Exceptions prévisibles à la liste blanche

Le plan crée des besoins que la liste blanche d'AGENTS.md ne couvre pas. Plutôt que de les découvrir en cours de route, ils passent par la procédure d'exception maintenant :


Backend : génération PDF (barryvdh/laravel-dompdf ou spatie/laravel-pdf — trancher), export Excel (maatwebsite/excel), stockage fichiers (league/flysystem-aws-s3-v3 vers un MinIO auto-hébergé ou équivalent), SDK ou client HTTP de l'agrégateur de paiement, fournisseur SMS pour les notifications (phase 5 — l'OTP d'auth est porté par Clerk).
Frontend : expo-image-picker et expo-location (phase 6), expo-document-picker et expo-file-system (phase 5).
Les files d'attente utilisent les queues Laravel natives sur Redis déjà autorisé : aucune exception nécessaire.



Phase 0 — Socle et déploiement continu (LIVRÉE)

Fusionne l'ancienne phase 1 et l'ancienne phase 8 : le déploiement n'est pas une phase finale, c'est une pratique continue. Découvrir la production à la fin du projet est le moyen le plus sûr de la découvrir cassée.


Squelette frontend (Expo, TS strict, Router, NativeWind, React Query, Zustand, Axios, ClerkProvider) et backend (Laravel 12, guard Clerk, PostgreSQL) conformes à AGENTS.md.
CI : la boucle de vérification d'AGENTS.md (tsc, eslint, jest, pint --test, php artisan test) exécutée sur chaque PR, bloquante.
Docker, environnements Dev / Staging / Production. Staging déployé automatiquement dès la première semaine ; production vide mais en ligne, HTTPS, sauvegardée.
Squelette de l'API /api/v1 avec l'enveloppe définie dans AGENTS.md.


Critère de sortie : un écran de l'app affiche une donnée servie par l'API en staging, déployée par le pipeline ; la boucle CI est verte et bloquante ; une sauvegarde de la base a été restaurée avec succès (une sauvegarde jamais restaurée n'existe pas).

Phase 1 — Identité et rôles (LIVRÉE)


Inscription et connexion portées par Clerk via le canal décidé (téléphone + OTP probable) ; guard Laravel adossé au SDK Clerk ; aucun endpoint d'auth côté API (cf. AGENTS.md).
Miroir local des utilisateurs : webhooks Clerk vérifiés (Svix) et idempotents, filet find-or-create au premier JWT valide. Le profil métier (type de contribuable, rattachements communaux) vit en local ; l'identité (téléphone, nom) vit chez Clerk et se synchronise par webhook.
Séparer deux concepts que l'ancien plan confondait : le rôle (citoyen, agent municipal, régisseur, administrateur de commune — et super-administrateur si multi-tenant) et le type de contribuable (particulier, commerçant). Un commerçant n'est pas un rôle d'autorisation, c'est un attribut métier d'un contribuable. spatie/laravel-permission porte les rôles ; le type de contribuable vit dans le modèle.
Le rôle régisseur (valide et encaisse les recettes) manquait : c'est lui qui rend possible la séparation des tâches de la phase 3.


Critère de sortie : parcours inscription → connexion → action protégée complet sur device réel ; tests Feature auth et permissions verts sans aucun appel réseau à Clerk ; la révocation d'une session dans le dashboard Clerk coupe l'accès à l'API (vérifié) ; chaque rôle ne voit que ce que sa Policy autorise (testé) ; le rejeu d'un webhook utilisateur ne crée aucun doublon.

Phase 2 — Référentiel des taxes et des contribuables (EN COURS — Backend LIVRÉ)


Création et paramétrage des taxes (montants base et timbre en centimes), seeder de 39 actes réels, rattachement des contribuables (avis de taxes/factures) et sécurisation par Policies d'autorisation.
Backend : LIVRÉ le 2026-06-12 (Migrations, Modèles, Seeders, Policies, Ressources, Requêtes, Routes, et Tests 100% OK).
Frontend : EN COURS (Intégration du service API, des hooks React Query et de l'écran TaxesScreen).


Critère de sortie : un agent crée une taxe et l'assigne ; le contribuable la voit dans l'app avec son échéance, le coût de base, le coût du timbre et le statut.

Phase 3 — Paiement et quittance (cœur du MVP, risque maximal)

C'est de l'argent public : les exigences sont celles d'une régie de recettes, pas d'un e-commerce.


Paiement Mobile Money via le rail décidé : webhooks idempotents (un callback rejoué ne crée jamais deux paiements), machine à états explicite (initié / en attente / confirmé / échoué / remboursé), gestion des timeouts et des paiements orphelins.
Réconciliation : rapprochement quotidien entre le relevé de l'agrégateur et les écritures locales ; tout écart est bloquant et tracé.
Quittance : document légal. Numérotation séquentielle sans trous par commune et par exercice — table de compteurs verrouillée en transaction, pas une SEQUENCE PostgreSQL (les séquences laissent des trous). PDF généré, QR code de vérification publique de l'authenticité.
Journal d'audit append-only sur toute opération financière (qui, quoi, quand, ancien état, nouvel état). Aucune suppression, aucune mise à jour destructive.
Séparation des tâches : celui qui paramètre une taxe n'est pas celui qui encaisse ; celui qui encaisse n'est pas celui qui annule. Porté par les Policies, testé.
Paiement par carte bancaire : reporté post-MVP sauf exigence contractuelle.


Critère de sortie : un paiement réel de bout en bout en staging (sandbox de l'agrégateur) produit une quittance numérotée vérifiable par QR ; une journée simulée se réconcilie sans écart ; le rejeu d'un webhook ne duplique rien (testé) ; le journal d'audit reconstitue l'historique complet d'un paiement.

Phase 4 — Tableau de bord régisseur et administrateur

Version minimale et exacte, pas exhaustive : recettes par taxe et par période, liste des paiements recherchable, export PDF et Excel de base.

Critère de sortie : les totaux affichés égalent la somme des paiements confirmés — vérifié par un test, pas à l'œil.


═══ MVP livré ici. Tout ce qui suit est secondaire jusqu'à sa livraison complète. ═══

Le MVP n'est pas une liste de modules, c'est un scénario : « Un contribuable identifié par son téléphone paie une taxe municipale par Airtel Money ou Moov Money et reçoit une quittance numérotée, vérifiable par QR code ; le régisseur voit la recette exacte le jour même. » Phases 0 à 4.


Phase 5 — Démarches administratives

Dépôt de demande, téléversement de pièces (stockage décidé en exception), workflow d'états du traitement, notifications push (expo-notifications) et SMS.

Critère de sortie : une demande déposée avec pièce jointe traverse tous les états jusqu'à clôture, avec notification à chaque transition.

Phase 6 — Signalements citoyens

Signalement d'incident avec géolocalisation et photos, file de traitement côté agents, suivi citoyen.

Critère de sortie : un signalement créé sur le terrain (photo + position) apparaît chez l'agent et son traitement est visible par le citoyen.

Phase 7 — Durcissement et exploitation


Migration vers l'auto-hébergé si la décision 5 ne l'a pas déjà imposée (Ubuntu 24.04, Docker, Nginx, Let's Encrypt, PostgreSQL).
Supervision et alertes, test de restauration des sauvegardes planifié et récurrent, runbook d'exploitation (procédures incident, rotation des secrets, montée de version).


Critère de sortie : un exercice de restauration complète à partir des seules sauvegardes réussit en environnement vierge, chronométré.

Critères de qualité

Remplacés par des portes vérifiables — « être sécurisée, documentée, testée » ne se vérifie pas, ceci si :


Une fonctionnalité est terminée quand elle est déployée en staging et que le critère de sortie est démontré — pas quand le code est écrit.
La boucle de vérification d'AGENTS.md est verte en CI sur chaque PR.
Tout endpoint touchant auth, permissions ou écriture de données a son test Feature (règle AGENTS.md).
Toute modification structurelle (schéma, dépendance, API publique, sécurité) suit le workflow proportionné d'AGENTS.md : plan, validation, puis code.