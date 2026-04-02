# Photo-Pro

## Setup

1. Creer les fichiers .env dans infra/env/ :
```bash
cp infra/env/auth-db.env.dist infra/env/auth-db.env
cp infra/env/gallery-db.env.dist infra/env/gallery-db.env
cp infra/env/notifications-db.env.dist infra/env/notifications-db.env
cp services/service-auth/app/config/.env.dist services/service-auth/app/config/.env

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