
-- schéma à insérer
-- =========================================================
-- TABLE : galerie
-- =========================================================

CREATE TABLE IF NOT EXISTS galerie (
    id                  VARCHAR(36)  NOT NULL,
    titre               VARCHAR(255) NOT NULL,
    description         VARCHAR(255),
    type                VARCHAR(50)  NOT NULL,
    mode_mise_en_page   VARCHAR(50)  NOT NULL,
    statut              VARCHAR(50)  NOT NULL DEFAULT 'brouillon',
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    published_at        TIMESTAMP,
    photographe_id      VARCHAR(36)  NOT NULL,
    photo_couverture_id VARCHAR(36),

    CONSTRAINT pk_galerie PRIMARY KEY (id)
);
-- =========================================================
-- TABLE : galerie_photo
-- =========================================================

CREATE TABLE IF NOT EXISTS galerie_photo (
    galerie_id UUID  NOT NULL,
    photo_id   UUID NOT NULL,
    ordre      INT         NOT NULL DEFAULT 0,
    added_at   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_galerie_photo PRIMARY KEY (galerie_id, photo_id)
);

-- =========================================================
-- TABLE : galerie_privee
-- =========================================================
CREATE TABLE IF NOT EXISTS galerie_privee (

    id               VARCHAR(36)  NOT NULL,
    galerie_id       VARCHAR(36)  NOT NULL,
    nom_client       VARCHAR(255) NOT NULL,
    email_client     VARCHAR(255) NOT NULL,
    telephone_client VARCHAR(50),
    code_acces       VARCHAR(100) NOT NULL,
    url_acces        VARCHAR(500) NOT NULL,

    CONSTRAINT pk_galerie_privee PRIMARY KEY (id),
    CONSTRAINT uk_galerie_privee_galerie_id UNIQUE (galerie_id),
    CONSTRAINT uk_galerie_privee_url_acces  UNIQUE (url_acces)
);

