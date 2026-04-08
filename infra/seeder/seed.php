<?php
/**
 * Seeder Photo-Pro
 * Execution : .\infra\seeder\run.ps1
 */

require '/var/php/vendor/autoload.php';

use Aws\S3\S3Client;
use Faker\Factory as Faker;
use Ramsey\Uuid\Uuid;

$photographeId  = '9b0b0764-36d5-41e7-a7bc-9615ee75f054';
$bucket         = 'photo-pro';
$frontofficeUrl = 'http://localhost:8080';

$faker = Faker::create('fr_FR');

echo "Connexion aux bases de donnees et S3...\n";

$s3 = new S3Client([
    'version'                 => 'latest',
    'region'                  => 'us-east-1',
    'endpoint'                => 'http://S3:8333',
    'use_path_style_endpoint' => true,
    'credentials'             => ['key' => 'ABCDEF', 'secret' => '123456'],
]);

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

echo "Nettoyage des donnees precedentes...\n";

$galleryDb->exec("DELETE FROM photo_commentaire WHERE galerie_id IN (SELECT id FROM galerie WHERE photographe_id = '{$photographeId}')");
$galleryDb->exec("DELETE FROM galerie_photo     WHERE galerie_id IN (SELECT id::uuid FROM galerie WHERE photographe_id = '{$photographeId}')");
$galleryDb->exec("DELETE FROM galerie_privee    WHERE galerie_id IN (SELECT id FROM galerie WHERE photographe_id = '{$photographeId}')");
$galleryDb->exec("DELETE FROM galerie           WHERE photographe_id = '{$photographeId}'");
$galleryDb->exec("DELETE FROM photo");
$stockageDb->exec("DELETE FROM photo WHERE photographe_id = '{$photographeId}'");

$photoDefs = [
    [10, 1200, 800], [11, 1200, 800], [12, 800, 1200],
    [13, 1200, 800], [14, 800, 1200], [15, 1200, 800],
    [16, 1200, 800], [17, 800, 1200],
    [40, 1200, 800], [41, 1200, 800], [42, 1200, 800],
    [43, 1200, 800], [44, 800, 1200], [45, 1200, 800],
    [50, 800, 1200], [51, 1200, 800], [52, 800, 1200],
    [53, 1200, 800], [54, 1200, 800],
    [60, 800, 1200], [61, 800, 1200], [62, 800, 1200],
    [63, 800, 1200], [64, 1200, 800], [65, 800, 1200],
    [70, 1200, 800], [71, 1200, 800], [72, 1200, 800], [73, 1200, 800],
    [80, 1200, 800], [81, 1200, 800], [82, 1200, 800], [83, 1200, 800],
];

$total = count($photoDefs);
echo "Upload de {$total} photos dans S3...\n";

$stmtStockage = $stockageDb->prepare("
    INSERT INTO photo (id, titre, mime_type, taille_mo, nom_fichier_original, chemin_s3, photographe_id)
    VALUES (:id, :titre, :mime, :taille, :filename, :chemin, :photographeId)
    ON CONFLICT (id) DO NOTHING
");

$uploaded = [];
foreach ($photoDefs as $i => [$picsumId, $w, $h]) {
    $uuid  = Uuid::uuid4()->toString();
    $s3Key = "users/{$photographeId}/{$uuid}-photo.jpg";
    $titre = $faker->sentence(3, false);
    $n = $i + 1;
    echo "  ({$n}/{$total}) {$titre}\n";

    $data = @file_get_contents("https://picsum.photos/id/{$picsumId}/{$w}/{$h}");
    if ($data === false) {
        $data = file_get_contents("https://picsum.photos/{$w}/{$h}?random={$picsumId}");
    }

    $s3->putObject(['Bucket' => $bucket, 'Key' => $s3Key, 'Body' => $data, 'ContentType' => 'image/jpeg']);

    $stmtStockage->execute([
        ':id'            => $uuid,
        ':titre'         => $titre,
        ':mime'          => 'image/jpeg',
        ':taille'        => round(strlen($data) / 1048576, 4),
        ':filename'      => 'photo.jpg',
        ':chemin'        => $s3Key,
        ':photographeId' => $photographeId,
    ]);

    $uploaded[] = ['photo_id' => $uuid, 's3_key' => $s3Key, 'titre' => $titre];
}

$prenomMarie1   = $faker->firstNameFemale();
$prenomMarie2   = $faker->firstNameMale();
$nomFamille     = $faker->lastName();
$dateEvent      = $faker->dateTimeBetween('-6 months', '+6 months')->format('d/m/Y');
$prenomPortrait = $faker->firstNameFemale();
$nomPortrait    = $faker->lastName();
$societeGala    = $faker->company();
$nomBrut        = $faker->lastName();

$galeries = [
    [
        'id'          => Uuid::uuid4()->toString(),
        'titre'       => "Mariage {$prenomMarie1} & {$prenomMarie2} {$nomFamille}",
        'description' => "Ceremonie et soiree du {$dateEvent}. Reportage complet de la journee.",
        'type'        => 'publique',
        'mode'        => 'carrousel',
        'statut'      => 'publie',
        'photos'      => array_slice($uploaded, 0, 8),
    ],
    [
        'id'          => Uuid::uuid4()->toString(),
        'titre'       => "Paysages d'Alsace - Automne " . date('Y'),
        'description' => "Serie paysages de la route des vins et des Vosges alsaciennes.",
        'type'        => 'publique',
        'mode'        => 'grille',
        'statut'      => 'publie',
        'photos'      => array_slice($uploaded, 8, 6),
    ],
    [
        'id'          => Uuid::uuid4()->toString(),
        'titre'       => 'Architecture Strasbourg ' . date('Y'),
        'description' => "Reportage architectural du centre historique - en cours de selection.",
        'type'        => 'publique',
        'mode'        => 'grille',
        'statut'      => 'brouillon',
        'photos'      => array_slice($uploaded, 14, 5),
    ],
    [
        'id'          => Uuid::uuid4()->toString(),
        'titre'       => "Portraits - {$prenomPortrait} {$nomPortrait}",
        'description' => "Seance portrait studio lumiere naturelle - usage personnel.",
        'type'        => 'privee',
        'mode'        => 'grille',
        'statut'      => 'publie',
        'photos'      => array_slice($uploaded, 19, 6),
        'client'      => [
            'nom'   => "{$prenomPortrait} {$nomPortrait}",
            'email' => strtolower("{$prenomPortrait}.{$nomPortrait}@" . $faker->freeEmailDomain()),
            'tel'   => $faker->phoneNumber(),
            'code'  => 'portrait-' . strtolower($prenomPortrait) . '-' . date('Y'),
        ],
    ],
    [
        'id'          => Uuid::uuid4()->toString(),
        'titre'       => "Soiree Gala - {$societeGala}",
        'description' => "Gala annuel - acces reserve aux collaborateurs.",
        'type'        => 'privee',
        'mode'        => 'grille',
        'statut'      => 'publie',
        'photos'      => array_slice($uploaded, 25, 4),
        'client'      => [
            'nom'   => $societeGala,
            'email' => 'events@' . $faker->domainName(),
            'tel'   => $faker->phoneNumber(),
            'code'  => 'gala-' . date('Y'),
        ],
    ],
    [
        'id'          => Uuid::uuid4()->toString(),
        'titre'       => "Seance brute - Famille {$nomBrut}",
        'description' => "Photos avant retouche - non publie, usage interne.",
        'type'        => 'publique',
        'mode'        => 'grille',
        'statut'      => 'brouillon',
        'photos'      => array_slice($uploaded, 29, 4),
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
        ':photographeId' => $photographeId,
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
        echo "  {$g['titre']} [{$g['statut']}] code: {$c['code']}\n";
    } else {
        echo "  {$g['titre']} [{$g['statut']}]\n";
    }
}

echo "Termine. {$total} photos, " . count($galeries) . " galeries.\n";