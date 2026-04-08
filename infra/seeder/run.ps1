# run.ps1 — Lance le seeder Photo-Pro
# Usage : .\infra\seeder\run.ps1  (depuis la racine du projet)

$ErrorActionPreference = "Stop"
$containerName = "photo-pro-api.stockage-1"

# 1. Vérifier que le conteneur tourne
$status = docker inspect --format "{{.State.Status}}" $containerName 2>$null
if ($status -ne "running") {
    Write-Host "ERREUR : le conteneur $containerName n'est pas démarré." -ForegroundColor Red
    Write-Host "Lancez d'abord : docker compose up -d" -ForegroundColor Yellow
    exit 1
}

# 2. Installer FakerPHP dans le conteneur (sans toucher au lock file du service)
Write-Host "Installation de FakerPHP..."
docker exec $containerName composer require --dev fakerphp/faker --no-interaction -q

# 3. Copier le script dans le conteneur
Write-Host "Copie du script..."
docker cp infra/seeder/seed.php "${containerName}:/tmp/seed.php"

# 4. Exécuter
Write-Host "Démarrage du seeder..."
Write-Host ""
docker exec $containerName php /tmp/seed.php
