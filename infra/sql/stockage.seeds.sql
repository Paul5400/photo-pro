-- ============================================================
-- SEEDS — stockage_db
-- photographe_id : af738448-6b65-4779-873e-8d8354040ca6
-- chemin_s3      : réutilise la seule vraie image uploadée
-- ============================================================

INSERT INTO photo (id, titre, mime_type, taille_mo, nom_fichier_original, chemin_s3, photographe_id) VALUES
-- Galerie 1 (Mariage Dupont)
('10000001-0000-4000-8000-000000000001', 'Cérémonie 1',   'image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
('10000001-0000-4000-8000-000000000002', 'Cérémonie 2',   'image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
('10000001-0000-4000-8000-000000000003', 'Cérémonie 3',   'image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
-- Galerie 2 (Paysages Alsace)
('10000001-0000-4000-8000-000000000004', 'Paysage 1',     'image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
('10000001-0000-4000-8000-000000000005', 'Paysage 2',     'image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
('10000001-0000-4000-8000-000000000006', 'Paysage 3',     'image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
-- Galerie 3 (Anniversaire Martin - privée)
('10000001-0000-4000-8000-000000000007', 'Anniversaire 1','image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
('10000001-0000-4000-8000-000000000008', 'Anniversaire 2','image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6'),
('10000001-0000-4000-8000-000000000009', 'Anniversaire 3','image/png', 0.5, 'test_image.png', 'users/af738448-6b65-4779-873e-8d8354040ca6/56d9b205-e1df-4a86-b2d1-ee02eb6086bc-test_image.png', 'af738448-6b65-4779-873e-8d8354040ca6')
ON CONFLICT (id) DO NOTHING;
