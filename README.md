# Photo-Pro

## Setup

1. Creer les fichiers .env dans infra/env/ :
```bash
cp infra/env/auth-db.env.dist infra/env/auth-db.env
cp infra/env/gallery-db.env.dist infra/env/gallery-db.env
cp infra/env/notifications-db.env.dist infra/env/notifications-db.env
cp services/service-auth/app/config/.env.dist services/service-auth/app/config/.env
cp services/service-stockage/.env.dist services/service-stockage/.env
cp services/service-notifications/app/config/.env.dist services/service-notifications/app/config/.env

```
  (Éditez les fichiers .env pour configurer les mots de passe)

2. Creer le fichier s3.json dans infra/S3/ :
```bash
cp infra/S3/s3.json.dist infra/S3/s3.json
```
   (Éditez `infra/S3/s3.json` pour configurer vos clés S3 si nécessaire).

3. Demarrer la stack :
```bash
docker compose up -d
```

4. Charger les données de test :
```powershell
.\infra\seeder\run.ps1
```
Cela upload 32 photos dans S3 et crée 5 galeries pour 3 photographes.

> Les données persistent dans les volumes Docker. Il n'est pas nécessaire de relancer le seeder à chaque `docker compose up -d`.
> Pour repartir de zéro : `docker compose down -v && docker compose up -d && .\infra\seeder\run.ps1`

### Comptes de test

| Email | Mot de passe | Galeries |
|---|---|---|
| `mouadtaj@test.com` | `password123` | *Mariage Capucine & Pierre Mercier* (publique, publiée, 8 photos) · *Portraits - Nathalie Delattre* (privée, brouillon, code : `portrait-nathalie-2026`) |
| `sophie.laurent@test.com` | `password123` | *Paysages d'Alsace - Automne 2026* (publique, publiée, 7 photos) · *Séance - Susanne Lamy* (privée, brouillon, code : `seance-susanne-2026`) |
| `marc.dupont@test.com` | `password123` | *Architecture Strasbourg 2026* (publique, publiée, 6 photos) |

### Services disponibles
- Gateway Front : http://localhost:8080
- Gateway Back : http://localhost:8081
- Adminer : http://localhost:8090
- Mailpit : http://localhost:8025
- SeaweedFS : http://localhost:9333
- S3 : http://localhost:8333

## Structure
- services/ : Code source PHP
- infra/ : Config, SQL, S3, RabbitMQ
- build/ : Dockerfiles mutualisés

---

Ce projet contient des diagrammes collaboratifs versionnés avec Git (Diagrammer). 