# Staging Access Control — Fichier de Contexte Technique

> **Dépôt GitHub** : [SOYOO974/woo-staging-access-control](https://github.com/SOYOO974/woo-staging-access-control)  
> **Auteur** : Soyoo.re (`dev@soyoo.re`)  
> **Version actuelle** : 1.1.6  
> **Type** : Extension WordPress / WooCommerce  
> **Mises à jour automatiques** : Intégrées via *Plugin Update Checker (PUC) v5* branché sur `main`

---

## 1. Vue d'Ensemble & Objectif Métier

**Staging Access Control (SAC)** est une extension WordPress développée par SOYOO pour sécuriser et isoler les environnements de préproduction / recette (*staging*).

Ses deux missions fondamentales :
1. **Verrouiller le front-end du site de staging** :
   - Empêcher l'indexation par les moteurs de recherche (Google, Bing) et la navigation publique par des visiteurs non autorisés.
   - Présenter une page d'attente / maintenance soignée (Glassmorphism sombre) avec bouton de redirection vers la production.
   - Permettre un déverrouillage discret via mot de passe partagé (avec cookie de session 24h), liste blanche d'adresses IP, ou authentification administrateur WordPress.
2. **Neutraliser totalement l'envoi d'e-mails en staging** :
   - Éviter le risque critique d'envoyer des e-mails transactionnels réels aux clients (commandes fictives, relances paniers abandonnés, réinitialisations de mot de passe, notifications) lors des phases de tests ou de synchronisation de base de données.
   - Intégrer un mock PHPMailer complet et un module *Must-Use Plugin* (mu-plugin) garantissant l'interception prioritaire de `wp_mail()`.

---

## 2. Architecture des Fichiers & Rôles

```text
woo-staging-access-control/
├── staging-access-control.php          # Point d'entrée principal, constantes, hook d'activation, initialisation
├── includes/
│   ├── class-staging-access-control.php # Cœur du contrôle d'accès : vérification IP, staging, cookies, redirection
│   ├── class-sac-settings.php          # Interface d'administration WP (onglets Général, Mot de passe, E-mails, Logs)
│   ├── class-sac-logger.php            # Gestionnaire SQL des logs des tentatives d'accès bloquées
│   ├── class-sac-disable-emails.php    # Logique d'interception d'e-mails, indicateurs admin, gestionnaire du mu-plugin
│   ├── class-sac-phpmailer-mock.php    # Mock PHPMailer compatible simulant l'envoi réussi sans émettre de mail
│   └── sac-pluggable.php               # Remplacement de la fonction pluggable wp_mail()
├── mu-plugin/
│   └── sac-disable-emails-mu.php       # Fichier MU-Plugin copiable dans wp-content/mu-plugins/
├── templates/
│   ├── maintenance-page.php            # Template autonome de la page d'interdiction/maintenance (HTTP 503)
│   └── password-page.php               # Template autonome de la page de déverrouillage par mot de passe (HTTP 401)
└── plugin-update-checker/              # Librairie YahnisElsts/plugin-update-checker v5.7
```

---

## 3. Analyse Détaillée des Composants

### 3.1. Point d'Entrée (`staging-access-control.php`)
- **Constantes définies** : `SAC_VERSION` (1.1.5), `SAC_PLUGIN_DIR`, `SAC_PLUGIN_URL`.
- **Auto-Updater** : Déclaration de `PucFactory::buildUpdateChecker` ciblant le dépôt GitHub public `SOYOO974/woo-staging-access-control` sur la branche `main` avec support des *Release Assets*.
- **Activation** : `register_activation_hook` appelant `SAC_Logger::create_table()`.
- **Chargement** : Sur le hook `plugins_loaded`, instancie `Staging_Access_Control`, `SAC_Disable_Emails`, et `SAC_Settings` (si `is_admin()`).
- **Inclusion anticipée** : Inclut `sac-pluggable.php` au chargement du fichier (avant `pluggable.php` du core WordPress si le plugin est actif).

---

### 3.2. Contrôle d'Accès Front-End (`Staging_Access_Control`)

#### Déclenchement & Règles d'Exception
Le filtre s'exécute sur le hook `template_redirect` (priorité par défaut 10).
L'accès est accordé sans blocage si **l'une** des conditions suivantes est remplie :
1. **Contexte non-front** : `is_admin()`, `wp_doing_ajax()`, ou `wp_doing_cron()`.
2. **Environnement non-staging** : `! $this->is_staging()`.
3. **Privilèges administrateur** : `current_user_can( 'manage_options' )`.
4. **IP en liste blanche** : Correspondance exacte avec une entrée de `sac_ip_whitelist`.
5. **Cookie de session valide** : `$_COOKIE['sac_bypass_token'] === wp_hash( 'sac_access_' . $correct_password )`.

#### Détection du Staging (`is_staging()`)
- Actuellement basé uniquement sur :
  ```php
  $site_url = site_url();
  return ( strpos( $site_url, 'staging' ) !== false );
  ```

#### Récupération de l'IP Visiteur (`get_visitor_ip()`)
- Ordre de priorité :
  1. `$_SERVER['HTTP_CF_CONNECTING_IP']` (Support Cloudflare)
  2. Premier segment de `$_SERVER['HTTP_X_FORWARDED_FOR']` (Reverse proxies)
  3. `$_SERVER['REMOTE_ADDR']`

#### Déverrouillage par Mot de Passe
- Le paramètre discret d'URL (par défaut `?unlock=1`) force l'affichage du template `password-page.php`.
- La soumission du formulaire poste `sac_bypass_password_input` avec un nonce WP (`sac_bypass_nonce`).
- Si le mot de passe correspond à `sac_bypass_password` (par défaut `soyoo`) :
  - Pose d'un cookie HTTP-Only `sac_bypass_token` d'une durée de 24 heures (`time() + 24 * HOUR_IN_SECONDS`).
  - Redirection vers l'URI d'origine épurée du paramètre de déverrouillage via `wp_safe_redirect()`.

#### Pages d'Affichage & En-têtes HTTP
- **Page Maintenance** : Code HTTP `503 Service Unavailable`, en-têtes `nocache_headers()`, constante `DONOTCACHEPAGE` définie à `true`.
- **Page Mot de Passe** : Code HTTP `401 Unauthorized`, en-têtes `nocache_headers()`.
- **Design** : Aucune dépendance externe (hors police Google Inter). Palette dark moderne, glassmorphism CSS, animations fluides, support du logo personnalisé et de la couleur d'accentuation dynamique.

---

### 3.3. Neutralisation des E-mails (`SAC_Disable_Emails` & `SAC_PHPMailer_Mock`)

#### Stratégie de Surcharge
- WordPress déclare `wp_mail()` dans `wp-includes/pluggable.php`.
- Si `sac-pluggable.php` est chargé avant, il définit sa propre fonction `wp_mail()` qui instancie `SAC_PHPMailer_Mock`.
- **Le mock PHPMailer** :
  - Encapsule une instance réelle de PHPMailer (`$this->phpmailer = new PHPMailer( true )`).
  - Déclenche optionnellement les hooks habituels de WordPress (`wp_mail`, `wp_mail_from`, `wp_mail_from_name`, `wp_mail_content_type`, `wp_mail_charset`, `phpmailer_init`) pour préserver les éventuels outils de journalisation/logs (comme WP Mail Logging) sans envoyer le mail réel.
  - Retourne systématiquement `true` pour tromper l'appelant et éviter tout blocage d'exécution de code ou exception non attrapée.

#### Sécurité MU-Plugin (Must-Use)
- Problème classique dans WordPress : d'autres extensions (ex: extensions SMTP, extensions d'envoi transactionnel) peuvent se charger avant les extensions standards dans le dossier `plugins/` et déclarer `wp_mail()` en premier.
- Solution intégrée : Dans l'onglet *Disable Emails*, un bouton en 1 clic installe `sac-disable-emails-mu.php` dans `wp-content/mu-plugins/`.
- Le MU-Plugin s'exécute en tout premier avant n'importe quel plugin actif standard, garantissant la prise de contrôle absolue sur `wp_mail()`.

#### Intégrations Spécifiques
- **BuddyPress** : Filtre `bp_email_use_wp_mail` forcé à `true` pour forcer BuddyPress à utiliser la pile standard intercepted.
- **Events Manager** : Forçage de `dbem_rsvp_mail_send_method` à `wp_mail`.
- **Indicateurs d'état** :
  - Badge / icône dans la barre d'administration WP (`admin_bar`).
  - Notice globale d'alerte pour les administrateurs.
  - Widget statut dans la boîte *D'un coup d'œil* du tableau de bord.

---

### 3.4. Journalisation des Tentatives d'Accès (`SAC_Logger`)

- **Table SQL dédiée** : `{$wpdb->prefix}sac_logs`
  - `id` (bigint, auto-increment)
  - `ip_address` (varchar 100)
  - `timestamp` (datetime)
  - `requested_url` (varchar 255)
  - `user_agent` (varchar 255)
- **Affichage Admin** : Tableau paginé (20 par page) avec horodatage, IP, URL et User-Agent.
- **Bouton d'action** : `TRUNCATE TABLE` avec nonce de sécurité et confirmation JS.

---

### 3.5. Réglages & Options en Base de Données

| Clé Option WP | Type | Valeur par défaut | Description |
| :--- | :--- | :--- | :--- |
| `sac_production_url` | string (URL) | `''` | URL du site de production (ex: `https://monsite.com`) |
| `sac_button_text` | string | `'Go to Production Site'` | Libellé du bouton principal vers la production |
| `sac_login_button_text`| string | `'Staging Login'` | Libellé du lien discret d'accès au formulaire |
| `sac_button_color` | string (hex) | `'#3b82f6'` | Couleur principale (accentuation boutons/glow) |
| `sac_heading_text` | string | `'Staging Environment'` | Titre H1 de la page de maintenance |
| `sac_maintenance_message` | string (HTML) | `'This staging site is currently restricted.'` | Message explicatif de maintenance |
| `sac_custom_logo` | int | `0` | ID d'attachement média du logo |
| `sac_logo_max_width` | int | `200` | Largeur maximale du logo en pixels |
| `sac_ip_whitelist` | string (multiline) | `''` | Liste d'adresses IP autorisées (une par ligne) |
| `sac_bypass_param` | string | `'unlock'` | Nom du paramètre d'URL discret pour le login |
| `sac_bypass_password` | string | `'soyoo'` | Mot de passe de déverrouillage du staging |
| `sac_password_heading_text` | string | `'Access Restricted'` | Titre H1 de la page de mot de passe |
| `sac_password_message` | string (HTML) | `'Please enter the password...'` | Message au-dessus du champ mot de passe |
| `sac_password_placeholder` | string | `'Enter password'` | Placeholder du champ input |
| `sac_password_button_text` | string | `'Unlock Site'` | Libellé du bouton de soumission de mot de passe |
| `sac_disable_emails_options` | array | `[]` | Tableau associatif de configuration du bloqueur d'e-mails |

---

## 4. Évolutions Apportées en Version 1.1.6

Plusieurs optimisations majeures ont été déployées pour consolider l'extension sur l'infrastructure **Rocket.net** :

1. **Bouton d'Ajout d'IP en 1-Clic (`sac_add_current_ip_btn`)** :
   - Détection et affichage de l'IP actuelle du visiteur dans l'onglet *General*.
   - Bouton interactif permettant d'insérer instantanément son adresse IP dans le champ `sac_ip_whitelist` sans risque de doublon, facilitant l'accès des postes de développement et agents IA locaux.
2. **Rotation & Purge Automatique des Logs (`SAC_Logger::purge_old_logs`)** :
   - Définition d'un seuil maximal de rétention (`MAX_LOGS = 1000`).
   - Purge automatique à l'écriture pour supprimer les anciennes entrées et préserver le volume de la base de données.
   - Tronquage sécurisé des champs `requested_url` et `user_agent` à 255 caractères (`mb_substr`) pour éviter les rejets MySQL en mode strict.
3. **Sécurisation de la Détection IP (`get_visitor_ip`)** :
   - Priorité stricte à `HTTP_CF_CONNECTING_IP` (fourni de manière infalsifiable par Cloudflare Enterprise sur Rocket.net) avec validation de format `FILTER_VALIDATE_IP`.
   - Fallback sur `REMOTE_ADDR` puis `HTTP_X_FORWARDED_FOR` validé.
4. **Neutralisation du Cache Edge Rocket.net / Cloudflare** :
   - Lorsqu'un visiteur est autorisé (IP en liste blanche ou cookie de session `sac_bypass_token`), l'interdiction de mise en cache (`DONOTCACHEPAGE` + `nocache_headers()`) est automatiquement imposée pour éviter qu'une page du staging déverrouillée ne soit mise en cache à l'Edge pour d'autres utilisateurs.
5. **Fiabilisation du MU-Plugin (`sac-disable-emails-mu.php`)** :
   - Résolution dynamique multi-chemins (`woo-staging-access-control` et `staging-access-control`, avec recherche `glob`) pour garantir l'interception de `wp_mail()` quel que soit le nom du dossier d'installation Git/ZIP.
6. **Extensibilité de la Détection Staging** :
   - Ajout du filtre WordPress `sac_is_staging` pour permettre un override si nécessaire.

---

## 5. Guide de Déploiement & Bonnes Pratiques SOYOO

1. **Installation sur un Staging** :
   - Déposer le plugin dans `wp-content/plugins/woo-staging-access-control/`.
   - Activer l'extension dans l'admin WordPress.
   - Vérifier que la table `{$wpdb->prefix}sac_logs` a bien été générée.
2. **Activation du MU-Plugin** :
   - Aller dans **Réglages > Staging Access Control > Onglet Disable Emails**.
   - Cliquer sur **Activate must-use plugin** pour blinder la coupure des emails avant toute autre extension.
3. **Configuration du contournement** :
   - Renseigner l'URL de production.
   - Configurer le mot de passe de bypass (différent de `soyoo` par défaut).
   - Utiliser le bouton **"Add My Current IP"** pour autoriser immédiatement votre machine / agent local de dev.
4. **Mises à jour** :
   - Toute modification poussée sur la branche `main` du dépôt `SOYOO974/woo-staging-access-control` sera notifiée directement dans le tableau de bord WordPress via le *Plugin Update Checker*.

