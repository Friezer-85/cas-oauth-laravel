# 🔐 CAS OAuth Laravel

Un pont entre un fournisseur OAuth (Discord, Google, GitHub, etc.) et une application utilisant uniquement CAS pour l'authentification.

Ce package permet de transformer n'importe quel provider OAuth en serveur CAS, idéal pour connecter des applications legacy (comme Pronote, Moodle, etc.) à des systèmes d'authentification modernes.

---

## 📋 Table des matières

- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
- [Providers supportés](#-providers-supportés)
- [Dépannage](#-dépannage)

---

## 🔧 Prérequis

- PHP 8.2 ou supérieur
- Laravel 12.x
- Un serveur web (Apache/Nginx)
- Une application OAuth (Discord, Google, GitHub, etc.)

---

## 📦 Installation

### 1. Créer un nouveau projet Laravel

```bash
composer create-project laravel/laravel mon-serveur-cas
cd mon-serveur-cas
```

### 2. Installer le package

```bash
composer require friezer-85/cas-oauth-laravel
```

### 3. Installer un provider Socialite

Selon le provider OAuth que vous souhaitez utiliser :

#### Discord
```bash
composer require socialiteproviders/discord
```

#### GitHub
```bash
composer require socialiteproviders/github
```

#### Google
```bash
composer require socialiteproviders/google
```

#### Autres providers
Voir la liste complète sur [SocialiteProviders](https://socialiteproviders.com/)

---

## ⚙️ Configuration

### 1. Créer l'application OAuth

#### Pour Discord :

1. Allez sur https://discord.com/developers/applications
2. Cliquez sur "New Application"
3. Donnez un nom à votre application
4. Allez dans "OAuth2" → "General"
5. Ajoutez l'URL de redirection :
   ```
   http://cas.example.com/oauth/callback
   ```
   (En production, remplacez par votre vrai domaine)
6. Notez votre **Client ID** et **Client Secret**

#### Pour Google :

1. Allez sur https://console.cloud.google.com/
2. Créez un nouveau projet
3. Activez "Google+ API"
4. Allez dans "Credentials" → "Create Credentials" → "OAuth client ID"
5. Ajoutez l'URL de redirection :
   ```
   http://cas.example.com/oauth/callback
   ```
6. Notez votre **Client ID** et **Client Secret**

#### Pour GitHub :

1. Allez sur https://github.com/settings/developers
2. Cliquez sur "New OAuth App"
3. Remplissez les informations :
   - **Homepage URL** : `http://cas.example.com`
   - **Authorization callback URL** : `http://cas.example.com/oauth/callback`
4. Notez votre **Client ID** et **Client Secret**

### 2. Configuration du fichier .env

Ajoutez ces lignes à votre fichier `.env` :

```bash
# Provider OAuth à utiliser (discord, github, google, etc.)
OAUTH_PROVIDER=discord

# Credentials de votre application OAuth
OAUTH_CLIENT_ID=votre_client_id_ici
OAUTH_CLIENT_SECRET=votre_client_secret_ici

# Scopes OAuth (séparés par des virgules ou espaces)
# Discord :
OAUTH_SCOPES=identify,email

# Google :
# OAUTH_SCOPES=openid,profile,email

# GitHub :
# OAUTH_SCOPES=user:email

# Propriété utilisée pour générer le ticket CAS (défaut: id)
CAS_PROPERTY=id

# Paramètres OAuth personnalisés (optionnel)
# Format: key=value,key2=value2
OAUTH_PARAMS=
```

### 3. Configuration des services autorisés

Éditez le fichier `config/services.php` et ajoutez :

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CAS Services Configuration
    |--------------------------------------------------------------------------
    |
    | Liste des URLs autorisées à utiliser le serveur CAS.
    | Utilisez des regex pour matcher les URLs.
    | IMPORTANT : Échappez les points avec \.
    |
    */

    'cas' => [
        // Exemple : autoriser tout sur un domaine
        'https://app1\.example\.com/(.*)',
        
        // Exemple : autoriser plusieurs domaines
        'https://moodle\.example\.com/(.*)',
        'https://app2\.example\.com/(.*)',
        
        // Pour le développement local
        'http://localhost/(.*)',
        'http://127\.0\.0\.1:8000/(.*)',
    ],
];
```

⚠️ **Important** : Les points `.` doivent être échappés avec `\.` dans les regex, sinon ils matchent n'importe quel caractère.

### 4. Lancer les migrations

```bash
php artisan migrate
```

Cette commande créera la table `tickets` nécessaire au fonctionnement du CAS.

---

### Endpoints disponibles

#### 1. **Login CAS** (Entrypoint)
```
GET /cas/login?service={RETURN_URL}
```

Exemple :
```
https://cas.example.com/cas/login?service=https://app.example.com/cas
```

**Paramètres :**
- `service` (requis) : URL de redirection après authentification
- `renew` (optionnel) : Force une nouvelle authentification

#### 2. **Service Validate** (Validation de ticket)
```
GET /cas/serviceValidate?service={URL}&ticket={TICKET}
```

Retourne une réponse XML avec les informations de l'utilisateur.

#### 3. **SAML Validate** (Validation SAML)
```
POST /cas/samlValidate
```

Même chose que serviceValidate mais avec le format SAML.

### Flow d'authentification complet

```
1. Application CAS → /cas/login?service=https://example.com
   ↓
2. Redirection vers Discord (ou autre provider)
   ↓
3. Utilisateur s'authentifie sur le provider
   ↓
4. Callback → /oauth/callback
   ↓
5. Génération du ticket CAS
   ↓
6. Redirection vers https://example.com?ticket=ST-xxx
   ↓
7. L'application valide le ticket via /cas/serviceValidate
   ↓
8. Réponse XML avec les infos utilisateur
```

### Exemple de réponse XML

Après validation d'un ticket, vous recevrez une réponse XML de ce type :

```xml
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
  <cas:authenticationSuccess>
    <cas:user>123456789</cas:user>
    <cas:attributes>
      <cas:id>123456789</cas:id>
      <cas:nickname>username</cas:nickname>
      <cas:name>User Name</cas:name>
      <cas:email>user@example.com</cas:email>
      <cas:avatar>https://cdn.discordapp.com/avatars/...</cas:avatar>
    </cas:attributes>
  </cas:authenticationSuccess>
</cas:serviceResponse>
```

---

## 🔌 Providers supportés

Ce package utilise [Laravel Socialite](https://laravel.com/docs/socialite) et [SocialiteProviders](https://socialiteproviders.com/).

### Providers testés

| Provider | Package | Scopes recommandés |
|----------|---------|-------------------|
| Discord | `socialiteproviders/discord` | `identify email` |
| GitHub | `socialiteproviders/github` | `user:email` |
| Google | `socialiteproviders/google` | `openid,profile,email` |
| Microsoft | `socialiteproviders/microsoft` | `openid,profile,email` |

### Ajouter un nouveau provider

1. Installez le package Socialite correspondant
2. Configurez `OAUTH_PROVIDER` dans `.env`
3. Ajoutez les credentials dans `.env`
4. Ajoutez les scopes appropriés

Exemple pour Microsoft :

```bash
composer require socialiteproviders/microsoft
```

```bash
OAUTH_PROVIDER=microsoft
OAUTH_CLIENT_ID=votre_client_id
OAUTH_CLIENT_SECRET=votre_client_secret
OAUTH_SCOPES=openid,profile,email
```

---

## 🐛 Dépannage

### Erreur 404 sur /cas/login

**Cause** : Les routes ne sont pas chargées

**Solutions** :
1. Vérifiez que toutes les variables obligatoires sont dans `.env`
2. Vérifiez que `config/services.php` contient la clé `'cas'`
3. Clearez le cache :
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```
4. Vérifiez les routes chargées :
```bash
php artisan route:list | grep cas
```

### Erreur "redirect_uri_mismatch"

**Cause** : L'URL de callback ne correspond pas à celle configurée dans votre application OAuth

**Solution** : Vérifiez que l'URL dans votre application OAuth est exactement :
```
http://cas.example.com/oauth/callback
```

### Erreur "invalid_client"

**Cause** : Client ID ou Secret incorrect

**Solution** : Vérifiez vos credentials dans `.env` et dans votre application OAuth

### Scope "1" au lieu des vrais scopes (Discord)

**Cause** : Scopes non déclarés

**Solution** : Déclarez au minimum le scope openid ou identify :
```bash
# ✅ BON
OAUTH_SCOPES=openid
```

### Ticket invalide lors de la validation

**Cause** : Le ticket a expiré (10 secondes par défaut)

**Solution** : Les tickets CAS sont à usage unique et expirent rapidement. Assurez-vous que votre application valide le ticket immédiatement après l'avoir reçu.

### Service non autorisé

**Cause** : L'URL du service n'est pas dans la liste des services autorisés

**Solution** : Ajoutez l'URL dans `config/services.php` avec la bonne regex :
```php
'cas' => [
    'https://app\.example\.com/(.*)',
]
```

---

## 📝 Variables d'environnement

### Obligatoires

| Variable | Description | Exemple |
|----------|-------------|---------|
| `OAUTH_PROVIDER` | Provider OAuth à utiliser | `discord` |
| `OAUTH_CLIENT_ID` | ID client OAuth | `123456789` |
| `OAUTH_CLIENT_SECRET` | Secret client OAuth | `abcdef123456` |

### Optionnelles

| Variable | Description | Défaut | Exemple |
|----------|-------------|--------|---------|
| `CAS_PROPERTY` | Propriété utilisée pour le user CAS | `id` | `email` |
| `OAUTH_SCOPES` | Scopes OAuth demandés | `openid,profile,email` | `identify email` |
| `OAUTH_PARAMS` | Paramètres OAuth personnalisés | `` | `prompt=consent` |

---

## 🔒 Sécurité

- Les tickets CAS expirent après 10 secondes
- Les tickets sont à usage unique
- Les services doivent être explicitement autorisés dans `config/services.php`
- Utilisez HTTPS en production
- Ne commitez jamais votre `.env` dans Git

---

## 📄 Licence

Ce projet est sous licence [GNU General Public License v3.0](LICENSE).

---


**⭐ Si ce projet vous a aidé, n'hésitez pas à lui donner une étoile sur GitHub !**
