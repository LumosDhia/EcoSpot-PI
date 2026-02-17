# EcoSpot — Documentation technique et suivi PIDEV

Ce document décrit l’architecture, les modules, les entités, les APIs et les liens avec le cahier des charges PIDEV (semaine du 09/02/2026).

---

## 1. Vue d’ensemble du projet

**EcoSpot** est une application web Symfony (PHP) qui associe :

- **Front Office** : page d’accueil, blog, événements, tickets publics, inscription/connexion.
- **Back Office Admin** : tableau de bord, gestion des utilisateurs, des articles/blog, des commentaires, des événements, des sponsors, des tickets en attente et des complétions.
- **Espace NGO** : tableau de bord, rédaction/édition d’articles, gestion des commentaires et des événements.

Les **5 modules** traités dans cette doc sont :

1. **Front / Utilisateur** (accueil, inscription, connexion, tableau de bord, rôles).
2. **Ticket** (création par l’utilisateur, liste publique, complétion, workflow admin).
3. **Événement** (événements + sponsors, liste/détail public, CRUD admin/NGO).
4. **Blog** (articles, commentaires, recherche/tri, API Guardian, workflow admin/NGO).

---

## 2. Conformité PIDEV (travail à faire pour la séance notée)

| Exigence | Réalisation dans EcoSpot |
|----------|---------------------------|
| **Template Front Office et Back Office avec liens fonctionnels** | Templates distincts : `base_front.html.twig` (front), `base_back.html.twig` (admin/NGO). Liens de menu selon le rôle (Admin, NGO, Dashboard). |
| **Entités + CRUD avec au moins une relation** | Entités : `User`, `Ticket`, `Event` (Evenement), `Sponsor`, `Article`, `Comment`. Toutes ont au moins une relation (OneToMany, ManyToOne, ManyToMany). CRUD complet pour articles, commentaires, événements, sponsors, utilisateurs admin ; NGO : articles et événements limités. |
| **Contrôles de saisie côté serveur uniquement** | Contraintes Symfony (Assert) sur les entités et formulaires (longueur, NotBlank, etc.). Pas de validation métier critique en HTML/JS uniquement. |
| **Fonctionnalités avancées (recherche, tri, API)** | **Blog** : recherche (`q`) et tri (`sort` ASC/DESC) sur les articles publiés. **Événements** : recherche par nom/description/lieu. **APIs** : Guardian (actualités), Nominatim (géocodage), Open-Meteo (météo), Turnstile (captcha). API interne : `/api/geocode` pour le géocodage. |
| **Intégration sur une machine avec GitHub** | Projet versionné (Git), prêt à être poussé sur un dépôt GitHub et cloné sur une seule machine pour démo. |

---

## 3. Module Front / Utilisateur

### 3.1 Rôle et origine

- **Fonction** : Point d’entrée du site (accueil), inscription, connexion, redirection selon le rôle (Admin → `/admin`, NGO → `/ngo`, User → dashboard), et gestion des comptes (côté admin).
- **Origine** : Nécessité d’avoir des utilisateurs identifiés (ROLE_USER), des admins (ROLE_ADMIN) et des NGOs (ROLE_NGO) avec des zones et des droits différents.

### 3.2 Tables et relations

**Table `user`**

| Colonne      | Type            | Description |
|-------------|-----------------|-------------|
| id          | INT (PK)        | Identifiant |
| email       | VARCHAR(180)    | Unique, identifiant de connexion |
| roles       | JSON            | Liste de rôles : ROLE_ADMIN, ROLE_NGO, ROLE_USER |
| password    | VARCHAR(255)    | Mot de passe haché |
| lastname    | VARCHAR(100)    | Nom |
| firstname   | VARCHAR(100)    | Prénom |
| address     | VARCHAR(255)    | Adresse (optionnel) |
| zipcode     | VARCHAR(5)      | Code postal (optionnel) |
| city        | VARCHAR(150)    | Ville (optionnel) |
| created_at  | DATETIME        | Date de création |

**Relations**

- **User** → **Ticket** : OneToMany (`user.tickets`). Un utilisateur a plusieurs tickets.
- **User** → **Article** : OneToMany en tant que `writer`. Un utilisateur (admin/NGO) peut être auteur de plusieurs articles.

Aucune autre table ne référence `user` en FK directe dans ce module ; les relations sont détaillées dans les modules Ticket et Blog.

### 3.3 APIs et services

- **Cloudflare Turnstile** (service `TurnstileVerifier`) : vérification captcha côté serveur lors de l’inscription/connexion (clés dans `.env` : `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`). Pas d’API métier interne ; appel HTTP vers Cloudflare.

### 3.4 Contrôle de saisie côté serveur

- Contraintes sur l’entité `User` et sur les formulaires (Registration, AdminUserCreateType) : email valide, longueurs, unicité de l’email (UniqueEntity).

### 3.5 Routes principales

- `/` : accueil (`home`).
- `/register` : inscription (`app_register`).
- `/login` : connexion (`app_login`).
- `/logout` : déconnexion (`app_logout`).
- `/dashboard` : tableau de bord (redirection Admin/NGO ou liste pour USER).
- `/admin` : tableau de bord admin.
- `/admin/users` : CRUD utilisateurs (admin).

---

## 4. Module Ticket

### 4.1 Rôle et origine

- **Fonction** : Permettre aux utilisateurs (ROLE_USER) de proposer des “tickets” (actions écologiques à réaliser), de les voir en liste publique, de soumettre une preuve de complétion (message + image). L’admin peut publier, refuser, renvoyer pour modification, et valider les complétions (achieve).
- **Origine** : Module “actions écologiques” avec un workflow : brouillon → en attente → publié ; puis complétion par l’utilisateur → validation admin.

### 4.2 Tables et relations

**Table `ticket`**

| Colonne               | Type            | Description |
|-----------------------|-----------------|-------------|
| id                    | INT (PK)        | Identifiant |
| title                 | VARCHAR(255)    | Titre |
| description           | TEXT            | Description |
| location              | VARCHAR(500)    | Lieu |
| latitude / longitude  | FLOAT (nullable)| Coordonnées (optionnel) |
| created_at            | DATETIME        | Création |
| updated_at            | DATETIME        | Dernière modification |
| status                | ENUM            | PENDING, PUBLISHED, REFUSED, SENT_BACK, etc. |
| priority              | ENUM            | Priorité |
| domain                | ENUM            | Domaine d’action (ActionDomain) |
| user_id               | INT (FK)        | Propriétaire du ticket |
| admin_notes           | TEXT            | Note admin (refus / renvoi) |
| completed_by_id       | INT (FK, User)  | Utilisateur ayant soumis la complétion |
| completion_message    | TEXT            | Message de complétion |
| completion_image      | VARCHAR(255)    | Image de preuve |
| completion_submitted_at | DATETIME      | Date de soumission de la preuve |
| achieved_at           | DATETIME        | Date de validation par l’admin |

**Relations**

- **Ticket** → **User** : ManyToOne (`user_id`). Chaque ticket appartient à un utilisateur.
- **Ticket** → **User** (completedBy) : ManyToOne. Utilisateur ayant soumis la complétion (peut être différent du créateur si règles métier le permettent).

Le module Ticket est lié **uniquement** à la table `user` (créateur et compléteur). Pas de relation avec Event, Article ou Comment.

### 4.3 APIs

- **Backend interne** : `/api/geocode` (GET) utilise un service de géocodage (ex. Nominatim / OpenStreetMap) pour obtenir latitude/longitude à partir d’une adresse, utilisée pour les tickets (et éventuellement les événements) dans les formulaires.
- **Externe** : Le service de géocodage (ex. `NominatimGeocodeService`) appelle l’API Nominatim (OpenStreetMap). Pas d’autre API externe spécifique au module Ticket dans le code fourni.

### 4.4 Contrôles de saisie côté serveur

- Contraintes sur l’entité `Ticket` : titre (longueur min), description (longueur min), lieu, priority et domain requis (enums). Validation dans les formulaires de création/édition et de complétion.

### 4.5 Routes principales

- `/tickets` : liste publique des tickets (`public_tickets`).
- `/tickets/{id}` : détail d’un ticket (`public_ticket_show`).
- `/tickets/{id}/complete` : soumission de la complétion (preuve) (`public_ticket_complete`).
- `/achievements` : page des réalisations (`public_achievements`).
- `/dashboard/tickets` : liste des tickets de l’utilisateur connecté (`ticket_my_list`), création, édition, détail.
- `/admin/pending-tickets` : tickets en attente (publier, refuser, renvoyer).
- `/admin/completions` : liste des complétions à valider (achieve).

---

## 5. Module Événement

### 5.1 Rôle et origine

- **Fonction** : Gérer des événements (nom, description, capacité, lieu, dates, image, coordonnées GPS) et leurs sponsors. Affichage public (liste + détail) ; CRUD complet côté admin ; création/édition limitée côté NGO.
- **Origine** : Besoin d’un module “événements” avec une relation many-to-many avec les sponsors (un événement peut avoir plusieurs sponsors, un sponsor plusieurs événements).

### 5.2 Tables et relations

**Table `event` (entité Evenement)**

| Colonne      | Type           | Description |
|-------------|----------------|-------------|
| id          | INT (PK)       | Identifiant |
| name        | VARCHAR(255)   | Nom unique |
| description | TEXT           | Description |
| capacity    | INT            | Capacité |
| location    | VARCHAR(255)   | Lieu |
| started_at  | DATETIME       | Date/heure de début |
| ended_at    | DATETIME       | Date/heure de fin |
| image       | VARCHAR(255)   | Image (optionnel) |
| latitude    | FLOAT (nullable)| Latitude |
| longitude   | FLOAT (nullable)| Longitude |

**Table `sponsor`**

| Colonne      | Type         | Description |
|-------------|--------------|-------------|
| id          | INT (PK)     | Identifiant |
| name        | VARCHAR(255) | Nom |
| image       | VARCHAR(255) | Image (optionnel) |
| description | TEXT         | Description |
| sector      | VARCHAR(150) | Secteur |
| location    | VARCHAR(150) | Localisation |

**Table de jointure `event_sponsor`**

| Colonne    | Type | Description |
|------------|------|-------------|
| event_id   | INT  | FK → event.id |
| sponsor_id | INT  | FK → sponsor.id |

**Relations**

- **Evenement** ↔ **Sponsor** : ManyToMany via `event_sponsor`. Un événement a plusieurs sponsors ; un sponsor est lié à plusieurs événements.
- Aucune relation directe avec `user`, `ticket` ou `article` : le module Événement est autonome côté données, à part la table `sponsor` partagée avec la gestion des événements.

### 5.3 APIs

- **Backend** : `/api/geocode` pour remplir latitude/longitude à partir du lieu (même service que pour les tickets).
- **Externe** : Service de géocodage (ex. Nominatim). Optionnellement, un service météo (ex. Open-Meteo) peut être utilisé pour afficher la météo sur la page d’un événement ; à vérifier dans les templates ou contrôleurs qui injectent le service météo.

### 5.4 Contrôles de saisie côté serveur

- Contraintes sur `Evenement` : nom (min length), description (min length), capacité (positive), lieu, dates (début/fin, fin > début). Sur `Sponsor` : nom, description (min length), secteur, localisation. Validation dans les formulaires EvenementType et SponsorType.

### 5.5 Routes principales

- `/events` : liste des événements avec recherche (`events_index`).
- `/events/{id}` : détail d’un événement (`events_show`).
- `/admin/events` : CRUD événements (admin).
- `/admin/sponsors` : CRUD sponsors (admin).
- `/ngo/events` : liste et création/édition événements (NGO).

---

## 6. Module Blog

### 6.1 Rôle et origine

- **Fonction** : Articles publiés (rédigés par Admin ou NGO), affichage public avec recherche et tri (date de publication), commentaires sous les articles. Workflow : brouillon → publication par admin ou NGO ; admin peut renvoyer un article en révision (return for revision) avec une note.
- **Origine** : Module “blog” pour la communication (actualités, environnement) avec modération des commentaires (flag) et intégration d’une API d’actualités (Guardian).

### 6.2 Tables et relations

**Table `article`**

| Colonne            | Type         | Description |
|--------------------|--------------|-------------|
| id                 | INT (PK)     | Identifiant |
| title              | VARCHAR(255) | Titre |
| content            | TEXT         | Contenu |
| image              | VARCHAR(255) | Image (optionnel) |
| created_at         | DATETIME     | Création |
| published_at       | DATETIME     | Date de publication (null = brouillon) |
| admin_revision_note| TEXT         | Note admin pour retour en révision |
| writer_id          | INT (FK)     | Auteur (User) |

**Table `comment`**

| Colonne       | Type      | Description |
|---------------|-----------|-------------|
| id            | INT (PK)  | Identifiant |
| author        | VARCHAR(100) | Nom affiché de l’auteur |
| content       | TEXT      | Contenu du commentaire |
| created_at    | DATETIME  | Date de création |
| article_id    | INT (FK)  | Article concerné |
| author_user_id| INT (FK)  | Utilisateur connecté (optionnel) |
| flagged       | BOOLEAN   | Signalé par NGO/admin |

**Relations**

- **Article** → **User** (writer) : ManyToOne. Un article a un auteur (admin ou NGO).
- **Article** → **Comment** : OneToMany. Un article a plusieurs commentaires.
- **Comment** → **Article** : ManyToOne. Chaque commentaire est lié à un article.
- **Comment** → **User** (authorUser) : ManyToOne optionnel. Lien vers le compte si l’auteur est connecté.

Le module Blog est donc relié à la table `user` (writer, authorUser) et aux tables `article` et `comment` uniquement.

### 6.3 APIs

- **Externe – The Guardian** : Service `GuardianApiService` (clé `GUARDIAN_API_KEY` dans `.env`). Appel à l’API Guardian pour récupérer des actualités (environnement / société), affichées côté blog (liste ou encart “actualités”).
- **Backend** : Pas d’API REST interne dédiée au blog ; tout passe par les contrôleurs et formulaires. Upload d’images pour l’éditeur d’articles : route `/blog/editor-upload` (réservée ADMIN/NGO).

### 6.4 Contrôles de saisie côté serveur

- **Article** : titre (longueur min/max), contenu (longueur min). **Comment** : author (longueur), content (longueur min). Validation dans ArticleType, CommentType, CommentPublicType.

### 6.5 Fonctionnalités avancées

- **Recherche** : paramètre `q` sur la liste des articles publiés (recherche dans le titre).
- **Tri** : paramètre `sort` (ASC ou DESC) sur la date de publication (`publishedAt`).
- Implémentation dans `ArticleRepository::findPublishedBySearchAndOrder()`.

### 6.6 Routes principales

- `/blog` : liste des articles publiés (recherche + tri) (`blog_index`).
- `/blog/{id}` : détail d’un article + formulaire de commentaire (`blog_show`).
- `/admin/blog/article` : CRUD articles, publication, retour en révision (admin).
- `/admin/blog/comment` : CRUD commentaires (admin).
- `/ngo/articles` : liste, création, édition, publication (NGO).
- `/ngo/comments` : liste des commentaires, flag (NGO).
- `/blog/editor-upload` : upload d’image pour l’éditeur (Admin/NGO).

---

## 7. Synthèse des relations entre tables

```
user
  ├── 1:N ticket (user_id)
  ├── 1:N article (writer_id)
  └── 1:N comment (author_user_id, optionnel)

ticket
  └── N:1 user (user_id, completed_by_id)

event (evenement)
  └── N:N sponsor via event_sponsor

sponsor
  └── N:N event via event_sponsor

article
  ├── N:1 user (writer_id)
  └── 1:N comment (article_id)

comment
  ├── N:1 article (article_id)
  └── N:1 user (author_user_id, optionnel)
```

Aucune relation directe entre Ticket, Event/Sponsor et Article/Comment : les modules sont reliés uniquement via `user`.

---

## 8. APIs externes et internes

| API / Service        | Type    | Usage principal                    | Module(s)      |
|----------------------|--------|-------------------------------------|----------------|
| Guardian (content)   | Externe| Actualités pour le blog             | Blog           |
| Nominatim (OSM)      | Externe| Géocodage adresse → lat/long        | Ticket, Event  |
| Open-Meteo           | Externe| Météo (si utilisé dans les vues)   | Event / Front  |
| Cloudflare Turnstile | Externe| Captcha inscription/connexion       | Front / User  |
| GET /api/geocode     | Interne| Géocodage (appelle Nominatim)       | Ticket, Event  |

---

## 9. Templates et liens (PIDEV)

- **Front Office** : `base_front.html.twig` — liens vers accueil, blog, événements, tickets, connexion/inscription, dashboard selon rôle.
- **Back Office** : `base_back.html.twig` — menu Admin (dashboard, users, blog, events, sponsors, pending tickets, completions) ou NGO (dashboard, articles, comments, events) avec liens fonctionnels entre les pages.

---

## 10. Récapitulatif par module

| Module        | Tables concernées     | Relations avec d’autres tables | APIs (externe / interne)     | Avancé (recherche/tri/API) |
|---------------|------------------------|-------------------------------|------------------------------|----------------------------|
| Front / User  | user                  | —                             | Turnstile                    | —                          |
| Ticket        | ticket, user          | user uniquement               | Nominatim, /api/geocode      | —                          |
| Événement     | event, sponsor, event_sponsor | — (entre event et sponsor) | Nominatim, /api/geocode, (Open-Meteo) | Recherche par nom/description/lieu |
| Blog          | article, comment, user| user (writer, authorUser)     | Guardian, editor-upload      | Recherche (titre), tri (date) |

Ce document peut servir de base pour le suivi noté PIDEV et pour toute présentation technique du projet EcoSpot.
