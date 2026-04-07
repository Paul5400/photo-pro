# Rapport d'analyse — Photo-Pro

> Date initiale : 4 avril 2026 — **Mis à jour : 7 avril 2026**

---

## 1. Vue d'ensemble

**Photo-Pro** est une application de gestion de galeries photos construite en **architecture microservices** avec un pattern **hexagonal (ports & adapters)**. L'infrastructure repose sur deux API Gateways qui proxifient les requêtes vers cinq microservices, chacun disposant de sa propre base de données PostgreSQL. Un worker asynchrone consomme des événements RabbitMQ pour l'envoi d'emails.

### Schéma des services

| Service | Port externe | Rôle | État |
|---|---|---|---|
| `gateway.backoffice` | 8081 | Gateway photographe (Slim 4, Guzzle) | ✅ Fonctionnel |
| `gateway.frontoffice` | 8080 | Gateway visiteur (Slim 4, Guzzle) | ✅ Fonctionnel |
| `api.auth` | 8082 | Authentification JWT, gestion photographes | ✅ Complet |
| `api.gallery` | 8083 | Gestion galeries & photos | ✅ Complet |
| `api.stockage` | 8086 | Upload fichiers vers S3 (SeaweedFS) | ✅ Fonctionnel |
| `api.notifications` | 8084 | Consumer RabbitMQ + envoi emails | ✅ Fonctionnel |
| `worker.notifications` | — | Processus long blocking (consumer AMQP) | ✅ Actif |
| `rabbitmq` | 5672 / 15672 | Broker de messages | ✅ |
| `mailpit` | 1025 / 8025 | Serveur mail de développement | ✅ |
| `S3` | 8333 / 9333 | Stockage objet SeaweedFS | ✅ |
| `gallery.db` | 5433 | PostgreSQL 17 | ✅ |
| `auth.db` | 5435 | PostgreSQL 17 | ✅ |
| `notifications.db` | 5436 | PostgreSQL 17 | ✅ |

---

## 2. Stack technique

| Couche | Technologie | Version |
|---|---|---|
| Langage | PHP | 8.4 |
| Framework HTTP | Slim | 4.14 |
| Injection de dépendances | PHP-DI | 7.0 |
| JWT | Firebase PHP-JWT | 7.0 |
| Client HTTP (gateway) | Guzzle | 7.8 |
| Accès base de données | PDO (requêtes SQL manuelles) | — |
| Base de données | PostgreSQL | 17 |
| Broker de messages | RabbitMQ | 4 |
| Client AMQP | php-amqplib | 3.7 |
| Envoi d'emails | Symfony Mailer | 7.0 |
| Stockage fichiers | SeaweedFS (compatible S3) | 3.77 |
| Logs | Monolog | 3.7 |
| Validation | Respect Validation | 2.3.7 |
| Documentation API | Swagger PHP | 4.11 |
| Tests unitaires | PHPUnit | 11 |

---

## 3. Architecture par service

### 3.1 service-auth

Gère l'inscription et la connexion des photographes, ainsi que l'authentification rapide des visiteurs.

**Routes disponibles :**
- `POST /auth/register` — Inscription
- `POST /auth/login/photographe` — Connexion photographe (retourne JWT)
- `POST /auth/login/visiteur` — Connexion visiteur
- `POST /auth/refresh` — Renouvellement du token
- `GET /photographes` — Liste des photographes (protégée)
- `GET /photographes/{id}` — Détail d'un photographe (protégée)

**Structure :** Architecture hexagonale complète — entités `Photographe`, ports `PhotographeRepositoryInterface`, usecases `PhotographeService` et `AuthnService`, adapter `PDOPhotographeRepository`.

**Schéma DB :**
```
photographe (id, nom, pseudo UNIQUE, email, password, telephone, description, created_at)
```

**État :** Entièrement fonctionnel. Middlewares `AuthnMiddleware` et `AuthzPhotographeMiddleware` correctement chaînés.

---

### 3.2 service-galeries

Gestion du cycle de vie des galeries (création, publication, ajout de photos, commentaires) avec émission d'événements vers RabbitMQ.

**Routes disponibles :**
- `GET /galeries` — Lister les galeries du photographe connecté (protégée)
- `GET /galeries/{id}` — Afficher une galerie publiée avec ses photos (publique ; `?code_acces=XX` pour galeries privées)
- `POST /galeries` — Créer une galerie (protégée)
- `PATCH /galeries/{id}/photos` — Ajouter une photo (protégée, ownership vérifié)
- `DELETE /galeries/{id}/photos/{photoId}` — Retirer une photo (protégée, ownership vérifié)
- `GET /galeries/{id}/preview` — Aperçu complet de la galerie (protégée)
- `POST /galeries/{id}/publish` — Publier (protégée, émet `gallery.published`)
- `POST /galeries/{id}/unpublish` — Dépublier (protégée, émet `gallery.unpublished`)
- `POST /galeries/{id}/photos/{photoId}/commentaires` — Ajouter un commentaire (publique)

**Structure :** Architecture hexagonale — entités `Galerie`, `GaleriePhoto`, `GaleriePrivee`, ports `GalerieRepositoryInterface` et `GalerieEventPublisherInterface`, usecases `GalerieService`, `PublishGalerieUseCase`, `UnpublishGalerieUseCase`, `PreviewGalerieUseCase`, `AjouterCommentaireUseCase`, adapter `PdoRepositorieGalerie` et `RabbitMQPublisher`.

**Schéma DB :**
```
galerie            (id, titre, description, type, mode_mise_en_page, statut, created_at, published_at, photographe_id, photo_couverture_id)
galerie_photo      (galerie_id, photo_id, ordre, added_at) — clé composite
galerie_privee     (id, galerie_id UNIQUE, nom_client, email_client, telephone_client, code_acces UNIQUE, url_acces UNIQUE)
photo_commentaire  (id, galerie_id, photo_id, auteur, contenu, created_at)
```

**Publication d'événements AMQP :**
- Exchange : `photopro.events` (topic)
- Routing keys : `gallery.published`, `gallery.unpublished`
- Payload : `galerie_id`, `galerie_titre`, `client_email`, `url_acces`, `code_acces`, `date_event`
- Condition : l'événement n'est émis que si la galerie est privée (présence d'un `email_client`)

**Sécurité :**
- `AuthMiddleware` décode le JWT HS256 avec `firebase/php-jwt`, extrait `sub` comme `user_id`
- Toutes les routes mutantes récupèrent `user_id` depuis l'attribut de requête (non falsifiable)
- `addPhotoToGalerie` et `deletePhotoFromGalerie` vérifient l'ownership en base avant d'agir
- Route commentaire : vérifie que la galerie est publiée ; si privée, valide le `code_acces`

**État :** Complet et fonctionnel.

---

### 3.3 service-stockage

Gère l'upload de fichiers vers SeaweedFS (compatible S3) et génère des URLs présignées.

**Routes disponibles :**
- `POST /users/{id}/photos` — Upload d'une photo (JWT requis)
- `GET /` — Health check

**Fonctionnement :**
1. Middleware valide le JWT (signature réelle vérifiée)
2. Fichier uploadé dans `users/{userId}/{filename}` sur S3
3. URL présignée retournée (expiration : 60 minutes)

Deux clients S3 configurés : interne (`http://s3:8333`) pour l'upload, externe (`http://localhost:8333`) pour les URLs présignées accessibles depuis le navigateur.

**État :** Fonctionnel.

---

### 3.4 service-notifications

Consomme les événements RabbitMQ et envoie des emails HTML via Symfony Mailer.

**Architecture du consumer :**
```
worker.notifications
  └── app/consumer/run.php
        └── RabbitMQConsumer (processus bloquant)
              └── NotificationHandlerInterface
                    └── EnvoyerNotificationUseCase
                          └── SymfonyMailerAdapter
```

**Routing keys écoutées :**
- `gallery.published` → 2 emails (lien d'accès + code d'accès)
- `gallery.unpublished` → 1 email (galerie indisponible)
- `gallery.modified` → 1 email (mise à jour)

**Garanties de livraison :**
- Mode ACK : message acquitté uniquement si traitement réussi
- Dead-letter exchange `photopro.events.dlx` configuré
- Prefetch count = 1 (traitement séquentiel)
- `restart: unless-stopped` sur le worker

**Schéma DB :**
```
notification (id, galerie_privee_id, type_evenement, envoyee_at, succes)
```

**État :** Fonctionnel. Les emails sont visibles dans Mailpit (http://localhost:8025).

---

### 3.5 api-gateway-backoffice

Reverse proxy vers les services auth, gallery et stockage, avec validation complète du JWT.

**Routes protégées (Bearer token requis) :**
- Auth : register, login, refresh
- Galeries : list, get, create, add/delete photo, preview, publish, unpublish
- Stockage : upload, delete

**Sécurité :** `JwtAuthMiddleware` décode le token avec `firebase/php-jwt` (HS256). Un token expiré ou à signature invalide retourne immédiatement **401** sans atteindre les services en aval. L'attribut `user_id` extrait du claim `sub` est propagé vers les services.

**Fonctionnement :** `ProxyAction` sélectionne le client Guzzle approprié selon le préfixe de route, puis transmet la requête au service cible en préservant les headers, le body et les query parameters.

**État :** Fonctionnel. JWT validé cryptographiquement.

---

### 3.6 api-gateway-frontoffice

Gateway publique accessible sans authentification pour les visiteurs.

**Routes disponibles :**
- `POST /auth/login/visiteur`
- `GET /galeries` — Liste des galeries publiées
- `GET /galeries/{id}` — Détail d'une galerie publiée (transmet `?code_acces` si fourni)
- `POST /galeries/{id}/photos/{photoId}/commentaires` — Ajouter un commentaire

**État :** Fonctionnel.

---

## 4. Ce qui fonctionne

| Fonctionnalité | Route | Vérifié |
|---|---|---|
| Inscription photographe | `POST /auth/register` | ✅ |
| Connexion photographe + JWT | `POST /auth/login/photographe` | ✅ |
| Liste des galeries du photographe | `GET /galeries` | ✅ |
| Affichage galerie publiée | `GET /galeries/{id}` | ✅ |
| Accès galerie privée avec code | `GET /galeries/{id}?code_acces=XX` | ✅ |
| Création de galerie | `POST /galeries` | ✅ |
| Ajout de photo (ownership vérifié) | `PATCH /galeries/{id}/photos` | ✅ |
| Suppression de photo (ownership vérifié) | `DELETE /galeries/{id}/photos/{photoId}` | ✅ |
| Aperçu de galerie | `GET /galeries/{id}/preview` | ✅ |
| Publication (+ événement AMQP) | `POST /galeries/{id}/publish` | ✅ |
| Dépublication (+ événement AMQP) | `POST /galeries/{id}/unpublish` | ✅ |
| Email client à la publication | worker.notifications | ✅ |
| Upload photo vers S3 | `POST /stockage/upload` | ✅ |
| Ajout de commentaire (public) | `POST /galeries/{id}/photos/{photoId}/commentaires` | ✅ |
| Refus token absent ou forgé | gateway.backoffice | ✅ |
| Refus token expiré | gateway.backoffice + service-galeries | ✅ |

---

## 5. Problèmes résolus (sprint du 7 avril 2026)

| Problème initial | Correction apportée |
|---|---|
| `JwtAuthMiddleware` (backoffice) : format uniquement | Réécriture avec `Firebase\JWT\JWT::decode()` HS256 — signature + expiry validés |
| `AuthMiddleware` (service-galeries) : format uniquement | Même correction — extrait `sub` comme `user_id` |
| `user_id` issu du header `X-User-Id` (falsifiable) | Toutes les actions lisent `$request->getAttribute('user_id')` injecté par le middleware |
| Pas de vérification d'ownership sur add/delete photo | SQL vérifie `photographe_id = :user_id` avant INSERT/DELETE |
| Fonctionnalité commentaires absente | Implémentée : table `photo_commentaire`, `AjouterCommentaireUseCase`, `CommentaireAction`, route pubique |
| `GET /galeries` : placeholder | Implémenté avec `ListGaleriesAction` — retourne les galeries du photographe avec `nb_photos` |
| `GET /galeries/{id}` : placeholder | Implémenté avec `GetGalerieAction` — galerie publiée + photos ; code d'accès pour galeries privées |
| `JWT_SECRET` absent du docker-compose des services concernés | Ajouté dans `api.gallery` et `gateway.backoffice` |

---

## 6. Flux métiers — état d'implémentation

| Flux | Diagramme | État |
|---|---|---|
| Créer une galerie | `create-gallery-3cd1c0.mmd` | ✅ Fonctionnel |
| Uploader une photo | `upload-18e06b.mmd` | ✅ Fonctionnel |
| Ajouter une photo à une galerie | `add-photo-939f18.mmd` | ✅ Fonctionnel |
| Publier une galerie + notifier | `publish-725680.mmd` | ✅ Fonctionnel |
| Accès visiteur galerie privée | `private-access-bab02d.mmd` | ✅ Fonctionnel |
| Commenter une photo | `comment-78de41.mmd` | ✅ Fonctionnel |

---

## 7. Points restants

| Priorité | Sujet |
|---|---|
| 🟡 | Aucun test unitaire sur `AjouterCommentaireUseCase`, `ListGaleriesAction`, `GetGalerieAction` |
| 🟡 | `service-auth` : aucun test unitaire |
| 🟡 | `UploadAction` : pas de validation type MIME ni taille fichier |
| 🟢 | Timeout Guzzle (5 s) insuffisant pour uploads volumineux |
| 🟢 | Dossier `photo-pro/app/` : résidu d'un projet antérieur, sans rôle dans l'application |

---

## 8. Évaluation globale

| Critère | Score initial | Score actuel |
|---|---|---|
| Organisation du code | 8 / 10 | 8 / 10 |
| Complétude des fonctionnalités | 6 / 10 | **9 / 10** |
| Sécurité | 5 / 10 | **8 / 10** |
| Cohérence diagrammes ↔ code | 6 / 10 | **10 / 10** |
| Couverture de tests | 3 / 10 | 3 / 10 |

**Résumé :** Tous les flux métiers documentés dans les diagrammes sont désormais implémentés et fonctionnels. La validation JWT est cohérente sur l'ensemble des points d'entrée (gateway backoffice et service-galeries). Le contrôle d'ownership protège les opérations mutantes. La principale lacune restante est la couverture de tests unitaires.


---

## 1. Vue d'ensemble

**Photo-Pro** est une application de gestion de galeries photos construite en **architecture microservices** avec un pattern **hexagonal (ports & adapters)**. L'infrastructure repose sur deux API Gateways qui proxifient les requêtes vers cinq microservices, chacun disposant de sa propre base de données PostgreSQL. Un worker asynchrone consomme des événements RabbitMQ pour l'envoi d'emails.

### Schéma des services

| Service | Port externe | Rôle | État |
|---|---|---|---|
| `gateway.backoffice` | 8081 | Gateway photographe (Slim 4, Guzzle) | ✅ Fonctionnel |
| `gateway.frontoffice` | 8080 | Gateway visiteur (Slim 4, Guzzle) | ⚠️ Incomplet |
| `api.auth` | 8082 | Authentification JWT, gestion photographes | ✅ Complet |
| `api.gallery` | 8083 | Gestion galeries & photos | ⚠️ Partiel |
| `api.stockage` | 8086 | Upload fichiers vers S3 (SeaweedFS) | ✅ Fonctionnel |
| `api.notifications` | 8084 | Consumer RabbitMQ + envoi emails | ✅ Fonctionnel |
| `worker.notifications` | — | Processus long blocking (consumer AMQP) | ✅ Actif |
| `rabbitmq` | 5672 / 15672 | Broker de messages | ✅ |
| `mailpit` | 1025 / 8025 | Serveur mail de développement | ✅ |
| `S3` | 8333 / 9333 | Stockage objet SeaweedFS | ✅ |
| `gallery.db` | 5433 | PostgreSQL 17 | ✅ |
| `auth.db` | 5435 | PostgreSQL 17 | ✅ |
| `notifications.db` | 5436 | PostgreSQL 17 | ✅ |

---

## 2. Stack technique

| Couche | Technologie | Version |
|---|---|---|
| Langage | PHP | 8.4 |
| Framework HTTP | Slim | 4.14 |
| Injection de dépendances | PHP-DI | 7.0 |
| JWT | Firebase PHP-JWT | 7.0 |
| Client HTTP (gateway) | Guzzle | 7.8 |
| Accès base de données | PDO (requêtes SQL manuelles) | — |
| Base de données | PostgreSQL | 17 |
| Broker de messages | RabbitMQ | 4 |
| Client AMQP | php-amqplib | 3.7 |
| Envoi d'emails | Symfony Mailer | 7.0 |
| Stockage fichiers | SeaweedFS (compatible S3) | 3.77 |
| Logs | Monolog | 3.7 |
| Validation | Respect Validation | 2.3.7 |
| Documentation API | Swagger PHP | 4.11 |
| Tests unitaires | PHPUnit | 11 |

---

## 3. Architecture par service

### 3.1 service-auth

Gère l'inscription et la connexion des photographes, ainsi que l'authentification rapide des visiteurs.

**Routes disponibles :**
- `POST /auth/register` — Inscription
- `POST /auth/login/photographe` — Connexion photographe (retourne JWT)
- `POST /auth/login/visiteur` — Connexion visiteur
- `POST /auth/refresh` — Renouvellement du token
- `GET /photographes` — Liste des photographes (protégée)
- `GET /photographes/{id}` — Détail d'un photographe (protégée)

**Structure :** Architecture hexagonale complète — entités `Photographe`, ports `PhotographeRepositoryInterface`, usecases `PhotographeService` et `AuthnService`, adapter `PDOPhotographeRepository`.

**Schéma DB :**
```
photographe (id, nom, pseudo UNIQUE, email, password, telephone, description, created_at)
```

**État :** Entièrement fonctionnel. Middlewares `AuthnMiddleware` et `AuthzPhotographeMiddleware` correctement chaînés.

---

### 3.2 service-galeries

Gestion du cycle de vie des galeries (création, publication, ajout de photos) avec émission d'événements vers RabbitMQ.

**Routes disponibles :**
- `POST /galeries` — Créer une galerie (protégée)
- `PATCH /galeries/{id}/photos` — Ajouter une photo (protégée)
- `DELETE /galeries/{id}/photos/{photoId}` — Retirer une photo (protégée)
- `GET /galeries/{id}/preview` — Aperçu de la galerie (protégée)
- `POST /galeries/{id}/publish` — Publier (protégée, émet `gallery.published`)
- `POST /galeries/{id}/unpublish` — Dépublier (protégée, émet `gallery.unpublished`)

**Structure :** Architecture hexagonale — entités `Galerie`, `GaleriePhoto`, `GaleriePrivee`, port `GalerieRepositoryInterface` et `GalerieEventPublisherInterface`, adapter `PdoRepositorieGalerie` et `RabbitMQPublisher`.

**Schéma DB :**
```
galerie         (id, titre, description, type, mode_mise_en_page, statut, created_at, published_at, photographe_id, photo_couverture_id)
galerie_photo   (galerie_id, photo_id, ordre, added_at) — clé composite
galerie_privee  (id, galerie_id UNIQUE, nom_client, email_client, telephone_client, code_acces UNIQUE, url_acces UNIQUE)
```

**Publication d'événements AMQP :**
- Exchange : `photopro.events` (topic)
- Routing keys : `gallery.published`, `gallery.unpublished`
- Payload : `galerie_id`, `galerie_titre`, `client_email`, `url_acces`, `code_acces`, `date_event`
- Condition : l'événement n'est émis que si la galerie est privée (présence d'un `email_client`)

**État :** Fonctionnel sur les routes existantes. Fonctionnalité commentaires absente (voir section 5).

---

### 3.3 service-stockage

Gère l'upload de fichiers vers SeaweedFS (compatible S3) et génère des URLs présignées.

**Routes disponibles :**
- `POST /users/{id}/photos` — Upload d'une photo (JWT requis)
- `GET /` — Health check

**Fonctionnement :**
1. Middleware valide le JWT (signature réelle vérifiée)
2. Fichier uploadé dans `users/{userId}/{filename}` sur S3
3. URL présignée retournée (expiration : 60 minutes)

Deux clients S3 configurés : interne (`http://s3:8333`) pour l'upload, externe (`http://localhost:8333`) pour les URLs présignées accessibles depuis le navigateur.

**État :** Fonctionnel.

---

### 3.4 service-notifications

Consomme les événements RabbitMQ et envoie des emails HTML via Symfony Mailer.

**Architecture du consumer :**
```
worker.notifications
  └── app/consumer/run.php
        └── RabbitMQConsumer (processus bloquant)
              └── NotificationHandlerInterface
                    └── EnvoyerNotificationUseCase
                          └── SymfonyMailerAdapter
```

**Routing keys écoutées :**
- `gallery.published` → 2 emails (lien d'accès + code d'accès)
- `gallery.unpublished` → 1 email (galerie indisponible)
- `gallery.modified` → 1 email (mise à jour)

**Garanties de livraison :**
- Mode ACK : message acquitté uniquement si traitement réussi
- Dead-letter exchange `photopro.events.dlx` configuré
- Prefetch count = 1 (traitement séquentiel)
- `restart: unless-stopped` sur le worker

**Schéma DB :**
```
notification (id, galerie_privee_id, type_evenement, envoyee_at, succes)
```

**État :** Fonctionnel. Les emails sont visibles dans Mailpit (http://localhost:8025).

---

### 3.5 api-gateway-backoffice

Reverse proxy vers les services auth, gallery et stockage, avec vérification basique du token JWT.

**Routes protégées (Bearer token requis) :**
- Auth : register, login, refresh
- Galeries : CRUD, preview, publish, unpublish
- Stockage : upload, delete

**Fonctionnement :** `ProxyAction` reçoit la requête, sélectionne le client Guzzle approprié selon le préfixe de route, puis transmet la requête au service cible en préservant les headers, le body et les query parameters.

**État :** Fonctionnel en tant que proxy. Voir section 5.2 pour les limitations de sécurité.

---

### 3.6 api-gateway-frontoffice

Gateway publique accessible sans authentification pour les visiteurs.

**Routes disponibles :**
- `POST /auth/login/visiteur`
- `GET /galeries`, `GET /galeries/{id}`
- `POST /galeries/{id}/photos/{photoId}/commentaires` ← **NON IMPLÉMENTÉ**

**État :** Partiellement fonctionnel. La route des commentaires est déclarée mais non implémentée côté service-galeries.

---

## 4. Ce qui fonctionne

| Fonctionnalité | Route | Vérifié |
|---|---|---|
| Inscription photographe | `POST /auth/register` | ✅ |
| Connexion photographe + JWT | `POST /auth/login/photographe` | ✅ |
| Création de galerie | `POST /galeries` | ✅ |
| Ajout / suppression de photo | `PATCH/DELETE /galeries/{id}/photos` | ✅ |
| Aperçu de galerie | `GET /galeries/{id}/preview` | ✅ |
| Publication (+ événement AMQP) | `POST /galeries/{id}/publish` | ✅ |
| Dépublication (+ événement AMQP) | `POST /galeries/{id}/unpublish` | ✅ |
| Email client à la publication | worker.notifications | ✅ |
| Upload photo vers S3 | `POST /stockage/upload` | ✅ |
| Refus sans token | — | ✅ |

---

## 5. Problèmes identifiés

### 5.1 Fonctionnalité commentaires — critique 🔴

La route `POST /galeries/{id}/photos/{photoId}/commentaires` est définie dans les deux gateways et documentée dans le diagramme `comment-78de41.mmd`, mais :

- Aucune classe `CommentAction` dans `service-galeries`
- Aucune table `commentaire` dans `gallery.schema.sql`
- Aucun port, use case ou DTO pour les commentaires
- La gateway frontoffice retournera une erreur 404 ou 502 si appelée

**Impact :** Le backoffice visiteur est non fonctionnel sur cette feature.

---

### 5.2 Validation JWT incohérente — élevé 🟠

| Composant | Comportement |
|---|---|
| `gateway.backoffice` `JwtAuthMiddleware` | Vérifie uniquement le format `Bearer xxx`, pas la signature |
| `service-galeries` `AuthMiddleware` | Idem — format uniquement |
| `service-stockage` `JwtMiddleware` | ✅ Valide la signature avec la clé secrète |

Un token JWT forgé (signature invalide) passe librement à travers la gateway et le service galeries. Seul le service stockage le rejette correctement.

---

### 5.3 Extraction de l'identité utilisateur — élevé 🟠

`AuthMiddleware` dans `service-galeries` ne décode pas le JWT et n'en extrait pas l'`user_id`. L'identité de l'utilisateur repose sur le header `X-User-Id` transmis par le client (ou la gateway), ce qui est falsifiable.

Les vérifications de propriété en SQL (`WHERE photographe_id = :user_id`) fonctionnent correctement mais dépendent d'une entrée non validée côté serveur.

---

### 5.4 Vérification du code d'accès pour les galeries privées — moyen 🟡

`PreviewGalerieAction` ne vérifie pas le `code_acces` du visiteur. N'importe qui connaissant l'UUID de la galerie peut en voir le contenu.

---

### 5.5 Absence de validation des uploads — moyen🟡

`UploadAction` dans `service-stockage` n'effectue aucun contrôle sur :
- La taille du fichier
- Le type MIME
- Le format réel du fichier

---

### 5.6 Timeout Guzzle trop court — faible 🟢

Les clients Guzzle dans les gateways ont un timeout fixé à 5 secondes. Insuffisant pour les uploads de fichiers volumineux.

---

### 5.7 Dossier `photo-pro/app/` — résidu 🟢

Le dossier `photo-pro/app/` contient les traces d'un projet antérieur (application médicale **ToubiLib**). Il n'est pas intégré à Docker Compose et n'a aucun rôle dans l'application actuelle. Il s'agit vraisemblablement d'un template réutilisé.

---

## 6. Flux métiers — état d'implémentation

| Flux | Diagramme | État |
|---|---|---|
| Créer une galerie | `create-gallery-3cd1c0.mmd` | ✅ Fonctionnel |
| Uploader une photo | `upload-18e06b.mmd` | ✅ Fonctionnel |
| Ajouter une photo à une galerie | `add-photo-939f18.mmd` | ✅ Fonctionnel |
| Publier une galerie + notifier | `publish-725680.mmd` | ✅ Fonctionnel |
| Accès visiteur galerie privée | `private-access-bab02d.mmd` | ⚠️ Partiel (pas de vérif. code) |
| Commenter une photo | `comment-78de41.mmd` | ❌ Non implémenté |

---

## 7. Évaluation globale

| Critère | Score |
|---|---|
| Organisation du code | 8 / 10 |
| Complétude des fonctionnalités | 6 / 10 |
| Sécurité | 5 / 10 |
| Cohérence diagrammes ↔ code | 6 / 10 |
| Couverture de tests | 3 / 10 |

**Résumé :** Le cœur de l'application (auth, galeries, stockage, notifications) est fonctionnel et bien structuré. L'architecture hexagonale est correctement appliquée dans l'ensemble des services. Les lacunes principales sont la **fonctionnalité commentaires** entièrement absente, des **vérifications JWT insuffisantes** sur plusieurs services, et l'absence de **contrôle d'ownership** côté applicatif.
