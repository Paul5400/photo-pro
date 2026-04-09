<?php
/**
 * Seeder Photo-Pro
 * Execution : .\infra\seeder\run.ps1
 *
 * Cree 3 photographes :
 *   - photographe 1 : 1 galerie publique publiee (8 photos) + 1 galerie privee non publiee (6 photos)
 *   - photographe 2 : 1 galerie publique publiee (7 photos) + 1 galerie privee non publiee (5 photos)
 *   - photographe 3 : 1 galerie publique publiee (6 photos)
 */

require '/var/php/vendor/autoload.php';

use Aws\S3\S3Client;
use Faker\Factory as Faker;
use Ramsey\Uuid\Uuid;

$bucket         = 'photo-pro';
$frontofficeUrl = 'http://localhost:8080';
$faker          = Faker::create('fr_FR');

// 3 photographes avec UUIDs fixes pour reproductibilite
$photographes = [
    [
        'id'       => '9b0b0764-36d5-41e7-a7bc-9615ee75f054',
        'nom'      => 'Tajeddine Mouad',
        'pseudo'   => 'mouadtaj',
        'email'    => 'mouadtaj@test.com',
        'password' => 'password123',
    ],
    [
        'id'       => 'a1b2c3d4-1111-2222-3333-444455556666',
        'nom'      => 'Sophie Laurent',
        'pseudo'   => 'sophielaurent',
        'email'    => 'sophie.laurent@test.com',
        'password' => 'password123',
    ],
    [
        'id'       => 'b2c3d4e5-aaaa-bbbb-cccc-ddddeeeefff0',
        'nom'      => 'Marc Dupont',
        'pseudo'   => 'marcdupont',
        'email'    => 'marc.dupont@test.com',
        'password' => 'password123',
    ],
];

echo "Connexion aux bases de donnees et S3...\n";

$s3 = new S3Client([
    'version'                 => 'latest',
    'region'                  => 'us-east-1',
    'endpoint'                => 'http://S3:8333',
    'use_path_style_endpoint' => true,
    'credentials'             => ['key' => 'ABCDEF', 'secret' => '123456'],
]);

$authDb = new PDO(
    'pgsql:host=auth.db;port=5432;dbname=auth_db',
    'photo_auth', 'secret',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stockageDb = new PDO(
    'pgsql:host=stockage.db;port=5432;dbname=stockage_db',
    'photo_stockage', 'secret',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$galleryDb = new PDO(
    'pgsql:host=gallery.db;port=5432;dbname=gallery_db',
    'photo_gallery', 'secret',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Nettoyage
echo "Nettoyage des donnees precedentes...\n";
$ids = array_map(fn($p) => "'{$p['id']}'", $photographes);
$inList = implode(',', $ids);

$galleryDb->exec("DELETE FROM photo_commentaire WHERE galerie_id IN (SELECT id FROM galerie WHERE photographe_id IN ({$inList}))");
$galleryDb->exec("DELETE FROM galerie_photo     WHERE galerie_id IN (SELECT id::uuid FROM galerie WHERE photographe_id IN ({$inList}))");
$galleryDb->exec("DELETE FROM galerie_privee    WHERE galerie_id IN (SELECT id FROM galerie WHERE photographe_id IN ({$inList}))");
$galleryDb->exec("DELETE FROM galerie           WHERE photographe_id IN ({$inList})");
$galleryDb->exec("DELETE FROM photo");
$stockageDb->exec("DELETE FROM photo WHERE photographe_id IN ({$inList})");
$authDb->exec("DELETE FROM photographe WHERE id IN ({$inList})");

// Insertion photographes dans auth_db
echo "Creation des photographes...\n";
$stmtAuth = $authDb->prepare(
    "INSERT INTO photographe (id, nom, pseudo, email, password) VALUES (:id, :nom, :pseudo, :email, :password) ON CONFLICT (id) DO NOTHING"
);
foreach ($photographes as $p) {
    $stmtAuth->execute([
        ':id'       => $p['id'],
        ':nom'      => $p['nom'],
        ':pseudo'   => $p['pseudo'],
        ':email'    => $p['email'],
        ':password' => password_hash($p['password'], PASSWORD_BCRYPT),
    ]);
    echo "  {$p['email']}\n";
}

// Definitions photos : [picsumId, w, h]
// 27 photos au total (8+6+7+5+6 = 32 mais on reutilise si besoin, on prend 32)
$photoDefs = [
    // photographe 1 - galerie publique (8 photos) indices 0-7
    [10, 1200, 800], [11, 1200, 800], [12, 800, 1200], [13, 1200, 800],
    [14, 800, 1200], [15, 1200, 800], [16, 1200, 800], [17, 800, 1200],
    // photographe 1 - galerie privee (6 photos) indices 8-13
    [20, 800, 1200], [21, 1200, 800], [22, 800, 1200],
    [23, 1200, 800], [24, 800, 1200], [25, 1200, 800],
    // photographe 2 - galerie publique (7 photos) indices 14-20
    [40, 1200, 800], [41, 1200, 800], [42, 800, 1200], [43, 1200, 800],
    [44, 800, 1200], [45, 1200, 800], [46, 800, 1200],
    // photographe 2 - galerie privee (5 photos) indices 21-25
    [50, 800, 1200], [51, 1200, 800], [52, 800, 1200], [53, 1200, 800], [54, 800, 1200],
    // photographe 3 - galerie publique (6 photos) indices 26-31
    [60, 1200, 800], [61, 800, 1200], [62, 1200, 800],
    [63, 800, 1200], [64, 1200, 800], [65, 800, 1200],
];

$total = count($photoDefs);
echo "Upload de {$total} photos dans S3...\n";

// Repartition par photographe
$uploadedPerPhotographe = [];
foreach ($photographes as $p) {
    $uploadedPerPhotographe[$p['id']] = [];
}

$stmtStockage = $stockageDb->prepare("
    INSERT INTO photo (id, titre, mime_type, taille_mo, nom_fichier_original, chemin_s3, photographe_id)
    VALUES (:id, :titre, :mime, :taille, :filename, :chemin, :photographeId)
    ON CONFLICT (id) DO NOTHING
");

// Indices par photographe
$ranges = [
    $photographes[0]['id'] => [0, 13],   // indices 0-13 (14 photos : 8 publique + 6 privee)
    $photographes[1]['id'] => [14, 25],  // indices 14-25 (12 photos : 7 publique + 5 privee)
    $photographes[2]['id'] => [26, 31],  // indices 26-31 (6 photos : 6 publique)
];

$allUploaded = [];
foreach ($photoDefs as $i => [$picsumId, $w, $h]) {
    // Determiner quel photographe possede cette photo
    $ownerPhotographeId = null;
    foreach ($ranges as $pid => [$start, $end]) {
        if ($i >= $start && $i <= $end) {
            $ownerPhotographeId = $pid;
            break;
        }
    }

    $uuid  = Uuid::uuid4()->toString();
    $s3Key = "users/{$ownerPhotographeId}/{$uuid}-photo.jpg";
    $titre = $faker->sentence(3, false);
    $n = $i + 1;
    echo "  ({$n}/{$total}) {$titre}\n";

    $imgData = @file_get_contents("https://picsum.photos/id/{$picsumId}/{$w}/{$h}");
    if ($imgData === false) {
        $imgData = file_get_contents("https://picsum.photos/{$w}/{$h}?random={$picsumId}");
    }

    $s3->putObject(['Bucket' => $bucket, 'Key' => $s3Key, 'Body' => $imgData, 'ContentType' => 'image/jpeg']);

    $stmtStockage->execute([
        ':id'            => $uuid,
        ':titre'         => $titre,
        ':mime'          => 'image/jpeg',
        ':taille'        => round(strlen($imgData) / 1048576, 4),
        ':filename'      => 'photo.jpg',
        ':chemin'        => $s3Key,
        ':photographeId' => $ownerPhotographeId,
    ]);

    $allUploaded[$i] = ['photo_id' => $uuid, 's3_key' => $s3Key, 'titre' => $titre];
}

// Construction des galeries
$p1Id = $photographes[0]['id'];
$p2Id = $photographes[1]['id'];
$p3Id = $photographes[2]['id'];

$p1Prenom = $faker->firstNameFemale();
$p1Nom    = $faker->lastName();
$p2Prenom = $faker->firstNameFemale();
$p2Nom    = $faker->lastName();

$galeries = [
    // Photographe 1 - galerie publique publiee (8 photos)
    [
        'photographeId' => $p1Id,
        'id'            => Uuid::uuid4()->toString(),
        'titre'         => 'Mariage ' . $faker->firstNameFemale() . ' & ' . $faker->firstNameMale() . ' ' . $faker->lastName(),
        'description'   => 'Reportage complet ceremonie et soiree.',
        'type'          => 'publique',
        'mode'          => 'carrousel',
        'statut'        => 'publie',
        'photos'        => array_values(array_filter($allUploaded, fn($i) => $i >= 0 && $i <= 7, ARRAY_FILTER_USE_KEY)),
    ],
    // Photographe 1 - galerie privee NON publiee (6 photos)
    [
        'photographeId' => $p1Id,
        'id'            => Uuid::uuid4()->toString(),
        'titre'         => "Portraits - {$p1Prenom} {$p1Nom}",
        'description'   => 'Seance portrait studio - acces prive.',
        'type'          => 'privee',
        'mode'          => 'grille',
        'statut'        => 'brouillon',
        'photos'        => array_values(array_filter($allUploaded, fn($i) => $i >= 8 && $i <= 13, ARRAY_FILTER_USE_KEY)),
        'client'        => [
            'nom'   => "{$p1Prenom} {$p1Nom}",
            'email' => strtolower("{$p1Prenom}.{$p1Nom}@" . $faker->freeEmailDomain()),
            'tel'   => $faker->phoneNumber(),
            'code'  => 'portrait-' . strtolower($p1Prenom) . '-' . date('Y'),
        ],
    ],
    // Photographe 2 - galerie publique publiee (7 photos)
    [
        'photographeId' => $p2Id,
        'id'            => Uuid::uuid4()->toString(),
        'titre'         => "Paysages d'Alsace - Automne " . date('Y'),
        'description'   => 'Serie paysages de la route des vins.',
        'type'          => 'publique',
        'mode'          => 'grille',
        'statut'        => 'publie',
        'photos'        => array_values(array_filter($allUploaded, fn($i) => $i >= 14 && $i <= 20, ARRAY_FILTER_USE_KEY)),
    ],
    // Photographe 2 - galerie privee NON publiee (5 photos)
    [
        'photographeId' => $p2Id,
        'id'            => Uuid::uuid4()->toString(),
        'titre'         => "Seance - {$p2Prenom} {$p2Nom}",
        'description'   => 'Seance privee - non publiee.',
        'type'          => 'privee',
        'mode'          => 'grille',
        'statut'        => 'brouillon',
        'photos'        => array_values(array_filter($allUploaded, fn($i) => $i >= 21 && $i <= 25, ARRAY_FILTER_USE_KEY)),
        'client'        => [
            'nom'   => "{$p2Prenom} {$p2Nom}",
            'email' => strtolower("{$p2Prenom}.{$p2Nom}@" . $faker->freeEmailDomain()),
            'tel'   => $faker->phoneNumber(),
            'code'  => 'seance-' . strtolower($p2Prenom) . '-' . date('Y'),
        ],
    ],
    // Photographe 3 - galerie publique publiee (6 photos)
    [
        'photographeId' => $p3Id,
        'id'            => Uuid::uuid4()->toString(),
        'titre'         => 'Architecture Strasbourg ' . date('Y'),
        'description'   => 'Reportage architectural du centre historique.',
        'type'          => 'publique',
        'mode'          => 'grille',
        'statut'        => 'publie',
        'photos'        => array_values(array_filter($allUploaded, fn($i) => $i >= 26 && $i <= 31, ARRAY_FILTER_USE_KEY)),
    ],
];

echo "Insertion des galeries en base...\n";

$stmtGalerie = $galleryDb->prepare("
    INSERT INTO galerie (id, titre, description, type, mode_mise_en_page, statut, published_at, photographe_id)
    VALUES (:id, :titre, :desc, :type, :mode, :statut, :publishedAt, :photographeId)
");
$stmtPhotoProj = $galleryDb->prepare("
    INSERT INTO photo (id, chemin_s3, titre) VALUES (:id, :chemin, :titre)
    ON CONFLICT (id) DO NOTHING
");
$stmtGaleriePhoto = $galleryDb->prepare("
    INSERT INTO galerie_photo (galerie_id, photo_id, ordre)
    VALUES (:galerieId, :photoId, :ordre)
    ON CONFLICT DO NOTHING
");
$stmtPrivee = $galleryDb->prepare("
    INSERT INTO galerie_privee (id, galerie_id, nom_client, email_client, telephone_client, code_acces, url_acces)
    VALUES (:id, :galerieId, :nom, :email, :tel, :code, :url)
    ON CONFLICT (id) DO NOTHING
");

$now = (new DateTime())->format('Y-m-d H:i:s');

foreach ($galeries as $g) {
    $publishedAt = ($g['statut'] === 'publie') ? $now : null;

    $stmtGalerie->execute([
        ':id'            => $g['id'],
        ':titre'         => $g['titre'],
        ':desc'          => $g['description'],
        ':type'          => $g['type'],
        ':mode'          => $g['mode'],
        ':statut'        => $g['statut'],
        ':publishedAt'   => $publishedAt,
        ':photographeId' => $g['photographeId'],
    ]);

    foreach ($g['photos'] as $ordre => $photo) {
        $stmtPhotoProj->execute([':id' => $photo['photo_id'], ':chemin' => $photo['s3_key'], ':titre' => $photo['titre']]);
        $stmtGaleriePhoto->execute([':galerieId' => $g['id'], ':photoId' => $photo['photo_id'], ':ordre' => $ordre + 1]);
    }

    if (!empty($g['client'])) {
        $c   = $g['client'];
        $url = "{$frontofficeUrl}/galeries/{$g['id']}?code_acces={$c['code']}";
        $stmtPrivee->execute([
            ':id'        => Uuid::uuid4()->toString(),
            ':galerieId' => $g['id'],
            ':nom'       => $c['nom'],
            ':email'     => $c['email'],
            ':tel'       => $c['tel'],
            ':code'      => $c['code'],
            ':url'       => $url,
        ]);
        echo "  [{$g['statut']}] {$g['titre']} (prive, code: {$c['code']})\n";
    } else {
        echo "  [{$g['statut']}] {$g['titre']}\n";
    }
}

echo "\nTermine.\n";
echo "  {$total} photos uploadees\n";
echo "  " . count($galeries) . " galeries creees\n";
echo "  3 photographes :\n";
foreach ($photographes as $p) {
    echo "    {$p['email']} / {$p['password']}\n";
}