# 🤘 Punk Booking

Outil de booking de concerts pour un groupe DIY : suivi des lieux, des contacts, des pistes de dates et des relances, avec un back-office Filament et une intégration Slack dans les deux sens.

- **Back-office** (`/admin`) : lieux, contacts, tournées, dates (gigs) avec historique des échanges, indisponibilités des membres, tableau de bord (stats, relances du jour, prochaines dates).
- **Slack → app** : la commande `/lieu` ouvre une modale pour ajouter un lieu sans quitter Slack.
- **App → Slack** : annonce des dates confirmées, alerte si un membre est indisponible ce jour-là, récap quotidien des relances (lun–ven, 9h).

---

## Sommaire

1. [Stack](#stack)
2. [Installation locale](#installation-locale)
3. [Utilisation](#utilisation)
4. [Intégration Slack](#intégration-slack)
5. [Architecture](#architecture)
6. [Tests et qualité](#tests-et-qualité)
7. [Mise en production](#mise-en-production)
8. [Gestion du dépôt](#gestion-du-dépôt)
9. [Dépannage](#dépannage)

---

## Stack

| Composant | Version |
|---|---|
| PHP | **8.4 minimum** (8.5 recommandé) — imposé par `composer.lock` |
| Laravel | 13 |
| Filament | 5 (Livewire 4) |
| Base de données | SQLite ou MySQL 8.4 |
| Tests | Pest 4 (SQLite en mémoire) |
| Environnement | au choix : [Laravel Herd](https://herd.laravel.com) ou Docker via [Laravel Sail](https://laravel.com/docs/sail) |

Aucun paquet Slack externe : les appels passent par le client HTTP de Laravel.

---

## Installation locale

Deux façons de faire tourner le projet, au choix. **Herd** est le plus simple si on ne veut pas de Docker ; **Sail** reproduit un environnement complet (MySQL, Redis, Mailpit…) dans des conteneurs.

> Dans le reste de ce README, les commandes sont écrites `php artisan …`.
> Avec Sail, remplacer `php` par `sail` : `sail artisan …`, `sail composer …`, `sail npm …`, `sail test`.

### Option A — Laravel Herd (sans Docker)

**Prérequis** : [Herd](https://herd.laravel.com) (Windows ou macOS) avec **PHP 8.4 ou 8.5** sélectionné, et Node.js (fourni par Herd).
Herd embarque PHP, Composer et Node : rien d'autre à installer. La base par défaut est **SQLite** (un simple fichier), aucune base de données à installer.

```bash
# 1. Cloner dans le dossier des sites Herd
#    (Windows : %USERPROFILE%\Herd — macOS : ~/Herd)
cd ~/Herd
git clone https://github.com/waner1er/punk-booking.git
cd punk-booking

# 2. Dépendances
composer install
npm install && npm run build

# 3. Configuration (.env.example est déjà prévu pour SQLite)
cp .env.example .env          # PowerShell : Copy-Item .env.example .env
php artisan key:generate
```

Dans `.env`, ajuster :

```dotenv
APP_NAME="Punk Booking"
APP_URL=http://punk-booking.test
APP_LOCALE=fr
```

```bash
# 4. Base de données + données de démo
#    (répondre « yes » si artisan propose de créer database/database.sqlite)
php artisan migrate --seed
```

Le site est servi automatiquement par Herd sur **http://punk-booking.test**, back-office sur **http://punk-booking.test/admin**.
Si le projet est cloné ailleurs que dans le dossier Herd : `herd link punk-booking` depuis le dossier du projet.

> Herd Pro (ou DBngin) fournit MySQL si on préfère : renseigner alors `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dans `.env` et créer la base avant `migrate`.

### Option B — Laravel Sail (Docker)

**Prérequis** :

- Docker (Docker Desktop sous Windows/macOS).
- **Windows** : WSL2 obligatoire, Sail ne fonctionne pas sous PowerShell ni Git Bash. Cloner le projet **dans le système de fichiers WSL** (`~/code/…`) plutôt que sur `/mnt/c` ou `/mnt/d` : c'est nettement plus rapide.
- L'alias `sail` dans `~/.bashrc` ou `~/.zshrc` :

  ```bash
  alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
  ```

Pas besoin de PHP ni de Composer sur la machine :

```bash
git clone https://github.com/waner1er/punk-booking.git
cd punk-booking

# 1. Dépendances PHP via un conteneur jetable (une seule fois)
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

# 2. Configuration
cp .env.example .env
```

Dans `.env`, passer sur le MySQL du conteneur (`.env.example` est prévu pour SQLite) :

```dotenv
APP_NAME="Punk Booking"
APP_URL=http://localhost
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

```bash
# 3. Démarrer les conteneurs
sail up -d

# 4. Clé d'application, base et données de démo
sail artisan key:generate
sail artisan migrate --seed

# 5. Assets front
sail npm install && sail npm run build
```

Le back-office est sur **http://localhost/admin** (port réglable via `APP_PORT` dans `.env`).

### Comptes de démo (seeder)

| Email | Mot de passe | Accès panel |
|---|---|---|
| `admin@admin.com` | `password` | oui |
| `erwan@erwan.com` | `password` | non |
| `tom@`, `julie@`, `max@`, `lea@groupe.test` | `password` | non (membres avec indispos) |

Le `BookingSeeder` crée des lieux, contacts, tournées, dates et échanges fictifs, avec des dates calculées par rapport à aujourd'hui : le dashboard reste « vivant » quel que soit le jour où on le lance. Repartir de zéro : `php artisan migrate:fresh --seed`.

> ⚠️ Ces comptes sont **uniquement pour le développement**. Ne jamais lancer le seeder en production.

---

## Utilisation

### Accès au panel

L'accès à `/admin` est réservé aux utilisateurs `is_admin` (`User::canAccessPanel()`). Ce champ n'est volontairement pas « fillable » : on le gère en ligne de commande.

```bash
php artisan booking:make-admin quelqu-un@exemple.fr            # donner l'accès
php artisan booking:make-admin quelqu-un@exemple.fr --revoke   # le retirer
```

### Cycle de vie d'une date

`Piste` → `Contacté` → `En discussion` → `Option posée` → **`Confirmé`** → `Joué`
(ou `Refusé` / `Annulé`).

Les quatre premiers statuts sont « en cours » (`GigStatus::open()`) : ce sont ceux qui apparaissent dans les relances quand `next_follow_up_at` est atteint.

### Commandes console

| Commande | Rôle |
|---|---|
| `php artisan booking:follow-ups` | Tableau des relances du jour et en retard |
| `php artisan booking:follow-ups --slack` | Idem + récap posté dans Slack (rien si la liste est vide) |
| `php artisan booking:make-admin <email> [--revoke]` | Gérer l'accès au panel |
| `php artisan schedule:list` | Voir les tâches planifiées |
| `php artisan schedule:work` | Faire tourner le planificateur en local |

Tâche planifiée (`routes/console.php`) : `booking:follow-ups --slack` du lundi au vendredi à 9h, heure de Paris.

---

## Intégration Slack

Fonctionne avec un espace Slack **gratuit**.

### Variables d'environnement

```dotenv
SLACK_BOOKING_WEBHOOK=   # URL de l'Incoming Webhook (canal de booking)
SLACK_BOT_TOKEN=         # xoxb-… (Bot User OAuth Token)
SLACK_SIGNING_SECRET=    # pour vérifier que les requêtes viennent de Slack
```

Lues dans `config/services.php` (`services.slack.*`). Après modification : `php artisan config:clear`.
Si le webhook n'est pas renseigné, les notifications sont simplement ignorées (pratique en local et en tests). Une erreur Slack est journalisée mais ne bloque jamais une action métier.

### Configurer l'app sur api.slack.com

1. [api.slack.com/apps](https://api.slack.com/apps) → **Create New App** → *From scratch* → choisir l'espace.
2. **Basic Information → App Credentials** : copier le *Signing Secret* → `SLACK_SIGNING_SECRET`.
3. **OAuth & Permissions → Bot Token Scopes** : ajouter `commands` et `chat:write`.
4. **Incoming Webhooks** : activer, puis *Add New Webhook to Workspace*, choisir le canal (ex. `#booking`) → copier l'URL dans `SLACK_BOOKING_WEBHOOK`.
5. **OAuth & Permissions → Install to Workspace** : copier le *Bot User OAuth Token* (`xoxb-…`) → `SLACK_BOT_TOKEN`. Réinstaller l'app après chaque changement de scope.
6. **Slash Commands → Create New Command** :
   - Command : `/lieu`
   - Request URL : `https://<hôte>/slack/command`
   - Description : « Ajouter un lieu »
7. **Interactivity & Shortcuts** : activer, Request URL : `https://<hôte>/slack/interact`.

### Tester en local (tunnel HTTPS)

Slack doit pouvoir joindre l'app en HTTPS depuis Internet : on ouvre un tunnel (Expose) vers le site local.

```bash
# Herd (depuis le dossier du projet)
herd share

# Sail
sail share                          # URL publique aléatoire
sail share --subdomain=punk-booking # URL stable (évite de tout reconfigurer)
```

Reporter l'URL obtenue dans les étapes 6 et 7 ci-dessus, puis :

- taper `/lieu` dans Slack et valider la modale → « 📍 Nouveau lieu ajouté par … » dans le canal ;
- passer une date à « Confirmé » dans le panel → annonce (+ alerte si un membre est indisponible) ;
- `php artisan booking:follow-ups --slack` → récap des relances.

### Sécurité des routes Slack

| Route | Contrôleur |
|---|---|
| `POST /slack/command` | `SlackController@command` |
| `POST /slack/interact` | `SlackController@interact` |

- Middleware `VerifySlackSignature` : HMAC SHA-256 de `v0:{timestamp}:{corps brut}` comparé avec `hash_equals`, requête refusée (401) si la signature est fausse, si le timestamp a plus de 5 minutes ou si le secret n'est pas configuré.
- La protection CSRF (`PreventRequestForgery`) est désactivée **uniquement** sur ces routes : la signature la remplace.

---

## Architecture

```
app/
├── Console/Commands/        booking:make-admin, booking:follow-ups
├── Enums/                   GigStatus, DealType, VenueType, InteractionType (libellés FR)
├── Filament/                Resources, relation managers et widgets du panel admin
├── Http/
│   ├── Controllers/SlackController.php    routage des commandes et des modales Slack
│   └── Middleware/VerifySlackSignature.php
├── Models/                  Venue, Contact, Tour, Gig, Interaction, Unavailability, User
├── Observers/GigObserver.php              notifications à la confirmation d'une date
├── Services/
│   ├── SlackNotifier.php    envoi via Incoming Webhook
│   └── SlackApi.php         API Web Slack (views.open) avec le bot token
└── Slack/
    ├── SlashCommand.php     contrat d'une slash command
    ├── Modal.php            contrat d'une modale (vue + traitement de la soumission)
    ├── ViewState.php        lecture des valeurs saisies dans une modale
    ├── Commands/OpenVenueModal.php        /lieu
    └── Modals/VenueCreateModal.php        callback_id « venue_create »
```

### Modèle de données

- **Venue** (lieu) ↔ **Contact** : plusieurs-à-plusieurs (`contact_venue`).
- **Gig** (date) : appartient à un lieu, éventuellement à un contact et à une tournée (**Tour**) ; a des **Interactions** (historique mail/appel/message/rencontre).
- **Unavailability** : période d'indisponibilité d'un membre (**User**).

### Ajouter une commande Slack (ex. `/date`)

1. `php artisan make:class Slack/Commands/OpenGigModal` → implémenter `App\Slack\SlashCommand`.
2. Si elle ouvre une modale : `php artisan make:class Slack/Modals/GigCreateModal` → implémenter `App\Slack\Modal`, avec une constante `CALLBACK_ID`. Convention : `block_id` = `action_id` = nom du champ (les erreurs de validation se renvoient alors directement par nom de champ).
3. Ajouter une ligne dans `SlackController::COMMANDS` (et `::MODALS`).
4. Déclarer la commande sur api.slack.com (même Request URL `/slack/command`).
5. Écrire les tests dans `tests/Feature/Slack/`.

### Conventions du code

- Création de fichiers via `php artisan make:*`, puis remplissage.
- Attributs Eloquent (`#[Fillable]`, `#[ObservedBy]`), enums PHP avec `HasLabel` / `HasColor`.
- Textes affichés en français.
- Pas de paquet Composer supplémentaire sans vraie nécessité.
- Ne jamais modifier une migration déjà passée : en créer une nouvelle.

---

## Tests et qualité

```bash
php artisan test                       # toute la suite (Pest)
php artisan test --filter=Slack        # un sous-ensemble
vendor/bin/pint                       # formatage (Laravel Pint)
php artisan route:list --path=slack
```

- Les tests tournent sur une base **SQLite en mémoire** (`phpunit.xml`) : rien à créer, identique sous Herd et sous Sail, et sans risque pour les données locales.
- Tous les appels Slack sont simulés avec `Http::fake()` et `Http::preventStrayRequests()` : aucun test ne contacte Slack.
- Helpers de test dans `tests/Pest.php` : `fakeSlackConfig()` et `postFromSlack()` (requête signée comme Slack).

Avant chaque push : `vendor/bin/pint && php artisan test`.

---

## Mise en production

Points à ne pas oublier :

- `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` en **HTTPS** (obligatoire pour Slack), vraies variables `SLACK_*`.
- Déploiement :

  ```bash
  composer install --no-dev --optimize-autoloader
  php artisan migrate --force
  php artisan optimize
  php artisan filament:optimize
  ```

- **Planificateur** : une entrée cron, sinon le récap de 9h ne part jamais :

  ```cron
  * * * * * cd /chemin/vers/punk-booking && php artisan schedule:run >> /dev/null 2>&1
  ```

- `QUEUE_CONNECTION=database` : aucun job n'est mis en file pour l'instant ; si on en ajoute, lancer un worker (`php artisan queue:work`, sous Supervisor).
- Ne **pas** lancer `db:seed` (comptes de démo avec mot de passe `password`). Créer le premier compte puis `php artisan booking:make-admin <email>`.
- Mettre à jour les Request URL de l'app Slack avec le domaine de production.

---

## Gestion du dépôt

- Branche principale : `main`, qui doit toujours passer `vendor/bin/pint --test` et `php artisan test`.
- Travailler sur des branches courtes (`feat/slack-date`, `fix/relances-fuseau`…) puis Pull Request vers `main`.
- Messages de commit courts, à l'impératif, en français (ex. « Ajoute la commande Slack /date »).
- **Secrets** : `.env` est ignoré par Git. Ne jamais committer de token Slack, de webhook ni de mot de passe ; seules les clés **vides** vont dans `.env.example`. En cas de fuite : régénérer le secret côté Slack (*Basic Information → Regenerate*, *OAuth → Revoke/Reinstall*, supprimer le webhook) puis mettre à jour `.env`.
- Mise à jour des dépendances : `composer update` puis `php artisan test` ; `filament:upgrade` est lancé automatiquement par Composer.
- `setup-booking.sh` : script historique qui a généré le socle (enums, modèles, resources Filament). Il refuse de s'exécuter si le socle existe déjà ; il sert de référence, pas d'outil à relancer.
- `AGENTS.md` / `CLAUDE.md` : consignes pour les assistants de code IA.

---

## Dépannage

| Symptôme | Cause / solution |
|---|---|
| `Unsupported operating system [MINGW64…]` | Sail lancé depuis Git Bash ou PowerShell : ouvrir un terminal **WSL2**. |
| `Your lock file does not contain a compatible set of packages` / `requires php >=8.4.1` | Version de PHP trop ancienne : sélectionner PHP 8.4 ou 8.5 dans Herd (`herd use 8.5`) puis relancer `composer install`. |
| Herd : `Database file at path … database.sqlite does not exist` | Créer le fichier : `touch database/database.sqlite` (PowerShell : `New-Item database/database.sqlite`), puis `php artisan migrate --seed`. |
| Herd : le site `punk-booking.test` ne répond pas | Le projet n'est pas dans le dossier des sites Herd : `herd link punk-booking` depuis le dossier du projet. |
| Page d'accueil : `Vite manifest not found` | Assets non compilés : `npm install && npm run build`. |
| `Swoole\Error: API must be called in the coroutine` | L'extension Swoole de l'image Sail déclare un `defer()` global. Toujours importer `use function Illuminate\Support\defer;`. |
| `/lieu` répond « Impossible d'ouvrir le formulaire » | `SLACK_BOT_TOKEN` absent ou invalide, scope `commands` manquant, ou réponse trop lente (le `trigger_id` expire en 3 s). Voir `storage/logs/laravel.log`. |
| Slack renvoie `dispatch_failed` / 401 | Mauvais `SLACK_SIGNING_SECRET`, URL du tunnel (`herd share` / `sail share`) périmée, ou horloge du serveur décalée de plus de 5 min. |
| Aucune notification dans le canal | `SLACK_BOOKING_WEBHOOK` vide, ou config en cache : `php artisan config:clear`. |
| Le récap de 9h ne part pas | Planificateur non lancé (`php artisan schedule:work` en local, cron en prod). |
