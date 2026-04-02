-- =========================================================
-- TABLE : photo
-- =========================================================
CREATE TABLE IF NOT EXISTS photo (
    id                   VARCHAR(36)  NOT NULL,
    titre                VARCHAR(255) NOT NULL,
    mime_type            VARCHAR(100) NOT NULL,
    taille_mo            FLOAT        NOT NULL,
    nom_fichier_original VARCHAR(255) NOT NULL,
    chemin_s3            VARCHAR(500) NOT NULL,
    uploaded_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    photographe_id       VARCHAR(36)  NOT NULL,

    CONSTRAINT pk_photo PRIMARY KEY (id),
    CONSTRAINT fk_photo_photographe
        FOREIGN KEY (photographe_id)
        REFERENCES photographe(id)
        ON DELETE CASCADE
);