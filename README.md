# API Fullstack - Gestion de Hamsters

API REST Symfony pour la gestion d'un système de hamsters avec authentification JWT.

## 📋 Prérequis

- **PHP** >= 8.2
- **Composer** >= 2.0
- **MySQL** ou **PostgreSQL** (ou autre base de données supportée par Doctrine)
- **OpenSSL** (pour la génération des clés JWT)
- **Symfony CLI** (optionnel, recommandé)

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone <url-du-repo>
cd FullstackAPI_paul_mehr
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer les variables d'environnement

Créez un fichier `.env.local` à la racine du projet :

```env
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/hamster_db?serverVersion=8.0.32&charset=utf8mb4"
# Ou pour PostgreSQL :
# DATABASE_URL="postgresql://user:password@127.0.0.1:5432/hamster_db?serverVersion=15&charset=utf8"

# JWT Configuration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
```

**Important :** Remplacez :
- `user` et `password` par vos identifiants de base de données
- `hamster_db` par le nom de votre base de données
- `your_passphrase_here` par une phrase secrète de votre choix

### 4. Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

Cette commande génère les clés privée et publique nécessaires pour l'authentification JWT dans le dossier `config/jwt/`.

### 5. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 6. Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 7. Charger les données de test (Fixtures)

```bash
php bin/console doctrine:fixtures:load
```

Cette commande crée :
- **1 utilisateur admin** : `admin@admin.com` / `password` (1000 gold)
- **1 utilisateur normal** : `test@test.com` / `password` (500 gold, 4 hamsters)

## 🏃 Démarrer le serveur

### Avec Symfony CLI (recommandé)

```bash
symfony server:start
```

### Avec PHP built-in server

```bash
php -S 127.0.0.1:8000 -t public
```

L'API sera accessible sur : `http://127.0.0.1:8000`

## 📚 Documentation de l'API

### Base URL

```
http://127.0.0.1:8000/api
```

### Authentification

L'API utilise l'authentification JWT. Pour obtenir un token :

```bash
POST /api/login_check
Content-Type: application/json

{
  "email": "test@test.com",
  "password": "password"
}
```

**Réponse :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Utilisation du token :**
Ajoutez le header suivant à toutes vos requêtes authentifiées :
```
Authorization: Bearer {votre_token}
```

---

## 🔐 Routes d'authentification

### POST /api/register
Inscription d'un nouvel utilisateur

**Body :**
```json
{
  "email": "user@example.com",
  "password": "motdepasse"
}
```

**Validation :**
- Email valide
- Mot de passe minimum 8 caractères

**Réponse :**
```json
{
  "id": 1,
  "email": "user@example.com",
  "gold": 500,
  "hamsters": []
}
```

### POST /api/login_check
Connexion et obtention du token JWT

**Body :**
```json
{
  "email": "user@example.com",
  "password": "motdepasse"
}
```

**Réponse :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### GET /api/user
Récupère les informations de l'utilisateur connecté

**Headers :** `Authorization: Bearer {token}`

**Réponse :**
```json
{
  "id": 1,
  "email": "user@example.com",
  "gold": 500,
  "hamsters": [...]
}
```

### DELETE /api/delete/{id}
Supprime un utilisateur (Admin uniquement)

**Headers :** `Authorization: Bearer {token_admin}`

---

## 🐹 Routes de gestion des hamsters

### GET /api/hamsters
Récupère tous les hamsters de l'utilisateur connecté

**Headers :** `Authorization: Bearer {token}`

**Réponse :**
```json
{
  "listHamster": [
    {
      "id": 1,
      "name": "Hamster0",
      "age": 0,
      "hunger": 100,
      "gender": "m",
      "active": true
    }
  ]
}
```

### GET /api/hamsters/{id}
Récupère les informations d'un hamster spécifique

**Permissions :**
- Utilisateur : Peut voir uniquement ses propres hamsters
- Admin : Peut voir n'importe quel hamster

**Headers :** `Authorization: Bearer {token}`

### POST /api/hamsters/reproduce
Crée un nouveau hamster par reproduction

**Body :**
```json
{
  "idHamster1": 1,
  "idHamster2": 2
}
```

**Conditions :**
- Les deux hamsters doivent appartenir à l'utilisateur
- Les deux hamsters doivent être actifs
- Les deux hamsters doivent être de sexes opposés

**Réponse :**
```json
{
  "id": 5,
  "name": "Baby123",
  "age": 0,
  "hunger": 100,
  "gender": "m",
  "active": true
}
```

### POST /api/hamsters/{id}/feed
Nourrit un hamster (hunger passe à 100)

**Coût :** `(100 - hunger_actuel)` en gold

**Exemple :** Si un hamster a 40 en hunger, il passe à 100 et 60 gold sont retirés.

**Headers :** `Authorization: Bearer {token}`

**Réponse :**
```json
{
  "gold": 440,
  "hamster": {
    "id": 1,
    "name": "Hamster0",
    "hunger": 100,
    "age": 5,
    "active": true
  }
}
```

### POST /api/hamsters/{id}/sell
Vend un hamster pour 300 gold

**Headers :** `Authorization: Bearer {token}`

**Réponse :**
```json
{
  "message": "Hamster vendu avec succès",
  "gold": 800
}
```

### POST /api/hamster/sleep/{nbDays}
Fait vieillir tous les hamsters de l'utilisateur

**Effets :**
- Age : +nbDays
- Hunger : -nbDays

**Headers :** `Authorization: Bearer {token}`

**Exemple :** `POST /api/hamster/sleep/10`

**Réponse :**
```json
{
  "message": "Tous les hamsters ont vieillis de 10 jours",
  "nbDays": 10,
  "affectedHamsters": 3,
  "inactiveHamsters": 0
}
```

### PUT /api/hamsters/{id}/rename
Renomme un hamster

**Permissions :**
- Utilisateur : Peut renommer uniquement ses propres hamsters
- Admin : Peut renommer n'importe quel hamster

**Body :**
```json
{
  "name": "NouveauNom"
}
```

**Validation :** Le nom doit contenir au moins 2 caractères

**Headers :** `Authorization: Bearer {token}`

**Réponse :**
```json
{
  "message": "Hamster renommé avec succès",
  "hamster": {
    "id": 1,
    "name": "NouveauNom",
    ...
  }
}
```

---

## 🎮 Règles du jeu

### Hamsters
- **Name** : Minimum 2 caractères
- **Hunger** : 0-100 (devient inactif si < 0)
- **Age** : 0-500 jours (devient inactif si > 500)
- **Gender** : 'm' ou 'f'
- **Active** : true/false (devient false si age > 500 ou hunger < 0)

### Effets de transaction
Après chaque action (feed, sell, reproduce), tous les hamsters de l'utilisateur :
- Vieillissent de **+5 jours** (age +5)
- Perdent **-5 de faim** (hunger -5)

### Utilisateurs
- **Gold initial** : 500 (admin : 1000)
- **Rôles** : ROLE_USER, ROLE_ADMIN

---

## 🧪 Comptes de test

Après avoir chargé les fixtures :

**Admin :**
- Email : `admin@admin.com`
- Password : `password`
- Gold : 1000

**User :**
- Email : `test@test.com`
- Password : `password`
- Gold : 500
- Hamsters : 4 (2 mâles, 2 femelles)

---

## 🛠️ Commandes utiles

### Base de données
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Vider la base de données
php bin/console doctrine:database:drop --force
```

### Cache
```bash
# Vider le cache
php bin/console cache:clear
```

### JWT
```bash
# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair
```

---

## 📝 Structure du projet

```
├── config/
│   ├── jwt/              # Clés JWT (générées)
│   ├── packages/         # Configuration des bundles
│   └── routes.yaml       # Configuration des routes
├── migrations/           # Migrations Doctrine
├── public/              # Point d'entrée (index.php)
├── src/
│   ├── Controller/      # Contrôleurs API
│   ├── Entity/          # Entités Doctrine
│   ├── Repository/      # Repositories
│   ├── Service/         # Services métier
│   └── DataFixtures/    # Données de test
└── var/                 # Cache, logs
```

---

## ⚠️ Notes importantes

1. **Sécurité JWT** : Ne partagez jamais votre `JWT_PASSPHRASE` et gardez les clés privées secrètes
2. **Base de données** : Assurez-vous que la base de données existe avant d'exécuter les migrations
3. **Fixtures** : Les fixtures suppriment toutes les données existantes avant de charger les nouvelles
4. **Environnement** : Utilisez `.env.local` pour vos configurations locales (non versionné)

---

## 🐛 Dépannage

### Erreur "Invalid JWT Token"
- Vérifiez que les clés JWT sont générées : `php bin/console lexik:jwt:generate-keypair`
- Vérifiez le format du header : `Authorization: Bearer {token}` (avec un espace après "Bearer")

### Erreur de connexion à la base de données
- Vérifiez votre `DATABASE_URL` dans `.env.local`
- Assurez-vous que la base de données existe
- Vérifiez les identifiants (user, password)

### Erreur 404 sur les routes
- Videz le cache : `php bin/console cache:clear`
- Vérifiez que le serveur est démarré

---

## 📄 Licence

Proprietary

---

## 👥 Auteur

Paul Mehr

---

**Bon développement ! 🚀**

